<?php
// pages/assets_maintenance.php - 资产维修
require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM Assets WHERE Id = ?");
$stmt->execute([$id]);
$asset = $stmt->fetch();

if (!$asset) {
    flash_set('未找到该资产');
    header('Location: ?p=assets');
    exit;
}

// 处理开始维修
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_maintenance'])) {
    require_csrf();

    $type = trim($_POST['type'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $cost = $_POST['cost'] ?? '';
    $notes = trim($_POST['notes'] ?? '');

    if (empty($type) || empty($description)) {
        flash_set('维修类型和故障描述为必填项');
    } else {
        // 保存原状态，维修完成后恢复
        $originalStatus = $asset['Status'];

        // 更新资产状态为维修中
        $pdo->prepare("UPDATE Assets SET Status = 'Maintenance' WHERE Id = ?")->execute([$id]);

        // 创建维修记录
        $currentUser = get_current_user_info();
        $stmt = $pdo->prepare("INSERT INTO Maintenance (AssetId, Type, Description, Cost, StartedAt, Status, Notes, OriginalStatus) VALUES (?, ?, ?, ?, NOW(), 'in_progress', ?, ?)");
        $stmt->execute([$id, $type, $description, $cost ?: null, $notes, $originalStatus]);

        // 记录交易
        $pdo->prepare("INSERT INTO AssetTransactions (AssetId, UserId, TransactionType, Notes, CreatedAt, OperatorId) VALUES (?, ?, 'Maintenance', ?, NOW(), ?)")
            ->execute([$id, null, "开始维修: {$type} - {$description}", $currentUser['id']]);

        // 记录审计日志
        $pdo->prepare("INSERT INTO AuditLog (UserId, Action, ResourceType, ResourceId, Details, IpAddress) VALUES (?, 'maintenance', 'asset', ?, ?, ?)")
            ->execute([$currentUser['id'], $id, "资产维修: {$asset['Name']} - {$type}", $_SERVER['REMOTE_ADDR'] ?? '']);

        flash_set('维修记录已创建,资产状态已更新');
        header("Location: ?p=asset_details&id={$id}");
        exit;
    }
}

// 处理完成维修
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_maintenance'])) {
    require_csrf();

    $maintenanceId = (int)$_POST['maintenance_id'];
    $result = $_POST['result'] ?? '';
    $notes = trim($_POST['notes'] ?? '');

    // 获取维修记录
    $stmt = $pdo->prepare("SELECT * FROM Maintenance WHERE Id = ?");
    $stmt->execute([$maintenanceId]);
    $maintenance = $stmt->fetch();

    if (!$maintenance) {
        flash_set('未找到维修记录');
        header("Location: ?p=asset_details&id={$id}");
        exit;
    }

    // 更新维修记录
    $currentUser = get_current_user_info();
    $stmt = $pdo->prepare("UPDATE Maintenance SET Status = 'completed', CompletedAt = NOW(), Notes = CONCAT(Notes, ?) WHERE Id = ?");
    $stmt->execute(["\n完成备注: " . $notes, $maintenanceId]);

    // 恢复资产到原状态
    $originalStatus = $maintenance['OriginalStatus'] ?: 'InStock';
    $pdo->prepare("UPDATE Assets SET Status = ? WHERE Id = ?")->execute([$originalStatus, $id]);

    // 记录交易
    $pdo->prepare("INSERT INTO AssetTransactions (AssetId, UserId, TransactionType, Notes, CreatedAt, OperatorId) VALUES (?, ?, 'MaintenanceComplete', ?, NOW(), ?)")
        ->execute([$id, null, "维修完成: {$result} - {$notes}", $currentUser['id']]);

    // 记录审计日志
    $pdo->prepare("INSERT INTO AuditLog (UserId, Action, ResourceType, ResourceId, Details, IpAddress) VALUES (?, 'maintenance_complete', 'asset', ?, ?, ?)")
        ->execute([$currentUser['id'], $id, "维修完成: {$asset['Name']} - 恢复到" . status_text($originalStatus), $_SERVER['REMOTE_ADDR'] ?? '']);

    flash_set('维修已完成，资产状态已恢复');
    header("Location: ?p=asset_details&id={$id}");
    exit;
}

// 获取历史维修记录
$history = $pdo->prepare("SELECT * FROM Maintenance WHERE AssetId = ? ORDER BY StartedAt DESC LIMIT 10");
$history->execute([$id]);
$historyList = $history->fetchAll();

// 获取进行中的维修记录
$activeMaintenance = $pdo->prepare("SELECT * FROM Maintenance WHERE AssetId = ? AND Status = 'in_progress' LIMIT 1");
$activeMaintenance->execute([$id]);
$active = $activeMaintenance->fetch();
?>
<?php include __DIR__ . '/../templates/header.php'; ?>

<?php if ($active): ?>
<!-- 完成维修表单 -->
<div class="row justify-content-center">
  <div class="col-md-8">
    <div class="card border-info">
      <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="bi bi-check-circle"></i> 完成维修</h5>
      </div>
      <div class="card-body">
        <div class="alert alert-info">
          <strong><?= h($asset['Name']) ?></strong> (标签: <code><?= h($asset['Tag']) ?></code>)
          <br>当前状态: <span class="badge <?= status_class($asset['Status']) ?>"><?= status_text($asset['Status']) ?></span>
          <br><strong>维修类型:</strong> <?= h($active['Type']) ?>
        </div>
        <form method="post" class="row g-3">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>" />
          <input type="hidden" name="maintenance_id" value="<?= $active['Id'] ?>" />
          <div class="col-md-6">
            <label class="form-label">维修结果 <span class="text-danger">*</span></label>
            <select class="form-select" name="result" required>
              <option value="修复完成">修复完成</option>
              <option value="更换配件">更换配件</option>
              <option value="无法修复">无法修复</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">维修费用</label>
            <input type="number" step="0.01" class="form-control" value="<?= $active['Cost'] ?: '' ?>" disabled />
            <small class="text-muted">开始维修时已设置</small>
          </div>
          <div class="col-12">
            <label class="form-label">完成备注</label>
            <textarea class="form-control" name="notes" rows="3" placeholder="请填写维修完成情况..."></textarea>
          </div>
          <div class="col-12">
            <div class="alert alert-warning">
              <strong>提示:</strong> 维修完成后，资产将恢复到原状态: <strong><?= status_text($active['OriginalStatus'] ?: '在库') ?></strong>
            </div>
          </div>
          <div class="col-12">
            <button class="btn btn-success" type="submit" name="complete_maintenance"><i class="bi bi-check-circle"></i> 确认完成</button>
            <a class="btn btn-secondary" href="?p=asset_details&id=<?= $id ?>">取消</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php else: ?>
<!-- 开始维修表单 -->
<div class="row justify-content-center">
  <div class="col-md-8">
    <div class="card">
      <div class="card-header">
        <h5><i class="bi bi-tools"></i> 资产维修</h5>
      </div>
      <div class="card-body">
        <div class="alert alert-info">
          <strong><?= h($asset['Name']) ?></strong> (标签: <code><?= h($asset['Tag']) ?></code>)
          <br>当前状态: <span class="badge <?= status_class($asset['Status']) ?>"><?= status_text($asset['Status']) ?></span>
        </div>
        <form method="post" class="row g-3">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>" />
          <div class="col-md-6">
            <label class="form-label">维修类型 <span class="text-danger">*</span></label>
            <select class="form-select" name="type" required>
              <option value="">请选择类型</option>
              <option value="硬件故障">硬件故障</option>
              <option value="软件问题">软件问题</option>
              <option value="定期保养">定期保养</option>
              <option value="意外损坏">意外损坏</option>
              <option value="其他">其他</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">维修费用</label>
            <input type="number" step="0.01" class="form-control" name="cost" placeholder="¥" />
          </div>
          <div class="col-12">
            <label class="form-label">故障描述 <span class="text-danger">*</span></label>
            <textarea class="form-control" name="description" rows="3" required placeholder="请详细描述故障情况..."></textarea>
          </div>
          <div class="col-12">
            <label class="form-label">备注</label>
            <textarea class="form-control" name="notes" rows="2" placeholder="其他说明..."></textarea>
          </div>
          <div class="col-12">
            <div class="alert alert-warning">
              <strong>提示:</strong> 维修完成后，资产将自动恢复到维修前的状态
            </div>
          </div>
          <div class="col-12">
            <button class="btn btn-info" type="submit" name="start_maintenance"><i class="bi bi-tools"></i> 开始维修</button>
            <a class="btn btn-secondary" href="?p=asset_details&id=<?= $id ?>">取消</a>
          </div>
        </form>
      </div>
    </div>

    <!-- 历史维修记录 -->
    <?php if (!empty($historyList)): ?>
    <div class="card mt-3">
      <div class="card-header">
        <h6 class="mb-0">历史维修记录</h6>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead><tr><th>类型</th><th>描述</th><th>费用</th><th>状态</th><th>时间</th></tr></thead>
            <tbody>
              <?php foreach ($historyList as $h): ?>
                <tr>
                  <td><?= h($h['Type']) ?></td>
                  <td><small><?= h($h['Description']) ?></small></td>
                  <td><?= $h['Cost'] ? '¥' . number_format($h['Cost'], 2) : '-' ?></td>
                  <td>
                    <span class="badge bg-<?= $h['Status'] === 'completed' ? 'success' : ($h['Status'] === 'in_progress' ? 'warning' : 'secondary') ?>">
                      <?= $h['Status'] === 'completed' ? '已完成' : ($h['Status'] === 'in_progress' ? '进行中' : $h['Status']) ?>
                    </span>
                  </td>
                  <td><small><?= h($h['StartedAt']) ?></small></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../templates/footer.php'; ?>

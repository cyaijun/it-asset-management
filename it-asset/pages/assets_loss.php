<?php
// pages/assets_loss.php - 资产丢失
require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT a.*, u.FullName, u.Username FROM Assets a LEFT JOIN Users u ON a.OwnerId = u.Id WHERE a.Id = ?");
$stmt->execute([$id]);
$asset = $stmt->fetch();

if (!$asset) {
    flash_set('未找到该资产');
    header('Location: ?p=assets');
    exit;
}

if ($asset['Status'] === 'Disposed' || $asset['Status'] === 'Lost') {
    flash_set('该资产已' . status_text($asset['Status']) . ',无法标记为丢失');
    header("Location: ?p=asset_details&id={$id}");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $lossType = trim($_POST['loss_type'] ?? '');
    $reason = trim($_POST['reason'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (empty($lossType) || empty($reason)) {
        flash_set('丢失类型和丢失原因为必填项');
    } else {
        // 更新资产状态
        $pdo->prepare("UPDATE Assets SET Status = 'Lost', OwnerId = NULL WHERE Id = ?")->execute([$id]);

        // 创建丢失记录
        $currentUser = get_current_user_info();
        $stmt = $pdo->prepare("INSERT INTO LossRecords (AssetId, LossType, Reason, Location, LostAt, OperatorId, Notes) VALUES (?, ?, ?, ?, NOW(), ?, ?)");
        $stmt->execute([$id, $lossType, $reason, $location, $currentUser['id'], $notes]);

        // 记录交易
        $pdo->prepare("INSERT INTO AssetTransactions (AssetId, UserId, TransactionType, Notes, CreatedAt, OperatorId) VALUES (?, ?, 'Loss', ?, NOW(), ?)")
            ->execute([$id, null, "资产丢失: {$lossType} - {$reason}", $currentUser['id']]);

        // 记录审计日志
        $pdo->prepare("INSERT INTO AuditLog (UserId, Action, ResourceType, ResourceId, Details, IpAddress) VALUES (?, 'loss', 'asset', ?, ?, ?)")
            ->execute([$currentUser['id'], $id, "资产丢失: {$asset['Name']} - {$lossType}", $_SERVER['REMOTE_ADDR'] ?? '']);

        flash_set('资产已标记为丢失');
        header("Location: ?p=asset_details&id={$id}");
        exit;
    }
}

$lossTypeOptions = [
    '遗失' => '遗失',
    '被盗' => '被盗',
    '其他' => '其他',
];
?>
<?php include __DIR__ . '/../templates/header.php'; ?>
<div class="row justify-content-center">
  <div class="col-md-8">
    <div class="card border-danger">
      <div class="card-header bg-danger text-white">
        <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> 资产丢失</h5>
      </div>
      <div class="card-body">
        <div class="alert alert-warning">
          <strong>⚠️ 警告:</strong> 此操作将资产状态设置为"已丢失",资产将无法继续使用!
        </div>
        <div class="alert alert-info">
          <strong><?= h($asset['Name']) ?></strong> (标签: <code><?= h($asset['Tag']) ?></code>)
          <br>当前状态: <span class="badge <?= status_class($asset['Status']) ?>"><?= status_text($asset['Status']) ?></span>
          <?php if ($asset['OwnerId']): ?>
            <br>当前归属: <?= h($asset['FullName']) ?> (<?= h($asset['Username']) ?>)
          <?php endif; ?>
        </div>
        <form method="post" class="row g-3">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>" />
          <div class="col-md-6">
            <label class="form-label">丢失类型 <span class="text-danger">*</span></label>
            <select class="form-select" name="loss_type" required>
              <option value="">请选择类型</option>
              <?php foreach ($lossTypeOptions as $val => $label): ?>
                <option value="<?= $val ?>"><?= h($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">丢失地点</label>
            <input type="text" class="form-control" name="location" placeholder="如：办公室A区会议室" />
          </div>
          <div class="col-12">
            <label class="form-label">丢失原因/经过 <span class="text-danger">*</span></label>
            <textarea class="form-control" name="reason" rows="3" placeholder="请详细说明资产丢失的原因和经过..." required></textarea>
          </div>
          <div class="col-12">
            <label class="form-label">备注说明</label>
            <textarea class="form-control" name="notes" rows="2" placeholder="其他需要说明的情况..."></textarea>
          </div>
          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="confirm" required />
              <label class="form-check-label" for="confirm">
                我确认要将此资产标记为丢失
              </label>
            </div>
          </div>
          <div class="col-12">
            <button class="btn btn-danger" type="submit"><i class="bi bi-exclamation-triangle"></i> 确认标记丢失</button>
            <a class="btn btn-secondary" href="?p=asset_details&id=<?= $id ?>">取消</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../templates/footer.php'; ?>

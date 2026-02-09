<?php
// pages/assets_dispose.php - 资产报废
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $reason = trim($_POST['reason'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (empty($reason)) {
        flash_set('报废原因不能为空');
    } else {
        // 更新资产状态
        $pdo->prepare("UPDATE Assets SET Status = 'Disposed', OwnerId = NULL WHERE Id = ?")->execute([$id]);

        // 创建报废记录
        $currentUser = get_current_user_info();
        $stmt = $pdo->prepare("INSERT INTO Disposals (AssetId, Reason, DisposedAt, OperatorId, Notes) VALUES (?, ?, NOW(), ?, ?)");
        $stmt->execute([$id, $reason, $currentUser['id'], $notes]);

        // 记录交易
        $pdo->prepare("INSERT INTO AssetTransactions (AssetId, UserId, TransactionType, Notes, CreatedAt, OperatorId) VALUES (?, ?, 'Dispose', ?, NOW(), ?)")
            ->execute([$id, null, "资产报废: {$reason}", $currentUser['id']]);

        // 记录审计日志
        $pdo->prepare("INSERT INTO AuditLog (UserId, Action, ResourceType, ResourceId, Details, IpAddress) VALUES (?, 'dispose', 'asset', ?, ?, ?)")
            ->execute([$currentUser['id'], $id, "资产报废: {$asset['Name']} - {$reason}", $_SERVER['REMOTE_ADDR'] ?? '']);

        flash_set('资产已报废');
        header('Location: ?p=assets');
        exit;
    }
}
?>
<?php include __DIR__ . '/../templates/header.php'; ?>
<div class="row justify-content-center">
  <div class="col-md-8">
    <div class="card border-danger">
      <div class="card-header bg-danger text-white">
        <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> 资产报废</h5>
      </div>
      <div class="card-body">
        <div class="alert alert-warning">
          <strong>⚠️ 警告:</strong> 此操作将资产状态设置为"已报废",此操作不可撤销!
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
          <div class="col-12">
            <label class="form-label">报废原因 <span class="text-danger">*</span></label>
            <select class="form-select" name="reason" required>
              <option value="">请选择原因</option>
              <option value="设备老化无法使用">设备老化无法使用</option>
              <option value="损坏无法修复">损坏无法修复</option>
              <option value="技术落后被淘汰">技术落后被淘汰</option>
              <option value="丢失">丢失</option>
              <option value="被盗">被盗</option>
              <option value="其他">其他</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">详细说明</label>
            <textarea class="form-control" name="notes" rows="4" placeholder="请详细说明报废情况..."></textarea>
          </div>
          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="confirm" required />
              <label class="form-check-label" for="confirm">
                我确认要将此资产报废,此操作不可撤销
              </label>
            </div>
          </div>
          <div class="col-12">
            <button class="btn btn-danger" type="submit"><i class="bi bi-trash"></i> 确认报废</button>
            <a class="btn btn-secondary" href="?p=asset_details&id=<?= $id ?>">取消</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../templates/footer.php'; ?>

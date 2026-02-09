<?php
// pages/asset_delete.php - 资产删除
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    // 删除资产(相关记录会因外键约束自动删除)
    $pdo->prepare("DELETE FROM Assets WHERE Id = ?")->execute([$id]);

    // 记录审计日志
    $currentUser = get_current_user_info();
    $pdo->prepare("INSERT INTO AuditLog (UserId, Action, ResourceType, Details, IpAddress) VALUES (?, 'delete', 'asset', ?, ?)")
        ->execute([$currentUser['id'], "删除资产: {$asset['Name']} (Tag: {$asset['Tag']})", $_SERVER['REMOTE_ADDR'] ?? '']);

    flash_set('资产已删除');
    header('Location: ?p=assets');
    exit;
}

include __DIR__ . '/../templates/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card border-danger">
      <div class="card-header bg-danger text-white">
        <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> 确认删除</h5>
      </div>
      <div class="card-body">
        <div class="alert alert-warning">
          <strong>⚠️ 警告:</strong> 此操作将永久删除该资产及其所有相关记录,此操作不可撤销!
        </div>
        <p>您确定要删除以下资产吗?</p>
        <ul>
          <li><strong>名称:</strong> <?= h($asset['Name']) ?></li>
          <li><strong>标签:</strong> <code><?= h($asset['Tag']) ?></code></li>
          <li><strong>型号:</strong> <?= h($asset['Model'] ?: '-') ?></li>
          <li><strong>序列号:</strong> <?= h($asset['SerialNumber'] ?: '-') ?></li>
        </ul>
        <form method="post" class="mt-4">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>" />
          <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i> 确认删除</button>
          <a href="?p=asset_details&id=<?= $id ?>" class="btn btn-secondary">取消</a>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../templates/footer.php'; ?>

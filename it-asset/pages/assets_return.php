<?php
// pages/assets_return.php - 资产归还

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT a.*, u.FullName, u.Username FROM Assets a LEFT JOIN Users u ON a.OwnerId = u.Id WHERE a.Id = ?");
$stmt->execute([$id]);
$asset = $stmt->fetch();

if (!$asset) {
    flash_set('未找到该资产');
    header('Location: ?p=assets');
    exit;
}

// 检查权限：管理员可以归还任何资产，user只能归还自己的资产
$currentUser = get_current_user_info();
if (!is_admin() && $asset['OwnerId'] != $currentUser['id']) {
    flash_set('您没有权限归还此资产');
    header("Location: ?p=assets");
    exit;
}

if (!$asset) {
    flash_set('未找到该资产');
    header('Location: ?p=assets');
    exit;
}

if (!$asset['OwnerId'] || $asset['Status'] !== 'Assigned') {
    flash_set('该资产未被领用,无需归还');
    header("Location: ?p=asset_details&id={$id}");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $notes = trim($_POST['notes'] ?? '');
    $prevOwner = $asset['OwnerId'];
    $condition = $_POST['condition'] ?? 'good';

    $pdo->prepare("UPDATE Assets SET OwnerId = NULL, Status = 'InStock' WHERE Id = ?")->execute([$id]);
    $currentUser = get_current_user_info();
    $pdo->prepare("INSERT INTO AssetTransactions (AssetId, UserId, TransactionType, Notes, CreatedAt, OperatorId) VALUES (?, ?, 'CheckIn', ?, NOW(), ?)")
        ->execute([$id, $prevOwner, "归还 (状态: {$condition}) " . ($notes ? "- {$notes}" : ''), $currentUser['id']]);

    // 记录审计日志
    $pdo->prepare("INSERT INTO AuditLog (UserId, Action, ResourceType, ResourceId, Details, IpAddress) VALUES (?, 'checkin', 'asset', ?, ?, ?)")
        ->execute([$currentUser['id'], $id, "资产归还: {$asset['Name']} from {$asset['FullName']}", $_SERVER['REMOTE_ADDR'] ?? '']);

    flash_set('归还成功');
    header("Location: ?p=asset_details&id={$id}");
    exit;
}

$conditionOptions = [
    'good' => '完好',
    'minor' => '轻微磨损',
    'damaged' => '损坏',
    'need_repair' => '需要维修',
];
?>
<?php include __DIR__ . '/../templates/header.php'; ?>
<div class="row justify-content-center">
  <div class="col-md-8">
    <div class="card">
      <div class="card-header">
        <h5><i class="bi bi-box-arrow-in-left"></i> 资产归还</h5>
      </div>
      <div class="card-body">
        <div class="alert alert-info">
          <strong><?= h($asset['Name']) ?></strong> (标签: <code><?= h($asset['Tag']) ?></code>)
          <br>当前归属: <strong><?= h($asset['FullName']) ?></strong> (<?= h($asset['Username']) ?>)
        </div>
        <form method="post" class="row g-3">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>" />
          <div class="col-md-6">
            <label class="form-label">资产状态 <span class="text-danger">*</span></label>
            <select class="form-select" name="condition" required>
              <?php foreach ($conditionOptions as $val => $label): ?>
                <option value="<?= $val ?>"><?= h($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">归还日期</label>
            <input type="date" class="form-control" value="<?= date('Y-m-d') ?>" disabled />
          </div>
          <div class="col-12">
            <label class="form-label">备注</label>
            <textarea class="form-control" name="notes" rows="3" placeholder="如有损坏请详细说明..."></textarea>
          </div>
          <div class="col-12">
            <button class="btn btn-warning" type="submit"><i class="bi bi-check-circle"></i> 确认归还</button>
            <a class="btn btn-secondary" href="?p=asset_details&id=<?= $id ?>">取消</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../templates/footer.php'; ?>
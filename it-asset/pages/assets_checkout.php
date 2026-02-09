<?php
// pages/assets_checkout.php - 资产领用

// 只有管理员可以进行资产领用操作
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

if ($asset['Status'] === 'Disposed' || $asset['Status'] === 'Lost' || $asset['Status'] === 'Maintenance') {
    flash_set('该资产已' . status_text($asset['Status']) . ',无法领用');
    header('Location: ?p=asset_details&id=' . $id);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $userId = (int)$_POST['userId'];
    $notes = trim($_POST['notes'] ?? '');
    $expectedReturnDate = $_POST['expectedReturnDate'] ?? '';

    $u = $pdo->prepare("SELECT Id, FullName, Username FROM Users WHERE Id = ? AND Status = 'active'");
    $u->execute([$userId]);
    $user = $u->fetch();

    if (!$user) {
        flash_set('用户不存在或已被禁用');
        header("Location: ?p=asset_checkout&id={$id}");
        exit;
    }

    $pdo->prepare("UPDATE Assets SET OwnerId = ?, Status = 'Assigned' WHERE Id = ?")->execute([$userId, $id]);
    $currentUser = get_current_user_info();
    $pdo->prepare("INSERT INTO AssetTransactions (AssetId, UserId, TransactionType, Notes, CreatedAt, OperatorId) VALUES (?, ?, 'CheckOut', ?, NOW(), ?)")
        ->execute([$id, $userId, $notes ?: "领用给 {$user['FullName']} ({$user['Username']})", $currentUser['id']]);

    // 记录审计日志
    $pdo->prepare("INSERT INTO AuditLog (UserId, Action, ResourceType, ResourceId, Details, IpAddress) VALUES (?, 'checkout', 'asset', ?, ?, ?)")
        ->execute([$currentUser['id'], $id, "资产领用: {$asset['Name']} -> {$user['FullName']}", $_SERVER['REMOTE_ADDR'] ?? '']);

    flash_set('领用成功');
    header("Location: ?p=asset_details&id={$id}");
    exit;
}

// 获取所有激活用户
$users = $pdo->query("SELECT Id, Username, FullName, Department FROM Users WHERE Status = 'active' ORDER BY FullName")->fetchAll();
?>
<?php include __DIR__ . '/../templates/header.php'; ?>
<div class="row justify-content-center">
  <div class="col-md-8">
    <div class="card">
      <div class="card-header">
        <h5><i class="bi bi-box-arrow-right"></i> 资产领用</h5>
      </div>
      <div class="card-body">
        <div class="alert alert-info">
          <strong><?= h($asset['Name']) ?></strong> (标签: <code><?= h($asset['Tag']) ?></code>)
          <br><small>当前状态: <span class="badge <?= status_class($asset['Status']) ?>"><?= status_text($asset['Status']) ?></span></small>
        </div>
        <form method="post" class="row g-3">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>" />
          <div class="col-md-6">
            <label class="form-label">领用人 <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="userIdDisplay" id="userSelect" placeholder="输入姓名或用户名搜索..." required autocomplete="off" list="userList" />
            <input type="hidden" name="userId" id="userIdHidden" />
            <datalist id="userList">
              <?php foreach ($users as $u): ?>
                <option value="<?= $u['FullName'] ?><?= $u['Department'] ? " ({$u['Department']})" : '' ?>" data-id="<?= $u['Id'] ?>" data-username="<?= h($u['Username']) ?>">
                </option>
              <?php endforeach; ?>
            </datalist>
            <div id="selectedUser" class="mt-2" style="display:none;">
              <span class="badge bg-success">
                <i class="bi bi-person-check"></i> <span id="selectedUserName"></span>
                <button type="button" class="btn-close btn-close-white ms-2" onclick="clearUserSelection()"></button>
              </span>
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label">预计归还日期</label>
            <input type="date" class="form-control" name="expectedReturnDate" />
          </div>
          <div class="col-12">
            <label class="form-label">备注</label>
            <textarea class="form-control" name="notes" rows="3" placeholder="请填写领用说明..."></textarea>
          </div>
          <div class="col-12">
            <button class="btn btn-success" type="submit"><i class="bi bi-check-circle"></i> 确认领用</button>
            <a class="btn btn-secondary" href="?p=asset_details&id=<?= $id ?>">取消</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<script>
// 用户数据存储
const userData = {
    <?php foreach ($users as $u): ?>
    '<?= h($u['FullName'] . ($u['Department'] ? " ({$u['Department']})" : '')) ?>': { id: <?= $u['Id'] ?>, username: '<?= h($u['Username']) ?>' },
    <?php endforeach; ?>
};

const userSelect = document.getElementById('userSelect');
const userIdHidden = document.getElementById('userIdHidden');
const selectedUser = document.getElementById('selectedUser');
const selectedUserName = document.getElementById('selectedUserName');

// 监听用户选择变化
userSelect.addEventListener('input', function() {
    const displayName = this.value;

    // 检查输入的是否是有效的用户显示名称
    if (userData[displayName]) {
        // 显示选中的用户信息
        selectedUser.style.display = 'block';
        selectedUserName.textContent = displayName;
        userIdHidden.value = userData[displayName].id; // 设置隐藏字段为用户ID
    } else {
        // 隐藏选中信息，表示用户正在输入搜索
        selectedUser.style.display = 'none';
        userIdHidden.value = '';
    }
});

// 清除用户选择
function clearUserSelection() {
    userSelect.value = '';
    userIdHidden.value = '';
    selectedUser.style.display = 'none';
    userSelect.focus();
}

// 表单提交前验证
document.querySelector('form').addEventListener('submit', function(e) {
    const userId = userIdHidden.value;

    if (!userId) {
        e.preventDefault();
        alert('请从下拉列表中选择一个有效的用户');
        return false;
    }
});
</script>
<?php include __DIR__ . '/../templates/footer.php'; ?>

<?php
// pages/assets_transfer.php - 资产转移
require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT a.*, u.FullName as CurrentOwnerName, u.Username as CurrentUsername, u.Department as CurrentDepartment
                      FROM Assets a
                      LEFT JOIN Users u ON a.OwnerId = u.Id
                      WHERE a.Id = ?");
$stmt->execute([$id]);
$asset = $stmt->fetch();

if (!$asset) {
    flash_set('未找到该资产');
    header('Location: ?p=assets');
    exit;
}

if (!$asset['OwnerId'] || $asset['Status'] !== 'Assigned') {
    flash_set('该资产未被领用,无法转移');
    header("Location: ?p=asset_details&id={$id}");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $toUserId = (int)$_POST['toUserId'];
    $notes = trim($_POST['notes'] ?? '');

    // 检查目标用户
    $u = $pdo->prepare("SELECT Id, FullName, Username, Department FROM Users WHERE Id = ? AND Status = 'active'");
    $u->execute([$toUserId]);
    $toUser = $u->fetch();

    if (!$toUser) {
        flash_set('目标用户不存在或已被禁用');
        header("Location: ?p=asset_transfer&id={$id}");
        exit;
    }

    if ($toUserId === $asset['OwnerId']) {
        flash_set('不能将资产转移给当前持有者');
        header("Location: ?p=asset_transfer&id={$id}");
        exit;
    }

    // 获取当前用户
    $currentUser = get_current_user_info();

    // 更新资产归属
    $pdo->prepare("UPDATE Assets SET OwnerId = ? WHERE Id = ?")->execute([$toUserId, $id]);

    // 记录交易
    $transactionNotes = "从 {$asset['CurrentOwnerName']} ({$asset['CurrentUsername']}) 转移给 {$toUser['FullName']} ({$toUser['Username']})";
    if ($notes) {
        $transactionNotes .= " - 备注: {$notes}";
    }
    $pdo->prepare("INSERT INTO AssetTransactions (AssetId, UserId, TransactionType, Notes, CreatedAt, OperatorId) VALUES (?, ?, 'Transfer', ?, NOW(), ?)")
        ->execute([$id, $toUserId, $transactionNotes, $currentUser['id']]);

    // 记录审计日志
    $pdo->prepare("INSERT INTO AuditLog (UserId, Action, ResourceType, ResourceId, Details, IpAddress) VALUES (?, 'transfer', 'asset', ?, ?, ?)")
        ->execute([$currentUser['id'], $id, "资产转移: {$asset['Name']} - {$transactionNotes}", $_SERVER['REMOTE_ADDR'] ?? '']);

    flash_set('资产转移成功');
    header("Location: ?p=asset_details&id={$id}");
    exit;
}

// 获取所有激活用户（排除当前持有者）
$users = $pdo->query("SELECT Id, Username, FullName, Department FROM Users WHERE Status = 'active' AND Id != " . (int)$asset['OwnerId'] . " ORDER BY FullName")->fetchAll();
?>
<?php include __DIR__ . '/../templates/header.php'; ?>
<div class="row justify-content-center">
  <div class="col-md-8">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-arrow-left-right"></i> 资产转移</h5>
      </div>
      <div class="card-body">
        <div class="alert alert-info">
          <strong><?= h($asset['Name']) ?></strong> (标签: <code><?= h($asset['Tag']) ?></code>)
          <br>当前归属: <strong><?= h($asset['CurrentOwnerName']) ?></strong>
          <?php if ($asset['CurrentDepartment']): ?>
            <small class="text-muted">(<?= h($asset['CurrentDepartment']) ?>)</small>
          <?php endif; ?>
          <?php if ($asset['CurrentUsername']): ?>
            <small class="text-muted"> - <?= h($asset['CurrentUsername']) ?></small>
          <?php endif; ?>
        </div>
        <form method="post" class="row g-3">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>" />
          <div class="col-12">
            <label class="form-label">目标用户 <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="toUserIdDisplay" id="userSelect" placeholder="输入姓名或用户名搜索..." required autocomplete="off" list="userList" />
            <input type="hidden" name="toUserId" id="userIdHidden" />
            <datalist id="userList">
              <?php foreach ($users as $u): ?>
                <option value="<?= $u['FullName'] ?><?= $u['Department'] ? " ({$u['Department']})" : '' ?>" data-id="<?= $u['Id'] ?>" data-username="<?= h($u['Username']) ?>">
                </option>
              <?php endforeach; ?>
            </datalist>
            <div id="selectedUser" class="mt-2" style="display:none;">
              <span class="badge bg-primary">
                <i class="bi bi-person-check"></i> <span id="selectedUserName"></span>
                <button type="button" class="btn-close btn-close-white ms-2" onclick="clearUserSelection()"></button>
              </span>
            </div>
          </div>
          <div class="col-12">
            <label class="form-label">转移说明</label>
            <textarea class="form-control" name="notes" rows="3" placeholder="请填写转移原因或说明..."></textarea>
          </div>
          <div class="col-12">
            <div class="alert alert-warning">
              <strong>⚠️ 注意:</strong> 此操作将资产从当前持有者转移到目标用户,系统会自动记录此操作。
            </div>
          </div>
          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="confirm" required />
              <label class="form-check-label" for="confirm">
                我确认要将此资产转移给目标用户
              </label>
            </div>
          </div>
          <div class="col-12">
            <button class="btn btn-primary" type="submit"><i class="bi bi-arrow-left-right"></i> 确认转移</button>
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

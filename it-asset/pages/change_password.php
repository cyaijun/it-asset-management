<?php
// pages/change_password.php - 修改密码

require_login();

// 获取当前用户
$currentUser = get_current_user_info();
$isAdmin = is_admin();
$targetUserId = isset($_GET['id']) ? (int)$_GET['id'] : $currentUser['id'];

// 非管理员只能修改自己的密码
if (!$isAdmin && $targetUserId !== $currentUser['id']) {
    flash_set('您只能修改自己的密码', 'error');
    header('Location: ?p=assets');
    exit;
}

// 如果是管理员，验证目标用户存在
if ($isAdmin && $targetUserId !== $currentUser['id']) {
    $stmt = $pdo->prepare("SELECT * FROM Users WHERE Id = ?");
    $stmt->execute([$targetUserId]);
    $targetUser = $stmt->fetch();
    if (!$targetUser) {
        flash_set('用户不存在', 'error');
        header('Location: ?p=users');
        exit;
    }
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    // 如果修改的是自己的密码，需要验证旧密码
    if ($targetUserId === $currentUser['id']) {
        $oldPassword = trim($_POST['old_password'] ?? '');
        if (empty($oldPassword)) {
            $errors[] = '请输入当前密码';
        } else {
            // 验证旧密码
            $stmt = $pdo->prepare("SELECT Password FROM Users WHERE Id = ?");
            $stmt->execute([$currentUser['id']]);
            $userData = $stmt->fetch();
            if (!password_verify($oldPassword, $userData['Password'] ?? '')) {
                $errors[] = '当前密码不正确';
            }
        }
    }

    $newPassword = trim($_POST['new_password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    // 验证新密码
    if (empty($newPassword)) {
        $errors[] = '请输入新密码';
    } elseif (strlen($newPassword) < 6) {
        $errors[] = '新密码长度至少为6位';
    }

    if (empty($confirmPassword)) {
        $errors[] = '请确认新密码';
    } elseif ($newPassword !== $confirmPassword) {
        $errors[] = '两次输入的密码不一致';
    }

    if (empty($errors)) {
        // 更新密码
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE Users SET Password = ? WHERE Id = ?");
        $stmt->execute([$passwordHash, $targetUserId]);

        // 记录审计日志
        $targetName = $targetUserId === $currentUser['id'] ? $currentUser['username'] : ($targetUser['Username'] ?? $targetUserId);
        $details = $targetUserId === $currentUser['id']
            ? "用户修改了自己的密码"
            : "管理员 {$currentUser['username']} 修改了用户 {$targetName} 的密码";
        $pdo->prepare("INSERT INTO AuditLog (UserId, Action, ResourceType, ResourceId, Details, IpAddress) VALUES (?, 'update', 'user', ?, ?, ?)")
            ->execute([$currentUser['id'], $targetUserId, $details, $_SERVER['REMOTE_ADDR'] ?? '']);

        flash_set('密码修改成功！请使用新密码登录。');
        $success = true;

        // 如果是管理员修改了其他用户的密码，返回用户列表
        if ($isAdmin && $targetUserId !== $currentUser['id']) {
            header('Location: ?p=users');
            exit;
        }
    }
}
?>
<?php include __DIR__ . '/../templates/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h1>
    <?php if ($isAdmin && $targetUserId !== $currentUser['id']): ?>
      <i class="bi bi-key"></i> 修改用户密码
    <?php else: ?>
      <i class="bi bi-key"></i> 修改密码
    <?php endif; ?>
  </h1>
  <?php if ($isAdmin && $targetUserId !== $currentUser['id']): ?>
    <a class="btn btn-secondary" href="?p=users"><i class="bi bi-arrow-left"></i> 返回用户列表</a>
  <?php else: ?>
    <a class="btn btn-secondary" href="?p=assets"><i class="bi bi-arrow-left"></i> 返回</a>
  <?php endif; ?>
</div>

<?php if (!$success): ?>
  <?php if ($isAdmin && $targetUserId !== $currentUser['id']): ?>
    <div class="alert alert-info">
      <i class="bi bi-info-circle"></i> 正在为用户 <strong><?= h($targetUser['Username']) ?> (<?= h($targetUser['FullName']) ?>)</strong> 设置新密码
    </div>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
      <ul class="mb-0">
        <?php foreach ($errors as $error): ?>
          <li><?= h($error) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card">
        <div class="card-header bg-primary text-white">
          <h6 class="mb-0"><i class="bi bi-key"></i> <?= $isAdmin && $targetUserId !== $currentUser['id'] ? '设置新密码' : '修改密码' ?></h6>
        </div>
        <div class="card-body">
          <form method="post">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>" />

            <?php if ($targetUserId === $currentUser['id']): ?>
            <div class="mb-3">
              <label class="form-label">当前密码 <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" class="form-control" name="old_password" required placeholder="请输入当前密码" />
              </div>
            </div>
            <?php endif; ?>

            <div class="mb-3">
              <label class="form-label">新密码 <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                <input type="password" class="form-control" name="new_password" id="newPassword" required placeholder="请输入新密码（至少6位）" minlength="6" />
                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('newPassword')">
                  <i class="bi bi-eye" id="newPasswordIcon"></i>
                </button>
              </div>
              <small class="text-muted">密码长度至少为6位</small>
            </div>

            <div class="mb-3">
              <label class="form-label">确认新密码 <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                <input type="password" class="form-control" name="confirm_password" id="confirmPassword" required placeholder="请再次输入新密码" minlength="6" />
                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('confirmPassword')">
                  <i class="bi bi-eye" id="confirmPasswordIcon"></i>
                </button>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label">密码强度</label>
              <div class="progress" style="height: 10px;">
                <div class="progress-bar bg-danger" id="passwordStrength" role="progressbar" style="width: 0%"></div>
              </div>
              <small class="text-muted" id="passwordStrengthText">请输入密码</small>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100">
              <i class="bi bi-check-circle"></i> <?= $isAdmin && $targetUserId !== $currentUser['id'] ? '确认设置' : '确认修改' ?>
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<script>
// 切换密码可见性
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(inputId + 'Icon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}

// 检查密码强度
document.getElementById('newPassword').addEventListener('input', function(e) {
    const password = e.target.value;
    let strength = 0;

    if (password.length >= 6) strength += 20;
    if (password.length >= 10) strength += 20;
    if (/[a-z]/.test(password)) strength += 20;
    if (/[A-Z]/.test(password)) strength += 20;
    if (/[0-9]/.test(password)) strength += 20;

    const bar = document.getElementById('passwordStrength');
    const text = document.getElementById('passwordStrengthText');

    bar.style.width = strength + '%';

    if (strength < 40) {
        bar.className = 'progress-bar bg-danger';
        text.textContent = '弱';
    } else if (strength < 80) {
        bar.className = 'progress-bar bg-warning';
        text.textContent = '中等';
    } else {
        bar.className = 'progress-bar bg-success';
        text.textContent = '强';
    }
});
</script>

<?php include __DIR__ . '/../templates/footer.php'; ?>

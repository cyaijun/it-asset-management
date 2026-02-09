<?php
// pages/users_create.php - 用户创建

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $errors = [];
    $data = [
        'Username' => trim($_POST['Username'] ?? ''),
        'FullName' => trim($_POST['FullName'] ?? ''),
        'Email' => trim($_POST['Email'] ?? ''),
        'Department' => trim($_POST['Department'] ?? ''),
        'Role' => $_POST['Role'] ?? 'user',
        'Password' => $_POST['Password'] ?? '',
    ];

    // 验证必填字段
    if ($data['Username'] === '') $errors[] = '用户名不能为空';
    if ($data['FullName'] === '') $errors[] = '姓名不能为空';
    if ($data['Password'] === '') $errors[] = '密码不能为空';

    // 验证用户名唯一性
    if (!validate_username_unique($pdo, $data['Username'])) {
        $errors[] = '用户名已存在';
    }

    // 验证邮箱格式
    if ($data['Email'] && !validate_email($data['Email'])) {
        $errors[] = '邮箱格式不正确';
    }

    // 验证密码长度
    if (strlen($data['Password']) < 6) {
        $errors[] = '密码长度不能少于6位';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO Users (Username, FullName, Email, Department, Password, Role, Status, CreatedAt) VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())");
        $stmt->execute([
            $data['Username'], $data['FullName'], $data['Email'], $data['Department'],
            password_hash($data['Password'], PASSWORD_DEFAULT), $data['Role']
        ]);

        // 记录审计日志
        $currentUser = get_current_user_info();
        $pdo->prepare("INSERT INTO AuditLog (UserId, Action, ResourceType, Details, IpAddress) VALUES (?, 'create', 'user', ?, ?)")
            ->execute([$currentUser['id'], "创建用户: {$data['Username']}", $_SERVER['REMOTE_ADDR'] ?? '']);

        flash_set('用户创建成功');
        header('Location: ?p=users');
        exit;
    }
}
?>
<?php include __DIR__ . '/../templates/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1>新增用户</h1>
  <a class="btn btn-secondary" href="?p=users">返回</a>
</div>

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
  <div class="col-md-8">
    <div class="card">
      <div class="card-body">
        <form method="post" class="row g-3">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>" />
          <div class="col-md-6">
            <label class="form-label">用户名 <span class="text-danger">*</span></label>
            <input class="form-control" name="Username" required />
          </div>
          <div class="col-md-6">
            <label class="form-label">姓名 <span class="text-danger">*</span></label>
            <input class="form-control" name="FullName" required />
          </div>
          <div class="col-md-6">
            <label class="form-label">邮箱</label>
            <input type="email" class="form-control" name="Email" />
          </div>
          <div class="col-md-6">
            <label class="form-label">部门</label>
            <input class="form-control" name="Department" />
          </div>
          <div class="col-md-6">
            <label class="form-label">角色</label>
            <select class="form-select" name="Role">
              <option value="user" selected>普通用户</option>
              <option value="admin">管理员</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">密码 <span class="text-danger">*</span></label>
            <input type="password" class="form-control" name="Password" required minlength="6" />
            <small class="text-muted">至少6位</small>
          </div>
          <div class="col-12">
            <button class="btn btn-primary" type="submit"><i class="bi bi-person-plus"></i> 创建用户</button>
            <button class="btn btn-secondary" type="reset">重置</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../templates/footer.php'; ?>

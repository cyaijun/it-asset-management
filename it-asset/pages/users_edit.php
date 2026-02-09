<?php
// pages/users_edit.php - 用户编辑
require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM Users WHERE Id = ?");
$stmt->execute([$id]);
$editUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$editUser) {
    flash_set('未找到该用户');
    header('Location: ?p=users');
    exit;
}

$errors = [];
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $formData = [
        'Username' => trim($_POST['Username'] ?? ''),
        'FullName' => trim($_POST['FullName'] ?? ''),
        'Email' => trim($_POST['Email'] ?? ''),
        'Department' => trim($_POST['Department'] ?? ''),
        'Role' => $_POST['Role'] ?? 'user',
        'Status' => $_POST['Status'] ?? 'active',
        'Password' => $_POST['Password'] ?? '',
    ];

    $data = $formData;

    // 验证必填字段
    if ($data['Username'] === '') $errors[] = '用户名不能为空';
    if ($data['FullName'] === '') $errors[] = '姓名不能为空';

    // 验证用户名唯一性
    if (!validate_username_unique($pdo, $data['Username'], $id)) {
        $errors[] = '用户名已存在';
    }

    // 验证邮箱格式
    if ($data['Email'] && !validate_email($data['Email'])) {
        $errors[] = '邮箱格式不正确';
    }

    // 验证密码长度
    if ($data['Password'] && strlen($data['Password']) < 6) {
        $errors[] = '密码长度不能少于6位';
    }

    if (empty($errors)) {
        $passwordSql = '';
        $params = [
            $data['Username'], $data['FullName'], $data['Email'], $data['Department'],
            $data['Role'], $data['Status'], $id
        ];

        if ($data['Password']) {
            $passwordSql = ', Password = ?';
            $passwordHash = password_hash($data['Password'], PASSWORD_DEFAULT);
            array_splice($params, -1, 0, $passwordHash);
        }

        $sql = "UPDATE Users SET Username = ?, FullName = ?, Email = ?, Department = ?, Role = ?, Status = ?{$passwordSql} WHERE Id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // 记录审计日志
        $currentUser = get_current_user_info();
        $pdo->prepare("INSERT INTO AuditLog (UserId, Action, ResourceType, ResourceId, Details, IpAddress) VALUES (?, 'update', 'user', ?, ?, ?)")
            ->execute([$currentUser['id'], $id, "编辑用户: {$data['Username']}", $_SERVER['REMOTE_ADDR'] ?? '']);

        flash_set('用户更新成功');
        header('Location: ?p=users');
        exit;
    }
}

// 如果是POST请求且有错误，使用表单数据填充字段
if (!empty($errors) && !empty($formData)) {
    $editUser = array_merge($editUser, $formData);
}

// 确保必要的字段存在并转换为字符串
$editUser['Id'] = (string)($editUser['Id'] ?? '');
$editUser['Username'] = (string)($editUser['Username'] ?? '');
$editUser['FullName'] = (string)($editUser['FullName'] ?? '');
$editUser['Email'] = (string)($editUser['Email'] ?? '');
$editUser['Department'] = (string)($editUser['Department'] ?? '');
$editUser['Role'] = (string)($editUser['Role'] ?? 'user');
$editUser['Status'] = (string)($editUser['Status'] ?? 'active');

?>
<?php include __DIR__ . '/../templates/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1>编辑用户</h1>
  <div>
    <a class="btn btn-secondary" href="?p=users">返回</a>
  </div>
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
            <input class="form-control" name="Username" value="<?= h($editUser['Username'] ?? '') ?>" required />
          </div>
          <div class="col-md-6">
            <label class="form-label">姓名 <span class="text-danger">*</span></label>
            <input class="form-control" name="FullName" value="<?= h($editUser['FullName'] ?? '') ?>" required />
          </div>
          <div class="col-md-6">
            <label class="form-label">邮箱</label>
            <input type="email" class="form-control" name="Email" value="<?= h($editUser['Email'] ?? '') ?>" />
          </div>
          <div class="col-md-6">
            <label class="form-label">部门</label>
            <input class="form-control" name="Department" value="<?= h($editUser['Department'] ?? '') ?>" />
          </div>
          <div class="col-md-6">
            <label class="form-label">角色</label>
            <select class="form-select" name="Role">
              <option value="user" <?= $editUser['Role'] === 'user' ? 'selected' : '' ?>>普通用户</option>
              <option value="admin" <?= $editUser['Role'] === 'admin' ? 'selected' : '' ?>>管理员</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">状态</label>
            <select class="form-select" name="Status">
              <option value="active" <?= $editUser['Status'] === 'active' ? 'selected' : '' ?>>启用</option>
              <option value="disabled" <?= $editUser['Status'] === 'disabled' ? 'selected' : '' ?>>禁用</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">新密码</label>
            <input type="password" class="form-control" name="Password" placeholder="留空则不修改" />
            <small class="text-muted">至少6位,留空则不修改密码</small>
          </div>
          <div class="col-12">
            <button class="btn btn-primary" type="submit">保存修改</button>
            <a class="btn btn-secondary" href="?p=users">取消</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../templates/footer.php'; ?>

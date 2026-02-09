<?php
// pages/login.php - 登录页面

if (is_logged_in()) {
    header('Location: ?p=assets');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = '用户名和密码不能为空';
    } elseif (login($username, $password, $pdo)) {
        flash_set('登录成功');
        header('Location: ?p=assets');
        exit;
    } else {
        $error = '用户名或密码错误';
    }
}
?>
<?php include __DIR__ . '/../templates/header.php'; ?>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow">
                <div class="card-body">
                    <h3 class="card-title text-center mb-4">登录</h3>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= h($error) ?></div>
                    <?php endif; ?>
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">用户名</label>
                            <input type="text" name="username" class="form-control" required autofocus />
                        </div>
                        <div class="mb-3">
                            <label class="form-label">密码</label>
                            <input type="password" name="password" class="form-control" required />
                        </div>
                        <button type="submit" class="btn btn-primary w-100">登录</button>
                    </form>
                    <p class="text-center mt-3 text-muted small">
                        默认管理员: admin / admin123<br>
                        请首次登录后立即修改密码
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../templates/footer.php'; ?>

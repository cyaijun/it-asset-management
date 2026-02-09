<?php
// auth.php - 身份验证和权限控制

// 会话启动
function session_start_safe() {
    if (session_status() === PHP_SESSION_NONE) {
        session_name('it_asset_session');
        session_start();
    }
}

// 检查用户是否登录
function is_logged_in() {
    session_start_safe();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// 获取当前登录用户
function get_current_user_info() {
    session_start_safe();
    if (isset($_SESSION['user_id'])) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'] ?? '',
            'full_name' => $_SESSION['full_name'] ?? '',
            'role' => $_SESSION['role'] ?? 'user',
        ];
    }
    return null;
}

// 检查是否为管理员
function is_admin() {
    $user = get_current_user_info();
    return $user && $user['role'] === 'admin';
}

// 需要登录才能访问
function require_login() {
    if (!is_logged_in()) {
        flash_set('请先登录');
        header('Location: ?p=login');
        exit;
    }
}

// 需要管理员权限
function require_admin() {
    require_login();
    if (!is_admin()) {
        flash_set('您没有权限执行此操作');
        header('Location: ?p=assets');
        exit;
    }
}

// 用户登录
function login($username, $password, $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM Users WHERE Username = ? AND Status != 'disabled'");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['Password'])) {
        session_start_safe();
        $_SESSION['user_id'] = $user['Id'];
        $_SESSION['username'] = $user['Username'];
        $_SESSION['full_name'] = $user['FullName'];
        $_SESSION['role'] = $user['Role'] ?? 'user';

        // 更新最后登录时间
        $pdo->prepare("UPDATE Users SET LastLoginAt = NOW() WHERE Id = ?")->execute([$user['Id']]);

        return true;
    }
    return false;
}

// 用户登出
function logout() {
    session_start_safe();
    session_unset();
    session_destroy();
}

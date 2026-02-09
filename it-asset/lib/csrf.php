<?php
// csrf.php - CSRF 防护

// 生成 CSRF token
function csrf_token() {
    session_start_safe();
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// 验证 CSRF token
function csrf_verify() {
    session_start_safe();
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// 需要验证 CSRF token
function require_csrf() {
    if (!csrf_verify()) {
        flash_set('无效的请求,请刷新页面重试');
        header('Location: ?p=assets');
        exit;
    }
}

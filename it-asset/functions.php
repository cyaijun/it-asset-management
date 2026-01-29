<?php
// functions.php - 一些辅助函数

function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function flash_set($msg) {
    if (!session_id()) session_start();
    $_SESSION['flash'] = $msg;
}

function flash_get() {
    if (!session_id()) session_start();
    if (!empty($_SESSION['flash'])) {
        $m = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $m;
    }
    return null;
}

// 简单分页计算（可选）
function paginate_offset($page, $perPage) {
    $p = max(1, (int)$page);
    return ($p - 1) * $perPage;
}
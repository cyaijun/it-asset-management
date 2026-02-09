<?php
// functions.php - 辅助函数

require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/csrf.php';
require_once __DIR__ . '/lib/validation.php';

function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function flash_set($msg) {
    session_start_safe();
    $_SESSION['flash'] = $msg;
}

function flash_get() {
    session_start_safe();
    if (!empty($_SESSION['flash'])) {
        $m = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $m;
    }
    return null;
}

// ========== 凭证加密相关函数 ==========

// 加密凭证
function encrypt_credential($data, $key = null) {
    if ($key === null) {
        // 使用应用密钥（从环境变量或默认值）
        $key = $_ENV['ENCRYPTION_KEY'] ?? 'it-asset-default-encryption-key-32bytes';
    }

    // 确保密钥长度为32字节（AES-256）
    $key = substr(hash('sha256', $key, true), 0, 32);

    // 生成随机IV
    $iv = random_bytes(16);

    // 加密
    $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);

    // 组合 IV 和加密数据
    return base64_encode($iv . $encrypted);
}

// 解密凭证
function decrypt_credential($encrypted, $key = null) {
    if ($key === null) {
        $key = $_ENV['ENCRYPTION_KEY'] ?? 'it-asset-default-encryption-key-32bytes';
    }

    // 确保密钥长度为32字节（AES-256）
    $key = substr(hash('sha256', $key, true), 0, 32);

    // 解码
    $data = base64_decode($encrypted);

    // 提取 IV（前16字节）
    $iv = substr($data, 0, 16);
    $encrypted = substr($data, 16);

    // 解密
    return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
}

// 遮蔽敏感信息（显示部分）
function mask_sensitive_data($data, $showFirst = 2, $showLast = 4) {
    if (strlen($data) <= $showFirst + $showLast) {
        return '***';
    }
    return substr($data, 0, $showFirst) . str_repeat('*', strlen($data) - $showFirst - $showLast) . substr($data, -$showLast);
}

// 简单分页计算
function paginate_offset($page, $perPage) {
    $p = max(1, (int)$page);
    return ($p - 1) * $perPage;
}

// 分页导航
function paginate_nav($total, $page, $perPage, $baseUrl) {
    $totalPages = ceil($total / $perPage);
    if ($totalPages <= 1) return '';

    $html = '<nav><ul class="pagination">';
    $start = max(1, $page - 2);
    $end = min($totalPages, $page + 2);

    if ($page > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . 'page=' . ($page - 1) . '">上一页</a></li>';
    }

    for ($i = $start; $i <= $end; $i++) {
        $active = $i == $page ? ' active' : '';
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $baseUrl . 'page=' . $i . '">' . $i . '</a></li>';
    }

    if ($page < $totalPages) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . 'page=' . ($page + 1) . '">下一页</a></li>';
    }

    $html .= '</ul></nav>';
    return $html;
}

// 状态对应的样式类
function status_class($status) {
    $classes = [
        'InStock' => 'bg-success',
        'Assigned' => 'bg-primary',
        'Maintenance' => 'bg-warning',
        'Disposed' => 'bg-danger',
        'Lost' => 'bg-secondary',
    ];
    return $classes[$status] ?? 'bg-secondary';
}

// 状态对应的中文
function status_text($status) {
    $texts = [
        'InStock' => '在库',
        'Assigned' => '已领用',
        'Maintenance' => '维修中',
        'Disposed' => '已报废',
        'Lost' => '已丢失',
    ];
    return $texts[$status] ?? $status;
}

// 交易类型对应的中文
function transaction_type_text($type) {
    $texts = [
        'CheckOut' => '领用',
        'CheckIn' => '归还',
        'Transfer' => '转移',
        'Maintenance' => '维修',
        'Dispose' => '报废',
        'Loss' => '丢失',
    ];
    return $texts[$type] ?? $type;
}

// 生成资产编号
function generate_asset_code($pdo, $categoryId) {
    // 获取类别信息
    $stmt = $pdo->prepare("SELECT CodeRule, Name, NextCode FROM assetcategories WHERE Id = ?");
    $stmt->execute([$categoryId]);
    $category = $stmt->fetch();

    if (!$category || empty($category['CodeRule'])) {
        return null; // 没有编号规则
    }

    $rule = $category['CodeRule'];
    $nextNum = $category['NextCode'] ?? 1;

    $code = $rule;

    // 替换 {YEAR}
    $code = str_replace('{YEAR}', date('Y'), $code);

    // 替换 {MONTH}
    $code = str_replace('{MONTH}', date('m'), $code);

    // 替换 {NUM:n} - 支持任意位数
    $code = preg_replace_callback('/\{NUM:(\d+)\}/', function($matches) use ($nextNum) {
        $numLen = intval($matches[1]);
        return sprintf('%0' . $numLen . 'd', $nextNum);
    }, $code);

    // 更新下一个编号
    $pdo->prepare("UPDATE assetcategories SET NextCode = NextCode + 1 WHERE Id = ?")->execute([$categoryId]);

    return $code;
}
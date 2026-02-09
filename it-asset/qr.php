<?php
// qr.php - 生产环境：安全地输出资产详情页的二维码（PNG）
// 保存时请使用 UTF-8 无 BOM

// 不要在此文件之前输出任何字符（确保所有被 include 的文件也无 BOM/多余空白）

// 关闭在页面直接显示错误，避免污染二进制输出
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

// 清理输出缓冲（如果有残留）
if (ob_get_length()) { @ob_end_clean(); }

require_once __DIR__ . '/db.php';

// 尝试包含本地 QR 库（请确保 lib/phpqrcode.php 是可用的真实库）
$qrLib = __DIR__ . '/lib/phpqrcode.php';
if (file_exists($qrLib)) {
    require_once $qrLib;
}

// 读取并校验 id
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("HTTP/1.1 400 Bad Request");
    exit;
}

// 检查资产是否存在
$stmt = $pdo->prepare("SELECT Id FROM Assets WHERE Id = ? LIMIT 1");
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    header("HTTP/1.1 404 Not Found");
    exit;
}

// 构造二维码内容：使用 URL 跳转到资产详情页面
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), "/\\");
$qrContent = "{$scheme}://{$host}{$base}/?p=asset_details&id=" . $id;

// 输出 PNG 的通用头（允许客户端缓存一天）
header('Content-Type: image/png');
header('Cache-Control: public, max-age=86400');

// 优先使用本地库的 QRcode::png（常见单文件 phpqrcode 库或其它实现）
if (class_exists('QRcode')) {
    // 参数：内容, outfile(false=输出), level, size（像素/模块）, margin
    // 调整 size 与 margin 以满足扫码效果
    $level = defined('QR_ECLEVEL_L') ? QR_ECLEVEL_L : 'L';
    $size  = 6;
    $margin = 2;
    QRcode::png($qrContent, false, $level, $size, $margin);
    exit;
}

// 如果没有本地库，且 GD 不可用，使用谷歌图表 API 作为临时后备（会重定向到外部服务）
$sizePx = 300;
$googleApi = "https://chart.googleapis.com/chart?chs={$sizePx}x{$sizePx}&cht=qr&chl=" . urlencode($qrContent);
header('Location: ' . $googleApi);
exit;
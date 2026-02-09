<?php
// index.php - 路由器
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$p = isset($_GET['p']) ? $_GET['p'] : 'assets';

// 公开页面(不需要登录)
$publicPages = ['login', 'logout'];

// 检查是否需要登录
if (!in_array($p, $publicPages)) {
    require_login();
}

switch ($p) {
    case 'login':
        include __DIR__ . '/pages/login.php';
        break;
    case 'logout':
        include __DIR__ . '/pages/logout.php';
        break;
    case 'assets':
        include __DIR__ . '/pages/assets_list.php';
        break;
    case 'asset_create':
        include __DIR__ . '/pages/assets_create.php';
        break;
    case 'assets_import':
        include __DIR__ . '/pages/assets_import.php';
        break;
    case 'assets_export':
        include __DIR__ . '/pages/assets_export.php';
        break;
    case 'download_asset_template':
        include __DIR__ . '/pages/download_asset_template.php';
        break;
    case 'print_label':
        include __DIR__ . '/pages/print_label.php';
        break;
    case 'asset_edit':
        include __DIR__ . '/pages/assets_edit.php';
        break;
    case 'asset_copy':
        include __DIR__ . '/pages/asset_copy.php';
        break;
    case 'asset_delete':
        include __DIR__ . '/pages/asset_delete.php';
        break;
    case 'asset_details':
        include __DIR__ . '/pages/assets_details.php';
        break;
    case 'asset_checkout':
        include __DIR__ . '/pages/assets_checkout.php';
        break;
    case 'asset_return':
        include __DIR__ . '/pages/assets_return.php';
        break;
    case 'asset_transfer':
        include __DIR__ . '/pages/assets_transfer.php';
        break;
    case 'asset_maintenance':
        include __DIR__ . '/pages/assets_maintenance.php';
        break;
    case 'asset_dispose':
        include __DIR__ . '/pages/assets_dispose.php';
        break;
    case 'asset_loss':
        include __DIR__ . '/pages/assets_loss.php';
        break;
    case 'asset_credentials':
        include __DIR__ . '/pages/asset_credentials.php';
        break;
    case 'users':
        include __DIR__ . '/pages/users_list.php';
        break;
    case 'user_create':
        include __DIR__ . '/pages/users_create.php';
        break;
    case 'user_edit':
        include __DIR__ . '/pages/users_edit.php';
        break;
    case 'change_password':
        include __DIR__ . '/pages/change_password.php';
        break;
    case 'users_import':
        include __DIR__ . '/pages/users_import.php';
        break;
    case 'download_user_template':
        include __DIR__ . '/pages/download_user_template.php';
        break;
    case 'download_user_template_csv':
        include __DIR__ . '/pages/download_user_template_csv.php';
        break;
    case 'download_asset_template_csv':
        include __DIR__ . '/pages/download_asset_template_csv.php';
        break;
    case 'test_phpspreadsheet':
        include __DIR__ . '/test_phpspreadsheet.php';
        break;
    case 'check_php_extensions':
        include __DIR__ . '/check_php_extensions.php';
        break;
    case 'setup_mbstring':
        include __DIR__ . '/setup_mbstring.php';
        break;
    case 'scan':
        include __DIR__ . '/pages/scan.php';
        break;
    case 'statistics':
        include __DIR__ . '/pages/statistics.php';
        break;
    case 'categories':
        include __DIR__ . '/pages/categories.php';
        break;
    case 'license_assets':
        include __DIR__ . '/pages/license_assets.php';
        break;
    case 'backup':
        include __DIR__ . '/pages/backup.php';
        break;
    default:
        echo "未知页面";
}
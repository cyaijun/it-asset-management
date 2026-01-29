<?php
// index.php - 简单路由器
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$p = isset($_GET['p']) ? $_GET['p'] : 'assets';
switch ($p) {
    case 'assets':
        include __DIR__ . '/pages/assets_list.php';
        break;
    case 'asset_create':
        include __DIR__ . '/pages/assets_create.php';
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
    case 'users':
        include __DIR__ . '/pages/users_list.php';
        break;
    case 'user_create':
        include __DIR__ . '/pages/users_create.php';
        break;
    case 'scan':
        include __DIR__ . '/pages/scan.php';
        break;
    default:
        echo "Unknown page";
}
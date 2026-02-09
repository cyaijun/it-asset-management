<?php
// config.php - 配置文件
// 从环境变量读取配置,如果不存在则使用默认值

return [
    'db' => [
        'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
        'port' => $_ENV['DB_PORT'] ?? '3306',
        'name' => $_ENV['DB_NAME'] ?? 'it_asset_db',
        'user' => $_ENV['DB_USER'] ?? 'root',
        'pass' => $_ENV['DB_PASS'] ?? '',
    ],
    'app' => [
        'name' => 'IT资产管理系统',
        'session_name' => 'it_asset_session',
        'csrf_token_name' => 'csrf_token',
    ],
    'security' => [
        'password_min_length' => 6,
        'session_lifetime' => 7200, // 2小时
    ]
];

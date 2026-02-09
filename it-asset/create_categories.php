<?php
// 创建资产类别表并插入默认数据
require_once 'db.php';

$sql = file_get_contents(__DIR__ . '/scripts/create_categories.sql');

try {
    $pdo->exec($sql);
    echo "成功创建资产类别表并插入默认数据！<br>";
    echo "<a href='?p=categories'>前往类别管理</a>";
} catch (Exception $e) {
    echo "错误: " . $e->getMessage();
}

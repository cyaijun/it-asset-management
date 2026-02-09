<?php
// pages/download_asset_template_csv.php - 下载资产导入模板 (CSV格式)
require_admin();

require __DIR__ . '/../db.php';

// 获取现有类别
$categories = $pdo->query("SELECT Name FROM assetcategories ORDER BY Name")->fetchAll(PDO::FETCH_COLUMN);

require __DIR__ . '/../lib/vendor/simplexlsx.php';

$excel = new SimpleExcelExporter([
    '资产编号*', '名称*', '类别', '型号', '序列号', '位置', '供应商', '采购价格', '购买日期', '保修到期日', '备注'
]);

// 添加示例数据
$excel->addRow([
    'PC-202601-0001',
    '台式电脑',
    $categories[0] ?? '台式电脑',
    'Dell OptiPlex 7090',
    'DELL123456',
    '技术部办公室',
    '戴尔公司',
    '5999.00',
    '2024-01-15',
    '2025-01-15',
    '新采购设备'
]);

$excel->addRow([
    'LAPTOP-202601-0001',
    '笔记本电脑',
    $categories[1] ?? '笔记本电脑',
    'ThinkPad X1 Carbon',
    'TPX1123456',
    '人事部办公室',
    '联想',
    '8999.00',
    '2024-02-10',
    '2025-02-10',
    '管理层配置'
]);

$excel->download('asset_import_template');

<?php
// pages/download_asset_template.php - 下载资产导入模板

// 先调用 require_admin() 但避免它影响输出
ob_start(); // 开始输出缓冲
require_admin();
ob_end_clean(); // 清除所有输出

// 检查 PhpSpreadsheet 是否已安装
$autoloadPath = __DIR__ . '/../lib/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    die("<h1>PhpSpreadsheet 未安装</h1><p>请运行 install_composer.bat 安装 PhpSpreadsheet 库</p><p>查找路径: " . htmlspecialchars($autoloadPath) . "</p>");
}

require $autoloadPath;
require __DIR__ . '/../db.php';

// 开启错误报告
error_reporting(E_ALL);
ini_set('display_errors', 0);

// 清除所有可能的输出缓冲区
while (ob_get_level()) {
    ob_end_clean();
}

try {
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // 设置表头 - 使用更简单的方式
    $sheet->fromArray([
        ['资产编号*', '名称*', '类别', '型号', '序列号', '位置', '供应商', '采购价格', '购买日期', '保修到期日', '备注']
    ], null, 'A1');

    // 添加示例数据 - 直接使用数组
    $sheet->fromArray([
        ['PC-202601-0001', '台式电脑', '台式电脑', 'Dell OptiPlex 7090', 'DELL123456', '技术部办公室', '戴尔公司', '5999.00', '2024-01-15', '2025-01-15', '新采购设备'],
        ['LAPTOP-202601-0001', '笔记本电脑', '笔记本电脑', 'ThinkPad X1 Carbon', 'TPX1123456', '人事部办公室', '联想', '8999.00', '2024-02-10', '2025-02-10', '管理层配置']
    ], null, 'A2');

    // 设置表头样式
    $headerRange = 'A1:K1';
    $sheet->getStyle($headerRange)->getFont()->setBold(true);
    $sheet->getStyle($headerRange)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
    $sheet->getStyle($headerRange)->getFill()->getStartColor()->setARGB('FFE6E6FA');

    // 设置列宽
    foreach (range('A', 'K') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // 手动调整一些列宽
    $sheet->getColumnDimension('A')->setWidth(20);
    $sheet->getColumnDimension('D')->setWidth(20);
    $sheet->getColumnDimension('K')->setWidth(20);

    // 添加说明
    $sheet->setCellValue('A5', '说明：');
    $sheet->setCellValue('A6', '1. 带*的列为必填项');
    $sheet->setCellValue('A7', '2. 资产编号必须唯一');
    $sheet->setCellValue('A8', '3. 日期格式：YYYY-MM-DD（例如：2024-01-15）');
    $sheet->setCellValue('A9', '4. 采购价格：仅填写数字，不包含货币符号');
    $sheet->setCellValue('A10', '5. 类别需要先在系统中定义');

    // 输出文件前再次清理缓冲区
    if (ob_get_level()) ob_end_clean();
    
    // 输出文件
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="asset_import_template.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save('php://output');
    exit;
    
} catch (Exception $e) {
    // 清除缓冲区后显示错误
    while (ob_get_level()) ob_end_clean();
    die("<h1>下载失败</h1><p>错误信息: " . htmlspecialchars($e->getMessage()) . "</p>");
}
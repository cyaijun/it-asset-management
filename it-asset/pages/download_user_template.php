<?php
// pages/download_user_template.php - 下载用户导入模板
require_admin();

// 检查 PhpSpreadsheet 是否已安装
$autoloadPath = __DIR__ . '/../lib/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    die("<h1>PhpSpreadsheet 未安装</h1><p>请运行 install_composer.bat 安装 PhpSpreadsheet 库</p>");
}

require $autoloadPath;

try {
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

// 设置表头
$headers = ['用户名*', '姓名*', '邮箱', '部门', '角色', '密码'];
foreach ($headers as $index => $header) {
    $sheet->setCellValue(chr(65 + $index) . '1', $header);
}

// 添加示例数据
$sheet->setCellValue('A2', 'zhangsan');
$sheet->setCellValue('B2', '张三');
$sheet->setCellValue('C2', 'zhangsan@example.com');
$sheet->setCellValue('D2', '技术部');
$sheet->setCellValue('E2', 'user');
$sheet->setCellValue('F2', '123456');

$sheet->setCellValue('A3', 'lisi');
$sheet->setCellValue('B3', '李四');
$sheet->setCellValue('C3', 'lisi@example.com');
$sheet->setCellValue('D3', '人事部');
$sheet->setCellValue('E3', 'user');
$sheet->setCellValue('F3', '123456');

// 设置表头样式
$headerRange = 'A1:F1';
$sheet->getStyle($headerRange)->getFont()->setBold(true);
$sheet->getStyle($headerRange)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
$sheet->getStyle($headerRange)->getFill()->getStartColor()->setARGB('FFE6E6FA');

// 设置列宽
$sheet->getColumnDimension('A')->setWidth(15);
$sheet->getColumnDimension('B')->setWidth(15);
$sheet->getColumnDimension('C')->setWidth(25);
$sheet->getColumnDimension('D')->setWidth(15);
$sheet->getColumnDimension('E')->setWidth(10);
$sheet->getColumnDimension('F')->setWidth(15);

// 添加说明
$sheet->setCellValue('A5', '说明：');
$sheet->setCellValue('A6', '1. 带*的列为必填项');
$sheet->setCellValue('A7', '2. 用户名必须唯一');
$sheet->setCellValue('A8', '3. 角色可选值: admin（管理员）或 user（普通用户）');
$sheet->setCellValue('A9', '4. 密码如果不填写，默认为 123456');

// 输出文件
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="user_import_template.xlsx"');
header('Cache-Control: max-age=0');

$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
$writer->save('php://output');
exit;
} catch (Exception $e) {
    die("<h1>下载失败</h1><p>错误信息: " . htmlspecialchars($e->getMessage()) . "</p>");
}

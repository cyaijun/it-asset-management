<?php
// pages/assets_export.php - 资产批量导出
require_admin();

$autoloadPath = __DIR__ . '/../lib/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    die('PhpSpreadsheet 未安装');
}

require_once $autoloadPath;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// 获取筛选条件
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$category = $_GET['category'] ?? '';

// 构建查询
$where = ['1=1'];
$params = [];

if ($search) {
    $where[] = "(a.Tag LIKE ? OR a.Name LIKE ? OR a.SerialNumber LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($status) {
    $where[] = "a.Status = ?";
    $params[] = $status;
}

if ($category) {
    $where[] = "a.Category = ?";
    $params[] = $category;
}

$whereClause = implode(' AND ', $where);

// 获取数据
$sql = "SELECT a.*, u.Username as OwnerUsername, u.FullName as OwnerFullName, u.Department as OwnerDepartment
        FROM Assets a
        LEFT JOIN Users u ON a.OwnerId = u.Id
        WHERE {$whereClause}
        ORDER BY a.Id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$assets = $stmt->fetchAll();

// 创建Spreadsheet对象
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// 设置列宽
$sheet->getColumnDimension('A')->setWidth(8);   // ID
$sheet->getColumnDimension('B')->setWidth(20);  // 资产编号
$sheet->getColumnDimension('C')->setWidth(25);  // 名称
$sheet->getColumnDimension('D')->setWidth(15);  // 类别
$sheet->getColumnDimension('E')->setWidth(20);  // 型号
$sheet->getColumnDimension('F')->setWidth(25);  // 序列号
$sheet->getColumnDimension('G')->setWidth(10);  // 状态
$sheet->getColumnDimension('H')->setWidth(20);  // 持有者
$sheet->getColumnDimension('I')->setWidth(15);  // 持有者部门
$sheet->getColumnDimension('J')->setWidth(15);  // 位置
$sheet->getColumnDimension('K')->setWidth(20);  // 供应商
$sheet->getColumnDimension('L')->setWidth(15);  // 购买日期
$sheet->getColumnDimension('M')->setWidth(15);  // 保修到期
$sheet->getColumnDimension('N')->setWidth(12);  // 采购价格
$sheet->getColumnDimension('O')->setWidth(20);  // 创建时间
$sheet->getColumnDimension('P')->setWidth(30);  // 备注

// 表头
$headers = ['ID', '资产编号', '名称', '类别', '型号', '序列号', '状态', '持有者', '持有者部门', '位置', '供应商', '购买日期', '保修到期', '采购价格', '创建时间', '备注'];
$sheet->fromArray($headers, null, 'A1');

// 设置表头样式
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
];
$sheet->getStyle('A1:P1')->applyFromArray($headerStyle);

// 填充数据
$row = 2;
foreach ($assets as $a) {
    $statusText = status_text($a['Status']);
    $ownerName = $a['OwnerFullName'] ? h($a['OwnerFullName']) : '';

    $sheet->setCellValue('A' . $row, $a['Id']);
    $sheet->setCellValue('B' . $row, h($a['Tag']));
    $sheet->setCellValue('C' . $row, h($a['Name']));
    $sheet->setCellValue('D' . $row, h($a['Category'] ?: ''));
    $sheet->setCellValue('E' . $row, h($a['Model'] ?: ''));
    $sheet->setCellValue('F' . $row, h($a['SerialNumber'] ?: ''));
    $sheet->setCellValue('G' . $row, $statusText);
    $sheet->setCellValue('H' . $row, $ownerName);
    $sheet->setCellValue('I' . $row, h($a['OwnerDepartment'] ?: ''));
    $sheet->setCellValue('J' . $row, h($a['Location'] ?: ''));
    $sheet->setCellValue('K' . $row, h($a['Supplier'] ?: ''));
    $sheet->setCellValue('L' . $row, h($a['PurchaseDate'] ?: ''));
    $sheet->setCellValue('M' . $row, h($a['WarrantyExpiry'] ?: ''));
    $sheet->setCellValue('N' . $row, $a['PurchasePrice'] ? $a['PurchasePrice'] : '');
    $sheet->setCellValue('O' . $row, h($a['CreatedAt']));
    $sheet->setCellValue('P' . $row, h($a['Notes'] ?: ''));

    $row++;
}

// 自动调整行高
$sheet->getStyle('A2:P' . ($row - 1))->getAlignment()->setWrapText(true);

// 设置边框
$sheet->getStyle('A1:P' . ($row - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

// 冻结首行
$sheet->freezePane('A2');

// 输出文件
$filename = '资产导出_' . date('YmdHis') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;

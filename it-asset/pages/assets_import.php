<?php
// pages/assets_import.php - 资产批量导入
require_admin();

// 检查 PhpSpreadsheet 是否已安装
$autoloadPath = __DIR__ . '/../lib/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    $errors[] = 'PhpSpreadsheet 未安装，请运行 install_composer.bat 安装';
}

$importResults = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = '请选择有效的Excel文件';
    } else {
        $file = $_FILES['excel_file']['tmp_name'];
        $fileExt = strtolower(pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION));

        if (!in_array($fileExt, ['xlsx', 'xls', 'csv'])) {
            $errors[] = '只支持 .xlsx、.xls 或 .csv 格式的文件';
        } elseif ($fileExt === 'csv') {
            // 处理 CSV 文件
            $handle = fopen($file, 'r');
            $rows = [];
            while (($row = fgetcsv($handle)) !== false) {
                // 转换编码（假设文件是 UTF-8）
                $row = array_map(function($v) { return mb_convert_encoding($v, 'UTF-8', 'UTF-8'); }, $row);
                $rows[] = $row;
            }
            fclose($handle);
        } else {
            require $autoloadPath;

            try {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = $worksheet->toArray();
            $headerRow = array_shift($rows); // 第一行是表头

            $successCount = 0;
            $errorCount = 0;
            $errorsList = [];

            foreach ($rows as $rowIndex => $row) {
                // 跳过空行
                if (empty(array_filter($row))) {
                    continue;
                }

                // 解析行数据：资产编号, 名称, 类别, 型号, 序列号, 位置, 供应商, 采购价格, 购买日期, 保修到期日, 备注
                $tag = trim($row[0] ?? '');
                $name = trim($row[1] ?? '');
                $category = trim($row[2] ?? '');
                $model = trim($row[3] ?? '');
                $serialNumber = trim($row[4] ?? '');
                $location = trim($row[5] ?? '');
                $supplier = trim($row[6] ?? '');
                $purchasePrice = trim($row[7] ?? '');
                $purchaseDate = trim($row[8] ?? '');
                $warrantyExpiry = trim($row[9] ?? '');
                $notes = trim($row[10] ?? '');

                // 验证必填字段
                if ($tag === '' || $name === '') {
                    $errorsList[] = "第" . ($rowIndex + 2) . "行：资产编号和名称不能为空";
                    $errorCount++;
                    continue;
                }

                // 验证编号唯一性
                $stmt = $pdo->prepare("SELECT Id FROM Assets WHERE Tag = ?");
                $stmt->execute([$tag]);
                if ($stmt->fetch()) {
                    $errorsList[] = "第" . ($rowIndex + 2) . "行：资产编号 {$tag} 已存在";
                    $errorCount++;
                    continue;
                }

                // 验证日期格式
                if ($purchaseDate && !validate_date($purchaseDate)) {
                    $errorsList[] = "第" . ($rowIndex + 2) . "行：购买日期格式不正确";
                    $errorCount++;
                    continue;
                }

                if ($warrantyExpiry && !validate_date($warrantyExpiry)) {
                    $errorsList[] = "第" . ($rowIndex + 2) . "行：保修到期日格式不正确";
                    $errorCount++;
                    continue;
                }

                // 验证价格
                if ($purchasePrice && !validate_number($purchasePrice, 0)) {
                    $errorsList[] = "第" . ($rowIndex + 2) . "行：采购价格必须是有效数字";
                    $errorCount++;
                    continue;
                }

                try {
                    $stmt = $pdo->prepare("INSERT INTO Assets (Tag, Name, Category, Model, SerialNumber, Location, Supplier, PurchasePrice, PurchaseDate, WarrantyExpiry, Notes, Status, CreatedAt) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'InStock', NOW())");
                    $stmt->execute([
                        $tag, $name, $category, $model, $serialNumber,
                        $location, $supplier, $purchasePrice ?: null,
                        $purchaseDate ?: null, $warrantyExpiry ?: null, $notes
                    ]);

                    $assetId = $pdo->lastInsertId();

                    // 记录交易日志
                    $user = get_current_user_info();
                    $pdo->prepare("INSERT INTO AssetTransactions (AssetId, UserId, TransactionType, Notes, CreatedAt, OperatorId) VALUES (?, ?, 'Create', ?, NOW(), ?)")
                        ->execute([$assetId, $user['id'], "批量导入资产: {$name}", $user['id']]);

                    $successCount++;
                } catch (PDOException $e) {
                    $errorsList[] = "第" . ($rowIndex + 2) . "行：导入失败 - " . $e->getMessage();
                    $errorCount++;
                }
            }

            $importResults = [
                'success' => $successCount,
                'error' => $errorCount,
                'errors' => $errorsList
            ];

            // 记录审计日志
            $currentUser = get_current_user_info();
            $pdo->prepare("INSERT INTO AuditLog (UserId, Action, ResourceType, Details, IpAddress) VALUES (?, 'batch_import', 'asset', ?, ?)")
                ->execute([$currentUser['id'], "批量导入资产: 成功{$successCount}个, 失败{$errorCount}个", $_SERVER['REMOTE_ADDR'] ?? '']);
            } catch (Exception $e) {
                $errors[] = '读取Excel文件失败: ' . $e->getMessage();
            }
        }
    }
}

// 获取现有类别列表，用于模板
$categories = $pdo->query("SELECT Name FROM assetcategories ORDER BY Name")->fetchAll(PDO::FETCH_COLUMN);
?>
<?php include __DIR__ . '/../templates/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1>批量导入资产</h1>
  <div class="btn-group">
    <a class="btn btn-success" href="?p=download_asset_template_csv"><i class="bi bi-download"></i> 下载CSV模板</a>
    <a class="btn btn-outline-success" href="?p=download_asset_template"><i class="bi bi-file-earmark-excel"></i> 下载Excel模板</a>
    <a class="btn btn-secondary" href="?p=assets">返回</a>
  </div>
</div>

<?php if (!empty($importResults)): ?>
  <div class="alert alert-<?= $importResults['error'] > 0 ? 'warning' : 'success' ?>">
    <h5>导入结果</h5>
    <p>成功导入 <?= $importResults['success'] ?> 个资产，失败 <?= $importResults['error'] ?> 个</p>
    <?php if (!empty($importResults['errors'])): ?>
      <h6>错误详情：</h6>
      <ul class="mb-0">
        <?php foreach ($importResults['errors'] as $error): ?>
          <li><?= h($error) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $error): ?>
        <li><?= h($error) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="card mb-4">
  <div class="card-body">
    <h5 class="card-title">导入说明</h5>
    <ol>
      <li>下载导入模板</li>
      <li>按照模板格式填写资产信息</li>
      <li>上传填写好的Excel文件</li>
      <li>日期格式：YYYY-MM-DD（例如：2024-01-15）</li>
      <li>采购价格：仅填写数字，不包含货币符号</li>
    </ol>
  </div>
</div>

<div class="card mb-4">
  <div class="card-body">
    <h5 class="card-title">现有类别列表</h5>
    <p class="text-muted mb-2">以下类别已存在，可直接在Excel中使用：</p>
    <div>
      <?php foreach ($categories as $cat): ?>
        <span class="badge bg-secondary me-1 mb-1"><?= h($cat) ?></span>
      <?php endforeach; ?>
      <?php if (empty($categories)): ?>
        <span class="text-muted">暂无类别，请先到 <a href="?p=categories">类别管理</a> 添加</span>
      <?php endif; ?>
    </div>
  </div>
</div>

<form method="post" enctype="multipart/form-data" class="card">
  <div class="card-body">
    <div class="mb-3">
      <label class="form-label">选择文件 <span class="text-danger">*</span></label>
      <input type="file" class="form-control" name="excel_file" accept=".xlsx,.xls,.csv" required />
      <small class="text-muted">支持 .xlsx、.xls 和 .csv 格式</small>
    </div>
    <button class="btn btn-primary" type="submit"><i class="bi bi-upload"></i> 开始导入</button>
    <a class="btn btn-secondary" href="?p=assets">取消</a>
  </div>
  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>" />
</form>
<?php include __DIR__ . '/../templates/footer.php'; ?>

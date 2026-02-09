<?php
// pages/users_import.php - 用户批量导入
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
                // 转换编码
                $row = array_map(function($v) { return mb_convert_encoding($v, 'UTF-8', 'UTF-8'); }, $row);
                $rows[] = $row;
            }
            fclose($handle);
        } else {
            // 使用PhpSpreadsheet读取Excel
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

                // 解析行数据（按列顺序：用户名, 姓名, 邮箱, 部门, 角色, 密码）
                $username = trim($row[0] ?? '');
                $fullname = trim($row[1] ?? '');
                $email = trim($row[2] ?? '');
                $department = trim($row[3] ?? '');
                $role = trim($row[4] ?? 'user');
                $password = trim($row[5] ?? '123456'); // 默认密码

                // 验证
                if ($username === '' || $fullname === '') {
                    $errorsList[] = "第" . ($rowIndex + 2) . "行：用户名和姓名不能为空";
                    $errorCount++;
                    continue;
                }

                // 验证用户名唯一性
                $stmt = $pdo->prepare("SELECT Id FROM Users WHERE Username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    $errorsList[] = "第" . ($rowIndex + 2) . "行：用户名 {$username} 已存在";
                    $errorCount++;
                    continue;
                }

                // 验证邮箱格式
                if ($email && !validate_email($email)) {
                    $errorsList[] = "第" . ($rowIndex + 2) . "行：邮箱格式不正确";
                    $errorCount++;
                    continue;
                }

                // 规范化角色
                $role = in_array(strtolower($role), ['admin', '管理员']) ? 'admin' : 'user';

                try {
                    $stmt = $pdo->prepare("INSERT INTO Users (Username, FullName, Email, Department, Password, Role, Status, CreatedAt) VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())");
                    $stmt->execute([
                        $username, $fullname, $email, $department,
                        password_hash($password, PASSWORD_DEFAULT), $role
                    ]);
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
            $pdo->prepare("INSERT INTO AuditLog (UserId, Action, ResourceType, Details, IpAddress) VALUES (?, 'batch_import', 'user', ?, ?)")
                ->execute([$currentUser['id'], "批量导入用户: 成功{$successCount}个, 失败{$errorCount}个", $_SERVER['REMOTE_ADDR'] ?? '']);
            } catch (Exception $e) {
                $errors[] = '读取Excel文件失败: ' . $e->getMessage();
            }
        }
    }
}
?>
<?php include __DIR__ . '/../templates/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1>批量导入用户</h1>
  <div class="btn-group">
    <a class="btn btn-success" href="?p=download_user_template_csv"><i class="bi bi-download"></i> 下载CSV模板</a>
    <a class="btn btn-outline-success" href="?p=download_user_template"><i class="bi bi-file-earmark-excel"></i> 下载Excel模板</a>
    <a class="btn btn-secondary" href="?p=users">返回</a>
  </div>
</div>

<?php if (!empty($importResults)): ?>
  <div class="alert alert-<?= $importResults['error'] > 0 ? 'warning' : 'success' ?>">
    <h5>导入结果</h5>
    <p>成功导入 <?= $importResults['success'] ?> 个用户，失败 <?= $importResults['error'] ?> 个</p>
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
      <li>按照模板格式填写用户信息</li>
      <li>上传填写好的Excel文件</li>
      <li>默认密码为 123456（可在Excel中修改）</li>
      <li>角色填写 "admin" 或 "user"</li>
    </ol>
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
    <a class="btn btn-secondary" href="?p=users">取消</a>
  </div>
  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>" />
</form>
<?php include __DIR__ . '/../templates/footer.php'; ?>

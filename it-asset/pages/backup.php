<?php
// pages/backup.php - 数据库备份和还原
require_admin();

// 获取数据库配置
require_once __DIR__ . '/../db.php';

// 获取配置文件中的数据库配置
$config = require __DIR__ . '/../lib/config.php';
$dbConfig = $config['db'];

// 备份目录
$backupDir = __DIR__ . '/../backups';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// 创建备份
if (isset($_POST['action']) && $_POST['action'] === 'backup' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $description = trim($_POST['description'] ?? '');
    $filename = 'backup_' . date('Ymd_His') . '.sql';

    // 使用 mysqldump 命令
    $dumpCmd = sprintf(
        'mysqldump -h%s -u%s -p%s %s > %s 2>&1',
        escapeshellarg($dbConfig['host']),
        escapeshellarg($dbConfig['user']),
        escapeshellarg($dbConfig['pass']),
        escapeshellarg($dbConfig['name']),
        escapeshellarg($backupFile)
    );

    $backupFile = $backupDir . '/' . $filename;

    // 尝试使用 mysqldump
    @exec($dumpCmd, $output, $returnCode);

    // 如果 mysqldump 不可用，使用 PHP 导出
    if ($returnCode !== 0 || !file_exists($backupFile)) {
        $backupFile = create_backup_php($pdo, $backupDir, $filename);
    }

    if ($backupFile) {
        // 添加描述信息
        $metaFile = $backupDir . '/' . pathinfo($filename, PATHINFO_FILENAME) . '.meta';
        $meta = [
            'filename' => $filename,
            'description' => $description,
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => get_current_user_info()['username'] ?? 'admin',
            'file_size' => filesize($backupFile)
        ];
        file_put_contents($metaFile, json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        flash_set('备份创建成功: ' . $filename);
    } else {
        flash_set('备份创建失败', 'error');
    }
    header('Location: ?p=backup');
    exit;
}

// 删除备份
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['file'])) {
    require_csrf();
    $file = basename($_GET['file']);
    $sqlFile = $backupDir . '/' . $file;
    $metaFile = $backupDir . '/' . pathinfo($file, PATHINFO_FILENAME) . '.meta';

    $deleted = true;
    if (file_exists($sqlFile)) {
        $deleted = @unlink($sqlFile) && $deleted;
    }
    if (file_exists($metaFile)) {
        $deleted = @unlink($metaFile) && $deleted;
    }

    if ($deleted) {
        flash_set('备份文件已删除');
    } else {
        flash_set('删除失败', 'error');
    }
    header('Location: ?p=backup');
    exit;
}

// 下载备份
if (isset($_GET['action']) && $_GET['action'] === 'download' && isset($_GET['file'])) {
    $file = basename($_GET['file']);
    $sqlFile = $backupDir . '/' . $file;

    if (file_exists($sqlFile)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Length: ' . filesize($sqlFile));
        header('Pragma: no-cache');
        readfile($sqlFile);
        exit;
    } else {
        flash_set('文件不存在', 'error');
        header('Location: ?p=backup');
        exit;
    }
}

// 获取所有备份文件
$backups = [];
$files = glob($backupDir . '/*.sql');
foreach ($files as $file) {
    $filename = basename($file);
    $metaFile = $backupDir . '/' . pathinfo($filename, PATHINFO_FILENAME) . '.meta';
    $meta = [];

    if (file_exists($metaFile)) {
        $meta = json_decode(file_get_contents($metaFile), true) ?? [];
    }

    $backups[] = [
        'filename' => $filename,
        'file_size' => format_file_size(filesize($file)),
        'created_at' => $meta['created_at'] ?? date('Y-m-d H:i:s', filemtime($file)),
        'description' => $meta['description'] ?? '',
        'created_by' => $meta['created_by'] ?? 'unknown'
    ];
}

// 按创建时间倒序排列
usort($backups, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// PHP 导出函数（备用方案）
function create_backup_php($pdo, $backupDir, $filename) {
    $backupFile = $backupDir . '/' . $filename;
    $handle = fopen($backupFile, 'w');

    if (!$handle) {
        return false;
    }

    // 获取所有表
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        // 获取创建表语句
        $createTable = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch();
        fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
        fwrite($handle, $createTable['Create Table'] . ";\n\n");

        // 获取表数据
        $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($rows)) {
            $columns = array_keys($rows[0]);
            fwrite($handle, "INSERT INTO `{$table}` (`" . implode('`,`', $columns) . "`) VALUES\n");

            foreach ($rows as $i => $row) {
                $values = array_map(function($val) {
                    if ($val === null) {
                        return 'NULL';
                    } elseif (is_numeric($val)) {
                        return $val;
                    } else {
                        return "'" . addslashes($val) . "'";
                    }
                }, $row);

                fwrite($handle, "(" . implode(',', $values) . ")");
                fwrite($handle, ($i < count($rows) - 1) ? ",\n" : ";\n\n");
            }
        }
    }

    fclose($handle);
    return $backupFile;
}

// 格式化文件大小
function format_file_size($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}
?>
<?php include __DIR__ . "/../templates/header.php"; ?>

<!-- 页面标题 -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1><i class="bi bi-database"></i> 数据库备份与还原</h1>
  <a class="btn btn-secondary" href="?p=assets"><i class="bi bi-arrow-left"></i> 返回</a>
</div>

<!-- 创建备份 -->
<div class="row mb-4">
  <div class="col-md-8">
    <div class="card">
      <div class="card-header bg-primary text-white">
        <h6 class="mb-0"><i class="bi bi-plus-circle"></i> 创建新备份</h6>
      </div>
      <div class="card-body">
        <form method="post">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>" />
          <input type="hidden" name="action" value="backup" />
          <div class="mb-3">
            <label class="form-label">备份描述（可选）</label>
            <input type="text" class="form-control" name="description" placeholder="例如：升级前备份、月度备份等" />
          </div>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-download"></i> 创建备份
          </button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card">
      <div class="card-header bg-info text-white">
        <h6 class="mb-0"><i class="bi bi-info-circle"></i> 备份说明</h6>
      </div>
      <div class="card-body">
        <ul class="mb-0 small">
          <li>备份文件保存在 <code>/backups</code> 目录</li>
          <li>备份包含完整的数据库结构和数据</li>
          <li>建议定期备份，特别是在重要操作前</li>
          <li>还原功能请使用数据库管理工具</li>
          <li>可下载备份文件后使用 phpMyAdmin 等工具还原</li>
        </ul>
      </div>
    </div>
  </div>
</div>

<!-- 备份文件列表 -->
<div class="card">
  <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
    <h6 class="mb-0"><i class="bi bi-list"></i> 备份文件列表 (<?= count($backups) ?> 个)</h6>
  </div>
  <div class="card-body p-0">
    <?php if (empty($backups)): ?>
      <div class="text-center py-5 text-muted">
        <i class="bi bi-inbox" style="font-size: 3rem;"></i>
        <p class="mt-3">暂无备份文件</p>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-striped table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>文件名</th>
              <th>描述</th>
              <th>创建时间</th>
              <th>文件大小</th>
              <th>创建者</th>
              <th>操作</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($backups as $backup): ?>
              <tr>
                <td><code><?= h($backup['filename']) ?></code></td>
                <td><?= h($backup['description'] ?: '-') ?></td>
                <td><?= h($backup['created_at']) ?></td>
                <td><?= h($backup['file_size']) ?></td>
                <td><?= h($backup['created_by']) ?></td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <a class="btn btn-primary" href="?p=backup&action=download&file=<?= urlencode($backup['filename']) ?>" title="下载">
                      <i class="bi bi-download"></i> 下载
                    </a>
                    <a class="btn btn-danger" href="?p=backup&action=delete&file=<?= urlencode($backup['filename']) ?>&csrf_token=<?= csrf_token() ?>"
                       onclick="return confirm('确定要删除此备份文件吗？此操作不可恢复。')" title="删除">
                      <i class="bi bi-trash"></i> 删除
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . "/../templates/footer.php"; ?>

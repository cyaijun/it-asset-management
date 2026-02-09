<?php
// pages/license_assets.php - License资产统计
require_admin();

// 搜索和筛选条件
$search = trim($_GET['search'] ?? '');
$expiryFilter = $_GET['expiry'] ?? ''; // expired, expiring_soon, all
$days = isset($_GET['days']) ? (int)$_GET['days'] : 30; // 即将到期的天数

// 构建查询
$where = ["a.IsLicense = 1"];
$params = [];

if ($search) {
    $where[] = "(a.Tag LIKE ? OR a.Name LIKE ? OR a.SerialNumber LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

// 到期时间筛选
$today = date('Y-m-d');
switch ($expiryFilter) {
    case 'expired':
        $where[] = "a.LicenseExpiry < ?";
        $params[] = $today;
        break;
    case 'expiring_soon':
        $futureDate = date('Y-m-d', strtotime("+{$days} days"));
        $where[] = "a.LicenseExpiry >= ? AND a.LicenseExpiry <= ?";
        $params[] = $today;
        $params[] = $futureDate;
        break;
    // 'all' 不添加条件
}

$whereClause = implode(' AND ', $where);

// 获取总数
$countSql = "SELECT COUNT(*) FROM Assets a LEFT JOIN Users u ON a.OwnerId = u.Id WHERE {$whereClause}";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$total = $stmt->fetchColumn();

// 获取数据
$sql = "SELECT a.*, u.Username as OwnerUsername, u.FullName as OwnerFullName
        FROM Assets a
        LEFT JOIN Users u ON a.OwnerId = u.Id
        WHERE {$whereClause}
        ORDER BY a.LicenseExpiry ASC, a.Id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$licenses = $stmt->fetchAll();

// 统计信息
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM Assets WHERE IsLicense = 1")->fetchColumn(),
    'expired' => $pdo->query("SELECT COUNT(*) FROM Assets WHERE IsLicense = 1 AND LicenseExpiry < CURDATE()")->fetchColumn(),
    'valid' => $pdo->query("SELECT COUNT(*) FROM Assets WHERE IsLicense = 1 AND LicenseExpiry >= CURDATE()")->fetchColumn(),
    'expiring_30' => $pdo->query("SELECT COUNT(*) FROM Assets WHERE IsLicense = 1 AND LicenseExpiry >= CURDATE() AND LicenseExpiry <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->fetchColumn(),
    'expiring_90' => $pdo->query("SELECT COUNT(*) FROM Assets WHERE IsLicense = 1 AND LicenseExpiry >= CURDATE() AND LicenseExpiry <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)")->fetchColumn(),
];
?>
<?php include __DIR__ . "/../templates/header.php"; ?>

<!-- 页面标题和操作按钮 -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1><i class="bi bi-key"></i> License资产统计</h1>
  <a class="btn btn-secondary" href="?p=assets"><i class="bi bi-arrow-left"></i> 返回资产总览</a>
</div>

<!-- 统计卡片 -->
<div class="row mb-4">
  <div class="col-md-2">
    <div class="card text-center border-primary">
      <div class="card-body py-2">
        <h5 class="card-title mb-1 text-primary"><?= $stats['total'] ?></h5>
        <small class="text-muted">License总数</small>
      </div>
    </div>
  </div>
  <div class="col-md-2">
    <div class="card text-center border-success">
      <div class="card-body py-2">
        <h5 class="card-title mb-1 text-success"><?= $stats['valid'] ?></h5>
        <small class="text-muted">有效期内的</small>
      </div>
    </div>
  </div>
  <div class="col-md-2">
    <div class="card text-center border-danger">
      <div class="card-body py-2">
        <h5 class="card-title mb-1 text-danger"><?= $stats['expired'] ?></h5>
        <small class="text-muted">已过期</small>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-center border-warning">
      <div class="card-body py-2">
        <h5 class="card-title mb-1 text-warning"><?= $stats['expiring_30'] ?></h5>
        <small class="text-muted">30天内到期</small>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-center border-info">
      <div class="card-body py-2">
        <h5 class="card-title mb-1 text-info"><?= $stats['expiring_90'] ?></h5>
        <small class="text-muted">90天内到期</small>
      </div>
    </div>
  </div>
</div>

<!-- 搜索和筛选表单 -->
<div class="card mb-4">
  <div class="card-body">
    <form class="row g-3" method="get">
      <input type="hidden" name="p" value="license_assets" />
      <div class="col-md-4">
        <label class="form-label">搜索</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-search"></i></span>
          <input type="text" class="form-control" name="search" placeholder="标签/名称/序列号" value="<?= h($search) ?>" />
        </div>
      </div>
      <div class="col-md-3">
        <label class="form-label">到期状态</label>
        <select class="form-select" name="expiry">
          <option value="">全部</option>
          <option value="all" <?= $expiryFilter === 'all' ? 'selected' : '' ?>>所有License</option>
          <option value="expired" <?= $expiryFilter === 'expired' ? 'selected' : '' ?>>已过期</option>
          <option value="expiring_soon" <?= $expiryFilter === 'expiring_soon' ? 'selected' : '' ?>>即将到期</option>
        </select>
      </div>
      <div class="col-md-2" id="daysDiv" style="display: <?= $expiryFilter === 'expiring_soon' ? 'block' : 'none' ?>;">
        <label class="form-label">到期天数</label>
        <input type="number" class="form-control" name="days" value="<?= $days ?>" min="1" max="365" />
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> 筛选</button>
        <a class="btn btn-outline-secondary ms-2" href="?p=license_assets"><i class="bi bi-arrow-counterclockwise"></i> 重置</a>
      </div>
    </form>
  </div>
</div>

<!-- License资产列表 -->
<div class="card">
  <div class="card-body p-0">
    <div style="max-height: 70vh; overflow-y: auto;">
      <table class="table table-striped table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>资产编号</th>
            <th>名称</th>
            <th>类别</th>
            <th>License到期时间</th>
            <th>到期状态</th>
            <th>状态</th>
            <th>归属</th>
            <th>操作</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($licenses)): ?>
            <tr><td colspan="9" class="text-center py-5">暂无License资产</td></tr>
          <?php else: ?>
            <?php foreach($licenses as $a): 
              $today = new DateTime();
              $expiryDate = $a['LicenseExpiry'] ? new DateTime($a['LicenseExpiry']) : null;
              $daysLeft = null;
              $statusClass = 'bg-secondary';
              $statusText = '未设置';
              
              if ($expiryDate) {
                if ($expiryDate < $today) {
                  $statusClass = 'bg-danger';
                  $statusText = '已过期';
                } else {
                  $diff = $today->diff($expiryDate);
                  $daysLeft = $diff->days;
                  if ($daysLeft <= 30) {
                    $statusClass = 'bg-danger';
                    $statusText = "剩余 {$daysLeft} 天";
                  } elseif ($daysLeft <= 90) {
                    $statusClass = 'bg-warning';
                    $statusText = "剩余 {$daysLeft} 天";
                  } else {
                    $statusClass = 'bg-success';
                    $statusText = "剩余 {$daysLeft} 天";
                  }
                }
              }
            ?>
              <tr>
                <td><span class="badge bg-light text-dark"><?= $a["Id"] ?></span></td>
                <td><strong><?= h($a["Tag"]) ?></strong></td>
                <td><?= h($a["Name"]) ?></td>
                <td><span class="badge bg-light text-dark"><?= h($a["Category"] ?: '-') ?></span></td>
                <td>
                  <?php if ($a["LicenseExpiry"]): ?>
                    <strong><?= h($a["LicenseExpiry"]) ?></strong>
                  <?php else: ?>
                    <span class="text-muted">未设置</span>
                  <?php endif; ?>
                </td>
                <td><span class="badge <?= $statusClass ?>"><?= $statusText ?></span></td>
                <td><span class="badge <?= status_class($a["Status"]) ?>"><?= status_text($a["Status"]) ?></span></td>
                <td>
                  <?php if ($a["OwnerUsername"]): ?>
                    <span><i class="bi bi-person"></i> <?= h($a["OwnerFullName"]) ?></span>
                    <br><small class="text-muted">@<?= h($a["OwnerUsername"]) ?></small>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <a class="btn btn-outline-primary" href="?p=asset_details&id=<?= $a["Id"] ?>" title="详情"><i class="bi bi-eye"></i></a>
                    <?php if (is_admin()): ?>
                      <a class="btn btn-outline-dark" href="?p=asset_edit&id=<?= $a["Id"] ?>" title="编辑"><i class="bi bi-pencil"></i></a>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
function toggleDaysInput() {
    const expiryFilter = document.querySelector('select[name="expiry"]').value;
    const daysDiv = document.getElementById('daysDiv');
    daysDiv.style.display = expiryFilter === 'expiring_soon' ? 'block' : 'none';
}

document.querySelector('select[name="expiry"]').addEventListener('change', toggleDaysInput);
</script>

<?php include __DIR__ . "/../templates/footer.php"; ?>

<?php
// pages/statistics.php - 统计报表
require_admin();

// 资产统计
$assetStats = $pdo->query("SELECT Status, COUNT(*) as count FROM Assets GROUP BY Status")->fetchAll(PDO::FETCH_KEY_PAIR);

// 按类别统计
$categoryStats = $pdo->query("SELECT Category, COUNT(*) as count FROM Assets WHERE Category IS NOT NULL AND Category != '' GROUP BY Category ORDER BY count DESC LIMIT 10")->fetchAll();

// 按部门统计借出情况
$deptStats = $pdo->query("SELECT u.Department, COUNT(*) as count FROM Assets a JOIN Users u ON a.OwnerId = u.Id WHERE a.Status = 'Assigned' AND u.Department IS NOT NULL GROUP BY u.Department ORDER BY count DESC LIMIT 10")->fetchAll();

// 最近交易
$recentTx = $pdo->query("SELECT t.*, a.Name as AssetName, u.FullName as UserName FROM AssetTransactions t JOIN Assets a ON t.AssetId = a.Id LEFT JOIN Users u ON t.UserId = u.Id ORDER BY t.CreatedAt DESC LIMIT 20")->fetchAll();

// 用户统计
$userStats = $pdo->query("SELECT Role, Status, COUNT(*) as count FROM Users GROUP BY Role, Status")->fetchAll();

// 总览数据
$totalAssets = $pdo->query("SELECT COUNT(*) FROM Assets")->fetchColumn();
$totalUsers = $pdo->query("SELECT COUNT(*) FROM Users")->fetchColumn();
$assignedAssets = $pdo->query("SELECT COUNT(*) FROM Assets WHERE Status = 'Assigned'")->fetchColumn();
$maintenanceAssets = $pdo->query("SELECT COUNT(*) FROM Assets WHERE Status = 'Maintenance'")->fetchColumn();
?>
<?php include __DIR__ . '/../templates/header.php'; ?>
<h1 class="mb-4"><i class="bi bi-bar-chart"></i> 统计报表</h1>

<!-- 总览卡片 -->
<div class="row mb-4">
  <div class="col-md-3">
    <div class="card text-center bg-primary text-white">
      <div class="card-body">
        <h3><?= $totalAssets ?></h3>
        <p class="mb-0">总资产数</p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-center bg-success text-white">
      <div class="card-body">
        <h3><?= $assetStats['InStock'] ?? 0 ?></h3>
        <p class="mb-0">在库资产</p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-center bg-info text-white">
      <div class="card-body">
        <h3><?= $assignedAssets ?></h3>
        <p class="mb-0">已领用</p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-center bg-warning text-dark">
      <div class="card-body">
        <h3><?= $totalUsers ?></h3>
        <p class="mb-0">总用户数</p>
      </div>
    </div>
  </div>
</div>

<!-- 资产状态分布 -->
<div class="row mb-4">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">资产状态分布</h5>
      </div>
      <div class="card-body">
        <?php foreach (['InStock' => '在库', 'Assigned' => '已领用', 'Maintenance' => '维修中', 'Disposed' => '已报废', 'Lost' => '已丢失'] as $key => $label): ?>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span><?= $label ?></span>
            <span class="badge <?= status_class($key) ?>"><?= $assetStats[$key] ?? 0 ?></span>
          </div>
          <div class="progress mb-3" style="height: 8px;">
            <div class="progress-bar <?= str_replace('bg-', 'bg-', status_class($key)) ?>" style="width: <?= round(($assetStats[$key] ?? 0) / max($totalAssets, 1) * 100, 1) ?>%"></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">用户统计</h5>
      </div>
      <div class="card-body">
        <div class="row text-center">
          <div class="col-6 mb-3">
            <h4><?= $pdo->query("SELECT COUNT(*) FROM Users WHERE Role = 'admin'")->fetchColumn() ?></h4>
            <small class="text-muted">管理员</small>
          </div>
          <div class="col-6 mb-3">
            <h4><?= $pdo->query("SELECT COUNT(*) FROM Users WHERE Role = 'user'")->fetchColumn() ?></h4>
            <small class="text-muted">普通用户</small>
          </div>
          <div class="col-6">
            <h4><?= $pdo->query("SELECT COUNT(*) FROM Users WHERE Status = 'active'")->fetchColumn() ?></h4>
            <small class="text-muted">启用用户</small>
          </div>
          <div class="col-6">
            <h4><?= $pdo->query("SELECT COUNT(*) FROM Users WHERE Status = 'disabled'")->fetchColumn() ?></h4>
            <small class="text-muted">禁用用户</small>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- 按类别统计 -->
<?php if (!empty($categoryStats)): ?>
<div class="row mb-4">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">资产类别分布 (Top 10)</h5>
      </div>
      <div class="card-body">
        <table class="table table-sm">
          <thead><tr><th>类别</th><th>数量</th><th>占比</th></tr></thead>
          <tbody>
            <?php foreach ($categoryStats as $cat): ?>
              <tr>
                <td><?= h($cat['Category']) ?></td>
                <td><?= $cat['count'] ?></td>
                <td>
                  <div class="progress" style="height: 6px; width: 100px;">
                    <div class="progress-bar bg-primary" style="width: <?= round($cat['count'] / max($totalAssets, 1) * 100, 1) ?>%"></div>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- 最近交易 -->
<div class="card">
  <div class="card-header">
    <h5 class="mb-0">最近交易记录</h5>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-striped mb-0">
        <thead><tr><th>时间</th><th>类型</th><th>资产</th><th>用户</th></tr></thead>
        <tbody>
          <?php foreach ($recentTx as $tx): ?>
            <tr>
              <td><small><?= h($tx['CreatedAt']) ?></small></td>
              <td><span class="badge bg-secondary"><?= transaction_type_text($tx['TransactionType']) ?></span></td>
              <td><?= h($tx['AssetName']) ?></td>
              <td><?= h($tx['UserName'] ?? '-') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>

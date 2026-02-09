<?php
// pages/assets_details.php - 资产详情

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$currentUser = get_current_user_info();

// user组用户只能查看自己的资产
$userIdFilter = '';
if (!is_admin() && $currentUser) {
    $userIdFilter = ' AND a.OwnerId = ' . (int)$currentUser['id'];
}

$stmt = $pdo->prepare("SELECT a.*, u.Username as OwnerUsername, u.FullName as OwnerFullName, u.Department as OwnerDepartment
                      FROM Assets a
                      LEFT JOIN Users u ON a.OwnerId = u.Id
                      WHERE a.Id = ? {$userIdFilter}");
$stmt->execute([$id]);
$asset = $stmt->fetch();

if (!$asset) {
    flash_set('未找到该资产或无权访问');
    header('Location: ?p=assets');
    exit;
}

$txs = $pdo->prepare("SELECT t.*, u.Username, u.FullName FROM AssetTransactions t LEFT JOIN Users u ON t.UserId = u.Id WHERE t.AssetId = ? ORDER BY t.CreatedAt DESC LIMIT 50");
$txs->execute([$id]);
$txlist = $txs->fetchAll();

// 获取维修记录
$maintenance = $pdo->prepare("SELECT * FROM Maintenance WHERE AssetId = ? ORDER BY CreatedAt DESC LIMIT 10");
$maintenance->execute([$id]);
$maintenanceList = $maintenance->fetchAll();

// 获取丢失记录
$loss = $pdo->prepare("SELECT * FROM LossRecords WHERE AssetId = ? ORDER BY LostAt DESC LIMIT 10");
$loss->execute([$id]);
$lossList = $loss->fetchAll();
?>
<?php include __DIR__ . '/../templates/header.php'; ?>
<div class="row">
  <div class="col-md-7">
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><?= h($asset["Name"]) ?></h5>
        <span class="badge <?= status_class($asset["Status"]) ?>"><?= status_text($asset["Status"]) ?></span>
      </div>
      <div class="card-body">
        <dl class="row">
          <dt class="col-sm-3">标签:</dt><dd class="col-sm-9"><code><?= h($asset["Tag"]) ?></code></dd>
          <dt class="col-sm-3">类别:</dt><dd class="col-sm-9"><?= h($asset["Category"] ?: '-') ?></dd>
          <dt class="col-sm-3">型号:</dt><dd class="col-sm-9"><?= h($asset["Model"] ?: '-') ?></dd>
          <dt class="col-sm-3">序列号:</dt><dd class="col-sm-9"><code><?= h($asset["SerialNumber"] ?: '-') ?></code></dd>
          <dt class="col-sm-3">位置:</dt><dd class="col-sm-9"><?= h($asset["Location"] ?: '-') ?></dd>
          <dt class="col-sm-3">供应商:</dt><dd class="col-sm-9"><?= h($asset["Supplier"] ?: '-') ?></dd>
          <dt class="col-sm-3">购买日期:</dt><dd class="col-sm-9"><?= h($asset["PurchaseDate"] ?: '-') ?></dd>
          <dt class="col-sm-3">保修到期:</dt><dd class="col-sm-9">
            <?php if ($asset["WarrantyExpiry"]): ?>
              <span class="<?= strtotime($asset["WarrantyExpiry"]) < time() ? 'text-danger' : '' ?>"><?= h($asset["WarrantyExpiry"]) ?></span>
            <?php else: ?>
              -
            <?php endif; ?>
          </dd>
          <dt class="col-sm-3">采购价格:</dt><dd class="col-sm-9"><?= $asset["PurchasePrice"] ? '¥' . number_format($asset["PurchasePrice"], 2) : '-' ?></dd>
          <dt class="col-sm-3">当前归属:</dt><dd class="col-sm-9">
            <?php if ($asset["OwnerUsername"]): ?>
              <strong><?= h($asset["OwnerFullName"]) ?></strong>
              <small class="text-muted">(<?= h($asset["OwnerUsername"]) ?>, <?= h($asset["OwnerDepartment"] ?: '-') ?>)</small>
            <?php else: ?>
              <span class="text-muted">无</span>
            <?php endif; ?>
          </dd>
          <dt class="col-sm-3">创建时间:</dt><dd class="col-sm-9"><small class="text-muted"><?= h($asset["CreatedAt"]) ?></small></dd>
          <?php if ($asset["Notes"]): ?>
          <dt class="col-sm-3">备注:</dt><dd class="col-sm-9"><?= h($asset["Notes"]) ?></dd>
          <?php endif; ?>
        </dl>
      </div>
      <div class="card-footer">
        <div class="btn-group">
          <?php if (is_admin()): ?>
            <a class="btn btn-success" href="?p=asset_checkout&id=<?= $asset["Id"] ?>"><i class="bi bi-box-arrow-right"></i> 领用</a>
            <a class="btn btn-outline-dark" href="?p=asset_credentials&asset_id=<?= $asset["Id"] ?>"><i class="bi bi-shield-lock"></i> 凭证管理</a>
          <?php endif; ?>
          <?php if ($asset["OwnerId"] && $asset["Status"] === 'Assigned'): ?>
            <a class="btn btn-warning" href="?p=asset_return&id=<?= $asset["Id"] ?>"><i class="bi bi-box-arrow-in-left"></i> 归还</a>
          <?php endif; ?>
          <?php if (is_admin() && $asset["OwnerId"] && $asset["Status"] === 'Assigned'): ?>
            <a class="btn btn-primary" href="?p=asset_transfer&id=<?= $asset["Id"] ?>"><i class="bi bi-arrow-left-right"></i> 转移</a>
          <?php endif; ?>
          <?php if (is_admin()): ?>
            <a class="btn btn-info" href="?p=asset_maintenance&id=<?= $asset["Id"] ?>"><i class="bi bi-tools"></i> 维修</a>
            <a class="btn btn-danger" href="?p=asset_dispose&id=<?= $asset["Id"] ?>"><i class="bi bi-trash"></i> 报废</a>
            <a class="btn btn-dark" href="?p=asset_loss&id=<?= $asset["Id"] ?>"><i class="bi bi-exclamation-triangle"></i> 标记丢失</a>
            <a class="btn btn-secondary" href="?p=asset_edit&id=<?= $asset["Id"] ?>"><i class="bi bi-pencil"></i> 编辑</a>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- 交易记录 -->
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-clock-history"></i> 交易记录</h5>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped table-sm mb-0">
            <thead><tr><th>时间</th><th>类型</th><th>用户</th><th>备注</th></tr></thead>
            <tbody>
              <?php foreach($txlist as $t): ?>
                <tr>
                  <td><small><?= h($t["CreatedAt"]) ?></small></td>
                  <td><span class="badge bg-secondary"><?= transaction_type_text($t["TransactionType"]) ?></span></td>
                  <td><?= $t["Username"] ? h($t["FullName"] ?: $t["Username"]) : "-" ?></td>
                  <td><small><?= h($t["Notes"] ?: '-') ?></small></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-5">
    <!-- 二维码 -->
    <div class="card text-center mb-3">
      <div class="card-body">
        <h5 class="card-title">资产二维码</h5>
        <img src="qr.php?id=<?= $asset["Id"] ?>" alt="QR" class="img-fluid mb-2" style="max-width: 250px;" />
        <p class="small text-muted">扫描以打开详情页</p>
        <div class="btn-group btn-group-sm">
          <a class="btn btn-outline-primary" href="qr.php?id=<?= $asset["Id"] ?>" target="_blank">
            <i class="bi bi-download"></i> 下载二维码
          </a>
          <a class="btn btn-outline-secondary" href="?p=print_label&ids=<?= $asset["Id"] ?>" target="_blank">
            <i class="bi bi-printer"></i> 打印标签
          </a>
        </div>
      </div>
    </div>

    <!-- 维修记录 -->
    <?php if (!empty($maintenanceList)): ?>
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-tools"></i> 维修记录</h5>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead><tr><th>类型</th><th>状态</th><th>时间</th></tr></thead>
            <tbody>
              <?php foreach($maintenanceList as $m): ?>
                <tr>
                  <td><?= h($m["Type"]) ?></td>
                  <td><span class="badge bg-<?= $m["Status"] === 'completed' ? 'success' : ($m["Status"] === 'in_progress' ? 'warning' : 'secondary') ?>"><?= h($m["Status"]) ?></span></td>
                  <td><small><?= h($m["CreatedAt"]) ?></small></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- 丢失记录 -->
    <?php if (!empty($lossList)): ?>
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> 丢失记录</h5>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead><tr><th>类型</th><th>原因</th><th>时间</th></tr></thead>
            <tbody>
              <?php foreach($lossList as $l): ?>
                <tr>
                  <td><span class="badge bg-danger"><?= h($l["LossType"]) ?></span></td>
                  <td><small><?= h(mb_substr($l["Reason"], 0, 30) . (mb_strlen($l["Reason"]) > 30 ? '...' : '')) ?></small></td>
                  <td><small><?= h($l["LostAt"]) ?></small></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/../templates/footer.php'; ?>
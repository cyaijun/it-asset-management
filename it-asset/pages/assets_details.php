<?php
// pages/assets_details.php - 资产详情（中文）
$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
$stmt = $pdo->prepare("SELECT a.*, u.Username as OwnerUsername, u.FullName as OwnerFullName FROM Assets a LEFT JOIN Users u ON a.OwnerId = u.Id WHERE a.Id = ?");
$stmt->execute([$id]);
$asset = $stmt->fetch();
if (!$asset) { echo "未找到该资产"; exit; }
$txs = $pdo->prepare("SELECT t.*, u.Username FROM AssetTransactions t LEFT JOIN Users u ON t.UserId = u.Id WHERE t.AssetId = ? ORDER BY t.CreatedAt DESC");
$txs->execute([$id]);
$txlist = $txs->fetchAll();
?>
<?php include __DIR__ . "/../templates/header.php"; ?>
<div class="row">
  <div class="col-md-6">
    <h2><?= h($asset["Name"]) ?> (<?= h($asset["Tag"]) ?>)</h2>
    <ul class="list-group">
      <li class="list-group-item"><strong>型号:</strong> <?= h($asset["Model"]) ?></li>
      <li class="list-group-item"><strong>序列号:</strong> <?= h($asset["SerialNumber"]) ?></li>
      <li class="list-group-item"><strong>状态:</strong> <?= h($asset["Status"]) ?></li>
      <li class="list-group-item"><strong>位置:</strong> <?= h($asset["Location"]) ?></li>
      <li class="list-group-item"><strong>供应商:</strong> <?= h($asset["Supplier"]) ?></li>
      <li class="list-group-item"><strong>归属:</strong> <?= $asset["OwnerUsername"] ? h($asset["OwnerFullName"] . " (" . $asset["OwnerUsername"] . ")") : "-" ?></li>
    </ul>
    <div class="mt-3">
      <a class="btn btn-success" href="?p=asset_checkout&id=<?= $asset["Id"] ?>">领用</a>
      <a class="btn btn-warning" href="?p=asset_return&id=<?= $asset["Id"] ?>">归还</a>
    </div>
  </div>
  <div class="col-md-6 text-center">
    <p>二维码（扫描以打开详情）</p>
    <img src="qr.php?id=<?= $asset["Id"] ?>" alt="QR" class="img-fluid" />
    <p class="mt-2"><a class="btn btn-outline-primary" href="qr.php?id=<?= $asset["Id"] ?>" target="_blank">下载二维码 PNG</a></p>
  </div>
</div>

<h3 class="mt-4">交易 / 审计</h3>
<table class="table">
  <thead><tr><th>时间</th><th>类型</th><th>用户</th><th>备注</th></tr></thead>
  <tbody>
    <?php foreach($txlist as $t): ?>
      <tr>
        <td><?= h($t["CreatedAt"]) ?></td>
        <td><?= h($t["TransactionType"]) ?></td>
        <td><?= $t["Username"] ? h($t["Username"]) : "-" ?></td>
        <td><?= h($t["Notes"]) ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php include __DIR__ . "/../templates/footer.php"; ?>
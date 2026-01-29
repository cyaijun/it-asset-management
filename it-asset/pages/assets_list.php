<?php
// pages/assets_list.php - 资产总览（中文）
$flash = flash_get();
$perPage = 50;
$page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;
$offset = paginate_offset($page, $perPage);

$stmt = $pdo->prepare("SELECT a.*, u.Username as OwnerUsername, u.FullName as OwnerFullName FROM Assets a LEFT JOIN Users u ON a.OwnerId = u.Id ORDER BY a.Id DESC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $perPage, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$assets = $stmt->fetchAll();
?>
<?php include __DIR__ . "/../templates/header.php"; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1>资产总览</h1>
  <div>
    <a class="btn btn-primary" href="?p=asset_create">入库</a>
    <a class="btn btn-secondary" href="?p=users">用户管理</a>
    <a class="btn btn-info" href="?p=scan">手机扫码</a>
  </div>
</div>

<?php if ($flash): ?>
  <div class="alert alert-info"><?= h($flash) ?></div>
<?php endif; ?>

<table class="table table-striped table-responsive">
  <thead><tr><th>ID</th><th>标签</th><th>名称</th><th>型号</th><th>状态</th><th>归属</th><th>操作</th></tr></thead>
  <tbody>
    <?php foreach($assets as $a): ?>
      <tr>
        <td><?= h($a["Id"]) ?></td>
        <td><?= h($a["Tag"]) ?></td>
        <td><?= h($a["Name"]) ?></td>
        <td><?= h($a["Model"]) ?></td>
        <td><?= h($a["Status"]) ?></td>
        <td><?= $a["OwnerUsername"] ? h($a["OwnerFullName"] . " (" . $a["OwnerUsername"] . ")") : "-" ?></td>
        <td>
          <a class="btn btn-sm btn-outline-primary" href="?p=asset_details&id=<?= $a["Id"] ?>">详情</a>
          <a class="btn btn-sm btn-outline-success" href="?p=asset_checkout&id=<?= $a["Id"] ?>">领用</a>
          <a class="btn btn-sm btn-outline-warning" href="?p=asset_return&id=<?= $a["Id"] ?>">归还</a>
          <a class="btn btn-sm btn-outline-dark" target="_blank" href="qr.php?id=<?= $a["Id"] ?>">二维码</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php include __DIR__ . "/../templates/footer.php"; ?>
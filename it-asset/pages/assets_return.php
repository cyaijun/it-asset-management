<?php
// pages/assets_return.php - 资产归还（中文）
$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
$stmt = $pdo->prepare("SELECT * FROM Assets WHERE Id = ?");
$stmt->execute([$id]);
$asset = $stmt->fetch();
if (!$asset) { echo "未找到该资产"; exit; }
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $notes = trim($_POST["notes"] ?? "");
    $prevOwner = $asset["OwnerId"];
    $pdo->prepare("UPDATE Assets SET OwnerId = NULL, Status = 'InStock' WHERE Id = ?")->execute([$id]);
    $pdo->prepare("INSERT INTO AssetTransactions (AssetId, UserId, TransactionType, Notes, CreatedAt) VALUES (?, ?, 'CheckIn', ?, NOW())")
        ->execute([$id, $prevOwner, $notes ?: "归还"]);
    flash_set("归还成功");
    header("Location: ?p=assets");
    exit;
}
?>
<?php include __DIR__ . "/../templates/header.php"; ?>
<h1>归还：<?= h($asset["Name"]) ?> (<?= h($asset["Tag"]) ?>)</h1>
<p>当前归属: <?= $asset["OwnerId"] ? h($asset["OwnerId"]) : "无" ?></p>
<form method="post" class="row g-3">
  <div class="col-md-8"><label class="form-label">备注</label><input class="form-control" name="notes" /></div>
  <div class="col-12"><button class="btn btn-warning" type="submit">确认归还</button> <a class="btn btn-secondary" href="?p=assets">返回</a></div>
</form>
<?php include __DIR__ . "/../templates/footer.php"; ?>
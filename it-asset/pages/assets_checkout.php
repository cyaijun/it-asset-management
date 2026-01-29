<?php
// pages/assets_checkout.php - 资产领用（中文）
$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
$stmt = $pdo->prepare("SELECT * FROM Assets WHERE Id = ?");
$stmt->execute([$id]);
$asset = $stmt->fetch();
if (!$asset) { echo "未找到该资产"; exit; }
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $userId = isset($_POST["userId"]) ? (int)$_POST["userId"] : 0;
    $notes = trim($_POST["notes"] ?? "");
    $u = $pdo->prepare("SELECT Id FROM Users WHERE Id = ?");
    $u->execute([$userId]);
    if (!$u->fetch()) { flash_set("用户不存在"); header("Location: ?p=asset_checkout&id={$id}"); exit; }
    $pdo->prepare("UPDATE Assets SET OwnerId = ?, Status = 'Assigned' WHERE Id = ?")->execute([$userId, $id]);
    $pdo->prepare("INSERT INTO AssetTransactions (AssetId, UserId, TransactionType, Notes, CreatedAt) VALUES (?, ?, 'CheckOut', ?, NOW())")
        ->execute([$id, $userId, $notes ?: "领用给用户ID {$userId}"]);
    flash_set("领用成功");
    header("Location: ?p=assets");
    exit;
}
?>
<?php include __DIR__ . "/../templates/header.php"; ?>
<h1>领用���<?= h($asset["Name"]) ?> (<?= h($asset["Tag"]) ?>)</h1>
<form method="post" class="row g-3">
  <div class="col-md-6"><label class="form-label">领用用户 ID</label><input class="form-control" name="userId" /></div>
  <div class="col-md-6"><label class="form-label">备注</label><input class="form-control" name="notes" /></div>
  <div class="col-12"><button class="btn btn-success" type="submit">确认领用</button> <a class="btn btn-secondary" href="?p=assets">返回</a></div>
</form>
<?php include __DIR__ . "/../templates/footer.php"; ?>
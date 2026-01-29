<?php
// pages/assets_create.php - 资产入库（中文）
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $tag = trim($_POST["Tag"] ?? "");
    $name = trim($_POST["Name"] ?? "");
    if ($tag === "" || $name === "") {
        flash_set("Tag 与 Name 为必填");
        header("Location: ?p=asset_create");
        exit;
    }
    $stmt = $pdo->prepare("INSERT INTO Assets (Tag, Name, Category, Model, SerialNumber, Location, Supplier, PurchaseDate, Status, CreatedAt) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'InStock', NOW())");
    $stmt->execute([$tag, $name, $_POST["Category"] ?? "", $_POST["Model"] ?? "", $_POST["SerialNumber"] ?? "", $_POST["Location"] ?? "", $_POST["Supplier"] ?? "", $_POST["PurchaseDate"] ?? null]);
    $assetId = $pdo->lastInsertId();
    $pdo->prepare("INSERT INTO AssetTransactions (AssetId, TransactionType, Notes, CreatedAt) VALUES (?, 'Create', ?, NOW())")->execute([$assetId, "资产入库"]);
    flash_set("入库成功");
    header("Location: ?p=assets");
    exit;
}
?>
<?php include __DIR__ . "/../templates/header.php"; ?>
<h1>资产入库</h1>
<form method="post" class="row g-3">
  <div class="col-md-6"><label class="form-label">标签 (Tag)</label><input class="form-control" name="Tag" /></div>
  <div class="col-md-6"><label class="form-label">名称</label><input class="form-control" name="Name" /></div>
  <div class="col-md-4"><label class="form-label">类别</label><input class="form-control" name="Category" /></div>
  <div class="col-md-4"><label class="form-label">型号</label><input class="form-control" name="Model" /></div>
  <div class="col-md-4"><label class="form-label">序列号</label><input class="form-control" name="SerialNumber" /></div>
  <div class="col-md-6"><label class="form-label">位置</label><input class="form-control" name="Location" /></div>
  <div class="col-md-6"><label class="form-label">供应商</label><input class="form-control" name="Supplier" /></div>
  <div class="col-md-4"><label class="form-label">购买日期</label><input type="date" class="form-control" name="PurchaseDate" /></div>
  <div class="col-12"><button class="btn btn-primary" type="submit">入库</button> <a class="btn btn-secondary" href="?p=assets">返回</a></div>
</form>
<?php include __DIR__ . "/../templates/footer.php"; ?>
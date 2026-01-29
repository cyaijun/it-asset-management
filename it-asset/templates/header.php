<?php
// header.php - 中文页眉
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>IT 资产管理</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-3">
  <div class="container-fluid">
    <a class="navbar-brand" href="?p=assets">IT 资产管理</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="?p=assets">资产总览</a></li>
        <li class="nav-item"><a class="nav-link" href="?p=asset_create">资产入库</a></li>
        <li class="nav-item"><a class="nav-link" href="?p=users">用户管理</a></li>
        <li class="nav-item"><a class="nav-link" href="?p=scan">手机扫码</a></li>
      </ul>
    </div>
  </div>
</nav>
<div class="container">
<?php
$flash = flash_get();
if ($flash) {
    echo "<div class=\"alert alert-info\">" . h($flash) . "</div>";
}
?>
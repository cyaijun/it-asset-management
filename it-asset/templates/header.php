<?php
// header.php - 中文页眉
$user = get_current_user_info();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>IT 资产管理系统</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet" />
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-3">
  <div class="container-fluid">
    <a class="navbar-brand" href="?p=assets"><i class="bi bi-box-seam"></i> IT 资产管理系统</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav me-auto">
        <?php if ($user): ?>
        <li class="nav-item"><a class="nav-link" href="?p=assets"><i class="bi bi-list-ul"></i> 资产总览</a></li>
        <li class="nav-item"><a class="nav-link" href="?p=scan"><i class="bi bi-qr-code-scan"></i> 手机扫码</a></li>
        <?php if (is_admin()): ?>
        <li class="nav-item"><a class="nav-link" href="?p=asset_create"><i class="bi bi-plus-circle"></i> 资产入库</a></li>
        <li class="nav-item"><a class="nav-link" href="?p=users"><i class="bi bi-people"></i> 用户管理</a></li>
        <li class="nav-item"><a class="nav-link" href="?p=categories"><i class="bi bi-tags"></i> 类别管理</a></li>
        <li class="nav-item"><a class="nav-link" href="?p=license_assets"><i class="bi bi-key"></i> License管理</a></li>
        <li class="nav-item"><a class="nav-link" href="?p=statistics"><i class="bi bi-bar-chart"></i> 统计报表</a></li>
        <li class="nav-item"><a class="nav-link" href="?p=backup"><i class="bi bi-database"></i> 数据备份</a></li>
        <?php endif; ?>
        <?php endif; ?>
      </ul>
      <ul class="navbar-nav">
        <?php if ($user): ?>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
            <i class="bi bi-person-circle"></i> <?= h($user['full_name'] ?: $user['username']) ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><h6 class="dropdown-header">角色: <?= $user['role'] === 'admin' ? '管理员' : '普通用户' ?></h6></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="?p=change_password"><i class="bi bi-key"></i> 修改密码</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="?p=logout"><i class="bi bi-box-arrow-right"></i> 退出登录</a></li>
          </ul>
        </li>
        <?php else: ?>
        <li class="nav-item"><a class="nav-link" href="?p=login"><i class="bi bi-box-arrow-in-right"></i> 登录</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<div class="container">
<?php
$flash = flash_get();
if ($flash) {
    echo "<div class=\"alert alert-info alert-dismissible fade show\">" . h($flash) . "<button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\"></button></div>";
}
?>
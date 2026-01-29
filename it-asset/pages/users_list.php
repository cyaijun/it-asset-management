<?php
// pages/users_list.php - 用户管理（中文）
$stmt = $pdo->query("SELECT * FROM Users ORDER BY Id DESC");
$users = $stmt->fetchAll();
?>
<?php include __DIR__ . "/../templates/header.php"; ?>
<h1>用户管理</h1>
<p><a class="btn btn-primary" href="?p=user_create">新增用户</a></p>
<table class="table">
  <thead><tr><th>ID</th><th>用户名</th><th>全名</th><th>邮箱</th></tr></thead>
  <tbody>
    <?php foreach($users as $u): ?>
      <tr>
        <td><?= h($u["Id"]) ?></td>
        <td><?= h($u["Username"]) ?></td>
        <td><?= h($u["FullName"]) ?></td>
        <td><?= h($u["Email"]) ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php include __DIR__ . "/../templates/footer.php"; ?>
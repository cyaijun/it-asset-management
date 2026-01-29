<?php
// pages/users_create.php - 新增用户（中文）
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["Username"] ?? "");
    $fullname = trim($_POST["FullName"] ?? "");
    $email = trim($_POST["Email"] ?? "");
    if ($username === "" || $fullname === "") {
        flash_set("Username 与 FullName 必填");
        header("Location: ?p=user_create");
        exit;
    }
    $stmt = $pdo->prepare("INSERT INTO Users (Username, FullName, Email, CreatedAt) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$username, $fullname, $email]);
    flash_set("用户已创建");
    header("Location: ?p=users");
    exit;
}
?>
<?php include __DIR__ . "/../templates/header.php"; ?>
<h1>新增用户</h1>
<form method="post" class="row g-3">
  <div class="col-md-4"><label class="form-label">用户名</label><input class="form-control" name="Username" /></div>
  <div class="col-md-4"><label class="form-label">全名</label><input class="form-control" name="FullName" /></div>
  <div class="col-md-4"><label class="form-label">Email</label><input class="form-control" name="Email" /></div>
  <div class="col-12"><button class="btn btn-primary" type="submit">创建</button> <a class="btn btn-secondary" href="?p=users">返回</a></div>
</form>
<?php include __DIR__ . "/../templates/footer.php"; ?>
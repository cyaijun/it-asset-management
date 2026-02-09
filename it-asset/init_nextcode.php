<?php
require __DIR__ . '/db.php';
require __DIR__ . '/lib/auth.php';
require __DIR__ . '/templates/header.php';

$messages = [];

try {
    // 检查 NextCode 字段是否存在
    $stmt = $pdo->query("SHOW COLUMNS FROM assetcategories LIKE 'NextCode'");
    $exists = $stmt->fetch();

    if (!$exists) {
        // 添加 NextCode 字段
        $pdo->exec("ALTER TABLE assetcategories ADD COLUMN NextCode INT DEFAULT 1 COMMENT '下一个编号'");
        $messages[] = ['type' => 'success', 'text' => 'NextCode 字段添加成功'];

        // 初始化现有类别的 NextCode 值
        // 根据该类别已有的资产数量来设置下一个编号
        $pdo->exec("UPDATE assetcategories SET NextCode = (
            SELECT COALESCE(COUNT(*), 0) + 1
            FROM Assets WHERE Assets.Category = assetcategories.Name
        ) WHERE NextCode = 1");

        $messages[] = ['type' => 'success', 'text' => '类别的 NextCode 值已根据现有资产数量初始化'];
    } else {
        $messages[] = ['type' => 'info', 'text' => 'NextCode 字段已存在，无需重复添加'];
    }

    // 显示当前各类别的下一个编号
    $categories = $pdo->query("SELECT Id, Name, NextCode, CodeRule FROM assetcategories ORDER BY Name")->fetchAll();

} catch (PDOException $e) {
    $messages[] = ['type' => 'danger', 'text' => '错误: ' . $e->getMessage()];
}
?>

<div class="container py-4">
  <h1>初始化 NextCode 字段</h1>

  <?php foreach ($messages as $msg): ?>
    <div class="alert alert-<?= $msg['type'] ?>"><?= h($msg['text']) ?></div>
  <?php endforeach; ?>

  <h3 class="mt-4">各类别当前状态</h3>
  <table class="table table-striped">
    <thead>
      <tr>
        <th>ID</th>
        <th>类别名称</th>
        <th>编号规则</th>
        <th>下一个序号</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($categories as $cat): ?>
        <tr>
          <td><?= $cat['Id'] ?></td>
          <td><?= h($cat['Name']) ?></td>
          <td><?= h($cat['CodeRule']) ?: '<span class="text-muted">未设置</span>' ?></td>
          <td><?= $cat['NextCode'] ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="mt-4">
    <a class="btn btn-primary" href="?p=categories">返回类别管理</a>
  </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>

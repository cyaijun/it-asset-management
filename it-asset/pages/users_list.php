<?php
// pages/users_list.php - 用户管理
require_admin();

$search = trim($_GET['search'] ?? '');
$status = isset($_GET['status']) ? $_GET['status'] : '';

$where = ['1=1'];
$params = [];

if ($search) {
    $where[] = "(Username LIKE ? OR FullName LIKE ? OR Email LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($status !== '') {
    $where[] = "Status = ?";
    $params[] = $status;
}

$whereClause = implode(' AND ', $where);

$sql = "SELECT * FROM Users WHERE {$whereClause} ORDER BY Id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>
<?php include __DIR__ . '/../templates/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1>用户管理</h1>
  <div class="btn-group">
    <a class="btn btn-primary" href="?p=user_create"><i class="bi bi-person-plus"></i> 新增用户</a>
    <a class="btn btn-success" href="?p=users_import"><i class="bi bi-upload"></i> 批量导入</a>
  </div>
</div>

<!-- 搜索 -->
<form class="row g-2 mb-3" method="get">
  <input type="hidden" name="p" value="users" />
  <div class="col-md-6">
    <div class="input-group">
      <input type="text" class="form-control" name="search" placeholder="搜索用户名/姓名/邮箱" value="<?= h($search) ?>" />
      <div class="col-md-3">
    <select class="form-select" name="status">
      <option value="">全部状态</option>
      <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>启用</option>
      <option value="disabled" <?= $status === 'disabled' ? 'selected' : '' ?>>禁用</option>
    </select>

  </div>
      <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> 搜索</button>
      <a class="btn btn-outline-secondary" href="?p=users">重置</a>
    </div>
  </div>



  </form>
<div style="max-height: 70vh; overflow-y: auto;">
  <table class="table table-striped table-hover">
    <thead><tr>
      <th>ID</th><th>用户名</th><th>姓名</th><th>邮箱</th><th>部门</th><th>角色</th><th>状态</th><th>最后登录</th><th>操作</th>
    </tr></thead>
    <tbody>
      <?php if (empty($users)): ?>
        <tr><td colspan="9" class="text-center">暂无用户</td></tr>
      <?php else: ?>
        <?php foreach($users as $u): ?>
          <tr>
            <td><?= h($u["Id"]) ?></td>
            <td><code><?= h($u["Username"]) ?></code></td>
            <td><?= h($u["FullName"]) ?></td>
            <td><?= h($u["Email"] ?: '-') ?></td>
            <td><?= h($u["Department"] ?: '-') ?></td>
            <td><span class="badge <?= $u['Role'] === 'admin' ? 'bg-danger' : 'bg-primary' ?>"><?= $u['Role'] === 'admin' ? '管理员' : '普通用户' ?></span></td>
            <td><span class="badge <?= $u['Status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>"><?= $u['Status'] === 'active' ? '启用' : '禁用' ?></span></td>
            <td><small class="text-muted"><?= h($u["LastLoginAt"] ?? '从未登录') ?></small></td>
            <td>
              <div class="btn-group btn-group-sm">
                <a class="btn btn-outline-primary" href="?p=user_edit&id=<?= $u["Id"] ?>" title="编辑"><i class="bi bi-pencil"></i></a>
                <a class="btn btn-outline-warning" href="?p=change_password&id=<?= $u["Id"] ?>" title="修改密码"><i class="bi bi-key"></i></a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__ . '/../templates/footer.php'; ?>
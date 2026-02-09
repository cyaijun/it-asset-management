<?php
// pages/categories.php - 类别管理
require_admin();

// 处理新增类别
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    require_csrf();
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($name)) {
        flash_set('类别名称不能为空');
    } else {
        $stmt = $pdo->prepare("INSERT INTO assetcategories (Name, Description, CreatedAt) VALUES (?, ?, NOW())");
        $stmt->execute([$name, $description]);
        flash_set('类别添加成功');
        header('Location: ?p=categories');
        exit;
    }
}

// 处理编辑类别
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit'])) {
    require_csrf();
    $id = (int)$_POST['id'];
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $codeRule = trim($_POST['codeRule'] ?? '');

    if (empty($name)) {
        flash_set('类别名称不能为空');
    } else {
        $stmt = $pdo->prepare("UPDATE assetcategories SET Name = ?, Description = ?, CodeRule = ? WHERE Id = ?");
        $stmt->execute([$name, $description, $codeRule ?: null, $id]);
        flash_set('类别更新成功');
        header('Location: ?p=categories');
        exit;
    }
}

// 处理删除类别
if (isset($_GET['delete']) && is_admin()) {
    $id = (int)$_GET['delete'];

    // 检查是否有资产使用此类别
    $check = $pdo->prepare("SELECT COUNT(*) FROM Assets WHERE Category = (SELECT Name FROM assetcategories WHERE Id = ?)");
    $check->execute([$id]);
    if ($check->fetchColumn() > 0) {
        flash_set('该类别下有资产，无法删除');
    } else {
        $stmt = $pdo->prepare("DELETE FROM assetcategories WHERE Id = ?");
        $stmt->execute([$id]);
        flash_set('类别已删除');
    }
    header('Location: ?p=categories');
    exit;
}

// 获取编辑中的类别
$editingCategory = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT Id, Name, Description, CodeRule, CreatedAt FROM assetcategories WHERE Id = ?");
    $stmt->execute([$id]);
    $editingCategory = $stmt->fetch();
}

// 获取所有类别（按ID排序）
$categories = $pdo->query("SELECT Id, Name, Description, CodeRule, CreatedAt FROM assetcategories ORDER BY Id ASC")->fetchAll();
?>
<?php include __DIR__ . '/../templates/header.php'; ?>

<!-- 页面标题 -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1><i class="bi bi-tags"></i> 资产类别管理</h1>
  <?php if ($editingCategory): ?>
    <a href="?p=categories" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> 返回列表</a>
  <?php endif; ?>
</div>

<?php if ($editingCategory): ?>
<!-- 编辑模式 -->
<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card border-primary">
      <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="bi bi-pencil"></i> 编辑类别</h5>
      </div>
      <div class="card-body">
        <form method="post">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>" />
          <input type="hidden" name="id" value="<?= $editingCategory['Id'] ?>" />
          <div class="mb-3">
            <label class="form-label">类别名称 <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="name" value="<?= h($editingCategory['Name']) ?>" required placeholder="请输入类别名称" />
          </div>
          <div class="mb-3">
            <label class="form-label">描述</label>
            <textarea class="form-control" name="description" rows="3" placeholder="可选，描述该类别的用途"><?= h($editingCategory['Description']) ?></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">编号规则</label>
            <input type="text" class="form-control font-monospace" name="codeRule" value="<?= h($editingCategory['CodeRule']) ?>" placeholder="如: PC-{NUM:4}" />
            <small class="text-muted">
              可用变量: <code>{NUM:n}</code> - 序号(n位数字), <code>{YEAR}</code> - 年份, <code>{MONTH}</code> - 月份<br>
              例如: <code>PC-{NUM:4}</code> 生成 PC-0001, PC-0002...
            </small>
          </div>
          <button type="submit" name="edit" class="btn btn-primary"><i class="bi bi-check-circle"></i> 保存修改</button>
          <a href="?p=categories" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i> 取消</a>
        </form>
      </div>
    </div>
  </div>
</div>

<?php else: ?>
<!-- 列表模式 -->
<div class="row">
  <div class="col-md-4">
    <div class="card border-success">
      <div class="card-header bg-success text-white">
        <h5 class="mb-0"><i class="bi bi-plus-circle"></i> 添加新类别</h5>
      </div>
      <div class="card-body">
        <form method="post">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>" />
          <div class="mb-3">
            <label class="form-label">类别名称 <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="name" required placeholder="请输入类别名称" />
          </div>
          <div class="mb-3">
            <label class="form-label">描述</label>
            <textarea class="form-control" name="description" rows="3" placeholder="可选，描述该类别的用途"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">编号规则</label>
            <input type="text" class="form-control font-monospace" name="codeRule" placeholder="如: PC-{NUM:4}" />
            <small class="text-muted">
              可用变量: <code>{NUM:n}</code> - 序号(n位数字), <code>{YEAR}</code> - 年份, <code>{MONTH}</code> - 月份<br>
              例如: <code>PC-{NUM:4}</code> 生成 PC-0001, PC-0002...
            </small>
          </div>
          <button type="submit" name="add" class="btn btn-success"><i class="bi bi-plus-circle"></i> 添加类别</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-md-8">
    <div class="card">
      <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="bi bi-list-ul"></i> 类别列表</h5>
      </div>
      <div class="card-body p-0">
        <div style="max-height: 70vh; overflow-y: auto;">
          <table class="table table-striped table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th width="50">ID</th>
                <th>类别名称</th>
                <th>编号规则</th>
                <th>描述</th>
                <th width="120">创建时间</th>
                <th width="100">操作</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($categories)): ?>
                <tr><td colspan="6" class="text-center py-5">暂无类别</td></tr>
              <?php else: ?>
                <?php foreach ($categories as $cat): ?>
                <tr>
                  <td><span class="badge bg-light text-dark"><?= $cat['Id'] ?></span></td>
                  <td><strong><?= h($cat['Name']) ?></strong></td>
                  <td><code class="small"><?= h($cat['CodeRule'] ?: '<span class="text-muted">未设置</span>') ?></code></td>
                  <td><small class="text-muted"><?= h($cat['Description'] ?: '-') ?></small></td>
                  <td><small class="text-muted"><?= h($cat['CreatedAt']) ?></small></td>
                  <td>
                    <div class="btn-group btn-group-sm">
                      <a href="?p=categories&edit=<?= $cat['Id'] ?>" class="btn btn-outline-primary" title="编辑">
                        <i class="bi bi-pencil"></i>
                      </a>
                      <a href="?p=categories&delete=<?= $cat['Id'] ?>"
                         class="btn btn-outline-danger"
                         onclick="return confirm('确定要删除此类别吗？')">
                        <i class="bi bi-trash"></i>
                      </a>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../templates/footer.php'; ?>

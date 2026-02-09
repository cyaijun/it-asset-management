<?php
// pages/assets_list.php - 资产总览

$perPage = 20;
$page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;
$offset = paginate_offset($page, $perPage);

// 搜索和筛选条件
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$category = $_GET['category'] ?? '';
$owner = $_GET['owner'] ?? '';
$sortBy = $_GET['sort'] ?? 'id';
$sortOrder = $_GET['order'] ?? 'desc';

// 验证排序字段和方向
$allowedSortFields = ['id', 'tag', 'name', 'category', 'model', 'status', 'location', 'purchase_date', 'warranty_expiry', 'license_expiry'];
$allowedSortOrders = ['asc', 'desc'];
$sortBy = in_array($sortBy, $allowedSortFields) ? $sortBy : 'id';
$sortOrder = in_array(strtolower($sortOrder), $allowedSortOrders) ? strtolower($sortOrder) : 'desc';

// 构建查询
$where = ['1=1'];
$params = [];

// 获取当前用户信息
$currentUser = get_current_user_info();

// user组用户只能查看自己的资产
if (!is_admin() && $currentUser) {
    $where[] = "a.OwnerId = ?";
    $params[] = $currentUser['id'];
}

if ($search) {
    $where[] = "(a.Tag LIKE ? OR a.Name LIKE ? OR a.SerialNumber LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($status) {
    $where[] = "a.Status = ?";
    $params[] = $status;
}

if ($category) {
    $where[] = "a.Category = ?";
    $params[] = $category;
}

if ($owner) {
    $where[] = "(u.Username LIKE ? OR u.FullName LIKE ?)";
    $params[] = "%{$owner}%";
    $params[] = "%{$owner}%";
}

$whereClause = implode(' AND ', $where);

// 获取总数 - 使用 DISTINCT 避免重复计数
$countSql = "SELECT COUNT(DISTINCT a.Id) FROM Assets a LEFT JOIN Users u ON a.OwnerId = u.Id WHERE {$whereClause}";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$total = $stmt->fetchColumn();

// 获取数据
$sql = "SELECT a.*, u.Username as OwnerUsername, u.FullName as OwnerFullName
        FROM Assets a
        LEFT JOIN Users u ON a.OwnerId = u.Id
        WHERE {$whereClause}
        ORDER BY {$sortBy} {$sortOrder}
        LIMIT {$perPage} OFFSET {$offset}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$assets = $stmt->fetchAll();

// 获取所有类别用于筛选
$categories = $pdo->query("SELECT DISTINCT Category FROM Assets WHERE Category IS NOT NULL AND Category != '' ORDER BY Category")->fetchAll(PDO::FETCH_COLUMN);
?>
<?php include __DIR__ . "/../templates/header.php"; ?>

<!-- 页面标题和操作按钮 -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1><i class="bi bi-box-seam"></i> 资产总览</h1>
  <?php if (is_admin()): ?>
  <div class="btn-group">
    <a class="btn btn-primary" href="?p=asset_create"><i class="bi bi-plus-circle"></i> 入库</a>
    <a class="btn btn-success" href="?p=assets_import"><i class="bi bi-upload"></i> 批量导入</a>
    <a class="btn btn-warning" href="?p=assets_export&search=<?= urlencode($search) ?>&status=<?= $status ?>&category=<?= $category ?>&owner=<?= $owner ?>"><i class="bi bi-download"></i> 批量导出</a>
    <a class="btn btn-info" href="?p=license_assets"><i class="bi bi-key"></i> License统计</a>
    <a class="btn btn-info" href="?p=scan"><i class="bi bi-qr-code-scan"></i> 手机扫码</a>
  </div>
  <?php else: ?>
  <div class="btn-group">
    <a class="btn btn-warning" href="?p=assets_export&search=<?= urlencode($search) ?>&status=<?= $status ?>&category=<?= $category ?>&owner=<?= $owner ?>"><i class="bi bi-download"></i> 批量导出</a>
    <a class="btn btn-info" href="?p=scan"><i class="bi bi-qr-code-scan"></i> 手机扫码</a>
  </div>
  <?php endif; ?>
</div>

<!-- 搜索和筛选表单 -->
<div class="card mb-4">
  <div class="card-body">
    <form class="row g-3" method="get">
      <input type="hidden" name="p" value="assets" />
      <div class="col-md-4">
        <label class="form-label">搜索</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-search"></i></span>
          <input type="text" class="form-control" name="search" placeholder="标签/名称/序列号" value="<?= h($search) ?>" />
        </div>
      </div>
      <div class="col-md-2">
        <label class="form-label">状态</label>
        <select class="form-select" name="status">
          <option value="">全部状态</option>
          <option value="InStock" <?= $status === 'InStock' ? 'selected' : '' ?>>在库</option>
          <option value="Assigned" <?= $status === 'Assigned' ? 'selected' : '' ?>>已领用</option>
          <option value="Maintenance" <?= $status === 'Maintenance' ? 'selected' : '' ?>>维修中</option>
          <option value="Disposed" <?= $status === 'Disposed' ? 'selected' : '' ?>>已报废</option>
          <option value="Lost" <?= $status === 'Lost' ? 'selected' : '' ?>>已丢失</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">类别</label>
        <select class="form-select" name="category">
          <option value="">全部类别</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= h($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= h($cat) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">归属人</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-person"></i></span>
          <input type="text" class="form-control" name="owner" placeholder="用户名/姓名" value="<?= h($owner) ?>" />
        </div>
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> 搜索</button>
        <a class="btn btn-outline-secondary ms-2" href="?p=assets"><i class="bi bi-arrow-counterclockwise"></i> 重置</a>
      </div>
    </form>
  </div>
</div>

<!-- 统计信息 -->
<div class="row mb-4">
  <div class="col-md-2">
    <div class="card text-center border-primary">
      <div class="card-body py-2">
        <h5 class="card-title mb-1"><a href="?p=assets" class="text-decoration-none text-primary"><?= $total ?></a></h5>
        <small class="text-muted">总资产</small>
      </div>
    </div>
  </div>
  <?php
  $statsSql = "SELECT a.Status, COUNT(*) as cnt FROM Assets a LEFT JOIN Users u ON a.OwnerId = u.Id WHERE " . $whereClause . " GROUP BY a.Status";
  $statsStmt = $pdo->prepare($statsSql);
  $statsStmt->execute($params);
  $stats = $statsStmt->fetchAll(PDO::FETCH_KEY_PAIR);
  foreach (['InStock' => '在库', 'Assigned' => '已领用', 'Maintenance' => '维修中', 'Disposed' => '已报废', 'Lost' => '已丢失'] as $s => $label):
    $bgClass = ['InStock' => 'success', 'Assigned' => 'primary', 'Maintenance' => 'warning', 'Disposed' => 'secondary', 'Lost' => 'danger'][$s] ?? 'secondary';
  ?>
  <div class="col-md-2">
    <div class="card text-center border-<?= $bgClass ?>">
      <div class="card-body py-2">
        <h5 class="card-title mb-1"><a href="?p=assets&status=<?= $s ?>" class="text-decoration-none <?= $status === $s ? "text-{$bgClass}" : "text-muted" ?>"><?= $stats[$s] ?? 0 ?></a></h5>
        <small class="text-muted"><?= $label ?></small>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- 批量操作 -->
<div class="mb-3" id="bulkActions" style="display:none;">
  <div class="alert alert-info d-flex align-items-center mb-0">
    <i class="bi bi-info-circle me-2"></i>
    <span class="me-3">已选择 <strong id="selectedCount">0</strong> 项</span>
    <button type="button" class="btn btn-primary" onclick="bulkPrint()">
      <i class="bi bi-printer"></i> 批量打印标签
    </button>
  </div>
</div>

<!-- 资产列表 -->
<div class="card">
  <div class="card-body p-0">
    <div style="max-height: 70vh; overflow-y: auto;">
      <table class="table table-striped table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th width="40"><input type="checkbox" id="selectAll" onchange="toggleSelectAll()" /></th>
            <th>
              <a href="?p=assets&search=<?= urlencode($search) ?>&status=<?= $status ?>&category=<?= $category ?>&owner=<?= $owner ?>&sort=id&order=<?= $sortBy === 'id' ? ($sortOrder === 'asc' ? 'desc' : 'asc') : 'asc' ?>" class="text-decoration-none text-dark">
                ID <?= $sortBy === 'id' ? ($sortOrder === 'asc' ? '<i class="bi bi-arrow-up"></i>' : '<i class="bi bi-arrow-down"></i>') : '' ?>
              </a>
            </th>
            <th>
              <a href="?p=assets&search=<?= urlencode($search) ?>&status=<?= $status ?>&category=<?= $category ?>&owner=<?= $owner ?>&sort=tag&order=<?= $sortBy === 'tag' ? ($sortOrder === 'asc' ? 'desc' : 'asc') : 'asc' ?>" class="text-decoration-none text-dark">
                资产编号 <?= $sortBy === 'tag' ? ($sortOrder === 'asc' ? '<i class="bi bi-arrow-up"></i>' : '<i class="bi bi-arrow-down"></i>') : '' ?>
              </a>
            </th>
            <th>
              <a href="?p=assets&search=<?= urlencode($search) ?>&status=<?= $status ?>&category=<?= $category ?>&owner=<?= $owner ?>&sort=name&order=<?= $sortBy === 'name' ? ($sortOrder === 'asc' ? 'desc' : 'asc') : 'asc' ?>" class="text-decoration-none text-dark">
                名称 <?= $sortBy === 'name' ? ($sortOrder === 'asc' ? '<i class="bi bi-arrow-up"></i>' : '<i class="bi bi-arrow-down"></i>') : '' ?>
              </a>
            </th>
            <th>
              <a href="?p=assets&search=<?= urlencode($search) ?>&status=<?= $status ?>&category=<?= $category ?>&owner=<?= $owner ?>&sort=category&order=<?= $sortBy === 'category' ? ($sortOrder === 'asc' ? 'desc' : 'asc') : 'asc' ?>" class="text-decoration-none text-dark">
                类别 <?= $sortBy === 'category' ? ($sortOrder === 'asc' ? '<i class="bi bi-arrow-up"></i>' : '<i class="bi bi-arrow-down"></i>') : '' ?>
              </a>
            </th>
            <th>型号</th>
            <th>状态</th>
            <th>归属</th>
            <th>操作</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($assets)): ?>
            <tr><td colspan="9" class="text-center py-5">暂无资产</td></tr>
          <?php else: ?>
            <?php foreach($assets as $a): ?>
              <tr>
                <td><input type="checkbox" class="asset-checkbox" value="<?= $a["Id"] ?>" onchange="updateBulkActions()" /></td>
                <td><span class="badge bg-light text-dark"><?= $a["Id"] ?></span></td>
                <td><strong><?= h($a["Tag"]) ?></strong></td>
                <td><?= h($a["Name"]) ?></td>
                <td>
                  <span class="badge bg-light text-dark"><?= h($a["Category"] ?: '-') ?></span>
                  <?php if ($a["IsLicense"]): ?>
                    <span class="badge bg-info ms-1"><i class="bi bi-key"></i> License</span>
                  <?php endif; ?>
                </td>
                <td><small class="text-muted"><?= h($a["Model"]) ?></small></td>
                <td><span class="badge <?= status_class($a["Status"]) ?>"><?= status_text($a["Status"]) ?></span></td>
                <td>
                  <?php if ($a["OwnerUsername"]): ?>
                    <span><i class="bi bi-person"></i> <?= h($a["OwnerFullName"]) ?></span>
                    <br><small class="text-muted">@<?= h($a["OwnerUsername"]) ?></small>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <a class="btn btn-outline-primary" href="?p=asset_details&id=<?= $a["Id"] ?>" title="详情"><i class="bi bi-eye"></i></a>
                    <?php if (is_admin()): ?>
                      <a class="btn btn-outline-secondary" href="?p=asset_checkout&id=<?= $a["Id"] ?>" title="领用"><i class="bi bi-box-arrow-right"></i></a>
                      <a class="btn btn-outline-success" href="?p=asset_copy&id=<?= $a["Id"] ?>" title="复制"><i class="bi bi-copy"></i></a>
                    <?php endif; ?>
                    <?php if ($a["OwnerId"] && $a["Status"] === 'Assigned'): ?>
                      <a class="btn btn-outline-warning" href="?p=asset_return&id=<?= $a["Id"] ?>" title="归还"><i class="bi bi-box-arrow-in-left"></i></a>
                    <?php endif; ?>
                    <?php if (is_admin() && $a["OwnerId"] && $a["Status"] === 'Assigned'): ?>
                      <a class="btn btn-outline-primary" href="?p=asset_transfer&id=<?= $a["Id"] ?>" title="转移"><i class="bi bi-arrow-left-right"></i></a>
                    <?php endif; ?>
                    <a class="btn btn-outline-info" href="qr.php?id=<?= $a["Id"] ?>" target="_blank" title="二维码"><i class="bi bi-qr-code"></i></a>
                    <?php if (is_admin()): ?>
                      <a class="btn btn-outline-dark" href="?p=asset_edit&id=<?= $a["Id"] ?>" title="编辑"><i class="bi bi-pencil"></i></a>
                      <?php if ($a["Status"] !== 'Lost' && $a["Status"] !== 'Disposed'): ?>
                      <a class="btn btn-outline-danger" href="?p=asset_loss&id=<?= $a["Id"] ?>" title="标记丢失"><i class="bi bi-exclamation-triangle"></i></a>
                      <?php endif; ?>
                    <?php endif; ?>
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

<?= paginate_nav($total, $page, $perPage, "?p=assets&search=" . urlencode($search) . "&status={$status}&category={$category}&owner={$owner}&sort={$sortBy}&order={$sortOrder}&") ?>

<script>
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.asset-checkbox');
    checkboxes.forEach(cb => cb.checked = selectAll.checked);
    updateBulkActions();
}

function updateBulkActions() {
    const checkboxes = document.querySelectorAll('.asset-checkbox:checked');
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.getElementById('selectedCount');
    if (checkboxes.length > 0) {
        bulkActions.style.display = 'block';
        selectedCount.textContent = checkboxes.length;
    } else {
        bulkActions.style.display = 'none';
    }
}

function bulkPrint() {
    const checkboxes = document.querySelectorAll('.asset-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('请至少选择一个资产');
        return;
    }
    const ids = Array.from(checkboxes).map(cb => cb.value).join(',');
    window.open('?p=print_label&ids=' + ids, '_blank');
}
</script>

<?php include __DIR__ . "/../templates/footer.php"; ?>
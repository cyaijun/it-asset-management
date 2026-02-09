<?php
// pages/assets_edit.php - 资产编辑
require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM Assets WHERE Id = ?");
$stmt->execute([$id]);
$asset = $stmt->fetch();

if (!$asset) {
    flash_set('未找到该资产');
    header('Location: ?p=assets');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $errors = [];
    $data = [
        'Tag' => trim($_POST['Tag'] ?? ''),
        'Name' => trim($_POST['Name'] ?? ''),
        'Category' => trim($_POST['Category'] ?? ''),
        'Model' => trim($_POST['Model'] ?? ''),
        'SerialNumber' => trim($_POST['SerialNumber'] ?? ''),
        'Location' => trim($_POST['Location'] ?? ''),
        'Supplier' => trim($_POST['Supplier'] ?? ''),
        'PurchaseDate' => $_POST['PurchaseDate'] ?? '',
        'WarrantyExpiry' => $_POST['WarrantyExpiry'] ?? '',
        'PurchasePrice' => $_POST['PurchasePrice'] ?? '',
        'IsLicense' => isset($_POST['IsLicense']) ? 1 : 0,
        'LicenseExpiry' => $_POST['LicenseExpiry'] ?? '',
        'Notes' => trim($_POST['Notes'] ?? ''),
    ];

    // 验证必填字段
    if ($data['Tag'] === '') $errors[] = '资产编号不能为空';
    if ($data['Name'] === '') $errors[] = '名称不能为空';

    // 验证编号唯一性
    if (!validate_tag_unique($pdo, $data['Tag'], $id)) {
        $errors[] = '资产编号已存在';
    }

    // 验证日期格式
    if ($data['PurchaseDate'] && !validate_date($data['PurchaseDate'])) {
        $errors[] = '购买日期格式不正确';
    }
    if ($data['WarrantyExpiry'] && !validate_date($data['WarrantyExpiry'])) {
        $errors[] = '保修到期日格式不正确';
    }

    // 验证价格
    if ($data['PurchasePrice'] && !validate_number($data['PurchasePrice'], 0)) {
        $errors[] = '采购价格必须是有效数字';
    }

    // 如果是License资产，必须填写到期时间
    if ($data['IsLicense'] && !$data['LicenseExpiry']) {
        $errors[] = 'License资产必须填写到期时间';
    }

    // 验证License到期日期格式
    if ($data['LicenseExpiry'] && !validate_date($data['LicenseExpiry'])) {
        $errors[] = 'License到期日期格式不正确';
    }

    if (empty($errors)) {
        $sql = "UPDATE Assets SET Tag = ?, Name = ?, Category = ?, Model = ?, SerialNumber = ?, Location = ?, Supplier = ?, PurchaseDate = ?, WarrantyExpiry = ?, PurchasePrice = ?, IsLicense = ?, LicenseExpiry = ?, Notes = ? WHERE Id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['Tag'], $data['Name'], $data['Category'], $data['Model'], $data['SerialNumber'],
            $data['Location'], $data['Supplier'], $data['PurchaseDate'] ?: null, $data['WarrantyExpiry'] ?: null,
            $data['PurchasePrice'] ?: null, $data['IsLicense'], $data['LicenseExpiry'] ?: null, $data['Notes'], $id
        ]);

        // 记录审计日志
        $user = get_current_user_info();
        $pdo->prepare("INSERT INTO AuditLog (UserId, Action, ResourceType, ResourceId, Details, IpAddress) VALUES (?, 'update', 'asset', ?, ?, ?)")
            ->execute([$user['id'], $id, "编辑资产: {$data['Name']}", $_SERVER['REMOTE_ADDR'] ?? '']);

        flash_set('资产更新成功');
        header("Location: ?p=asset_details&id={$id}");
        exit;
    }
}
?>
<?php include __DIR__ . '/../templates/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1>编辑资产</h1>
  <div>
    <a class="btn btn-secondary" href="?p=asset_details&id=<?= $asset['Id'] ?>">返回</a>
  </div>
</div>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $error): ?>
        <li><?= h($error) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="post" class="row g-3">
  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>" />
  <div class="col-md-6">
    <label class="form-label">资产编号 <span class="text-danger">*</span></label>
    <input class="form-control" name="Tag" value="<?= h($asset['Tag'] ?? '') ?>" required />
  </div>
  <div class="col-md-6">
    <label class="form-label">名称 <span class="text-danger">*</span></label>
    <input class="form-control" name="Name" value="<?= h($asset['Name'] ?? '') ?>" required />
  </div>
  <div class="col-md-4">
    <label class="form-label">类别</label>
    <select class="form-select" name="Category">
      <option value="">请选择类别</option>
      <?php
      $categories = $pdo->query("SELECT Name FROM assetcategories ORDER BY Name")->fetchAll(PDO::FETCH_COLUMN);
      $existingCategories = $pdo->query("SELECT DISTINCT Category FROM Assets WHERE Category IS NOT NULL AND Category != '' AND Category NOT IN (SELECT Name FROM assetcategories) LIMIT 20")->fetchAll(PDO::FETCH_COLUMN);
      ?>
      <?php foreach ($categories as $cat): ?>
        <option value="<?= h($cat) ?>" <?= $asset['Category'] === $cat ? 'selected' : '' ?>><?= h($cat) ?></option>
      <?php endforeach; ?>
      <?php if (!empty($existingCategories)): ?>
        <option disabled>━━━━ 历史类别 ━━━━</option>
        <?php foreach ($existingCategories as $cat): ?>
          <option value="<?= h($cat) ?>" <?= $asset['Category'] === $cat ? 'selected' : '' ?>><?= h($cat) ?></option>
        <?php endforeach; ?>
      <?php endif; ?>
    </select>
  </div>
  <div class="col-md-4">
    <label class="form-label">型号</label>
    <input class="form-control" name="Model" value="<?= h($asset['Model'] ?? '') ?>" />
  </div>
  <div class="col-md-4">
    <label class="form-label">序列号</label>
    <input class="form-control" name="SerialNumber" value="<?= h($asset['SerialNumber'] ?? '') ?>" />
  </div>
  <div class="col-md-6">
    <label class="form-label">位置</label>
    <input class="form-control" name="Location" value="<?= h($asset['Location'] ?? '') ?>" />
  </div>
  <div class="col-md-6">
    <label class="form-label">供应商</label>
    <input class="form-control" name="Supplier" value="<?= h($asset['Supplier'] ?? '') ?>" />
  </div>
  <div class="col-md-4">
    <label class="form-label">购买日期</label>
    <input type="date" class="form-control" name="PurchaseDate" value="<?= h($asset['PurchaseDate'] ?? '') ?>" />
  </div>
  <div class="col-md-4">
    <label class="form-label">保修到期日</label>
    <input type="date" class="form-control" name="WarrantyExpiry" value="<?= h($asset['WarrantyExpiry'] ?? '') ?>" />
  </div>
  <div class="col-md-4">
    <label class="form-label">采购价格</label>
    <input type="number" step="0.01" class="form-control" name="PurchasePrice" value="<?= h($asset['PurchasePrice'] ?? '') ?>" />
  </div>
  <div class="col-md-6">
    <label class="form-label">是否License资产</label>
    <div class="form-check form-switch mt-2">
      <input class="form-check-input" type="checkbox" name="IsLicense" id="isLicenseSelect" value="1" <?= ($asset['IsLicense'] ?? 0) == 1 ? 'checked' : '' ?> onchange="toggleLicenseExpiry()" />
      <label class="form-check-label" for="isLicenseSelect">是License资产</label>
    </div>
  </div>
  <div class="col-md-6" id="licenseExpiryDiv" style="display:<?= ($asset['IsLicense'] ?? 0) == 1 ? 'block' : 'none' ?>;">
    <label class="form-label">License到期时间 <span class="text-danger">*</span></label>
    <input type="date" class="form-control" name="LicenseExpiry" id="licenseExpiryInput" value="<?= h($asset['LicenseExpiry'] ?? '') ?>" />
    <small class="text-muted">选择是否为License资产后必填</small>
  </div>
  <div class="col-12">
    <label class="form-label">备注</label>
    <textarea class="form-control" name="Notes" rows="3"><?= h($asset['Notes'] ?? '') ?></textarea>
  </div>
  <div class="col-12">
    <button class="btn btn-primary" type="submit">保存修改</button>
    <a class="btn btn-danger" href="?p=asset_delete&id=<?= $asset['Id'] ?>" onclick="return confirm('确定要删除此资产吗?')">删除资产</a>
    <a class="btn btn-secondary" href="?p=asset_details&id=<?= $asset['Id'] ?>">取消</a>
  </div>
</form>

<script>
function toggleLicenseExpiry() {
    const isLicense = document.getElementById('isLicenseSelect').checked;
    const expiryDiv = document.getElementById('licenseExpiryDiv');
    const expiryInput = document.getElementById('licenseExpiryInput');
    expiryDiv.style.display = isLicense ? 'block' : 'none';
    if (!isLicense) {
        expiryInput.value = '';
        expiryInput.required = false;
    } else {
        expiryInput.required = true;
    }
}
</script>

<?php include __DIR__ . '/../templates/footer.php'; ?>

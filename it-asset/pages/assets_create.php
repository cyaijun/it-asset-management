<?php
// pages/assets_create.php - 资产入库
require_admin();

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

    // 如果点击了"自动生成"，则使用服务器端生成
    $autoGenerate = isset($_POST['auto_generate']) && $_POST['auto_generate'] === '1';
    if ($autoGenerate) {
        $categoryId = $_POST['category_id'] ?? null;
        if ($categoryId) {
            $generatedTag = generate_asset_code($pdo, $categoryId);
            if ($generatedTag) {
                $data['Tag'] = $generatedTag;
            }
        }
    }

    // 验证必填字段
    if ($data['Tag'] === '') $errors[] = '资产编号不能为空';
    if ($data['Name'] === '') $errors[] = '名称不能为空';

    // 验证编号唯一性
    if (!validate_tag_unique($pdo, $data['Tag'])) {
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
        $stmt = $pdo->prepare("INSERT INTO Assets (Tag, Name, Category, Model, SerialNumber, Location, Supplier, PurchaseDate, WarrantyExpiry, PurchasePrice, IsLicense, LicenseExpiry, Notes, Status, CreatedAt) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'InStock', NOW())");
        $stmt->execute([
            $data['Tag'], $data['Name'], $data['Category'], $data['Model'], $data['SerialNumber'],
            $data['Location'], $data['Supplier'], $data['PurchaseDate'] ?: null, $data['WarrantyExpiry'] ?: null,
            $data['PurchasePrice'] ?: null, $data['IsLicense'], $data['LicenseExpiry'] ?: null, $data['Notes']
        ]);
        $assetId = $pdo->lastInsertId();

        $user = get_current_user_info();
        $pdo->prepare("INSERT INTO AssetTransactions (AssetId, UserId, TransactionType, Notes, CreatedAt, OperatorId) VALUES (?, ?, 'Create', ?, NOW(), ?)")
            ->execute([$assetId, $user['id'], "资产入库: {$data['Name']}", $user['id']]);

        // 记录审计日志
        $pdo->prepare("INSERT INTO AuditLog (UserId, Action, ResourceType, ResourceId, Details, IpAddress) VALUES (?, 'create', 'asset', ?, ?, ?)")
            ->execute([$user['id'], $assetId, "创建资产: {$data['Name']}", $_SERVER['REMOTE_ADDR'] ?? '']);

        flash_set('资产入库成功');
        header("Location: ?p=asset_details&id={$assetId}");
        exit;
    }
}

// 获取类别ID，用于自动生成编号
$categoryId = null;
$categoryName = '';
if (isset($_GET['category'])) {
    $categoryName = $_GET['category'];
    $stmt = $pdo->prepare("SELECT Id, Name, CodeRule FROM assetcategories WHERE Name = ?");
    $stmt->execute([$categoryName]);
    $cat = $stmt->fetch();
    if ($cat) {
        $categoryId = $cat['Id'];
    }
}
?>
<?php include __DIR__ . '/../templates/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1><i class="bi bi-plus-circle"></i> 资产入库</h1>
  <a class="btn btn-secondary" href="?p=assets"><i class="bi bi-arrow-left"></i> 返回</a>
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

<form method="post">
  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>" />
  <input type="hidden" name="auto_generate" id="autoGenerate" value="0" />
  <input type="hidden" name="category_id" id="categoryIdInput" value="" />

  <!-- 基本信息 -->
  <div class="card mb-3">
    <div class="card-header bg-primary text-white">
      <h6 class="mb-0"><i class="bi bi-info-circle"></i> 基本信息</h6>
    </div>
    <div class="card-body row g-3">
      <div class="col-md-6">
        <label class="form-label">资产编号 <span class="text-danger">*</span></label>
        <div class="input-group">
          <input type="text" class="form-control" name="Tag" id="tagInput" required placeholder="手动输入或使用自动生成" />
          <button type="button" class="btn btn-success" onclick="generateCode()">
            <i class="bi bi-magic"></i> 自动生成
          </button>
        </div>
        <small class="text-muted">选择类别后可自动生成编号（从0001开始递增）</small>
      </div>
      <div class="col-md-6">
        <label class="form-label">资产名称 <span class="text-danger">*</span></label>
        <input type="text" class="form-control" name="Name" required placeholder="请输入资产名称" />
      </div>
    </div>
  </div>

  <!-- 详细信息 -->
  <div class="card mb-3">
    <div class="card-header bg-info text-white">
      <h6 class="mb-0"><i class="bi bi-list-ul"></i> 详细信息</h6>
    </div>
    <div class="card-body row g-3">
      <div class="col-md-4">
        <label class="form-label">类别</label>
        <select class="form-select" name="Category" id="categorySelect" onchange="onCategoryChange()">
          <option value="">请选择类别</option>
          <?php
          $categories = $pdo->query("SELECT Id, Name, CodeRule, NextCode FROM assetcategories ORDER BY Name")->fetchAll();
          $existingCategories = $pdo->query("SELECT DISTINCT Category FROM Assets WHERE Category IS NOT NULL AND Category != '' AND Category NOT IN (SELECT Name FROM assetcategories) LIMIT 20")->fetchAll(PDO::FETCH_COLUMN);
          ?>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= h($cat['Name']) ?>" data-id="<?= $cat['Id'] ?>" data-rule="<?= h($cat['CodeRule']) ?>" data-nextcode="<?= $cat['NextCode'] ?>">
              <?= h($cat['Name']) ?>
              <?php if ($cat['CodeRule']): ?>
                <small class="text-muted">(下个序号: <?= $cat['NextCode'] ?>)</small>
              <?php endif; ?>
            </option>
          <?php endforeach; ?>
          <?php if (!empty($existingCategories)): ?>
            <option disabled>━━━━ 历史类别 ━━━━</option>
            <?php foreach ($existingCategories as $cat): ?>
              <option value="<?= h($cat) ?>"><?= h($cat) ?></option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">型号</label>
        <input class="form-control" name="Model" placeholder="可选" />
      </div>
      <div class="col-md-4">
        <label class="form-label">序列号</label>
        <input class="form-control" name="SerialNumber" placeholder="可选" />
      </div>
      <div class="col-md-4">
        <label class="form-label">位置</label>
        <input class="form-control" name="Location" placeholder="存放位置" />
      </div>
      <div class="col-md-4">
        <label class="form-label">供应商</label>
        <input class="form-control" name="Supplier" placeholder="供应商名称" />
      </div>
      <div class="col-md-4">
        <label class="form-label">状态</label>
        <select class="form-select" name="Status">
          <option value="InStock" selected>在库</option>
          <option value="Assigned">已领用</option>
          <option value="Maintenance">维修中</option>
        </select>
      </div>
    </div>
  </div>

  <!-- 采购信息 -->
  <div class="card mb-3">
    <div class="card-header bg-warning text-dark">
      <h6 class="mb-0"><i class="bi bi-cart"></i> 采购信息</h6>
    </div>
    <div class="card-body row g-3">
      <div class="col-md-4">
        <label class="form-label">采购价格</label>
        <div class="input-group">
          <span class="input-group-text">¥</span>
          <input type="number" step="0.01" class="form-control" name="PurchasePrice" placeholder="0.00" />
        </div>
      </div>
      <div class="col-md-4">
        <label class="form-label">购买日期</label>
        <input type="date" class="form-control" name="PurchaseDate" />
      </div>
      <div class="col-md-4">
        <label class="form-label">保修到期日</label>
        <input type="date" class="form-control" name="WarrantyExpiry" />
      </div>
      <div class="col-md-6">
        <label class="form-label">是否License资产</label>
        <div class="form-check form-switch mt-2">
          <input class="form-check-input" type="checkbox" name="IsLicense" id="isLicenseSelect" value="1" onchange="toggleLicenseExpiry()" />
          <label class="form-check-label" for="isLicenseSelect">是License资产</label>
        </div>
      </div>
      <div class="col-md-6" id="licenseExpiryDiv" style="display:none;">
        <label class="form-label">License到期时间 <span class="text-danger">*</span></label>
        <input type="date" class="form-control" name="LicenseExpiry" id="licenseExpiryInput" />
        <small class="text-muted">选择是否为License资产后必填</small>
      </div>
    </div>
  </div>

  <!-- 备注 -->
  <div class="card mb-3">
    <div class="card-header bg-secondary text-white">
      <h6 class="mb-0"><i class="bi bi-pencil"></i> 备注</h6>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-12">
          <textarea class="form-control" name="Notes" rows="3" placeholder="请输入备注信息（可选）"></textarea>
        </div>
      </div>
    </div>
  </div>

  <!-- 操作按钮 -->
  <div class="row">
    <div class="col-12">
      <button class="btn btn-primary btn-lg" type="submit">
        <i class="bi bi-plus-circle"></i> 确认入库
      </button>
      <button class="btn btn-outline-secondary btn-lg" type="reset">
        <i class="bi bi-arrow-counterclockwise"></i> 重置表单
      </button>
      <a class="btn btn-outline-danger btn-lg" href="?p=assets">
        <i class="bi bi-x-circle"></i> 取消
      </a>
    </div>
  </div>
</form>

<script>
// 获取类别ID
function getCategoryId() {
    const select = document.getElementById('categorySelect');
    const selected = select.options[select.selectedIndex];
    return selected ? selected.getAttribute('data-id') : null;
}

// 自动生成资产编号
function generateCode() {
    const categoryId = getCategoryId();
    if (!categoryId) {
        alert('请先选择类别');
        return;
    }

    // 获取选中的类别规则和下一个序号
    const select = document.getElementById('categorySelect');
    const selected = select.options[select.selectedIndex];
    const rule = selected.getAttribute('data-rule');
    const nextCode = selected.getAttribute('data-nextcode');

    if (!rule) {
        alert('该类别未设置编号规则');
        return;
    }

    // 设置隐藏字段，让服务器端生成
    document.getElementById('categoryIdInput').value = categoryId;
    document.getElementById('autoGenerate').value = '1';

    // 预览生成的编号
    let preview = rule;

    // 替换 {YEAR} - 当前年份
    preview = preview.replace(/{YEAR}/g, new Date().getFullYear());

    // 替换 {MONTH} - 当前月份
    preview = preview.replace(/{MONTH}/g, (new Date().getMonth() + 1).toString().padStart(2, '0'));

    // 替换 {NUM:n} - 序号（使用数据库中的下一个序号）
    const match = preview.match(/{NUM:(\d+)}/);
    if (match) {
        const numLen = parseInt(match[1]);
        const nextNum = parseInt(nextCode) || 1;
        const mockNum = nextNum.toString().padStart(numLen, '0');
        preview = preview.replace(/{NUM:\d+}/g, mockNum);
    }

    document.getElementById('tagInput').value = preview;
    alert('编号预览: ' + preview + '\n\n提交后将以该编号保存，序号将自动递增');
}

// 类别改变时的提示
function onCategoryChange() {
    const select = document.getElementById('categorySelect');
    const selected = select.options[select.selectedIndex];
    const rule = selected ? selected.getAttribute('data-rule') : '';

    if (rule) {
        console.log('编号规则:', rule);
    }
}

// 切换License到期时间显示
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
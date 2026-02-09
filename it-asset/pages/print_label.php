<?php
// pages/print_label.php - 资产标签打印
require_login();

$ids = isset($_GET['ids']) ? explode(',', $_GET['ids']) : [];

if (empty($ids)) {
    flash_set('请选择要打印的资产');
    header('Location: ?p=assets');
    exit;
}

// 获取资产数据
$placeholders = str_repeat('?,', count($ids) - 1) . '?';
$sql = "SELECT a.*, u.Username as OwnerUsername, u.FullName as OwnerFullName, u.Department as OwnerDepartment
        FROM Assets a
        LEFT JOIN Users u ON a.OwnerId = u.Id
        WHERE a.Id IN ({$placeholders})";
$stmt = $pdo->prepare($sql);
$stmt->execute($ids);
$assets = $stmt->fetchAll();

if (empty($assets)) {
    flash_set('未找到选中的资产');
    header('Location: ?p=assets');
    exit;
}

// 获取所有可打印的属性字段
$allFields = [
    'tag' => '资产编号',
    'name' => '资产名称',
    'category' => '类别',
    'model' => '型号',
    'serial_number' => '序列号',
    'owner' => '持有者',
    'location' => '位置',
    'supplier' => '供应商',
    'purchase_date' => '购买日期',
    'warranty_expiry' => '保修到期',
    'purchase_price' => '采购价格',
];

// 默认选中的字段
$defaultFields = ['tag', 'name', 'category', 'model', 'owner'];

// 处理表单提交
$printMode = $_GET['mode'] ?? 'both';
$selectedFields = $_GET['fields'] ?? $defaultFields;
if (!is_array($selectedFields)) {
    $selectedFields = $defaultFields;
}

// 纸张尺寸预设
$paperPresets = [
    'a4' => ['name' => 'A4', 'width' => 210, 'height' => 297],
    'a5' => ['name' => 'A5', 'width' => 148, 'height' => 210],
    'letter' => ['name' => 'Letter', 'width' => 216, 'height' => 279],
];

// 标签尺寸预设
$labelPresets = [
    'large' => ['name' => '大标签', 'width' => 100, 'height' => 70, 'cols' => 2, 'rows' => 4],
    'medium' => ['name' => '中标签', 'width' => 70, 'height' => 50, 'cols' => 3, 'rows' => 5],
    'small' => ['name' => '小标签', 'width' => 50, 'height' => 30, 'cols' => 4, 'rows' => 8],
];

$paperType = $_GET['paper'] ?? 'a4';
if (!isset($paperPresets[$paperType])) {
    $paperType = 'a4';
}

$labelType = $_GET['label'] ?? 'large';
if (!isset($labelPresets[$labelType])) {
    $labelType = 'large';
}

// 自定义纸张尺寸
if ($paperType === 'custom') {
    $paperWidth = floatval($_GET['paperWidth'] ?? 210);
    $paperHeight = floatval($_GET['paperHeight'] ?? 297);
} else {
    $paperWidth = $paperPresets[$paperType]['width'];
    $paperHeight = $paperPresets[$paperType]['height'];
}

// 自定义标签尺寸
if ($labelType === 'custom') {
    $labelWidth = floatval($_GET['labelWidth'] ?? 100);
    $labelHeight = floatval($_GET['labelHeight'] ?? 70);
    $cols = max(1, min(10, intval($_GET['cols'] ?? 2)));
    $rows = max(1, min(10, intval($_GET['rows'] ?? 4)));
} else {
    $labelWidth = $labelPresets[$labelType]['width'];
    $labelHeight = $labelPresets[$labelType]['height'];
    $cols = $labelPresets[$labelType]['cols'];
    $rows = $labelPresets[$labelType]['rows'];
}

// 计算边距和间距
$margin = floatval($_GET['margin'] ?? 10);
$gap = floatval($_GET['gap'] ?? 5);

$fontSize = $_GET['fontSize'] ?? '12';
?>
<?php include __DIR__ . '/../templates/header.php'; ?>
<style>
@media print {
    .no-print { display: none !important; }
    .label-container { page-break-inside: avoid; }
    body { background: white !important; }
    .print-area { padding: 0 !important; }
    @page {
        size: <?= $paperWidth ?>mm <?= $paperHeight ?>mm;
        margin: <?= $margin ?>mm;
    }
}
.print-grid {
    display: grid;
    grid-template-columns: repeat(<?= $cols ?>, <?= $labelWidth ?>mm);
    grid-template-rows: repeat(<?= $rows ?>, <?= $labelHeight ?>mm);
    gap: <?= $gap ?>mm;
    width: calc(<?= $cols ?> * <?= $labelWidth ?>mm + <?= $cols - 1 ?> * <?= $gap ?>mm);
    margin: 0 auto;
}
.label-container {
    border: 2px solid #333;
    padding: 5px;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
    background: white;
    break-inside: avoid;
    width: 100%;
    height: 100%;
    overflow: hidden;
}
.label-header {
    text-align: center;
    font-weight: bold;
    font-size: max(10px, <?= $fontSize * 1.2 ?>px);
    margin-bottom: 4px;
    border-bottom: 1px solid #333;
    padding-bottom: 2px;
}
.qr-code {
    text-align: center;
    margin: 4px 0;
}
.qr-code img {
    width: min(40px, calc(<?= $labelHeight ?>mm * 0.4));
    height: min(40px, calc(<?= $labelHeight ?>mm * 0.4));
}
.label-fields {
    margin-top: 4px;
}
.label-field {
    display: flex;
    padding: 1px 0;
    border-bottom: 1px dashed #ccc;
    font-size: <?= $fontSize ?>px;
    line-height: 1.2;
}
.field-label {
    font-weight: bold;
    min-width: 50px;
    color: #333;
    font-size: max(10px, <?= $fontSize ?>px);
}
.field-value {
    flex: 1;
    color: #666;
    font-size: max(10px, <?= $fontSize ?>px);
}
.print-header {
    text-align: center;
    font-size: 18px;
    font-weight: bold;
    margin-bottom: 20px;
}
.print-info {
    text-align: right;
    font-size: 12px;
    color: #666;
    margin-bottom: 10px;
}
</style>

<div class="no-print">
    <h1>资产标签打印</h1>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="card-title">打印设置</h5>
            <form id="printForm" class="row g-3">
                <div class="col-12">
                    <h6 class="text-primary mb-3">纸张设置</h6>
                </div>
                <div class="col-md-3">
                    <label class="form-label">纸张类型</label>
                    <select class="form-select" name="paper" id="paperType" onchange="updatePaperControls()">
                        <?php foreach ($paperPresets as $key => $size): ?>
                            <option value="<?= $key ?>" <?= $paperType === $key ? 'selected' : '' ?>><?= $size['name'] ?> (<?= $size['width'] ?> x <?= $size['height'] ?>mm)</option>
                        <?php endforeach; ?>
                        <option value="custom" <?= $paperType === 'custom' ? 'selected' : '' ?>>自定义</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">纸张宽度 (mm)</label>
                    <input type="number" class="form-control" name="paperWidth" id="paperWidth"
                           value="<?= $paperType === 'custom' ? $paperWidth : '' ?>"
                           placeholder="210" min="50" max="500" <?= $paperType !== 'custom' ? 'disabled' : '' ?>>
                </div>
                <div class="col-md-2">
                    <label class="form-label">纸张高度 (mm)</label>
                    <input type="number" class="form-control" name="paperHeight" id="paperHeight"
                           value="<?= $paperType === 'custom' ? $paperHeight : '' ?>"
                           placeholder="297" min="50" max="500" <?= $paperType !== 'custom' ? 'disabled' : '' ?>>
                </div>
                <div class="col-md-2">
                    <label class="form-label">边距 (mm)</label>
                    <input type="number" class="form-control" name="margin"
                           value="<?= $margin ?>" min="0" max="20">
                </div>
                <div class="col-12 mt-3">
                    <h6 class="text-primary mb-3">标签设置</h6>
                </div>
                <div class="col-md-3">
                    <label class="form-label">标签类型</label>
                    <select class="form-select" name="label" id="labelType" onchange="updateLabelControls()">
                        <?php foreach ($labelPresets as $key => $label): ?>
                            <option value="<?= $key ?>" <?= $labelType === $key ? 'selected' : '' ?>><?= $label['name'] ?> (<?= $label['width'] ?> x <?= $label['height'] ?>mm)</option>
                        <?php endforeach; ?>
                        <option value="custom" <?= $labelType === 'custom' ? 'selected' : '' ?>>自定义</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">标签宽度 (mm)</label>
                    <input type="number" class="form-control" name="labelWidth" id="labelWidth"
                           value="<?= $labelType === 'custom' ? $labelWidth : '' ?>"
                           placeholder="100" min="20" max="200" <?= $labelType !== 'custom' ? 'disabled' : '' ?>>
                </div>
                <div class="col-md-2">
                    <label class="form-label">标签高度 (mm)</label>
                    <input type="number" class="form-control" name="labelHeight" id="labelHeight"
                           value="<?= $labelType === 'custom' ? $labelHeight : '' ?>"
                           placeholder="70" min="20" max="200" <?= $labelType !== 'custom' ? 'disabled' : '' ?>>
                </div>
                <div class="col-md-2">
                    <label class="form-label">列数</label>
                    <input type="number" class="form-control" name="cols" id="cols"
                           value="<?= $labelType === 'custom' ? $cols : '' ?>"
                           placeholder="2" min="1" max="10" <?= $labelType !== 'custom' ? 'disabled' : '' ?>>
                </div>
                <div class="col-md-2">
                    <label class="form-label">行数</label>
                    <input type="number" class="form-control" name="rows" id="rows"
                           value="<?= $labelType === 'custom' ? $rows : '' ?>"
                           placeholder="4" min="1" max="10" <?= $labelType !== 'custom' ? 'disabled' : '' ?>>
                </div>
                <div class="col-md-2">
                    <label class="form-label">间距 (mm)</label>
                    <input type="number" class="form-control" name="gap"
                           value="<?= $gap ?>" min="0" max="20">
                </div>
                <div class="col-md-2">
                    <label class="form-label">字体大小</label>
                    <select class="form-select" name="fontSize" onchange="updatePreview()">
                        <option value="10" <?= $fontSize == '10' ? 'selected' : '' ?>>10px</option>
                        <option value="12" <?= $fontSize == '12' ? 'selected' : '' ?>>12px (默认)</option>
                        <option value="14" <?= $fontSize == '14' ? 'selected' : '' ?>>14px</option>
                        <option value="16" <?= $fontSize == '16' ? 'selected' : '' ?>>16px</option>
                    </select>
                </div>
                <div class="col-12 mt-3">
                    <h6 class="text-primary mb-3">打印内容</h6>
                </div>
                <div class="col-md-4">
                    <label class="form-label">打印模式</label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="mode" id="modeBoth" value="both" <?= $printMode === 'both' ? 'checked' : '' ?>>
                        <label class="btn btn-outline-primary" for="modeBoth">二维码+属性</label>

                        <input type="radio" class="btn-check" name="mode" id="modeQR" value="qr" <?= $printMode === 'qr' ? 'checked' : '' ?>>
                        <label class="btn btn-outline-primary" for="modeQR">仅二维码</label>

                        <input type="radio" class="btn-check" name="mode" id="modeFields" value="fields" <?= $printMode === 'fields' ? 'checked' : '' ?>>
                        <label class="btn btn-outline-primary" for="modeFields">仅属性</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">选择打印字段</label>
                    <div class="p-2 border rounded" style="max-height: 150px; overflow-y: auto;">
                        <?php foreach ($allFields as $key => $label): ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input field-checkbox" type="checkbox" value="<?= $key ?>"
                                       id="field_<?= $key ?>" <?= in_array($key, $selectedFields) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="field_<?= $key ?>"><?= $label ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-12">
                    <button type="button" class="btn btn-primary me-2" onclick="updatePreview()">
                        <i class="bi bi-eye"></i> 预览
                    </button>
                    <button type="button" class="btn btn-success me-2" onclick="window.print()">
                        <i class="bi bi-printer"></i> 打印
                    </button>
                    <a class="btn btn-secondary" href="?p=assets">返回</a>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="print-area">
    <?php if ($printMode !== 'qr'): ?>
        <div class="print-header no-print">资产标签预览</div>
        <div class="print-info no-print">打印时间: <?= date('Y-m-d H:i:s') ?> | 共 <?= count($assets) ?> 个标签 | 纸张: <?= $paperWidth ?>x<?= $paperHeight ?>mm | 标签: <?= $labelWidth ?>x<?= $labelHeight ?>mm (<?= $cols ?>列 x <?= $rows ?>行)</div>
    <?php endif; ?>

    <div class="print-grid">
        <?php foreach ($assets as $asset): ?>
            <div class="label-container">
                <?php if ($printMode !== 'fields'): ?>
                    <div class="label-header">IT资产标签</div>
                    <div class="qr-code">
                        <img src="qr.php?id=<?= $asset['Id'] ?>&size=150" alt="QR Code" />
                    </div>
                <?php endif; ?>

                <?php if ($printMode !== 'qr' && !empty($selectedFields)): ?>
                    <div class="label-fields">
                        <?php foreach ($allFields as $key => $label): ?>
                            <?php if (in_array($key, $selectedFields)): ?>
                                <div class="label-field">
                                    <span class="field-label"><?= $label ?>:</span>
                                    <span class="field-value">
                                        <?php
                                        switch ($key) {
                                            case 'tag': echo h($asset['Tag']); break;
                                            case 'name': echo h($asset['Name']); break;
                                            case 'category': echo h($asset['Category'] ?: '-'); break;
                                            case 'model': echo h($asset['Model'] ?: '-'); break;
                                            case 'serial_number': echo h($asset['SerialNumber'] ?: '-'); break;
                                            case 'owner': echo $asset['OwnerFullName'] ? h($asset['OwnerFullName']) : '-'; break;
                                            case 'location': echo h($asset['Location'] ?: '-'); break;
                                            case 'supplier': echo h($asset['Supplier'] ?: '-'); break;
                                            case 'purchase_date': echo h($asset['PurchaseDate'] ?: '-'); break;
                                            case 'warranty_expiry': echo h($asset['WarrantyExpiry'] ?: '-'); break;
                                            case 'purchase_price': echo $asset['PurchasePrice'] ? '¥' . number_format($asset['PurchasePrice'], 2) : '-'; break;
                                            default: echo '-';
                                        }
                                        ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>

<script>
function updatePaperControls() {
    const paperType = document.getElementById('paperType').value;
    const widthInput = document.getElementById('paperWidth');
    const heightInput = document.getElementById('paperHeight');

    if (paperType === 'custom') {
        widthInput.disabled = false;
        heightInput.disabled = false;
    } else {
        widthInput.disabled = true;
        heightInput.disabled = true;
    }
}

function updateLabelControls() {
    const labelType = document.getElementById('labelType').value;
    const widthInput = document.getElementById('labelWidth');
    const heightInput = document.getElementById('labelHeight');
    const colsInput = document.getElementById('cols');
    const rowsInput = document.getElementById('rows');

    if (labelType === 'custom') {
        widthInput.disabled = false;
        heightInput.disabled = false;
        colsInput.disabled = false;
        rowsInput.disabled = false;
    } else {
        widthInput.disabled = true;
        heightInput.disabled = true;
        colsInput.disabled = true;
        rowsInput.disabled = true;
    }
}

function updatePreview() {
    const form = document.getElementById('printForm');
    const formData = new FormData(form);
    const params = new URLSearchParams(formData);

    // 收集选中的字段
    const checkboxes = document.querySelectorAll('.field-checkbox:checked');
    const fields = [];
    checkboxes.forEach(cb => fields.push(cb.value));
    params.set('fields', fields);

    // 获取资产ID
    const ids = '<?= implode(',', $ids) ?>';
    params.set('ids', ids);

    // 跳转到预览页面
    window.location.href = '?p=print_label&' + params.toString();
}

// 模式切换时自动更新预览
document.querySelectorAll('input[name="mode"]').forEach(radio => {
    radio.addEventListener('change', () => {
        const fieldsSection = document.querySelector('.btn-group').parentElement.nextElementSibling;
        if (radio.value === 'qr') {
            fieldsSection.style.opacity = '0.5';
            fieldsSection.style.pointerEvents = 'none';
        } else {
            fieldsSection.style.opacity = '1';
            fieldsSection.style.pointerEvents = 'auto';
        }
    });
});

// 初始化
document.addEventListener('DOMContentLoaded', () => {
    updatePaperControls();
    updateLabelControls();
    const mode = document.querySelector('input[name="mode"]:checked');
    if (mode && mode.value === 'qr') {
        const fieldsSection = document.querySelector('.btn-group').parentElement.nextElementSibling;
        fieldsSection.style.opacity = '0.5';
        fieldsSection.style.pointerEvents = 'none';
    }
});
</script>

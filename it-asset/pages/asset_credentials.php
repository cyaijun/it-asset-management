<?php
// pages/asset_credentials.php - 资产凭证管理
require_admin();

$assetId = isset($_GET['asset_id']) ? (int)$_GET['asset_id'] : 0;

// 验证资产存在
$stmt = $pdo->prepare("SELECT * FROM Assets WHERE Id = ?");
$stmt->execute([$assetId]);
$asset = $stmt->fetch();
if (!$asset) {
    flash_set('资产不存在', 'error');
    header('Location: ?p=assets');
    exit;
}

$mode = isset($_GET['mode']) ? $_GET['mode'] : 'view';
$editing = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

// 添加/编辑凭证
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    require_csrf();

    if ($_POST['action'] === 'save') {
        $errors = [];

        $data = [
            'credential_type' => trim($_POST['credential_type'] ?? ''),
            'credential_name' => trim($_POST['credential_name'] ?? ''),
            'username' => trim($_POST['username'] ?? ''),
            'password' => trim($_POST['password'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
        ];

        if (empty($data['credential_type'])) {
            $errors[] = '请选择凭证类型';
        }
        if (empty($data['password'])) {
            $errors[] = '请输入凭证值';
        }

        if (empty($errors)) {
            $encryptedValue = encrypt_credential($data['password']);

            if ($editing > 0) {
                // 更新
                $stmt = $pdo->prepare("UPDATE AssetCredentials SET CredentialType = ?, CredentialName = ?, Username = ?, EncryptedValue = ?, Description = ? WHERE Id = ? AND AssetId = ?");
                $stmt->execute([
                    $data['credential_type'],
                    $data['credential_name'],
                    $data['username'],
                    $encryptedValue,
                    $data['description'],
                    $editing,
                    $assetId
                ]);
                flash_set('凭证更新成功');
            } else {
                // 新增
                $stmt = $pdo->prepare("INSERT INTO AssetCredentials (AssetId, CredentialType, CredentialName, Username, EncryptedValue, Description) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $assetId,
                    $data['credential_type'],
                    $data['credential_name'],
                    $data['username'],
                    $encryptedValue,
                    $data['description']
                ]);
                flash_set('凭证添加成功');
            }

            header("Location: ?p=asset_credentials&asset_id={$assetId}");
            exit;
        }
    } elseif ($_POST['action'] === 'delete') {
        $credId = (int)$_POST['credential_id'];
        $stmt = $pdo->prepare("DELETE FROM AssetCredentials WHERE Id = ? AND AssetId = ?");
        $stmt->execute([$credId, $assetId]);

        // 记录审计日志
        $user = get_current_user_info();
        $pdo->prepare("INSERT INTO AuditLog (UserId, Action, ResourceType, ResourceId, Details, IpAddress) VALUES (?, 'delete', 'credential', ?, ?, ?)")
            ->execute([$user['id'], $credId, "删除资产 #{$assetId} 的凭证 #{$credId}", $_SERVER['REMOTE_ADDR'] ?? '']);

        flash_set('凭证已删除');
        header("Location: ?p=asset_credentials&asset_id={$assetId}");
        exit;
    }
}

// 查看凭证（需要验证码验证）
if ($mode === 'view' && isset($_GET['view_id'])) {
    $viewId = (int)$_GET['view_id'];
    $verified = false;

    // 生成验证码
    if (!isset($_SESSION['credential_verify_code']) || !isset($_SESSION['credential_verify_time']) || (time() - $_SESSION['credential_verify_time']) > 300) {
        $_SESSION['credential_verify_code'] = rand(100000, 999999);
        $_SESSION['credential_verify_time'] = time();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_code'])) {
        require_csrf();
        $inputCode = trim($_POST['verification_code'] ?? '');
        $storedCode = $_SESSION['credential_verify_code'] ?? '';
        $timeDiff = isset($_SESSION['credential_verify_time']) ? (time() - $_SESSION['credential_verify_time']) : 999;

        if (strval($inputCode) === strval($storedCode) && $timeDiff <= 300) {
            $verified = true;

            // 清除已使用的验证码
            unset($_SESSION['credential_verify_code']);
            unset($_SESSION['credential_verify_time']);

            // 更新访问记录
            $user = get_current_user_info();
            $stmt = $pdo->prepare("UPDATE AssetCredentials SET AccessCount = AccessCount + 1, LastAccessAt = NOW(), LastAccessBy = ? WHERE Id = ?");
            $stmt->execute([$user['id'], $viewId]);

            // 记录审计日志
            $cred = $pdo->query("SELECT * FROM AssetCredentials WHERE Id = {$viewId}")->fetch();
            $pdo->prepare("INSERT INTO AuditLog (UserId, Action, ResourceType, ResourceId, Details, IpAddress) VALUES (?, 'view', 'credential', ?, ?, ?)")
                ->execute([$user['id'], $viewId, "查看资产 #{$assetId} 的凭证: {$cred['CredentialType']} - {$cred['CredentialName']}", $_SERVER['REMOTE_ADDR'] ?? '']);
        } else {
            $errors[] = '验证码错误或已过期，请刷新页面重试';
            // 验证失败后重新生成验证码
            $_SESSION['credential_verify_code'] = rand(100000, 999999);
            $_SESSION['credential_verify_time'] = time();
        }
    }

    if ($verified) {
        $stmt = $pdo->prepare("SELECT * FROM AssetCredentials WHERE Id = ?");
        $stmt->execute([$viewId]);
        $cred = $stmt->fetch();
        $decryptedValue = decrypt_credential($cred['EncryptedValue']);
    }
}

// 获取所有凭证
$stmt = $pdo->prepare("SELECT ac.*, u.Username as LastAccessByName
                      FROM AssetCredentials ac
                      LEFT JOIN Users u ON ac.LastAccessBy = u.Id
                      WHERE ac.AssetId = ?
                      ORDER BY ac.Id DESC");
$stmt->execute([$assetId]);
$credentials = $stmt->fetchAll();
?>
<?php include __DIR__ . "/../templates/header.php"; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1><i class="bi bi-shield-lock"></i> 资产凭证</h1>
    <p class="mb-0 text-muted">资产：<?= h($asset['Name']) ?> (<?= h($asset['Tag']) ?>)</p>
  </div>
  <a class="btn btn-secondary" href="?p=asset_details&id=<?= $assetId ?>"><i class="bi bi-arrow-left"></i> 返回资产详情</a>
</div>

<?php if ($mode === 'view' && isset($viewId) && !$verified): ?>
<!-- 验证码验证对话框 -->
<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header bg-warning text-dark">
        <h6 class="mb-0"><i class="bi bi-shield-lock"></i> 安全验证</h6>
      </div>
      <div class="card-body">
        <div class="alert alert-warning">
          <i class="bi bi-exclamation-triangle"></i> 查看敏感信息需要输入验证码，验证码将在5分钟后失效
        </div>

        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
              <?= h($error) ?><br>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <form method="post">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>" />
          <input type="hidden" name="verify_code" value="1" />
          <div class="mb-3 text-center">
            <div class="alert alert-info d-inline-block px-4">
              <h3 class="mb-0" style="letter-spacing: 8px; font-weight: bold;">
                <?= $_SESSION['credential_verify_code'] ?>
              </h3>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">请输入上方显示的验证码 <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-shield-check"></i></span>
              <input type="text" class="form-control" name="verification_code" required autofocus maxlength="6" pattern="[0-9]{6}" placeholder="6位数字验证码" />
              <button type="button" class="btn btn-outline-secondary" onclick="location.reload()">
                <i class="bi bi-arrow-clockwise"></i> 刷新
              </button>
            </div>
          </div>
          <button type="submit" class="btn btn-warning w-100">
            <i class="bi bi-shield-check"></i> 验证并查看
          </button>
          <a class="btn btn-outline-secondary w-100 mt-2" href="?p=asset_credentials&asset_id=<?= $assetId ?>">取消</a>
        </form>
      </div>
    </div>
  </div>
</div>
<?php elseif (isset($verified) && $verified): ?>
<!-- 显示解密后的凭证 -->
<div class="row justify-content-center">
  <div class="col-md-8">
    <div class="card">
      <div class="card-header bg-success text-white">
        <h6 class="mb-0"><i class="bi bi-shield-check"></i> 凭证信息</h6>
      </div>
      <div class="card-body">
        <dl class="row">
          <dt class="col-sm-3">凭证类型</dt>
          <dd class="col-sm-9"><strong><?= h($cred['CredentialType']) ?></strong></dd>

          <dt class="col-sm-3">凭证名称</dt>
          <dd class="col-sm-9"><?= h($cred['CredentialName'] ?? '-') ?></dd>

          <?php if ($cred['Username']): ?>
          <dt class="col-sm-3">用户名</dt>
          <dd class="col-sm-9"><code><?= h($cred['Username']) ?></code></dd>
          <?php endif; ?>

          <dt class="col-sm-3">凭证值</dt>
          <dd class="col-sm-9">
            <div class="input-group">
              <input type="text" class="form-control" value="<?= h($decryptedValue) ?>" readonly id="credentialValue" />
              <button class="btn btn-outline-primary" onclick="copyToClipboard('credentialValue')">
                <i class="bi bi-clipboard"></i> 复制
              </button>
            </div>
          </dd>

          <dt class="col-sm-3">描述</dt>
          <dd class="col-sm-9"><?= h($cred['Description'] ?? '-') ?></dd>
        </dl>
        <hr>
        <a class="btn btn-secondary" href="?p=asset_credentials&asset_id=<?= $assetId ?>">
          <i class="bi bi-arrow-left"></i> 返回列表
        </a>
      </div>
    </div>
  </div>
</div>
<?php else: ?>
<!-- 凭证列表 -->
<div class="row mb-4">
  <div class="col-12">
    <button class="btn btn-primary" onclick="showAddForm()">
      <i class="bi bi-plus-circle"></i> 添加凭证
    </button>
  </div>
</div>

<?php if (isset($_GET['edit'])): ?>
<!-- 添加/编辑表单 -->
<div class="card mb-4" id="editForm">
  <div class="card-header bg-info text-white">
    <h6 class="mb-0"><i class="bi bi-pencil"></i> <?= $editing > 0 ? '编辑凭证' : '添加凭证' ?></h6>
  </div>
      <div class="card-body">
    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $error): ?>
            <li><?= h($error) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php
    // 获取编辑数据
    $editData = [
        'credential_type' => '',
        'credential_name' => '',
        'username' => '',
        'password' => '',
        'description' => '',
    ];
    if ($editing > 0) {
        $stmt = $pdo->prepare("SELECT * FROM AssetCredentials WHERE Id = ? AND AssetId = ?");
        $stmt->execute([$editing, $assetId]);
        $editData = $stmt->fetch();
    }
    ?>

    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>" />
      <input type="hidden" name="action" value="save" />
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">凭证类型 <span class="text-danger">*</span></label>
          <select class="form-select" name="credential_type" required>
            <option value="">请选择</option>
            <option value="账号" <?= ($editData['CredentialType'] ?? '') === '账号' ? 'selected' : '' ?>>账号</option>
            <option value="密码" <?= ($editData['CredentialType'] ?? '') === '密码' ? 'selected' : '' ?>>密码</option>
            <option value="API密钥" <?= ($editData['CredentialType'] ?? '') === 'API密钥' ? 'selected' : '' ?>>API密钥</option>
            <option value="License Key" <?= ($editData['CredentialType'] ?? '') === 'License Key' ? 'selected' : '' ?>>License Key</option>
            <option value="其他" <?= ($editData['CredentialType'] ?? '') === '其他' ? 'selected' : '' ?>>其他</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">凭证名称</label>
          <input type="text" class="form-control" name="credential_name" value="<?= h($editData['CredentialName'] ?? '') ?>" placeholder="例如：VPN账号、系统密码等" />
        </div>
        <div class="col-md-6">
          <label class="form-label">用户名（可选）</label>
          <input type="text" class="form-control" name="username" value="<?= h($editData['Username'] ?? '') ?>" placeholder="关联的用户名" />
        </div>
        <div class="col-md-6">
          <label class="form-label">凭证值 <span class="text-danger">*</span></label>
          <input type="password" class="form-control" name="password" required placeholder="<?= $editing > 0 ? '留空保持不变' : '敏感信息将加密存储' ?>" />
        </div>
        <div class="col-12">
          <label class="form-label">描述</label>
          <textarea class="form-control" name="description" rows="2" placeholder="凭证的用途说明"><?= h($editData['Description'] ?? '') ?></textarea>
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-success">
            <i class="bi bi-check-circle"></i> 保存
          </button>
          <button type="button" class="btn btn-secondary" onclick="hideEditForm()">
            <i class="bi bi-x-circle"></i> 取消
          </button>
        </div>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php if (empty($credentials)): ?>
  <div class="text-center py-5 text-muted">
    <i class="bi bi-inbox" style="font-size: 3rem;"></i>
    <p class="mt-3">暂无凭证信息</p>
  </div>
<?php else: ?>
  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-striped table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>凭证类型</th>
              <th>凭证名称</th>
              <th>用户名</th>
              <th>凭证值（已加密）</th>
              <th>访问记录</th>
              <th>操作</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($credentials as $cred): ?>
              <tr>
                <td><span class="badge bg-primary"><?= h($cred['CredentialType']) ?></span></td>
                <td><?= h($cred['CredentialName'] ?? '-') ?></td>
                <td><?= h($cred['Username'] ?? '-') ?></td>
                <td><code class="text-muted">*** <?= mask_sensitive_data($cred['EncryptedValue'], 0, 10) ?> ***</code></td>
                <td>
                  <small>
                    <?= $cred['AccessCount'] ?> 次访问<br>
                    <?= $cred['LastAccessAt'] ? h($cred['LastAccessAt']) : '从未' ?>
                    <?php if ($cred['LastAccessByName']): ?>
                      <br><span class="text-muted">by <?= h($cred['LastAccessByName']) ?></span>
                    <?php endif; ?>
                  </small>
                </td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <a class="btn btn-success" href="?p=asset_credentials&asset_id=<?= $assetId ?>&mode=view&view_id=<?= $cred['Id'] ?>" title="查看">
                      <i class="bi bi-eye"></i> 查看
                    </a>
                    <a class="btn btn-primary" href="?p=asset_credentials&asset_id=<?= $assetId ?>&edit=<?= $cred['Id'] ?>" title="编辑">
                      <i class="bi bi-pencil"></i>
                    </a>
                    <form method="post" style="display:inline;">
                      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>" />
                      <input type="hidden" name="action" value="delete" />
                      <input type="hidden" name="credential_id" value="<?= $cred['Id'] ?>" />
                      <button type="submit" class="btn btn-danger" onclick="return confirm('确定要删除此凭证吗？')" title="删除">
                        <i class="bi bi-trash"></i>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php endif; ?>
<?php endif; ?>

<script>
var assetId = <?= $assetId ?>;

function showAddForm() {
    window.location.href = '?p=asset_credentials&asset_id=' + assetId + '&edit=0';
}

function hideEditForm() {
    window.location.href = '?p=asset_credentials&asset_id=' + assetId;
}

function copyToClipboard(elementId) {
    const element = document.getElementById(elementId);
    element.select();
    element.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(element.value);
    alert('已复制到剪贴板');
}
</script>

<?php include __DIR__ . "/../templates/footer.php"; ?>

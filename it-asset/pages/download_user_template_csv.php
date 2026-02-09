<?php
// pages/download_user_template_csv.php - 下载用户导入模板 (CSV格式)
require_admin();

require __DIR__ . '/../lib/vendor/simplexlsx.php';

$excel = new SimpleExcelExporter([
    '用户名*', '姓名*', '邮箱', '部门', '角色', '密码'
]);

// 添加示例数据
$excel->addRow([
    'zhangsan',
    '张三',
    'zhangsan@example.com',
    '技术部',
    'user',
    '123456'
]);

$excel->addRow([
    'lisi',
    '李四',
    'lisi@example.com',
    '人事部',
    'user',
    '123456'
]);

$excel->download('user_import_template');

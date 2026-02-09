<?php
// validation.php - 输入验证

// 验证必填字段
function validate_required($data, $fields) {
    $errors = [];
    foreach ($fields as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            $errors[] = "{$field} 不能为空";
        }
    }
    return $errors;
}

// 验证邮箱
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// 验证长度
function validate_length($value, $min, $max) {
    $len = strlen($value);
    return $len >= $min && $len <= $max;
}

// 验证日期
function validate_date($date) {
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && strtotime($date);
}

// 验证数字
function validate_number($value, $min = null, $max = null) {
    if (!is_numeric($value)) {
        return false;
    }
    $num = floatval($value);
    if ($min !== null && $num < $min) {
        return false;
    }
    if ($max !== null && $num > $max) {
        return false;
    }
    return true;
}

// 验证标签唯一性
function validate_tag_unique($pdo, $tag, $excludeId = null) {
    $sql = "SELECT Id FROM Assets WHERE Tag = ?";
    $params = [$tag];
    if ($excludeId) {
        $sql .= " AND Id != ?";
        $params[] = $excludeId;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch() === false;
}

// 验证用户名唯一性
function validate_username_unique($pdo, $username, $excludeId = null) {
    $sql = "SELECT Id FROM Users WHERE Username = ?";
    $params = [$username];
    if ($excludeId) {
        $sql .= " AND Id != ?";
        $params[] = $excludeId;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch() === false;
}

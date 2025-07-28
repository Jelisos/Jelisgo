<?php
/**
 * 用户密码修改接口 - 统一认证版本
 * @author AI
 * @return JSON
 */
header('Content-Type: application/json; charset=utf-8');
require_once '../config.php';

// 获取请求头中的Authorization信息
$headers = getallheaders();
$user_id = null;

// 从请求中获取用户ID
if (isset($_POST['user_id']) && !empty($_POST['user_id'])) {
    // 从POST参数获取用户ID
    $user_id = intval($_POST['user_id']);
} elseif (isset($headers['Authorization']) && !empty($headers['Authorization'])) {
    // 从Authorization头获取用户ID
    $auth_parts = explode(' ', $headers['Authorization']);
    if (count($auth_parts) == 2 && $auth_parts[0] == 'Bearer') {
        $user_id = intval($auth_parts[1]);
    }
}

// 检查用户是否登录
if (!$user_id) {
    echo json_encode([
        'code' => 401,
        'msg' => '未登录',
        'data' => null
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 获取请求数据
$input = json_decode(file_get_contents('php://input'), true);
if ($input) {
    // JSON格式请求
    $old_password = $input['old_password'] ?? '';
    $new_password = $input['new_password'] ?? '';
    $confirm_password = $input['confirm_password'] ?? '';
} else {
    // 表单格式请求
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
}

if (!$old_password || !$new_password || !$confirm_password) {
    echo json_encode([
        'code' => 2,
        'msg' => '所有字段均为必填',
        'data' => null
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($new_password !== $confirm_password) {
    echo json_encode([
        'code' => 3,
        'msg' => '两次输入的新密码不一致',
        'data' => null
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
if (strlen($new_password) < 4) {
    echo json_encode([
        'code' => 4,
        'msg' => '新密码长度不能少于4位',
        'data' => null
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PWD, DB_NAME);
if ($conn->connect_error) {
    echo json_encode([
        'code' => 500,
        'msg' => '数据库连接失败',
        'data' => null
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $conn->prepare('SELECT password FROM users WHERE id=?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($db_password);
$stmt->fetch();
$stmt->close();

if (!password_verify($old_password, $db_password)) {
    echo json_encode([
        'code' => 5,
        'msg' => '旧密码错误',
        'data' => null
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
$stmt = $conn->prepare('UPDATE users SET password=? WHERE id=?');
$stmt->bind_param('si', $new_password_hash, $user_id);
if ($stmt->execute()) {
    echo json_encode([
        'code' => 0,
        'msg' => '密码修改成功',
        'data' => null
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        'code' => 6,
        'msg' => '密码修改失败',
        'data' => null
    ], JSON_UNESCAPED_UNICODE);
}
$stmt->close();
$conn->close();
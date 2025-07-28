<?php
/**
 * 用户资料修改接口 - 统一认证版本
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

$username = trim($_POST['username'] ?? '');

if (!$username) {
    echo json_encode([
        'code' => 2,
        'msg' => '昵称不能为空',
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
$conn->set_charset("utf8mb4");

// 检查昵称是否已被其他用户使用
$check_stmt = $conn->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
$check_stmt->bind_param('si', $username, $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    echo json_encode([
        'code' => 5,
        'msg' => '该昵称已被其他用户使用，请更换',
        'data' => null
    ], JSON_UNESCAPED_UNICODE);
    $check_stmt->close();
    $conn->close();
    exit;
}
$check_stmt->close();

$stmt = $conn->prepare('UPDATE users SET username=? WHERE id=?');
$stmt->bind_param('si', $username, $user_id);
if ($stmt->execute()) {
    echo json_encode([
        'code' => 0,
        'msg' => '资料修改成功',
        'data' => [
            'username' => $username,
            'user_id' => $user_id
        ]
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        'code' => 4,
        'msg' => '资料修改失败',
        'data' => null
    ], JSON_UNESCAPED_UNICODE);
}
$stmt->close();
$conn->close();
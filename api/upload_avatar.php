<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
function log_debug($msg) {
    file_put_contents(__DIR__.'/../static/avatar/upload_debug.log', date('Y-m-d H:i:s').' '. $msg."\n", FILE_APPEND);
}
ob_start();
log_debug('接口开始');
/**
 * 用户头像上传接口 - 统一认证版本
 * @author AI
 * @return JSON
 */
header('Content-Type: application/json; charset=utf-8');
require_once '../config.php';

// 获取请求头中的Authorization信息
$headers = getallheaders();
log_debug('请求头: ' . json_encode($headers));
$user_id = null;

// 从请求中获取用户ID
if (isset($_POST['user_id']) && !empty($_POST['user_id'])) {
    // 从POST参数获取用户ID
    $user_id = intval($_POST['user_id']);
    log_debug('从POST获取用户ID: ' . $user_id);
} elseif (isset($headers['Authorization']) && !empty($headers['Authorization'])) {
    // 从Authorization头获取用户ID
    $auth_parts = explode(' ', $headers['Authorization']);
    if (count($auth_parts) == 2 && $auth_parts[0] == 'Bearer') {
        $user_id = intval($auth_parts[1]);
        log_debug('从Authorization头获取用户ID: ' . $user_id);
    }
}

// 检查用户是否登录
if (!$user_id) {
    log_debug('未登录');
    ob_clean();
    echo json_encode([
        'code' => 401,
        'msg' => '未登录',
        'data' => null
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    log_debug('未选择文件或上传失败');
    ob_clean();
    echo json_encode([
        'code' => 2,
        'msg' => '未选择文件或上传失败',
        'data' => null
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$file = $_FILES['avatar'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allow_ext = ['jpg', 'jpeg', 'png', 'webp'];
if (!in_array($ext, $allow_ext)) {
    log_debug('格式不支持:'.$ext);
    ob_clean();
    echo json_encode([
        'code' => 3,
        'msg' => '仅支持jpg/jpeg/png/webp格式',
        'data' => null
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($file['size'] > 2 * 1024 * 1024) {
    log_debug('文件过大:'.$file['size']);
    ob_clean();
    echo json_encode([
        'code' => 4,
        'msg' => '文件不能超过2MB',
        'data' => null
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
$upload_dir = '../static/avatar/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
    log_debug('创建目录:'.$upload_dir);
}
$filename = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
$target = $upload_dir . $filename;
if (!move_uploaded_file($file['tmp_name'], $target)) {
    log_debug('move_uploaded_file失败');
    ob_clean();
    echo json_encode([
        'code' => 5,
        'msg' => '保存失败',
        'data' => null
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
log_debug('文件保存成功:'.$target);
// 保存路径到数据库
$conn = new mysqli(DB_HOST, DB_USER, DB_PWD, DB_NAME);
if ($conn->connect_error) {
    log_debug('数据库连接失败:'.$conn->connect_error);
    ob_clean();
    echo json_encode([
        'code' => 500,
        'msg' => '数据库连接失败',
        'data' => null
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
$url = '/static/avatar/' . $filename;
$stmt = $conn->prepare('UPDATE users SET avatar=? WHERE id=?');
$stmt->bind_param('si', $url, $user_id);
if ($stmt->execute()) {
    log_debug('数据库更新成功:'.$url);
    ob_clean();
    echo json_encode([
        'code' => 0,
        'msg' => '上传成功',
        'data' => $url
    ], JSON_UNESCAPED_UNICODE);
} else {
    log_debug('数据库保存失败');
    ob_clean();
    echo json_encode([
        'code' => 6,
        'msg' => '数据库保存失败',
        'data' => null
    ], JSON_UNESCAPED_UNICODE);
}
$stmt->close();
$conn->close();
log_debug('接口结束');
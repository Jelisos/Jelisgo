<?php
require_once '../config.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 处理OPTIONS请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['code' => 1, 'msg' => '请求方式错误']);
    exit;
}

$wallpaper_id = $_GET['wallpaper_id'] ?? '';
if (empty($wallpaper_id)) {
    echo json_encode(['code' => 1, 'msg' => '参数错误: wallpaper_id 不能为空']);
    exit;
}

$ip = $_SERVER['REMOTE_ADDR'];

// 检查用户登录状态
$user_id = null;

// 获取请求头中的Authorization信息
$headers = getallheaders();

// 从请求中获取用户ID
if (isset($_GET['user_id']) && !empty($_GET['user_id'])) {
    // 从GET参数获取用户ID
    $user_id = intval($_GET['user_id']);
} elseif (isset($headers['Authorization']) && !empty($headers['Authorization'])) {
    // 从Authorization头获取用户ID
    $auth_parts = explode(' ', $headers['Authorization']);
    if (count($auth_parts) == 2 && $auth_parts[0] == 'Bearer') {
        $user_id = intval($auth_parts[1]);
    }
}

$db = new mysqli(DB_HOST, DB_USER, DB_PWD, DB_NAME);
if ($db->connect_errno) {
    echo json_encode(['code' => 1, 'msg' => '数据库连接失败']);
    exit;
}

if ($user_id) {
    // 登录用户：检查用户ID的点赞记录
    $stmt = $db->prepare('SELECT id FROM wallpaper_likes WHERE wallpaper_id=? AND user_id=?');
    $stmt->bind_param('si', $wallpaper_id, $user_id);
} else {
    // 未登录用户：检查IP地址的点赞记录
    $stmt = $db->prepare('SELECT id FROM wallpaper_likes WHERE wallpaper_id=? AND ip_address=? AND user_id IS NULL');
    $stmt->bind_param('ss', $wallpaper_id, $ip);
}

$stmt->execute();
$stmt->store_result();

$liked = $stmt->num_rows > 0;
$stmt->close();
$db->close();

echo json_encode(['code' => 0, 'liked' => $liked]);
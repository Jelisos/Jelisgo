<?php
/**
 * 获取当前登录用户浏览历史壁纸列表接口 - 统一认证版本
 * @author AI
 * @return JSON
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 处理OPTIONS请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../config.php';

// 获取请求头中的Authorization信息
$headers = getallheaders();
$user_id = null;

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

// 检查用户是否登录
if (!$user_id) {
    echo json_encode([
        'code' => 401,
        'msg' => '未登录',
        'data' => null
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 数据库连接
$conn = new mysqli(DB_HOST, DB_USER, DB_PWD, DB_NAME);
if ($conn->connect_error) {
    echo json_encode([
        'code' => 500,
        'msg' => '数据库连接失败',
        'data' => null
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 查询用户浏览历史壁纸
$sql = "SELECT w.id, w.title, w.thumb, w.created_at FROM wallpapers w INNER JOIN history h ON w.id = h.wallpaper_id WHERE h.user_id = ? ORDER BY h.created_at DESC LIMIT 50";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$list = [];
while ($row = $result->fetch_assoc()) {
    $list[] = $row;
}
$stmt->close();
$conn->close();

echo json_encode([
    'code' => 0,
    'msg' => 'success',
    'data' => $list
], JSON_UNESCAPED_UNICODE);
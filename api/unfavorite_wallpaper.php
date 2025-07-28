<?php
session_start();
require_once __DIR__ . '/auth_unified.php'; // 引入统一认证
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
require_once '../config.php'; // 引入数据库配置
require_once 'utils.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['code' => 1, 'msg' => '请求方式错误']);
    exit;
}

$wallpaper_id = isset($_POST['wallpaper_id']) ? intval($_POST['wallpaper_id']) : 0;
if (!$wallpaper_id) {
    echo json_encode(['code' => 1, 'msg' => '参数错误: wallpaper_id 不能为空']);
    exit;
}

// 2024-12-19 修复：支持Authorization头认证
$user_id = null;

// 1. 优先从Authorization头获取用户ID
$headers = getallheaders();
if (isset($headers['Authorization'])) {
    $auth_parts = explode(' ', $headers['Authorization']);
    if (count($auth_parts) == 2 && $auth_parts[0] == 'Bearer') {
        $user_id = intval($auth_parts[1]);
    }
}

// 2. 如果Authorization头中没有，则检查session（向后兼容）
if (!$user_id && isset($_SESSION['user_id']) && $_SESSION['user_id']) {
    $user_id = $_SESSION['user_id'];
} elseif (!$user_id && isset($_SESSION['user']['id']) && $_SESSION['user']['id']) {
    $user_id = $_SESSION['user']['id'];
}

if (!$user_id) {
    echo json_encode(['code' => 401, 'msg' => '未登录，请先登录才能收藏']);
    exit;
}

$db = new mysqli(DB_HOST, DB_USER, DB_PWD, DB_NAME);
if ($db->connect_errno) {
    echo json_encode(['code' => 1, 'msg' => '数据库连接失败']);
    exit;
}

$stmt = $db->prepare('DELETE FROM wallpaper_favorites WHERE user_id = ? AND wallpaper_id = ?');
$stmt->bind_param('ii', $user_id, $wallpaper_id);
if ($stmt->execute()) {
    echo json_encode(['code' => 0, 'msg' => '取消收藏成功']);
    exit;
} else {
    echo json_encode(['code' => 1, 'msg' => '取消收藏失败']);
    exit;
}

$stmt->close();
$db->close();
exit();
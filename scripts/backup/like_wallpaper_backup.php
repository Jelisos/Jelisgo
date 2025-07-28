<?php
session_start(); // 启动会话
ini_set('display_errors', 0); // 关闭错误显示
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING); // 仅报告致命错误和解析错误，忽略通知和警告
include_once '../config.php';
include_once './write_log.php'; // 引入日志函数
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

// 获取用户IP地址
$ip_address = $_SERVER['REMOTE_ADDR'];
if (!$ip_address) {
    $ip_address = '127.0.0.1';
}

// 检查用户登录状态
$user_id = null;
if (isset($_SESSION['user_id']) && $_SESSION['user_id']) {
    $user_id = $_SESSION['user_id'];
} elseif (isset($_SESSION['user']['id']) && $_SESSION['user']['id']) {
    $user_id = $_SESSION['user']['id'];
}

$db = new mysqli(DB_HOST, DB_USER, DB_PWD, DB_NAME);
if ($db->connect_errno) {
    echo json_encode(['code' => 1, 'msg' => '数据库连接失败']);
    exit;
}

if ($user_id) {
    // 登录用户：先删除该用户对此壁纸的所有点赞记录（包括IP和用户ID的记录），然后插入新的用户ID记录
    $delete_stmt = $db->prepare('DELETE FROM wallpaper_likes WHERE wallpaper_id = ? AND (user_id = ? OR ip_address = ?)');
    $delete_stmt->bind_param('iis', $wallpaper_id, $user_id, $ip_address);
    $delete_stmt->execute();
    $delete_stmt->close();
    
    // 插入新的登录用户点赞记录
    $stmt = $db->prepare('INSERT INTO wallpaper_likes (wallpaper_id, user_id, ip_address) VALUES (?, ?, ?)');
    $stmt->bind_param('iis', $wallpaper_id, $user_id, $ip_address);
    $log_data = ['wallpaper_id'=>$wallpaper_id, 'user_id'=>$user_id, 'ip_address'=>$ip_address];
} else {
    // 未登录用户：使用IP地址点赞
    $stmt = $db->prepare('INSERT IGNORE INTO wallpaper_likes (wallpaper_id, ip_address) VALUES (?, ?)');
    $stmt->bind_param('is', $wallpaper_id, $ip_address);
    $log_data = ['wallpaper_id'=>$wallpaper_id, 'ip_address'=>$ip_address];
}

if ($stmt->execute()) {
    sendDebugLog(json_encode(['code'=>200, 'msg'=>'点赞成功'] + $log_data), 'wallpaper_debug_log.txt', 'append', 'like_success');
    echo json_encode(['code' => 0, 'msg' => '点赞成功']);
} else {
    sendDebugLog(json_encode(['code'=>500, 'msg'=>'点赞失败', 'error'=>$stmt->error] + $log_data), 'wallpaper_debug_log.txt', 'append', 'like_fail');
    echo json_encode(['code' => 1, 'msg' => '点赞失败']);
}
$stmt->close();
$db->close();
exit;
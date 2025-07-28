<?php
/**
 * 获取当前用户点赞的壁纸ID列表接口
 * @author AI
 * @return JSON
 */
ob_start(); // 启动输出缓冲
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config.php';

// 获取用户IP地址
$ip_address = $_SERVER['REMOTE_ADDR'];
if (!$ip_address) {
    $ip_address = '127.0.0.1'; // 确保有一个默认IP，防止空值
}

// 检查用户登录状态
$user_id = null;
if (isset($_SESSION['user_id']) && $_SESSION['user_id']) {
    $user_id = $_SESSION['user_id'];
} elseif (isset($_SESSION['user']['id']) && $_SESSION['user']['id']) {
    $user_id = $_SESSION['user']['id'];
}

// 数据库连接
$conn = new mysqli(DB_HOST, DB_USER, DB_PWD, DB_NAME);
if ($conn->connect_error) {
    ob_clean(); // 清除之前的任何输出
    echo json_encode([
        'code' => 500,
        'msg' => '数据库连接失败',
        'data' => null
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush(); // 关闭输出缓冲并发送输出
    exit;
}

// 查询用户点赞的壁纸ID
if ($user_id) {
    // 登录用户：查询用户ID的点赞记录
    $sql = "SELECT wallpaper_id FROM wallpaper_likes WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
} else {
    // 未登录用户：查询IP地址的点赞记录（排除已有用户ID的记录）
    $sql = "SELECT wallpaper_id FROM wallpaper_likes WHERE ip_address = ? AND user_id IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $ip_address);
}

$stmt->execute();
$result = $stmt->get_result();
$liked_wallpaper_ids = [];
while ($row = $result->fetch_assoc()) {
    $liked_wallpaper_ids[] = $row['wallpaper_id']; // 2024-07-26 修复：移除 (int) 强制转换，避免BIGINT截断
}
$stmt->close();
$conn->close();

ob_clean(); // 清除之前的任何输出
echo json_encode([
    'code' => 0,
    'msg' => 'success',
    'data' => $liked_wallpaper_ids
], JSON_UNESCAPED_UNICODE); 
ob_end_flush(); // 关闭输出缓冲并发送输出
exit;
?>
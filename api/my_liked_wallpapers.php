<?php
/**
 * 获取当前用户点赞的壁纸详细信息接口 - 统一认证版本
 * 文件: api/my_liked_wallpapers.php
 * 描述: 返回用户喜欢的壁纸完整信息，用于个人中心"我的喜欢"页面显示
 * 功能: 解决dashboard页面显示空白的问题
 */
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 处理OPTIONS请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config.php';

// 获取用户IP地址
$ip_address = $_SERVER['REMOTE_ADDR'];
if (!$ip_address) {
    $ip_address = '127.0.0.1';
}

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

// 数据库连接
$conn = new mysqli(DB_HOST, DB_USER, DB_PWD, DB_NAME);
if ($conn->connect_error) {
    echo json_encode([
        'code' => 500,
        'msg' => '数据库连接失败',
        'data' => []
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // 构建查询SQL
    if ($user_id) {
        // 登录用户：查询用户ID的点赞记录
        $sql = "SELECT w.id, w.title, w.description, w.file_path, 
                       w.category, w.tags, w.created_at, w.likes, w.views,
                       u.username as uploader_name
                FROM wallpaper_likes wl 
                JOIN wallpapers w ON wl.wallpaper_id = w.id 
                LEFT JOIN users u ON w.user_id = u.id
                WHERE wl.user_id = ? 
                ORDER BY wl.created_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $user_id);
    } else {
        // 未登录用户：查询IP地址的点赞记录（排除已有用户ID的记录）
        $sql = "SELECT w.id, w.title, w.description, w.file_path, 
                       w.category, w.tags, w.created_at, w.likes, w.views,
                       u.username as uploader_name
                FROM wallpaper_likes wl 
                JOIN wallpapers w ON wl.wallpaper_id = w.id 
                LEFT JOIN users u ON w.user_id = u.id
                WHERE wl.ip_address = ? AND wl.user_id IS NULL 
                ORDER BY wl.created_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $ip_address);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $liked_wallpapers = [];
    while ($row = $result->fetch_assoc()) {
        // 处理标签
        $tags = [];
        if (!empty($row['tags'])) {
            $tags = explode(',', $row['tags']);
            $tags = array_map('trim', $tags);
        }
        
        $liked_wallpapers[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'description' => $row['description'],
            'image_url' => $row['file_path'],
            'thumbnail_url' => $row['file_path'], // 使用同一个文件路径
            'category' => $row['category'],
            'tags' => $tags,
            'upload_time' => $row['created_at'],
            'likes_count' => (int)$row['likes'],
            'downloads_count' => (int)$row['views'], // 使用views作为下载数
            'uploader_name' => $row['uploader_name'] ?: '匿名用户'
        ];
    }
    
    $stmt->close();
    $conn->close();
    
    echo json_encode([
        'code' => 200,
        'msg' => 'success',
        'data' => $liked_wallpapers
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'code' => 500,
        'msg' => '查询失败: ' . $e->getMessage(),
        'data' => []
    ], JSON_UNESCAPED_UNICODE);
}

exit;
?>
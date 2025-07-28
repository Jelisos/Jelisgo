<?php
/**
 * 下载记录API v2
 * 基于localStorage用户ID记录下载行为
 * 文件: api/vip/record_download_v2.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 处理OPTIONS预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 只允许POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => '只允许POST请求',
        'code' => 405
    ]);
    exit();
}

// 数据库连接函数
function getDbConnection() {
    try {
        $pdo = new PDO(
            'mysql:host=localhost;dbname=wallpaper_db;charset=utf8mb4',
            'root',
            '',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        error_log('数据库连接失败: ' . $e->getMessage());
        return null;
    }
}

// 简化下载处理：仅扣减配额
function recordUserDownload($user_id, $wallpaper_id, $is_restricted, $download_url, $file_size) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return false;
    }
    
    try {
        // 验证用户是否存在
        $user_stmt = $pdo->prepare("SELECT id, username, membership_type FROM users WHERE id = ?");
        $user_stmt->execute([$user_id]);
        $user = $user_stmt->fetch();
        
        if (!$user) {
            error_log("下载处理失败: 用户ID {$user_id} 不存在");
            return false;
        }
        
        // 扣减用户配额（永久会员除外）
        if ($user['membership_type'] !== 'permanent') {
            $quota_stmt = $pdo->prepare("
                UPDATE users 
                SET download_quota = GREATEST(0, download_quota - 1) 
                WHERE id = ?
            ");
            $quota_stmt->execute([$user_id]);
        }
        
        return true;
        
    } catch (PDOException $e) {
        error_log('下载处理失败: ' . $e->getMessage());
        return false;
    }
}

try {
    // 获取POST数据
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode([
            'success' => false,
            'message' => '无效的JSON数据',
            'code' => 400
        ]);
        exit();
    }
    
    // 验证必需参数
    $user_id = $input['user_id'] ?? null;
    $wallpaper_id = $input['wallpaper_id'] ?? null;
    $is_restricted = $input['is_restricted'] ?? null;
    $download_url = $input['download_url'] ?? '';
    $file_size = $input['file_size'] ?? 0;
    
    if ($user_id === null) {
        echo json_encode([
            'success' => false,
            'message' => '缺少user_id参数',
            'code' => 400
        ]);
        exit();
    }
    
    if ($wallpaper_id === null) {
        echo json_encode([
            'success' => false,
            'message' => '缺少wallpaper_id参数',
            'code' => 400
        ]);
        exit();
    }
    
    if ($is_restricted === null) {
        echo json_encode([
            'success' => false,
            'message' => '缺少is_restricted参数',
            'code' => 400
        ]);
        exit();
    }
    
    // 转换参数类型
    $user_id = intval($user_id);
    $wallpaper_id = intval($wallpaper_id);
    $is_restricted = filter_var($is_restricted, FILTER_VALIDATE_BOOLEAN);
    $file_size = intval($file_size);
    
    if ($user_id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => '无效的user_id',
            'code' => 400
        ]);
        exit();
    }
    
    if ($wallpaper_id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => '无效的wallpaper_id',
            'code' => 400
        ]);
        exit();
    }
    
    // 处理下载行为
    $record_result = recordUserDownload($user_id, $wallpaper_id, $is_restricted, $download_url, $file_size);
    
    if ($record_result) {
        echo json_encode([
            'success' => true,
            'message' => '下载处理成功',
            'data' => [
                'user_id' => $user_id,
                'wallpaper_id' => $wallpaper_id,
                'is_restricted' => $is_restricted,
                'processed_at' => date('Y-m-d H:i:s')
            ],
            'code' => 200
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => '下载处理失败',
            'code' => 500
        ]);
    }
    
} catch (Exception $e) {
    error_log('record_download_v2.php 错误: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '服务器内部错误',
        'code' => 500
    ]);
}
?>
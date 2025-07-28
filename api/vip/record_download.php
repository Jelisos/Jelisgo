<?php
/**
 * 下载记录API
 * 记录用户下载行为并扣减配额
 * 
 * @author AI Assistant
 * @date 2024-01-27
 */

require_once 'membership_functions.php';
require_once '../auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 处理OPTIONS请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 只允许POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false, 
        'message' => '只允许POST请求',
        'code' => 405
    ]);
    exit;
}

try {
    // 检查用户登录状态
    $user = getCurrentUser();
    if (!$user) {
        http_response_code(401);
        echo json_encode([
            'success' => false, 
            'message' => '请先登录',
            'code' => 401
        ]);
        exit;
    }
    
    // 获取POST数据
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode([
            'success' => false, 
            'message' => '无效的JSON数据',
            'code' => 400
        ]);
        exit;
    }
    
    $wallpaper_id = $input['wallpaper_id'] ?? null;
    $is_restricted = $input['is_restricted'] ?? null;
    $download_url = $input['download_url'] ?? '';
    $file_size = $input['file_size'] ?? 0;
    
    // 验证必需参数
    if ($wallpaper_id === null) {
        echo json_encode([
            'success' => false, 
            'message' => '缺少wallpaper_id参数',
            'code' => 400
        ]);
        exit;
    }
    
    if ($is_restricted === null) {
        echo json_encode([
            'success' => false, 
            'message' => '缺少is_restricted参数',
            'code' => 400
        ]);
        exit;
    }
    
    // 转换参数类型
    $wallpaper_id = intval($wallpaper_id);
    $is_restricted = filter_var($is_restricted, FILTER_VALIDATE_BOOLEAN);
    $file_size = intval($file_size);
    
    if ($wallpaper_id <= 0) {
        echo json_encode([
            'success' => false, 
            'message' => '无效的wallpaper_id',
            'code' => 400
        ]);
        exit;
    }
    
    // 根据is_restricted参数确定下载类型
    $download_type = $is_restricted ? 'hd_combo' : 'original';
    
    // 再次检查下载权限（防止前端绕过）
    $permission_result = checkDownloadPermission($user['id'], $download_type);
    
    if (!$permission_result['allowed']) {
        echo json_encode([
            'success' => false,
            'message' => $permission_result['reason'],
            'reason' => 'permission_denied',
            'code' => 403
        ]);
        exit;
    }
    
    // 开始事务
    $pdo = getMembershipDbConnection();
    $pdo->beginTransaction();
    
    try {
        // 扣减下载配额（仅对受限内容）
        if ($is_restricted) {
            $quota_result = consumeDownloadQuota($user['id'], $download_type, $wallpaper_id);
            if (!$quota_result) {
                $pdo->rollBack();
                echo json_encode([
                    'success' => false,
                    'message' => '配额扣减失败',
                    'reason' => 'quota_consume_failed',
                    'code' => 403
                ]);
                exit;
            }
        }
        
        // 记录下载日志
        $stmt = $pdo->prepare("
            INSERT INTO user_download_logs 
            (user_id, wallpaper_id, is_restricted, download_url, file_size, download_time, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, NOW(), ?, ?)
        ");
        
        $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $stmt->execute([
            $user['id'],
            $wallpaper_id,
            $is_restricted ? 1 : 0,
            $download_url,
            $file_size,
            $ip_address,
            $user_agent
        ]);
        
        $download_log_id = $pdo->lastInsertId();
        
        // 提交事务
        $pdo->commit();
        
        // 获取更新后的用户信息
        $membership_info = getUserMembershipInfo($user['id']);
        
        // 下载记录已成功保存到数据库
        
        // 构建成功响应
        $response = [
            'success' => true,
            'message' => '下载记录成功',
            'data' => [
                'download_log_id' => $download_log_id,
                'wallpaper_id' => $wallpaper_id,
                'is_restricted' => $is_restricted,
                'quota_consumed' => $is_restricted,
                'user_info' => [
                    'membership_type' => $membership_info['membership_type'],
                    'membership_expires_at' => $membership_info['membership_expires_at'],
                    'daily_downloads_used' => $membership_info['daily_downloads_used'],
                    'daily_download_limit' => $membership_info['daily_download_limit'],
                    'remaining_downloads' => max(0, $membership_info['daily_download_limit'] - $membership_info['daily_downloads_used'])
                ]
            ],
            'code' => 200,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        // 回滚事务
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    // 记录错误日志
    error_log("下载记录API错误: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '服务器内部错误，请稍后重试',
        'code' => 500,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
}

?>
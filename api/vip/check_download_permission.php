<?php
/**
 * 下载权限检查API
 * 检查用户是否有权限下载指定壁纸
 * 
 * @author AI Assistant
 * @date 2024-01-27
 */

require_once 'membership_functions.php';
require_once '../auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 处理OPTIONS请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 允许GET和POST请求
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'])) {
    http_response_code(405);
    echo json_encode([
        'success' => false, 
        'message' => '只允许GET或POST请求',
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
    
    // 获取参数
    $wallpaper_id = null;
    $is_restricted = null;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if ($input) {
            $wallpaper_id = $input['wallpaper_id'] ?? null;
            $is_restricted = $input['is_restricted'] ?? null;
        }
    } else {
        $wallpaper_id = $_GET['wallpaper_id'] ?? null;
        $is_restricted = $_GET['is_restricted'] ?? null;
    }
    
    // 验证参数
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
    
    // 检查下载权限
    $permission_result = checkDownloadPermission($user['id'], $download_type);
    
    // 转换返回格式以匹配前端期望
    $can_download = $permission_result['allowed'] ?? false;
    $message = $permission_result['reason'] ?? '未知错误';
    $reason = '';
    
    if (!$can_download) {
        if (strpos($message, '需要会员权限') !== false) {
            $reason = 'restricted_content';
        } elseif (strpos($message, '配额已用完') !== false) {
            $reason = 'quota_exceeded';
        } elseif (strpos($message, '已过期') !== false) {
            $reason = 'membership_expired';
        } else {
            $reason = 'unknown';
        }
    }
    
    $permission_result = [
        'can_download' => $can_download,
        'message' => $message,
        'reason' => $reason
    ];
    
    // 获取用户会员状态信息
    $membership_info = getUserMembershipInfo($user['id']);
    
    // 构建响应数据
    $response = [
        'success' => true,
        'data' => [
            'can_download' => $permission_result['can_download'],
            'message' => $permission_result['message'],
            'wallpaper_id' => $wallpaper_id,
            'is_restricted' => $is_restricted,
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
    
    // 如果不能下载，添加额外信息
    if (!$permission_result['can_download']) {
        $response['data']['reason'] = $permission_result['reason'] ?? 'unknown';
        
        // 根据不同原因提供不同的建议
        switch ($permission_result['reason']) {
            case 'quota_exceeded':
                $response['data']['suggestion'] = '今日下载次数已用完，请明天再试或升级会员';
                break;
            case 'membership_expired':
                $response['data']['suggestion'] = '会员已过期，请重新购买会员码';
                break;
            case 'restricted_content':
                $response['data']['suggestion'] = '此内容需要会员权限，请升级会员';
                break;
            default:
                $response['data']['suggestion'] = '请检查您的会员状态或联系客服';
        }
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // 记录错误日志
    error_log("下载权限检查API错误: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '服务器内部错误，请稍后重试',
        'code' => 500,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
}

?>
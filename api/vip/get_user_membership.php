<?php
/**
 * 用户会员信息API
 * 获取当前用户的会员状态和信息
 * 
 * @author AI Assistant
 * @date 2024-01-27
 */

require_once 'membership_functions.php';
require_once __DIR__ . '/../auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// 处理OPTIONS请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 只允许GET请求
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false, 
        'message' => '只允许GET请求',
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
    
    // 获取用户会员信息
    $membership_info = getUserMembershipInfo($user['id']);
    
    // 计算会员状态
    $is_member = $membership_info['membership_type'] !== 'free';
    $is_expired = false;
    $days_remaining = null;
    
    if ($is_member && $membership_info['membership_expires_at']) {
        $expires_at = new DateTime($membership_info['membership_expires_at']);
        $now = new DateTime();
        
        if ($expires_at <= $now) {
            $is_expired = true;
            $days_remaining = 0;
        } else {
            $interval = $now->diff($expires_at);
            $days_remaining = $interval->days;
        }
    }
    
    // 获取今日下载统计
    $pdo = getMembershipDbConnection();
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_downloads,
            COUNT(CASE WHEN quota_consumed = 1 THEN 1 END) as restricted_downloads
        FROM user_download_logs 
        WHERE user_id = ? AND DATE(created_at) = CURDATE()
    ");
    $stmt->execute([$user['id']]);
    $download_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 获取历史下载统计
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_downloads,
            COUNT(CASE WHEN quota_consumed = 1 THEN 1 END) as restricted_downloads
        FROM user_download_logs 
        WHERE user_id = ?
    ");
    $stmt->execute([$user['id']]);
    $history_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 构建响应数据
    $response = [
        'success' => true,
        'message' => '获取用户会员信息成功',
        'data' => [
            'user_id' => $user['id'],
            'username' => $user['username'] ?? '',
            'membership' => [
                'type' => $membership_info['membership_type'],
                'type_display' => [
                    'free' => '免费用户',
                    'monthly' => '1元会员',
                    'permanent' => '永久会员'
                ][$membership_info['membership_type']] ?? '未知',
                'is_member' => $is_member,
                'is_expired' => $is_expired,
                'expires_at' => $membership_info['membership_expires_at'],
                'days_remaining' => $days_remaining
            ],
            'download_quota' => $membership_info['download_quota'] ?? 0,
            'download_stats' => [
                'today' => [
                    'total' => intval($download_stats['total_downloads']),
                    'restricted' => intval($download_stats['restricted_downloads']),
                    'unrestricted' => intval($download_stats['total_downloads']) - intval($download_stats['restricted_downloads'])
                ],
                'history' => [
                    'total' => intval($history_stats['total_downloads']),
                    'restricted' => intval($history_stats['restricted_downloads']),
                    'unrestricted' => intval($history_stats['total_downloads']) - intval($history_stats['restricted_downloads'])
                ]
            ],
            'permissions' => [
                'can_download_restricted' => $is_member && !$is_expired,
                'can_download_unrestricted' => true,
                'has_quota_limit' => $membership_info['membership_type'] !== 'permanent'
            ]
        ],
        'code' => 200,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // 记录错误日志
    error_log("用户会员信息API错误: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '服务器内部错误，请稍后重试',
        'code' => 500,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
}

?>
<?php
/**
 * 会员码统计信息API
 * 获取会员码的统计数据
 * 
 * @author AI Assistant
 * @date 2024-01-27
 */

require_once 'membership_functions.php';
// 引入管理员验证模块 - 已废弃SESSION验证
require_once '../admin_auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

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
    // 检查管理员权限 - 使用统一的Authorization头验证
    // 注意：已完全废弃SESSION验证，改为LOCAL和数据库管理员验证
    $adminUserId = checkAdminAuth();
    if (!$adminUserId) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => '权限不足，需要管理员权限',
            'code' => 403
        ]);
        exit;
    }
    
    // 获取统计信息
    $stats = getMembershipCodesStats();
    
    // 构建响应
    $response = [
        'success' => true,
        'message' => '获取统计信息成功',
        'data' => $stats,
        'code' => 200,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // 记录错误日志
    error_log("会员码统计API错误: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '服务器内部错误，请稍后重试',
        'code' => 500,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
}

?>
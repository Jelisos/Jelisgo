<?php
/**
 * 最近会员码列表API
 * 获取最近生成的会员码列表
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
    
    // 获取查询参数
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    $status = $_GET['status'] ?? 'all'; // all, unused, used, expired
    $membership_type = $_GET['membership_type'] ?? 'all'; // all, monthly, permanent
    
    // 验证参数
    if ($limit < 1 || $limit > 100) {
        $limit = 20;
    }
    
    if ($offset < 0) {
        $offset = 0;
    }
    
    $valid_statuses = ['all', 'unused', 'used', 'expired'];
    if (!in_array($status, $valid_statuses)) {
        $status = 'all';
    }
    
    $valid_types = ['all', 'monthly', 'permanent'];
    if (!in_array($membership_type, $valid_types)) {
        $membership_type = 'all';
    }
    
    // 获取会员码列表
    $codes = getRecentMembershipCodes($limit, $offset, $status, $membership_type);
    
    // 获取总数（用于分页）
    $total_count = getRecentMembershipCodesCount($status, $membership_type);
    
    // 构建响应
    $response = [
        'success' => true,
        'message' => '获取会员码列表成功',
        'data' => [
            'codes' => $codes,
            'pagination' => [
                'total' => $total_count,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $total_count
            ],
            'filters' => [
                'status' => $status,
                'membership_type' => $membership_type
            ]
        ],
        'code' => 200,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // 记录错误日志
    error_log("最近会员码列表API错误: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '服务器内部错误，请稍后重试',
        'code' => 500,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
}

?>
<?php
/**
 * 会员码兑换API
 * 处理用户会员码兑换请求
 * 
 * @author AI Assistant
 * @date 2024-01-27
 */

require_once 'membership_functions.php';
require_once 'auth_helper.php';

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
    
    $code = strtoupper(trim($input['code'] ?? ''));
    
    // 验证会员码格式
    if (empty($code)) {
        echo json_encode([
            'success' => false, 
            'message' => '会员码不能为空',
            'code' => 400
        ]);
        exit;
    }
    
    if (strlen($code) !== 12) {
        echo json_encode([
            'success' => false, 
            'message' => '会员码格式错误，必须是12位字符',
            'code' => 400
        ]);
        exit;
    }
    
    if (!preg_match('/^[A-Z0-9]{12}$/', $code)) {
        echo json_encode([
            'success' => false, 
            'message' => '会员码只能包含大写字母和数字',
            'code' => 400
        ]);
        exit;
    }
    
    // 调用会员码兑换函数
    $result = redeemMembershipCode($code, $user['id']);
    
    // 设置HTTP状态码
    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(400);
    }
    
    // 添加额外信息
    $result['code'] = $result['success'] ? 200 : 400;
    $result['timestamp'] = date('Y-m-d H:i:s');
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // 记录错误日志
    error_log("会员码兑换API错误: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '服务器内部错误，请稍后重试',
        'code' => 500,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
}

?>
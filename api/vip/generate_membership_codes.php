<?php
/**
 * 会员码生成API - 修复版本
 * 管理员生成会员码的接口
 * 
 * @author AI Assistant
 * @date 2024-06-24
 */

// 启用错误报告
error_reporting(E_ALL);
ini_set('display_errors', 0); // 生产环境关闭显示错误
ini_set('log_errors', 1);

// 引入管理员验证模块 - 已废弃SESSION验证
require_once '../admin_auth.php';

// 设置响应头
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
    ], JSON_UNESCAPED_UNICODE);
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
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 获取POST参数
    $membership_type = $_POST['membership_type'] ?? '';
    $count = intval($_POST['count'] ?? 1);

    // 验证参数
    if (empty($membership_type)) {
        throw new Exception('会员类型不能为空');
    }

    if (!in_array($membership_type, ['monthly', 'permanent'])) {
        throw new Exception('无效的会员类型');
    }

    if ($count < 1 || $count > 100) {
        throw new Exception('生成数量必须在1-100之间');
    }

    // 数据库连接
    $host = 'localhost';
    $dbname = 'wallpaper_db';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 生成会员码
    $batch_id = 'batch_' . date('YmdHis') . '_' . $adminUserId;
    $codes = [];
    
    $pdo->beginTransaction();
    
    for ($i = 0; $i < $count; $i++) {
        // 生成唯一码
        do {
            $code = strtoupper(substr(md5(uniqid(rand(), true)), 0, 12));
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM membership_codes WHERE code = ?");
            $stmt->execute([$code]);
            $exists = $stmt->fetchColumn() > 0;
        } while ($exists);
        
        // 设置过期时间（1年后）
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 year'));
        
        // 插入数据库
        $sql = "INSERT INTO membership_codes (code, membership_type, status, expires_at, generated_by, batch_id, created_at, updated_at) VALUES (?, ?, 'active', ?, ?, ?, NOW(), NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$code, $membership_type, $expires_at, $adminUserId, $batch_id]);
        
        $codes[] = [
            'code' => $code,
            'membership_type' => $membership_type,
            'expires_at' => $expires_at
        ];
    }
    
    $pdo->commit();
    
    // 记录操作日志
    $log_sql = "INSERT INTO admin_logs (admin_id, action, details, created_at) VALUES (?, 'generate_membership_codes', ?, NOW())";
    $log_stmt = $pdo->prepare($log_sql);
    $log_details = json_encode([
        'membership_type' => $membership_type,
        'count' => $count,
        'batch_id' => $batch_id
    ], JSON_UNESCAPED_UNICODE);
    $log_stmt->execute([$adminUserId, $log_details]);
    
    // 返回成功响应
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => '会员码生成成功',
        'codes' => $codes,
        'count' => count($codes),
        'batch_id' => $batch_id
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '数据库错误',
        'code' => 500
    ], JSON_UNESCAPED_UNICODE);
    
    error_log('Database error in generate_membership_codes: ' . $e->getMessage());
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'code' => 400
    ], JSON_UNESCAPED_UNICODE);
}
?>
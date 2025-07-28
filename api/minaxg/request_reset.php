<?php
// request_reset.php - 处理密码重置请求
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'db_connect.php';
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '只允许POST请求']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => '请输入邮箱地址']);
    exit;
}

if (!is_valid_email($email)) {
    echo json_encode(['success' => false, 'message' => '邮箱格式不正确']);
    exit;
}

try {
    $conn = get_db_connection();
    
    // 检查用户是否存在 - 增强版验证逻辑
    $stmt = $conn->prepare("SELECT id, username, email FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // 记录调试信息
        error_log("Password reset failed - Email not found: " . $email);
        
        // 检查是否有相似的邮箱（用于调试）
        $stmt_similar = $conn->prepare("SELECT email FROM users WHERE email LIKE ? LIMIT 5");
        $email_pattern = '%' . substr($email, 0, strpos($email, '@')) . '%';
        $stmt_similar->execute([$email_pattern]);
        $similar_emails = $stmt_similar->fetchAll(PDO::FETCH_COLUMN);
        
        if ($similar_emails) {
            error_log("Similar emails found: " . implode(', ', $similar_emails));
        }
        
        echo json_encode(['success' => false, 'message' => '该邮箱未注册']);
        exit;
    }
    
    // 记录成功找到用户的日志
    error_log("Password reset - User found: ID={$user['id']}, Username={$user['username']}, Email={$user['email']}");
    
    // 生成验证码
    $code = generate_verification_code();
    
    // 删除该邮箱的旧验证码
    $stmt = $conn->prepare("DELETE FROM password_reset_codes WHERE email = ?");
    $stmt->execute([$email]);
    
    // 插入新验证码
    $stmt = $conn->prepare("INSERT INTO password_reset_codes (email, code) VALUES (?, ?)");
    $stmt->execute([$email, $code]);
    
    // 发送邮件
    if (send_verification_email($email, $code)) {
        echo json_encode([
            'success' => true, 
            'message' => '验证码已发送到您的邮箱，请查收'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => '邮件发送失败，请稍后重试'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => '系统错误：' . $e->getMessage()
    ]);
}
?>
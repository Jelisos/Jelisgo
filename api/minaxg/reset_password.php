<?php
// reset_password.php - 重置密码
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

session_start();

// 检查会话验证
if (!isset($_SESSION['reset_verified']) || !$_SESSION['reset_verified']) {
    echo json_encode(['success' => false, 'message' => '请先验证邮箱']);
    exit;
}

// 检查会话是否过期（10分钟）
if (!isset($_SESSION['reset_time']) || (time() - $_SESSION['reset_time']) > 600) {
    session_destroy();
    echo json_encode(['success' => false, 'message' => '会话已过期，请重新验证']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$new_password = trim($input['password'] ?? '');
$confirm_password = trim($input['confirm_password'] ?? '');

if (empty($new_password) || empty($confirm_password)) {
    echo json_encode(['success' => false, 'message' => '请输入新密码']);
    exit;
}

if ($new_password !== $confirm_password) {
    echo json_encode(['success' => false, 'message' => '两次输入的密码不一致']);
    exit;
}

if (strlen($new_password) < 4) {
    echo json_encode(['success' => false, 'message' => '密码长度至少4位']);
    exit;
}

try {
    $conn = get_db_connection();
    $email = $_SESSION['reset_email'];
    
    // 更新用户密码
    $password_hash = hash_password($new_password);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->execute([$password_hash, $email]);
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => '用户不存在']);
        exit;
    }
    
    // 删除验证码记录
    $stmt = $conn->prepare("DELETE FROM password_reset_codes WHERE email = ?");
    $stmt->execute([$email]);
    
    // 清除会话
    session_destroy();
    
    echo json_encode([
        'success' => true, 
        'message' => '密码重置成功，请使用新密码登录'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => '系统错误：' . $e->getMessage()
    ]);
}
?>
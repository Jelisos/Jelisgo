<?php
// verify_code.php - 验证重置码
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'db_connect.php';
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '只允许POST请求']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');
$code = trim($input['code'] ?? '');

if (empty($email) || empty($code)) {
    echo json_encode(['success' => false, 'message' => '请输入邮箱和验证码']);
    exit;
}

try {
    $conn = get_db_connection();
    
    // 查询验证码
    $stmt = $conn->prepare("
        SELECT id, created_at 
        FROM password_reset_codes 
        WHERE email = ? AND code = ?
    ");
    $stmt->execute([$email, $code]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        echo json_encode(['success' => false, 'message' => '验证码错误']);
        exit;
    }
    
    // 检查验证码是否过期
    $created_time = strtotime($result['created_at']);
    $current_time = time();
    
    if (($current_time - $created_time) > CODE_EXPIRATION) {
        // 删除过期验证码
        $stmt = $conn->prepare("DELETE FROM password_reset_codes WHERE id = ?");
        $stmt->execute([$result['id']]);
        
        echo json_encode(['success' => false, 'message' => '验证码已过期，请重新获取']);
        exit;
    }
    
    // 验证成功，生成临时token
    session_start();
    $_SESSION['reset_email'] = $email;
    $_SESSION['reset_verified'] = true;
    $_SESSION['reset_time'] = time();
    
    echo json_encode([
        'success' => true, 
        'message' => '验证成功，可以设置新密码',
        'token' => session_id()
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => '系统错误：' . $e->getMessage()
    ]);
}
?>
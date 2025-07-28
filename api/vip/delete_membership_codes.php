<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '只允许POST请求']);
    exit;
}

// 引入管理员验证模块 - 已废弃SESSION验证
require_once '../admin_auth.php';

// 检查管理员权限 - 使用统一的Authorization头验证
// 注意：已完全废弃SESSION验证，改为LOCAL和数据库管理员验证
$adminUserId = checkAdminAuth();
if (!$adminUserId) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => '权限不足，需要管理员权限']);
    exit;
}

try {
    // 数据库连接
    $pdo = new PDO('mysql:host=localhost;dbname=wallpaper_db;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
    ]);
    
    // 获取请求数据
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['code_ids']) || !is_array($input['code_ids']) || empty($input['code_ids'])) {
        echo json_encode(['success' => false, 'message' => '请提供要删除的会员码ID']);
        exit;
    }
    
    $codeIds = array_map('intval', $input['code_ids']);
    $placeholders = str_repeat('?,', count($codeIds) - 1) . '?';
    
    // 开始事务
    $pdo->beginTransaction();
    
    try {
        // 先查询要删除的会员码信息（用于日志记录）
        $selectStmt = $pdo->prepare("SELECT id, code, membership_type, status FROM membership_codes WHERE id IN ($placeholders)");
        $selectStmt->execute($codeIds);
        $codesToDelete = $selectStmt->fetchAll();
        
        if (empty($codesToDelete)) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => '未找到要删除的会员码']);
            exit;
        }
        
        // 执行删除操作
        $deleteStmt = $pdo->prepare("DELETE FROM membership_codes WHERE id IN ($placeholders)");
        $deleteStmt->execute($codeIds);
        
        $deletedCount = $deleteStmt->rowCount();
        
        // 记录删除日志（可选）
        $logData = [
            'action' => 'batch_delete_codes',
            'admin_id' => $adminUserId,
            'deleted_count' => $deletedCount,
            'deleted_codes' => array_column($codesToDelete, 'code'),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // 这里可以添加日志记录到数据库或文件
        error_log('会员码批量删除: ' . json_encode($logData, JSON_UNESCAPED_UNICODE));
        
        // 提交事务
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => "成功删除 {$deletedCount} 个会员码",
            'deleted_count' => $deletedCount
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    error_log('删除会员码数据库错误: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '数据库操作失败']);
} catch (Exception $e) {
    error_log('删除会员码错误: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '操作失败，请稍后重试']);
}
?>
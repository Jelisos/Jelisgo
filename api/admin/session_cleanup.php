<?php
/**
 * Session清理API
 * 提供session清理和管理功能
 * 
 * 注意：由于已完全废弃SESSION验证机制，改为LOCAL和数据库管理员验证，
 * 此文件主要用于清理历史session数据，新的验证方式不再依赖session表。
 * 建议在确认无历史session数据需要保留后，可以考虑删除此文件。
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 处理OPTIONS请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 只允许POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => '只允许POST请求'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

require_once '../../config/database.php';
require_once '../../config/session_config.php';

try {
    // 获取请求数据
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    // 初始化session管理器
    $sessionManager = SessionManager::getInstance();
    
    // 获取数据库连接
    $conn = getDBConnection();
    
    $result = [];
    
    switch ($action) {
        case 'cleanup':
            $result = cleanupExpiredSessions($conn);
            break;
            
        case 'clear_all':
            $result = clearAllSessions($conn);
            break;
            
        case 'clear_user':
            $userId = $input['user_id'] ?? 0;
            $result = clearUserSessions($conn, $userId);
            break;
            
        case 'clear_ip':
            $ipAddress = $input['ip_address'] ?? '';
            $result = clearIPSessions($conn, $ipAddress);
            break;
            
        default:
            throw new Exception('无效的操作类型');
    }
    
    // 返回成功响应
    echo json_encode(array_merge([
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s')
    ], $result), JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // 返回错误响应
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
} finally {
    if (isset($conn)) {
        closeDBConnection($conn);
    }
}

/**
 * 清理过期的session
 */
function cleanupExpiredSessions($conn) {
    // 记录清理前的统计
    $beforeCount = getSessionCount($conn, 'expired');
    
    // 执行清理
    $stmt = $conn->prepare("DELETE FROM sessions WHERE expires_at <= NOW()");
    $stmt->execute();
    $cleanedCount = $stmt->affected_rows;
    
    // 记录日志
    logSessionAction('cleanup_expired', [
        'cleaned_count' => $cleanedCount,
        'before_count' => $beforeCount
    ]);
    
    return [
        'action' => 'cleanup',
        'cleaned_count' => $cleanedCount,
        'message' => "成功清理了 {$cleanedCount} 个过期session"
    ];
}

/**
 * 清空所有session
 */
function clearAllSessions($conn) {
    // 记录清理前的统计
    $beforeCount = getSessionCount($conn, 'all');
    
    // 执行清空
    $stmt = $conn->prepare("DELETE FROM sessions");
    $stmt->execute();
    $clearedCount = $stmt->affected_rows;
    
    // 记录日志
    logSessionAction('clear_all', [
        'cleared_count' => $clearedCount,
        'before_count' => $beforeCount
    ]);
    
    return [
        'action' => 'clear_all',
        'cleared_count' => $clearedCount,
        'message' => "成功清空了 {$clearedCount} 个session"
    ];
}

/**
 * 清理指定用户的所有session
 */
function clearUserSessions($conn, $userId) {
    if (empty($userId)) {
        throw new Exception('用户ID不能为空');
    }
    
    // 记录清理前的统计
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM sessions WHERE user_id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $beforeCount = $stmt->get_result()->fetch_assoc()['count'];
    
    // 执行清理
    $stmt = $conn->prepare("DELETE FROM sessions WHERE user_id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $clearedCount = $stmt->affected_rows;
    
    // 记录日志
    logSessionAction('clear_user', [
        'user_id' => $userId,
        'cleared_count' => $clearedCount,
        'before_count' => $beforeCount
    ]);
    
    return [
        'action' => 'clear_user',
        'user_id' => $userId,
        'cleared_count' => $clearedCount,
        'message' => "成功清理了用户 {$userId} 的 {$clearedCount} 个session"
    ];
}

/**
 * 清理指定IP地址的所有session
 */
function clearIPSessions($conn, $ipAddress) {
    if (empty($ipAddress)) {
        throw new Exception('IP地址不能为空');
    }
    
    // 记录清理前的统计
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM sessions WHERE ip_address = ?");
    $stmt->bind_param('s', $ipAddress);
    $stmt->execute();
    $beforeCount = $stmt->get_result()->fetch_assoc()['count'];
    
    // 执行清理
    $stmt = $conn->prepare("DELETE FROM sessions WHERE ip_address = ?");
    $stmt->bind_param('s', $ipAddress);
    $stmt->execute();
    $clearedCount = $stmt->affected_rows;
    
    // 记录日志
    logSessionAction('clear_ip', [
        'ip_address' => $ipAddress,
        'cleared_count' => $clearedCount,
        'before_count' => $beforeCount
    ]);
    
    return [
        'action' => 'clear_ip',
        'ip_address' => $ipAddress,
        'cleared_count' => $clearedCount,
        'message' => "成功清理了IP {$ipAddress} 的 {$clearedCount} 个session"
    ];
}

/**
 * 获取session数量
 */
function getSessionCount($conn, $type = 'all') {
    switch ($type) {
        case 'all':
            $sql = "SELECT COUNT(*) as count FROM sessions";
            break;
        case 'active':
            $sql = "SELECT COUNT(*) as count FROM sessions WHERE expires_at > NOW()";
            break;
        case 'expired':
            $sql = "SELECT COUNT(*) as count FROM sessions WHERE expires_at <= NOW()";
            break;
        default:
            $sql = "SELECT COUNT(*) as count FROM sessions";
    }
    
    $result = $conn->query($sql);
    return $result->fetch_assoc()['count'];
}

/**
 * 记录session操作日志
 */
function logSessionAction($action, $details = []) {
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'action' => $action,
        'details' => $details,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ];
    
    // 写入日志文件
    $logFile = __DIR__ . '/../../logs/session_admin.log';
    $logDir = dirname($logFile);
    
    // 确保日志目录存在
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logEntry = date('Y-m-d H:i:s') . ' - ' . json_encode($logData, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * 批量清理session（按条件）
 */
function batchCleanupSessions($conn, $conditions = []) {
    $whereClause = [];
    $params = [];
    $types = '';
    
    // 构建WHERE条件
    if (!empty($conditions['older_than_days'])) {
        $whereClause[] = "created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        $params[] = $conditions['older_than_days'];
        $types .= 'i';
    }
    
    if (!empty($conditions['inactive_days'])) {
        $whereClause[] = "updated_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        $params[] = $conditions['inactive_days'];
        $types .= 'i';
    }
    
    if (isset($conditions['user_id'])) {
        $whereClause[] = "user_id = ?";
        $params[] = $conditions['user_id'];
        $types .= 'i';
    }
    
    if (!empty($conditions['ip_pattern'])) {
        $whereClause[] = "ip_address LIKE ?";
        $params[] = $conditions['ip_pattern'];
        $types .= 's';
    }
    
    if (empty($whereClause)) {
        throw new Exception('必须指定至少一个清理条件');
    }
    
    $sql = "DELETE FROM sessions WHERE " . implode(' AND ', $whereClause);
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    
    return $stmt->affected_rows;
}
?>
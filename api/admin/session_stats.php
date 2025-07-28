<?php
/**
 * Session统计API
 * 提供session数据统计和列表查询功能
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

require_once '../../config/database.php';
require_once '../../config/session_config.php';

try {
    // 初始化session管理器
    $sessionManager = SessionManager::getInstance();
    
    // 获取数据库连接
    $conn = getDBConnection();
    
    // 获取session统计数据
    $stats = getSessionStats($conn);
    
    // 获取活跃session列表
    $sessions = getActiveSessions($conn);
    
    // 返回成功响应
    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'sessions' => $sessions,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    
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
 * 获取session统计数据
 */
function getSessionStats($conn) {
    $stats = [];
    
    // 总session数
    $result = $conn->query("SELECT COUNT(*) as total FROM sessions");
    $stats['total'] = $result->fetch_assoc()['total'];
    
    // 活跃session数（未过期）
    $stmt = $conn->prepare("SELECT COUNT(*) as active FROM sessions WHERE expires_at > NOW()");
    $stmt->execute();
    $stats['active'] = $stmt->get_result()->fetch_assoc()['active'];
    
    // 过期session数
    $stmt = $conn->prepare("SELECT COUNT(*) as expired FROM sessions WHERE expires_at <= NOW()");
    $stmt->execute();
    $stats['expired'] = $stmt->get_result()->fetch_assoc()['expired'];
    
    // 已登录用户session数（有user_id的）
    $stmt = $conn->prepare("SELECT COUNT(*) as logged_in FROM sessions WHERE user_id IS NOT NULL AND expires_at > NOW()");
    $stmt->execute();
    $stats['logged_in'] = $stmt->get_result()->fetch_assoc()['logged_in'];
    
    return $stats;
}

/**
 * 获取活跃session列表
 */
function getActiveSessions($conn) {
    $sessions = [];
    
    // 查询活跃session，包含用户信息
    $sql = "
        SELECT 
            s.id,
            s.session_id,
            s.user_id,
            s.ip_address,
            s.created_at,
            s.updated_at,
            s.expires_at,
            u.username,
            u.email
        FROM sessions s
        LEFT JOIN users u ON s.user_id = u.id
        WHERE s.expires_at > NOW()
        ORDER BY s.updated_at DESC
        LIMIT 100
    ";
    
    $result = $conn->query($sql);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $sessions[] = [
                'id' => $row['id'],
                'session_id' => $row['session_id'],
                'user_id' => $row['user_id'],
                'username' => $row['username'],
                'email' => $row['email'],
                'ip_address' => $row['ip_address'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
                'expires_at' => $row['expires_at'],
                'is_logged_in' => !empty($row['user_id'])
            ];
        }
    }
    
    return $sessions;
}

/**
 * 获取session详细信息
 */
function getSessionDetails($conn, $sessionId) {
    $stmt = $conn->prepare("
        SELECT 
            s.*,
            u.username,
            u.email,
            u.created_at as user_created_at
        FROM sessions s
        LEFT JOIN users u ON s.user_id = u.id
        WHERE s.session_id = ?
    ");
    
    $stmt->bind_param('s', $sessionId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return [
            'session' => [
                'id' => $row['id'],
                'session_id' => $row['session_id'],
                'user_id' => $row['user_id'],
                'ip_address' => $row['ip_address'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
                'expires_at' => $row['expires_at'],
                'session_data' => $row['session_data']
            ],
            'user' => $row['user_id'] ? [
                'username' => $row['username'],
                'email' => $row['email'],
                'created_at' => $row['user_created_at']
            ] : null
        ];
    }
    
    return null;
}

/**
 * 获取用户的所有session
 */
function getUserSessions($conn, $userId) {
    $stmt = $conn->prepare("
        SELECT 
            session_id,
            ip_address,
            created_at,
            updated_at,
            expires_at,
            CASE WHEN expires_at > NOW() THEN 'active' ELSE 'expired' END as status
        FROM sessions 
        WHERE user_id = ?
        ORDER BY updated_at DESC
    ");
    
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sessions = [];
    while ($row = $result->fetch_assoc()) {
        $sessions[] = $row;
    }
    
    return $sessions;
}

/**
 * 获取IP地址的session历史
 */
function getIPSessions($conn, $ipAddress) {
    $stmt = $conn->prepare("
        SELECT 
            s.session_id,
            s.user_id,
            s.created_at,
            s.updated_at,
            s.expires_at,
            u.username,
            CASE WHEN s.expires_at > NOW() THEN 'active' ELSE 'expired' END as status
        FROM sessions s
        LEFT JOIN users u ON s.user_id = u.id
        WHERE s.ip_address = ?
        ORDER BY s.updated_at DESC
        LIMIT 50
    ");
    
    $stmt->bind_param('s', $ipAddress);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sessions = [];
    while ($row = $result->fetch_assoc()) {
        $sessions[] = $row;
    }
    
    return $sessions;
}
?>
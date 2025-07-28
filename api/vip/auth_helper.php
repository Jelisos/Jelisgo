<?php
/**
 * 会员系统认证辅助函数
 * 提供用户认证相关的辅助函数
 * 
 * @author AI Assistant
 * @date 2024-01-27
 */

session_start();
require_once __DIR__ . '/../../config/database.php';

/**
 * 获取当前登录用户信息
 * @return array|null 用户信息数组或null（未登录）
 */
function getCurrentUser() {
    $userId = null;
    
    // 1. 尝试从Authorization头获取用户ID
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $auth_parts = explode(' ', $headers['Authorization']);
        if (count($auth_parts) == 2 && $auth_parts[0] == 'Bearer') {
            $userId = intval($auth_parts[1]);
        }
    }
    
    // 2. 如果Authorization头中没有，则检查session
    if (!$userId && isset($_SESSION['user_id'])) {
        $userId = intval($_SESSION['user_id']);
    }
    
    // 如果都没有找到用户ID，返回null
    if (!$userId) {
        return null;
    }
    
    try {
        $conn = getDBConnection();
        if (!$conn) {
            return null;
        }
        
        // 查询用户信息，包括会员相关字段
        $stmt = $conn->prepare("
            SELECT 
                id, 
                username, 
                email, 
                is_admin,
                membership_type,
                membership_expires_at,
                download_quota,
                quota_reset_date,
                created_at
            FROM users 
            WHERE id = ?
        ");
        
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // Session中有user_id但数据库中找不到用户，清除session
            session_unset();
            session_destroy();
            $stmt->close();
            closeDBConnection($conn);
            return null;
        }
        
        $user = $result->fetch_assoc();
        
        // 转换数据类型
        $user['id'] = intval($user['id']);
        $user['is_admin'] = (bool)$user['is_admin'];
        $user['download_quota'] = intval($user['download_quota'] ?? 0);
        
        $stmt->close();
        closeDBConnection($conn);
        
        return $user;
        
    } catch (Exception $e) {
        error_log("getCurrentUser错误: " . $e->getMessage());
        return null;
    }
}

/**
 * 检查用户是否为管理员
 * @return bool
 */
function isAdmin() {
    $user = getCurrentUser();
    return $user && $user['is_admin'];
}

/**
 * 检查用户是否已登录
 * @return bool
 */
function isLoggedIn() {
    return getCurrentUser() !== null;
}

/**
 * 获取当前用户ID
 * @return int|null
 */
if (!function_exists('getCurrentUserId')) {
    function getCurrentUserId() {
        $user = getCurrentUser();
        return $user ? $user['id'] : null;
    }
}

/**
 * 检查用户权限
 * @param string $permission 权限类型 ('admin', 'user')
 * @return bool
 */
function hasPermission($permission) {
    $user = getCurrentUser();
    
    if (!$user) {
        return false;
    }
    
    switch ($permission) {
        case 'admin':
            return $user['is_admin'];
        case 'user':
            return true; // 已登录用户都有基本权限
        default:
            return false;
    }
}

/**
 * 要求用户登录，如果未登录则返回错误响应
 */
function requireLogin() {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => '请先登录',
            'code' => 401
        ]);
        exit;
    }
}

/**
 * 要求管理员权限，如果不是管理员则返回错误响应
 */
function requireAdmin() {
    requireLogin();
    
    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => '权限不足，需要管理员权限',
            'code' => 403
        ]);
        exit;
    }
}

?>
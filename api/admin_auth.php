<?php
/**
 * 管理员权限验证中间件
 * 位置: api/admin_auth.php
 * 依赖: utils.php, config/database.php
 * 更新: 2025-01-03 添加verify和log接口支持
 */

require_once 'utils.php';
require_once __DIR__ . '/../config/database.php';

// 初始化PDO连接
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PWD,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
        ]
    );
} catch (PDOException $e) {
    error_log("数据库连接失败: " . $e->getMessage());
    sendResponse(500, '数据库连接失败');
    exit;
}

// 只有在直接访问此文件时才处理HTTP请求
// 如果是被其他文件包含，则不执行请求处理逻辑
if (basename($_SERVER['SCRIPT_NAME']) === 'admin_auth.php') {
    // 处理HTTP请求
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'verify':
                handleVerifyAdmin();
                break;
            default:
                sendResponse(400, '无效的操作');
                break;
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'log':
                handleLogAdminAction();
                break;
            default:
                sendResponse(400, '无效的操作');
                break;
        }
    } else {
        sendResponse(405, '不支持的请求方法');
    }
}

/**
 * 处理管理员验证请求
 * 验证用户是否具有管理员权限
 * 仅通过Authorization头和数据库查询进行验证
 */
function handleVerifyAdmin() {
    try {
        // 从Authorization头获取用户ID（支持多种方式）
        $authHeader = '';
        
        // 方法1: 使用getallheaders()
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            $authHeader = $headers['Authorization'] ?? '';
        }
        
        // 方法2: 从$_SERVER获取
        if (empty($authHeader)) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        }
        
        // 方法3: 从Apache环境变量获取
        if (empty($authHeader) && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }
        
        if (strpos($authHeader, 'Bearer ') === 0) {
            $userId = substr($authHeader, 7);
        } else {
            sendResponse(401, '缺少Authorization头信息');
            return;
        }
        
        // 验证用户ID格式
        if (!is_numeric($userId) || $userId <= 0) {
            sendResponse(400, '无效的用户ID');
            return;
        }
        
        // 查询数据库验证管理员权限
        global $pdo;
        $stmt = $pdo->prepare("SELECT id, username, email, avatar, is_admin FROM users WHERE id = ? AND is_admin = 1");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            sendResponse(200, '管理员验证成功', [
                'success' => true,
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'avatar' => $user['avatar'],
                'is_admin' => true
            ]);
        } else {
            sendResponse(403, '用户不存在或没有管理员权限');
        }
        
    } catch (Exception $e) {
        error_log("管理员验证失败: " . $e->getMessage());
        sendResponse(500, '验证过程中发生错误');
    }
}

/**
 * 处理管理员操作日志记录请求
 * 移除Session验证，使用Authorization头验证
 */
function handleLogAdminAction() {
    try {
        // 从Authorization头获取用户ID（支持多种方式）
        $authHeader = '';
        
        // 方法1: 使用getallheaders()
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            $authHeader = $headers['Authorization'] ?? '';
        }
        
        // 方法2: 从$_SERVER获取
        if (empty($authHeader)) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        }
        
        // 方法3: 从Apache环境变量获取
        if (empty($authHeader) && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }
        
        if (strpos($authHeader, 'Bearer ') === 0) {
            $userId = substr($authHeader, 7);
        } else {
            sendResponse(401, '缺少Authorization头信息', ['success' => false]);
            return;
        }
        
        // 验证用户ID格式
        if (!is_numeric($userId) || $userId <= 0) {
            sendResponse(401, '无效的用户ID', ['success' => false]);
            return;
        }
        
        // 验证用户是否为管理员
        global $pdo;
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND is_admin = 1");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            sendResponse(403, '权限不足，需要管理员权限', ['success' => false]);
            return;
        }
        
        // 获取POST数据
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            sendResponse(400, '无效的请求数据', ['success' => false]);
            return;
        }
        
        $action = $input['action'] ?? '';
        $details = $input['details'] ?? '';
        $page = $input['page'] ?? '';
        
        if (empty($action)) {
            sendResponse(400, '操作类型不能为空', ['success' => false]);
            return;
        }
        
        // 记录日志
        $fullDetails = $details;
        if ($page) {
            $fullDetails .= " [页面: {$page}]";
        }
        
        logAdminAction($userId, $action, $fullDetails);
        
        sendResponse(200, '日志记录成功', ['success' => true]);
        
    } catch (Exception $e) {
        sendResponse(500, '日志记录失败: ' . $e->getMessage(), ['success' => false]);
    }
}

/**
 * 检查管理员权限
 * @return int 返回管理员用户ID
 * @throws Exception 权限不足时抛出异常
 * 注意: 此函数已弃用Session验证，仅保留用于向后兼容
 * 建议使用handleVerifyAdmin()进行新的验证逻辑
 */
function checkAdminAuth() {
    // 从Authorization头获取用户ID（支持多种方式）
    $authHeader = '';
    
    // 方法1: 使用getallheaders()
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
    }
    
    // 方法2: 从$_SERVER获取
    if (empty($authHeader)) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    }
    
    // 方法3: 从Apache环境变量获取
    if (empty($authHeader) && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    }
    
    if (strpos($authHeader, 'Bearer ') === 0) {
        $userId = substr($authHeader, 7);
    } else {
        http_response_code(401);
        sendResponse(401, '缺少Authorization头信息');
        exit;
    }
    
    // 验证用户ID格式
    if (!is_numeric($userId) || $userId <= 0) {
        http_response_code(401);
        sendResponse(401, '无效的用户ID');
        exit;
    }
    
    // 直接查询数据库验证管理员权限
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND is_admin = 1");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            http_response_code(403);
            sendResponse(403, '权限不足，需要管理员权限');
            exit;
        }
        
        return $userId;
        
    } catch (Exception $e) {
        error_log("管理员权限检查失败: " . $e->getMessage());
        http_response_code(500);
        sendResponse(500, '权限验证过程中发生错误');
        exit;
    }
}

/**
 * 记录管理员操作日志
 * @param int $admin_id 管理员ID
 * @param string $action 操作类型
 * @param string $details 操作详情
 */
function logAdminAction($admin_id, $action, $details = '') {
    $conn = null;
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PWD, DB_NAME);
        $conn->set_charset("utf8mb4");
        
        if ($conn->connect_error) {
            sendDebugLog('管理员日志记录失败 - 数据库连接错误:', ['error' => $conn->connect_error], 'admin_log_error');
            return;
        }
        
        // 检查admin_logs表是否存在，不存在则创建
        $check_table = $conn->query("SHOW TABLES LIKE 'admin_logs'");
        if ($check_table->num_rows == 0) {
            $create_sql = "
                CREATE TABLE admin_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    admin_id INT NOT NULL,
                    action VARCHAR(100) NOT NULL,
                    details TEXT,
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_admin_id (admin_id),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            
            if (!$conn->query($create_sql)) {
                sendDebugLog('创建admin_logs表失败:', ['error' => $conn->error], 'admin_log_error');
                return;
            }
        }
        
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $stmt = $conn->prepare("
            INSERT INTO admin_logs (admin_id, action, details, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        if ($stmt) {
            $stmt->bind_param('issss', $admin_id, $action, $details, $ip_address, $user_agent);
            $stmt->execute();
            $stmt->close();
        }
        
    } catch (Exception $e) {
        sendDebugLog('管理员日志记录异常:', ['error' => $e->getMessage()], 'admin_log_error');
    } finally {
        if ($conn) {
            $conn->close();
        }
    }
}

/**
 * 获取管理员操作日志
 * @param int $limit 限制条数
 * @param int $offset 偏移量
 * @return array 日志列表
 */
function getAdminLogs($limit = 50, $offset = 0) {
    $conn = null;
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PWD, DB_NAME);
        $conn->set_charset("utf8mb4");
        
        if ($conn->connect_error) {
            return [];
        }
        
        $stmt = $conn->prepare("
            SELECT al.*, u.username as admin_name
            FROM admin_logs al
            LEFT JOIN users u ON al.admin_id = u.id
            ORDER BY al.created_at DESC
            LIMIT ? OFFSET ?
        ");
        
        if ($stmt) {
            $stmt->bind_param('ii', $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();
            $logs = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $logs;
        }
        
        return [];
        
    } catch (Exception $e) {
        sendDebugLog('获取管理员日志异常:', ['error' => $e->getMessage()], 'admin_log_error');
        return [];
    } finally {
        if ($conn) {
            $conn->close();
        }
    }
}
?>
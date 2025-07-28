<?php
/**
 * 统一用户认证API - 根据登录模块逻辑文档重构
 * @author AI Assistant
 * @date 2025-01-27
 * @description 实现标准化的用户认证接口，支持用户名或邮箱登录
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 防止缓存API响应
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// 引入必要的配置文件
require_once __DIR__ . '/../config/database.php';

// 禁用自动session初始化，手动控制
define('DISABLE_AUTO_SESSION_INIT', true);
require_once __DIR__ . '/../config/session_config.php';

require_once __DIR__ . '/email_validator.php';
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/login_logger.php';

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 手动启动简单的文件session（避免数据库session的复杂性）
if (session_status() === PHP_SESSION_NONE) {
    // 设置基本session配置
    ini_set('session.gc_maxlifetime', 86400);
    ini_set('session.cookie_lifetime', 86400);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_path', '/');
    session_start();
    error_log("[AUTH_UNIFIED] 手动启动文件session，Session ID: " . session_id());
}

// sendResponse函数已移至utils.php中，避免重复定义

/**
 * 验证用户名或邮箱格式
 * @param string $input 输入的用户名或邮箱
 * @return array ['isEmail' => bool, 'isValid' => bool]
 */
function validateUsernameOrEmail($input) {
    $input = trim($input);
    $isEmail = filter_var($input, FILTER_VALIDATE_EMAIL);
    
    if ($isEmail) {
        return ['isEmail' => true, 'isValid' => true];
    }
    
    // 用户名验证：3-20个字符，允许字母、数字、下划线
    $isValidUsername = preg_match('/^[a-zA-Z0-9_]{3,20}$/', $input);
    
    return ['isEmail' => false, 'isValid' => $isValidUsername];
}

/**
 * 处理用户登录
 * @param array $data 登录数据
 */
function handleLogin($data) {
    // 验证必要字段
    if (!isset($data['username']) || !isset($data['password'])) {
        sendResponse(400, '用户名/邮箱和密码均为必填');
    }
    
    $username = trim($data['username']);
    $password = $data['password'];
    
    // 参数校验
    if (empty($username) || empty($password)) {
        sendResponse(400, '用户名/邮箱和密码不能为空');
    }
    
    // 验证用户名或邮箱格式
    $validation = validateUsernameOrEmail($username);
    if (!$validation['isValid']) {
        sendResponse(400, '用户名或邮箱格式不正确');
    }
    
    // 连接数据库
    $conn = getDBConnection();
    if (!$conn) {
        sendResponse(500, '数据库连接失败');
    }
    
    try {
        // 根据输入类型查询用户
        if ($validation['isEmail']) {
            $stmt = $conn->prepare('SELECT id, username, email, password, is_admin FROM users WHERE email = ? LIMIT 1');
        } else {
            $stmt = $conn->prepare('SELECT id, username, email, password, is_admin FROM users WHERE username = ? LIMIT 1');
        }
        
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if (!$user) {
            // 记录登录失败 - 用户不存在
            $logger = LoginLogger::getInstance();
            $logger->logLoginFailure($username, $_SERVER['REMOTE_ADDR'] ?? 'unknown', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown', '用户不存在');
            sendResponse(401, '用户名或密码错误');
        }
        
        // 验证密码
        if (!password_verify($password, $user['password'])) {
            // 记录登录失败 - 密码错误
            $logger = LoginLogger::getInstance();
            $logger->logLoginFailure($username, $_SERVER['REMOTE_ADDR'] ?? 'unknown', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown', '密码错误');
            sendResponse(401, '用户名或密码错误');
        }
        
        // 登录成功，直接设置文件session数据
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['is_admin'] = (bool)$user['is_admin'];
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        // 强制写入session数据
        session_write_close();
        session_start();
        
        error_log("[AUTH_UNIFIED] 登录成功后Session数据: " . json_encode($_SESSION));
        
        // 记录登录成功到独立的登录记录表
        $logger = LoginLogger::getInstance();
        $logger->logLoginSuccess(
            $user['id'], 
            session_id(), 
            $_SERVER['REMOTE_ADDR'] ?? 'unknown', 
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'password'
        );
        
        // 记录登录日志
        error_log("[AUTH_UNIFIED] 用户 {$user['id']} 登录成功，Session ID: " . session_id());
        
        // 返回用户信息
        $response = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'is_admin' => (bool)$user['is_admin']
        ];
        
        sendResponse(200, '登录成功', $response);
        
    } catch (Exception $e) {
        error_log("[AUTH_UNIFIED] 登录异常: " . $e->getMessage());
        sendResponse(500, '登录处理异常');
    } finally {
        if (isset($stmt)) {
            $stmt->close();
        }
        closeDBConnection($conn);
    }
}

/**
 * 生成唯一的数字用户名
 * @param mysqli $conn 数据库连接
 * @return string 唯一的数字用户名
 */
function generateUniqueNumericUsername($conn) {
    do {
        // 生成8位随机数字用户名
        $username = str_pad(mt_rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
        
        // 检查是否已存在
        $stmt = $conn->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        
    } while ($exists);
    
    return $username;
}

/**
 * 处理用户注册
 * @param array $data 注册数据
 */
function handleRegister($data) {
    // 验证必要字段
    if (!isset($data['email']) || !isset($data['password'])) {
        sendResponse(400, '邮箱和密码均为必填');
    }
    
    $email = trim($data['email']);
    $password = $data['password'];
    $humanVerification = $data['human_verification'] ?? false;
    
    // 验证真人验证
    if (!$humanVerification) {
        sendResponse(400, '请确认您是真人');
    }
    
    // 验证邮箱格式
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendResponse(400, '邮箱格式不正确');
    }
    
    // 验证邮箱域名有效性
    if (!isEmailDomainValid($email)) {
        sendResponse(400, '该邮箱域名不存在或未配置邮件服务，请检查后重试');
    }
    
    // 验证密码强度（修改为至少4位）
    if (strlen($password) < 4) {
        sendResponse(400, '密码至少4位');
    }
    
    // 连接数据库
    $conn = getDBConnection();
    if (!$conn) {
        sendResponse(500, '数据库连接失败');
    }
    
    try {
        // 检查邮箱是否已存在
        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            sendResponse(400, '该邮箱已被注册');
        }
        
        $stmt->close();
        
        // 生成唯一的数字用户名
        $username = generateUniqueNumericUsername($conn);
        
        // 密码加密
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // 插入新用户
        $stmt = $conn->prepare('INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())');
        $stmt->bind_param('sss', $username, $email, $hashedPassword);
        
        if ($stmt->execute()) {
            $userId = $conn->insert_id;
            
            // 记录注册日志
            error_log("[AUTH_UNIFIED] 新用户注册成功，ID: {$userId}, 用户名: {$username}");
            
            $response = [
                'id' => $userId,
                'username' => $username,
                'email' => $email
            ];
            
            sendResponse(200, '注册成功', $response);
        } else {
            sendResponse(500, '注册失败');
        }
        
    } catch (Exception $e) {
        error_log("[AUTH_UNIFIED] 注册异常: " . $e->getMessage());
        sendResponse(500, '注册处理异常');
    } finally {
        if (isset($stmt)) {
            $stmt->close();
        }
        closeDBConnection($conn);
    }
}

/**
 * 获取用户信息
 */
function handleGetUserInfo() {
    // 调试信息：记录Session状态
    error_log("[AUTH_UNIFIED] getUserInfo - Session状态: " . session_status() . ", Session ID: " . session_id());
    error_log("[AUTH_UNIFIED] getUserInfo - Session数据: " . json_encode($_SESSION));
    error_log("[AUTH_UNIFIED] getUserInfo - Cookie: " . json_encode($_COOKIE));
    
    $userId = null;
    
    // 1. 尝试从Authorization头获取用户ID
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    if (isset($headers['Authorization'])) {
        $auth_parts = explode(' ', $headers['Authorization']);
        if (count($auth_parts) == 2 && $auth_parts[0] == 'Bearer') {
            $userId = intval($auth_parts[1]);
        }
    }
    
    // 2. 如果Authorization头中没有，则检查session
    if (!$userId) {
        // 确保Session已启动
        if (session_status() !== PHP_SESSION_ACTIVE) {
            error_log("[AUTH_UNIFIED] getUserInfo - Session未激活，尝试启动");
            session_start();
        }
        
        error_log("[AUTH_UNIFIED] getUserInfo - 检查Session数据: " . json_encode($_SESSION));
        
        // 直接检查Session中的用户ID
        if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
            $userId = intval($_SESSION['user_id']);
            error_log("[AUTH_UNIFIED] getUserInfo - 从Session获取用户ID: {$userId}");
        } else {
            error_log("[AUTH_UNIFIED] getUserInfo - Session中未找到有效的用户ID");
        }
    }
    
    // 如果都没有找到用户ID
    if (!$userId) {
        error_log("[AUTH_UNIFIED] getUserInfo - 未找到有效的用户ID，返回401");
        sendResponse(401, '未登录或会话已过期');
        return;
    }
    error_log("[AUTH_UNIFIED] getUserInfo - 准备查询用户ID: {$userId}");
    
    // 连接数据库
    $conn = getDBConnection();
    if (!$conn) {
        error_log("[AUTH_UNIFIED] getUserInfo - 数据库连接失败");
        sendResponse(500, '数据库连接失败');
        return;
    }
    
    try {
        // 查询用户信息，包含会员信息
        // 优先获取永久会员，其次是最新的有效会员
        $stmt = $conn->prepare('
            SELECT 
                u.id, 
                u.username, 
                u.email, 
                u.avatar, 
                u.is_admin, 
                u.created_at,
                COALESCE(
                    (SELECT "permanent" FROM membership_codes 
                     WHERE used_by_user_id = u.id 
                       AND status = "used" 
                       AND membership_type = "permanent" 
                       AND (expires_at IS NULL OR expires_at > NOW()) 
                     LIMIT 1),
                    (SELECT membership_type FROM membership_codes 
                     WHERE used_by_user_id = u.id 
                       AND status = "used" 
                       AND (expires_at IS NULL OR expires_at > NOW()) 
                     ORDER BY expires_at DESC 
                     LIMIT 1),
                    "free"
                ) as membership_type,
                (SELECT expires_at FROM membership_codes 
                 WHERE used_by_user_id = u.id 
                   AND status = "used" 
                   AND (expires_at IS NULL OR expires_at > NOW()) 
                 ORDER BY 
                   CASE WHEN membership_type = "permanent" THEN 0 ELSE 1 END,
                   expires_at DESC 
                 LIMIT 1) as membership_expires_at
            FROM users u 
            WHERE u.id = ? 
            LIMIT 1
        ');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // Session中有user_id但数据库中找不到用户，清除session
            error_log("[AUTH_UNIFIED] getUserInfo - 用户ID {$userId} 在数据库中不存在，清除Session");
            $_SESSION = array();
            session_destroy();
            sendResponse(401, '用户不存在或会话无效');
            return;
        }
        
        $user = $result->fetch_assoc();
        error_log("[AUTH_UNIFIED] getUserInfo - 成功获取用户信息: {$user['username']}, 会员类型: {$user['membership_type']}");
        
        // 更新最后活动时间
        $_SESSION['last_activity'] = time();
        
        // 确保session数据被保存
        session_write_close();
        session_start();
        
        error_log("[AUTH_UNIFIED] getUserInfo - 更新活动时间后Session数据: " . json_encode($_SESSION));
        
        sendResponse(200, '获取成功', $user);
        
    } catch (Exception $e) {
        error_log("[AUTH_UNIFIED] 获取用户信息异常: " . $e->getMessage());
        sendResponse(500, '获取用户信息失败');
    } finally {
        if (isset($stmt)) {
            $stmt->close();
        }
        closeDBConnection($conn);
    }
}

/**
 * 处理用户登出
 */
function handleLogout() {
    // 调试信息：记录Session状态
    error_log("[AUTH_UNIFIED] 开始处理登出请求，Session ID: " . session_id());
    error_log("[AUTH_UNIFIED] 当前Session数据: " . json_encode($_SESSION));
    error_log("[AUTH_UNIFIED] 当前Cookie数据: " . json_encode($_COOKIE));
    error_log("[AUTH_UNIFIED] 当前请求方法: " . $_SERVER['REQUEST_METHOD']);
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    error_log("[AUTH_UNIFIED] 当前请求头: " . json_encode($headers));
    
    // 检查是否已登录，但不要立即返回错误
    if (!isset($_SESSION['user_id'])) {
        error_log("[AUTH_UNIFIED] 登出警告：未找到user_id，可能已经登出");
        // 仍然继续执行登出流程，确保清理所有可能的会话数据
    } else {
        // 记录登出日志
        $userId = $_SESSION['user_id'];
        error_log("[AUTH_UNIFIED] 用户 {$userId} 登出");
        
        // 尝试记录登出日志到数据库
        try {
            require_once __DIR__ . '/login_logger.php';
            $logger = LoginLogger::getInstance();
            $logger->logLogout(
                $userId,
                session_id(),
                $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            error_log("[AUTH_UNIFIED] 已记录用户 {$userId} 登出日志");
        } catch (Exception $e) {
            error_log("[AUTH_UNIFIED] 记录登出日志失败: " . $e->getMessage());
            // 继续处理登出，不因日志记录失败而中断
        }
    }
    
    // 获取当前session_id用于日志记录
    $oldSessionId = session_id();
    
    // 直接销毁文件session
    error_log("[AUTH_UNIFIED] 开始销毁Session: {$oldSessionId}");
    
    // 清除所有会话数据
    $_SESSION = array();
    
    // 确保cookie被删除
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
        error_log("[AUTH_UNIFIED] 已删除session cookie");
    }
    
    // 销毁session
    session_destroy();
    error_log("[AUTH_UNIFIED] Session销毁完成");
    
    // 验证session是否已被清除
    error_log("[AUTH_UNIFIED] 登出后Session状态: " . (session_id() ? "仍有Session ID: " . session_id() : "无Session ID"));
    error_log("[AUTH_UNIFIED] 登出后Session数据: " . json_encode($_SESSION));
    
    // 始终返回成功，因为我们已尽力清理会话
    sendResponse(200, '登出成功');
}

/**
 * 处理用户身份验证 - 直接查询数据库
 */
function handleValidateUser() {
    error_log("[AUTH_UNIFIED] handleValidateUser - 开始验证用户身份");
    
    // 获取用户ID
    $userId = $_GET['user_id'] ?? null;
    
    // 也支持从Authorization头获取
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!$userId && preg_match('/Bearer\s+(\d+)/', $authHeader, $matches)) {
        $userId = intval($matches[1]);
    }
    
    if (!$userId) {
        error_log("[AUTH_UNIFIED] handleValidateUser - 缺少用户ID");
        sendResponse(400, '缺少用户ID参数');
    }
    
    $userId = intval($userId);
    error_log("[AUTH_UNIFIED] handleValidateUser - 验证用户ID: {$userId}");
    
    // 连接数据库
    $conn = getDBConnection();
    if (!$conn) {
        error_log("[AUTH_UNIFIED] handleValidateUser - 数据库连接失败");
        sendResponse(500, '数据库连接失败');
    }
    
    try {
        // 查询用户信息，确保用户存在
        $stmt = $conn->prepare("SELECT id, username, email, is_admin FROM users WHERE id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        if (!$user) {
            error_log("[AUTH_UNIFIED] handleValidateUser - 用户ID {$userId} 不存在");
            sendResponse(401, '用户不存在');
        }
        
        error_log("[AUTH_UNIFIED] handleValidateUser - 用户验证成功: {$user['username']}");
        
        // 返回用户信息（不包含敏感信息）
        $userData = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'is_admin' => (bool)$user['is_admin']
        ];
        
        sendResponse(200, '用户验证成功', $userData);
        
    } catch (Exception $e) {
        error_log("[AUTH_UNIFIED] handleValidateUser - 验证异常: " . $e->getMessage());
        sendResponse(500, '用户验证失败');
    } finally {
        closeDBConnection($conn);
    }
}

/**
 * 处理修改密码
 * @param array $data 修改密码数据
 */
function handleChangePassword($data) {
    // 检查是否已登录
    if (!isset($_SESSION['user_id'])) {
        sendResponse(401, '未登录或会话已过期');
    }
    
    // 验证必要字段
    if (!isset($data['currentPassword']) || !isset($data['newPassword'])) {
        sendResponse(400, '当前密码和新密码均为必填');
    }
    
    $currentPassword = $data['currentPassword'];
    $newPassword = $data['newPassword'];
    $userId = $_SESSION['user_id'];
    
    // 验证新密码强度
    if (strlen($newPassword) < 4) {
        sendResponse(400, '新密码必须至少4位');
    }
    
    // 连接数据库
    $conn = getDBConnection();
    if (!$conn) {
        sendResponse(500, '数据库连接失败');
    }
    
    try {
        // 获取用户当前密码
        $stmt = $conn->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            sendResponse(401, '用户不存在');
        }
        
        $user = $result->fetch_assoc();
        $stmt->close();
        
        // 验证当前密码
        if (!password_verify($currentPassword, $user['password'])) {
            sendResponse(401, '当前密码不正确');
        }
        
        // 加密新密码
        $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // 更新密码
        $stmt = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
        $stmt->bind_param('si', $hashedNewPassword, $userId);
        
        if ($stmt->execute()) {
            // 密码修改成功，记录日志
            error_log("[AUTH_UNIFIED] 用户 {$userId} 修改密码成功");
            
            // 销毁当前session，要求重新登录
            global $sessionManager;
            $sessionManager->destroySession();
            
            sendResponse(200, '密码修改成功，请重新登录');
        } else {
            sendResponse(500, '密码修改失败');
        }
        
    } catch (Exception $e) {
        error_log("[AUTH_UNIFIED] 修改密码异常: " . $e->getMessage());
        sendResponse(500, '修改密码处理异常');
    } finally {
        if (isset($stmt)) {
            $stmt->close();
        }
        closeDBConnection($conn);
    }
}

/**
 * 处理更新个人信息
 * @param array $data 更新数据
 */
function handleUpdateProfile($data) {
    // 检查是否已登录
    if (!isset($_SESSION['user_id'])) {
        sendResponse(401, '未登录或会话已过期');
    }
    
    // 验证必要字段
    if (!isset($data['username']) || empty(trim($data['username']))) {
        sendResponse(400, '用户名不能为空');
    }
    
    $newUsername = trim($data['username']);
    $userId = $_SESSION['user_id'];
    
    // 验证用户名格式
    if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $newUsername)) {
        sendResponse(400, '用户名格式不正确，只能包含字母、数字、下划线，长度3-20位');
    }
    
    // 连接数据库
    $conn = getDBConnection();
    if (!$conn) {
        sendResponse(500, '数据库连接失败');
    }
    
    try {
        // 检查用户名是否已被其他用户使用
        $stmt = $conn->prepare('SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1');
        $stmt->bind_param('si', $newUsername, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            sendResponse(400, '该用户名已被其他用户使用');
        }
        
        $stmt->close();
        
        // 更新用户名
        $stmt = $conn->prepare('UPDATE users SET username = ? WHERE id = ?');
        $stmt->bind_param('si', $newUsername, $userId);
        
        if ($stmt->execute()) {
            // 更新session中的用户名
            $_SESSION['username'] = $newUsername;
            
            // 记录更新日志
            error_log("[AUTH_UNIFIED] 用户 {$userId} 更新用户名为: {$newUsername}");
            
            sendResponse(200, '个人信息更新成功', ['username' => $newUsername]);
        } else {
            sendResponse(500, '个人信息更新失败');
        }
        
    } catch (Exception $e) {
        error_log("[AUTH_UNIFIED] 更新个人信息异常: " . $e->getMessage());
        sendResponse(500, '更新个人信息处理异常');
    } finally {
        if (isset($stmt)) {
            $stmt->close();
        }
        closeDBConnection($conn);
    }
}

/**
 * 验证管理员认证状态
 * @return array 包含success、user_id、message等信息的数组
 */
function validateAdminAuth() {
    // 检查session是否启动
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // 检查Authorization头
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $authHeader = $headers['Authorization'] ?? '';
    
    if (empty($authHeader)) {
        return [
            'success' => false,
            'message' => '缺少Authorization头',
            'user_id' => null
        ];
    }
    
    // 解析Bearer token (这里简化处理，直接取用户ID)
    $userId = str_replace('Bearer ', '', $authHeader);
    
    if (empty($userId) || !is_numeric($userId)) {
        return [
            'success' => false,
            'message' => '无效的Authorization头格式',
            'user_id' => null
        ];
    }
    
    // 连接数据库验证用户是否为管理员
    $conn = getDBConnection();
    if (!$conn) {
        return [
            'success' => false,
            'message' => '数据库连接失败',
            'user_id' => null
        ];
    }
    
    try {
        $stmt = $conn->prepare("SELECT id, username, email, is_admin FROM users WHERE id = ? AND is_admin = 1 LIMIT 1");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return [
                'success' => false,
                'message' => '用户不存在或非管理员',
                'user_id' => null
            ];
        }
        
        $user = $result->fetch_assoc();
        
        return [
            'success' => true,
            'message' => '管理员认证成功',
            'user_id' => (int)$user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'is_admin' => true
        ];
        
    } catch (Exception $e) {
        error_log("[AUTH_UNIFIED] 管理员认证异常: " . $e->getMessage());
        return [
            'success' => false,
            'message' => '认证过程中发生错误',
            'user_id' => null
        ];
    } finally {
        if (isset($stmt)) {
            $stmt->close();
        }
        closeDBConnection($conn);
    }
}

/**
 * 验证用户认证状态
 * @return array 包含success、user_id、message等信息的数组
 */
function authenticateUser() {
    // 检查session是否启动
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // 检查session中是否有用户信息
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        return [
            'success' => false,
            'message' => '未登录或会话已过期',
            'user_id' => null
        ];
    }
    
    $userId = $_SESSION['user_id'];
    
    // 连接数据库验证用户是否存在
    $conn = getDBConnection();
    if (!$conn) {
        return [
            'success' => false,
            'message' => '数据库连接失败',
            'user_id' => null
        ];
    }
    
    try {
        $stmt = $conn->prepare("SELECT id, username, email, is_admin, membership_type FROM users WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return [
                'success' => false,
                'message' => '用户不存在',
                'user_id' => null
            ];
        }
        
        $user = $result->fetch_assoc();
        
        return [
            'success' => true,
            'message' => '认证成功',
            'user_id' => (int)$user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'is_admin' => (bool)$user['is_admin'],
            'membership_type' => $user['membership_type']
        ];
        
    } catch (Exception $e) {
        error_log("[AUTH_UNIFIED] 用户认证异常: " . $e->getMessage());
        return [
            'success' => false,
            'message' => '认证过程中发生错误',
            'user_id' => null
        ];
    } finally {
        if (isset($stmt)) {
            $stmt->close();
        }
        closeDBConnection($conn);
    }
}

// 只有在直接访问此文件时才执行路由逻辑
if (basename($_SERVER['PHP_SELF']) === 'auth_unified.php') {
    // 获取请求方法
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'POST') {
        // 获取POST数据
        $input = file_get_contents('php://input');
        error_log("[AUTH_UNIFIED] 接收到的原始数据: " . $input);
        
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("[AUTH_UNIFIED] JSON解析错误: " . json_last_error_msg());
            sendResponse(400, 'JSON格式错误: ' . json_last_error_msg());
        }
        
        error_log("[AUTH_UNIFIED] 解析后的数据: " . print_r($data, true));
        
        // 支持从URL参数或JSON数据中获取action（向后兼容）
        $action = $_GET['action'] ?? $data['action'] ?? '';
        error_log("[AUTH_UNIFIED] 获取到的action: " . $action);
        
        switch ($action) {
            case 'register':
                handleRegister($data);
                break;
            case 'login':
                handleLogin($data);
                break;
            case 'changePassword':
                handleChangePassword($data);
                break;
            case 'updateProfile':
                handleUpdateProfile($data);
                break;
            default:
                sendResponse(400, '不支持的操作');
        }
    } elseif ($method === 'GET') {
        $action = $_GET['action'] ?? '';
        switch ($action) {
            case 'getUserInfo':
                handleGetUserInfo();
                break;
            case 'validateUser':
                handleValidateUser();
                break;
            case 'logout':
                handleLogout();
                break;
            default:
                sendResponse(400, '不支持的操作');
        }
    } else {
        sendResponse(405, '不支持的请求方法');
    }
}

?>
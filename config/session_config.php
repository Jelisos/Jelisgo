<?php
/**
 * Session数据库存储配置文件
 * 实现基于数据库的session存储机制
 * @author AI Assistant
 * @date 2025-01-27
 */

require_once __DIR__ . '/database.php';

/**
 * 解析PHP session数据的自定义函数
 */
function session_decode_custom($sessionData, &$sessionArray) {
    $sessionArray = [];
    
    if (empty($sessionData)) {
        return true;
    }
    
    // PHP session数据格式: key|type:value;
    // 例如: user_id|i:1;username|s:7:"testuser";
    $offset = 0;
    $length = strlen($sessionData);
    
    while ($offset < $length) {
        // 查找键名结束位置
        $keyEnd = strpos($sessionData, '|', $offset);
        if ($keyEnd === false) break;
        
        $key = substr($sessionData, $offset, $keyEnd - $offset);
        $offset = $keyEnd + 1;
        
        // 获取数据类型
        $type = $sessionData[$offset];
        $offset += 2; // 跳过类型和冒号
        
        switch ($type) {
            case 'i': // 整数
                $valueEnd = strpos($sessionData, ';', $offset);
                if ($valueEnd === false) break 2;
                $sessionArray[$key] = (int)substr($sessionData, $offset, $valueEnd - $offset);
                $offset = $valueEnd + 1;
                break;
                
            case 's': // 字符串
                $lengthEnd = strpos($sessionData, ':', $offset);
                if ($lengthEnd === false) break 2;
                $strLength = (int)substr($sessionData, $offset, $lengthEnd - $offset);
                $offset = $lengthEnd + 2; // 跳过长度和冒号和引号
                $sessionArray[$key] = substr($sessionData, $offset, $strLength);
                $offset += $strLength + 2; // 跳过字符串和引号和分号
                break;
                
            case 'b': // 布尔值
                $valueEnd = strpos($sessionData, ';', $offset);
                if ($valueEnd === false) break 2;
                $sessionArray[$key] = (bool)substr($sessionData, $offset, $valueEnd - $offset);
                $offset = $valueEnd + 1;
                break;
                
            case 'N': // NULL
                $sessionArray[$key] = null;
                $offset += 1; // 跳过分号
                break;
                
            default:
                // 跳过未知类型
                $valueEnd = strpos($sessionData, ';', $offset);
                if ($valueEnd === false) break 2;
                $offset = $valueEnd + 1;
                break;
        }
    }
    
    return true;
}

/**
 * 数据库Session处理器类
 */
class DatabaseSessionHandler implements SessionHandlerInterface {
    public $conn;
    private $table = 'sessions';
    private $maxLifetime = 7200; // 2小时默认过期时间
    
    public function __construct() {
        $this->conn = getDBConnection();
        if (!$this->conn) {
            throw new Exception('无法连接数据库');
        }
    }
    
    /**
     * 打开session
     */
    public function open($savePath, $sessionName): bool {
        return true;
    }
    
    /**
     * 关闭session
     */
    public function close(): bool {
        // 不在这里关闭数据库连接，让PHP自动处理
        // 因为session操作可能在脚本执行期间多次调用
        return true;
    }
    
    /**
     * 确保数据库连接有效
     */
    private function ensureConnection() {
        if (!$this->conn || $this->conn->ping() === false) {
            $this->conn = getDBConnection();
            if (!$this->conn) {
                throw new Exception('无法重新连接数据库');
            }
        }
    }
    
    /**
     * 读取session数据
     */
    public function read($sessionId): string {
        $this->ensureConnection();
        
        $stmt = $this->conn->prepare(
            "SELECT session_data FROM {$this->table} 
             WHERE session_id = ? AND expires_at > NOW()"
        );
        
        if (!$stmt) {
            error_log('Session读取准备语句失败: ' . $this->conn->error);
            return '';
        }
        
        $stmt->bind_param('s', $sessionId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            return $row['session_data'] ?? '';
        }
        
        $stmt->close();
        return '';
    }
    
    /**
     * 写入session数据
     */
    public function write($sessionId, $sessionData): bool {
        $this->ensureConnection();
        
        // 从sessionData中解析user_id，而不是从$_SESSION中获取
        $userId = null;
        if (!empty($sessionData)) {
            // 解析session数据
            $sessionArray = [];
            if (session_decode_custom($sessionData, $sessionArray)) {
                $userId = $sessionArray['user_id'] ?? null;
            }
        }
        
        // 如果解析失败，尝试从$_SESSION获取（兼容性处理）
        if ($userId === null && isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
        }
        
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ipAddress = $this->getClientIP();
        $expiresAt = date('Y-m-d H:i:s', time() + $this->maxLifetime);
        
        // 调试日志：记录user_id的值和来源
        error_log("Session写入调试 - Session ID: {$sessionId}, User ID: " . ($userId ?? 'NULL') . ", IP: {$ipAddress}, Data: " . substr($sessionData, 0, 100));
        
        // 检查记录是否存在
        $checkStmt = $this->conn->prepare("SELECT id FROM {$this->table} WHERE session_id = ?");
        $checkStmt->bind_param('s', $sessionId);
        $checkStmt->execute();
        $exists = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();
        
        if ($exists) {
            // 更新现有记录
            $stmt = $this->conn->prepare(
                "UPDATE {$this->table} 
                 SET user_id = ?, session_data = ?, ip_address = ?, user_agent = ?, expires_at = ?, updated_at = NOW() 
                 WHERE session_id = ?"
            );
            if (!$stmt) {
                error_log('Session更新准备语句失败: ' . $this->conn->error);
                return false;
            }
            $stmt->bind_param('isssss', $userId, $sessionData, $ipAddress, $userAgent, $expiresAt, $sessionId);
        } else {
            // 插入新记录
            $stmt = $this->conn->prepare(
                "INSERT INTO {$this->table} 
                 (session_id, user_id, session_data, ip_address, user_agent, expires_at, created_at, updated_at) 
                 VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())"
            );
            if (!$stmt) {
                error_log('Session插入准备语句失败: ' . $this->conn->error);
                return false;
            }
            $stmt->bind_param('sissss', $sessionId, $userId, $sessionData, $ipAddress, $userAgent, $expiresAt);
        }
        $result = $stmt->execute();
        
        if (!$result) {
            error_log('Session写入失败: ' . $stmt->error);
        }
        
        $stmt->close();
        return $result;
    }
    
    /**
     * 销毁session
     */
    public function destroy($sessionId): bool {
        $this->ensureConnection();
        
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE session_id = ?");
        
        if (!$stmt) {
            error_log('Session销毁准备语句失败: ' . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param('s', $sessionId);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * 垃圾回收 - 清理过期session
     */
    public function gc($maxLifetime): int {
        $this->ensureConnection();
        
        // 先获取即将被清理的session_id列表，用于记录过期
        $selectStmt = $this->conn->prepare("SELECT session_id FROM {$this->table} WHERE expires_at < NOW()");
        $expiredSessions = [];
        
        if ($selectStmt) {
            $selectStmt->execute();
            $result = $selectStmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $expiredSessions[] = $row['session_id'];
            }
            $selectStmt->close();
        }
        
        // 记录Session过期到LoginLogger
        if (!empty($expiredSessions)) {
            try {
                require_once __DIR__ . '/../api/login_logger.php';
                $logger = LoginLogger::getInstance();
                foreach ($expiredSessions as $sessionId) {
                    $logger->logSessionExpired($sessionId);
                }
            } catch (Exception $e) {
                error_log("[SessionHandler] 记录Session过期失败: " . $e->getMessage());
            }
        }
        
        // 执行清理
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE expires_at < NOW()");
        
        if (!$stmt) {
            error_log('Session垃圾回收准备语句失败: ' . $this->conn->error);
            return 0;
        }
        
        $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        $stmt->close();
        
        return $affectedRows;
    }
    
    /**
     * 获取客户端真实IP地址
     */
    public function getClientIP(): string {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // 处理多个IP的情况（X-Forwarded-For可能包含多个IP）
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                // 验证IP格式
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}

/**
 * Session管理工具类
 */
class SessionManager {
    private static $instance = null;
    private $handler;
    
    private function __construct() {
        $this->handler = new DatabaseSessionHandler();
    }
    
    /**
     * 获取单例实例
     */
    public static function getInstance(): SessionManager {
        if (self::$instance === null) {
            self::$instance = new SessionManager();
        }
        return self::$instance;
    }
    
    /**
     * 初始化session配置
     */
    public function initSession(): void {
        // 记录当前会话状态
        $sessionStatus = session_status();
        error_log("[SessionManager] 初始化会话前状态: " . $sessionStatus . " (1=禁用, 2=启用但未启动, 3=已启动)");
        
        // 只在session未启动时设置配置
        if ($sessionStatus === PHP_SESSION_NONE) {
            // 设置session配置
            ini_set('session.gc_maxlifetime', 86400); // 24小时
            ini_set('session.cookie_lifetime', 86400); // 24小时，不仅仅是浏览器关闭时过期
            ini_set('session.cookie_httponly', 1); // 防止XSS
            ini_set('session.cookie_secure', 0); // HTTP环境设为0，HTTPS环境应设为1
            ini_set('session.use_strict_mode', 1); // 严格模式
            ini_set('session.use_cookies', 1); // 使用cookie
            ini_set('session.use_only_cookies', 1); // 仅使用cookie，不使用URL传递
            ini_set('session.cookie_path', '/'); // Cookie路径设为根目录
            
            // 设置自定义session处理器
            session_set_save_handler($this->handler, true);
            
            // 启动session
            session_start();
            error_log("[SessionManager] 会话已启动，Session ID: " . session_id());
        } else if ($sessionStatus === PHP_SESSION_ACTIVE) {
            error_log("[SessionManager] 会话已经处于活动状态，Session ID: " . session_id());
        } else {
            error_log("[SessionManager] 会话功能已禁用");
        }
    }
    
    /**
     * 验证session安全性
     */
    public function validateSession(): bool {
        if (!isset($_SESSION['user_id'])) {
            return true; // 未登录用户无需验证
        }
        
        // IP地址验证（可选，严格模式）
        if (isset($_SESSION['ip_address'])) {
            $currentIP = $this->handler->getClientIP();
            if ($_SESSION['ip_address'] !== $currentIP) {
                error_log("Session IP不匹配: 存储IP={$_SESSION['ip_address']}, 当前IP={$currentIP}");
                // 可以选择是否强制退出，这里仅记录日志
                // $this->destroySession();
                // return false;
            }
        }
        
        // User-Agent验证（可选）
        if (isset($_SESSION['user_agent'])) {
            $currentUA = $_SERVER['HTTP_USER_AGENT'] ?? '';
            if ($_SESSION['user_agent'] !== $currentUA) {
                error_log("Session User-Agent不匹配");
                // 可以选择是否强制退出
            }
        }
        
        return true;
    }
    
    /**
     * 设置用户session数据
     */
    public function setUserSession($userId, $userData = []): void {
        // 先设置session数据
        $_SESSION['user_id'] = $userId;
        $_SESSION['ip_address'] = $this->handler->getClientIP();
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        
        // 存储额外用户数据
        foreach ($userData as $key => $value) {
            $_SESSION[$key] = $value;
        }
        
        // 强制写入session数据到数据库
        session_write_close();
        
        // 重新启动session（这样可以确保数据已经持久化）
        session_start();
        
        // 重新生成session ID防止会话固定攻击（在数据设置并持久化后）
        session_regenerate_id(true);
        
        // 再次确保数据存在
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['user_id'] = $userId;
            $_SESSION['ip_address'] = $this->handler->getClientIP();
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $_SESSION['login_time'] = time();
            $_SESSION['last_activity'] = time();
            
            foreach ($userData as $key => $value) {
                $_SESSION[$key] = $value;
            }
        }
        
        // 验证数据是否正确设置
        if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $userId) {
            error_log("Session设置验证失败 - 期望user_id: {$userId}, 实际: " . ($_SESSION['user_id'] ?? 'NULL'));
            error_log("当前Session内容: " . print_r($_SESSION, true));
        } else {
            error_log("Session设置验证成功 - user_id: {$userId}, Session ID: " . session_id());
        }
    }
    
    /**
     * 销毁当前会话
     */
    public function destroySession(): void {
        // 记录当前会话状态
        $sessionId = session_id();
        error_log("[SessionManager] 开始销毁会话，当前Session ID: {$sessionId}");
        error_log("[SessionManager] 当前会话数据: " . json_encode($_SESSION));
        
        // 如果存在活跃会话，记录登出日志
        if (isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
            error_log("[SessionManager] 销毁用户 {$userId} 的会话");
            
            // 从数据库中删除会话记录
            try {
                $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE id = ?");
                $stmt->execute([$sessionId]);
                $rowCount = $stmt->rowCount();
                error_log("[SessionManager] 从数据库删除会话记录: {$rowCount} 行受影响");
            } catch (PDOException $e) {
                error_log("[SessionManager] 从数据库删除会话记录失败: " . $e->getMessage());
            }
            
            // 这部分登出日志记录已移至auth_unified.php的handleLogout函数中
            // 避免重复记录登出日志
        }
        
        // 清空会话数据
        $_SESSION = array();
        error_log("[SessionManager] 已清空会话数据数组");
        
        // 删除会话cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
            error_log("[SessionManager] 已尝试删除会话Cookie");
        }
        
        // 销毁会话
        $result = session_destroy();
        error_log("[SessionManager] 会话销毁结果: " . ($result ? "成功" : "失败"));
    }
    
    /**
     * 清理过期session（可用于定时任务）
     */
    public function cleanExpiredSessions(): int {
        return $this->handler->gc(0);
    }
    
    /**
     * 获取用户的所有活跃session
     */
    public function getUserActiveSessions($userId): array {
        $conn = getDBConnection();
        if (!$conn) {
            return [];
        }
        
        $stmt = $conn->prepare(
            "SELECT session_id, ip_address, user_agent, created_at, updated_at 
             FROM sessions 
             WHERE user_id = ? AND expires_at > NOW() 
             ORDER BY updated_at DESC"
        );
        
        if (!$stmt) {
            $conn->close();
            return [];
        }
        
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $sessions = [];
        while ($row = $result->fetch_assoc()) {
            $sessions[] = $row;
        }
        
        $stmt->close();
        $conn->close();
        
        return $sessions;
    }
}

// 自动初始化session（如果需要）
if (!defined('DISABLE_AUTO_SESSION_INIT')) {
    try {
        $sessionManager = SessionManager::getInstance();
        $sessionManager->initSession();
        $sessionManager->validateSession();
    } catch (Exception $e) {
        error_log('Session初始化失败: ' . $e->getMessage());
        // 降级到文件session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}
?>
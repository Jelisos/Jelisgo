<?php
/**
 * 登录记录管理器 - 简化版本
 * 独立管理用户登录历史记录，与Session系统解耦
 */

require_once __DIR__ . '/../config/database.php';

class LoginLogger {
    private $conn;
    private static $instance = null;
    
    private function __construct() {
        $this->conn = getDBConnection();
        if (!$this->conn) {
            throw new Exception('数据库连接失败');
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 记录登录成功
     */
    public function logLoginSuccess($userId, $sessionId, $ipAddress, $userAgent, $loginMethod = 'password', $deviceInfo = null) {
        try {
            $sql = "INSERT INTO user_login_logs 
                    (user_id, session_id, login_time, ip_address, user_agent, login_status, login_method, device_info) 
                    VALUES (?, ?, NOW(), ?, ?, 'success', ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            $deviceJson = $deviceInfo ? json_encode($deviceInfo) : null;
            
            $stmt->bind_param('isssss', $userId, $sessionId, $ipAddress, $userAgent, $loginMethod, $deviceJson);
            $result = $stmt->execute();
            
            if ($result) {
                error_log("[LoginLogger] 登录成功记录已保存 - 用户ID: {$userId}, Session: {$sessionId}");
            }
            
            $stmt->close();
            return $result;
        } catch (Exception $e) {
            error_log("[LoginLogger] 记录登录成功失败: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 记录登录失败
     */
    public function logLoginFailure($identifier, $ipAddress, $userAgent, $failureReason) {
        try {
            $userId = $this->getUserIdByIdentifier($identifier);
            
            $sql = "INSERT INTO user_login_logs 
                    (user_id, session_id, login_time, ip_address, user_agent, login_status, failure_reason) 
                    VALUES (?, NULL, NOW(), ?, ?, 'failure', ?)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('isss', $userId, $ipAddress, $userAgent, $failureReason);
            $result = $stmt->execute();
            
            if ($result) {
                error_log("[LoginLogger] 登录失败记录已保存 - 标识: {$identifier}, 原因: {$failureReason}");
            }
            
            $stmt->close();
            return $result;
        } catch (Exception $e) {
            error_log("[LoginLogger] 记录登录失败失败: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 记录登出
     */
    public function logLogout($userId, $sessionId, $ipAddress, $userAgent) {
        try {
            $sql = "INSERT INTO user_login_logs 
                    (user_id, session_id, login_time, ip_address, user_agent, login_status) 
                    VALUES (?, ?, NOW(), ?, ?, 'logout')";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('isss', $userId, $sessionId, $ipAddress, $userAgent);
            $result = $stmt->execute();
            
            if ($result) {
                error_log("[LoginLogger] 登出记录已保存 - 用户ID: {$userId}, Session: {$sessionId}");
            }
            
            $stmt->close();
            return $result;
        } catch (Exception $e) {
            error_log("[LoginLogger] 记录登出失败: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 根据标识获取用户ID
     */
    private function getUserIdByIdentifier($identifier) {
        try {
            $sql = "SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('ss', $identifier, $identifier);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            
            return $user ? $user['id'] : null;
        } catch (Exception $e) {
            error_log("[LoginLogger] 获取用户ID失败: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * 获取用户登录历史
     */
    public function getUserLoginHistory($userId, $limit = 10) {
        try {
            $sql = "SELECT * FROM user_login_logs WHERE user_id = ? ORDER BY login_time DESC LIMIT ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('ii', $userId, $limit);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $logs = [];
            while ($row = $result->fetch_assoc()) {
                $logs[] = $row;
            }
            $stmt->close();
            
            return $logs;
        } catch (Exception $e) {
            error_log("[LoginLogger] 获取登录历史失败: " . $e->getMessage());
            return [];
        }
    }
    
    public function __destruct() {
        if ($this->conn) {
            closeDBConnection($this->conn);
        }
    }
}
?>
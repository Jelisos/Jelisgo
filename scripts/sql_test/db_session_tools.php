<?php
/**
 * 会话数据库工具 - 用于管理和维护会话表
 * 此工具提供了会话表的管理功能，包括清理过期会话、查看活跃会话、修复会话表等
 */

// 设置响应头
if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/html; charset=utf-8');
}

// 引入数据库配置
require_once __DIR__ . '/../../config/database.php';

/**
 * 会话数据库工具类
 */
class SessionDatabaseTools {
    private $pdo;
    private $isCliMode;
    
    /**
     * 构造函数
     */
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->isCliMode = php_sapi_name() === 'cli';
    }
    
    /**
     * 输出消息
     */
    private function output($message) {
        if ($this->isCliMode) {
            echo $message . PHP_EOL;
        } else {
            echo $message . '<br>';
        }
    }
    
    /**
     * 获取会话表信息
     */
    public function getSessionTableInfo() {
        try {
            // 获取表结构
            $stmt = $this->pdo->query("DESCRIBE sessions");
            $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 获取表索引
            $stmt = $this->pdo->query("SHOW INDEX FROM sessions");
            $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 获取表状态
            $stmt = $this->pdo->query("SHOW TABLE STATUS LIKE 'sessions'");
            $status = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // 获取记录数
            $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM sessions");
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // 获取活跃会话数
            $stmt = $this->pdo->query("SELECT COUNT(*) as active FROM sessions WHERE last_activity > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
            $active = $stmt->fetch(PDO::FETCH_ASSOC)['active'];
            
            return [
                'structure' => $structure,
                'indexes' => $indexes,
                'status' => $status,
                'count' => $count,
                'active' => $active,
            ];
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * 清理过期会话
     */
    public function cleanExpiredSessions($hours = 24) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE last_activity < DATE_SUB(NOW(), INTERVAL ? HOUR)");
            $stmt->execute([$hours]);
            $count = $stmt->rowCount();
            
            return [
                'success' => true,
                'message' => "已清理 {$count} 个过期会话",
                'count' => $count,
            ];
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * 获取活跃会话列表
     */
    public function getActiveSessions($hours = 24) {
        try {
            $stmt = $this->pdo->prepare("SELECT s.*, u.username 
                FROM sessions s 
                LEFT JOIN users u ON s.user_id = u.id 
                WHERE s.last_activity > DATE_SUB(NOW(), INTERVAL ? HOUR) 
                ORDER BY s.last_activity DESC");
            $stmt->execute([$hours]);
            $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 解析会话数据
            foreach ($sessions as &$session) {
                if (isset($session['data'])) {
                    $sessionData = $session['data'];
                    $decodedData = [];
                    
                    // 手动解析会话数据
                    $pattern = '/([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)(\|)([^|]*)(\|)/';
                    preg_match_all($pattern, $sessionData, $matches, PREG_SET_ORDER);
                    
                    foreach ($matches as $match) {
                        $key = $match[1];
                        $value = $match[3];
                        $decodedData[$key] = $value;
                    }
                    
                    $session['decoded_data'] = $decodedData;
                }
            }
            
            return $sessions;
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * 获取用户会话列表
     */
    public function getUserSessions($userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT s.*, u.username 
                FROM sessions s 
                LEFT JOIN users u ON s.user_id = u.id 
                WHERE s.user_id = ? 
                ORDER BY s.last_activity DESC");
            $stmt->execute([$userId]);
            $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 解析会话数据
            foreach ($sessions as &$session) {
                if (isset($session['data'])) {
                    $sessionData = $session['data'];
                    $decodedData = [];
                    
                    // 手动解析会话数据
                    $pattern = '/([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)(\|)([^|]*)(\|)/';
                    preg_match_all($pattern, $sessionData, $matches, PREG_SET_ORDER);
                    
                    foreach ($matches as $match) {
                        $key = $match[1];
                        $value = $match[3];
                        $decodedData[$key] = $value;
                    }
                    
                    $session['decoded_data'] = $decodedData;
                }
            }
            
            return $sessions;
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * 删除指定会话
     */
    public function deleteSession($sessionId) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE id = ?");
            $stmt->execute([$sessionId]);
            $count = $stmt->rowCount();
            
            return [
                'success' => $count > 0,
                'message' => $count > 0 ? "已删除会话 {$sessionId}" : "未找到会话 {$sessionId}",
                'count' => $count,
            ];
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * 删除用户所有会话
     */
    public function deleteUserSessions($userId) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE user_id = ?");
            $stmt->execute([$userId]);
            $count = $stmt->rowCount();
            
            return [
                'success' => true,
                'message' => "已删除用户 {$userId} 的 {$count} 个会话",
                'count' => $count,
            ];
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * 修复会话表结构
     */
    public function repairSessionTable() {
        try {
            // 检查表是否存在
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'sessions'");
            $tableExists = $stmt->rowCount() > 0;
            
            if (!$tableExists) {
                // 创建会话表
                $this->pdo->exec("CREATE TABLE sessions (
                    id VARCHAR(128) NOT NULL PRIMARY KEY,
                    user_id INT,
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    data TEXT,
                    last_activity INT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    expires_at TIMESTAMP NULL,
                    INDEX(user_id),
                    INDEX(last_activity),
                    INDEX(expires_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                
                return [
                    'success' => true,
                    'message' => '已创建会话表',
                    'action' => 'created',
                ];
            }
            
            // 检查表结构
            $stmt = $this->pdo->query("DESCRIBE sessions");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $columnNames = array_column($columns, 'Field');
            
            $alterStatements = [];
            
            // 检查必要的列
            if (!in_array('user_agent', $columnNames)) {
                $alterStatements[] = "ADD COLUMN user_agent TEXT AFTER ip_address";
            }
            
            if (!in_array('updated_at', $columnNames)) {
                $alterStatements[] = "ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at";
            }
            
            if (!in_array('expires_at', $columnNames)) {
                $alterStatements[] = "ADD COLUMN expires_at TIMESTAMP NULL AFTER updated_at";
            }
            
            // 执行修改
            if (!empty($alterStatements)) {
                $alterSql = "ALTER TABLE sessions " . implode(", ", $alterStatements);
                $this->pdo->exec($alterSql);
                
                // 添加索引
                $stmt = $this->pdo->query("SHOW INDEX FROM sessions");
                $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $indexNames = array_column($indexes, 'Key_name');
                
                if (!in_array('user_id', $indexNames)) {
                    $this->pdo->exec("ALTER TABLE sessions ADD INDEX(user_id)");
                }
                
                if (!in_array('last_activity', $indexNames)) {
                    $this->pdo->exec("ALTER TABLE sessions ADD INDEX(last_activity)");
                }
                
                if (!in_array('expires_at', $indexNames)) {
                    $this->pdo->exec("ALTER TABLE sessions ADD INDEX(expires_at)");
                }
                
                return [
                    'success' => true,
                    'message' => '已修复会话表结构',
                    'action' => 'altered',
                    'alterStatements' => $alterStatements,
                ];
            }
            
            return [
                'success' => true,
                'message' => '会话表结构正常',
                'action' => 'none',
            ];
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * 优化会话表
     */
    public function optimizeSessionTable() {
        try {
            $this->pdo->exec("OPTIMIZE TABLE sessions");
            
            return [
                'success' => true,
                'message' => '已优化会话表',
            ];
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * 分析会话表
     */
    public function analyzeSessionTable() {
        try {
            $stmt = $this->pdo->query("ANALYZE TABLE sessions");
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'message' => '已分析会话表',
                'result' => $result,
            ];
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * 运行会话工具命令
     */
    public function runCommand($command, $params = []) {
        switch ($command) {
            case 'info':
                $result = $this->getSessionTableInfo();
                $this->output("会话表信息:");
                $this->output("总记录数: {$result['count']}");
                $this->output("活跃会话数: {$result['active']}");
                $this->output("表结构:");
                foreach ($result['structure'] as $column) {
                    $this->output("  {$column['Field']} - {$column['Type']} - {$column['Null']} - {$column['Key']} - {$column['Default']}");
                }
                break;
            
            case 'clean':
                $hours = isset($params['hours']) ? (int)$params['hours'] : 24;
                $result = $this->cleanExpiredSessions($hours);
                $this->output($result['message']);
                break;
            
            case 'active':
                $hours = isset($params['hours']) ? (int)$params['hours'] : 24;
                $result = $this->getActiveSessions($hours);
                if (isset($result['error'])) {
                    $this->output("错误: {$result['error']}");
                } else {
                    $this->output("活跃会话列表 (过去 {$hours} 小时):");
                    foreach ($result as $session) {
                        $username = $session['username'] ?? '未登录';
                        $lastActivity = date('Y-m-d H:i:s', $session['last_activity']);
                        $this->output("  ID: {$session['id']} - 用户: {$username} - IP: {$session['ip_address']} - 最后活动: {$lastActivity}");
                    }
                }
                break;
            
            case 'user':
                $userId = isset($params['user_id']) ? (int)$params['user_id'] : 0;
                if ($userId <= 0) {
                    $this->output("错误: 无效的用户ID");
                    break;
                }
                $result = $this->getUserSessions($userId);
                if (isset($result['error'])) {
                    $this->output("错误: {$result['error']}");
                } else {
                    $this->output("用户 {$userId} 的会话列表:");
                    foreach ($result as $session) {
                        $username = $session['username'] ?? '未知';
                        $lastActivity = date('Y-m-d H:i:s', $session['last_activity']);
                        $this->output("  ID: {$session['id']} - 用户: {$username} - IP: {$session['ip_address']} - 最后活动: {$lastActivity}");
                    }
                }
                break;
            
            case 'delete':
                $sessionId = isset($params['session_id']) ? $params['session_id'] : '';
                if (empty($sessionId)) {
                    $this->output("错误: 无效的会话ID");
                    break;
                }
                $result = $this->deleteSession($sessionId);
                $this->output($result['message']);
                break;
            
            case 'delete_user':
                $userId = isset($params['user_id']) ? (int)$params['user_id'] : 0;
                if ($userId <= 0) {
                    $this->output("错误: 无效的用户ID");
                    break;
                }
                $result = $this->deleteUserSessions($userId);
                $this->output($result['message']);
                break;
            
            case 'repair':
                $result = $this->repairSessionTable();
                if (isset($result['error'])) {
                    $this->output("错误: {$result['error']}");
                } else {
                    $this->output($result['message']);
                }
                break;
            
            case 'optimize':
                $result = $this->optimizeSessionTable();
                if (isset($result['error'])) {
                    $this->output("错误: {$result['error']}");
                } else {
                    $this->output($result['message']);
                }
                break;
            
            case 'analyze':
                $result = $this->analyzeSessionTable();
                if (isset($result['error'])) {
                    $this->output("错误: {$result['error']}");
                } else {
                    $this->output($result['message']);
                }
                break;
            
            default:
                $this->output("未知命令: {$command}");
                $this->output("可用命令:");
                $this->output("  info - 获取会话表信息");
                $this->output("  clean [hours=24] - 清理过期会话");
                $this->output("  active [hours=24] - 获取活跃会话列表");
                $this->output("  user user_id - 获取用户会话列表");
                $this->output("  delete session_id - 删除指定会话");
                $this->output("  delete_user user_id - 删除用户所有会话");
                $this->output("  repair - 修复会话表结构");
                $this->output("  optimize - 优化会话表");
                $this->output("  analyze - 分析会话表");
                break;
        }
    }
}

// 创建工具实例
$sessionTools = new SessionDatabaseTools();

// 处理命令行参数
if (php_sapi_name() === 'cli') {
    $command = $argv[1] ?? 'help';
    $params = [];
    
    // 解析参数
    for ($i = 2; $i < $argc; $i++) {
        $arg = $argv[$i];
        if (strpos($arg, '=') !== false) {
            list($key, $value) = explode('=', $arg, 2);
            $params[$key] = $value;
        } else {
            $params[$arg] = true;
        }
    }
    
    // 运行命令
    $sessionTools->runCommand($command, $params);
} else {
    // Web模式
    echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>会话数据库工具</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        h1, h2 {
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="number"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .result {
            margin-top: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .error {
            color: #f44336;
        }
        .success {
            color: #4CAF50;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>会话数据库工具</h1>
        
        <h2>会话表信息</h2>
        <form method="post">
            <input type="hidden" name="command" value="info">
            <button type="submit">获取会话表信息</button>
        </form>
        
        <h2>清理过期会话</h2>
        <form method="post">
            <input type="hidden" name="command" value="clean">
            <div class="form-group">
                <label for="hours">过期时间（小时）</label>
                <input type="number" id="hours" name="hours" value="24" min="1" max="720">
            </div>
            <button type="submit">清理过期会话</button>
        </form>
        
        <h2>查看活跃会话</h2>
        <form method="post">
            <input type="hidden" name="command" value="active">
            <div class="form-group">
                <label for="active_hours">活跃时间范围（小时）</label>
                <input type="number" id="active_hours" name="hours" value="24" min="1" max="720">
            </div>
            <button type="submit">查看活跃会话</button>
        </form>
        
        <h2>查看用户会话</h2>
        <form method="post">
            <input type="hidden" name="command" value="user">
            <div class="form-group">
                <label for="user_id">用户ID</label>
                <input type="number" id="user_id" name="user_id" value="1" min="1">
            </div>
            <button type="submit">查看用户会话</button>
        </form>
        
        <h2>删除指定会话</h2>
        <form method="post">
            <input type="hidden" name="command" value="delete">
            <div class="form-group">
                <label for="session_id">会话ID</label>
                <input type="text" id="session_id" name="session_id" placeholder="输入会话ID">
            </div>
            <button type="submit">删除会话</button>
        </form>
        
        <h2>删除用户所有会话</h2>
        <form method="post">
            <input type="hidden" name="command" value="delete_user">
            <div class="form-group">
                <label for="delete_user_id">用户ID</label>
                <input type="number" id="delete_user_id" name="user_id" value="1" min="1">
            </div>
            <button type="submit">删除用户所有会话</button>
        </form>
        
        <h2>维护会话表</h2>
        <form method="post" style="display: inline-block; margin-right: 10px;">
            <input type="hidden" name="command" value="repair">
            <button type="submit">修复会话表</button>
        </form>
        
        <form method="post" style="display: inline-block; margin-right: 10px;">
            <input type="hidden" name="command" value="optimize">
            <button type="submit">优化会话表</button>
        </form>
        
        <form method="post" style="display: inline-block;">
            <input type="hidden" name="command" value="analyze">
            <button type="submit">分析会话表</button>
        </form>
        
        <div class="result">';
    
    // 处理表单提交
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $command = $_POST['command'] ?? 'help';
        $params = $_POST;
        unset($params['command']);
        
        // 捕获输出
        ob_start();
        $sessionTools->runCommand($command, $params);
        $output = ob_get_clean();
        
        echo $output;
    } else {
        echo '<p>选择上面的操作来管理会话表。</p>';
    }
    
    echo '</div>
    </div>
</body>
</html>';
}
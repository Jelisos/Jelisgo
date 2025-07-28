<?php
/**
 * 数据库快速管理工具集
 * 提供数据库创建、检测、维护的一站式解决方案
 * 文件位置：/scripts/sql_test/db_quick_tools.php
 * 
 * 使用方法：
 * php db_quick_tools.php [action] [options]
 * 
 * 可用操作：
 * - create: 创建数据库和基础表结构
 * - verify: 检测数据库结构完整性
 * - status: 查看数据库状态信息
 * - optimize: 优化数据库性能
 * - backup: 备份数据库结构
 */

class DatabaseManager {
    private $pdo;
    private $dbName = 'wallpaper_db';
    private $host = 'localhost';
    private $username = 'root';
    private $password = '';
    
    public function __construct() {
        $this->connectToServer();
    }
    
    /**
     * 连接到MySQL服务器（不指定数据库）
     */
    private function connectToServer() {
        try {
            $this->pdo = new PDO(
                "mysql:host={$this->host};charset=utf8mb4", 
                $this->username, 
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
            echo "✓ MySQL服务器连接成功\n";
        } catch (PDOException $e) {
            die("✗ MySQL连接失败: " . $e->getMessage() . "\n");
        }
    }
    
    /**
     * 连接到指定数据库
     */
    private function connectToDatabase() {
        try {
            $this->pdo = new PDO(
                "mysql:host={$this->host};dbname={$this->dbName};charset=utf8mb4", 
                $this->username, 
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * 创建数据库和核心表结构
     */
    public function createDatabase() {
        echo "开始创建数据库和表结构...\n";
        
        try {
            // 1. 创建数据库
            $this->pdo->exec("CREATE DATABASE IF NOT EXISTS {$this->dbName} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "✓ 数据库 {$this->dbName} 创建成功\n";
            
            // 2. 选择数据库
            $this->pdo->exec("USE {$this->dbName}");
            
            // 3. 创建核心表结构
            $this->createCoreTables();
            
            echo "✓ 数据库创建完成\n";
            
        } catch (PDOException $e) {
            echo "✗ 创建失败: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * 创建核心表结构
     */
    private function createCoreTables() {
        $tables = [
            'users' => "
                CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(50) NOT NULL UNIQUE,
                    email VARCHAR(100) NOT NULL UNIQUE,
                    password_hash VARCHAR(255) NOT NULL,
                    avatar_url VARCHAR(255) DEFAULT NULL,
                    status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_username (username),
                    INDEX idx_email (email),
                    INDEX idx_status (status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'sessions' => "
                CREATE TABLE IF NOT EXISTS sessions (
                    id VARCHAR(128) PRIMARY KEY,
                    user_id INT NOT NULL,
                    ip_address VARCHAR(45) NOT NULL,
                    user_agent TEXT,
                    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_user_id (user_id),
                    INDEX idx_last_activity (last_activity)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'user_status_ext' => "
                CREATE TABLE IF NOT EXISTS user_status_ext (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL UNIQUE,
                    membership_level ENUM('free', 'premium', 'vip') DEFAULT 'free',
                    membership_expires_at TIMESTAMP NULL,
                    total_downloads INT DEFAULT 0,
                    total_uploads INT DEFAULT 0,
                    last_login_at TIMESTAMP NULL,
                    login_count INT DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_user_id (user_id),
                    INDEX idx_membership_level (membership_level)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'wallpapers' => "
                CREATE TABLE IF NOT EXISTS wallpapers (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    file_path VARCHAR(500) NOT NULL,
                    file_size INT NOT NULL,
                    width INT NOT NULL,
                    height INT NOT NULL,
                    category VARCHAR(50) NOT NULL,
                    tags TEXT,
                    format ENUM('jpg', 'jpeg', 'png', 'webp', 'gif') NOT NULL,
                    views INT DEFAULT 0,
                    likes INT DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_user_id (user_id),
                    INDEX idx_category (category),
                    INDEX idx_created_at (created_at),
                    INDEX idx_views (views),
                    INDEX idx_likes (likes)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            "
        ];
        
        foreach ($tables as $tableName => $sql) {
            try {
                $this->pdo->exec($sql);
                echo "  ✓ 表 {$tableName} 创建成功\n";
            } catch (PDOException $e) {
                echo "  ✗ 表 {$tableName} 创建失败: " . $e->getMessage() . "\n";
            }
        }
        
        // 添加外键约束
        $this->addForeignKeys();
    }
    
    /**
     * 添加外键约束
     */
    private function addForeignKeys() {
        $constraints = [
            "ALTER TABLE sessions ADD CONSTRAINT fk_sessions_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE",
            "ALTER TABLE user_status_ext ADD CONSTRAINT fk_user_status_ext_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE",
            "ALTER TABLE wallpapers ADD CONSTRAINT fk_wallpapers_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE"
        ];
        
        foreach ($constraints as $constraint) {
            try {
                $this->pdo->exec($constraint);
            } catch (PDOException $e) {
                // 外键可能已存在，忽略错误
            }
        }
        echo "  ✓ 外键约束添加完成\n";
    }
    
    /**
     * 验证数据库结构
     */
    public function verifyDatabase() {
        echo "开始验证数据库结构...\n";
        
        if (!$this->connectToDatabase()) {
            echo "✗ 无法连接到数据库 {$this->dbName}\n";
            return;
        }
        
        // 检查数据库是否存在
        $dbExists = $this->pdo->query(
            "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$this->dbName}'"
        )->fetch();
        
        if (!$dbExists) {
            echo "✗ 数据库 {$this->dbName} 不存在\n";
            return;
        }
        
        echo "✓ 数据库 {$this->dbName} 存在\n";
        
        // 检查核心表
        $requiredTables = ['users', 'sessions', 'user_status_ext', 'wallpapers'];
        $existingTables = $this->pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($requiredTables as $table) {
            if (in_array($table, $existingTables)) {
                echo "✓ 表 {$table} 存在\n";
                $this->analyzeTable($table);
            } else {
                echo "✗ 表 {$table} 不存在\n";
            }
        }
        
        // 检查外键约束
        $this->checkForeignKeys();
    }
    
    /**
     * 分析单个表的结构
     */
    private function analyzeTable($tableName) {
        // 字段信息
        $columns = $this->pdo->query("DESCRIBE {$tableName}")->fetchAll();
        echo "  字段数量: " . count($columns) . "\n";
        
        // 索引信息
        $indexes = $this->pdo->query("SHOW INDEX FROM {$tableName}")->fetchAll();
        $indexNames = array_unique(array_column($indexes, 'Key_name'));
        echo "  索引: " . implode(", ", $indexNames) . "\n";
        
        // 记录数量
        $count = $this->pdo->query("SELECT COUNT(*) FROM {$tableName}")->fetchColumn();
        echo "  记录数: {$count}\n";
        
        // 表大小
        $sizeInfo = $this->pdo->query("
            SELECT 
                ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
            FROM information_schema.TABLES 
            WHERE table_schema = '{$this->dbName}' AND table_name = '{$tableName}'
        ")->fetch();
        
        if ($sizeInfo) {
            echo "  表大小: {$sizeInfo['size_mb']} MB\n";
        }
    }
    
    /**
     * 检查外键约束
     */
    private function checkForeignKeys() {
        $foreignKeys = $this->pdo->query("
            SELECT 
                TABLE_NAME,
                COLUMN_NAME,
                CONSTRAINT_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE REFERENCED_TABLE_SCHEMA = '{$this->dbName}'
        ")->fetchAll();
        
        echo "\n外键约束:\n";
        if (empty($foreignKeys)) {
            echo "  无外键约束\n";
        } else {
            foreach ($foreignKeys as $fk) {
                echo "  {$fk['TABLE_NAME']}.{$fk['COLUMN_NAME']} -> {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}\n";
            }
        }
    }
    
    /**
     * 显示数据库状态信息
     */
    public function showStatus() {
        echo "数据库状态信息:\n";
        
        if (!$this->connectToDatabase()) {
            echo "✗ 无法连接到数据库\n";
            return;
        }
        
        // MySQL版本
        $version = $this->pdo->query("SELECT VERSION() as version")->fetch();
        echo "MySQL版本: {$version['version']}\n";
        
        // 连接数
        $connections = $this->pdo->query("SHOW STATUS LIKE 'Threads_connected'")->fetch();
        echo "当前连接数: {$connections['Value']}\n";
        
        // 查询统计
        $queries = $this->pdo->query("SHOW STATUS LIKE 'Queries'")->fetch();
        echo "总查询数: {$queries['Value']}\n";
        
        // 慢查询
        $slowQueries = $this->pdo->query("SHOW STATUS LIKE 'Slow_queries'")->fetch();
        echo "慢查询数: {$slowQueries['Value']}\n";
        
        // 数据库大小
        $dbSize = $this->pdo->query("
            SELECT 
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
            FROM information_schema.TABLES 
            WHERE table_schema = '{$this->dbName}'
        ")->fetch();
        
        if ($dbSize) {
            echo "数据库大小: {$dbSize['size_mb']} MB\n";
        }
    }
    
    /**
     * 优化数据库性能
     */
    public function optimizeDatabase() {
        echo "开始优化数据库...\n";
        
        if (!$this->connectToDatabase()) {
            echo "✗ 无法连接到数据库\n";
            return;
        }
        
        $tables = $this->pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($tables as $table) {
            try {
                // 分析表
                $this->pdo->exec("ANALYZE TABLE {$table}");
                echo "  ✓ 分析表 {$table}\n";
                
                // 优化表
                $this->pdo->exec("OPTIMIZE TABLE {$table}");
                echo "  ✓ 优化表 {$table}\n";
                
            } catch (PDOException $e) {
                echo "  ✗ 优化表 {$table} 失败: " . $e->getMessage() . "\n";
            }
        }
        
        echo "✓ 数据库优化完成\n";
    }
    
    /**
     * 备份数据库结构
     */
    public function backupStructure() {
        echo "开始备份数据库结构...\n";
        
        if (!$this->connectToDatabase()) {
            echo "✗ 无法连接到数据库\n";
            return;
        }
        
        $backupFile = "../scripts/backup_structure_" . date('Y-m-d_H-i-s') . ".sql";
        $sql = "-- 数据库结构备份\n-- 生成时间: " . date('Y-m-d H:i:s') . "\n\n";
        
        // 获取所有表
        $tables = $this->pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($tables as $table) {
            $createTable = $this->pdo->query("SHOW CREATE TABLE {$table}")->fetch();
            $sql .= "-- 表结构: {$table}\n";
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $sql .= $createTable['Create Table'] . ";\n\n";
        }
        
        if (file_put_contents($backupFile, $sql)) {
            echo "✓ 结构备份完成: {$backupFile}\n";
        } else {
            echo "✗ 备份失败\n";
        }
    }
}

// 命令行参数处理
if (php_sapi_name() === 'cli') {
    $action = $argv[1] ?? 'help';
    $manager = new DatabaseManager();
    
    switch ($action) {
        case 'create':
            $manager->createDatabase();
            break;
            
        case 'verify':
            $manager->verifyDatabase();
            break;
            
        case 'status':
            $manager->showStatus();
            break;
            
        case 'optimize':
            $manager->optimizeDatabase();
            break;
            
        case 'backup':
            $manager->backupStructure();
            break;
            
        case 'help':
        default:
            echo "数据库管理工具\n";
            echo "使用方法: php db_quick_tools.php [action]\n\n";
            echo "可用操作:\n";
            echo "  create   - 创建数据库和表结构\n";
            echo "  verify   - 验证数据库结构\n";
            echo "  status   - 显示数据库状态\n";
            echo "  optimize - 优化数据库性能\n";
            echo "  backup   - 备份数据库结构\n";
            echo "  help     - 显示帮助信息\n";
            break;
    }
} else {
    // Web访问时的简单界面
    echo "<h1>数据库管理工具</h1>";
    echo "<p>请通过命令行使用此工具</p>";
}
?>
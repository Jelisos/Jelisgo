<?php
/**
 * 数据库连接测试脚本
 * 快速检测MySQL连接状态和基本配置
 * 文件位置：/scripts/sql_test/db_connection_test.php
 * 
 * 使用方法：
 * php db_connection_test.php
 * 或在浏览器中访问：http://localhost/scripts/db_connection_test.php
 */

class ConnectionTester {
    private $tests = [];
    private $isWebMode;
    
    public function __construct() {
        $this->isWebMode = php_sapi_name() !== 'cli';
        if ($this->isWebMode) {
            echo "<html><head><title>数据库连接测试</title></head><body>";
            echo "<h1>数据库连接测试</h1>";
            echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} pre{background:#f5f5f5;padding:10px;border-radius:5px;}</style>";
        }
    }
    
    public function __destruct() {
        if ($this->isWebMode) {
            echo "</body></html>";
        }
    }
    
    /**
     * 输出信息（支持CLI和Web模式）
     */
    private function output($message, $type = 'info') {
        if ($this->isWebMode) {
            $class = $type === 'success' ? 'success' : ($type === 'error' ? 'error' : 'info');
            echo "<div class='{$class}'>{$message}</div>";
        } else {
            $prefix = $type === 'success' ? '✓' : ($type === 'error' ? '✗' : '•');
            echo "{$prefix} {$message}\n";
        }
    }
    
    /**
     * 运行所有测试
     */
    public function runAllTests() {
        $this->output("开始数据库连接测试...", 'info');
        $this->output("", 'info');
        
        $this->testPHPExtensions();
        $this->testMySQLConnection();
        $this->testDatabaseExists();
        $this->testTableStructure();
        $this->testPerformance();
        
        $this->showSummary();
    }
    
    /**
     * 测试PHP扩展
     */
    private function testPHPExtensions() {
        $this->output("1. 检查PHP扩展:", 'info');
        
        // 检查PDO扩展
        if (extension_loaded('pdo')) {
            $this->output("   PDO扩展: 已加载", 'success');
            $this->tests['pdo'] = true;
        } else {
            $this->output("   PDO扩展: 未加载", 'error');
            $this->tests['pdo'] = false;
        }
        
        // 检查PDO MySQL驱动
        if (extension_loaded('pdo_mysql')) {
            $this->output("   PDO MySQL驱动: 已加载", 'success');
            $this->tests['pdo_mysql'] = true;
        } else {
            $this->output("   PDO MySQL驱动: 未加载", 'error');
            $this->tests['pdo_mysql'] = false;
        }
        
        // 检查MySQLi扩展
        if (extension_loaded('mysqli')) {
            $this->output("   MySQLi扩展: 已加载", 'success');
            $this->tests['mysqli'] = true;
        } else {
            $this->output("   MySQLi扩展: 未加载", 'error');
            $this->tests['mysqli'] = false;
        }
        
        $this->output("", 'info');
    }
    
    /**
     * 测试MySQL连接
     */
    private function testMySQLConnection() {
        $this->output("2. 测试MySQL连接:", 'info');
        
        $configs = [
            ['host' => 'localhost', 'port' => '3306', 'user' => 'root', 'pass' => ''],
            ['host' => '127.0.0.1', 'port' => '3306', 'user' => 'root', 'pass' => ''],
        ];
        
        foreach ($configs as $config) {
            $this->testSingleConnection($config);
        }
        
        $this->output("", 'info');
    }
    
    /**
     * 测试单个连接配置
     */
    private function testSingleConnection($config) {
        $dsn = "mysql:host={$config['host']};port={$config['port']};charset=utf8mb4";
        
        try {
            $startTime = microtime(true);
            $pdo = new PDO($dsn, $config['user'], $config['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5
            ]);
            $endTime = microtime(true);
            
            $connectionTime = round(($endTime - $startTime) * 1000, 2);
            
            // 获取MySQL版本
            $version = $pdo->query("SELECT VERSION() as version")->fetch();
            
            $this->output("   {$config['host']}:{$config['port']} - 连接成功 ({$connectionTime}ms)", 'success');
            $this->output("   MySQL版本: {$version['version']}", 'info');
            
            $this->tests['connection'] = true;
            
            // 测试字符集
            $charset = $pdo->query("SELECT @@character_set_database as charset")->fetch();
            if ($charset) {
                $this->output("   默认字符集: {$charset['charset']}", 'info');
            }
            
            return $pdo;
            
        } catch (PDOException $e) {
            $this->output("   {$config['host']}:{$config['port']} - 连接失败: " . $e->getMessage(), 'error');
            $this->tests['connection'] = false;
            return null;
        }
    }
    
    /**
     * 测试数据库是否存在
     */
    private function testDatabaseExists() {
        $this->output("3. 检查数据库:", 'info');
        
        try {
            $pdo = new PDO("mysql:host=localhost;charset=utf8mb4", "root", "", [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            
            // 检查wallpaper_db数据库
            $databases = $pdo->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);
            
            if (in_array('wallpaper_db', $databases)) {
                $this->output("   wallpaper_db: 存在", 'success');
                $this->tests['database'] = true;
                
                // 连接到具体数据库
                $pdo->exec("USE wallpaper_db");
                
                // 检查表
                $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                $this->output("   表数量: " . count($tables), 'info');
                
                if (!empty($tables)) {
                    $this->output("   表列表: " . implode(", ", $tables), 'info');
                }
                
            } else {
                $this->output("   wallpaper_db: 不存在", 'error');
                $this->tests['database'] = false;
            }
            
            // 显示所有数据库
            $this->output("   所有数据库: " . implode(", ", $databases), 'info');
            
        } catch (PDOException $e) {
            $this->output("   检查失败: " . $e->getMessage(), 'error');
            $this->tests['database'] = false;
        }
        
        $this->output("", 'info');
    }
    
    /**
     * 测试表结构
     */

    /**
     * 执行SQL查询并返回结果
     */
    public function executeQuery($sql, $dbName = 'wallpaper_db') {
        try {
            $pdo = new PDO("mysql:host=localhost;dbname={$dbName};charset=utf8mb4", "root", "", [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            $stmt = $pdo->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->output("SQL查询失败: " . $e->getMessage(), 'error');
            return false;
        }
    }

    private function testTableStructure() {
        $this->output("4. 检查表结构:", 'info');
        
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=wallpaper_db;charset=utf8mb4", "root", "", [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            
            $requiredTables = ['users', 'sessions', 'user_status_ext', 'wallpapers'];
            $existingTables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($requiredTables as $table) {
                if (in_array($table, $existingTables)) {
                    $this->output("   表 {$table}: 存在", 'success');
                    
                    // 检查字段数量
                    $columns = $pdo->query("DESCRIBE {$table}")->fetchAll();
                    $this->output("     字段数: " . count($columns), 'info');
                    
                    // 检查记录数
                    $count = $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
                    $this->output("     记录数: {$count}", 'info');
                    
                } else {
                    $this->output("   表 {$table}: 不存在", 'error');
                }
            }
            
            $this->tests['tables'] = true;
            
        } catch (PDOException $e) {
            $this->output("   检查失败: " . $e->getMessage(), 'error');
            $this->tests['tables'] = false;
        }
        
        $this->output("", 'info');
    }
    
    /**
     * 性能测试
     */
    private function testPerformance() {
        $this->output("5. 性能测试:", 'info');
        
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=wallpaper_db;charset=utf8mb4", "root", "", [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            
            // 测试简单查询性能
            $startTime = microtime(true);
            for ($i = 0; $i < 10; $i++) {
                $pdo->query("SELECT 1")->fetch();
            }
            $endTime = microtime(true);
            
            $avgTime = round((($endTime - $startTime) / 10) * 1000, 2);
            $this->output("   简单查询平均耗时: {$avgTime}ms", 'info');
            
            // 测试连接池
            $startTime = microtime(true);
            for ($i = 0; $i < 5; $i++) {
                $tempPdo = new PDO("mysql:host=localhost;dbname=wallpaper_db;charset=utf8mb4", "root", "");
                $tempPdo = null;
            }
            $endTime = microtime(true);
            
            $avgConnTime = round((($endTime - $startTime) / 5) * 1000, 2);
            $this->output("   连接建立平均耗时: {$avgConnTime}ms", 'info');
            
            $this->tests['performance'] = true;
            
        } catch (PDOException $e) {
            $this->output("   性能测试失败: " . $e->getMessage(), 'error');
            $this->tests['performance'] = false;
        }
        
        $this->output("", 'info');
    }
    
    /**
     * 显示测试总结
     */
    private function showSummary() {
        $this->output("测试总结:", 'info');
        
        $passed = 0;
        $total = count($this->tests);
        
        foreach ($this->tests as $test => $result) {
            if ($result) {
                $passed++;
            }
        }
        
        if ($passed === $total) {
            $this->output("   所有测试通过 ({$passed}/{$total})", 'success');
            $this->output("   数据库连接正常，可以开始开发工作", 'success');
        } else {
            $this->output("   部分测试失败 ({$passed}/{$total})", 'error');
            $this->output("   请检查失败的项目并修复后重试", 'error');
        }
        
        // 显示系统信息
        $this->output("", 'info');
        $this->output("系统信息:", 'info');
        $this->output("   PHP版本: " . PHP_VERSION, 'info');
        $this->output("   操作系统: " . PHP_OS, 'info');
        $this->output("   当前时间: " . date('Y-m-d H:i:s'), 'info');
        
        if ($this->isWebMode) {
            $this->output("", 'info');
            $this->output("<a href='?refresh=1'>重新测试</a>", 'info');
        }
    }
    
    /**
     * 生成配置建议
     */
    public function generateConfigSuggestions() {
        $this->output("", 'info');
        $this->output("配置建议:", 'info');
        
        if (!$this->tests['pdo'] || !$this->tests['pdo_mysql']) {
            $this->output("   请在php.ini中启用PDO和PDO_MySQL扩展", 'error');
        }
        
        if (!$this->tests['connection']) {
            $this->output("   请检查XAMPP中的MySQL服务是否启动", 'error');
            $this->output("   请确认MySQL端口3306未被占用", 'error');
        }
        
        if (!$this->tests['database']) {
            $this->output("   建议运行: php db_quick_tools.php create", 'info');
        }
        
        $this->output("   推荐的PDO连接配置:", 'info');
        if ($this->isWebMode) {
            echo "<pre>";
        }
        $config = '
$pdo = new PDO(
    "mysql:host=localhost;dbname=wallpaper_db;charset=utf8mb4",
    "root",
    "",
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
    ]
);';
        echo $config;
        if ($this->isWebMode) {
            echo "</pre>";
        } else {
            echo "\n";
        }
    }
}

// 执行测试
$tester = new ConnectionTester();
$tester->runAllTests();
$tester->generateConfigSuggestions();

?>
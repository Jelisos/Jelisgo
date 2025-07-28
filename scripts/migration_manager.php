<?php
/**
 * 文件: scripts/migration_manager.php
 * 描述: 数据库迁移管理器 - 第三阶段完整迁移
 * 功能: 数据备份、同步验证、配置切换、回滚管理
 * 创建时间: 2025-01-27
 * 维护: AI助手
 */

require_once __DIR__ . '/../config/database.php';

class MigrationManager {
    private $conn;
    private $logFile;
    private $backupDir;
    
    public function __construct() {
        $this->logFile = __DIR__ . '/../logs/migration_' . date('Ymd_His') . '.log';
        $this->backupDir = __DIR__ . '/../backups/' . date('Ymd_His') . '/';
        
        // 创建必要目录
        if (!is_dir(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0755, true);
        }
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
        
        $this->log('Migration Manager initialized');
    }
    
    /**
     * 记录日志
     */
    private function log($message, $level = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] [{$level}] {$message}\n";
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
        echo $logEntry;
    }
    
    /**
     * 连接数据库
     */
    private function connectDatabase() {
        try {
            $this->conn = getDbConnection();
            if (!$this->conn) {
                throw new Exception('Database connection failed');
            }
            $this->log('Database connected successfully');
            return true;
        } catch (Exception $e) {
            $this->log('Database connection error: ' . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * 备份关键文件
     */
    public function backupFiles() {
        $this->log('Starting file backup...');
        
        $filesToBackup = [
            'static/data/list.json',
            'static/js/image-loader.js',
            'config/database.php',
            'index.php'
        ];
        
        $backupSuccess = true;
        
        foreach ($filesToBackup as $file) {
            $sourcePath = __DIR__ . '/../' . $file;
            $backupPath = $this->backupDir . $file;
            
            if (file_exists($sourcePath)) {
                // 创建备份目录
                $backupFileDir = dirname($backupPath);
                if (!is_dir($backupFileDir)) {
                    mkdir($backupFileDir, 0755, true);
                }
                
                if (copy($sourcePath, $backupPath)) {
                    $this->log("Backed up: {$file}");
                } else {
                    $this->log("Failed to backup: {$file}", 'ERROR');
                    $backupSuccess = false;
                }
            } else {
                $this->log("File not found for backup: {$file}", 'WARNING');
            }
        }
        
        return $backupSuccess;
    }
    
    /**
     * 备份数据库
     */
    public function backupDatabase() {
        $this->log('Starting database backup...');
        
        $backupFile = $this->backupDir . 'wallpaper_db_backup.sql';
        
        // 使用mysqldump命令备份数据库
        $command = "mysqldump -u root -h localhost wallpaper_db > \"{$backupFile}\"";
        
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0 && file_exists($backupFile)) {
            $this->log('Database backup completed: ' . $backupFile);
            return true;
        } else {
            $this->log('Database backup failed', 'ERROR');
            return false;
        }
    }
    
    /**
     * 验证数据完整性
     */
    public function validateDataIntegrity() {
        $this->log('Starting data integrity validation...');
        
        if (!$this->connectDatabase()) {
            return false;
        }
        
        // 读取list.json数据
        $jsonPath = __DIR__ . '/../static/data/list.json';
        if (!file_exists($jsonPath)) {
            $this->log('list.json file not found', 'ERROR');
            return false;
        }
        
        $jsonData = json_decode(file_get_contents($jsonPath), true);
        if (!$jsonData) {
            $this->log('Failed to parse list.json', 'ERROR');
            return false;
        }
        
        $this->log('JSON data loaded: ' . count($jsonData) . ' records');
        
        // 查询数据库数据
        $sql = "SELECT COUNT(*) as count FROM wallpapers";
        $result = $this->conn->query($sql);
        $dbCount = $result->fetch_assoc()['count'];
        
        $this->log('Database records: ' . $dbCount);
        
        // 比较数据量
        if (count($jsonData) !== intval($dbCount)) {
            $this->log('Data count mismatch: JSON=' . count($jsonData) . ', DB=' . $dbCount, 'WARNING');
        }
        
        // 抽样验证数据一致性
        $sampleSize = min(10, count($jsonData));
        $validationErrors = 0;
        
        for ($i = 0; $i < $sampleSize; $i++) {
            $jsonRecord = $jsonData[$i];
            $id = $jsonRecord['id'];
            
            $sql = "SELECT * FROM wallpapers WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('s', $id);
            $stmt->execute();
            $dbRecord = $stmt->get_result()->fetch_assoc();
            
            if (!$dbRecord) {
                $this->log("Record not found in DB: ID={$id}", 'ERROR');
                $validationErrors++;
                continue;
            }
            
            // 验证关键字段
            $fieldsToCheck = [
                'title' => 'name',
                'file_path' => 'path',
                'width' => 'width',
                'height' => 'height',
                'category' => 'category'
            ];
            
            foreach ($fieldsToCheck as $dbField => $jsonField) {
                if (isset($jsonRecord[$jsonField]) && $dbRecord[$dbField] != $jsonRecord[$jsonField]) {
                    $this->log("Data mismatch for ID={$id}, field={$dbField}: DB='{$dbRecord[$dbField]}', JSON='{$jsonRecord[$jsonField]}'", 'WARNING');
                }
            }
        }
        
        $this->log("Data validation completed. Errors: {$validationErrors}");
        return $validationErrors === 0;
    }
    
    /**
     * 同步数据到数据库
     */
    public function syncDataToDatabase() {
        $this->log('Starting data synchronization...');
        
        if (!$this->connectDatabase()) {
            return false;
        }
        
        // 读取list.json数据
        $jsonPath = __DIR__ . '/../static/data/list.json';
        $jsonData = json_decode(file_get_contents($jsonPath), true);
        
        $insertedCount = 0;
        $updatedCount = 0;
        $errorCount = 0;
        
        foreach ($jsonData as $record) {
            try {
                // 检查记录是否存在
                $checkSql = "SELECT COUNT(*) as count FROM wallpapers WHERE id = ?";
                $checkStmt = $this->conn->prepare($checkSql);
                $checkStmt->bind_param('s', $record['id']);
                $checkStmt->execute();
                $exists = $checkStmt->get_result()->fetch_assoc()['count'] > 0;
                
                if ($exists) {
                    // 更新记录
                    $updateSql = "UPDATE wallpapers SET 
                                    title = ?, description = ?, file_path = ?, file_size = ?,
                                    width = ?, height = ?, category = ?, tags = ?, format = ?,
                                    updated_at = NOW()
                                  WHERE id = ?";
                    $updateStmt = $this->conn->prepare($updateSql);
                    $tags = json_encode($record['tags'] ?? []);
                    $title = $record['name'];
                    $description = $record['description'] ?? '';
                    $file_path = $record['path'];
                    $file_size = $record['size'] ?? '';
                    $width = $record['width'] ?? 0;
                    $height = $record['height'] ?? 0;
                    $category = $record['category'] ?? '';
                    $format = $record['format'] ?? '';
                    $id = $record['id'];
                    
                    $updateStmt->bind_param('ssssiiisss',
                        $title,
                        $description,
                        $file_path,
                        $file_size,
                        $width,
                        $height,
                        $category,
                        $tags,
                        $format,
                        $id
                    );
                    
                    if ($updateStmt->execute()) {
                        $updatedCount++;
                    } else {
                        $this->log("Failed to update record ID={$record['id']}: " . $updateStmt->error, 'ERROR');
                        $errorCount++;
                    }
                } else {
                    // 插入新记录
                    $insertSql = "INSERT INTO wallpapers 
                                    (id, title, description, file_path, file_size, width, height, 
                                     category, tags, format, views, likes, created_at, updated_at)
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, NOW(), NOW())";
                    $insertStmt = $this->conn->prepare($insertSql);
                    $tags = json_encode($record['tags'] ?? []);
                    
                    $id = $record['id'];
                    $title = $record['name'];
                    $description = $record['description'] ?? '';
                    $file_path = $record['path'];
                    $file_size = $record['size'] ?? '';
                    $width = $record['width'] ?? 0;
                    $height = $record['height'] ?? 0;
                    $category = $record['category'] ?? '';
                    $format = $record['format'] ?? '';
                    
                    $insertStmt->bind_param('sssssiiiss',
                        $id,
                        $title,
                        $description,
                        $file_path,
                        $file_size,
                        $width,
                        $height,
                        $category,
                        $tags,
                        $format
                    );
                    
                    if ($insertStmt->execute()) {
                        $insertedCount++;
                    } else {
                        $this->log("Failed to insert record ID={$record['id']}: " . $insertStmt->error, 'ERROR');
                        $errorCount++;
                    }
                }
            } catch (Exception $e) {
                $this->log("Error processing record ID={$record['id']}: " . $e->getMessage(), 'ERROR');
                $errorCount++;
            }
        }
        
        $this->log("Data sync completed. Inserted: {$insertedCount}, Updated: {$updatedCount}, Errors: {$errorCount}");
        return $errorCount === 0;
    }
    
    /**
     * 更新前端配置
     */
    public function updateFrontendConfig() {
        $this->log('Updating frontend configuration...');
        
        // 创建配置文件
        $configContent = "// 数据库迁移配置 - 自动生成\n";
        $configContent .= "// 生成时间: " . date('Y-m-d H:i:s') . "\n";
        $configContent .= "const MIGRATION_CONFIG = {\n";
        $configContent .= "    USE_DATABASE_API: true,\n";
        $configContent .= "    API_BASE_URL: '/api/',\n";
        $configContent .= "    WALLPAPER_DATA_ENDPOINT: 'wallpaper_data.php',\n";
        $configContent .= "    IMAGE_PROXY_ENDPOINT: 'image_proxy.php',\n";
        $configContent .= "    FALLBACK_TO_JSON: false,\n";
        $configContent .= "    MIGRATION_COMPLETED: true,\n";
        $configContent .= "    MIGRATION_DATE: '" . date('Y-m-d H:i:s') . "'\n";
        $configContent .= "};\n";
        
        $configPath = __DIR__ . '/../static/js/migration-config.js';
        
        if (file_put_contents($configPath, $configContent)) {
            $this->log('Frontend configuration updated: ' . $configPath);
            return true;
        } else {
            $this->log('Failed to update frontend configuration', 'ERROR');
            return false;
        }
    }
    
    /**
     * 执行完整迁移
     */
    public function executeMigration() {
        $this->log('=== Starting Complete Database Migration ===');
        
        $steps = [
            'backupFiles' => 'Backup Files',
            'backupDatabase' => 'Backup Database', 
            'syncDataToDatabase' => 'Sync Data to Database',
            'validateDataIntegrity' => 'Validate Data Integrity',
            'updateFrontendConfig' => 'Update Frontend Configuration'
        ];
        
        $results = [];
        
        foreach ($steps as $method => $description) {
            $this->log("--- Step: {$description} ---");
            $startTime = microtime(true);
            
            $result = $this->$method();
            $results[$method] = $result;
            
            $duration = round(microtime(true) - $startTime, 2);
            $status = $result ? 'SUCCESS' : 'FAILED';
            
            $this->log("Step '{$description}' completed in {$duration}s - Status: {$status}");
            
            if (!$result) {
                $this->log("Migration failed at step: {$description}", 'ERROR');
                return false;
            }
        }
        
        $this->log('=== Database Migration Completed Successfully ===');
        return true;
    }
    
    /**
     * 生成迁移报告
     */
    public function generateReport() {
        $reportPath = $this->backupDir . 'migration_report.html';
        
        $html = "<!DOCTYPE html>\n<html>\n<head>\n";
        $html .= "<title>Database Migration Report</title>\n";
        $html .= "<style>body{font-family:Arial,sans-serif;margin:20px;}h1{color:#333;}table{border-collapse:collapse;width:100%;}th,td{border:1px solid #ddd;padding:8px;text-align:left;}th{background-color:#f2f2f2;}.success{color:green;}.error{color:red;}</style>\n";
        $html .= "</head>\n<body>\n";
        $html .= "<h1>Database Migration Report</h1>\n";
        $html .= "<p><strong>Migration Date:</strong> " . date('Y-m-d H:i:s') . "</p>\n";
        $html .= "<p><strong>Log File:</strong> " . basename($this->logFile) . "</p>\n";
        $html .= "<p><strong>Backup Directory:</strong> " . $this->backupDir . "</p>\n";
        
        // 读取日志内容
        if (file_exists($this->logFile)) {
            $logContent = file_get_contents($this->logFile);
            $html .= "<h2>Migration Log</h2>\n";
            $html .= "<pre style='background:#f5f5f5;padding:10px;overflow:auto;max-height:400px;'>" . htmlspecialchars($logContent) . "</pre>\n";
        }
        
        $html .= "</body>\n</html>";
        
        file_put_contents($reportPath, $html);
        $this->log('Migration report generated: ' . $reportPath);
        
        return $reportPath;
    }
}

// 如果直接运行此脚本
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    echo "Database Migration Manager\n";
    echo "========================\n\n";
    
    $manager = new MigrationManager();
    
    if ($manager->executeMigration()) {
        echo "\n✅ Migration completed successfully!\n";
        $reportPath = $manager->generateReport();
        echo "📄 Report generated: {$reportPath}\n";
    } else {
        echo "\n❌ Migration failed! Check logs for details.\n";
    }
}
?>
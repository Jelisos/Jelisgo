<?php
/**
 * 创建预览图目录结构脚本
 * 用于三视图重构 - 第二阶段
 */

require_once __DIR__ . '/../config/database.php';

class PreviewDirectoryCreator {
    private $basePreviewPath;
    private $logFile;
    
    public function __construct() {
        $this->basePreviewPath = __DIR__ . '/../static/preview';
        $this->logFile = __DIR__ . '/../logs/preview_directory_creation.log';
        
        // 确保日志目录存在
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    /**
     * 创建预览图目录结构
     */
    public function createDirectories() {
        $this->log("开始创建预览图目录结构...");
        
        try {
            // 确保基础预览目录存在
            if (!is_dir($this->basePreviewPath)) {
                if (!mkdir($this->basePreviewPath, 0755, true)) {
                    throw new Exception("无法创建基础预览目录: {$this->basePreviewPath}");
                }
                $this->log("创建基础预览目录: {$this->basePreviewPath}");
            }
            
            $createdDirs = 0;
            $existingDirs = 0;
            
            // 创建 001-100 目录
            for ($i = 1; $i <= 100; $i++) {
                $dirName = sprintf('%03d', $i);
                $dirPath = $this->basePreviewPath . '/' . $dirName;
                
                if (!is_dir($dirPath)) {
                    if (mkdir($dirPath, 0755)) {
                        $createdDirs++;
                        if ($createdDirs <= 10 || $createdDirs % 10 === 0) {
                            $this->log("创建目录: {$dirName}");
                        }
                    } else {
                        $this->log("警告: 无法创建目录 {$dirName}", 'WARNING');
                    }
                } else {
                    $existingDirs++;
                }
            }
            
            $this->log("目录创建完成!");
            $this->log("新创建目录: {$createdDirs} 个");
            $this->log("已存在目录: {$existingDirs} 个");
            $this->log("总计目录: " . ($createdDirs + $existingDirs) . " 个");
            
            // 验证目录结构
            $this->verifyDirectories();
            
            return [
                'success' => true,
                'created' => $createdDirs,
                'existing' => $existingDirs,
                'total' => $createdDirs + $existingDirs
            ];
            
        } catch (Exception $e) {
            $this->log("错误: " . $e->getMessage(), 'ERROR');
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 验证目录结构
     */
    private function verifyDirectories() {
        $this->log("验证目录结构...");
        
        $missingDirs = [];
        $invalidPerms = [];
        
        for ($i = 1; $i <= 100; $i++) {
            $dirName = sprintf('%03d', $i);
            $dirPath = $this->basePreviewPath . '/' . $dirName;
            
            if (!is_dir($dirPath)) {
                $missingDirs[] = $dirName;
            } elseif (!is_writable($dirPath)) {
                $invalidPerms[] = $dirName;
            }
        }
        
        if (empty($missingDirs) && empty($invalidPerms)) {
            $this->log("✅ 目录结构验证通过");
        } else {
            if (!empty($missingDirs)) {
                $this->log("❌ 缺失目录: " . implode(', ', $missingDirs), 'ERROR');
            }
            if (!empty($invalidPerms)) {
                $this->log("❌ 权限错误目录: " . implode(', ', $invalidPerms), 'WARNING');
            }
        }
    }
    
    /**
     * 获取目录统计信息
     */
    public function getDirectoryStats() {
        $stats = [
            'base_path' => $this->basePreviewPath,
            'base_exists' => is_dir($this->basePreviewPath),
            'base_writable' => is_writable($this->basePreviewPath),
            'subdirs' => []
        ];
        
        if ($stats['base_exists']) {
            for ($i = 1; $i <= 100; $i++) {
                $dirName = sprintf('%03d', $i);
                $dirPath = $this->basePreviewPath . '/' . $dirName;
                
                $stats['subdirs'][$dirName] = [
                    'exists' => is_dir($dirPath),
                    'writable' => is_dir($dirPath) ? is_writable($dirPath) : false,
                    'path' => $dirPath
                ];
            }
        }
        
        return $stats;
    }
    
    /**
     * 清理空的预览图目录
     */
    public function cleanupEmptyDirectories() {
        $this->log("开始清理空目录...");
        
        $removedDirs = 0;
        
        for ($i = 1; $i <= 100; $i++) {
            $dirName = sprintf('%03d', $i);
            $dirPath = $this->basePreviewPath . '/' . $dirName;
            
            if (is_dir($dirPath)) {
                $files = scandir($dirPath);
                $files = array_diff($files, ['.', '..']);
                
                if (empty($files)) {
                    if (rmdir($dirPath)) {
                        $removedDirs++;
                        $this->log("删除空目录: {$dirName}");
                    }
                }
            }
        }
        
        $this->log("清理完成，删除了 {$removedDirs} 个空目录");
        return $removedDirs;
    }
    
    /**
     * 记录日志
     */
    private function log($message, $level = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // 同时输出到控制台
        echo $logEntry;
    }
}

// 命令行执行
if (php_sapi_name() === 'cli') {
    echo "=== 预览图目录创建工具 ===\n\n";
    
    $creator = new PreviewDirectoryCreator();
    
    // 显示当前状态
    echo "检查当前目录状态...\n";
    $stats = $creator->getDirectoryStats();
    
    if ($stats['base_exists']) {
        $existingCount = count(array_filter($stats['subdirs'], function($dir) {
            return $dir['exists'];
        }));
        echo "基础目录存在，当前有 {$existingCount} 个子目录\n\n";
    } else {
        echo "基础目录不存在\n\n";
    }
    
    // 创建目录
    echo "开始创建目录结构...\n";
    $result = $creator->createDirectories();
    
    if ($result['success']) {
        echo "\n✅ 目录创建成功!\n";
        echo "新创建: {$result['created']} 个\n";
        echo "已存在: {$result['existing']} 个\n";
        echo "总计: {$result['total']} 个\n";
    } else {
        echo "\n❌ 目录创建失败: {$result['error']}\n";
        exit(1);
    }
    
    echo "\n=== 完成 ===\n";
}

// Web访问
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
    
    $creator = new PreviewDirectoryCreator();
    $action = $_GET['action'] ?? 'create';
    
    switch ($action) {
        case 'create':
            $result = $creator->createDirectories();
            break;
            
        case 'stats':
            $result = $creator->getDirectoryStats();
            break;
            
        case 'cleanup':
            $removed = $creator->cleanupEmptyDirectories();
            $result = ['success' => true, 'removed' => $removed];
            break;
            
        default:
            $result = ['success' => false, 'error' => 'Invalid action'];
    }
    
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>
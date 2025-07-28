<?php
/**
 * 多期图片目录扫描脚本
 * 扫描static/wallpapers/下的所有期数目录（001、002、003等）
 * 并检查每个目录中的图片文件，确保数据库数据完整性
 * 文件位置：/scripts/sql_test/scan_multi_period_directories.php
 */

require_once __DIR__ . '/../../config/database.php';

class MultiPeriodScanner {
    private $conn;
    private $baseDir;
    
    public function __construct() {
        $this->conn = getDbConnection();
        if (!$this->conn) {
            throw new Exception('数据库连接失败');
        }
        $this->baseDir = __DIR__ . '/../../static/wallpapers';
    }
    
    /**
     * 扫描所有期数目录
     */
    public function scanAllPeriods() {
        echo "开始扫描多期图片目录...\n";
        echo "基础目录: {$this->baseDir}\n\n";
        
        $periods = $this->findPeriodDirectories();
        
        if (empty($periods)) {
            echo "未找到任何期数目录\n";
            return;
        }
        
        echo "找到期数目录: " . implode(', ', $periods) . "\n\n";
        
        $totalFiles = 0;
        $totalDbRecords = 0;
        $missingInDb = [];
        $missingFiles = [];
        
        foreach ($periods as $period) {
            echo "=== 扫描期数 {$period} ===\n";
            $result = $this->scanPeriod($period);
            
            echo "文件数量: {$result['file_count']}\n";
            echo "数据库记录: {$result['db_count']}\n";
            
            if (!empty($result['missing_in_db'])) {
                echo "缺失数据库记录的文件: " . count($result['missing_in_db']) . " 个\n";
                $missingInDb = array_merge($missingInDb, $result['missing_in_db']);
            }
            
            if (!empty($result['missing_files'])) {
                echo "数据库中存在但文件缺失: " . count($result['missing_files']) . " 个\n";
                $missingFiles = array_merge($missingFiles, $result['missing_files']);
            }
            
            $totalFiles += $result['file_count'];
            $totalDbRecords += $result['db_count'];
            echo "\n";
        }
        
        // 输出汇总信息
        echo "=== 扫描汇总 ===\n";
        echo "总文件数: {$totalFiles}\n";
        echo "总数据库记录: {$totalDbRecords}\n";
        
        if (!empty($missingInDb)) {
            echo "\n需要添加到数据库的文件 (" . count($missingInDb) . " 个):\n";
            foreach ($missingInDb as $file) {
                echo "  - {$file}\n";
            }
        }
        
        if (!empty($missingFiles)) {
            echo "\n数据库中存在但文件缺失 (" . count($missingFiles) . " 个):\n";
            foreach ($missingFiles as $file) {
                echo "  - {$file}\n";
            }
        }
        
        if (empty($missingInDb) && empty($missingFiles)) {
            echo "\n✓ 所有期数目录的文件与数据库记录完全一致！\n";
        }
    }
    
    /**
     * 查找所有期数目录
     */
    private function findPeriodDirectories() {
        $periods = [];
        
        if (!is_dir($this->baseDir)) {
            throw new Exception("基础目录不存在: {$this->baseDir}");
        }
        
        $items = scandir($this->baseDir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $fullPath = $this->baseDir . '/' . $item;
            if (is_dir($fullPath) && preg_match('/^\d{3}$/', $item)) {
                $periods[] = $item;
            }
        }
        
        sort($periods);
        return $periods;
    }
    
    /**
     * 扫描单个期数目录
     */
    private function scanPeriod($period) {
        $periodDir = $this->baseDir . '/' . $period;
        $files = $this->getImageFiles($periodDir);
        
        // 查询数据库中该期数的记录
        $dbFiles = $this->getDbFiles($period);
        
        // 比较文件和数据库记录
        $missingInDb = array_diff($files, $dbFiles);
        $missingFiles = array_diff($dbFiles, $files);
        
        return [
            'file_count' => count($files),
            'db_count' => count($dbFiles),
            'missing_in_db' => array_values($missingInDb),
            'missing_files' => array_values($missingFiles)
        ];
    }
    
    /**
     * 获取目录中的图片文件
     */
    private function getImageFiles($dir) {
        $files = [];
        $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!is_dir($dir)) {
            return $files;
        }
        
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
            if (in_array($ext, $extensions)) {
                $files[] = $item;
            }
        }
        
        sort($files);
        return $files;
    }
    
    /**
     * 获取数据库中指定期数的文件
     */
    private function getDbFiles($period) {
        $sql = "SELECT file_path FROM wallpapers WHERE file_path LIKE ?";
        $stmt = $this->conn->prepare($sql);
        $pattern = "static/wallpapers/{$period}/%";
        $stmt->bind_param('s', $pattern);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $files = [];
        while ($row = $result->fetch_assoc()) {
            $filename = basename($row['file_path']);
            $files[] = $filename;
        }
        
        sort($files);
        return $files;
    }
}

try {
    $scanner = new MultiPeriodScanner();
    $scanner->scanAllPeriods();
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n多期目录扫描完成！\n";
?>
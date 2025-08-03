<?php
/**
 * 线上图片访问速度测试脚本
 * 测试从线上预览目录随机获取图片的响应速度
 */

header('Content-Type: text/html; charset=utf-8');
set_time_limit(300); // 设置5分钟超时

// Linux系统优化设置
if (function_exists('ini_set')) {
    ini_set('memory_limit', '256M');
    ini_set('max_execution_time', 300);
}

// 禁用输出缓冲以实时显示进度
if (ob_get_level()) {
    ob_end_flush();
}
ob_implicit_flush(true);

echo "<!DOCTYPE html>";
echo "<html lang='zh-CN'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>线上图片访问速度测试</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 20px; background: #f9f9f9; }";
echo ".test-image { max-width: 200px; max-height: 150px; border: 2px solid #ddd; border-radius: 8px; margin: 10px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }";
echo ".image-container { display: inline-block; margin: 10px; text-align: center; background: white; padding: 10px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }";
echo ".image-info { font-size: 12px; color: #666; margin-top: 5px; }";
echo ".gallery { display: flex; flex-wrap: wrap; gap: 15px; margin-top: 20px; }";
echo ".success { color: green; }";
echo ".error { color: red; }";
echo ".loading { color: orange; }";
echo "</style>";
echo "</head>";
echo "<body>";
echo "<h2>线上图片访问速度测试</h2>";
echo "<p>测试目标: https://www.jelisgo.cn</p>";
echo "<hr>";

// 测试配置
$testCount = 10; // 测试图片数量
$baseUrl = 'https://www.jelisgo.cn';

// 尝试引入数据库配置文件
$configLoaded = false;
if (file_exists(__DIR__ . '/config/database.php')) {
    require_once __DIR__ . '/config/database.php';
    $configLoaded = true;
} elseif (file_exists('./config/database.php')) {
    require_once './config/database.php';
    $configLoaded = true;
} elseif (file_exists('config/database.php')) {
    require_once 'config/database.php';
    $configLoaded = true;
}

// 如果配置文件加载失败，定义备用函数
if (!$configLoaded || !function_exists('isOnlineEnvironment')) {
    function isOnlineEnvironment() {
        $serverName = $_SERVER["SERVER_NAME"] ?? "";
        if (strpos($serverName, "jelisgo.cn") !== false || strpos($serverName, "www.jelisgo.cn") !== false) {
            return true;
        }
        $httpHost = $_SERVER["HTTP_HOST"] ?? "";
        if (strpos($httpHost, "jelisgo.cn") !== false || strpos($httpHost, "www.jelisgo.cn") !== false) {
            return true;
        }
        $docRoot = $_SERVER["DOCUMENT_ROOT"] ?? "";
        if (strpos($docRoot, "/www/wwwroot") !== false || 
            strpos($docRoot, "/var/www") !== false ||
            strpos($docRoot, "/home/") !== false) {
            return true;
        }
        return false;
    }
}

if (!$configLoaded || !function_exists('getPDOConnection')) {
    function getPDOConnection() {
        $isOnline = isOnlineEnvironment();
        
        if ($isOnline) {
            // 线上环境配置
            $host = 'localhost';
            $dbname = 'wallpaper_db';
            $username = 'root';
            $password = 'wodebz123';
        } else {
            // 本地环境配置
            $host = 'localhost';
            $dbname = 'wallpaper_db';
            $username = 'root';
            $password = '';
        }
        
        try {
            $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);
            return $pdo;
        } catch (PDOException $e) {
            echo "<p style='color: red;'>数据库连接失败: " . $e->getMessage() . "</p>";
            return null;
        }
    }
}

// 从数据库获取壁纸数据（线上数据库版）
function getRandomImages($count) {
    try {
        echo "<p>正在从线上数据库获取壁纸数据...</p>";
        
        // 获取数据库连接
        $pdo = getPDOConnection();
        if (!$pdo) {
            throw new Exception("无法连接到数据库");
        }
        
        // 检测环境
        $isOnline = isOnlineEnvironment();
        echo "<p>当前环境: " . ($isOnline ? "线上环境" : "本地环境") . "</p>";
        
        // 查询随机壁纸数据（修复LIMIT参数绑定问题）
        $count = intval($count); // 确保是整数
        $sql = "SELECT 
                    id,
                    title,
                    description,
                    file_path,
                    file_size,
                    width,
                    height,
                    category,
                    tags,
                    format,
                    views,
                    created_at
                FROM wallpapers 
                WHERE file_path IS NOT NULL 
                    AND file_path != '' 
                ORDER BY RAND() 
                LIMIT {$count}";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $wallpapers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($wallpapers)) {
            throw new Exception("数据库中未找到壁纸数据");
        }
        
        echo "<p>从数据库中获取到 " . count($wallpapers) . " 张壁纸数据</p>";
        
        $images = [];
        foreach ($wallpapers as $index => $wallpaper) {
            // 构建预览路径：将 static/wallpapers 转换为 static/preview
            $originalPath = $wallpaper['file_path'];
            $previewPath = str_replace('static/wallpapers/', 'static/preview/', $originalPath);
            
            // 确保路径格式正确（使用正斜杠）
            $previewPath = str_replace('\\', '/', $previewPath);
            
            $images[] = [
                'id' => $wallpaper['id'],
                'title' => $wallpaper['title'] ?: '未命名壁纸',
                'file_path' => $previewPath,
                'original_path' => $originalPath,
                'file_size' => $wallpaper['file_size'],
                'width' => $wallpaper['width'],
                'height' => $wallpaper['height'],
                'category' => $wallpaper['category'],
                'format' => $wallpaper['format'],
                'views' => $wallpaper['views'],
                'created_at' => $wallpaper['created_at']
            ];
        }
        
        echo "<p>成功转换为预览路径格式，准备测试线上访问速度</p>";
        return $images;
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>获取壁纸数据失败: " . $e->getMessage() . "</p>";
        return [];
    }
}

// 测试单个图片访问速度
function testImageAccess($imageData, $baseUrl) {
    $imageId = $imageData['id'];
    $title = $imageData['title'];
    $filePath = $imageData['file_path'];
    
    // 构建完整URL，对中文文件名进行URL编码
    $encodedPath = implode('/', array_map('rawurlencode', explode('/', $filePath)));
    $fullUrl = $baseUrl . '/' . $encodedPath;
    
    // 记录开始时间
    $startTime = microtime(true);
    
    // 初始化cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $fullUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true); // 只获取头部信息
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    
    // 执行请求
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $contentLength = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
    $totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    // 计算响应时间
    $endTime = microtime(true);
    $responseTimeMs = ($endTime - $startTime) * 1000;
    
    // 格式化文件大小
    $fileSizeKB = $contentLength > 0 ? round($contentLength / 1024, 1) : 0;
    
    // 构建结果
    $result = [
        'image_id' => $imageId,
        'title' => $title,
        'file_path' => $filePath,
        'full_url' => $fullUrl,
        'http_code' => $httpCode,
        'content_type' => $contentType,
        'content_length' => $contentLength,
        'file_size_kb' => $fileSizeKB,
        'response_time_ms' => round($responseTimeMs, 2),
        'curl_total_time' => round($totalTime * 1000, 2),
        'success' => ($httpCode == 200 && empty($error)),
        'error' => $error
    ];
    
    return $result;
}

// 格式化输出结果
function formatTestResult($result, $index) {
    $timestamp = date('H:i:s');
    $status = $result['success'] ? '✅ 成功' : '❌ 失败';
    $imageId = $result['image_id'];
    $title = $result['title'];
    $fileSizeKB = $result['file_size_kb'];
    $fullUrl = $result['full_url'];
    
    echo "<div class='image-container'>";
    
    if ($result['success']) {
        echo "<p class='success'>[{$timestamp}] {$status} 图片访问 #{$imageId}: 图片可正常访问 ({$fileSizeKB}KB)</p>";
        echo "<img src='{$fullUrl}' alt='{$title}' class='test-image' onload='this.style.border=\"2px solid green\"' onerror='this.style.border=\"2px solid red\"; this.alt=\"加载失败\";'>";
        echo "<div class='image-info'>";
        echo "<strong>{$title}</strong><br>";
        echo "响应时间: {$result['response_time_ms']}ms<br>";
        echo "文件大小: {$fileSizeKB}KB";
        echo "</div>";
    } else {
        echo "<p class='error'>[{$timestamp}] {$status} 图片访问 #{$imageId}: 访问失败</p>";
        echo "<div class='test-image' style='border: 2px solid red; display: flex; align-items: center; justify-content: center; background: #f5f5f5;'>";
        echo "<span style='color: red;'>加载失败</span>";
        echo "</div>";
        echo "<div class='image-info'>";
        echo "<strong>{$title}</strong><br>";
        echo "错误: {$result['error']}";
        echo "</div>";
    }
    
    // 详细信息（可折叠）
    $detailsJson = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    echo "<details style='margin-top: 10px;'>";
    echo "<summary style='cursor: pointer; color: #666; font-size: 12px;'>详细信息</summary>";
    echo "<pre style='font-size: 10px; color: #666; background: #f0f0f0; padding: 5px; border-radius: 3px; overflow-x: auto;'>{$detailsJson}</pre>";
    echo "</details>";
    
    echo "</div>";
}

// 开始测试
echo "<h3>开始测试 {$testCount} 张随机图片...</h3>";
echo "<div style='font-family: monospace; background: #f5f5f5; padding: 10px; border-radius: 5px;'>";

$images = getRandomImages($testCount);

if (empty($images)) {
    echo "<p style='color: red;'>未能获取到图片数据，测试终止。</p>";
    exit;
}

$successCount = 0;
$totalResponseTime = 0;
$totalFileSize = 0;
$results = [];

foreach ($images as $index => $imageData) {
    $result = testImageAccess($imageData, $baseUrl);
    $results[] = $result;
    
    formatTestResult($result, $index + 1);
    
    if ($result['success']) {
        $successCount++;
        $totalResponseTime += $result['response_time_ms'];
        $totalFileSize += $result['file_size_kb'];
    }
    
    // 延迟机制已移除，加快测试速度
}

echo "</div>";

// 统计结果
echo "<h3>测试统计</h3>";
echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
echo "<tr><th>项目</th><th>数值</th></tr>";
echo "<tr><td>总测试数量</td><td>{$testCount}</td></tr>";
echo "<tr><td>成功数量</td><td style='color: green;'>{$successCount}</td></tr>";
echo "<tr><td>失败数量</td><td style='color: red;'>" . ($testCount - $successCount) . "</td></tr>";
echo "<tr><td>成功率</td><td>" . round(($successCount / $testCount) * 100, 1) . "%</td></tr>";

if ($successCount > 0) {
    $avgResponseTime = round($totalResponseTime / $successCount, 2);
    $avgFileSize = round($totalFileSize / $successCount, 1);
    echo "<tr><td>平均响应时间</td><td>{$avgResponseTime}ms</td></tr>";
    echo "<tr><td>平均文件大小</td><td>{$avgFileSize}KB</td></tr>";
}

echo "</table>";

// 性能分析
if ($successCount > 0) {
    echo "<h3>性能分析</h3>";
    
    $responseTimes = array_map(function($r) { return $r['success'] ? $r['response_time_ms'] : 0; }, $results);
    $responseTimes = array_filter($responseTimes);
    
    if (!empty($responseTimes)) {
        sort($responseTimes);
        $minTime = min($responseTimes);
        $maxTime = max($responseTimes);
        $medianTime = $responseTimes[floor(count($responseTimes) / 2)];
        
        echo "<ul>";
        echo "<li>最快响应时间: {$minTime}ms</li>";
        echo "<li>最慢响应时间: {$maxTime}ms</li>";
        echo "<li>中位数响应时间: {$medianTime}ms</li>";
        
        // 性能评级
        $avgTime = round(array_sum($responseTimes) / count($responseTimes), 2);
        if ($avgTime < 200) {
            echo "<li style='color: green;'>性能评级: 优秀 (平均 {$avgTime}ms)</li>";
        } elseif ($avgTime < 500) {
            echo "<li style='color: orange;'>性能评级: 良好 (平均 {$avgTime}ms)</li>";
        } else {
            echo "<li style='color: red;'>性能评级: 需要优化 (平均 {$avgTime}ms)</li>";
        }
        echo "</ul>";
    }
}

// 显示成功加载的图片画廊
if ($successCount > 0) {
    echo "<h3>成功加载的图片画廊</h3>";
    echo "<div class='gallery'>";
    
    foreach ($results as $result) {
        if ($result['success']) {
            $fullUrl = $result['full_url'];
            $title = $result['title'];
            $imageId = $result['image_id'];
            $responseTime = $result['response_time_ms'];
            $fileSizeKB = $result['file_size_kb'];
            
            echo "<div class='image-container'>";
            echo "<img src='{$fullUrl}' alt='{$title}' class='test-image' style='border: 2px solid green;'>";
            echo "<div class='image-info'>";
            echo "<strong>#{$imageId}</strong><br>";
            echo "{$title}<br>";
            echo "<span style='color: green;'>{$responseTime}ms</span> | {$fileSizeKB}KB";
            echo "</div>";
            echo "</div>";
        }
    }
    
    echo "</div>";
}

echo "<h3>测试完成</h3>";
echo "<p>测试时间: " . date('Y-m-d H:i:s') . "</p>";
echo "</body>";
echo "</html>";
?>
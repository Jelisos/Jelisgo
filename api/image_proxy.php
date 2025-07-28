<?php
/**
 * 文件: api/image_proxy.php
 * 描述: 图片代理接口 - 数据库迁移第二阶段
 * 功能: 实现图片访问控制、防盗链保护和访问日志记录
 * 创建时间: 2025-01-27
 * 维护: AI助手
 */

// 安全响应头
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// 日志记录函数
function logImageAccess($imagePath, $result = 'success', $error = '') {
    $logFile = __DIR__ . '/../logs/image_proxy.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $referer = $_SERVER['HTTP_REFERER'] ?? 'direct';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $logData = [
        'timestamp' => $timestamp,
        'ip' => $ip,
        'image' => $imagePath,
        'referer' => $referer,
        'result' => $result,
        'error' => $error,
        'user_agent' => substr($userAgent, 0, 200)
    ];
    
    file_put_contents($logFile, json_encode($logData, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
}

// 防盗链检查
function checkReferer() {
    $allowedDomains = [
        'localhost',
        '127.0.0.1',
        $_SERVER['HTTP_HOST'] ?? ''
    ];
    
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    
    // 如果没有referer，允许直接访问（某些浏览器或应用可能不发送referer）
    if (empty($referer)) {
        return true;
    }
    
    $refererHost = parse_url($referer, PHP_URL_HOST);
    
    return in_array($refererHost, $allowedDomains);
}

// 获取图片MIME类型
function getImageMimeType($filePath) {
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    
    $mimeTypes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'bmp' => 'image/bmp',
        'svg' => 'image/svg+xml'
    ];
    
    return $mimeTypes[$extension] ?? 'application/octet-stream';
}

// 图片压缩和优化
function optimizeImage($imagePath, $quality = 85, $maxWidth = null, $maxHeight = null) {
    // 检查GD扩展是否可用
    if (!extension_loaded('gd')) {
        return $imagePath; // GD扩展不可用，返回原文件
    }
    
    if (!file_exists($imagePath)) {
        return false;
    }
    
    $imageInfo = getimagesize($imagePath);
    if (!$imageInfo) {
        return false;
    }
    
    $originalWidth = $imageInfo[0];
    $originalHeight = $imageInfo[1];
    $mimeType = $imageInfo['mime'];
    
    // 如果不需要调整大小且质量为原始质量，直接返回原文件
    if (!$maxWidth && !$maxHeight && $quality >= 95) {
        return $imagePath;
    }
    
    // 计算新尺寸
    $newWidth = $originalWidth;
    $newHeight = $originalHeight;
    
    if ($maxWidth || $maxHeight) {
        $ratio = $originalWidth / $originalHeight;
        
        if ($maxWidth && $maxHeight) {
            if ($originalWidth > $maxWidth || $originalHeight > $maxHeight) {
                if ($ratio > $maxWidth / $maxHeight) {
                    $newWidth = $maxWidth;
                    $newHeight = $maxWidth / $ratio;
                } else {
                    $newHeight = $maxHeight;
                    $newWidth = $maxHeight * $ratio;
                }
            }
        } elseif ($maxWidth && $originalWidth > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = $maxWidth / $ratio;
        } elseif ($maxHeight && $originalHeight > $maxHeight) {
            $newHeight = $maxHeight;
            $newWidth = $maxHeight * $ratio;
        }
    }
    
    // 创建缓存文件名
    $cacheDir = __DIR__ . '/../static/cache/images/';
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    
    $cacheKey = md5($imagePath . $quality . $newWidth . $newHeight);
    $cacheFile = $cacheDir . $cacheKey . '.jpg';
    
    // 检查缓存
    if (file_exists($cacheFile) && filemtime($cacheFile) > filemtime($imagePath)) {
        return $cacheFile;
    }
    
    // 创建图像资源
    $source = null;
    switch ($mimeType) {
        case 'image/jpeg':
            if (function_exists('imagecreatefromjpeg')) {
                $source = imagecreatefromjpeg($imagePath);
            }
            break;
        case 'image/png':
            if (function_exists('imagecreatefrompng')) {
                $source = imagecreatefrompng($imagePath);
            }
            break;
        case 'image/gif':
            if (function_exists('imagecreatefromgif')) {
                $source = imagecreatefromgif($imagePath);
            }
            break;
        case 'image/webp':
            if (function_exists('imagecreatefromwebp')) {
                $source = imagecreatefromwebp($imagePath);
            }
            break;
        default:
            return $imagePath; // 不支持的格式，返回原文件
    }
    
    if (!$source) {
        return $imagePath;
    }
    
    // 创建新图像
    if (!function_exists('imagecreatetruecolor')) {
        imagedestroy($source);
        return $imagePath;
    }
    
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    if (!$newImage) {
        imagedestroy($source);
        return $imagePath;
    }
    
    // 保持透明度（PNG/GIF）
    if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
        if (function_exists('imagealphablending') && function_exists('imagesavealpha') && function_exists('imagecolorallocatealpha') && function_exists('imagefill')) {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefill($newImage, 0, 0, $transparent);
        }
    }
    
    // 调整图像大小
    if (!function_exists('imagecopyresampled')) {
        imagedestroy($source);
        imagedestroy($newImage);
        return $imagePath;
    }
    
    imagecopyresampled($newImage, $source, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
    
    // 保存优化后的图像
    $success = false;
    if (function_exists('imagejpeg')) {
        $success = imagejpeg($newImage, $cacheFile, $quality);
    }
    
    // 清理内存
    if (function_exists('imagedestroy')) {
        imagedestroy($source);
        imagedestroy($newImage);
    }
    
    return $success ? $cacheFile : $imagePath;
}

// 引入数据库配置（用于Token验证）
require_once __DIR__ . '/../config/database.php';

/**
 * Token验证函数
 * @param string $token Token字符串
 * @return array|null 返回图片信息或null
 */
function validateImageToken($token) {
    try {
        $pdo = getPDOConnection();
        if (!$pdo) {
            return null;
        }
        
        $sql = "SELECT wallpaper_id, image_path, path_type FROM image_tokens WHERE token = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Token验证失败: ' . $e->getMessage());
        return null;
    }
}

try {
    // 获取请求参数
    $imagePath = $_GET['path'] ?? '';
    $token = $_GET['token'] ?? '';
    $quality = min(100, max(10, intval($_GET['quality'] ?? 85)));
    $maxWidth = !empty($_GET['w']) ? max(50, min(2000, intval($_GET['w']))) : null;
    $maxHeight = !empty($_GET['h']) ? max(50, min(2000, intval($_GET['h']))) : null;
    $download = isset($_GET['download']);
    
    // Token验证模式
    if (!empty($token)) {
        $tokenInfo = validateImageToken($token);
        if (!$tokenInfo) {
            logImageAccess('token:' . $token, 'error', 'Invalid or expired token');
            http_response_code(403);
            echo 'Invalid or expired token';
            exit;
        }
        
        // 使用Token中的图片路径
        $imagePath = $tokenInfo['image_path'];
        
        // 检查是否来自yulan.php的原图请求
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $isYulanRequest = strpos($referer, 'yulan.php') !== false;
        
        // 如果是yulan.php请求且path_type为original，保持原图路径不变
        if ($isYulanRequest && $tokenInfo['path_type'] === 'original') {
            // 保持原图路径，不转换为预览图
            logImageAccess($imagePath, 'token_access', 'Token: ' . substr($token, 0, 8) . '... (yulan.php original access)');
        } elseif ($tokenInfo['path_type'] === 'original' && strpos($imagePath, '/wallpapers/') !== false) {
            // 其他情况下，original类型自动转换为preview路径
            $imagePath = str_replace('/wallpapers/', '/preview/', $imagePath);
            logImageAccess($imagePath, 'token_access', 'Token: ' . substr($token, 0, 8) . '... (auto-converted to preview)');
        } else {
            logImageAccess($imagePath, 'token_access', 'Token: ' . substr($token, 0, 8) . '...');
        }
    }
    
    // 验证图片路径
    if (empty($imagePath)) {
        logImageAccess('', 'error', 'Missing image path');
        http_response_code(400);
        echo 'Missing image path';
        exit;
    }
    
    // 安全检查：防止路径遍历攻击
    $imagePath = str_replace(['../', '..\\'], '', $imagePath);
    
    // 构建完整文件路径
    $fullPath = __DIR__ . '/../' . ltrim($imagePath, '/');
    $fullPath = realpath($fullPath);
    $basePath = realpath(__DIR__ . '/../');
    
    // 确保文件在项目目录内
    if (!$fullPath || strpos($fullPath, $basePath) !== 0) {
        logImageAccess($imagePath, 'error', 'Invalid path or path traversal attempt');
        http_response_code(403);
        echo 'Access denied';
        exit;
    }
    
    // 检查文件是否存在
    if (!file_exists($fullPath)) {
        logImageAccess($imagePath, 'error', 'File not found');
        http_response_code(404);
        echo 'Image not found';
        exit;
    }
    
    // 验证是否为图片文件
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
    $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
    
    if (!in_array($extension, $allowedExtensions)) {
        logImageAccess($imagePath, 'error', 'Invalid file type');
        http_response_code(403);
        echo 'Invalid file type';
        exit;
    }
    
    // 防盗链检查（Token访问跳过此检查）
    if (empty($token) && !checkReferer()) {
        logImageAccess($imagePath, 'blocked', 'Referer check failed');
        http_response_code(403);
        echo 'Access denied - Invalid referer';
        exit;
    }
    
    // 图片优化处理
    $optimizedPath = optimizeImage($fullPath, $quality, $maxWidth, $maxHeight);
    
    if (!$optimizedPath || !file_exists($optimizedPath)) {
        logImageAccess($imagePath, 'error', 'Image optimization failed');
        http_response_code(500);
        echo 'Image processing failed';
        exit;
    }
    
    // 设置缓存头
    $lastModified = filemtime($optimizedPath);
    $etag = md5_file($optimizedPath);
    
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
    header('ETag: "' . $etag . '"');
    header('Cache-Control: public, max-age=86400'); // 缓存1天
    
    // 检查客户端缓存
    $ifModifiedSince = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '';
    $ifNoneMatch = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
    
    if (($ifModifiedSince && strtotime($ifModifiedSince) >= $lastModified) ||
        ($ifNoneMatch && $ifNoneMatch === '"' . $etag . '"')) {
        http_response_code(304);
        exit;
    }
    
    // 设置内容类型
    $mimeType = getImageMimeType($optimizedPath);
    header('Content-Type: ' . $mimeType);
    
    // 设置文件大小
    $fileSize = filesize($optimizedPath);
    header('Content-Length: ' . $fileSize);
    
    // 如果是下载请求，设置下载头
    if ($download) {
        $filename = basename($imagePath);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
    } else {
        header('Content-Disposition: inline');
    }
    
    // 记录成功访问
    logImageAccess($imagePath, 'success');
    
    // 输出图片内容
    readfile($optimizedPath);
    
} catch (Exception $e) {
    logImageAccess($imagePath ?? '', 'error', $e->getMessage());
    http_response_code(500);
    echo 'Internal server error';
}
?>
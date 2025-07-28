<?php
/**
 * 文件: api/user_upload_wallpaper.php
 * 描述: 用户上传壁纸API - 存储到用户邮箱命名的文件夹
 * 维护: 用户上传壁纸功能相关修改请编辑此文件
 */

// 启动session（必须在ini_set之前）
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 引入数据库连接
require_once '../config/database.php';
require_once __DIR__ . '/auth_unified.php'; // 2025-01-27 新增：统一认证支持

// 获取数据库连接
$conn = getDBConnection();
if (!$conn) {
    sendResponse(500, '数据库连接失败');
}

// sendResponse函数已在utils.php中定义，此处移除重复定义

/**
 * 记录日志
 */
function logMessage($message) {
    $logFile = __DIR__ . '/../logs/user_upload_log.txt';
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    file_put_contents($logFile, date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL, FILE_APPEND);
}

/**
 * 验证图片文件
 */
function validateImageFile($file) {
    // 检查文件是否上传成功
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return '文件上传失败';
    }
    
    // 验证文件类型
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return '只允许上传 JPG, PNG, WebP 格式的图片';
    }
    
    // 验证文件大小 (10MB)
    $maxFileSize = 10 * 1024 * 1024;
    if ($file['size'] > $maxFileSize) {
        return '文件大小不能超过 10MB';
    }
    
    // 验证图片尺寸
    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return '无法读取图片信息';
    }
    
    return null; // 验证通过
}

/**
 * 生成安全的文件名
 */
function generateSafeFileName($originalName) {
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $timestamp = time();
    $randomString = bin2hex(random_bytes(8));
    return $timestamp . '_' . $randomString . '.' . $extension;
}

/**
 * 获取用户信息
 */
function getUserInfo($userId) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT id, username, email FROM users WHERE id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    return $user;
}

/**
 * 创建用户专属文件夹
 */
function createUserFolder($userEmail) {
    // 基础目录
    $baseDir = '../static/yhsc/';
    
    // 用户文件夹路径
    $userDir = $baseDir . $userEmail . '/';
    
    // 创建基础目录（如果不存在）
    if (!is_dir($baseDir)) {
        if (!mkdir($baseDir, 0755, true)) {
            return false;
        }
    }
    
    // 创建用户文件夹（如果不存在）
    if (!is_dir($userDir)) {
        if (!mkdir($userDir, 0755, true)) {
            return false;
        }
    }
    
    return $userDir;
}

/**
 * 验证用户身份 - 直接查询USERS表
 */
function validateUserFromDatabase($userId) {
    global $conn;
    
    if (!$userId) {
        return null;
    }
    
    $stmt = $conn->prepare("SELECT id, username, email, status FROM users WHERE id = ? AND status = 'active'");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    return $user;
}

/**
 * 处理用户壁纸上传
 */
function handleUserWallpaperUpload() {
    global $conn;
    
    try {
        // 直接从请求中获取用户ID进行数据库验证
        $userId = null;
        
        // 优先从 Authorization 头获取用户ID
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(\d+)/', $authHeader, $matches)) {
            $userId = intval($matches[1]);
        }
        
        // 如果没有Authorization头，尝试从POST参数获取
        if (!$userId && isset($_POST['user_id'])) {
            $userId = intval($_POST['user_id']);
        }
        
        if (!$userId) {
            sendResponse(401, '缺少用户身份信息，请重新登录');
        }
        
        // 直接从数据库验证用户身份
        $userInfo = validateUserFromDatabase($userId);
        if (!$userInfo) {
            sendResponse(401, '用户身份验证失败，请确认用户状态正常');
        }
        
        logMessage("用户 {$userId} ({$userInfo['username']}) 开始上传壁纸到用户专属目录");
        
        $userEmail = $userInfo['email'];
        logMessage("用户邮箱: {$userEmail}");
        
        // 验证请求方法
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            sendResponse(405, '只支持POST请求');
        }
        
        // 检查是否有文件上传
        if (!isset($_FILES['wallpaper'])) {
            sendResponse(400, '没有上传文件');
        }
        
        $file = $_FILES['wallpaper'];
        
        // 验证文件
        $validationError = validateImageFile($file);
        if ($validationError) {
            sendResponse(400, $validationError);
        }
        
        // 获取表单数据
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $category = isset($_POST['category']) ? trim($_POST['category']) : '';
        $tags = isset($_POST['tags']) ? trim($_POST['tags']) : '';
        
        // 验证必填字段
        if (empty($title)) {
            sendResponse(400, '壁纸标题不能为空');
        }
        
        // 获取图片信息
        $imageInfo = getimagesize($file['tmp_name']);
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        $format = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // 创建用户专属文件夹
        $userDir = createUserFolder($userEmail);
        if (!$userDir) {
            logMessage("创建用户文件夹失败: {$userEmail}");
            sendResponse(500, '创建用户文件夹失败');
        }
        
        // 创建压缩图文件夹
        $compressedDir = $userDir . 'compressed/';
        if (!is_dir($compressedDir)) {
            if (!mkdir($compressedDir, 0755, true)) {
                logMessage("创建压缩图文件夹失败: {$compressedDir}");
                sendResponse(500, '创建压缩图文件夹失败');
            }
        }
        
        // 生成文件名并保存原图
        $fileName = generateSafeFileName($file['name']);
        $filePath = $userDir . $fileName;
        
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            logMessage("文件移动失败: {$file['tmp_name']} -> {$filePath}");
            sendResponse(500, '文件保存失败');
        }
        
        logMessage("原图保存成功: {$filePath}");
        
        // 处理压缩图
        $compressedFilePath = null;
        if (isset($_FILES['compressed_wallpaper']) && $_FILES['compressed_wallpaper']['error'] === UPLOAD_ERR_OK) {
            $compressedFile = $_FILES['compressed_wallpaper'];
            $compressedFileName = 'compressed_' . $fileName;
            $compressedFilePath = $compressedDir . $compressedFileName;
            
            if (move_uploaded_file($compressedFile['tmp_name'], $compressedFilePath)) {
                logMessage("压缩图保存成功: {$compressedFilePath}");
            } else {
                logMessage("压缩图保存失败: {$compressedFile['tmp_name']} -> {$compressedFilePath}");
                // 压缩图保存失败不影响主流程，继续执行
                $compressedFilePath = null;
            }
        }
        
        // 处理标签
        $tagsArray = array_filter(array_map('trim', explode(',', $tags)));
        $tagsString = implode(',', $tagsArray);
        
        // 保存到用户上传壁纸表
        $relativePath = 'static/yhsc/' . $userEmail . '/' . $fileName;
        $compressedRelativePath = $compressedFilePath ? 'static/yhsc/' . $userEmail . '/compressed/compressed_' . $fileName : null;
        
        logMessage("准备插入数据库: userId={$userId}, userEmail={$userEmail}, title={$title}, relativePath={$relativePath}");
        
        $stmt = $conn->prepare("
            INSERT INTO user_wallpapers 
            (user_id, user_email, title, description, file_path, compressed_path, file_size, width, height, category, tags, format, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        if (!$stmt) {
            logMessage("数据库prepare失败: " . $conn->error);
            sendResponse(500, '数据库prepare失败: ' . $conn->error);
        }
        
        $stmt->bind_param(
            'isssssiiisss',
            $userId,
            $userEmail,
            $title,
            $description,
            $relativePath,
            $compressedRelativePath,
            $file['size'],
            $width,
            $height,
            $category,
            $tagsString,
            $format
        );
        
        if ($stmt->execute()) {
            $wallpaperId = $conn->insert_id;
            logMessage("用户壁纸保存成功: ID {$wallpaperId}, 用户: {$userEmail}");
            
            sendResponse(200, '壁纸上传成功', [
                'wallpaper_id' => $wallpaperId,
                'file_path' => $relativePath,
                'compressed_path' => $compressedRelativePath,
                'title' => $title,
                'user_folder' => $userEmail,
                'has_compressed' => $compressedFilePath !== null
            ]);
        } else {
            // 数据库保存失败，删除已上传的文件
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            logMessage("数据库保存失败: " . $stmt->error);
            sendResponse(500, '数据库保存失败');
        }
        
    } catch (Exception $e) {
        logMessage("上传异常: " . $e->getMessage() . " 文件: " . $e->getFile() . " 行号: " . $e->getLine());
        sendResponse(500, '服务器内部错误: ' . $e->getMessage());
    }
}

// 主要逻辑
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'upload') {
        handleUserWallpaperUpload();
    } else {
        sendResponse(400, '无效的操作');
    }
} else {
    sendResponse(405, '只支持POST请求');
}

// 关闭数据库连接
if (isset($conn)) {
    closeDBConnection($conn);
}
?>
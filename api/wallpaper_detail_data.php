<?php
/**
 * 文件: api/wallpaper_detail_data.php
 * 描述: 获取单个壁纸详细信息的API接口
 * 功能: 为wallpaper_detail.php页面提供数据支持
 * 创建时间: 2025-01-30
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// 引入数据库配置
require_once '../config/database.php';

// 引入用户认证函数
require_once '../api/vip/membership_status.php';

try {
    // 获取壁纸ID
    $wallpaper_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($wallpaper_id <= 0) {
        throw new Exception('无效的壁纸ID');
    }
    
    // 连接数据库
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    
    // 查询壁纸基本信息
    $stmt = $pdo->prepare("
        SELECT 
            id,
            name,
            file_path,
            file_size,
            resolution,
            category,
            tags,
            views,
            upload_time,
            status
        FROM wallpapers 
        WHERE id = ? AND status = 'active'
    ");
    
    $stmt->execute([$wallpaper_id]);
    $wallpaper = $stmt->fetch();
    
    if (!$wallpaper) {
        throw new Exception('壁纸不存在或已被删除');
    }
    
    // 检查用户权限
    $hasAdvancedAccess = false;
    
    try {
        // 尝试获取用户ID（不强制要求登录）
        $headers = function_exists("getallheaders") ? getallheaders() : [];
        
        // 如果 getallheaders 不可用，手动获取 Authorization 头
        if (empty($headers) && isset($_SERVER["HTTP_AUTHORIZATION"])) {
            $headers["Authorization"] = $_SERVER["HTTP_AUTHORIZATION"];
        }
        
        $user_id = null;
        
        // 从请求中获取用户ID
        if (isset($_GET["user_id"]) && !empty($_GET["user_id"])) {
            $user_id = intval($_GET["user_id"]);
        } elseif (isset($headers["Authorization"]) && !empty($headers["Authorization"])) {
            $auth_parts = explode(" ", $headers["Authorization"]);
            if (count($auth_parts) == 2 && $auth_parts[0] == "Bearer") {
                $user_id = intval($auth_parts[1]);
            }
        }
        
        if ($user_id) {
            // 获取用户会员状态
            $membershipStatus = getMembershipStatus($user_id);
            if ($membershipStatus && isset($membershipStatus['membership']['type'])) {
                $membershipType = $membershipStatus['membership']['type'];
                $isExpired = $membershipStatus['membership']['is_expired'] ?? false;
                $hasAdvancedAccess = in_array($membershipType, ['monthly', 'permanent']) && !$isExpired;
                
                // 管理员始终有权限
                if ($membershipType === 'admin') {
                    $hasAdvancedAccess = true;
                }
            }
        }
    } catch (Exception $e) {
        // 权限检查失败时，默认无权限
        error_log('权限检查失败: ' . $e->getMessage());
        $hasAdvancedAccess = false;
    }
    
    // 根据权限查询AI提示词和自定义链接
    $promptData = null;
    $customLinks = [];
    
    if ($hasAdvancedAccess) {
        // 查询AI提示词（如果存在）
        $promptStmt = $pdo->prepare("
            SELECT prompt_content 
            FROM wallpaper_prompts 
            WHERE wallpaper_id = ?
        ");
        
        $promptStmt->execute([$wallpaper_id]);
        $promptData = $promptStmt->fetch();
        
        // 查询自定义链接（如果存在）
        $linksStmt = $pdo->prepare("
            SELECT 
                title,
                url,
                description,
                priority
            FROM wallpaper_custom_links 
            WHERE wallpaper_id = ? 
            ORDER BY priority DESC, id ASC
        ");
        
        $linksStmt->execute([$wallpaper_id]);
        $customLinks = $linksStmt->fetchAll();
    }
    
    // 更新浏览次数
    $updateViewsStmt = $pdo->prepare("
        UPDATE wallpapers 
        SET views = views + 1 
        WHERE id = ?
    ");
    $updateViewsStmt->execute([$wallpaper_id]);
    
    // 记录浏览日志（如果表存在）
    try {
        $logStmt = $pdo->prepare("
            INSERT INTO wallpaper_views_log (wallpaper_id, view_time, ip_address, user_agent) 
            VALUES (?, NOW(), ?, ?)
        ");
        
        $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $logStmt->execute([$wallpaper_id, $ipAddress, $userAgent]);
    } catch (Exception $e) {
        // 如果日志表不存在或插入失败，不影响主要功能
        error_log('浏览日志记录失败: ' . $e->getMessage());
    }
    
    // 处理标签
    $tagsArray = [];
    if (!empty($wallpaper['tags'])) {
        $tagsArray = array_filter(array_map('trim', explode(',', $wallpaper['tags'])));
    }
    
    // 格式化文件大小
    $fileSize = $wallpaper['file_size'];
    $fileSizeFormatted = '';
    if ($fileSize > 0) {
        if ($fileSize >= 1048576) { // 1MB = 1048576 bytes
            $fileSizeFormatted = round($fileSize / 1048576, 2) . ' MB';
        } elseif ($fileSize >= 1024) { // 1KB = 1024 bytes
            $fileSizeFormatted = round($fileSize / 1024, 2) . ' KB';
        } else {
            $fileSizeFormatted = $fileSize . ' B';
        }
    }
    
    // 构建响应数据
    $response = [
        'success' => true,
        'data' => [
            'id' => intval($wallpaper['id']),
            'name' => $wallpaper['name'],
            'file_path' => $wallpaper['file_path'],
            'file_size' => intval($wallpaper['file_size']),
            'file_size_formatted' => $fileSizeFormatted,
            'resolution' => $wallpaper['resolution'],
            'category' => $wallpaper['category'],
            'tags' => $tagsArray,
            'views' => intval($wallpaper['views']) + 1, // 包含本次浏览
            'upload_time' => $wallpaper['upload_time'],
            'has_advanced_access' => $hasAdvancedAccess,
            'prompt' => $hasAdvancedAccess && $promptData ? $promptData['prompt_content'] : null,
            'custom_links' => $hasAdvancedAccess ? $customLinks : []
        ]
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
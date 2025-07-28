<?php
/**
 * 文件: api/admin/recall_wallpaper.php
 * 描述: 单个召回流放壁纸API
 * 功能: 将单个流放壁纸状态从1改为0，并记录操作日志
 * 权限: 管理员可访问
 * 创建时间: 2025-01-27
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../config/database.php';
require_once '../admin_auth.php';
require_once '../utils.php';

// 验证请求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(405, '不支持的请求方法');
    exit();
}

try {
    // 权限验证：使用统一的Authorization头验证
    // 注意：已完全废弃SESSION验证，改为LOCAL和数据库管理员验证
    $userId = checkAdminAuth();
    if (!$userId) {
        sendResponse(403, '权限不足，仅管理员可操作');
        exit();
    }
    
    // 获取管理员用户信息
    $conn = getDBConnection();
    if (!$conn) {
        sendResponse(500, '数据库连接失败');
        exit();
    }
    
    $stmt = $conn->prepare("SELECT username FROM users WHERE id = ? AND is_admin = 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        sendResponse(403, '管理员用户不存在');
        exit();
    }
    
    // 获取请求数据
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        sendResponse(400, '无效的JSON数据');
        exit();
    }
    
    $wallpaperId = intval($input['wallpaper_id'] ?? 0);
    $comment = trim($input['comment'] ?? '单个召回操作');
    
    // 验证参数
    if ($wallpaperId <= 0) {
        sendResponse(400, '无效的壁纸ID');
        exit();
    }
    
    // 开始事务
    $conn->begin_transaction();
    
    try {
        // 检查壁纸是否处于流放状态
        $checkStmt = $conn->prepare("SELECT id, status FROM wallpaper_exile_status WHERE wallpaper_id = ?");
        $checkStmt->bind_param("i", $wallpaperId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $exileRecord = $checkResult->fetch_assoc();
        
        if (!$exileRecord) {
            $conn->rollback();
            sendResponse(404, '该壁纸未找到流放记录');
            exit();
        }
        
        if ($exileRecord['status'] != 1) {
            $conn->rollback();
            sendResponse(400, '该壁纸未处于流放状态');
            exit();
        }
        
        // 获取壁纸基本信息
        $wallpaperStmt = $conn->prepare("SELECT title, user_id FROM wallpapers WHERE id = ?");
        $wallpaperStmt->bind_param("i", $wallpaperId);
        $wallpaperStmt->execute();
        $wallpaperResult = $wallpaperStmt->get_result();
        $wallpaper = $wallpaperResult->fetch_assoc();
        
        if (!$wallpaper) {
            $conn->rollback();
            sendResponse(404, '壁纸不存在');
            exit();
        }
        
        // 更新流放状态
        $updateStmt = $conn->prepare("UPDATE wallpaper_exile_status SET status = 0, last_operation_time = CURRENT_TIMESTAMP, comment = ? WHERE wallpaper_id = ? AND status = 1");
        $updateStmt->bind_param("si", $comment, $wallpaperId);
        $updateStmt->execute();
        
        if ($updateStmt->affected_rows === 0) {
            $conn->rollback();
            sendResponse(400, '召回失败，壁纸可能已被其他操作修改');
            exit();
        }
        
        // 记录操作日志
        $logStmt = $conn->prepare("INSERT INTO wallpaper_operation_log (wallpaper_id, action_type, operated_by_user_id, operation_time, old_status, new_status, comment) VALUES (?, 'admin_recall', ?, CURRENT_TIMESTAMP, 1, 0, ?)");
        $logStmt->bind_param("iis", $wallpaperId, $userId, $comment);
        $logStmt->execute();
        
        // 提交事务
        $conn->commit();
        
        // 返回结果
        sendResponse(200, '壁纸召回成功', [
            'wallpaper_id' => $wallpaperId,
            'wallpaper_title' => $wallpaper['title'] ?: '未命名壁纸',
            'operator' => $user['username'],
            'comment' => $comment,
            'recall_time' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log('召回壁纸错误: ' . $e->getMessage());
    sendResponse(500, '服务器错误: ' . $e->getMessage());
} finally {
    if (isset($conn)) {
        closeDBConnection($conn);
    }
}
?>
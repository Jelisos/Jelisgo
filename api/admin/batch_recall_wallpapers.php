<?php
/**
 * 文件: api/admin/batch_recall_wallpapers.php
 * 描述: 批量召回流放壁纸API
 * 功能: 将选中的流放壁纸状态从1改为0，并记录操作日志
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
    
    $wallpaperIds = $input['wallpaper_ids'] ?? [];
    $comment = trim($input['comment'] ?? '批量召回操作');
    
    // 验证参数
    if (empty($wallpaperIds) || !is_array($wallpaperIds)) {
        sendResponse(400, '请选择要召回的壁纸');
        exit();
    }
    
    // 验证壁纸ID格式
    $validIds = [];
    foreach ($wallpaperIds as $id) {
        $id = intval($id);
        if ($id > 0) {
            $validIds[] = $id;
        }
    }
    
    if (empty($validIds)) {
        sendResponse(400, '无效的壁纸ID');
        exit();
    }
    
    // 限制批量操作数量
    if (count($validIds) > 100) {
        sendResponse(400, '单次最多只能召回100张壁纸');
        exit();
    }
    
    // 开始事务
    $conn->begin_transaction();
    
    try {
        // 检查哪些壁纸确实处于流放状态
        $placeholders = str_repeat('?,', count($validIds) - 1) . '?';
        $checkSql = "SELECT wallpaper_id FROM wallpaper_exile_status WHERE wallpaper_id IN ({$placeholders}) AND status = 1";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param(str_repeat('i', count($validIds)), ...$validIds);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        $exiledIds = [];
        while ($row = $checkResult->fetch_assoc()) {
            $exiledIds[] = (int)$row['wallpaper_id'];
        }
        
        if (empty($exiledIds)) {
            $conn->rollback();
            sendResponse(400, '所选壁纸均未处于流放状态');
            exit();
        }
        
        // 批量更新流放状态
        $updatePlaceholders = str_repeat('?,', count($exiledIds) - 1) . '?';
        $updateSql = "UPDATE wallpaper_exile_status SET status = 0, last_operation_time = CURRENT_TIMESTAMP, comment = ? WHERE wallpaper_id IN ({$updatePlaceholders}) AND status = 1";
        $updateStmt = $conn->prepare($updateSql);
        $updateParams = [$comment, ...$exiledIds];
        $updateTypes = 's' . str_repeat('i', count($exiledIds));
        $updateStmt->bind_param($updateTypes, ...$updateParams);
        $updateStmt->execute();
        $recalledCount = $updateStmt->affected_rows;
        
        // 批量记录操作日志
        if ($recalledCount > 0) {
            $logSql = "INSERT INTO wallpaper_operation_log (wallpaper_id, action_type, operated_by_user_id, operation_time, old_status, new_status, comment) VALUES (?, 'admin_recall', ?, CURRENT_TIMESTAMP, 1, 0, ?)";
            $logStmt = $conn->prepare($logSql);
            
            foreach ($exiledIds as $wallpaperId) {
                $logStmt->bind_param("iis", $wallpaperId, $userId, $comment);
                $logStmt->execute();
            }
        }
        
        // 提交事务
        $conn->commit();
        
        $failedCount = count($validIds) - $recalledCount;
        
        // 返回结果
        sendResponse(200, "成功召回 {$recalledCount} 张壁纸" . ($failedCount > 0 ? "，{$failedCount} 张失败" : ''), [
            'recalled_count' => $recalledCount,
            'failed_count' => $failedCount,
            'total_requested' => count($validIds),
            'recalled_ids' => $exiledIds,
            'operator' => $user['username']
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log('批量召回壁纸错误: ' . $e->getMessage());
    sendResponse(500, '服务器错误: ' . $e->getMessage());
} finally {
    if (isset($conn)) {
        closeDBConnection($conn);
    }
}
?>
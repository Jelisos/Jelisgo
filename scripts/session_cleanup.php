<?php
/**
 * Session清理定时任务脚本
 * 用于清理过期的session数据
 * @author AI Assistant
 * @date 2025-01-27
 */

require_once __DIR__ . '/../config/session_config.php';

/**
 * 执行session清理任务
 */
function cleanupExpiredSessions() {
    try {
        $sessionManager = SessionManager::getInstance();
        $cleanedCount = $sessionManager->cleanExpiredSessions();
        
        $logMessage = date('Y-m-d H:i:s') . " - Session清理完成，清理了 {$cleanedCount} 个过期session\n";
        
        // 记录到日志文件
        file_put_contents(__DIR__ . '/../logs/session_cleanup.log', $logMessage, FILE_APPEND | LOCK_EX);
        
        echo $logMessage;
        
        return $cleanedCount;
    } catch (Exception $e) {
        $errorMessage = date('Y-m-d H:i:s') . " - Session清理失败: " . $e->getMessage() . "\n";
        
        // 记录错误到日志文件
        file_put_contents(__DIR__ . '/../logs/session_cleanup.log', $errorMessage, FILE_APPEND | LOCK_EX);
        
        echo $errorMessage;
        
        return false;
    }
}

/**
 * 获取session统计信息
 */
function getSessionStats() {
    try {
        $conn = getDBConnection();
        if (!$conn) {
            throw new Exception('数据库连接失败');
        }
        
        // 总session数
        $totalResult = $conn->query("SELECT COUNT(*) as total FROM sessions");
        $total = $totalResult->fetch_assoc()['total'];
        
        // 活跃session数（未过期）
        $activeResult = $conn->query("SELECT COUNT(*) as active FROM sessions WHERE expires_at > NOW()");
        $active = $activeResult->fetch_assoc()['active'];
        
        // 过期session数
        $expiredResult = $conn->query("SELECT COUNT(*) as expired FROM sessions WHERE expires_at <= NOW()");
        $expired = $expiredResult->fetch_assoc()['expired'];
        
        // 有用户ID的session数（已登录用户）
        $loggedInResult = $conn->query("SELECT COUNT(*) as logged_in FROM sessions WHERE user_id IS NOT NULL AND expires_at > NOW()");
        $loggedIn = $loggedInResult->fetch_assoc()['logged_in'];
        
        $conn->close();
        
        return [
            'total' => $total,
            'active' => $active,
            'expired' => $expired,
            'logged_in' => $loggedIn
        ];
    } catch (Exception $e) {
        echo "获取session统计失败: " . $e->getMessage() . "\n";
        return false;
    }
}

/**
 * 显示session统计信息
 */
function showSessionStats() {
    $stats = getSessionStats();
    if ($stats) {
        echo "\n=== Session统计信息 ===\n";
        echo "总session数: {$stats['total']}\n";
        echo "活跃session数: {$stats['active']}\n";
        echo "过期session数: {$stats['expired']}\n";
        echo "已登录用户session数: {$stats['logged_in']}\n";
        echo "========================\n\n";
    }
}

// 如果直接运行此脚本
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    echo "开始执行Session清理任务...\n";
    
    // 显示清理前的统计
    echo "清理前:";
    showSessionStats();
    
    // 执行清理
    $cleanedCount = cleanupExpiredSessions();
    
    // 显示清理后的统计
    echo "清理后:";
    showSessionStats();
    
    if ($cleanedCount !== false) {
        echo "Session清理任务完成！\n";
    } else {
        echo "Session清理任务失败！\n";
        exit(1);
    }
}

?>
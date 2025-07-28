<?php
/**
 * Session定时清理脚本
 * 用于定时清理过期的session数据
 * 可以通过Windows任务计划程序或手动执行
 */

// 设置脚本执行时间限制
set_time_limit(300); // 5分钟

// 设置内存限制
ini_set('memory_limit', '128M');

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 0); // 生产环境关闭错误显示
ini_set('log_errors', 1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_config.php';

// 日志文件路径
$logFile = __DIR__ . '/../logs/session_cleanup_cron.log';
$logDir = dirname($logFile);

// 确保日志目录存在
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

/**
 * 写入日志
 */
function writeLog($message, $level = 'INFO') {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * 主清理函数
 */
function performCleanup() {
    try {
        writeLog('开始执行Session清理任务');
        
        // 获取数据库连接
        $conn = getDBConnection();
        
        // 获取清理前的统计信息
        $beforeStats = getSessionStats($conn);
        writeLog("清理前统计: 总数={$beforeStats['total']}, 活跃={$beforeStats['active']}, 过期={$beforeStats['expired']}");
        
        // 执行清理操作
        $cleanupResults = [];
        
        // 1. 清理过期的session
        $expiredCount = cleanupExpiredSessions($conn);
        $cleanupResults['expired'] = $expiredCount;
        writeLog("清理过期Session: {$expiredCount} 个");
        
        // 2. 清理超过30天的旧session（即使未过期）
        $oldCount = cleanupOldSessions($conn, 30);
        $cleanupResults['old'] = $oldCount;
        writeLog("清理30天以上旧Session: {$oldCount} 个");
        
        // 3. 清理无效的session数据（session_data为空或损坏）
        $invalidCount = cleanupInvalidSessions($conn);
        $cleanupResults['invalid'] = $invalidCount;
        writeLog("清理无效Session: {$invalidCount} 个");
        
        // 4. 限制单个用户的session数量（最多保留5个最新的）
        $limitedCount = limitUserSessions($conn, 5);
        $cleanupResults['limited'] = $limitedCount;
        writeLog("限制用户Session数量，清理: {$limitedCount} 个");
        
        // 获取清理后的统计信息
        $afterStats = getSessionStats($conn);
        writeLog("清理后统计: 总数={$afterStats['total']}, 活跃={$afterStats['active']}, 过期={$afterStats['expired']}");
        
        // 计算清理效果
        $totalCleaned = array_sum($cleanupResults);
        $spaceSaved = ($beforeStats['total'] - $afterStats['total']);
        
        writeLog("清理完成: 共清理 {$totalCleaned} 个Session, 实际减少 {$spaceSaved} 个记录");
        
        // 优化数据库表
        optimizeSessionTable($conn);
        writeLog('数据库表优化完成');
        
        // 关闭数据库连接
        closeDBConnection($conn);
        
        writeLog('Session清理任务执行完成');
        
        return [
            'success' => true,
            'before_stats' => $beforeStats,
            'after_stats' => $afterStats,
            'cleanup_results' => $cleanupResults,
            'total_cleaned' => $totalCleaned
        ];
        
    } catch (Exception $e) {
        writeLog('清理任务执行失败: ' . $e->getMessage(), 'ERROR');
        writeLog('错误堆栈: ' . $e->getTraceAsString(), 'ERROR');
        
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * 获取session统计信息
 */
function getSessionStats($conn) {
    $stats = [];
    
    // 总数
    $result = $conn->query("SELECT COUNT(*) as total FROM sessions");
    $stats['total'] = $result->fetch_assoc()['total'];
    
    // 活跃数
    $result = $conn->query("SELECT COUNT(*) as active FROM sessions WHERE expires_at > NOW()");
    $stats['active'] = $result->fetch_assoc()['active'];
    
    // 过期数
    $result = $conn->query("SELECT COUNT(*) as expired FROM sessions WHERE expires_at <= NOW()");
    $stats['expired'] = $result->fetch_assoc()['expired'];
    
    // 已登录用户数
    $result = $conn->query("SELECT COUNT(*) as logged_in FROM sessions WHERE user_id IS NOT NULL AND expires_at > NOW()");
    $stats['logged_in'] = $result->fetch_assoc()['logged_in'];
    
    return $stats;
}

/**
 * 清理过期的session
 */
function cleanupExpiredSessions($conn) {
    $stmt = $conn->prepare("DELETE FROM sessions WHERE expires_at <= NOW()");
    $stmt->execute();
    return $stmt->affected_rows;
}

/**
 * 清理超过指定天数的旧session
 */
function cleanupOldSessions($conn, $days) {
    $stmt = $conn->prepare("DELETE FROM sessions WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
    $stmt->bind_param('i', $days);
    $stmt->execute();
    return $stmt->affected_rows;
}

/**
 * 清理无效的session数据
 */
function cleanupInvalidSessions($conn) {
    // 清理session_data为空或NULL的记录
    $stmt = $conn->prepare("DELETE FROM sessions WHERE session_data IS NULL OR session_data = ''");
    $stmt->execute();
    $count1 = $stmt->affected_rows;
    
    // 清理session_id为空的记录
    $stmt = $conn->prepare("DELETE FROM sessions WHERE session_id IS NULL OR session_id = ''");
    $stmt->execute();
    $count2 = $stmt->affected_rows;
    
    return $count1 + $count2;
}

/**
 * 限制单个用户的session数量
 */
function limitUserSessions($conn, $maxSessions) {
    $totalCleaned = 0;
    
    // 获取有多个session的用户
    $sql = "
        SELECT user_id, COUNT(*) as session_count 
        FROM sessions 
        WHERE user_id IS NOT NULL 
        GROUP BY user_id 
        HAVING session_count > ?
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $maxSessions);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $userId = $row['user_id'];
        $sessionCount = $row['session_count'];
        
        // 删除该用户最旧的session，保留最新的$maxSessions个
        $deleteSql = "
            DELETE FROM sessions 
            WHERE user_id = ? 
            AND id NOT IN (
                SELECT id FROM (
                    SELECT id FROM sessions 
                    WHERE user_id = ? 
                    ORDER BY updated_at DESC 
                    LIMIT ?
                ) as keep_sessions
            )
        ";
        
        $deleteStmt = $conn->prepare($deleteSql);
        $deleteStmt->bind_param('iii', $userId, $userId, $maxSessions);
        $deleteStmt->execute();
        $cleaned = $deleteStmt->affected_rows;
        $totalCleaned += $cleaned;
        
        writeLog("用户 {$userId}: 清理了 {$cleaned} 个旧session (原有 {$sessionCount} 个)");
    }
    
    return $totalCleaned;
}

/**
 * 优化session表
 */
function optimizeSessionTable($conn) {
    try {
        // 优化表结构
        $conn->query("OPTIMIZE TABLE sessions");
        writeLog('sessions表优化成功');
    } catch (Exception $e) {
        writeLog('sessions表优化失败: ' . $e->getMessage(), 'WARNING');
    }
}

/**
 * 发送清理报告邮件（可选）
 */
function sendCleanupReport($results) {
    // 这里可以实现邮件发送功能
    // 暂时只记录到日志
    if ($results['success']) {
        $message = "Session清理报告:\n";
        $message .= "清理前总数: {$results['before_stats']['total']}\n";
        $message .= "清理后总数: {$results['after_stats']['total']}\n";
        $message .= "共清理: {$results['total_cleaned']} 个Session\n";
        $message .= "详细信息: " . json_encode($results['cleanup_results'], JSON_UNESCAPED_UNICODE);
        
        writeLog('清理报告: ' . $message);
    } else {
        writeLog('清理失败报告: ' . $results['error'], 'ERROR');
    }
}

// 主程序执行
if (php_sapi_name() === 'cli') {
    // 命令行模式
    echo "开始执行Session清理任务...\n";
    $results = performCleanup();
    
    if ($results['success']) {
        echo "清理完成！共清理 {$results['total_cleaned']} 个Session\n";
        echo "详细信息请查看日志文件: {$logFile}\n";
    } else {
        echo "清理失败: {$results['error']}\n";
        exit(1);
    }
} else {
    // Web模式
    header('Content-Type: application/json; charset=utf-8');
    
    // 简单的安全检查（可以根据需要加强）
    $allowedIPs = ['127.0.0.1', '::1', 'localhost'];
    $clientIP = $_SERVER['REMOTE_ADDR'] ?? '';
    
    if (!in_array($clientIP, $allowedIPs)) {
        http_response_code(403);
        echo json_encode(['error' => '访问被拒绝'], JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    $results = performCleanup();
    echo json_encode($results, JSON_UNESCAPED_UNICODE);
}

// 发送清理报告
sendCleanupReport($results);
?>
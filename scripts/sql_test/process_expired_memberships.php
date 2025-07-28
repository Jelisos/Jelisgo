<?php
/**
 * 定时任务：处理过期会员
 * 每日执行，处理过期的会员账户
 * 
 * @author AI Assistant
 * @date 2024-01-27
 */

require_once __DIR__ . '/../api/vip/membership_functions.php';

// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 记录脚本开始时间
$start_time = microtime(true);
$log_file = __DIR__ . '/../logs/expired_memberships_' . date('Y-m-d') . '.log';

/**
 * 写入日志
 */
function writeLog($message, $level = 'INFO') {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
    file_put_contents($log_file, $log_message, FILE_APPEND | LOCK_EX);
    echo $log_message;
}

try {
    writeLog("开始执行过期会员处理任务");
    
    // 获取数据库连接
    $pdo = getDbConnection();
    
    // 查找所有过期的会员
    $stmt = $pdo->prepare("
        SELECT id, username, membership_type, membership_expires_at 
        FROM users 
        WHERE membership_type IN ('monthly', 'permanent') 
        AND membership_expires_at IS NOT NULL 
        AND membership_expires_at <= NOW()
        AND membership_type != 'free'
    ");
    
    $stmt->execute();
    $expired_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $processed_count = 0;
    $error_count = 0;
    
    if (empty($expired_users)) {
        writeLog("没有找到过期的会员账户");
    } else {
        writeLog("找到 " . count($expired_users) . " 个过期的会员账户");
        
        foreach ($expired_users as $user) {
            try {
                // 开始事务
                $pdo->beginTransaction();
                
                // 处理过期会员
                $result = processExpiredMembership($user['id']);
                
                if ($result['success']) {
                    $pdo->commit();
                    $processed_count++;
                    
                    writeLog("成功处理用户 {$user['username']} (ID: {$user['id']}) 的过期会员，原类型: {$user['membership_type']}, 过期时间: {$user['membership_expires_at']}");
                    
                    // 记录操作日志
                    logOperation('process_expired_membership', 
                        "自动处理过期会员: 用户ID={$user['id']}, 原类型={$user['membership_type']}, 过期时间={$user['membership_expires_at']}", 
                        $user['id']);
                        
                } else {
                    $pdo->rollBack();
                    $error_count++;
                    writeLog("处理用户 {$user['username']} (ID: {$user['id']}) 失败: {$result['message']}", 'ERROR');
                }
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $error_count++;
                writeLog("处理用户 {$user['username']} (ID: {$user['id']}) 时发生异常: " . $e->getMessage(), 'ERROR');
            }
        }
    }
    
    // 清理过期的未使用会员码（可选）
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM membership_codes 
            WHERE used_at IS NULL AND expires_at <= NOW()
        ");
        $stmt->execute();
        $expired_codes_count = $stmt->fetchColumn();
        
        if ($expired_codes_count > 0) {
            // 可以选择删除过期的未使用会员码，或者只是标记它们
            // 这里我们选择保留它们，只记录日志
            writeLog("发现 {$expired_codes_count} 个过期的未使用会员码");
        }
        
    } catch (Exception $e) {
        writeLog("检查过期会员码时发生错误: " . $e->getMessage(), 'ERROR');
    }
    
    // 统计信息
    $end_time = microtime(true);
    $execution_time = round($end_time - $start_time, 2);
    
    writeLog("过期会员处理任务完成");
    writeLog("处理统计: 成功 {$processed_count} 个, 失败 {$error_count} 个");
    writeLog("执行时间: {$execution_time} 秒");
    
    // 如果有错误，退出码为1
    if ($error_count > 0) {
        exit(1);
    }
    
} catch (Exception $e) {
    writeLog("脚本执行过程中发生严重错误: " . $e->getMessage(), 'FATAL');
    exit(1);
}

// 成功退出
exit(0);

?>
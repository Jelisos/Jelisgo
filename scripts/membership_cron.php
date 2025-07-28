<?php
/**
 * 会员系统定时任务脚本
 * 用于处理过期会员和配额重置
 * 建议每小时执行一次
 */

require_once '../config.php';
require_once '../api/write_log.php';

/**
 * 处理过期会员
 */
function handleExpiredMemberships($pdo) {
    try {
        $sql = "UPDATE users 
                SET membership_type = 'free', 
                    download_quota = 0, 
                    membership_expires_at = NULL,
                    quota_reset_date = NULL
                WHERE membership_type = 'monthly' 
                AND membership_expires_at < NOW()";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        
        $affected_rows = $stmt->rowCount();
        
        if ($affected_rows > 0) {
            write_log("定时任务: 处理了 {$affected_rows} 个过期会员");
            echo "处理了 {$affected_rows} 个过期会员\n";
        } else {
            echo "没有过期会员需要处理\n";
        }
        
        return $affected_rows;
        
    } catch (Exception $e) {
        write_log("处理过期会员错误: " . $e->getMessage());
        echo "处理过期会员错误: " . $e->getMessage() . "\n";
        return false;
    }
}

/**
 * 处理配额重置
 */
function handleQuotaReset($pdo) {
    try {
        // 查找需要重置配额的用户
        $sql = "SELECT id, username, download_quota, quota_reset_date, membership_expires_at
                FROM users 
                WHERE membership_type = 'monthly' 
                AND quota_reset_date <= NOW() 
                AND membership_expires_at > NOW()";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $reset_count = 0;
        
        foreach ($users as $user) {
            $now = new DateTime();
            $reset_date = new DateTime($user['quota_reset_date']);
            $expires_at = new DateTime($user['membership_expires_at']);
            
            // 计算下一个重置时间（30天后）
            $next_reset = clone $reset_date;
            $next_reset->add(new DateInterval('P30D'));
            
            // 确保下次重置时间不超过会员到期时间
            if ($next_reset > $expires_at) {
                $next_reset = $expires_at;
            }
            
            // 重置配额
            $update_sql = "UPDATE users 
                          SET download_quota = 10, 
                              quota_reset_date = ? 
                          WHERE id = ?";
            
            $stmt = $pdo->prepare($update_sql);
            $stmt->execute([$next_reset->format('Y-m-d H:i:s'), $user['id']]);
            
            $reset_count++;
            
            write_log("定时任务: 用户 {$user['username']} (ID: {$user['id']}) 配额已重置");
            echo "用户 {$user['username']} (ID: {$user['id']}) 配额已重置\n";
        }
        
        if ($reset_count > 0) {
            write_log("定时任务: 重置了 {$reset_count} 个用户的配额");
            echo "总共重置了 {$reset_count} 个用户的配额\n";
        } else {
            echo "没有用户需要重置配额\n";
        }
        
        return $reset_count;
        
    } catch (Exception $e) {
        write_log("配额重置错误: " . $e->getMessage());
        echo "配额重置错误: " . $e->getMessage() . "\n";
        return false;
    }
}

/**
 * 清理过期的会员码
 */
function cleanupExpiredCodes($pdo) {
    try {
        $sql = "UPDATE membership_codes 
                SET status = 'expired' 
                WHERE status = 'active' 
                AND expires_at < NOW()";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        
        $affected_rows = $stmt->rowCount();
        
        if ($affected_rows > 0) {
            write_log("定时任务: 标记了 {$affected_rows} 个过期会员码");
            echo "标记了 {$affected_rows} 个过期会员码\n";
        } else {
            echo "没有过期会员码需要处理\n";
        }
        
        return $affected_rows;
        
    } catch (Exception $e) {
        write_log("清理过期会员码错误: " . $e->getMessage());
        echo "清理过期会员码错误: " . $e->getMessage() . "\n";
        return false;
    }
}

/**
 * 生成统计报告
 */
function generateReport($pdo) {
    try {
        echo "\n=== 会员系统统计报告 ===\n";
        echo "生成时间: " . date('Y-m-d H:i:s') . "\n\n";
        
        // 用户统计
        $user_stats_sql = "SELECT 
                            membership_type,
                            COUNT(*) as count
                          FROM users 
                          GROUP BY membership_type";
        
        $stmt = $pdo->prepare($user_stats_sql);
        $stmt->execute();
        $user_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "用户统计:\n";
        foreach ($user_stats as $stat) {
            $type_name = [
                'free' => '免费用户',
                'monthly' => '1元会员',
                'permanent' => '永久会员'
            ][$stat['membership_type']] ?? $stat['membership_type'];
            
            echo "  {$type_name}: {$stat['count']} 人\n";
        }
        
        // 会员码统计
        $code_stats_sql = "SELECT 
                            membership_type,
                            status,
                            COUNT(*) as count
                          FROM membership_codes 
                          GROUP BY membership_type, status";
        
        $stmt = $pdo->prepare($code_stats_sql);
        $stmt->execute();
        $code_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\n会员码统计:\n";
        foreach ($code_stats as $stat) {
            $type_name = $stat['membership_type'] === 'monthly' ? '1元会员' : '永久会员';
            $status_name = [
                'active' => '可用',
                'used' => '已使用',
                'expired' => '已过期',
                'disabled' => '已禁用'
            ][$stat['status']] ?? $stat['status'];
            
            echo "  {$type_name} - {$status_name}: {$stat['count']} 个\n";
        }
        
        // 今日下载统计
        $download_stats_sql = "SELECT 
                                download_type,
                                COUNT(*) as count,
                                SUM(quota_consumed) as quota_used
                              FROM user_download_logs 
                              WHERE download_date = CURDATE()
                              GROUP BY download_type";
        
        $stmt = $pdo->prepare($download_stats_sql);
        $stmt->execute();
        $download_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\n今日下载统计:\n";
        foreach ($download_stats as $stat) {
            echo "  {$stat['download_type']}: {$stat['count']} 次 (消耗配额: {$stat['quota_used']})\n";
        }
        
        echo "\n=== 报告结束 ===\n\n";
        
    } catch (Exception $e) {
        write_log("生成统计报告错误: " . $e->getMessage());
        echo "生成统计报告错误: " . $e->getMessage() . "\n";
    }
}

// 主程序
try {
    echo "会员系统定时任务开始执行...\n";
    echo "执行时间: " . date('Y-m-d H:i:s') . "\n\n";
    
    // 连接数据库
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 1. 处理过期会员
    echo "1. 处理过期会员...\n";
    handleExpiredMemberships($pdo);
    
    // 2. 处理配额重置
    echo "\n2. 处理配额重置...\n";
    handleQuotaReset($pdo);
    
    // 3. 清理过期会员码
    echo "\n3. 清理过期会员码...\n";
    cleanupExpiredCodes($pdo);
    
    // 4. 生成统计报告
    generateReport($pdo);
    
    write_log("会员系统定时任务执行完成");
    echo "定时任务执行完成\n";
    
} catch (Exception $e) {
    write_log("定时任务执行错误: " . $e->getMessage());
    echo "定时任务执行错误: " . $e->getMessage() . "\n";
    exit(1);
}
?>
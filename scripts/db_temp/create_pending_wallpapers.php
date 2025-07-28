<?php
/**
 * 创建一些待审核的壁纸数据
 * 文件: create_pending_wallpapers.php
 * 功能: 将部分壁纸状态改为待审核，让仪表盘显示更真实的数据
 */

require_once 'config/database.php';

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PWD, DB_NAME);
    $conn->set_charset("utf8mb4");
    
    if ($conn->connect_error) {
        die("数据库连接失败: " . $conn->connect_error);
    }
    
    echo "=== 创建待审核壁纸数据 ===\n\n";
    
    // 随机选择20个壁纸改为待审核状态
    $pending_count = 20;
    
    // 获取当前已审核的壁纸ID
    $query = "
        SELECT wrs.wallpaper_id 
        FROM wallpaper_review_status wrs 
        WHERE wrs.status = 'approved' 
        ORDER BY RAND() 
        LIMIT $pending_count
    ";
    
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception("查询壁纸失败: " . $conn->error);
    }
    
    $wallpaper_ids = [];
    while ($row = $result->fetch_assoc()) {
        $wallpaper_ids[] = $row['wallpaper_id'];
    }
    
    if (empty($wallpaper_ids)) {
        echo "没有找到可以改为待审核的壁纸\n";
        exit;
    }
    
    echo "选择了 " . count($wallpaper_ids) . " 个壁纸改为待审核状态\n";
    echo "壁纸ID: " . implode(', ', $wallpaper_ids) . "\n\n";
    
    $conn->begin_transaction();
    
    try {
        // 更新这些壁纸的审核状态为待审核
        $ids_str = implode(',', $wallpaper_ids);
        $update_sql = "
            UPDATE wallpaper_review_status 
            SET status = 'pending', reviewer_id = NULL, review_time = NULL 
            WHERE wallpaper_id IN ($ids_str)
        ";
        
        if (!$conn->query($update_sql)) {
            throw new Exception("更新审核状态失败: " . $conn->error);
        }
        
        echo "✅ 成功将 " . count($wallpaper_ids) . " 个壁纸状态改为待审核\n";
        
        // 删除对应的管理员操作日志（因为现在是待审核状态）
        $delete_log_sql = "
            DELETE FROM admin_operation_logs 
            WHERE target_type = 'wallpaper' 
            AND target_id IN ($ids_str) 
            AND operation_type = '审核通过'
        ";
        
        $conn->query($delete_log_sql); // 不强制要求成功
        
        // 另外创建5个拒绝状态的壁纸
        $rejected_count = 5;
        $query2 = "
            SELECT wrs.wallpaper_id 
            FROM wallpaper_review_status wrs 
            WHERE wrs.status = 'approved' 
            AND wrs.wallpaper_id NOT IN ($ids_str)
            ORDER BY RAND() 
            LIMIT $rejected_count
        ";
        
        $result2 = $conn->query($query2);
        if ($result2 && $result2->num_rows > 0) {
            $rejected_ids = [];
            while ($row = $result2->fetch_assoc()) {
                $rejected_ids[] = $row['wallpaper_id'];
            }
            
            if (!empty($rejected_ids)) {
                $rejected_ids_str = implode(',', $rejected_ids);
                
                // 获取管理员ID
                $admin_query = "SELECT id FROM users WHERE is_admin = 1 LIMIT 1";
                $admin_result = $conn->query($admin_query);
                $admin_id = 1;
                if ($admin_result && $admin_result->num_rows > 0) {
                    $admin_id = $admin_result->fetch_assoc()['id'];
                }
                
                $update_rejected_sql = "
                    UPDATE wallpaper_review_status 
                    SET status = 'rejected', reviewer_id = $admin_id, review_time = NOW() 
                    WHERE wallpaper_id IN ($rejected_ids_str)
                ";
                
                if ($conn->query($update_rejected_sql)) {
                    echo "✅ 成功将 " . count($rejected_ids) . " 个壁纸状态改为已拒绝\n";
                    
                    // 添加拒绝操作日志
                    $log_values = [];
                    foreach ($rejected_ids as $id) {
                        $log_values[] = "($admin_id, '审核拒绝', 'wallpaper', $id, NOW())";
                    }
                    
                    if (!empty($log_values)) {
                        $log_sql = "
                            INSERT INTO admin_operation_logs (admin_id, operation_type, target_type, target_id, created_at) 
                            VALUES " . implode(', ', $log_values);
                        
                        $conn->query($log_sql);
                    }
                }
            }
        }
        
        $conn->commit();
        
        echo "\n=== 操作完成 ===\n\n";
        
        // 显示最新统计
        $stats_query = "
            SELECT 
                status,
                COUNT(*) as count
            FROM wallpaper_review_status 
            GROUP BY status
        ";
        
        $stats_result = $conn->query($stats_query);
        if ($stats_result) {
            echo "当前审核状态统计:\n";
            while ($row = $stats_result->fetch_assoc()) {
                echo "- {$row['status']}: {$row['count']} 个\n";
            }
        }
        
        echo "\n🎉 测试数据创建完成！现在仪表盘应该能显示真实的待审核数据了。\n";
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    echo "❌ 操作过程中发生错误: " . $e->getMessage() . "\n";
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
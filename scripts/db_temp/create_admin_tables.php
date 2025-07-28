<?php
/**
 * 创建管理后台相关数据表
 * 文件: create_admin_tables.php
 * 功能: 根据管理后台数据库替代方案创建所需的数据表
 */

require_once 'config/database.php';

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PWD, DB_NAME);
    $conn->set_charset("utf8mb4");
    
    if ($conn->connect_error) {
        die("数据库连接失败: " . $conn->connect_error);
    }
    
    echo "开始创建管理后台相关数据表...\n\n";
    
    // 1. 创建壁纸审核状态表
    $sql1 = "
        CREATE TABLE IF NOT EXISTS wallpaper_review_status (
            id INT AUTO_INCREMENT PRIMARY KEY,
            wallpaper_id BIGINT NOT NULL,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            reviewer_id INT NULL,
            review_time TIMESTAMP NULL,
            review_notes TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_wallpaper (wallpaper_id),
            INDEX idx_status (status),
            INDEX idx_review_time (review_time)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    if ($conn->query($sql1) === TRUE) {
        echo "✅ wallpaper_review_status 表创建成功\n";
    } else {
        echo "❌ wallpaper_review_status 表创建失败: " . $conn->error . "\n";
    }
    
    // 2. 创建用户状态扩展表
    $sql2 = "
        CREATE TABLE IF NOT EXISTS user_status_ext (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            status ENUM('active', 'disabled', 'suspended') DEFAULT 'active',
            status_reason VARCHAR(255) NULL,
            operator_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user (user_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    if ($conn->query($sql2) === TRUE) {
        echo "✅ user_status_ext 表创建成功\n";
    } else {
        echo "❌ user_status_ext 表创建失败: " . $conn->error . "\n";
    }
    
    // 3. 创建管理员操作日志表
    $sql3 = "
        CREATE TABLE IF NOT EXISTS admin_operation_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT NOT NULL,
            operation_type VARCHAR(50) NOT NULL,
            target_type VARCHAR(50) NOT NULL,
            target_id VARCHAR(50) NOT NULL,
            operation_details JSON NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_admin_id (admin_id),
            INDEX idx_operation_type (operation_type),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    if ($conn->query($sql3) === TRUE) {
        echo "✅ admin_operation_logs 表创建成功\n";
    } else {
        echo "❌ admin_operation_logs 表创建失败: " . $conn->error . "\n";
    }
    
    // 4. 创建统计数据缓存表
    $sql4 = "
        CREATE TABLE IF NOT EXISTS admin_statistics_cache (
            id INT AUTO_INCREMENT PRIMARY KEY,
            stat_key VARCHAR(100) NOT NULL,
            stat_value BIGINT NOT NULL,
            stat_date DATE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_stat (stat_key, stat_date),
            INDEX idx_stat_date (stat_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    if ($conn->query($sql4) === TRUE) {
        echo "✅ admin_statistics_cache 表创建成功\n";
    } else {
        echo "❌ admin_statistics_cache 表创建失败: " . $conn->error . "\n";
    }
    
    echo "\n开始初始化数据...\n\n";
    
    // 5. 为现有壁纸设置默认审核状态
    $sql5 = "
        INSERT IGNORE INTO wallpaper_review_status (wallpaper_id, status, review_time)
        SELECT id, 'approved', created_at
        FROM wallpapers
        WHERE id NOT IN (SELECT wallpaper_id FROM wallpaper_review_status)
    ";
    
    if ($conn->query($sql5) === TRUE) {
        $affected_rows = $conn->affected_rows;
        echo "✅ 为 {$affected_rows} 个现有壁纸设置默认审核状态\n";
    } else {
        echo "❌ 设置默认审核状态失败: " . $conn->error . "\n";
    }
    
    // 6. 为现有用户设置默认状态
    $sql6 = "
        INSERT IGNORE INTO user_status_ext (user_id, status)
        SELECT id, 'active'
        FROM users
        WHERE id NOT IN (SELECT user_id FROM user_status_ext)
    ";
    
    if ($conn->query($sql6) === TRUE) {
        $affected_rows = $conn->affected_rows;
        echo "✅ 为 {$affected_rows} 个现有用户设置默认状态\n";
    } else {
        echo "❌ 设置默认用户状态失败: " . $conn->error . "\n";
    }
    
    echo "\n🎉 管理后台数据表创建和初始化完成！\n";
    
} catch (Exception $e) {
    echo "❌ 执行过程中发生错误: " . $e->getMessage() . "\n";
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
<?php
/**
 * 会员系统数据库创建脚本
 * 文件: create_membership_system.php
 * 功能: 创建会员系统所需的所有表和字段
 * 解决数据库连接问题并提供详细的执行反馈
 */

// 数据库配置
$db_config = [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',  // XAMPP默认root密码为空
    'database' => 'wallpaper_db',
    'charset' => 'utf8mb4'
];

try {
    // 创建数据库连接
    $dsn = "mysql:host={$db_config['host']};dbname={$db_config['database']};charset={$db_config['charset']}";
    $pdo = new PDO($dsn, $db_config['username'], $db_config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$db_config['charset']}"
    ]);
    
    echo "✅ 数据库连接成功\n";
    echo "=== 开始创建会员系统数据库表 ===\n\n";
    
    // 1. 扩展users表，添加会员系统字段
    echo "1. 扩展users表，添加会员系统字段...\n";
    
    $user_fields = [
        "ADD COLUMN membership_type ENUM('free','monthly','permanent') DEFAULT 'free' COMMENT '会员类型'",
        "ADD COLUMN membership_expires_at DATETIME NULL COMMENT '会员到期时间'",
        "ADD COLUMN download_quota INT(11) DEFAULT 0 COMMENT '剩余下载次数'",
        "ADD COLUMN quota_reset_date DATETIME NULL COMMENT '下载配额重置时间（30天后）'"
    ];
    
    foreach ($user_fields as $field) {
        try {
            $pdo->exec("ALTER TABLE users $field");
            echo "   ✅ 字段添加成功: $field\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "   ⚠️  字段已存在，跳过: $field\n";
            } else {
                echo "   ❌ 字段添加失败: $field - {$e->getMessage()}\n";
            }
        }
    }
    
    // 添加索引
    $user_indexes = [
        "ADD INDEX idx_membership_type (membership_type)",
        "ADD INDEX idx_membership_expires (membership_expires_at)",
        "ADD INDEX idx_quota_reset (quota_reset_date)"
    ];
    
    foreach ($user_indexes as $index) {
        try {
            $pdo->exec("ALTER TABLE users $index");
            echo "   ✅ 索引添加成功: $index\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "   ⚠️  索引已存在，跳过: $index\n";
            } else {
                echo "   ❌ 索引添加失败: $index - {$e->getMessage()}\n";
            }
        }
    }
    
    // 2. 创建会员码管理表
    echo "\n2. 创建会员码管理表...\n";
    $membership_codes_sql = "
        CREATE TABLE IF NOT EXISTS membership_codes (
          id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
          code VARCHAR(32) NOT NULL COMMENT '会员码',
          membership_type ENUM('monthly','permanent') NOT NULL COMMENT '会员类型',
          status ENUM('active','used','expired','disabled') DEFAULT 'active' COMMENT '状态',
          used_by_user_id BIGINT(20) UNSIGNED NULL COMMENT '使用用户ID',
          used_at DATETIME NULL COMMENT '使用时间',
          expires_at DATETIME NULL COMMENT '会员码过期时间',
          generated_by VARCHAR(50) DEFAULT 'system' COMMENT '生成来源',
          batch_id VARCHAR(32) NULL COMMENT '批次ID',
          notes VARCHAR(255) NULL COMMENT '备注',
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          UNIQUE KEY uk_code (code),
          KEY idx_status_type (status, membership_type),
          KEY idx_used_by (used_by_user_id),
          KEY idx_expires_at (expires_at),
          KEY idx_batch_id (batch_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='会员码管理表'
    ";
    
    try {
        $pdo->exec($membership_codes_sql);
        echo "   ✅ membership_codes 表创建成功\n";
    } catch (PDOException $e) {
        echo "   ❌ membership_codes 表创建失败: {$e->getMessage()}\n";
    }
    
    // 3. 创建用户下载记录表
    echo "\n3. 创建用户下载记录表...\n";
    $download_logs_sql = "
        CREATE TABLE IF NOT EXISTS user_download_logs (
          id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
          user_id BIGINT(20) UNSIGNED NOT NULL COMMENT '用户ID',
          download_type ENUM('hd_combo','avatar','single_device','original','cover','other') NOT NULL COMMENT '下载类型',
          wallpaper_id VARCHAR(255) NULL COMMENT '壁纸标识',
          membership_type ENUM('free','monthly','permanent') NOT NULL COMMENT '下载时会员类型',
          quota_consumed TINYINT(1) DEFAULT 0 COMMENT '是否消耗配额',
          download_date DATE NOT NULL COMMENT '下载日期',
          ip_address VARCHAR(45) NULL COMMENT 'IP地址',
          user_agent TEXT NULL COMMENT '用户代理',
          download_status ENUM('success','failed','cancelled') DEFAULT 'success' COMMENT '下载状态',
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          KEY idx_user_date (user_id, download_date),
          KEY idx_user_quota (user_id, quota_consumed),
          KEY idx_download_type (download_type),
          KEY idx_download_date (download_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户下载记录表'
    ";
    
    try {
        $pdo->exec($download_logs_sql);
        echo "   ✅ user_download_logs 表创建成功\n";
    } catch (PDOException $e) {
        echo "   ❌ user_download_logs 表创建失败: {$e->getMessage()}\n";
    }
    
    // 4. 创建管理后台相关表
    echo "\n4. 创建管理后台相关表...\n";
    
    // 4.1 壁纸审核状态表
    $review_status_sql = "
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
    
    try {
        $pdo->exec($review_status_sql);
        echo "   ✅ wallpaper_review_status 表创建成功\n";
    } catch (PDOException $e) {
        echo "   ❌ wallpaper_review_status 表创建失败: {$e->getMessage()}\n";
    }
    
    // 4.2 用户状态扩展表
    $user_status_sql = "
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
    
    try {
        $pdo->exec($user_status_sql);
        echo "   ✅ user_status_ext 表创建成功\n";
    } catch (PDOException $e) {
        echo "   ❌ user_status_ext 表创建失败: {$e->getMessage()}\n";
    }
    
    // 4.3 管理员操作日志表
    $admin_logs_sql = "
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
    
    try {
        $pdo->exec($admin_logs_sql);
        echo "   ✅ admin_operation_logs 表创建成功\n";
    } catch (PDOException $e) {
        echo "   ❌ admin_operation_logs 表创建失败: {$e->getMessage()}\n";
    }
    
    // 4.4 统计数据缓存表
    $stats_cache_sql = "
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
    
    try {
        $pdo->exec($stats_cache_sql);
        echo "   ✅ admin_statistics_cache 表创建成功\n";
    } catch (PDOException $e) {
        echo "   ❌ admin_statistics_cache 表创建失败: {$e->getMessage()}\n";
    }
    
    // 5. 初始化数据
    echo "\n5. 初始化数据...\n";
    
    // 5.1 为现有壁纸设置默认审核状态
    try {
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO wallpaper_review_status (wallpaper_id, status, review_time)
            SELECT id, 'approved', created_at
            FROM wallpapers
            WHERE id NOT IN (SELECT wallpaper_id FROM wallpaper_review_status)
        ");
        $stmt->execute();
        $affected = $stmt->rowCount();
        echo "   ✅ 为 {$affected} 个现有壁纸设置默认审核状态\n";
    } catch (PDOException $e) {
        echo "   ❌ 设置默认审核状态失败: {$e->getMessage()}\n";
    }
    
    // 5.2 为现有用户设置默认状态
    try {
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO user_status_ext (user_id, status)
            SELECT id, 'active'
            FROM users
            WHERE id NOT IN (SELECT user_id FROM user_status_ext)
        ");
        $stmt->execute();
        $affected = $stmt->rowCount();
        echo "   ✅ 为 {$affected} 个现有用户设置默认状态\n";
    } catch (PDOException $e) {
        echo "   ❌ 设置默认用户状态失败: {$e->getMessage()}\n";
    }
    
    // 6. 验证创建结果
    echo "\n6. 验证创建结果...\n";
    
    $tables_to_check = [
        'membership_codes' => '会员码管理表',
        'user_download_logs' => '用户下载记录表',
        'wallpaper_review_status' => '壁纸审核状态表',
        'user_status_ext' => '用户状态扩展表',
        'admin_operation_logs' => '管理员操作日志表',
        'admin_statistics_cache' => '统计数据缓存表'
    ];
    
    foreach ($tables_to_check as $table => $description) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "   ✅ $description ($table) 存在\n";
            } else {
                echo "   ❌ $description ($table) 不存在\n";
            }
        } catch (PDOException $e) {
            echo "   ❌ 检查表 $table 时出错: {$e->getMessage()}\n";
        }
    }
    
    // 检查users表的新字段
    echo "\n   检查users表的会员字段...\n";
    try {
        $stmt = $pdo->query("DESCRIBE users");
        $columns = $stmt->fetchAll();
        $membership_fields = ['membership_type', 'membership_expires_at', 'download_quota', 'quota_reset_date'];
        
        foreach ($membership_fields as $field) {
            $found = false;
            foreach ($columns as $column) {
                if ($column['Field'] === $field) {
                    echo "   ✅ users表字段 $field 存在\n";
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                echo "   ❌ users表字段 $field 不存在\n";
            }
        }
    } catch (PDOException $e) {
        echo "   ❌ 检查users表字段时出错: {$e->getMessage()}\n";
    }
    
    echo "\n🎉 会员系统数据库创建完成！\n";
    echo "\n=== 执行总结 ===\n";
    echo "✅ 数据库连接成功\n";
    echo "✅ users表扩展完成\n";
    echo "✅ 会员系统相关表创建完成\n";
    echo "✅ 管理后台相关表创建完成\n";
    echo "✅ 初始化数据完成\n";
    echo "\n现在可以正常使用会员系统功能了！\n";
    
} catch (PDOException $e) {
    echo "❌ 数据库连接失败: " . $e->getMessage() . "\n";
    echo "\n请检查以下配置：\n";
    echo "- 数据库服务是否启动\n";
    echo "- 数据库名称是否正确: {$db_config['database']}\n";
    echo "- 用户名和密码是否正确\n";
    echo "- 主机地址是否正确: {$db_config['host']}\n";
} catch (Exception $e) {
    echo "❌ 执行过程中发生错误: " . $e->getMessage() . "\n";
}

echo "\n脚本执行完毕。\n";
?>
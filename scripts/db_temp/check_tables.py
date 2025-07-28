import mysql.connector

try:
    # 连接数据库
    conn = mysql.connector.connect(
        host='localhost',
        user='root',
        password='',
        database='wallpaper_db'
    )
    cursor = conn.cursor()
    
    # 查看所有表
    cursor.execute('SHOW TABLES')
    tables = cursor.fetchall()
    
    print('数据库中的所有表:')
    for table in tables:
        print(f'- {table[0]}')
    
    # 检查管理相关表
    admin_tables = []
    for table in tables:
        table_name = table[0]
        if 'review' in table_name or 'admin' in table_name or 'user_status' in table_name:
            admin_tables.append(table_name)
    
    print(f'\n管理相关表: {admin_tables}')
    
    # 如果没有管理表，尝试创建
    if not admin_tables:
        print('\n没有找到管理表，开始创建...')
        
        # 创建壁纸审核状态表
        cursor.execute("""
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
        """)
        print('创建 wallpaper_review_status 表')
        
        # 创建用户状态扩展表
        cursor.execute("""
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
        """)
        print('创建 user_status_ext 表')
        
        # 创建管理员操作日志表
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS admin_operation_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                admin_id INT NOT NULL,
                operation_type VARCHAR(50) NOT NULL,
                target_type VARCHAR(50) NOT NULL,
                target_id VARCHAR(50) NOT NULL,
                operation_details TEXT NULL,
                ip_address VARCHAR(45) NULL,
                user_agent TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_admin_id (admin_id),
                INDEX idx_operation_type (operation_type),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        """)
        print('创建 admin_operation_logs 表')
        
        conn.commit()
        print('\n管理表创建完成！')
        
        # 为现有壁纸设置默认审核状态
        cursor.execute("""
            INSERT IGNORE INTO wallpaper_review_status (wallpaper_id, status, review_time)
            SELECT id, 'approved', upload_time
            FROM wallpapers
        """)
        
        # 为现有用户设置默认状态
        cursor.execute("""
            INSERT IGNORE INTO user_status_ext (user_id, status)
            SELECT id, 'active'
            FROM users
        """)
        
        conn.commit()
        print('初始化数据完成！')
    
    conn.close()
    
except Exception as e:
    print(f'错误: {e}')
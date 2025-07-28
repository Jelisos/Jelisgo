import mysql.connector

try:
    conn = mysql.connector.connect(
        host='localhost',
        user='root',
        password='',
        database='wallpaper_db'
    )
    cursor = conn.cursor()
    
    print('开始初始化管理表数据...')
    
    # 为现有壁纸设置默认审核状态（使用created_at字段）
    cursor.execute("""
        INSERT IGNORE INTO wallpaper_review_status (wallpaper_id, status, review_time)
        SELECT id, 'approved', created_at
        FROM wallpapers
    """)
    affected_rows = cursor.rowcount
    print(f'为 {affected_rows} 个壁纸设置了审核状态')
    
    # 为现有用户设置默认状态
    cursor.execute("""
        INSERT IGNORE INTO user_status_ext (user_id, status)
        SELECT id, 'active'
        FROM users
    """)
    affected_rows = cursor.rowcount
    print(f'为 {affected_rows} 个用户设置了状态')
    
    conn.commit()
    
    # 检查结果
    cursor.execute('SELECT COUNT(*) FROM wallpaper_review_status')
    review_count = cursor.fetchone()[0]
    print(f'审核状态表中有 {review_count} 条记录')
    
    cursor.execute('SELECT COUNT(*) FROM user_status_ext')
    user_status_count = cursor.fetchone()[0]
    print(f'用户状态表中有 {user_status_count} 条记录')
    
    cursor.execute('SELECT COUNT(*) FROM admin_operation_logs')
    log_count = cursor.fetchone()[0]
    print(f'操作日志表中有 {log_count} 条记录')
    
    print('\n管理表初始化完成！')
    
    conn.close()
    
except Exception as e:
    print(f'错误: {e}')
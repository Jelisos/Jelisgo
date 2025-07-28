#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
创建管理员日志表
位置: create_admin_logs_table.py
"""

import mysql.connector
from mysql.connector import Error

def create_admin_logs_table():
    try:
        # 连接数据库
        connection = mysql.connector.connect(
            host='localhost',
            database='wallpaper_db',
            user='root',
            password=''
        )
        
        if connection.is_connected():
            cursor = connection.cursor()
            
            # 创建管理员日志表
            create_table_query = """
            CREATE TABLE IF NOT EXISTS admin_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                admin_id INT NOT NULL,
                action VARCHAR(100) NOT NULL,
                details TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_admin_id (admin_id),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            """
            
            cursor.execute(create_table_query)
            connection.commit()
            
            print("✅ 管理员日志表创建成功")
            
            # 检查表结构
            cursor.execute("DESCRIBE admin_logs")
            columns = cursor.fetchall()
            
            print("\n📋 表结构:")
            for column in columns:
                print(f"  {column[0]} - {column[1]} - {column[2]} - {column[3]} - {column[4]} - {column[5]}")
            
            # 检查现有记录数
            cursor.execute("SELECT COUNT(*) FROM admin_logs")
            count = cursor.fetchone()[0]
            print(f"\n📊 当前记录数: {count}")
            
    except Error as e:
        print(f"❌ 数据库错误: {e}")
    
    finally:
        if connection.is_connected():
            cursor.close()
            connection.close()
            print("\n🔌 数据库连接已关闭")

if __name__ == "__main__":
    create_admin_logs_table()
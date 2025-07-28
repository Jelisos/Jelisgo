#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
数据库表结构查询脚本
文件: check_tables.py
功能: 查看数据库中的所有表
"""

import mysql.connector
import sys

def get_db_connection():
    """获取数据库连接"""
    try:
        connection = mysql.connector.connect(
            host='localhost',
            database='wallpaper_db',
            user='root',
            password='',
            charset='utf8mb4',
            collation='utf8mb4_unicode_ci'
        )
        return connection
    except mysql.connector.Error as e:
        print(f"数据库连接失败: {e}")
        return None

def show_all_tables():
    """显示所有表"""
    conn = get_db_connection()
    if not conn:
        return
    
    try:
        cursor = conn.cursor()
        cursor.execute("SHOW TABLES")
        tables = cursor.fetchall()
        
        print("=== 数据库中的所有表 ===")
        for table in tables:
            print(f"- {table[0]}")
        
        # 检查是否有categories表
        table_names = [table[0] for table in tables]
        if 'categories' in table_names:
            print("\n=== categories表结构 ===")
            cursor.execute("DESCRIBE categories")
            columns = cursor.fetchall()
            for col in columns:
                print(f"{col[0]} | {col[1]} | {col[2]} | {col[3]} | {col[4]}")
        else:
            print("\n❌ categories表不存在")
            
    except mysql.connector.Error as e:
        print(f"查询失败: {e}")
    finally:
        if conn.is_connected():
            cursor.close()
            conn.close()

if __name__ == "__main__":
    show_all_tables()
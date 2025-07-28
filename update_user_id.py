#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
文件: update_user_id.py
描述: 独立脚本，专门用于更新数据库中所有壁纸记录的user_id为'Jelisgo'
创建时间: 2024-12-19
维护: 修改用户ID更新逻辑请编辑此文件
"""

import pymysql
import sys
from datetime import datetime

# 数据库配置
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'wallpaper_db'
}

def test_database_connection():
    """
    测试数据库连接
    @returns {bool} - 连接是否成功
    """
    try:
        conn = pymysql.connect(
            host=DB_CONFIG['host'],
            user=DB_CONFIG['user'],
            password=DB_CONFIG['password'],
            database=DB_CONFIG['database'],
            charset='utf8mb4'
        )
        conn.close()
        print("✅ 数据库连接测试成功")
        return True
    except Exception as e:
        print(f"❌ 数据库连接失败: {e}")
        return False

def get_current_user_id_stats():
    """
    获取当前数据库中user_id的统计信息
    @returns {dict} - 统计信息字典
    """
    conn = None
    try:
        conn = pymysql.connect(
            host=DB_CONFIG['host'],
            user=DB_CONFIG['user'],
            password=DB_CONFIG['password'],
            database=DB_CONFIG['database'],
            charset='utf8mb4'
        )
        cursor = conn.cursor()
        
        # 统计总记录数
        cursor.execute("SELECT COUNT(*) FROM wallpapers")
        total_count = cursor.fetchone()[0]
        
        # 统计NULL的记录数
        cursor.execute("SELECT COUNT(*) FROM wallpapers WHERE user_id IS NULL")
        null_count = cursor.fetchone()[0]
        
        # 统计已经是'Jelisgo'的记录数
        cursor.execute("SELECT COUNT(*) FROM wallpapers WHERE user_id = 'Jelisgo'")
        jelisgo_count = cursor.fetchone()[0]
        
        # 统计其他user_id的记录数
        cursor.execute("SELECT COUNT(*) FROM wallpapers WHERE user_id IS NOT NULL AND user_id != 'Jelisgo'")
        other_count = cursor.fetchone()[0]
        
        # 获取所有不同的user_id值
        cursor.execute("SELECT DISTINCT user_id FROM wallpapers WHERE user_id IS NOT NULL")
        distinct_user_ids = [row[0] for row in cursor.fetchall()]
        
        return {
            'total': total_count,
            'null': null_count,
            'jelisgo': jelisgo_count,
            'other': other_count,
            'distinct_user_ids': distinct_user_ids
        }
    except Exception as e:
        print(f"❌ 获取统计信息失败: {e}")
        return None
    finally:
        if conn:
            conn.close()

def ensure_jelisgo_user_exists():
    """
    确保'Jelisgo'用户在users表中存在，如果不存在则创建
    @returns {bool} - 操作是否成功
    """
    conn = None
    try:
        conn = pymysql.connect(
            host=DB_CONFIG['host'],
            user=DB_CONFIG['user'],
            password=DB_CONFIG['password'],
            database=DB_CONFIG['database'],
            charset='utf8mb4'
        )
        cursor = conn.cursor()
        
        # 检查'Jelisgo'用户是否存在
        cursor.execute("SELECT id FROM users WHERE username = 'Jelisgo'")
        result = cursor.fetchone()
        
        if result:
            print(f"✅ 用户'Jelisgo'已存在，ID: {result[0]}")
            return True
        else:
            # 创建'Jelisgo'用户
            print("🔄 用户'Jelisgo'不存在，正在创建...")
            cursor.execute("""
                INSERT INTO users (username, email, password, created_at) 
                VALUES ('Jelisgo', 'jelisgo@example.com', 'placeholder_password', NOW())
            """)
            conn.commit()
            print("✅ 用户'Jelisgo'创建成功")
            return True
            
    except Exception as e:
        print(f"❌ 处理用户'Jelisgo'失败: {e}")
        if conn:
            conn.rollback()
        return False
    finally:
        if conn:
            conn.close()

def update_user_ids_to_jelisgo():
    """
    更新数据库中所有壁纸记录的user_id为'Jelisgo'
    @returns {bool} - 更新是否成功
    """
    conn = None
    try:
        conn = pymysql.connect(
            host=DB_CONFIG['host'],
            user=DB_CONFIG['user'],
            password=DB_CONFIG['password'],
            database=DB_CONFIG['database'],
            charset='utf8mb4'
        )
        cursor = conn.cursor()
        
        # 获取'Jelisgo'用户的ID
        cursor.execute("SELECT id FROM users WHERE username = 'Jelisgo'")
        result = cursor.fetchone()
        
        if not result:
            print("❌ 找不到用户'Jelisgo'")
            return False
            
        jelisgo_user_id = result[0]
        print(f"📋 将使用用户ID: {jelisgo_user_id} (Jelisgo)")
        
        # 更新所有记录的user_id为Jelisgo的用户ID
        cursor.execute("UPDATE wallpapers SET user_id = %s WHERE user_id IS NULL OR user_id != %s", 
                      (jelisgo_user_id, jelisgo_user_id))
        affected_rows = cursor.rowcount
        
        # 提交事务
        conn.commit()
        
        print(f"✅ 成功更新 {affected_rows} 条记录的user_id为{jelisgo_user_id} (Jelisgo)")
        return True
        
    except Exception as e:
        print(f"❌ 更新user_id失败: {e}")
        if conn:
            conn.rollback()
        return False
    finally:
        if conn:
            conn.close()

def main():
    """
    主函数：执行user_id更新操作
    """
    print("🚀 开始更新数据库中壁纸记录的user_id...")
    print(f"⏰ 执行时间: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    print("-" * 50)
    
    # 1. 测试数据库连接
    if not test_database_connection():
        print("💥 数据库连接失败，程序退出")
        sys.exit(1)
    
    # 2. 获取更新前的统计信息
    print("\n📊 更新前的统计信息:")
    stats_before = get_current_user_id_stats()
    if stats_before:
        print(f"   总记录数: {stats_before['total']}")
        print(f"   user_id为NULL的记录: {stats_before['null']}")
        print(f"   user_id为'Jelisgo'的记录: {stats_before['jelisgo']}")
        print(f"   user_id为其他值的记录: {stats_before['other']}")
        if stats_before['distinct_user_ids']:
            print(f"   当前存在的user_id值: {stats_before['distinct_user_ids']}")
    else:
        print("❌ 无法获取统计信息")
        sys.exit(1)
    
    # 3. 确保Jelisgo用户存在
    print("\n👤 检查并确保Jelisgo用户存在...")
    if not ensure_jelisgo_user_exists():
        print("💥 无法创建或找到Jelisgo用户，程序退出")
        sys.exit(1)
    
    # 4. 执行更新操作
    print("\n🔄 执行更新操作...")
    if update_user_ids_to_jelisgo():
        # 5. 获取更新后的统计信息
        print("\n📊 更新后的统计信息:")
        stats_after = get_current_user_id_stats()
        if stats_after:
            print(f"   总记录数: {stats_after['total']}")
            print(f"   user_id为NULL的记录: {stats_after['null']}")
            print(f"   user_id为'Jelisgo'的记录: {stats_after['jelisgo']}")
            print(f"   user_id为其他值的记录: {stats_after['other']}")
            if stats_after['distinct_user_ids']:
                print(f"   当前存在的user_id值: {stats_after['distinct_user_ids']}")
        
        print("\n✨ 所有操作完成！")
    else:
        print("\n💥 更新操作失败")
        sys.exit(1)

if __name__ == '__main__':
    main()
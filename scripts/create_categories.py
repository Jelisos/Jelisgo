#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
创建分类表脚本
文件: create_categories.py
功能: 创建categories表和初始化数据
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

def create_categories_table():
    """创建分类表"""
    conn = get_db_connection()
    if not conn:
        return False
    
    try:
        cursor = conn.cursor()
        
        # 创建categories表
        create_table_sql = """
        CREATE TABLE IF NOT EXISTS `categories` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `name` varchar(100) NOT NULL COMMENT '分类名称',
          `description` text COMMENT '分类描述',
          `sort_order` int(11) DEFAULT 0 COMMENT '排序顺序',
          `is_active` tinyint(1) DEFAULT 1 COMMENT '是否启用 1=启用 0=禁用',
          `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
          `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
          PRIMARY KEY (`id`),
          UNIQUE KEY `name` (`name`),
          KEY `sort_order` (`sort_order`),
          KEY `is_active` (`is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='壁纸分类表'
        """
        
        cursor.execute(create_table_sql)
        print("✅ categories表创建成功")
        
        # 插入默认分类数据
        insert_data_sql = """
        INSERT IGNORE INTO `categories` (`name`, `description`, `sort_order`, `is_active`) VALUES
        ('风景', '自然风景、山水景色等壁纸', 1, 1),
        ('动漫', '动漫角色、二次元相关壁纸', 2, 1),
        ('游戏', '游戏截图、游戏角色壁纸', 3, 1),
        ('科技', '科技感、未来感壁纸', 4, 1),
        ('抽象', '抽象艺术、几何图案壁纸', 5, 1),
        ('动物', '可爱动物、宠物壁纸', 6, 1),
        ('汽车', '跑车、摩托车等交通工具壁纸', 7, 1),
        ('建筑', '建筑物、城市景观壁纸', 8, 1),
        ('其他', '其他类型壁纸', 99, 1)
        """
        
        cursor.execute(insert_data_sql)
        print("✅ 默认分类数据插入成功")
        
        # 检查wallpapers表是否已有category_id字段
        cursor.execute("DESCRIBE wallpapers")
        columns = cursor.fetchall()
        column_names = [col[0] for col in columns]
        
        if 'category_id' not in column_names:
            # 为wallpapers表添加category_id字段
            alter_table_sql = """
            ALTER TABLE `wallpapers` 
            ADD COLUMN `category_id` int(11) DEFAULT NULL COMMENT '分类ID' AFTER `user_id`,
            ADD KEY `category_id` (`category_id`)
            """
            cursor.execute(alter_table_sql)
            print("✅ wallpapers表添加category_id字段成功")
            
            # 添加外键约束
            try:
                fk_sql = """
                ALTER TABLE `wallpapers` 
                ADD CONSTRAINT `fk_wallpapers_category` 
                FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) 
                ON DELETE SET NULL ON UPDATE CASCADE
                """
                cursor.execute(fk_sql)
                print("✅ 外键约束添加成功")
            except mysql.connector.Error as e:
                print(f"⚠️ 外键约束添加失败（可能已存在）: {e}")
        else:
            print("✅ wallpapers表已有category_id字段")
        
        conn.commit()
        return True
        
    except mysql.connector.Error as e:
        print(f"创建表失败: {e}")
        conn.rollback()
        return False
    finally:
        if conn.is_connected():
            cursor.close()
            conn.close()

if __name__ == "__main__":
    print("开始创建categories表...")
    if create_categories_table():
        print("\n🎉 categories表创建完成！")
    else:
        print("\n❌ categories表创建失败！")
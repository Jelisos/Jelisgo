import os
import json
import time
from datetime import datetime
from PIL import Image
import pymysql
import json.decoder # 2024-07-15 新增：导入JSON解码器，用于捕获特定错误

# 数据库配置
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'wallpaper_db'
}

# 预定义的分类和标签映射
CATEGORY_TAGS = {
    '风景': ['自然', '山水', '天空', '海洋', '森林', '日出', '日落', '风景'],
    '动物': ['宠物', '野生动物', '鸟类', '海洋生物', '动物'],
    '建筑': ['城市', '建筑', '室内', '街道', '建筑'],
    '艺术': ['抽象', '艺术', '创意', '设计', '绘画', '数字艺术'],
    '人物': ['人像', '肖像', '生活', '男性', '女性', '孩子'],
    '科技': ['科技', '未来', '科幻', '机器人', '太空'],
    '美食': ['食物', '饮料', '甜点', '水果', '蔬菜'],
    '运动': ['体育', '运动', '健身', '竞技'],
    '幻想': ['神话', '魔幻', '怪兽', '恶魔', '天使', '龙', '魔法', '异世界'],
    '其他': ['抽象', '简约', '纹理', '通用']
}

# 支持的图片格式
SUPPORTED_FORMATS = ('.jpg', '.jpeg', '.png', '.gif', '.webp', '.bmp')

def get_image_dimensions(image_path):
    """获取图片尺寸"""
    try:
        with Image.open(image_path) as img:
            return img.size  # 返回 (width, height)
    except Exception as e:
        print(f"Warning: Cannot get dimensions for {image_path}: {e}")
        return (0, 0)

def analyze_filename(filename):
    """
    根据文件名智能判断分类，但标签始终为空
    @param {str} filename - 图片文件名
    @returns {Object} - 包含分类和空标签的字典
    """
    filename_lower = filename.lower()
    category = '其他'
    
    # 2024-07-15 用户要求标签为空，因此注释掉所有标签生成逻辑
    # tags = set() # 使用set避免重复

    # 分类关键词映射 - 按优先级排序，优化分类判断逻辑
    category_keywords = {
        '幻想': ['fantasy', 'mythical', 'monster', 'demon', 'angel', 'dragon', '幻想', '神话', 
                '魔幻', '怪兽', '恶魔', '天使', '龙', '巨兽', '巨眼', '废土', '鲛人', '魔物', '折翼天使',
                '末日', '异世界', '魔法', '神秘', '超现实'],
        '人物': ['portrait', 'people', 'person', '人物', '美女', '少年', '公主', '御姐', 
                '克杰逊', '杰克逊', '神秘人', '赛博人机女', '雨夜撑伞女', '少女', '女孩', '男性',
                '女性', '肖像', '人像', 'cos', 'cosplay', '明星', '合影'],
        '动物': ['animal', 'pet', 'bird', 'wildlife', '动物', '犬', '猪', '鹿', '狐狸', '猫', '狼人',
                '小猫', '小鹿', '八戒', '竹编', '萌兽', '丘比特'],
        '科技': ['tech', 'future', 'sci-fi', '科技', '太空航行', '赛博', 'cyber', '机械', '数字人',
                '全息', '投影', '电子', '集市'],
        '风景': ['landscape', 'nature', 'mountain', 'sea', 'sky', '风景', '自然', '山水', '破晓',
                '星芒', '祥云', '青月'],
        '建筑': ['building', 'city', 'architecture', 'street', '建筑', '城市', '工厂'],
        '艺术': ['art', 'abstract', 'design', '艺术', '时光之翼', '星芒破晓', '炭笔', 
                '血色残阳', '详云字体', '睡梦公式', '光绘', '故障', '克莱因蓝', '4k标志',
                '手机壳', '花环', '曼陀沙华'],
        '美食': ['food', 'drink', 'dessert', '美食'],
        '运动': ['sport', 'fitness', 'exercise', '运动']
    }

    # 判断分类
    for cat, keywords in category_keywords.items():
        if any(keyword in filename_lower for keyword in keywords):
            category = cat
            break

    # 2024-07-15 用户要求标签为空，因此不生成任何标签
    final_tags = []
    
    return {
        'category': category,
        'tags': final_tags
    }

def escape_sql_string(text):
    """转义SQL字符串中的特殊字符"""
    if text is None:
        return ''
    return text.replace("'", "\\'").replace('"', '\\"').replace('\\', '\\\\')

def generate_unique_id(base_time, index, used_ids):
    """生成唯一的数字ID"""
    unique_id = int(f"{base_time}{index:04d}")
    while unique_id in used_ids:
        unique_id += 1
    used_ids.add(unique_id)
    return unique_id

def get_existing_wallpapers_from_db():
    """
    查询数据库，获取所有已存在的壁纸文件名和ID
    @returns {dict} - {filename: id}
    """
    conn = None
    result = {}
    try:
        conn = pymysql.connect(
            host=DB_CONFIG['host'],
            user=DB_CONFIG['user'],
            password=DB_CONFIG['password'],
            database=DB_CONFIG['database'],
            charset='utf8mb4'
        )
        cursor = conn.cursor()
        cursor.execute("SELECT file_path, id FROM wallpapers")
        for row in cursor.fetchall():
            file_path = row[0]
            filename = os.path.basename(file_path)
            result[filename] = row[1]
    except Exception as e:
        print(f"❌ 数据库查询失败: {e}")
        return None
    finally:
        if conn:
            conn.close()
    return result

# 已移除get_all_wallpapers_from_db函数，因为不再需要生成list.json

def generate_short_id(date_str, seq):
    """
    生成短ID，格式为YYYYMMDD+递增号（如202507151、20250715100），递增号不做位数限制
    @param {str} date_str - 日期字符串YYYYMMDD
    @param {int} seq - 当天递增序号
    @returns {int}
    """
    return int(f"{date_str}{seq}")



def find_latest_period():
    """
    倒序扫描期数目录，找到最新的非空期数
    @returns {str} - 最新期数，如'003'，如果没有找到返回'001'
    """
    base_dir = os.path.dirname(__file__)
    wallpapers_base = os.path.join(base_dir, 'static', 'wallpapers')
    
    if not os.path.exists(wallpapers_base):
        print(f"❌ 壁纸基础目录不存在: {wallpapers_base}")
        return '001'
    
    # 扫描所有期数目录
    period_dirs = []
    for item in os.listdir(wallpapers_base):
        item_path = os.path.join(wallpapers_base, item)
        if os.path.isdir(item_path) and item.isdigit() and len(item) == 3:
            period_dirs.append(item)
    
    if not period_dirs:
        print("📁 未找到期数目录，使用默认期数001")
        return '001'
    
    # 倒序排列期数
    period_dirs.sort(reverse=True)
    
    # 从最新期数开始检查，找到第一个非空目录
    for period in period_dirs:
        period_path = os.path.join(wallpapers_base, period)
        image_files = [
            f for f in os.listdir(period_path)
            if os.path.isfile(os.path.join(period_path, f))
            and f.lower().endswith(SUPPORTED_FORMATS)
        ]
        if image_files:
            print(f"🎯 找到最新期数: {period} (包含 {len(image_files)} 个图片文件)")
            return period
    
    print("📁 所有期数目录都为空，使用默认期数001")
    return '001'

def update_wallpaper_list(period=None, auto_upload=False):
    """
    增量更新壁纸列表，只为新图片分配新ID并导入，老图片ID不变
    @param {str} period - 指定期数，如'002'，如果为None则自动检测最新期数
    @param {bool} auto_upload - 是否自动上传到数据库
    """
    # 自动检测最新期数
    if period is None:
        period = find_latest_period()
    
    # 路径配置
    base_dir = os.path.dirname(__file__)
    wallpapers_dir = os.path.join(base_dir, 'static', 'wallpapers', period)
    sql_path = os.path.join(base_dir, f'wallpapers_import_{period}.sql')
    
    if not os.path.exists(wallpapers_dir):
        print(f"❌ 指定期数目录不存在: {wallpapers_dir}")
        return False
    
    print(f"📂 处理期数: {period}")
    print(f"📂 壁纸目录: {wallpapers_dir}")

    # 读取数据库已存在壁纸
    db_wallpapers = get_existing_wallpapers_from_db()  # {filename: id}
    if db_wallpapers is None:
        print("🔴 数据库查询失败，终止数据生成任务。")
        return False
    print(f"📦 数据库已有壁纸: {len(db_wallpapers)} 个")

    # 数据已迁移到数据库，不再需要处理list.json

    # 获取所有图片文件
    image_files = [
        f for f in os.listdir(wallpapers_dir)
        if os.path.isfile(os.path.join(wallpapers_dir, f))
        and f.lower().endswith(SUPPORTED_FORMATS)
    ]
    print(f"🖼️ 当前壁纸目录图片: {len(image_files)} 个")

    # 找出新图片
    new_files = [f for f in image_files if f not in db_wallpapers]
    print(f"✨ 新增图片: {len(new_files)} 个")

    sql_values = []

    if new_files:
        print("✅ 处理新增图片...")

        # 获取今日已有ID的最大序号
        today_str = datetime.now().strftime('%Y%m%d')
        seq = 1
        try:
            conn = pymysql.connect(**DB_CONFIG)
            cursor = conn.cursor()
            cursor.execute("SELECT id FROM wallpapers WHERE id LIKE %s ORDER BY id DESC LIMIT 1", (f"{today_str}%",))
            result = cursor.fetchone()
            if result:
                last_id = str(result[0])
                if len(last_id) > 8:
                    seq = int(last_id[8:]) + 1
            conn.close()
        except Exception as e:
            print(f"Warning: 获取今日ID序号失败: {e}")

        for filename in new_files:
            file_path = os.path.join(wallpapers_dir, filename)
            name_without_ext = os.path.splitext(filename)[0]
            width, height = get_image_dimensions(file_path)
            size_bytes = os.path.getsize(file_path)
            if size_bytes < 1024 * 1024:
                size_str = f"{size_bytes / 1024:.1f} KB"
            else:
                size_str = f"{size_bytes / (1024 * 1024):.2f} MB"
            try:
                with Image.open(file_path) as img:
                    img_format = img.format
            except Exception as e:
                img_format = ''
            
            analyzed_info = analyze_filename(filename)
            category = analyzed_info['category']
            tags_list = analyzed_info['tags']
            
            new_id = generate_short_id(today_str, seq)
            seq += 1
            # 不再生成file_info，直接处理SQL
            tags_string = ','.join(tags_list)
            sql_value = (
                f"  ({new_id}, 1, '{escape_sql_string(name_without_ext)}', '', '{escape_sql_string(f'static/wallpapers/{period}/{filename}')}', '{escape_sql_string(size_str)}', "
                f"{width}, {height}, '{escape_sql_string(category)}', '', '{escape_sql_string(img_format)}', "
                f"0, 0, '{datetime.now().strftime('%Y-%m-%d %H:%M:%S')}', '{datetime.now().strftime('%Y-%m-%d %H:%M:%S')}')"
            )
            sql_values.append(sql_value)
            print(f"✅ 新增: {filename} -> ID: {new_id}")

        # 不再生成list.json文件

        if sql_values:
            # 生成SQL文件
            sql_content = [
                f"-- 新增壁纸数据导入SQL文件 (期数: {period})\n",
                f"-- 生成时间: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n",
                f"-- 数据库: {DB_CONFIG['database']}\n",
                f"-- 新增 {len(sql_values)} 条记录\n\n",
                f"USE `{DB_CONFIG['database']}`;\n\n",
                "INSERT INTO `wallpapers` (`id`, `user_id`, `title`, `description`, `file_path`, `file_size`, `width`, `height`, `category`, `tags`, `format`, `views`, `likes`, `created_at`, `updated_at`) VALUES\n"
            ]
            for i, sql_value in enumerate(sql_values):
                sql_content.append(sql_value)
                if i < len(sql_values) - 1:
                    sql_content.append(",\n")
                else:
                    sql_content.append(";\n")
            with open(sql_path, 'w', encoding='utf-8') as f:
                f.writelines(sql_content)
            print(f"\n🎉 新增图片SQL已生成: {os.path.abspath(sql_path)}")
            
            # 如果启用自动上传，直接插入数据库
            if auto_upload:
                try:
                    conn = pymysql.connect(**DB_CONFIG)
                    cursor = conn.cursor()
                    
                    insert_sql = "INSERT INTO `wallpapers` (`id`, `user_id`, `title`, `description`, `file_path`, `file_size`, `width`, `height`, `category`, `tags`, `format`, `views`, `likes`, `created_at`, `updated_at`) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)"
                    
                    # 2025-01-15 彻底修复ID冲突：重置seq到初始值，确保与SQL文件完全一致
                    # 重新获取初始seq值，因为前面的循环已经修改了seq
                    upload_seq = 1
                    try:
                        temp_conn = pymysql.connect(**DB_CONFIG)
                        temp_cursor = temp_conn.cursor()
                        temp_cursor.execute("SELECT id FROM wallpapers WHERE id LIKE %s ORDER BY id DESC LIMIT 1", (f"{today_str}%",))
                        temp_result = temp_cursor.fetchone()
                        if temp_result:
                            temp_last_id = str(temp_result[0])
                            if len(temp_last_id) > 8:
                                upload_seq = int(temp_last_id[8:]) + 1
                        temp_conn.close()
                    except Exception as e:
                        print(f"Warning: 重新获取ID序号失败: {e}")
                    
                    upload_count = 0
                    for filename in new_files:
                        file_path = os.path.join(wallpapers_dir, filename)
                        name_without_ext = os.path.splitext(filename)[0]
                        
                        width, height = get_image_dimensions(file_path)
                        size_bytes = os.path.getsize(file_path)
                        if size_bytes < 1024 * 1024:
                            size_str = f"{size_bytes / 1024:.1f} KB"
                        else:
                            size_str = f"{size_bytes / (1024 * 1024):.2f} MB"
                        
                        try:
                            with Image.open(file_path) as img:
                                img_format = img.format
                        except Exception as e:
                            img_format = ''
                        
                        analyzed_info = analyze_filename(filename)
                        category = analyzed_info['category']
                        tags_list = analyzed_info['tags']
                        
                        # 使用与SQL生成时完全相同的ID计算方式
                        new_id = generate_short_id(today_str, upload_seq + upload_count)
                        tags_string = ','.join(tags_list)
                        
                        cursor.execute(insert_sql, (
                            new_id, 1, name_without_ext, '', f'static/wallpapers/{period}/{filename}',
                            size_str, width, height, category, '', img_format,
                            0, 0, datetime.now().strftime('%Y-%m-%d %H:%M:%S'), datetime.now().strftime('%Y-%m-%d %H:%M:%S')
                        ))
                        upload_count += 1
                    
                    conn.commit()
                    conn.close()
                    print(f"✅ 成功上传 {upload_count} 条记录到数据库")
                    print(f"📋 使用的ID范围: {generate_short_id(today_str, upload_seq)} - {generate_short_id(today_str, upload_seq + upload_count - 1)}")
                    
                except Exception as e:
                    print(f"❌ 数据库上传失败: {e}")
                    if 'conn' in locals():
                        conn.rollback()
                        conn.close()
                    return False
        else:
            print("无新增图片，无需生成SQL文件")
    else:
        print("ℹ️ 无新增图片。")

    # 获取数据库中的总数
    try:
        conn = pymysql.connect(**DB_CONFIG)
        cursor = conn.cursor()
        cursor.execute("SELECT COUNT(*) FROM wallpapers")
        total_count = cursor.fetchone()[0]
        conn.close()
        print(f"\n📊 数据库中壁纸总数: {total_count}")
    except Exception as e:
        print(f"Warning: 获取总数失败: {e}")
    
    return True

if __name__ == '__main__':
    import sys
    
    # 解析命令行参数
    period = None
    auto_upload = False
    
    if len(sys.argv) > 1:
        for arg in sys.argv[1:]:
            if arg.startswith('--period='):
                period = arg.split('=')[1]
            elif arg == '--upload':
                auto_upload = True
            elif arg == '--help':
                print("使用方法:")
                print("  python update_list.py                    # 自动检测最新期数，仅生成SQL")
                print("  python update_list.py --period=002       # 指定期数002")
                print("  python update_list.py --upload           # 自动上传到数据库")
                print("  python update_list.py --period=003 --upload  # 指定期数并上传")
                sys.exit(0)
    
    print("🚀 开始更新壁纸列表...")
    if period:
        print(f"📌 指定期数: {period}")
    if auto_upload:
        print("📤 启用自动上传到数据库")
    
    success = update_wallpaper_list(period=period, auto_upload=auto_upload)
    if success:
        print("\n✨ 所有操作完成！")
        if not auto_upload:
            print("\n💡 SQL文件已生成但未上传到数据库，您可以:")
            print("  1. 使用 --upload 参数重新运行以自动上传:")
            if period:
                print(f"     python update_list.py --period={period} --upload")
            else:
                print("     python update_list.py --upload")
            print("  2. 手动导入SQL文件到数据库:")
            if period:
                print(f"     mysql -u {DB_CONFIG['user']} {DB_CONFIG['database']} < wallpapers_import_{period}.sql")
            else:
                latest_period = find_latest_period()
                print(f"     mysql -u {DB_CONFIG['user']} {DB_CONFIG['database']} < wallpapers_import_{latest_period}.sql")
            print("\n  输入 python update_list.py --help 查看更多选项")
    else:
        print("\n💥 操作失败，请检查错误信息")
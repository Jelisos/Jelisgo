#!/usr/bin/env python
# -*- coding: utf-8 -*-

'''
文件: compress_wallpapers.py
描述: 壁纸图片批量压缩工具
依赖: Pillow库 (pip install Pillow)
维护: 用于批量压缩wallpapers目录下的图片并保存到preview子目录
'''

import os
import sys
import time
from PIL import Image
import argparse
import re

# 压缩配置 - 参考自image-compressor.js
CONFIG = {
    # 缩略图配置
    'thumbnail': {
        'max_width': 600,
        'max_height': 450,
        'quality': 92,  # PIL中的质量范围是1-95
        'format': 'JPEG'
    },
    # 预览图配置
    'preview': {
        'max_width': 1200,
        'max_height': 900,
        'quality': 95,
        'format': 'JPEG'
    },
    # 原图配置
    'original': {
        'max_width': 1920,
        'max_height': 1080,
        'quality': 95,
        'format': 'JPEG'
    }
}

# 基础目录配置
BASE_WALLPAPERS_DIR = r'f:\XAMPP\htdocs\static\wallpapers'
BASE_PREVIEW_DIR = r'f:\XAMPP\htdocs\static\preview'

# 兼容性配置（保持向后兼容）
SOURCE_DIR = r'f:\XAMPP\htdocs\static\wallpapers'
TARGET_DIR = r'f:\XAMPP\htdocs\static\wallpapers\preview'

# 支持的图片格式
SUPPORTED_FORMATS = ('.jpg', '.jpeg', '.png', '.webp', '.bmp')

# 统计信息
stats = {
    'total': 0,
    'success': 0,
    'skipped': 0,
    'error': 0,
    'total_size_before': 0,
    'total_size_after': 0
}

def has_chinese(text):
    """检查文本是否包含中文字符"""
    return bool(re.search(r'[\u4e00-\u9fff]', text))

def get_compressed_path(original_path, compress_type, period=None):
    """构建压缩图片路径
    
    Args:
        original_path: 原图路径
        compress_type: 压缩类型 (thumbnail|preview|original)
        period: 期数，如'001'、'002'等
        
    Returns:
        压缩图片路径
    """
    # 获取文件名和目录
    directory, filename = os.path.split(original_path)
    name, ext = os.path.splitext(filename)
    
    # 检查是否包含中文
    if has_chinese(name):
        print(f"[警告] 检测到中文文件名: {name}，将进行处理")
    
    # 构建压缩文件名
    config = CONFIG[compress_type]
    # 根据文档要求，文件名与原图同名，且格式为JPEG，所以不再添加后缀
    extension = '.' + config['format'].lower() # 获取配置中的格式作为扩展名
    
    compressed_filename = f"{name}{extension}"
    
    # 如果指定了期数，使用新的目录结构
    if period:
        target_dir = os.path.join(BASE_PREVIEW_DIR, period)
    else:
        # 向后兼容，使用原来的目录
        target_dir = TARGET_DIR
    
    return os.path.join(target_dir, compressed_filename)

def calculate_compressed_size(original_width, original_height, max_width, max_height):
    """计算压缩后的尺寸
    
    Args:
        original_width: 原始宽度
        original_height: 原始高度
        max_width: 最大宽度
        max_height: 最大高度
        
    Returns:
        (width, height): 压缩后的宽度和高度
    """
    width, height = original_width, original_height
    
    # 如果原图尺寸小于最大尺寸，不需要压缩
    if width <= max_width and height <= max_height:
        return width, height
    
    # 计算缩放比例
    width_ratio = max_width / width
    height_ratio = max_height / height
    ratio = min(width_ratio, height_ratio)
    
    width = round(width * ratio)
    height = round(height * ratio)
    
    return width, height

def compress_image(image_path, compress_type='thumbnail', force=False, period=None):
    """压缩图片
    
    Args:
        image_path: 图片路径
        compress_type: 压缩类型 (thumbnail|preview|original)
        force: 是否强制重新压缩已存在的图片
        period: 期数，如'001'、'002'等
        
    Returns:
        bool: 是否成功
    """
    try:
        # 获取压缩配置
        config = CONFIG.get(compress_type, CONFIG['thumbnail'])
        
        # 构建压缩图片路径
        compressed_path = get_compressed_path(image_path, compress_type, period)
        
        # 如果压缩图片已存在且不强制重新压缩，则跳过
        if os.path.exists(compressed_path) and not force:
            print(f"[跳过] {compressed_path} 已存在")
            stats['skipped'] += 1
            return True
        
        # 打开原图
        with Image.open(image_path) as img:
            # 记录原始文件大小
            original_size = os.path.getsize(image_path)
            stats['total_size_before'] += original_size
            
            # 计算压缩后的尺寸
            width, height = calculate_compressed_size(
                img.width, img.height,
                config['max_width'], config['max_height']
            )
            
            # 调整图片大小
            if img.width != width or img.height != height:
                img = img.resize((width, height), Image.LANCZOS)
            
            # 确保目标目录存在
            os.makedirs(os.path.dirname(compressed_path), exist_ok=True)
            
            # 保存压缩图片
            if config['format'] == 'WEBP':
                img.save(compressed_path, 'WEBP', quality=config['quality'], method=6)
            else:  # JPEG
                # 如果原图是RGBA模式（有透明通道），转换为RGB
                if img.mode == 'RGBA':
                    img = img.convert('RGB')
                img.save(compressed_path, 'JPEG', quality=config['quality'], optimize=True)
            
            # 记录压缩后文件大小
            compressed_size = os.path.getsize(compressed_path)
            stats['total_size_after'] += compressed_size
            
            # 计算压缩比例
            ratio = (1 - compressed_size / original_size) * 100 if original_size > 0 else 0
            
            print(f"[成功] {image_path} -> {compressed_path}")
            print(f"       尺寸: {img.width}x{img.height} -> {width}x{height}")
            print(f"       大小: {original_size/1024:.1f}KB -> {compressed_size/1024:.1f}KB (节省 {ratio:.1f}%)")
            
            stats['success'] += 1
            return True
            
    except Exception as e:
        print(f"[错误] 压缩 {image_path} 失败: {str(e)}")
        stats['error'] += 1
        return False

def find_latest_period():
    """倒序扫描期数目录，找到最新的非空期数
    
    Returns:
        str: 最新期数，如'003'，如果没有找到返回None
    """
    if not os.path.exists(BASE_WALLPAPERS_DIR):
        print(f"[错误] 壁纸基础目录不存在: {BASE_WALLPAPERS_DIR}")
        return None
    
    # 扫描所有期数目录
    period_dirs = []
    for item in os.listdir(BASE_WALLPAPERS_DIR):
        item_path = os.path.join(BASE_WALLPAPERS_DIR, item)
        if os.path.isdir(item_path) and item.isdigit() and len(item) == 3:
            period_dirs.append(item)
    
    if not period_dirs:
        print("[信息] 未找到期数目录")
        return None
    
    # 倒序排列期数
    period_dirs.sort(reverse=True)
    
    # 从最新期数开始检查，找到第一个非空目录
    for period in period_dirs:
        period_path = os.path.join(BASE_WALLPAPERS_DIR, period)
        image_files = [
            f for f in os.listdir(period_path)
            if os.path.isfile(os.path.join(period_path, f))
            and f.lower().endswith(SUPPORTED_FORMATS)
        ]
        if image_files:
            print(f"[发现] 最新期数: {period} (包含 {len(image_files)} 个图片文件)")
            return period
    
    print("[信息] 所有期数目录都为空")
    return None

def get_existing_preview_files(period):
    """获取指定期数预览目录中已存在的文件列表
    
    Args:
        period: 期数，如'001'、'002'等
        
    Returns:
        set: 已存在的文件名集合（不含扩展名）
    """
    preview_dir = os.path.join(BASE_PREVIEW_DIR, period)
    if not os.path.exists(preview_dir):
        return set()
    
    existing_files = set()
    for file in os.listdir(preview_dir):
        if file.lower().endswith(('.jpg', '.jpeg')):
            name_without_ext = os.path.splitext(file)[0]
            existing_files.add(name_without_ext)
    
    return existing_files

def process_directory(directory=SOURCE_DIR, compress_types=None, force=False, period=None):
    """处理目录中的所有图片
    
    Args:
        directory: 要处理的目录
        compress_types: 要生成的压缩类型列表 ['thumbnail', 'preview', 'original']
        force: 是否强制重新压缩已存在的图片
        period: 期数，如'001'、'002'等
    """
    if compress_types is None:
        compress_types = ['thumbnail', 'preview']
    
    # 确定目标目录
    if period:
        target_dir = os.path.join(BASE_PREVIEW_DIR, period)
        # 获取已存在的预览文件，用于去重
        existing_files = get_existing_preview_files(period) if not force else set()
        print(f"[信息] 期数 {period} 预览目录中已有 {len(existing_files)} 个文件")
    else:
        target_dir = TARGET_DIR
        existing_files = set()
    
    # 确保目标目录存在
    os.makedirs(target_dir, exist_ok=True)
    
    # 遍历目录中的所有文件
    for root, _, files in os.walk(directory):
        # 跳过目标目录，避免重复处理或死循环
        if (os.path.normpath(root) == os.path.normpath(TARGET_DIR) or 
            os.path.normpath(root).startswith(os.path.normpath(BASE_PREVIEW_DIR))):
            continue
            
        for file in files:
            # 检查文件扩展名
            if not file.lower().endswith(SUPPORTED_FORMATS):
                continue
            
            # 去重检查：如果文件已存在于预览目录且不强制重新压缩，则跳过
            name_without_ext = os.path.splitext(file)[0]
            if period and name_without_ext in existing_files and not force:
                print(f"[跳过] {file} 预览图已存在")
                stats['skipped'] += 1
                continue
                
            # 构建完整路径
            file_path = os.path.join(root, file)
            
            # 统计总数
            stats['total'] += 1
            
            # 对每种压缩类型进行处理
            for compress_type in compress_types:
                compress_image(file_path, compress_type, force, period)

def print_stats():
    """打印统计信息"""
    print("\n" + "=" * 50)
    print("压缩统计信息:")
    print(f"总文件数: {stats['total']}")
    print(f"成功: {stats['success']}")
    print(f"跳过: {stats['skipped']}")
    print(f"错误: {stats['error']}")
    
    if stats['total_size_before'] > 0:
        ratio = (1 - stats['total_size_after'] / stats['total_size_before']) * 100
        print(f"总大小: {stats['total_size_before']/1024/1024:.2f}MB -> {stats['total_size_after']/1024/1024:.2f}MB")
        print(f"节省空间: {(stats['total_size_before'] - stats['total_size_after'])/1024/1024:.2f}MB ({ratio:.1f}%)")
    
    print("=" * 50)

def main():
    """主函数"""
    parser = argparse.ArgumentParser(description='壁纸图片批量压缩工具')
    parser.add_argument('-t', '--types', nargs='+', choices=['thumbnail', 'preview', 'original'],
                        default=['preview'], help='要生成的压缩类型')
    parser.add_argument('-f', '--force', action='store_true', help='强制重新压缩已存在的图片')
    parser.add_argument('-d', '--directory', help='要处理的目录（如果不指定，将自动检测最新期数）')
    parser.add_argument('-p', '--period', help='指定期数，如001、002等')
    parser.add_argument('--auto', action='store_true', help='自动检测最新期数并处理')
    
    args = parser.parse_args()
    
    # 确定处理的目录和期数
    period = None
    directory = args.directory
    
    if args.auto or (not args.directory and not args.period):
        # 自动检测最新期数
        period = find_latest_period()
        if period:
            directory = os.path.join(BASE_WALLPAPERS_DIR, period)
            print(f"[自动检测] 使用最新期数: {period}")
        else:
            print("[警告] 未找到期数目录，使用默认目录")
            directory = SOURCE_DIR
    elif args.period:
        # 使用指定期数
        period = args.period
        directory = os.path.join(BASE_WALLPAPERS_DIR, period)
        if not os.path.exists(directory):
            print(f"[错误] 指定期数目录不存在: {directory}")
            return
        print(f"[指定期数] 使用期数: {period}")
    elif args.directory:
        # 使用指定目录（向后兼容）
        directory = args.directory
        print(f"[指定目录] 使用目录: {directory}")
    
    if not os.path.exists(directory):
        print(f"[错误] 目录不存在: {directory}")
        return
    
    print(f"\n开始处理目录: {directory}")
    if period:
        print(f"期数: {period}")
        print(f"预览目录: {os.path.join(BASE_PREVIEW_DIR, period)}")
    print(f"压缩类型: {', '.join(args.types)}")
    print(f"强制重新压缩: {'是' if args.force else '否'}")
    print("\n开始处理...\n")
    
    start_time = time.time()
    process_directory(directory, args.types, args.force, period)
    end_time = time.time()
    
    print_stats()
    print(f"\n处理完成，耗时: {end_time - start_time:.2f}秒")
    
    if period:
        print(f"\n💡 预览图已保存到: {os.path.join(BASE_PREVIEW_DIR, period)}")
        print(f"💡 使用方法示例:")
        print(f"   自动检测最新期数: python compress_wallpapers.py --auto")
        print(f"   指定期数: python compress_wallpapers.py --period={period}")
        print(f"   强制重新压缩: python compress_wallpapers.py --period={period} --force")

if __name__ == '__main__':
    main()
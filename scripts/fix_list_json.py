#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
修复 list.json 文件中的 Git 合并冲突
自动选择 HEAD 版本的内容
"""

import re
import json
import os

def fix_git_conflicts_in_json(file_path):
    """
    修复JSON文件中的Git合并冲突
    """
    print(f"正在修复文件: {file_path}")
    
    # 读取文件内容
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # 检查是否有冲突标记
    if '<<<<<<< HEAD' not in content:
        print("文件中没有发现Git冲突标记")
        return False
    
    print("发现Git冲突标记，开始修复...")
    
    # 匹配并替换冲突标记
    # 模式：<<<<<<< HEAD\n(内容)\n=======\n(其他内容)\n>>>>>>> commit_hash
    pattern = r'<<<<<<< HEAD\n(.*?)\n=======\n.*?\n>>>>>>> [^\n]+'
    
    def replace_conflict(match):
        # 返回HEAD版本的内容
        return match.group(1)
    
    # 替换所有冲突标记
    fixed_content = re.sub(pattern, replace_conflict, content, flags=re.DOTALL)
    
    # 验证修复后的JSON格式
    try:
        json.loads(fixed_content)
        print("JSON格式验证通过")
    except json.JSONDecodeError as e:
        print(f"JSON格式验证失败: {e}")
        return False
    
    # 备份原文件
    backup_path = file_path + '.backup_before_fix'
    with open(backup_path, 'w', encoding='utf-8') as f:
        f.write(content)
    print(f"原文件已备份到: {backup_path}")
    
    # 写入修复后的内容
    with open(file_path, 'w', encoding='utf-8') as f:
        f.write(fixed_content)
    
    print("文件修复完成")
    return True

if __name__ == '__main__':
    # 修复 list.json 文件
    list_json_path = 'f:/XAMPP/htdocs/static/data/list.json'
    
    if os.path.exists(list_json_path):
        success = fix_git_conflicts_in_json(list_json_path)
        if success:
            print("\n修复成功！")
        else:
            print("\n修复失败！")
    else:
        print(f"文件不存在: {list_json_path}")
<?php
/**
 * 移除list.json文件脚本
 * 由于数据已迁移到数据库，不再需要list.json文件
 */

// 定义list.json文件路径
$listJsonPath = __DIR__ . '/../static/data/list.json';
$backupPath = __DIR__ . '/../static/data/list.json.backup.' . date('YmdHis');

// 检查文件是否存在
if (file_exists($listJsonPath)) {
    // 创建备份
    echo "创建备份: {$backupPath}\n";
    if (copy($listJsonPath, $backupPath)) {
        echo "✅ 备份创建成功\n";
        
        // 删除原文件
        if (unlink($listJsonPath)) {
            echo "✅ list.json 文件已成功删除\n";
            echo "数据已完全迁移到数据库，不再需要list.json文件\n";
        } else {
            echo "❌ 删除 list.json 文件失败\n";
        }
    } else {
        echo "❌ 创建备份失败，操作中止\n";
    }
} else {
    echo "⚠️ list.json 文件不存在，无需删除\n";
}

echo "\n操作完成\n";
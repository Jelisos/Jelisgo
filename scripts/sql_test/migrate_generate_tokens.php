<?php
/**
 * 数据迁移脚本：为现有壁纸生成Token
 * 文件: scripts/sql_test/migrate_generate_tokens.php
 * 描述: 为wallpapers表中的现有壁纸生成image_tokens记录
 */

require_once __DIR__ . '/../../config/database.php';

// 使用PDO连接
$pdo = getPDOConnection();

if (!$pdo) {
    die("数据库连接失败\n");
}

echo "开始为现有壁纸生成Token...\n";

try {
    // 开始事务
    $pdo->beginTransaction();
    
    // 获取所有壁纸记录
    $stmt = $pdo->prepare("SELECT id, file_path FROM wallpapers");
    $stmt->execute();
    $wallpapers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "找到 " . count($wallpapers) . " 个活跃壁纸\n";
    
    $successCount = 0;
    $skipCount = 0;
    
    foreach ($wallpapers as $wallpaper) {
        $wallpaperId = $wallpaper['id'];
        $originalPath = $wallpaper['file_path'];
        $previewPath = null; // 暂时不处理预览图
        
        // 检查是否已存在Token记录
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM image_tokens WHERE wallpaper_id = ?");
        $checkStmt->execute([$wallpaperId]);
        $existingCount = $checkStmt->fetchColumn();
        
        if ($existingCount > 0) {
            echo "壁纸ID {$wallpaperId} 已存在Token记录，跳过\n";
            $skipCount++;
            continue;
        }
        
        // 为原图生成Token
        if ($originalPath) {
            $originalToken = hash('sha256', $wallpaperId . '_original_' . $originalPath . '_' . time() . '_' . rand(1000, 9999));
            
            $insertStmt = $pdo->prepare("
                INSERT INTO image_tokens (wallpaper_id, token, image_path, path_type, created_at, updated_at) 
                VALUES (?, ?, ?, 'original', NOW(), NOW())
            ");
            $insertStmt->execute([$wallpaperId, $originalToken, $originalPath]);
        }
        
        // 预览图处理暂时跳过，因为当前表结构中没有preview_path字段
        
        $successCount++;
        echo "为壁纸ID {$wallpaperId} 生成Token成功\n";
    }
    
    // 提交事务
    $pdo->commit();
    
    echo "\n迁移完成！\n";
    echo "成功处理: {$successCount} 个壁纸\n";
    echo "跳过已存在: {$skipCount} 个壁纸\n";
    
    // 显示统计信息
    $totalTokens = $pdo->query("SELECT COUNT(*) FROM image_tokens")->fetchColumn();
    echo "当前Token总数: {$totalTokens}\n";
    
} catch (Exception $e) {
    // 回滚事务
    $pdo->rollBack();
    echo "迁移失败: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n数据迁移脚本执行完成。\n";
?>
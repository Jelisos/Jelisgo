<?php
/**
 * 批量更新壁纸路径脚本
 * 将数据库中的路径从 static/wallpapers/xxx.jpg 更新为 static/wallpapers/001/xxx.jpg
 * 文件位置：/scripts/sql_test/update_wallpaper_paths.php
 */

require_once __DIR__ . '/../../config/database.php';

try {
    $conn = getDbConnection();
    if (!$conn) {
        throw new Exception('数据库连接失败');
    }
    
    echo "开始更新壁纸路径...\n";
    
    // 查询所有需要更新的记录
    $selectSql = "SELECT id, file_path FROM wallpapers WHERE file_path LIKE 'static/wallpapers/%' AND file_path NOT LIKE 'static/wallpapers/001/%'";
    $selectStmt = $conn->prepare($selectSql);
    $selectStmt->execute();
    $result = $selectStmt->get_result();
    
    $updateCount = 0;
    $totalCount = $result->num_rows;
    
    echo "找到 {$totalCount} 条需要更新的记录\n";
    
    if ($totalCount > 0) {
        // 开始事务
        $conn->begin_transaction();
        
        try {
            // 准备更新语句
            $updateSql = "UPDATE wallpapers SET file_path = ? WHERE id = ?";
            $updateStmt = $conn->prepare($updateSql);
            
            while ($row = $result->fetch_assoc()) {
                $oldPath = $row['file_path'];
                // 将 static/wallpapers/xxx.jpg 转换为 static/wallpapers/001/xxx.jpg
                $newPath = str_replace('static/wallpapers/', 'static/wallpapers/001/', $oldPath);
                
                $updateStmt->bind_param('si', $newPath, $row['id']);
                $updateStmt->execute();
                
                $updateCount++;
                echo "更新记录 {$row['id']}: {$oldPath} -> {$newPath}\n";
            }
            
            // 提交事务
            $conn->commit();
            echo "\n✓ 成功更新 {$updateCount} 条记录\n";
            
        } catch (Exception $e) {
            // 回滚事务
            $conn->rollback();
            throw $e;
        }
    } else {
        echo "没有需要更新的记录\n";
    }
    
    // 验证更新结果
    echo "\n验证更新结果：\n";
    $verifySql = "SELECT COUNT(*) as count FROM wallpapers WHERE file_path LIKE 'static/wallpapers/001/%'";
    $verifyStmt = $conn->prepare($verifySql);
    $verifyStmt->execute();
    $verifyResult = $verifyStmt->get_result();
    $verifyCount = $verifyResult->fetch_assoc()['count'];
    
    echo "当前包含001目录的记录数：{$verifyCount}\n";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n路径更新完成！\n";
?>
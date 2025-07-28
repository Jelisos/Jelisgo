<?php
/**
 * 管理后台表创建脚本
 * 位置: setup_admin_tables.php
 */

try {
    // 连接数据库
    $pdo = new PDO('mysql:host=localhost;dbname=wallpaper_db', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "连接数据库成功\n";
    
    // 读取SQL文件
    $sql = file_get_contents('create_admin_tables.sql');
    
    // 分割SQL语句
    $statements = explode(';', $sql);
    
    foreach ($statements as $stmt) {
        $stmt = trim($stmt);
        
        // 跳过空语句、注释和USE语句
        if (empty($stmt) || 
            preg_match('/^(USE|--)/i', $stmt) || 
            preg_match('/^\s*$/', $stmt)) {
            continue;
        }
        
        try {
            $pdo->exec($stmt);
            echo "执行成功: " . substr($stmt, 0, 50) . "...\n";
        } catch (Exception $e) {
            echo "执行错误: " . $e->getMessage() . "\n";
            echo "SQL: " . substr($stmt, 0, 100) . "...\n";
        }
    }
    
    // 检查表是否创建成功
    echo "\n检查创建的表:\n";
    $tables = $pdo->query("SHOW TABLES LIKE '%review%'")->fetchAll();
    foreach ($tables as $table) {
        echo "- " . $table[0] . "\n";
    }
    
    $tables = $pdo->query("SHOW TABLES LIKE '%admin%'")->fetchAll();
    foreach ($tables as $table) {
        echo "- " . $table[0] . "\n";
    }
    
    $tables = $pdo->query("SHOW TABLES LIKE '%user_status%'")->fetchAll();
    foreach ($tables as $table) {
        echo "- " . $table[0] . "\n";
    }
    
    echo "\n数据库表创建完成！\n";
    
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
}
?>
<?php
/**
 * 创建自定义链接管理表脚本
 * 功能：为壁纸详情页面添加自定义链接功能
 * 创建时间：2024年
 * 作者：AI Assistant
 * 版本：v2.0 (包含扩展字段)
 */

require_once '../../config/database.php';

try {
    // 创建wallpaper_custom_links表
    $createTableSQL = "
    CREATE TABLE IF NOT EXISTS wallpaper_custom_links (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
        wallpaper_id BIGINT(20) UNSIGNED NOT NULL COMMENT '壁纸ID',
        title VARCHAR(100) NOT NULL COMMENT '链接标题',
        url VARCHAR(500) NOT NULL COMMENT '链接地址',
        priority TINYINT(4) NOT NULL DEFAULT 1 COMMENT '优先级(1-5，数字越大越重要)',
        color_class VARCHAR(50) NULL COMMENT '颜色样式类名',
        description VARCHAR(255) NULL COMMENT '链接描述',
        is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT '是否启用(0=禁用,1=启用)',
        click_count INT(11) NOT NULL DEFAULT 0 COMMENT '点击统计次数',
        target_type VARCHAR(20) NOT NULL DEFAULT '_blank' COMMENT '链接打开方式(_blank/_self)',
        icon_class VARCHAR(50) NULL COMMENT '图标样式类名',
        sort_order INT(11) NOT NULL DEFAULT 0 COMMENT '排序权重(数字越大越靠前)',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
        PRIMARY KEY (id),
        FOREIGN KEY (wallpaper_id) REFERENCES wallpapers(id) ON DELETE CASCADE,
        INDEX idx_wallpaper_id (wallpaper_id),
        INDEX idx_priority (priority),
                FOREIGN KEY (wallpaper_id) REFERENCES wallpapers(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='壁纸自定义链接表'
        ";
        
        try {
            $this->pdo->exec($sql);
            echo "✓ wallpaper_custom_links 表创建成功\n";
            
            // 验证表结构
            $this->verifyTable();
            
        } catch (PDOException $e) {
            echo "✗ 创建表失败: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * 验证表结构
     */
    private function verifyTable() {
        echo "\n验证表结构...\n";
        
        try {
            // 检查表是否存在
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'wallpaper_custom_links'");
            if ($stmt->rowCount() > 0) {
                echo "✓ 表存在检查通过\n";
                
                // 显示表结构
                $stmt = $this->pdo->query("DESCRIBE wallpaper_custom_links");
                $columns = $stmt->fetchAll();
                
                echo "\n表结构信息:\n";
                echo str_pad('字段名', 20) . str_pad('类型', 20) . str_pad('允许NULL', 10) . str_pad('键', 10) . "默认值\n";
                echo str_repeat('-', 80) . "\n";
                
                foreach ($columns as $column) {
                    echo str_pad($column['Field'], 20) . 
                         str_pad($column['Type'], 20) . 
                         str_pad($column['Null'], 10) . 
                         str_pad($column['Key'], 10) . 
                         $column['Default'] . "\n";
                }
                
                // 检查索引
                $stmt = $this->pdo->query("SHOW INDEX FROM wallpaper_custom_links");
                $indexes = $stmt->fetchAll();
                
                echo "\n索引信息:\n";
                foreach ($indexes as $index) {
                    echo "- {$index['Key_name']}: {$index['Column_name']}\n";
                }
                
            } else {
                echo "✗ 表不存在\n";
            }
            
        } catch (PDOException $e) {
            echo "✗ 验证失败: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * 插入示例数据（可选）
     */
    public function insertSampleData() {
        echo "\n是否插入示例数据？(y/n): ";
        $handle = fopen("php://stdin", "r");
        $input = trim(fgets($handle));
        fclose($handle);
        
        if (strtolower($input) === 'y') {
            try {
                // 获取第一个壁纸ID作为示例
                $stmt = $this->pdo->query("SELECT id FROM wallpapers LIMIT 1");
                $wallpaper = $stmt->fetch();
                
                if ($wallpaper) {
                    $wallpaperId = $wallpaper['id'];
                    
                    $sampleData = [
                        [
                            'wallpaper_id' => $wallpaperId,
                            'title' => '官方下载',
                            'url' => 'https://example.com/download',
                            'priority' => 1,
                            'color_class' => 'priority-1',
                            'description' => '官方高清下载链接'
                        ],
                        [
                            'wallpaper_id' => $wallpaperId,
                            'title' => '相关作品',
                            'url' => 'https://example.com/related',
                            'priority' => 3,
                            'color_class' => 'priority-3',
                            'description' => '作者的其他相关作品'
                        ]
                    ];
                    
                    $sql = "INSERT INTO wallpaper_custom_links (wallpaper_id, title, url, priority, color_class, description) VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt = $this->pdo->prepare($sql);
                    
                    foreach ($sampleData as $data) {
                        $stmt->execute([
                            $data['wallpaper_id'],
                            $data['title'],
                            $data['url'],
                            $data['priority'],
                            $data['color_class'],
                            $data['description']
                        ]);
                    }
                    
                    echo "✓ 示例数据插入成功\n";
                } else {
                    echo "✗ 没有找到壁纸数据，跳过示例数据插入\n";
                }
                
            } catch (PDOException $e) {
                echo "✗ 插入示例数据失败: " . $e->getMessage() . "\n";
            }
        }
    }
}

// 执行创建
if (php_sapi_name() === 'cli') {
    $creator = new CustomLinksTableCreator();
    $creator->createTable();
    $creator->insertSampleData();
    echo "\n操作完成！\n";
} else {
    echo "<h1>自定义链接表创建工具</h1>";
    echo "<p>请通过命令行运行此脚本</p>";
}
?>
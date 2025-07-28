<?php
require_once 'config/database.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PWD, DB_NAME);
$conn->set_charset("utf8mb4");

echo "=== 检查wallpaper_review_status表 ===\n";

// 检查总记录数
$result = $conn->query('SELECT COUNT(*) as cnt FROM wallpaper_review_status');
if ($result) {
    $count = $result->fetch_assoc()['cnt'];
    echo "wallpaper_review_status表记录数: $count\n\n";
}

// 按状态分组统计
$result2 = $conn->query('SELECT status, COUNT(*) as cnt FROM wallpaper_review_status GROUP BY status');
if ($result2) {
    echo "按状态分组统计:\n";
    while($row = $result2->fetch_assoc()) {
        echo "- {$row['status']}: {$row['cnt']} 个\n";
    }
}

// 检查是否所有壁纸都有审核状态
echo "\n=== 检查壁纸审核覆盖情况 ===\n";
$query = "
    SELECT 
        (SELECT COUNT(*) FROM wallpapers) as total_wallpapers,
        (SELECT COUNT(*) FROM wallpaper_review_status) as reviewed_wallpapers
";
$result3 = $conn->query($query);
if ($result3) {
    $data = $result3->fetch_assoc();
    echo "总壁纸数: {$data['total_wallpapers']}\n";
    echo "已设置审核状态的壁纸数: {$data['reviewed_wallpapers']}\n";
    
    if ($data['total_wallpapers'] > $data['reviewed_wallpapers']) {
        $missing = $data['total_wallpapers'] - $data['reviewed_wallpapers'];
        echo "❌ 还有 $missing 个壁纸没有设置审核状态\n";
    } else {
        echo "✅ 所有壁纸都已设置审核状态\n";
    }
}

$conn->close();
?>
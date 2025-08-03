<?php
// 测试首页图片随机显示修复
header('Content-Type: text/html; charset=utf-8');

echo "<h2>首页图片随机显示修复测试</h2>";

// 测试API返回的图片ID分布
echo "<h3>测试1: 检查API返回的图片ID分布</h3>";

$apiUrl = 'http://localhost/api/wallpaper_data.php?action=list&limit=10';

// 进行3次API调用，检查返回的图片ID是否不同
for ($i = 1; $i <= 3; $i++) {
    echo "<p><strong>第{$i}次调用:</strong></p>";
    
    $response = file_get_contents($apiUrl);
    $data = json_decode($response, true);
    
    if ($data && isset($data['data'])) {
        $ids = array_map(function($item) { return $item['id']; }, $data['data']);
        echo "<p>返回的图片ID: " . implode(', ', $ids) . "</p>";
        echo "<p>总数: " . count($ids) . "</p>";
        
        if ($i == 1) {
            $firstCallIds = $ids;
        } elseif ($i == 2) {
            $secondCallIds = $ids;
            $diff1 = array_diff($firstCallIds, $secondCallIds);
            echo "<p style='color: blue;'>与第1次的差异数量: " . count($diff1) . "</p>";
        } elseif ($i == 3) {
            $thirdCallIds = $ids;
            $diff2 = array_diff($secondCallIds, $thirdCallIds);
            echo "<p style='color: blue;'>与第2次的差异数量: " . count($diff2) . "</p>";
        }
    } else {
        echo "<p style='color: red;'>API调用失败</p>";
        echo "<p>响应内容: " . htmlspecialchars($response) . "</p>";
    }
    
    echo "<hr>";
    sleep(1); // 等待1秒
}

// 检查数据库总图片数
echo "<h3>测试2: 数据库图片总数</h3>";
try {
    require_once 'config/database.php';
    $conn = new mysqli($host, $username, $password, $database);
    
    if ($conn->connect_error) {
        throw new Exception("数据库连接失败: " . $conn->connect_error);
    }
    
    $result = $conn->query("SELECT COUNT(*) as total FROM wallpapers");
    $row = $result->fetch_assoc();
    echo "<p>数据库中总图片数: <strong>" . $row['total'] . "</strong></p>";
    
    // 检查最新和最旧的图片ID
    $newest = $conn->query("SELECT id, created_at FROM wallpapers ORDER BY created_at DESC LIMIT 1")->fetch_assoc();
    $oldest = $conn->query("SELECT id, created_at FROM wallpapers ORDER BY created_at ASC LIMIT 1")->fetch_assoc();
    
    echo "<p>最新图片ID: {$newest['id']} (创建时间: {$newest['created_at']})</p>";
    echo "<p>最旧图片ID: {$oldest['id']} (创建时间: {$oldest['created_at']})</p>";
    
    $conn->close();
} catch (Exception $e) {
    echo "<p style='color: red;'>数据库查询错误: " . $e->getMessage() . "</p>";
}

echo "<h3>修复总结</h3>";
echo "<p>✅ 已将API查询从 <code>ORDER BY w.created_at DESC</code> 改为 <code>ORDER BY RAND()</code></p>";
echo "<p>✅ 已将前端API调用的limit从20改为2000，确保加载足够多的数据</p>";
echo "<p>✅ 现在API会返回随机排序的图片，前端也有足够的数据池进行随机显示</p>";
echo "<p>✅ 首页将从全部图片中随机显示，而不是只在最新的55张中随机</p>";

echo "<h3>验证方法</h3>";
echo "<p>1. 多次刷新首页，观察图片是否有明显变化</p>";
echo "<p>2. 检查上方API调用结果，每次调用返回的图片ID应该不同</p>";
echo "<p>3. 如果差异数量接近10（总数），说明随机效果良好</p>";
?>
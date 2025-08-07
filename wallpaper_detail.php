<?php
/**
 * 文件: wallpaper_detail.php
 * 描述: 独立的壁纸详情页面，提供完整的SEO优化
 * 功能: 为每张图片提供独立的详情页面和固定链接
 * 创建时间: 2025-01-30
 * 维护: AI助手
 */

require_once 'config/database.php';

// 获取壁纸ID
$wallpaper_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($wallpaper_id <= 0) {
    http_response_code(404);
    include '404.php';
    exit;
}

// 连接数据库
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PWD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    die('数据库连接失败');
}

// 查询壁纸信息
$stmt = $pdo->prepare("
    SELECT w.*, wp.content as prompt_content, wp.is_locked as prompt_locked
    FROM wallpapers w 
    LEFT JOIN wallpaper_prompts wp ON w.id = wp.wallpaper_id 
    WHERE w.id = ?
");
$stmt->execute([$wallpaper_id]);
$wallpaper = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$wallpaper) {
    http_response_code(404);
    include '404.php';
    exit;
}

// 查询自定义链接
$linkStmt = $pdo->prepare("
    SELECT * FROM wallpaper_custom_links 
    WHERE wallpaper_id = ? 
    ORDER BY priority DESC, created_at DESC
");
$linkStmt->execute([$wallpaper_id]);
$customLinks = $linkStmt->fetchAll(PDO::FETCH_ASSOC);

// 读取系统设置
$configFile = 'config/system_settings.json';
$defaultSettings = [
    'basic' => [
        'site_name' => '壁纸喵 ° 不吃鱼',
        'site_subtitle' => '你的专属壁纸库',
        'site_description' => '高质量壁纸分享平台'
    ]
];

if (file_exists($configFile)) {
    $content = file_get_contents($configFile);
    $settings = json_decode($content, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $siteName = $settings['basic']['site_name'] ?? $defaultSettings['basic']['site_name'];
    } else {
        $siteName = $defaultSettings['basic']['site_name'];
    }
} else {
    $siteName = $defaultSettings['basic']['site_name'];
}

// SEO信息
$pageTitle = ($wallpaper['title'] ? htmlspecialchars($wallpaper['title'], ENT_QUOTES, 'UTF-8') : '壁纸详情') . ' - ' . $siteName;
$pageKeywords = $wallpaper['tags'] ? htmlspecialchars($wallpaper['tags'], ENT_QUOTES, 'UTF-8') : '';
$pageDescription = $wallpaper['prompt_content'] ? htmlspecialchars(mb_substr($wallpaper['prompt_content'], 0, 160), ENT_QUOTES, 'UTF-8') : '';
$imageUrl = 'https://jelisgo.cn/' . ltrim($wallpaper['file_path'], '/');
$currentUrl = 'https://jelisgo.cn/wallpaper_detail.php?id=' . $wallpaper_id;
$imageAlt = $wallpaper['title'] ? htmlspecialchars($wallpaper['title'], ENT_QUOTES, 'UTF-8') : '高清壁纸';

// 更新浏览量（简单实现）
$updateStmt = $pdo->prepare("UPDATE wallpapers SET views = views + 1 WHERE id = ?");
$updateStmt->execute([$wallpaper_id]);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- SEO Meta Tags -->
    <?php if ($pageKeywords): ?>
    <meta name="keywords" content="<?php echo $pageKeywords; ?>">
    <?php endif; ?>
    <?php if ($pageDescription): ?>
    <meta name="description" content="<?php echo $pageDescription; ?>">
    <?php endif; ?>
    
    <!-- Open Graph Tags -->
    <meta property="og:title" content="<?php echo $pageTitle; ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo $currentUrl; ?>">
    <meta property="og:image" content="<?php echo $imageUrl; ?>">
    <?php if ($pageDescription): ?>
    <meta property="og:description" content="<?php echo $pageDescription; ?>">
    <?php endif; ?>
    <meta property="og:site_name" content="<?php echo $siteName; ?>">
    
    <!-- Twitter Card Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo $pageTitle; ?>">
    <?php if ($pageDescription): ?>
    <meta name="twitter:description" content="<?php echo $pageDescription; ?>">
    <?php endif; ?>
    <meta name="twitter:image" content="<?php echo $imageUrl; ?>">
    
    <!-- Canonical URL -->
    <link rel="canonical" href="<?php echo $currentUrl; ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    
    <!-- CSS -->
    <link rel="stylesheet" href="/static/css/main.css">
    <link rel="stylesheet" href="/static/css/wallpaper-detail.css">
    
    <!-- Structured Data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "ImageObject",
        "name": "<?php echo addslashes($wallpaper['title'] ?: '高清壁纸'); ?>",
        "url": "<?php echo $currentUrl; ?>",
        "image": "<?php echo $imageUrl; ?>",
        "width": <?php echo $wallpaper['width'] ?: 1920; ?>,
        "height": <?php echo $wallpaper['height'] ?: 1080; ?>,
        "encodingFormat": "<?php echo strtoupper($wallpaper['format'] ?: 'JPEG'); ?>",
        "contentSize": "<?php echo $wallpaper['file_size'] ?: ''; ?>",
        <?php if ($pageDescription): ?>
        "description": "<?php echo addslashes($pageDescription); ?>",
        <?php endif; ?>
        "dateCreated": "<?php echo date('c', strtotime($wallpaper['created_at'])); ?>",
        "creator": {
            "@type": "Organization",
            "name": "<?php echo $siteName; ?>"
        }
    }
    </script>
</head>
<body>
    <div class="wallpaper-detail-container">
        <!-- 导航栏 -->
        <nav class="detail-nav">
            <a href="/" class="back-home">← 返回首页</a>
            <h1 class="site-title"><?php echo $siteName; ?></h1>
        </nav>
        
        <!-- 壁纸详情内容 -->
        <main class="detail-content">
            <div class="image-section">
                <img src="<?php echo htmlspecialchars($wallpaper['file_path'], ENT_QUOTES, 'UTF-8'); ?>" 
                     alt="<?php echo $imageAlt; ?>" 
                     class="wallpaper-image"
                     loading="lazy">
            </div>
            
            <div class="info-section">
                <h1 class="wallpaper-title"><?php echo htmlspecialchars($wallpaper['title'] ?: '高清壁纸', ENT_QUOTES, 'UTF-8'); ?></h1>
                
                <!-- 元数据 -->
                <div class="grid grid-cols-2 gap-4 text-sm text-gray-600 mb-4">
                    <div class="col-span-1">
                        <span>原图大小:</span> <span><?php echo htmlspecialchars($wallpaper['file_size'] ?: '未知', ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="col-span-1">
                        <span>格式:</span> <span><?php echo htmlspecialchars($wallpaper['format'] ?: 'JPEG', ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                </div>
                
                <!-- 格式和上传时间 -->
                <div class="grid grid-cols-2 gap-4 text-sm text-gray-600 mb-4">
                    <div class="col-span-1">
                        <span>原图分辨率:</span> <span><?php echo $wallpaper['width']; ?> × <?php echo $wallpaper['height']; ?></span>
                    </div>
                    <div class="col-span-1">
                        <span>上传时间:</span> <span><?php echo date('Y-m-d', strtotime($wallpaper['created_at'])); ?></span>
                    </div>
                </div>
                
                <!-- 分类 -->

                
                <!-- 浏览次数 -->
                <div class="mb-4 text-sm text-gray-600">
                    <span>浏览次数: <?php echo number_format($wallpaper['views']); ?> 次</span>
                </div>
                

                
                <!-- AI生图提示词 -->
                <div class="prompt-section">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center">
                            <h3 class="text-lg font-semibold text-gray-800">AI生图提示词</h3>
                            <?php if ($wallpaper['prompt_content']): ?>
                            <button id="copy-prompt-btn" class="ml-2 p-1 rounded-full text-gray-500 hover:bg-gray-200 hover:text-primary transition-colors focus:outline-none" title="复制提示词">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" />
                                    <path d="M6 3a2 2 0 00-2 2v11a2 2 0 002 2h8a2 2 0 002-2V5a2 2 0 00-2-2H6zM5 9a1 1 0 011-1h8a1 1 0 110 2H6a1 1 0 01-1-1z" />
                                </svg>
                            </button>
                            <span id="copy-success-message" class="text-sm opacity-0 transition-opacity duration-300 ml-2"></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($wallpaper['prompt_locked']): ?>
                        <div class="lock-indicator">
                            <img src="static/icons/fa-lock.svg" alt="锁定" class="w-4 h-4 inline">
                            <span class="text-sm">已锁定</span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="prompt-content">
                        <?php if ($wallpaper['prompt_content']): ?>
                            <?php echo nl2br(htmlspecialchars($wallpaper['prompt_content'], ENT_QUOTES, 'UTF-8')); ?>
                        <?php else: ?>
                            <span class="text-gray-500">暂无提示词信息</span>
                        <?php endif; ?>
                    </div>
                </div>
</body>
</html>
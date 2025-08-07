<?php
// 读取系统设置
$configFile = 'config/system_settings.json';
$defaultSettings = [
    'basic' => [
        'site_name' => '壁纸喵 ° 不吃鱼',
        'site_subtitle' => '你的专属壁纸库',
        'site_description' => '高质量壁纸分享平台'
    ],
    'seo' => [
        'keywords' => '壁纸,高清壁纸,桌面壁纸,手机壁纸,免费壁纸下载',
        'description' => '壁纸喵提供海量高清壁纸免费下载，包含风景、动漫、游戏、明星等各类精美壁纸，支持手机和桌面壁纸，让你的设备更加个性化。',
        'og_image' => '/static/images/og-default.jpeg'
    ]
];

if (file_exists($configFile)) {
    $content = file_get_contents($configFile);
    $settings = json_decode($content, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        // 基础设置
        $siteName = $settings['basic']['site_name'] ?? $defaultSettings['basic']['site_name'];
        $siteSubtitle = $settings['basic']['site_subtitle'] ?? $defaultSettings['basic']['site_subtitle'];
        $siteDescription = $settings['basic']['site_description'] ?? $defaultSettings['basic']['site_description'];
        
        // SEO设置
        $seoKeywords = $settings['seo']['keywords'] ?? $defaultSettings['seo']['keywords'];
        $seoDescription = $settings['seo']['description'] ?? $defaultSettings['seo']['description'];
        $ogImage = $settings['seo']['og_image'] ?? $defaultSettings['seo']['og_image'];
    } else {
        $siteName = $defaultSettings['basic']['site_name'];
        $siteSubtitle = $defaultSettings['basic']['site_subtitle'];
        $siteDescription = $defaultSettings['basic']['site_description'];
        $seoKeywords = $defaultSettings['seo']['keywords'];
        $seoDescription = $defaultSettings['seo']['description'];
        $ogImage = $defaultSettings['seo']['og_image'];
    }
} else {
    $siteName = $defaultSettings['basic']['site_name'];
    $siteSubtitle = $defaultSettings['basic']['site_subtitle'];
    $siteDescription = $defaultSettings['basic']['site_description'];
    $seoKeywords = $defaultSettings['seo']['keywords'];
    $seoDescription = $defaultSettings['seo']['description'];
    $ogImage = $defaultSettings['seo']['og_image'];
}

$pageTitle = htmlspecialchars($siteName . ' - ' . $siteSubtitle, ENT_QUOTES, 'UTF-8');
$currentUrl = 'https://Jelisgo.cn' . $_SERVER['REQUEST_URI'];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- 基础SEO标签 -->
    <meta name="description" content="<?php echo htmlspecialchars($seoDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($seoKeywords, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="author" content="<?php echo htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?php echo htmlspecialchars($currentUrl, ENT_QUOTES, 'UTF-8'); ?>">
    
    <!-- Open Graph标签 -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo $pageTitle; ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($seoDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($currentUrl, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:site_name" content="<?php echo htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:image" content="https://Jelisgo.cn<?php echo htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:locale" content="zh_CN">
    
    <!-- Twitter Card标签 -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo $pageTitle; ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($seoDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:image" content="https://Jelisgo.cn<?php echo htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8'); ?>">
    
    <!-- 结构化数据 -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "<?php echo addslashes($siteName); ?>",
        "description": "<?php echo addslashes($seoDescription); ?>",
        "url": "<?php echo addslashes($currentUrl); ?>",
        "potentialAction": {
            "@type": "SearchAction",
            "target": {
                "@type": "EntryPoint",
                "urlTemplate": "<?php echo addslashes($currentUrl); ?>?search={search_term_string}"
            },
            "query-input": "required name=search_term_string"
        },
        "publisher": {
            "@type": "Organization",
            "name": "<?php echo addslashes($siteName); ?>",
            "logo": {
                "@type": "ImageObject",
                "url": "https://Jelisgo.cn<?php echo addslashes($ogImage); ?>"
            }
        }
    }
    </script>
    <!-- 引入Tailwind CSS -->
    <link rel="stylesheet" href="static/css/tailwind.min.css">
    <!-- 引入Font Awesome -->
    <link rel="stylesheet" href="static/css/font-awesome.min.css">
    <!-- 引入自定义CSS -->
    <link href="static/css/main.css" rel="stylesheet">
    <!-- 引入SVG图标样式表 -->
    <link rel="stylesheet" href="static/css/svg-icons.css">
    <!-- 引入自定义链接管理样式 -->
    <link rel="stylesheet" href="static/css/custom-links.css">
    <!-- 引入Inter字体 -->
    <link rel="stylesheet" href="static/fonts/inter.css">
    <!-- 2024-07-25 修复: 移除不生效的Tailwind CSS自定义样式块，其内容将移至main.css -->
</head>
<body class="font-inter bg-neutral text-dark min-h-screen flex flex-col">
    <!-- 导航栏 -->
    <header class="sticky top-0 z-50 bg-white/80 bg-blur border-b border-gray-200 transition-all duration-300">
        <div class="container max-w-screen-xl mx-auto px-4">
            <nav class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="#" class="flex items-center space-x-2">
                        <img src="Jelisgo.ico" alt="图片" class="w-6 h-6 text-primary" />
                        <span id="nav-brand-name" class="text-xl font-bold">
                            <span class="brand-primary">壁纸喵</span>
                            <span class="brand-secondary">° 不吃鱼</span>
                        </span>
                    </a>
                </div>
                
                <!-- 搜索框 -->
                <div class="hidden md:flex items-center mx-4 flex-1 max-w-xl">
                    <div class="relative w-full">
                        <input type="text" id="search-input" placeholder="搜索壁纸..." 
                            class="w-full pl-10 pr-4 py-2 rounded-full border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all">
                        <img src="static/icons/fa-search.svg" alt="搜索" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                    </div>
                    <!-- 二维码按钮 -->
                    <button id="qrcode-btn" title="站点二维码" class="ml-2 flex items-center justify-center w-10 h-10 rounded-full bg-white border border-gray-300 hover:bg-primary hover:text-primary transition-colors">
                        <svg viewBox="0 0 1024 1024" width="24" height="24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                            <path d="M468.8 203.2H201.6v267.2h267.2V203.2z m-32 235.2H233.6V235.2h203.2v203.2z"/>
                            <path d="M395.2 276.8h-120v120h120v-120z m-16 104h-88v-88h88v88zM752 276.8h-120v120H752v-120z m-16 104h-88v-88H736v88z"/>
                            <path d="M822.4 203.2H555.2v267.2h267.2V203.2z m-32 235.2H587.2V235.2h203.2v203.2zM395.2 635.2h-120v120h120v-120z m-16 104h-88v-88h88v88z"/>
                            <path d="M468.8 553.6H201.6v267.2h267.2V553.6z m-32 235.2H233.6V585.6h203.2v203.2zM504 203.2h16v616h-16zM561.6 510.4h16v308.8h-16zM201.6 505.6h265.6v16H201.6zM681.6 819.2h142.4v-142.4h-142.4v142.4z m32-108.8h78.4v78.4h-78.4v-78.4zM619.2 510.4h16v308.8h-16zM681.6 510.4h16v120h-16zM742.4 510.4h16v120h-16zM806.4 510.4h16v120h-16z"/>
                        </svg>
                    </button>
                </div>
                
                <div class="flex items-center space-x-4">
                    <!-- 暂时注释掉上传壁纸按钮 -->
                    <!-- <a href="upload_wallpaper.php" id="upload-btn" class="hidden md:flex items-center space-x-2 bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-full transition-all">
                        <img src="static/icons/fa-cloud-upload.svg" alt="上传" class="w-4 h-4" />
                        <span>上传壁纸</span>
                    </a> -->
                    
                    <!-- 用户菜单 -->
                    <div id="user-menu" class="relative">
                        <button id="user-btn" class="flex items-center space-x-2 focus:outline-none cursor-pointer">
                            <img id="user-avatar" src="/static/icons/default-avatar.svg" alt="用户头像" class="w-8 h-8 min-w-[2rem] rounded-full hidden" />
                            <span id="username" class="hidden md:inline-block">登录/注册</span>
                            <img src="static/icons/fa-caret-down.svg" alt="下拉" class="w-4 h-4 text-gray-500" />
                        </button>
                        <div id="user-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-100 z-50">
                            <a href="#" id="login-link" class="block px-4 py-2 hover:bg-gray-100 cursor-pointer">登录</a>
                            <a href="#" id="register-link" class="block px-4 py-2 hover:bg-gray-100 cursor-pointer">注册</a>
                            <div class="border-t border-gray-100"></div>
                            <a href="#" id="admin-panel-link" class="hidden block px-4 py-2 hover:bg-gray-100 cursor-pointer">
                                <i class="fa fa-cog mr-2"></i>管理后台
                            </a>
                            <a href="#" id="logout-link" class="hidden block px-4 py-2 hover:bg-gray-100 cursor-pointer">退出登录</a>
                        </div>
                    </div>
                    
                    <!-- 移动端搜索按钮 -->
                    <div class="md:hidden flex items-center">
                        <button id="mobile-search-toggle" class="p-2 rounded-full hover:bg-gray-100 transition-colors">
                            <img src="static/icons/fa-search.svg" alt="搜索" class="w-5 h-5 text-gray-600" />
                        </button>
                    </div>
                </div>
            </nav>
            
            <!-- 移动端搜索框 -->
            <div id="mobile-search-container" class="hidden md:hidden pb-3">
                <div class="relative w-full">
                    <input type="text" id="mobile-search-input" placeholder="搜索壁纸..." 
                        class="w-full pl-10 pr-4 py-2 rounded-full border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary">
                    <img src="static/icons/fa-search.svg" alt="搜索" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                </div>
            </div>
            
            <!-- 移动端菜单 -->
            <div id="mobile-menu" class="hidden md:hidden pb-4">
                <!-- 暂时注释掉移动端上传壁纸按钮 -->
                <!-- <button class="w-full flex items-center justify-center space-x-2 bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-full mb-3" id="mobile-upload-btn">
                    <img src="static/icons/fa-cloud-upload.svg" alt="上传" class="w-4 h-4" />
                    <span>上传壁纸</span>
                </button> -->
                <div class="flex flex-col space-y-2">
                    <a href="#" class="block px-4 py-2 hover:bg-gray-100 rounded-lg not-implemented">登录</a>
                    <a href="#" class="block px-4 py-2 hover:bg-gray-100 rounded-lg not-implemented">注册</a>
                </div>
            </div>
        </div>
    </header>

    <!-- 主内容区 -->
    <main class="flex-1 container max-w-screen-xl mx-auto px-4 py-6">
        <!-- 分类导航 - 已注释掉 -->
        <!-- <div class="mb-8 overflow-x-auto min-h-12 flex items-center">
            <div id="category-nav-container" class="flex space-x-2 pb-2 w-full items-center"></div>
        </div> -->
        
        <!-- 视图切换 -->
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold">热门壁纸</h2>
            <div class="flex space-x-2">
                <button id="grid-view-btn" class="p-2 rounded bg-primary text-white">
                    <img src="static/icons/fa-th-large.svg" alt="网格视图" class="w-4 h-4" />
                </button>
                <button id="list-view-btn" class="p-2 rounded bg-white hover:bg-neutral-dark transition-colors" title="我的收藏">
                    <img src="static/icons/fa-heart.svg" alt="我的收藏" class="w-4 h-4" />
                </button>
                <!-- 2024-07-28 新增：流放图片按钮，仅管理员可见 -->
                <button id="exiled-list-view-btn" class="p-2 rounded bg-white hover:bg-neutral-dark transition-colors hidden">
                    <img src="static/icons/lf.png" alt="流放图片" class="w-4 h-4" />
                </button>
            </div>
        </div>
        
        <!-- 瀑布流展示区 -->
        <div id="wallpaper-container" class="masonry-grid min-h-[400px]">
            <!-- 由JS动态生成壁纸卡片，支持骨架屏与懒加载 -->
        </div>
        
        <!-- 加载更多按钮 -->
        <div class="flex justify-center mt-8">
            <button id="load-more-btn" class="px-6 py-3 bg-white border border-gray-300 rounded-full hover:bg-neutral-dark transition-colors flex items-center space-x-2">
                <span>加载更多</span>
                <img src="static/icons/fa-refresh.svg" alt="刷新" class="w-3 h-3 text-gray-400" />
            </button>
        </div>
    </main>

    <!-- 页脚 -->
    <footer class="bg-white border-t border-gray-200 py-8">
        <div class="container max-w-screen-xl mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="flex items-center mb-4 md:mb-0">
                    <img src="Jelisgo.ico" alt="图片" class="w-6 h-6 text-primary mr-2" />
                    <span id="footer-brand-name" class="text-xl font-bold text-primary"><?php echo htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <div class="flex space-x-6 mb-4 md:mb-0">
                    <a href="about.php" class="text-gray-600 hover:text-primary transition-colors">关于我们</a>
                    <a href="terms.php" class="text-gray-600 hover:text-primary transition-colors">使用条款</a>
                    <a href="privacy.php" class="text-gray-600 hover:text-primary transition-colors">隐私政策</a>
                    <a href="about.php#contact" class="text-gray-600 hover:text-primary transition-colors">联系我们</a>
                </div>
                <div class="flex space-x-4">
                    <a href="#" class="w-10 h-10 rounded-full bg-neutral flex items-center justify-center text-gray-600 hover:bg-primary hover:text-white transition-all">
                        <img src="static/icons/fa-facebook.svg" alt="Facebook" class="w-4 h-4" />
                    </a>
                    <a href="#" class="w-10 h-10 rounded-full bg-neutral flex items-center justify-center text-gray-600 hover:bg-primary hover:text-white transition-all">
                        <img src="static/icons/fa-twitter.svg" alt="Twitter" class="w-4 h-4" />
                    </a>
                    <a href="#" class="w-10 h-10 rounded-full bg-neutral flex items-center justify-center text-gray-600 hover:bg-primary hover:text-white transition-all">
                        <img src="static/icons/fa-instagram.svg" alt="Instagram" class="w-4 h-4" />
                    </a>
                    <a href="#" class="w-10 h-10 rounded-full bg-neutral flex items-center justify-center text-gray-600 hover:bg-primary hover:text-white transition-all">
                        <img src="static/icons/fa-github.svg" alt="GitHub" class="w-4 h-4" />
                    </a>
                </div>
            </div>
            <div class="mt-6 text-center text-gray-500 text-sm">
                <p>© 2025 Wallpaper Haven. 保留所有权利。</p>
            </div>
        </div>
    </footer>

    <!-- 登录模态框 -->
    <div id="login-modal" class="fixed inset-0 bg-black/50 z-50 hidden">
        <div class="bg-white rounded-xl w-full max-w-md mx-4 relative" id="login-modal-content">
            <div class="relative p-6">
                <button id="close-login-modal" class="absolute top-4 right-4 text-gray-700 hover:text-black z-50 text-2xl font-bold w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100" style="display:block;">×</button>
                <h2 class="text-2xl font-bold text-center mb-6">登录</h2>
                <form id="login-form">
                    <div class="mb-4">
                        <label for="login-username" class="block text-sm font-medium text-gray-700 mb-1">用户名</label>
                        <input type="text" id="login-username" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary" placeholder="请输入用户名或邮箱">
                    </div>
                    <div class="mb-6">
                        <label for="login-password" class="block text-sm font-medium text-gray-700 mb-1">密码</label>
                        <input type="password" id="login-password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary" placeholder="请输入密码">
                    </div>
                    <div class="form-error-msg text-red-500 text-sm mb-4 min-h-[20px]"></div>
                    <button type="button" id="login-submit" class="w-full py-3 rounded-lg transition-colors" style="background:#1A73E8;color:#fff;display:block;">登录</button>
                </form>
                <div class="mt-4 text-center">
                    <a href="api/minaxg/forgot-password.php" id="forgot-password-link" class="text-primary hover:underline text-sm mb-2 block">忘记密码？</a>
                    <span class="text-gray-600">没有账号？</span>
                    <button id="switch-to-register" class="text-primary hover:underline ml-1">立即注册</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 注册模态框 -->
    <div id="register-modal" class="fixed inset-0 bg-black/50 z-50 hidden">
        <div class="bg-white rounded-xl w-full max-w-md mx-4 relative" id="register-modal-content">
            <div class="relative p-6">
                <button id="close-register-modal" class="absolute top-4 right-4 text-gray-700 hover:text-black z-50 text-2xl font-bold w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100" style="display:block;">×</button>
                <h2 class="text-2xl font-bold text-center mb-6">注册</h2>
                <form id="register-form">
                    <div class="mb-4">
                        <label for="register-email" class="block text-sm font-medium text-gray-700 mb-1">邮箱</label>
                        <input type="email" id="register-email" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary" placeholder="请输入邮箱">
                    </div>
                    <div class="mb-4">
                        <label for="register-password" class="block text-sm font-medium text-gray-700 mb-1">密码</label>
                        <input type="password" id="register-password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary" placeholder="请输入密码（至少4位）">
                    </div>
                    <div class="mb-4">
                        <label for="register-confirm-password" class="block text-sm font-medium text-gray-700 mb-1">确认密码</label>
                        <input type="password" id="register-confirm-password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary" placeholder="请再次输入密码">
                    </div>
                    <!-- 真人验证 -->
                    <div class="mb-6">
                        <div class="flex items-center p-4 border border-gray-300 rounded-lg bg-gray-50">
                            <input type="checkbox" id="human-verification" class="mr-3 w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary focus:ring-2">
                            <label for="human-verification" class="text-sm text-gray-700 cursor-pointer flex items-center">
                                <span class="mr-2">确认您是真人</span>
                                <div class="flex items-center justify-center w-12 h-8 bg-orange-500 text-white text-xs font-bold rounded">
                                    <svg viewBox="0 0 24 24" class="w-4 h-4 fill-current">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                    </svg>
                                </div>
                            </label>
                        </div>
                    </div>
                    <div class="form-error-msg text-red-500 text-sm mb-4 min-h-[20px]"></div>
                    <button type="button" id="register-submit" class="w-full py-3 rounded-lg transition-colors" style="background:#1A73E8;color:#fff;display:block;">注册</button>
                </form>
                <div class="mt-4 text-center">
                    <span class="text-gray-600">已有账号？</span>
                    <button id="switch-to-login" class="text-primary hover:underline ml-1">立即登录</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 忘记密码模态框 -->    
    <div id="forgot-password-modal" class="fixed inset-0 bg-black/50 z-50 hidden">
        <div class="bg-white rounded-xl w-full max-w-md mx-4 relative" id="forgot-password-modal-content">
            <div class="relative p-6">
                <button id="close-forgot-password-modal" class="absolute top-4 right-4 text-gray-700 hover:text-black z-50 text-2xl font-bold w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100" style="display:block;">×</button>
                <h2 class="text-2xl font-bold text-center mb-6">重置密码</h2>
                <form id="forgot-password-form">
                    <div class="mb-4">
                        <label for="reset-email" class="block text-sm font-medium text-gray-700 mb-1">邮箱地址</label>
                        <input type="email" id="reset-email" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary" placeholder="请输入注册邮箱">
                    </div>
                    <div class="form-error-msg text-red-500 text-sm mb-4 min-h-[20px]"></div>
                    <button type="button" id="send-reset-code" class="w-full py-3 rounded-lg transition-colors" style="background:#1A73E8;color:#fff;display:block;">发送验证码</button>
                </form>
                <div class="mt-4 text-center">
                    <button id="back-to-login" class="text-primary hover:underline text-sm">返回登录</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 验证码确认模态框 -->
    <div id="verify-code-modal" class="fixed inset-0 bg-black/50 z-50 hidden">
        <div class="bg-white rounded-xl w-full max-w-md mx-4 relative" id="verify-code-modal-content">
            <div class="relative p-6">
                <button id="close-verify-code-modal" class="absolute top-4 right-4 text-gray-700 hover:text-black z-50 text-2xl font-bold w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100" style="display:block;">×</button>
                <h2 class="text-2xl font-bold text-center mb-6">输入验证码</h2>
                <p class="text-gray-600 text-center mb-4">验证码已发送到您的邮箱，请查收</p>
                <form id="verify-code-form">
                    <div class="mb-4">
                        <label for="verification-code" class="block text-sm font-medium text-gray-700 mb-1">验证码</label>
                        <input type="text" id="verification-code" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary text-center text-2xl tracking-widest" placeholder="请输入6位验证码" maxlength="6">
                    </div>
                    <div class="form-error-msg text-red-500 text-sm mb-4 min-h-[20px]"></div>
                    <button type="button" id="verify-code-submit" class="w-full py-3 rounded-lg transition-colors" style="background:#1A73E8;color:#fff;display:block;">验证</button>
                </form>
                <div class="mt-4 text-center">
                    <button id="resend-code" class="text-primary hover:underline text-sm">重新发送验证码</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 重置密码模态框 -->
    <div id="reset-password-modal" class="fixed inset-0 bg-black/50 z-50 hidden">
        <div class="bg-white rounded-xl w-full max-w-md mx-4 relative" id="reset-password-modal-content">
            <div class="relative p-6">
                <button id="close-reset-password-modal" class="absolute top-4 right-4 text-gray-700 hover:text-black z-50 text-2xl font-bold w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100" style="display:block;">×</button>
                <h2 class="text-2xl font-bold text-center mb-6">设置新密码</h2>
                <form id="reset-password-form">
                    <div class="mb-4">
                        <label for="new-password" class="block text-sm font-medium text-gray-700 mb-1">新密码</label>
                        <input type="password" id="new-password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary" placeholder="请输入新密码">
                    </div>
                    <div class="mb-6">
                        <label for="confirm-new-password" class="block text-sm font-medium text-gray-700 mb-1">确认新密码</label>
                        <input type="password" id="confirm-new-password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary" placeholder="请再次输入新密码">
                    </div>
                    <div class="form-error-msg text-red-500 text-sm mb-4 min-h-[20px]"></div>
                    <button type="button" id="reset-password-submit" class="w-full py-3 rounded-lg transition-colors" style="background:#1A73E8;color:#fff;display:block;">重置密码</button>
                </form>
            </div>
        </div>
    </div>

    <!-- 壁纸详情模态框 -->
    <div id="wallpaper-detail-modal" class="fixed inset-0 bg-black/80 z-50 hidden flex items-center justify-center">
      <div id="wallpaper-detail-modal-content">
        <!-- 独立关闭按钮 -->
        <button id="close-detail-modal">&times;</button>
        <div class="modal-grid">
          <!-- 图片区域 -->
          <div class="modal-image-container bg-black rounded-lg overflow-hidden relative shadow-2xl border border-gray-200">
            <div class="modal-image-wrapper w-full h-full flex items-center justify-center">
              <img id="detail-image" src="" alt="" class="modal-image object-contain w-full h-full rounded-lg opacity-0 transition-opacity duration-300">
              <!-- 图片加载指示器 (2024-07-29 新增) -->
              <div id="image-loading-indicator" class="absolute inset-0 flex items-center justify-center bg-black text-white text-lg rounded-lg">
                <i class="fa fa-spinner fa-spin mr-2"></i> 加载中...
              </div>
            </div>
          </div>
          
          <!-- 详情区域 -->
          <div class="modal-details-container">
            
            <!-- 标题 -->
            <h2 id="detail-title" class="text-2xl font-bold text-gray-800 mb-4">壁纸标题</h2>
            
            <!-- 元数据 -->
            <div class="grid grid-cols-2 gap-4 text-sm text-gray-600 mb-4">
              <div class="col-span-1">
                <span>原图大小:</span> <span id="detail-file-size"></span>
              </div>
              <div class="col-span-1">
                <span>格式:</span> <span id="detail-format"></span>
                
              </div>
            </div>
            
            <!-- 格式和上传时间 (2024-07-29 修复: 重新添加并调整结构) -->
            <div class="grid grid-cols-2 gap-4 text-sm text-gray-600 mb-4">
              <div class="col-span-1">
                <span>原图分辨率:</span> <span id="detail-dimensions"></span>
              </div>
              <div class="col-span-1">
                <span>上传时间:</span> <span id="detail-upload-time"></span>
              </div>
            </div>
            
            <!-- 分类 -->
            <div class="mb-4">
              <span class="font-medium text-gray-700">分类:</span>
              <span id="detail-category" class="ml-2 px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">未分类</span>
            </div>
            
            <!-- 标签 -->
            <div class="mb-4">
              <span class="font-medium text-gray-700">标签:</span>
              <div id="detail-tags" class="mt-2 flex flex-wrap gap-2">
                <!-- 标签将在这里动态添加 -->
              </div>
            </div>
            
            <!-- 查看和喜欢统计
            <div class="flex justify-between text-sm text-gray-500 pt-2 border-t mt-4">
                <span>查看: <span id="detail-views">0</span> 次</span>
                <span>喜欢: <span id="detail-likes">0</span> 次</span>
              </div> -->
            
            <!-- 操作按钮 -->
            <div class="flex flex-wrap gap-3 pt-4">
              <button id="download-btn" class="flex-1 bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                <img src="static/icons/fa-download.svg" alt="下载" class="w-4 h-4">
                高清下载
              </button>
              <button id="preview-btn" class="flex-1 preview-btn text-white px-4 py-2 rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                <img src="static/icons/fa-eye.svg" alt="预览" class="w-4 h-4">
                超清下载
              </button>
              <!-- <button id="like-btn" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors flex items-center gap-2">
                <img id="like-icon" src="static/icons/fa-heart-o.svg" alt="喜欢" class="w-4 h-4">
                <span id="like-text">喜欢</span>
              </button>-->
              <button id="favorite-btn" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors flex items-center gap-2">
                <img id="favorite-icon" src="static/icons/fa-star-o.svg" alt="收藏" class="w-4 h-4">
                <span id="favorite-text">收藏</span>
              </button>
              <button id="share-btn" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors flex items-center gap-2">
                <img src="static/icons/fa-share-alt.svg" alt="分享" class="w-4 h-4">
                分享
              </button>
              <!-- 详情页按钮--
              <button id="detail-page-btn" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors flex items-center gap-2" title="查看独立详情页面">
                <img src="static/icons/fa-external-link.svg" alt="详情页" class="w-4 h-4">
                详情页
              </button> -->
            </div>
            
            <!-- AI生图提示词 -->
            <div class="prompt-section">
                <div class="flex items-center justify-between mb-3">
                  <div class="flex items-center">
                    <h3 class="text-lg font-semibold text-gray-800">AI生图提示词</h3>
                    <button id="copy-prompt-btn" class="ml-2 p-1 rounded-full text-gray-500 hover:bg-gray-200 hover:text-primary transition-colors focus:outline-none" title="复制提示词">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" />
                        <path d="M6 3a2 2 0 00-2 2v11a2 2 0 002 2h8a2 2 0 002-2V5a2 2 0 00-2-2H6zM5 9a1 1 0 011-1h8a1 1 0 110 2H6a1 1 0 01-1-1z" />
                      </svg>
                    </button>
                    <!-- 复制成功提示消息 (2024-07-30 新增) -->
                    <span id="copy-success-message" class="text-sm opacity-0 transition-opacity duration-300 ml-2"></span>
                  </div>
                  <div class="flex items-center gap-3 ml-auto">
                    <!-- 锁定状态指示器 -->
                    <div class="lock-indicator">
                      <img id="prompt-lock-icon" src="static/icons/fa-lock.svg" alt="锁定" class="w-4 h-4">
                      <span id="prompt-lock-text" class="text-sm">已锁定</span>
                    </div>
                    <!-- 管理员锁定切换按钮 -->
                    <button id="toggle-prompt-lock" class="hidden px-3 py-1 bg-yellow-500 hover:bg-yellow-600 text-white text-sm rounded-md transition-colors">
                      切换锁定
                    </button>
                  </div>
                </div>
                
                <!-- 权限不足提示 -->
                <div id="prompt-permission-denied" class="hidden p-4 bg-gray-100 rounded-lg text-center text-gray-600" style="background: #f8f9fa; border: 2px dashed #dee2e6; border-radius: 8px; padding: 2rem; text-align: center; color: #6c757d; font-size: 0.95rem; margin: 1rem 0;">
                  <div class="icon" style="font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.6;">🔒</div>
                  <div class="message" style="margin-bottom: 0.5rem; font-weight: 500;">暂无内容</div>
                  <div class="hint" style="font-size: 0.85rem; opacity: 0.8;"></div>
                </div>
                
                <!-- 查看模式 -->
                <div id="prompt-view">
                  <div id="prompt-content" class="prompt-content">
                    暂无提示词信息
                  </div>
                  <div id="prompt-edit-btn-area" class="hidden mt-3">
                    <button id="edit-prompt-btn" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white text-sm rounded-md transition-colors">
                      编辑提示词
                    </button>
                  </div>
                </div>
                
                <!-- 编辑模式 -->
                <div id="prompt-edit" class="hidden">
                  <textarea id="prompt-textarea" class="prompt-textarea" placeholder="请输入AI生图提示词..."></textarea>
                  <div class="flex gap-2 mt-3">
                    <button id="save-prompt" class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white text-sm rounded-md transition-colors">
                      保存
                    </button>
                    <button id="cancel-prompt-edit" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white text-sm rounded-md transition-colors">
                      取消
                    </button>
                  </div>
                </div>
              </div>
              
              <!-- 自定义链接管理模块 -->
              <div id="custom-links-section" class="custom-links-section hidden">
                <div class="custom-links-header">
                  <h3 class="custom-links-title">智能体（Jelisgo）</h3>
                  <button id="add-custom-link-btn" class="custom-links-add-btn hidden" title="添加自定义链接">
                    <i class="fa fa-plus"></i>
                    添加链接
                  </button>
                </div>
                
                <!-- 权限不足提示 -->
                <div id="links-permission-denied" class="hidden p-4 bg-gray-100 rounded-lg text-center text-gray-600" style="background: #f8f9fa; border: 2px dashed #dee2e6; border-radius: 8px; padding: 2rem; text-align: center; color: #6c757d; font-size: 0.95rem; margin: 1rem 0;">
                  <div class="icon" style="font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.6;">🔒</div>
                  <div class="message" style="margin-bottom: 0.5rem; font-weight: 500;">暂无内容</div>
                  <div class="hint" style="font-size: 0.85rem; opacity: 0.8;"></div>
                </div>
                
                <div id="custom-links-list" class="custom-links-list">
                  <div class="custom-links-empty">暂无自定义链接</div>
                </div>
              </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 自定义链接模态框 -->
    <div id="custom-link-modal" class="custom-link-modal hidden">
      <div class="custom-link-modal-content">
        <div class="custom-link-modal-header">
          <h3 id="custom-link-modal-title">添加自定义链接</h3>
          <button id="close-custom-link-modal" class="custom-link-modal-close">
            <i class="fa fa-times"></i>
          </button>
        </div>
        <form id="custom-link-form" class="custom-link-form">
          <div class="custom-link-form-group">
            <label for="link-title">链接标题 *</label>
            <input type="text" id="link-title" name="title" required maxlength="100" placeholder="请输入链接标题">
          </div>
          <div class="custom-link-form-group">
            <label for="link-url">链接地址 *</label>
            <input type="url" id="link-url" name="url" required placeholder="https://example.com">
          </div>
          <div class="custom-link-form-group">
            <label for="link-priority">重要程度 *</label>
            <select id="link-priority" name="priority" required>
              <option value="1">低 (灰色)</option>
              <option value="2">中 (蓝色)</option>
              <option value="3" selected>高 (绿色)</option>
              <option value="4">紧急 (橙色)</option>
              <option value="5">关键 (红色)</option>
            </select>
          </div>
          <div class="custom-link-form-group">
            <label for="link-description">描述信息</label>
            <textarea id="link-description" name="description" maxlength="255" placeholder="可选：添加链接描述信息"></textarea>
          </div>
          <div class="custom-link-form-actions">
            <button type="button" id="cancel-custom-link" class="custom-link-btn-secondary">取消</button>
            <button type="submit" id="save-custom-link" class="custom-link-btn-primary">保存</button>
          </div>
        </form>
      </div>
    </div>

    <!-- 二维码模态框 -->
    <div id="qrcode-modal" class="fixed inset-0 bg-black/50 z-50 hidden">
        <div class="bg-white rounded-xl p-8 shadow-lg flex flex-col items-center" id="qrcode-modal-content">
            <span class="text-lg font-bold mb-4">手机扫码访问本站</span>
            <canvas id="qrcode-canvas" class="mb-4" width="200" height="200"></canvas>
            <button id="close-qrcode-modal" class="mt-2 px-6 py-2 border border-gray-300 rounded-lg hover:bg-neutral-dark transition-colors">关闭</button>
        </div>
    </div>

    <!-- 管理分类模态框 -->
    <div id="manage-categories-modal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
        <div id="manage-categories-modal-content" class="bg-white rounded-xl shadow-lg max-w-2xl w-full max-h-[80vh] overflow-y-auto transform scale-95 opacity-0 transition-all duration-300">
            <div class="p-6">
                <!-- 模态框头部 -->
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-900">管理分类</h2>
                    <button id="close-manage-categories-modal" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="fa fa-times text-xl"></i>
                    </button>
                </div>
                
                <!-- 添加新分类 -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">添加新分类</h3>
                    <div class="flex gap-3">
                        <input type="text" id="new-category-name" placeholder="输入分类名称" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <button id="submit-add-category" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                            <i class="fa fa-plus mr-2"></i>添加
                        </button>
                    </div>
                </div>
                
                <!-- 现有分类列表 -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">现有分类</h3>
                    <div id="categories-list" class="space-y-2 max-h-60 overflow-y-auto">
                        <!-- 分类列表将通过JavaScript动态生成 -->
                    </div>
                </div>
                
                <!-- 操作按钮 -->
                <div class="flex justify-end gap-3">
                    <button id="cancel-manage-categories" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        取消
                    </button>
                    <button id="save-categories" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                        <i class="fa fa-save mr-2"></i>保存更改
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <!-- 本地二维码库 -->
    <script src="static/js/qrious.min.js"></script>
    <!-- 设备指纹库 -->
    <script src="static/js/device-fingerprint.js"></script>
    <!-- 引入自定义JS -->
    <!-- 数据库迁移配置 -->
    <script src="static/js/migration-config.js"></script>
    <script src="static/js/config.js"></script>
    <script src="static/js/utils.js"></script>
    <script src="static/js/modals.js"></script>
    <script src="static/js/user-menu.js"></script>
    <script src="static/js/auth.js"></script>
    <script src="static/js/membership-status.js?v=20250627"></script> <!-- 2025-06-27 新增：会员状态管理模块 -->
    <script src="static/js/permission-manager.js"></script> <!-- 2025-01-27 新增：权限管理器 -->
    <script src="static/js/image-compressor.js"></script>
    <script src="static/js/intelligent-preloader.js"></script>
    <script src="static/js/image-token-manager.js"></script>
    <script src="static/js/image-loader.js"></script>
    <script src="static/js/wallpaper-detail.js"></script>
    <!-- 引入自定义链接管理模块 -->
    <script src="static/js/custom-links.js"></script>
    <script src="static/js/password-reset.js"></script>
    <script src="static/js/back-to-top.js"></script>
    <script src="static/js/main.js"></script>
    <script src="static/js/svg-icons.js"></script>
    <!-- 引入站点配置模块 -->
    <script src="static/js/site-config.js"></script>
    
    <!-- 初始化模块 -->
    <script>
        // 2025-01-27 修复重复初始化问题：统一在此处初始化ImageLoader和WallpaperDetail
        document.addEventListener('DOMContentLoaded', function() {
            console.log('[Main] 页面加载完成，初始化模块');
            
            // 初始化图片加载器
            if (typeof ImageLoader !== 'undefined') {
                ImageLoader.init();
            }
            
            // 初始化壁纸详情模块
            if (typeof WallpaperDetail !== 'undefined') {
                WallpaperDetail.init();
            }
            
            // 初始化自定义链接管理模块
            if (typeof CustomLinksManager !== 'undefined') {
                CustomLinksManager.init();
            }
        });
    </script>
    <script>
    // Removed Direct HTML Test Log

    // 首页自动刷新头像 - 已移除，统一使用auth.js中的checkLoginStatus
    </script>
    <!-- 回到顶部按钮 -->
    <button id="back-to-top" title="回到顶部" class="fixed right-6 bottom-8 z-50 bg-primary text-white rounded-full shadow-lg w-12 h-12 flex items-center justify-center text-2xl transition-all duration-300 opacity-0 pointer-events-none hover:bg-primary/90">
        <img src="static/icons/fa-arrow-up.svg" alt="回到顶部" class="w-5 h-5" />
    </button>
</body>
</html>
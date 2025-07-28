<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO基础标签 -->
    <title>多设备壁纸预览工具 - 壁纸喵 ° 不吃鱼</title>
    <meta name="description" content="免费在线多设备壁纸预览工具，支持手机、平板、电脑壁纸实时预览和下载。上传图片即可生成适配不同设备的高清壁纸，支持JPG、PNG、WebP格式。">
    <meta name="keywords" content="壁纸预览,多设备壁纸,手机壁纸,平板壁纸,电脑壁纸,壁纸下载,在线壁纸工具,高清壁纸,免费壁纸">
    <meta name="author" content="多设备壁纸预览工具">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://Jelisgo.cn/yulan.php">
    
    <!-- Open Graph标签 -->
    <meta property="og:title" content="多设备壁纸预览工具 - 壁纸喵 ° 不吃鱼">
    <meta property="og:description" content="免费在线多设备壁纸预览工具，支持手机、平板、电脑壁纸实时预览和下载。">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://Jelisgo.cn/yulan.php">
    <meta property="og:image" content="https://Jelisgo.cn/static/images/og-image.jpg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name" content="多设备壁纸预览工具">
    <meta property="og:locale" content="zh_CN">
    
    <!-- Twitter Card标签 -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="多设备壁纸预览工具 - 壁纸喵 ° 不吃鱼">
    <meta name="twitter:description" content="免费在线多设备壁纸预览工具，支持手机、平板、电脑壁纸实时预览和下载。">
    <meta name="twitter:image" content="https://Jelisgo.cn/static/images/twitter-image.jpg">
    
    <!-- 移动端优化 -->
    <meta name="theme-color" content="#165DFF">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="壁纸预览工具">
    
    <!-- 性能优化 -->
    <link rel="preconnect" href="https://cdn.tailwindcss.com">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="dns-prefetch" href="https://fonts.googleapis.com">
    
    <!-- 样式和脚本 -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
    <link href="static/css/main.css" rel="stylesheet">
    <link href="static/css/yulan.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#165DFF',
                        secondary: '#86909C',
                        accent: '#FF7D00',
                        dark: '#1D2129',
                        light: '#F2F3F5',
                        'apple-gray': '#8E8E93',
                        'apple-dark': '#1C1C1E',
                        'apple-white': '#F9F9F9'
                    },
                    fontFamily: {
                        inter: ['Inter', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    
    <!-- 结构化数据 JSON-LD -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebApplication",
        "name": "多设备壁纸预览工具",
        "description": "免费在线多设备壁纸预览工具，支持手机、平板、电脑壁纸实时预览和下载。上传图片即可生成适配不同设备的高清壁纸。",
        "url": "https://Jelisgo.cn/yulan.php",
        "applicationCategory": "DesignApplication",
        "operatingSystem": "Web Browser",
        "offers": {
            "@type": "Offer",
            "price": "0",
            "priceCurrency": "CNY",
            "availability": "https://schema.org/InStock"
        },
        "featureList": [
            "多设备壁纸预览",
            "手机壁纸适配",
            "平板壁纸适配",
            "电脑壁纸适配",
            "高清壁纸下载",
            "支持JPG、PNG、WebP格式"
        ],
        "screenshot": "https://Jelisgo.cn/static/images/yulan-app-screenshot.jpg",
        "author": {
            "@type": "Organization",
            "name": "多设备壁纸预览工具"
        },
        "inLanguage": "zh-CN",
        "browserRequirements": "Requires JavaScript. Requires HTML5."
    }
    </script>
</head>
<body class="bg-gradient-to-br from-light to-gray-100 min-h-screen font-inter text-dark overflow-x-hidden">
    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <!-- 头部 -->
        <header class="text-center mb-12" role="banner">
            <h1 class="text-[clamp(2rem,5vw,3.5rem)] font-bold text-primary mb-4 tracking-tight">
                <span class="text-accent">多设备</span>壁纸预览工具
            </h1>
            <p class="text-gray-600 text-[clamp(1rem,2vw,1.25rem)] max-w-2xl mx-auto">
                免费在线壁纸预览工具，支持手机、平板、电脑适配，一键生成高清壁纸
            </p>
        </header>

        <main role="main">
            <div class="flex flex-col lg:flex-row gap-12 items-center justify-center">
                <!-- 设备预览区域 -->
                <section class="w-full lg:w-1/2" aria-label="设备预览区域">
                    <h2 class="sr-only">壁纸预览</h2>
                    <!-- 设备选择标签 -->
                    <nav class="device-tabs mb-6" aria-label="设备选择" role="tablist">
                        <button class="device-tab active" data-device="phone" role="tab" aria-selected="true" aria-controls="phonePreview">
                            <i class="fa-solid fa-mobile-screen-button mr-2" aria-hidden="true"></i>手机预览
                        </button>
                        <button class="device-tab" data-device="tablet" role="tab" aria-selected="false" aria-controls="tabletPreview">
                            <i class="fa-solid fa-tablet-screen-button mr-2" aria-hidden="true"></i>平板预览
                        </button>
                        <button class="device-tab" data-device="laptop" role="tab" aria-selected="false" aria-controls="laptopPreview">
                            <i class="fa-solid fa-laptop mr-2" aria-hidden="true"></i>电脑预览
                        </button>
                        <button class="device-tab" data-device="moment" role="tab" aria-selected="false" aria-controls="momentPreview">
                            <i class="fa-solid fa-image mr-2" aria-hidden="true"></i>朋友圈预览
                        </button>
                    </nav>

                <!-- 设备预览容器 -->
                <div class="relative w-full" role="tabpanel">
                    <!-- 手机预览 -->
                    <div class="device-preview" id="phonePreview" role="tabpanel" aria-labelledby="phone-tab">
                        <div class="relative phone-rotate">
                            <canvas id="phoneCanvas" class="mx-auto phone-shadow rounded-[40px] phone-bezel" aria-label="手机壁纸预览画布"></canvas>
                            <!-- 苹果手机刘海 -->
                            <div class="apple-notch" aria-hidden="true"></div>
                        </div>
                    </div>

                    <!-- 平板预览 -->
                    <div class="device-preview hidden" id="tabletPreview" role="tabpanel" aria-labelledby="tablet-tab">
                        <div class="relative tablet-rotate">
                            <canvas id="tabletCanvas" class="mx-auto phone-shadow rounded-[25px] phone-bezel max-w-full h-auto" aria-label="平板壁纸预览画布"></canvas>
                        </div>
                    </div>

                    <!-- 电脑预览 -->
                    <div class="device-preview hidden" id="laptopPreview" role="tabpanel" aria-labelledby="laptop-tab">
                        <div class="relative laptop-rotate">
                            <canvas id="laptopCanvas" class="mx-auto max-w-full h-auto" aria-label="电脑壁纸预览画布"></canvas>
                        </div>
                     <!-- 调试控制区域 -->
                     <div class="mt-6 pt-4 border-t border-gray-200">
                        <h4 class="font-semibold text-sm mb-2 flex items-center">
                            <i class="fa-solid fa-sliders text-secondary mr-2"></i> 壁纸位置调整（电脑实时预览）
                        </h4>
                        <div class="flex items-center gap-2">
                            <button id="moveUpBtn" class="px-3 py-1 bg-gray-200 rounded-lg text-sm hover:bg-gray-300 active:bg-gray-400 touch-manipulation">
                                <i class="fa-solid fa-arrow-up"></i> 上移
                            </button>
                            <button id="moveDownBtn" class="px-3 py-1 bg-gray-200 rounded-lg text-sm hover:bg-gray-300 active:bg-gray-400 touch-manipulation">
                                <i class="fa-solid fa-arrow-down"></i> 下移
                            </button>
                            <span id="offsetValue" class="text-xs text-gray-500">偏移: 0px</span>
                        </div>
                    </div>   
                    </div>

                    <!-- 朋友圈预览 -->
                    <div class="device-preview hidden" id="momentPreview" role="tabpanel" aria-labelledby="moment-tab">
                        <div class="relative flex justify-center">
                            <canvas id="momentCanvas" class="mx-auto rounded-2xl shadow-lg max-w-full h-auto" style="width:100%;height:auto;display:block;" aria-label="朋友圈封面预览画布"></canvas>
                        </div>
                    </div>
                </div>
                </section>

                <!-- 功能区域 -->
                <section class="w-full lg:w-1/2 flex flex-col items-center" aria-label="壁纸上传和下载功能">
                    <h2 class="sr-only">壁纸上传和下载</h2>
                <!-- 上传区域 -->
                <article id="uploadArea" class="upload-area w-full max-w-md bg-white rounded-2xl p-8 shadow-lg mb-8 border-2 border-dashed border-gray-300 hover:border-primary transition-all duration-300" role="region" aria-label="壁纸上传区域">
                    <label for="wallpaperUpload" class="cursor-pointer flex flex-col items-center justify-center">
                        <div class="text-center mb-4">
                            <i class="fa-solid fa-cloud-arrow-up text-4xl text-primary mb-3" aria-hidden="true"></i>
                            <h3 class="text-xl font-semibold mb-2">上传壁纸图片</h3>
                            <p class="text-gray-500 text-sm">点击或拖拽图片到此处上传 (支持 JPG, PNG, WebP 格式)</p>
                        </div>
                        <input type="file" id="wallpaperUpload" accept="image/jpeg,image/png,image/webp" class="hidden" aria-describedby="upload-help" />
                        <button id="uploadButton" class="w-full bg-primary hover:bg-primary/90 text-white font-medium py-3 px-6 rounded-lg transition-all duration-300 button-hover button-shadow flex items-center justify-center" aria-describedby="upload-help">
                            <i class="fa-solid fa-image mr-2" aria-hidden="true"></i> 选择壁纸文件
                        </button>
                        <div id="upload-help" class="sr-only">支持上传JPG、PNG、WebP格式的图片文件，上传后可预览在不同设备上的显示效果</div>
                    </label>
                </article>

                <!-- 预览控制区域 -->
                <article id="previewControls" class="w-full max-w-md bg-white rounded-2xl p-6 shadow-lg mb-8 hidden" role="region" aria-label="壁纸下载控制">
                    <h3 class="text-xl font-semibold mb-4">壁纸下载</h3>
                    
                    <!-- 壁纸预览小窗口 -->
                    <div class="relative w-full aspect-[146/76] bg-gray-100 rounded-xl mb-6 overflow-hidden flex items-center justify-center" style="min-height:220px;" role="img" aria-label="壁纸预览窗口">
                        <img id="previewImage" src="" alt="上传的壁纸预览图" class="max-w-full max-h-80 object-contain rounded-xl shadow transition-all duration-300" style="aspect-ratio:146/76; background:#eee;">
                    </div>
                    
                    <!-- 控制按钮 - 仅管理员可见 -->
                    <div class="flex flex-col sm:flex-row gap-4 admin-only" style="display: none;" role="group" aria-label="管理员设备壁纸下载">
                        <button id="downloadPhoneButton" class="flex-1 bg-accent hover:bg-accent/90 text-white font-medium py-3 px-6 rounded-lg transition-all duration-300 button-hover button-shadow flex items-center justify-center" aria-label="下载手机壁纸">
                            <i class="fa-solid fa-mobile-screen-button mr-2" aria-hidden="true"></i> 手机壁纸
                        </button>
                        <button id="downloadTabletButton" class="flex-1 bg-secondary hover:bg-secondary/90 text-white font-medium py-3 px-6 rounded-lg transition-all duration-300 button-hover button-shadow flex items-center justify-center" aria-label="下载平板壁纸">
                            <i class="fa-solid fa-tablet-screen-button mr-2" aria-hidden="true"></i> 平板壁纸
                        </button>
                        <button id="downloadLaptopButton" class="flex-1 bg-primary hover:bg-primary/90 text-white font-medium py-3 px-6 rounded-lg transition-all duration-300 button-hover button-shadow flex items-center justify-center" aria-label="下载电脑壁纸">
                            <i class="fa-solid fa-laptop mr-2" aria-hidden="true"></i> 电脑壁纸
                        </button>
                    </div>
                    

                    
                    <!-- 微信头像和封面下载按钮 - 仅管理员可见 -->
                    <div class="mt-4 flex flex-col sm:flex-row gap-4 admin-only" style="display: none;" role="group" aria-label="管理员微信素材下载">
                        <button id="downloadAvatarButton" class="flex-1 bg-green-500 hover:bg-green-600 text-white font-medium py-3 px-6 rounded-lg transition-all duration-300 button-hover button-shadow flex items-center justify-center" aria-label="下载微信头像">
                            <i class="fa-solid fa-user-circle mr-2" aria-hidden="true"></i> 微信头像
                        </button>
                        <button id="downloadCoverButton" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white font-medium py-3 px-6 rounded-lg transition-all duration-300 button-hover button-shadow flex items-center justify-center" aria-label="下载朋友圈封面">
                            <i class="fa-solid fa-image mr-2" aria-hidden="true"></i> 朋友圈封面
                        </button>
                        <button id="downloadBothButton" class="flex-1 bg-purple-500 hover:bg-purple-600 text-white font-medium py-3 px-6 rounded-lg transition-all duration-300 button-hover button-shadow flex items-center justify-center" aria-label="下载组合素材">
                            <i class="fa-solid fa-download mr-2" aria-hidden="true"></i> 组合下载
                        </button>
                    </div>
                    <!-- 头像超清下载按钮 - 仅1元会员和永久会员可用 -->
                    <div class="mt-4 flex flex-col sm:flex-row gap-4" role="group" aria-label="会员专享头像下载">
                        <button id="downloadAvatarHDButton" class="w-full bg-green-500 hover:bg-green-600 text-white font-medium py-3 px-6 rounded-lg transition-all duration-300 button-hover button-shadow flex items-center justify-center" aria-label="下载超清头像，支持方形和圆形格式">
                            <i class="fa-solid fa-user-circle mr-2" aria-hidden="true"></i> 头像（方形/圆形）超清下载
                        </button>
                    </div>
                    <!-- 一键下载高清壁纸按钮 -->
                    <div class="mt-4 flex flex-col sm:flex-row gap-4" role="group" aria-label="一键下载功能">
                        <button id="downloadHDAllButton" class="w-full bg-gradient-to-r from-blue-500 to-green-400 hover:from-blue-600 hover:to-green-500 text-white font-bold py-3 px-6 rounded-lg transition-all duration-300 button-hover button-shadow flex items-center justify-center" aria-label="一键下载所有设备的超清壁纸">
                            <i class="fa-solid fa-cloud-arrow-down mr-2" aria-hidden="true"></i> 一键下载超清壁纸（手机/平板/电脑）
                        </button>
                    </div>
                    <!-- 剩余下载次数显示 -->
                    <div id="download-quota-display" class="mt-2 text-center text-sm text-gray-600">
                        <span>本月剩余下载次数: <span id="remaining-downloads-yulan" class="font-medium text-blue-600">-</span></span>
                    </div>
                </div>

                <!-- 信息卡片 -->
                <aside class="w-full max-w-md bg-white rounded-2xl p-6 shadow-lg" role="complementary" aria-label="使用说明和帮助信息">
                    <!-- 下载原图按钮 - 仅管理员可见 -->
                    <button id="downloadOriginalButton" class="w-full mb-4 bg-primary hover:bg-primary/90 text-white font-bold py-3 px-6 rounded-lg transition-all duration-300 button-hover button-shadow flex items-center justify-center admin-only" style="display: none;" aria-label="下载原始图片文件">
                        <i class="fa-solid fa-download mr-2" aria-hidden="true"></i> 下载原图
                    </button>
                    <h3 class="text-xl font-semibold mb-3 flex items-center">
                        <i class="fa-solid fa-info-circle text-primary mr-2" aria-hidden="true"></i> 使用说明
                    </h3>
                    <ul class="space-y-2 text-gray-600" role="list">
                        <li class="flex items-start">
                            <i class="fa-solid fa-check-circle text-primary mt-1 mr-2" aria-hidden="true"></i>
                            <span>点击"选择壁纸"按钮上传本地图片</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fa-solid fa-check-circle text-primary mt-1 mr-2" aria-hidden="true"></i>
                            <span>上传后可在左侧预览不同设备的效果</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fa-solid fa-check-circle text-primary mt-1 mr-2" aria-hidden="true"></i>
                            <span>点击对应按钮下载适配不同设备的壁纸</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fa-solid fa-check-circle text-primary mt-1 mr-2" aria-hidden="true"></i>
                            <span>支持 JPG、PNG 和 WebP 格式图片</span>
                        </li>
                    </ul>
                    

                </div>
            </div>
        </div>

        <!-- 页脚 -->
        <footer class="mt-16 text-center text-gray-500 text-sm" role="contentinfo">
            <p>© 2025 多设备壁纸预览工具 | 壁纸喵 ° 不吃鱼</p>
        </footer>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 全局权限状态
            let userPermissions = {
                hasVipAccess: false,
                isAdmin: false,
                membershipType: 'free',
                remainingDownloads: 0,
                userId: null
            };
            
            // 更新剩余下载次数显示
            function updateDownloadQuotaDisplay(membershipType, remainingDownloads) {
                const quotaDisplayEl = document.getElementById('download-quota-display');
                const remainingEl = document.getElementById('remaining-downloads-yulan');
                
                if (!quotaDisplayEl || !remainingEl) {
                    return;
                }
                
                // 根据会员类型显示不同的下载次数信息
                if (membershipType === 'permanent') {
                    remainingEl.textContent = '无限制';
                    remainingEl.className = 'font-medium text-purple-600';
                } else if (membershipType === 'monthly') {
                    remainingEl.textContent = remainingDownloads || '0';
                    remainingEl.className = 'font-medium text-blue-600';
                } else {
                    // 免费用户或未登录用户
                    quotaDisplayEl.style.display = 'none';
                    return;
                }
                
                quotaDisplayEl.style.display = 'block';
            }
            
            // 权限检查函数 - 基于localStorage用户ID
            async function checkUserPermissions() {
                try {
                    // 从localStorage获取用户信息
                    const userStr = localStorage.getItem('user');
                    if (!userStr) {
                        console.log('用户未登录，使用默认权限');
                        return userPermissions; // 返回默认的免费用户权限
                    }
                    
                    const userData = JSON.parse(userStr);
                    const userId = userData.id;
                    if (!userId) {
                        console.log('用户ID无效，使用默认权限');
                        return userPermissions;
                    }
                    
                    const response = await fetch('/api/vip/membership_status_v2.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            user_id: parseInt(userId)
                        })
                    });
                    const data = await response.json();
                    
                    if (data.success) {
                        const membershipType = data.data.membership?.type || 'free';
                        const isAdmin = data.data.is_admin || false;
                        const remainingDownloads = data.data.download?.daily_remaining || 0;
                        
                        // 更新全局权限状态
                        userPermissions = {
                            hasVipAccess: membershipType === 'monthly' || membershipType === 'permanent',
                            isAdmin: isAdmin,
                            membershipType: membershipType,
                            remainingDownloads: remainingDownloads,
                            userId: parseInt(userId) // 添加用户ID
                        };
                        
                        // 显示管理员专用功能
                        if (isAdmin) {
                            document.querySelectorAll('.admin-only').forEach(el => {
                                el.style.display = '';
                            });
                        }
                        
                        // 更新剩余下载次数显示
                        updateDownloadQuotaDisplay(membershipType, remainingDownloads);
                        
                        return userPermissions;
                    } else {
                        console.error('获取用户权限失败:', data.message);
                    }
                } catch (error) {
                    console.error('权限检查失败:', error);
                }
                return userPermissions;
            }
            
            // 检查下载权限的通用函数 - 基于localStorage用户ID
            async function checkDownloadPermission(downloadType) {
                try {
                    // 从localStorage获取用户信息
                    const userStr = localStorage.getItem('user');
                    if (!userStr) {
                        const upgradeMessage = downloadType === 'avatar_hd' 
                            ? '头像超清下载功能需要登录后使用！\n\n点击确定前往登录页面。'
                            : '一键下载超清壁纸功能需要登录后使用！\n\n点击确定前往登录页面。';
                        
                        if (confirm(upgradeMessage)) {
                            window.location.href = '/dashboard.php';
                        }
                        return false;
                    }
                    
                    const userData = JSON.parse(userStr);
                    const userId = userData.id;
                    if (!userId) {
                        alert('用户信息异常，请重新登录！');
                        return false;
                    }
                    
                    // 首先检查用户权限状态
                    if (!userPermissions || !userPermissions.userId) {
                        await checkUserPermissions();
                    }
                    
                    // 确定是否为受限内容（头像和高清壁纸都是受限内容）
                    const is_restricted = (downloadType === 'avatar_hd' || downloadType === 'hd_wallpaper');
                    
                    const response = await fetch('/api/vip/check_download_permission_v2.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            user_id: parseInt(userId),
                            is_restricted: is_restricted
                        })
                    });
                    const data = await response.json();
                    
                    if (!data.success) {
                        alert(data.message || '权限检查失败');
                        return false;
                    }
                    
                    // 检查是否有下载权限
                    if (!data.data.can_download) {
                        const reason = data.data.reason;
                        const suggestion = data.data.suggestion || '';
                        
                        if (reason === 'restricted_content') {
                            if (confirm('此功能仅限会员使用！\n\n点击确定前往个人中心升级会员，享受更多特权功能。')) {
                                window.location.href = '/dashboard.php#membership';
                            }
                        } else if (reason === 'quota_exceeded') {
                            if (confirm('您的下载次数已用完！\n\n点击确定前往个人中心升级永久会员，享受无限下载。')) {
                                window.location.href = '/dashboard.php#membership';
                            }
                        } else if (reason === 'membership_expired') {
                            if (confirm('您的会员已过期！\n\n点击确定前往个人中心重新购买会员。')) {
                                window.location.href = '/dashboard.php#membership';
                            }
                        } else if (reason === 'user_not_found') {
                            alert('用户信息异常，请重新登录！');
                            localStorage.removeItem('user');
                            window.location.href = '/dashboard.php';
                            return false;
                        } else {
                            alert(data.data.message || suggestion || '下载权限检查失败');
                        }
                        return false;
                    }
                    
                    // 更新本地权限状态
                    if (data.data.daily_remaining !== undefined) {
                        userPermissions.remainingDownloads = data.data.daily_remaining;
                    }
                    
                    return true;
                } catch (error) {
                    console.error('权限检查失败:', error);
                    alert('权限检查失败，请稍后重试！');
                    return false;
                }
            }
            
            // 记录下载行为的通用函数 - 基于localStorage用户ID
            async function recordDownload(downloadType) {
                try {
                    // 从localStorage获取用户信息
                    const userStr = localStorage.getItem('user');
                    if (!userStr) {
                        console.warn('用户未登录，跳过下载记录');
                        return;
                    }
                    
                    const userData = JSON.parse(userStr);
                    const userId = userData.id;
                    if (!userId) {
                        console.warn('用户ID无效，跳过下载记录');
                        return;
                    }
                    
                    // 确定是否为受限内容
                    const is_restricted = (downloadType === 'avatar_hd' || downloadType === 'hd_wallpaper');
                    
                    const response = await fetch('/api/vip/record_download_v2.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            user_id: parseInt(userId),
                            wallpaper_id: 1, // 默认壁纸ID
                            is_restricted: is_restricted,
                            download_url: window.location.href,
                            file_size: 0
                        })
                    });
                    const data = await response.json();
                    
                    if (data.success) {
                        // 更新剩余下载次数
                        if (userPermissions.membershipType === 'monthly' || userPermissions.membershipType === 'free') {
                            userPermissions.remainingDownloads = Math.max(0, userPermissions.remainingDownloads - 1);
                            // 实时更新下载次数显示
                            updateDownloadQuotaDisplay(userPermissions.membershipType, userPermissions.remainingDownloads);
                        }
                        console.log('下载记录成功:', downloadType, '剩余次数:', userPermissions.remainingDownloads);
                    } else {
                        console.warn('下载记录失败:', data.error);
                    }
                } catch (error) {
                    console.error('记录下载失败:', error);
                }
            }
            
            // 初始化权限检查
            checkUserPermissions();
            
            if (window.location.hash === '#previewControls') {
                const target = document.getElementById('previewControls');
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
            // 获取DOM元素
            const phoneCanvas = document.getElementById('phoneCanvas');
            const tabletCanvas = document.getElementById('tabletCanvas');
            const laptopCanvas = document.getElementById('laptopCanvas');
            
            const phoneCtx = phoneCanvas.getContext('2d');
            const tabletCtx = tabletCanvas.getContext('2d');
            const laptopCtx = laptopCanvas.getContext('2d');
            
            const wallpaperUpload = document.getElementById('wallpaperUpload');
            const uploadButton = document.getElementById('uploadButton');
            const uploadArea = document.getElementById('uploadArea');
            const previewControls = document.getElementById('previewControls');
            const previewImage = document.getElementById('previewImage');
            
            const downloadPhoneButton = document.getElementById('downloadPhoneButton');
            const downloadTabletButton = document.getElementById('downloadTabletButton');
            const downloadLaptopButton = document.getElementById('downloadLaptopButton');
            
            // 新增微信相关按钮
            const downloadAvatarButton = document.getElementById('downloadAvatarButton');
            const downloadCoverButton = document.getElementById('downloadCoverButton');
            const downloadBothButton = document.getElementById('downloadBothButton');
            
            // 微信头像和封面尺寸定义
            const avatarSize = {
                width: 300,
                height: 300,
                radius: 150,
                roundRadius: 20  // 圆角方形头像的圆角半径
            };
            
            const coverSize = {
                width: 750,
                height: 422
            };
            
            const deviceTabs = document.querySelectorAll('.device-tab');
            const devicePreviews = document.querySelectorAll('.device-preview');
            
            // 调试控制元素
            const moveUpBtn = document.getElementById('moveUpBtn');
            const moveDownBtn = document.getElementById('moveDownBtn');
            const offsetValue = document.getElementById('offsetValue');
            
            // 设备尺寸比例
            const phoneWidth = 300;
            const phoneHeight = 620;
            const phoneBezelWidth = 8; // 手机边框宽度
            const phoneCornerRadius = 40; // 手机圆角半径
            
            const tabletWidth = 420;
            const tabletHeight = 560;
            const tabletBezelWidth = 12; // 平板边框宽度
            const tabletCornerRadius = 25; // 平板圆角半径
            
            const laptopWidth = 550;
            const laptopHeight = 360;
            const laptopScreenHeight = 320;
            const laptopBezelWidth = 8; // 电脑边框宽度
            const laptopBaseHeight = 40; // 电脑底座高度
            
            // 朋友圈封面尺寸
            const momentWidth = 550;
            const momentHeight = 372;
            const momentBarHeight = 100; // 底部白色栏高度
            const momentAvatarSize = 90;
            const momentAvatarRadius = 15;
            const momentAvatarMargin = 15;
            const momentName = 'Jelis';
            let momentAvatarImg = null;
            let cameraIconImg = null;
            
            // 加载相机图标
            const loadCameraIcon = () => {
                cameraIconImg = new Image();
                cameraIconImg.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIiIGhlaWdodD0iMzIiIHZpZXdCb3g9IjAgMCAzMiAzMiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTI2IDhIMjJMMjAgNkgxMkwxMCA4SDZDNC45IDggNCA4LjkgNCA5VjI1QzQgMjYuMSA0LjkgMjcgNiAyN0gyNkMyNy4xIDI3IDI4IDI2LjEgMjggMjVWMTBDMjggOC45IDI3LjEgOCAyNiA4WiIgZmlsbD0iIzMzMzMzMyIvPgo8Y2lyY2xlIGN4PSIxNiIgY3k9IjE3IiByPSI1IiBmaWxsPSIjMzMzMzMzIi8+Cjwvc3ZnPgo=';
                cameraIconImg.onerror = () => {
                    console.warn('Camera icon failed to load');
                    cameraIconImg = null;
                };
            };
            
            // 初始化时加载相机图标
            loadCameraIcon();
            
            // 壁纸位置偏移量
            let yOffset = 100;
            
            // 适配高DPI屏幕
            const dpr = window.devicePixelRatio || 1;
            function setCanvasHD(canvas, width, height) {
                canvas.width = width * dpr;
                canvas.height = height * dpr;
                canvas.style.width = width + 'px';
                canvas.style.height = height + 'px';
                const ctx = canvas.getContext('2d');
                ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
                return ctx;
            }
            // 设置所有canvas为高清
            setCanvasHD(phoneCanvas, phoneWidth, phoneHeight);
            setCanvasHD(tabletCanvas, tabletWidth, tabletHeight);
            setCanvasHD(laptopCanvas, laptopWidth, laptopHeight + laptopBaseHeight);
            setCanvasHD(momentCanvas, momentWidth, momentHeight);
            // 修复朋友圈canvas context丢失
            momentCtx = momentCanvas.getContext('2d');
            
            // 壁纸文件名数组（如有新图片请手动添加到此数组）
            const wallpapers = [
                { filename: '机械少女2.jpeg', path: 'static/wallpapers/机械少女2.jpeg' },
                { filename: '机械少女.jpeg', path: 'static/wallpapers/机械少女.jpeg' },
                { filename: '血色残阳.jpeg', path: 'static/wallpapers/血色残阳.jpeg' },
                { filename: '神秘人.png', path: 'static/wallpapers/神秘人.png' },
                { filename: '兽耳娘.png', path: 'static/wallpapers/兽耳娘.png' }
            ];

            // 获取URL参数中的图片
            const urlParams = new URLSearchParams(window.location.search);
            let imageUrl = urlParams.get('image');

            // 修复：图片URL处理逻辑，支持从预览图路径转换为原图路径
            if (imageUrl) {
                // 检查是否为预览图路径，如果是则转换为原图路径
                let originalImageUrl = imageUrl;
                
                // 如果传入的是预览图路径（static/preview/001/），转换为原图路径（static/wallpapers/001/）
                if (imageUrl.includes('static/preview/')) {
                    originalImageUrl = imageUrl.replace('static/preview/', 'static/wallpapers/');
                    console.log('预览图路径转换为原图路径:', imageUrl, '->', originalImageUrl);
                }
                
                // 加载原图
                loadAndDisplayWallpaper(originalImageUrl);
            } else {
                // 如果没有图片URL，不加载任何图片，等待用户上传
                console.log('没有指定图片URL，请上传。');
            }

            // 设备切换
            deviceTabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    // 移除所有活动状态
                    deviceTabs.forEach(t => t.classList.remove('active'));
                    devicePreviews.forEach(p => p.classList.add('hidden'));
                    
                    // 添加当前活动状态
                    tab.classList.add('active');
                    const device = tab.getAttribute('data-device');
                    document.getElementById(`${device}Preview`).classList.remove('hidden');
                    
                    // 切换到朋友圈时重绘
                    if(device === 'moment') {
                        drawMomentPreview(currentWallpaper, currentWallpaper || momentAvatarImg, yOffset);
                    } else if (device === 'phone') {
                        drawPhone(currentWallpaper);
                    } else if (device === 'tablet') {
                        drawTablet(currentWallpaper);
                    } else if (device === 'laptop') {
                        drawLaptop(currentWallpaper);
                    }
                });
            });
            
            // 上传按钮点击事件
            uploadButton.addEventListener('click', () => {
                wallpaperUpload.click();
            });
            
            // 文件上传事件
            wallpaperUpload.addEventListener('change', handleFileUpload);
            
            // 拖拽上传
            uploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadArea.classList.add('border-primary');
                uploadArea.classList.add('bg-gray-50');
            });
            
            uploadArea.addEventListener('dragleave', () => {
                uploadArea.classList.remove('border-primary');
                uploadArea.classList.remove('bg-gray-50');
            });
            
            uploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadArea.classList.remove('border-primary');
                uploadArea.classList.remove('bg-gray-50');
                
                if (e.dataTransfer.files.length) {
                    wallpaperUpload.files = e.dataTransfer.files;
                    handleFileUpload();
                }
            });
            
            // 下载按钮点击事件
            downloadPhoneButton.addEventListener('click', () => downloadPreview(phoneCanvas, '手机', 'phone'));
            downloadTabletButton.addEventListener('click', () => downloadPreview(tabletCanvas, '平板', 'tablet'));
            downloadLaptopButton.addEventListener('click', () => downloadPreview(laptopCanvas, '电脑', 'laptop'));
            
            // 新增微信相关下载事件
            downloadAvatarButton.addEventListener('click', downloadAvatar);
            downloadCoverButton.addEventListener('click', downloadCover);
            downloadBothButton.addEventListener('click', downloadBoth);
            
            // 一键下载高清壁纸按钮
            const downloadHDAllButton = document.getElementById('downloadHDAllButton');
            downloadHDAllButton.addEventListener('click', async function() {
                // 检查下载权限
                const hasPermission = await checkDownloadPermission('hd_wallpaper');
                if (!hasPermission) {
                    return;
                }
                
                // 直接使用已经加载到 currentWallpaper 的原图对象
                if (!currentWallpaper || !currentWallpaper.complete || currentWallpaper.naturalWidth === 0) {
                    alert('壁纸未加载或加载异常，无法导出高清壁纸！');
                    // 尝试恢复按钮状态
                    downloadHDAllButton.innerHTML = '<i class="fa-solid fa-times mr-2"></i> 导出失败';
                     downloadHDAllButton.classList.remove('bg-gradient-to-r', 'from-blue-500', 'to-green-400', 'hover:from-blue-600', 'hover:to-green-500');
                     downloadHDAllButton.classList.add('bg-red-500');
                     setTimeout(() => {
                         downloadHDAllButton.classList.remove('bg-red-500');
                         downloadHDAllButton.classList.add('bg-gradient-to-r', 'from-blue-500', 'to-green-400', 'hover:from-blue-600', 'hover:to-green-500');
                         downloadHDAllButton.disabled = false;
                     }, 2000);
                    return;
                }
                downloadHDAllButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> 下载中...';
                downloadHDAllButton.disabled = true;
                let exportComplete = false; // 标记导出是否完成

                // 高清导出函数，直接使用 currentWallpaper 对象
                const performHDExport = async (img) => { // 修改为异步函数
                    // 手机 - 提高分辨率到2K
                     const phoneSuccess = await exportHDWallpaper(img, 1440, 3200, '手机高清壁纸.jpg');
                    // 平板 - 提高分辨率到4K
                     const tabletSuccess = await exportHDWallpaper(img, 2560, 3840, '平板高清壁纸.jpg');
                    // 电脑 - 提高分辨率到4K，内容区yOffset等比例缩放（与预览一致，无补偿）
                    /**
                     * 电脑壁纸导出时，yOffset等比例缩放，保证与预览一致
                     * 预览内容区高度为：laptopScreenHeight - 3 * laptopBezelWidth
                     * 导出内容区高度为：2160
                     */
                    const previewContentHeight = laptopScreenHeight - 3 * laptopBezelWidth; // 296
                    const exportContentHeight = 2160;
                    const scaledYOffset = yOffset * (exportContentHeight / previewContentHeight); // 仅等比例缩放
                     const laptopSuccess = await exportHDWallpaper(img, 3840, 2160, '电脑高清壁纸.jpg', scaledYOffset);

                     // 检查所有导出是否成功
                     if (phoneSuccess && tabletSuccess && laptopSuccess) {
                    // 记录下载
                    await recordDownload('hd_wallpaper');
                    
                    // 标记导出完成，更新按钮状态
                    downloadHDAllButton.innerHTML = '<i class="fa-solid fa-check mr-2"></i> 下载成功';
                    downloadHDAllButton.classList.remove('bg-gradient-to-r', 'from-blue-500', 'to-green-400', 'hover:from-blue-600', 'hover:to-green-500');
                    downloadHDAllButton.classList.add('bg-green-500');
                    exportComplete = true;
                    // 延迟恢复按钮状态
                     setTimeout(() => {
                         downloadHDAllButton.classList.remove('bg-green-500');
                         downloadHDAllButton.classList.add('bg-gradient-to-r', 'from-blue-500', 'to-green-400', 'hover:from-blue-600', 'hover:to-green-500');
                         downloadHDAllButton.disabled = false;
                    }, 2000);
                     } else {
                          // 任何一个导出失败，显示失败状态
                           downloadHDAllButton.innerHTML = '<i class="fa-solid fa-times mr-2"></i> 导出失败';
                          downloadHDAllButton.classList.remove('bg-gradient-to-r', 'from-blue-500', 'to-green-400', 'hover:from-blue-600', 'hover:to-green-500');
                          downloadHDAllButton.classList.add('bg-red-500');
                          setTimeout(() => {
                              downloadHDAllButton.classList.remove('bg-red-500');
                              downloadHDAllButton.classList.add('bg-gradient-to-r', 'from-blue-500', 'to-green-400', 'hover:from-blue-600', 'hover:to-green-500');
                              downloadHDAllButton.disabled = false;
                         }, 2000);
                     }
                };

                // 直接使用 currentWallpaper 进行导出
                 performHDExport(currentWallpaper).then(() => {
                      // Promise resolved，操作完成，按钮状态已在 performHDExport 中处理
                 }).catch((error) => {
                      console.error('一键下载高清壁纸过程中发生错误:', error);
                      // 确保在发生未捕获的错误时也更新按钮状态
                       downloadHDAllButton.innerHTML = '<i class="fa-solid fa-times mr-2"></i> 导出失败';
                       downloadHDAllButton.classList.remove('bg-gradient-to-r', 'from-blue-500', 'to-green-400', 'hover:from-blue-600', 'hover:to-green-500');
                       downloadHDAllButton.classList.add('bg-red-500');
                       setTimeout(() => {
                           downloadHDAllButton.classList.remove('bg-red-500');
                           downloadHDAllButton.classList.add('bg-gradient-to-r', 'from-blue-500', 'to-green-400', 'hover:from-blue-600', 'hover:to-green-500');
                           downloadHDAllButton.disabled = false;
                      }, 2000);
                 });
            });

            /**
             * 导出高清壁纸（等比例居中裁剪，电脑壁纸支持yOffset）
             * @param {HTMLImageElement} img - 原始壁纸图片
             * @param {number} targetWidth - 导出宽度
             * @param {number} targetHeight - 导出高度
             * @param {string} filename - 文件名
             * @param {number} [yOffset=0] - 仅电脑壁纸用，壁纸上下偏移
             * @returns {Promise<boolean>} - 返回一个Promise，表示导出是否成功
             */
            async function exportHDWallpaper(img, targetWidth, targetHeight, filename, yOffset = 0) {
                 console.log(`尝试导出高清壁纸: ${filename}`);
                const canvas = document.createElement('canvas');
                canvas.width = targetWidth;
                canvas.height = targetHeight;
                const ctx = canvas.getContext('2d');
                // 设置图像平滑
                ctx.imageSmoothingEnabled = true;
                ctx.imageSmoothingQuality = 'high';
                
                // 电脑壁纸导出时，内容区绘制逻辑与预览一致，防止顶部黑边
                if (targetWidth === 3840 && targetHeight === 2160) {
                    const imageRatio = img.width / img.height;
                    const screenRatio = targetWidth / targetHeight;
                    let drawWidth, drawHeight, drawX, drawY;
                    if (imageRatio > screenRatio) {
                        drawHeight = targetHeight;
                        drawWidth = drawHeight * imageRatio;
                        drawX = -(drawWidth - targetWidth) / 2;
                        drawY = - (drawHeight - targetHeight) / 2 + yOffset;
                    } else {
                        drawWidth = targetWidth;
                        drawHeight = drawWidth / imageRatio;
                        drawX = 0;
                        drawY = -(drawHeight - targetHeight) / 2 + yOffset;
                    }
                    ctx.drawImage(img, drawX, drawY, drawWidth, drawHeight);
                } else {
                    // 手机和平板壁纸导出，强制忽略yOffset，始终居中
                    const imgRatio = img.width / img.height;
                    const targetRatio = targetWidth / targetHeight;
                    let drawWidth, drawHeight, drawX, drawY;
                    if (imgRatio > targetRatio) {
                        drawHeight = targetHeight;
                        drawWidth = drawHeight * imgRatio;
                        drawX = -(drawWidth - targetWidth) / 2;
                        drawY = 0; // 不用yOffset
                    } else {
                        drawWidth = targetWidth;
                        drawHeight = drawWidth / imgRatio;
                        drawX = 0;
                        drawY = -(drawHeight - targetHeight) / 2; // 不用yOffset
                    }
                    ctx.drawImage(img, drawX, drawY, drawWidth, drawHeight);
                }

                // 改用 canvas.toBlob 导出
                return new Promise((resolve) => {
                     canvas.toBlob(function(blob) {
                         if (!blob || blob.size === 0) {
                             console.error('高清壁纸导出失败: 无法创建Blob或Blob为空。', blob);
                             alert(`导出高清壁纸失败: ${filename} 无法创建图片数据或数据为空。`);
                             resolve(false);
                             return;
                         }

                         console.log(`成功创建高清Blob，大小: ${blob.size} 字节，类型: ${blob.type}`);
                         const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.download = filename;
                         link.href = url;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                         // 延迟释放URL对象
                         setTimeout(() => {
                             URL.revokeObjectURL(url);
                              console.log('释放高清Blob URL:', url);
                         }, 100);

                         resolve(true);

                     }, 'image/jpeg', 0.95); // 高清导出默认使用JPEG，质量0.95
                });
            }
            
            // 处理文件上传
            function handleFileUpload() {
                const file = wallpaperUpload.files[0];
                if (!file) return;
                const reader = new FileReader();
                reader.onload = function(e) {
                    // 使用统一的加载函数处理本地上传的图片
                    loadAndDisplayWallpaper(e.target.result); // e.target.result 是blob: URL
                };
                reader.readAsDataURL(file);
            }
            
            // 绘制手机
            function drawPhone(wallpaper) {
                // 清除画布
                phoneCtx.clearRect(0, 0, phoneWidth, phoneHeight);
                
                // 绘制手机外壳（边框）
                phoneCtx.fillStyle = '#1C1C1E'; // 深空灰色
                roundRect(phoneCtx, 0, 0, phoneWidth, phoneHeight, phoneCornerRadius);
                phoneCtx.fill();
                
                // 绘制手机正面屏幕区域
                phoneCtx.fillStyle = '#000';
                roundRect(phoneCtx, phoneBezelWidth, phoneBezelWidth, phoneWidth - 2 * phoneBezelWidth, phoneHeight - 2 * phoneBezelWidth, phoneCornerRadius - 8);
                phoneCtx.fill();
                
                // 绘制屏幕内容（如果有壁纸则显示壁纸）
                if (wallpaper) {
                    // 计算壁纸缩放比例以适应屏幕并应用圆角
                    drawRoundedImage(phoneCtx, wallpaper, phoneBezelWidth * 1.5, phoneBezelWidth * 1.5, 
                                    phoneWidth - 3 * phoneBezelWidth, phoneHeight - 3 * phoneBezelWidth, 
                                    phoneCornerRadius - 10);
                } else {
                    // 没有壁纸时显示默认背景
                    phoneCtx.fillStyle = '#F9F9F9';
                    roundRect(phoneCtx, phoneBezelWidth * 1.5, phoneBezelWidth * 1.5, phoneWidth - 3 * phoneBezelWidth, phoneHeight - 3 * phoneBezelWidth, phoneCornerRadius - 10);
                    phoneCtx.fill();
                }
            }
            
            // 绘制平板
            function drawTablet(wallpaper) {
                // 清除画布
                tabletCtx.clearRect(0, 0, tabletWidth, tabletHeight);
                
                // 绘制平板外壳（边框）
                tabletCtx.fillStyle = '#1C1C1E'; // 深空灰色
                roundRect(tabletCtx, 0, 0, tabletWidth, tabletHeight, tabletCornerRadius);
                tabletCtx.fill();
                
                // 绘制平板正面屏幕区域
                tabletCtx.fillStyle = '#000';
                roundRect(tabletCtx, tabletBezelWidth, tabletBezelWidth, tabletWidth - 2 * tabletBezelWidth, tabletHeight - 2 * tabletBezelWidth, tabletCornerRadius - 5);
                tabletCtx.fill();
                
                // 绘制屏幕内容（如果有壁纸则显示壁纸）
                if (wallpaper) {
                    // 计算壁纸缩放比例以适应屏幕并应用圆角
                    drawRoundedImage(tabletCtx, wallpaper, tabletBezelWidth * 1.5, tabletBezelWidth * 1.5, 
                                    tabletWidth - 3 * tabletBezelWidth, tabletHeight - 3 * tabletBezelWidth, 
                                    tabletCornerRadius - 8);
                } else {
                    // 没有壁纸时显示默认背景
                    tabletCtx.fillStyle = '#F9F9F9';
                    roundRect(tabletCtx, tabletBezelWidth * 1.5, tabletBezelWidth * 1.5, tabletWidth - 3 * tabletBezelWidth, tabletHeight - 3 * tabletBezelWidth, tabletCornerRadius - 8);
                    tabletCtx.fill();
                }
            }
            
            // 绘制电脑
            function drawLaptop(wallpaper) {
                // 清除画布
                laptopCtx.clearRect(0, 0, laptopWidth, laptopHeight + laptopBaseHeight);
                
                // 绘制电脑屏幕边框
                laptopCtx.fillStyle = '#1C1C1E'; // 深空灰色
                roundRect(laptopCtx, 0, 0, laptopWidth, laptopHeight, 10);
                laptopCtx.fill();
                
                // 绘制电脑屏幕区域
                laptopCtx.fillStyle = '#000';
                roundRect(laptopCtx, laptopBezelWidth, laptopBezelWidth, laptopWidth - 2 * laptopBezelWidth, laptopScreenHeight - 2 * laptopBezelWidth, 5);
                laptopCtx.fill();
                
                // 绘制屏幕内容（如果有壁纸则显示壁纸）
                if (wallpaper) {
                    const screenWidth = laptopWidth - 3 * laptopBezelWidth;
                    const screenHeight = laptopScreenHeight - 3 * laptopBezelWidth;
                    const screenX = laptopBezelWidth * 1.5;
                    const screenY = laptopBezelWidth * 1.5;
                    drawWallpaperContentArea(laptopCtx, wallpaper, screenX, screenY, screenWidth, screenHeight, yOffset);
                } else {
                    // 没有壁纸时显示默认背景
                    laptopCtx.fillStyle = '#F9F9F9';
                    roundRect(laptopCtx, laptopBezelWidth * 1.5, laptopBezelWidth * 1.5, laptopWidth - 3 * laptopBezelWidth, laptopScreenHeight - 3 * laptopBezelWidth, 5);
                    laptopCtx.fill();
                }
                
                // 绘制电脑底座
                laptopCtx.fillStyle = '#2A2A2A';
                laptopCtx.fillRect(0, laptopHeight, laptopWidth, laptopBaseHeight);
                
                // 绘制底座凹槽
                laptopCtx.fillStyle = '#1A1A1A';
                laptopCtx.beginPath();
                laptopCtx.moveTo(laptopWidth * 0.2, laptopHeight);
                laptopCtx.bezierCurveTo(laptopWidth * 0.5, laptopHeight - 8, laptopWidth * 0.8, laptopHeight, laptopWidth * 0.8, laptopHeight);
                laptopCtx.lineTo(laptopWidth * 0.2, laptopHeight);
                laptopCtx.closePath();
                laptopCtx.fill();
            }
            
            // 绘制带圆角的图像
            function drawRoundedImage(ctx, img, x, y, width, height, radius) {
                ctx.save();
                ctx.beginPath();
                ctx.moveTo(x + radius, y);
                ctx.lineTo(x + width - radius, y);
                ctx.arcTo(x + width, y, x + width, y + radius, radius);
                ctx.lineTo(x + width, y + height - radius);
                ctx.arcTo(x + width, y + height, x + width - radius, y + height, radius);
                ctx.lineTo(x + radius, y + height);
                ctx.arcTo(x, y + height, x, y + height - radius, radius);
                ctx.lineTo(x, y + radius);
                ctx.arcTo(x, y, x + radius, y, radius);
                ctx.closePath();
                ctx.clip();
                
                // 计算壁纸缩放比例以适应屏幕
                const imageRatio = img.width / img.height;
                const screenRatio = width / height;
                
                let drawWidth, drawHeight, drawX, drawY;
                
                if (imageRatio > screenRatio) {
                    // 图片比屏幕宽，等比例缩放高度
                    drawHeight = height;
                    drawWidth = drawHeight * imageRatio;
                    drawX = x - (drawWidth - width) / 2;
                    drawY = y;
                } else {
                    // 图片比屏幕高，等比例缩放宽度
                    drawWidth = width;
                    drawHeight = drawWidth / imageRatio;
                    // 居中显示图片
                    drawX = x;
                    drawY = y - (drawHeight - height) / 2;
                }
                
                ctx.drawImage(img, drawX, drawY, drawWidth, drawHeight);
                ctx.restore();
            }
            
            // 绘制圆角矩形
            function roundRect(ctx, x, y, width, height, radius) {
                ctx.beginPath();
                ctx.moveTo(x + radius, y);
                ctx.lineTo(x + width - radius, y);
                ctx.arcTo(x + width, y, x + width, y + radius, radius);
                ctx.lineTo(x + width, y + height - radius);
                ctx.arcTo(x + width, y + height, x + width - radius, y + height, radius);
                ctx.lineTo(x + radius, y + height);
                ctx.arcTo(x, y + height, x, y + height - radius, radius);
                ctx.lineTo(x, y + radius);
                ctx.arcTo(x, y, x + radius, y, radius);
                ctx.closePath();
            }
            
            // 绘制手机状态栏
            function drawPhoneStatusBar() {
                phoneCtx.fillStyle = 'rgba(0, 0, 0, 0.2)';
                phoneCtx.fillRect(phoneBezelWidth * 1.5, phoneBezelWidth * 1.5, phoneWidth - 3 * phoneBezelWidth, 30);
                
                // 绘制时间
                phoneCtx.fillStyle = '#FFF';
                phoneCtx.font = 'bold 14px Inter';
                phoneCtx.textAlign = 'center';
                phoneCtx.fillText('9:41', phoneWidth / 2, phoneBezelWidth * 1.5 + 20);
                
                // 绘制信号、电池等图标（简化版）
                phoneCtx.fillStyle = '#FFF';
                phoneCtx.font = '12px Font Awesome 6 Free';
                phoneCtx.textAlign = 'right';
                phoneCtx.fillText('\uf1eb \uf2f1 \uf240', phoneWidth - phoneBezelWidth * 2, phoneBezelWidth * 1.5 + 20);
            }
            
            // 绘制手机底部指示条
            function drawPhoneHomeIndicator() {
                phoneCtx.fillStyle = 'rgba(255, 255, 255, 0.8)';
                phoneCtx.beginPath();
                phoneCtx.roundRect(phoneWidth / 2 - 60, phoneHeight - phoneBezelWidth * 3, 120, 5, 2.5);
                phoneCtx.fill();
            }
            
            // 绘制平板状态栏
            function drawTabletStatusBar() {
                tabletCtx.fillStyle = 'rgba(0, 0, 0, 0.2)';
                tabletCtx.fillRect(tabletBezelWidth * 1.5, tabletBezelWidth * 1.5, tabletWidth - 3 * tabletBezelWidth, 40);
                
                // 绘制时间
                tabletCtx.fillStyle = '#FFF';
                tabletCtx.font = 'bold 16px Inter';
                tabletCtx.textAlign = 'center';
                tabletCtx.fillText('9:41', tabletWidth / 2, tabletBezelWidth * 1.5 + 25);
                
                // 绘制信号、电池等图标（简化版）
                tabletCtx.fillStyle = '#FFF';
                tabletCtx.font = '14px Font Awesome 6 Free';
                tabletCtx.textAlign = 'right';
                tabletCtx.fillText('\uf1eb \uf2f1 \uf240', tabletWidth - tabletBezelWidth * 2, tabletBezelWidth * 1.5 + 25);
            }
            
            // 绘制电脑任务栏
            function drawLaptopTaskbar() {
                const taskbarHeight = 40;
                laptopCtx.fillStyle = 'rgba(0, 0, 0, 0.8)';
                laptopCtx.fillRect(laptopBezelWidth * 1.5, laptopScreenHeight - taskbarHeight - laptopBezelWidth * 1.5, 
                                  laptopWidth - 3 * laptopBezelWidth, taskbarHeight);
                
                // 绘制开始按钮
                laptopCtx.fillStyle = '#165DFF';
                laptopCtx.beginPath();
                laptopCtx.arc(laptopBezelWidth * 3, laptopScreenHeight - taskbarHeight/2 - laptopBezelWidth * 1.5, 15, 0, Math.PI * 2);
                laptopCtx.fill();
                
                // 绘制开始按钮图标
                laptopCtx.fillStyle = '#FFF';
                laptopCtx.font = '20px Font Awesome 6 Free';
                laptopCtx.textAlign = 'center';
                laptopCtx.fillText('\uf11b', laptopBezelWidth * 3, laptopScreenHeight - taskbarHeight/2 - laptopBezelWidth * 1.5 + 7);
                
                // 绘制任务栏图标
                const taskbarIcons = [
                    '\uf1c9', '\uf0c3', '\uf0e0', '\uf17a'
                ];
                
                taskbarIcons.forEach((icon, index) => {
                    laptopCtx.fillStyle = '#FFF';
                    laptopCtx.font = '20px Font Awesome 6 Free';
                    laptopCtx.textAlign = 'center';
                    laptopCtx.fillText(icon, laptopBezelWidth * 6 + index * 50, laptopScreenHeight - taskbarHeight/2 - laptopBezelWidth * 1.5 + 7);
                });
                
                // 绘制系统托盘
                laptopCtx.fillStyle = '#FFF';
                laptopCtx.font = '14px Font Awesome 6 Free';
                laptopCtx.textAlign = 'right';
                laptopCtx.fillText('\uf028 \uf1eb \uf240 9:41', laptopWidth - laptopBezelWidth * 3, laptopScreenHeight - taskbarHeight/2 - laptopBezelWidth * 1.5 + 5);
            }
            
            // 绘制内容区壁纸（预览和导出都用这个）
            function drawWallpaperContentArea(ctx, img, x, y, w, h, yOffset = 0) {
                const imageRatio = img.width / img.height;
                const screenRatio = w / h;
                let drawWidth, drawHeight, drawX, drawY;
                if (imageRatio > screenRatio) {
                    drawHeight = h;
                    drawWidth = drawHeight * imageRatio;
                    drawX = x - (drawWidth - w) / 2;
                    drawY = y + yOffset;
                } else {
                    drawWidth = w;
                    drawHeight = drawWidth / imageRatio;
                    drawX = x;
                    drawY = y - (drawHeight - h) / 2 + yOffset;
                }
                ctx.drawImage(img, drawX, drawY, drawWidth, drawHeight);
            }
            
            // 下载预览图
            async function downloadPreview(canvas, filename, deviceType) {
                // 检查壁纸是否加载完成
                if (!currentWallpaper || !currentWallpaper.complete || currentWallpaper.naturalWidth === 0) {
                    alert('壁纸未加载完成，无法下载！');
                    return;
                }
                let button;
                switch(filename) {
                    case '手机':
                        button = downloadPhoneButton;
                        break;
                    case '平板':
                        button = downloadTabletButton;
                        break;
                    case '电脑':
                        button = downloadLaptopButton;
                        break;
                    default:
                        return;
                }
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> 下载中...';
                button.disabled = true;
                try {
                    // 只下载"带外观的壁纸"
                    if (canvas === phoneCanvas) drawPhone(currentWallpaper);
                    if (canvas === tabletCanvas) drawTablet(currentWallpaper);
                    if (canvas === laptopCanvas) drawLaptop(currentWallpaper);
                    
                    // 调用 exportCanvasAsImage 并等待结果
                    const downloadSuccess = await exportCanvasAsImage(canvas, filename + '.png');

                    if (downloadSuccess) {
                         // 更新按钮状态为下载成功
                        button.innerHTML = '<i class="fa-solid fa-check mr-2"></i> 下载成功';
                        button.classList.remove('bg-accent', 'bg-secondary', 'bg-primary');
                        button.classList.add('bg-green-500');
                        setTimeout(() => {
                            button.innerHTML = originalText;
                            button.classList.remove('bg-green-500');
                            if (filename === '手机') {
                                button.classList.add('bg-accent');
                            } else if (filename === '平板') {
                                button.classList.add('bg-secondary');
                            } else {
                                button.classList.add('bg-primary');
                            }
                            button.disabled = false;
                        }, 2000);
                    } else {
                        // 更新按钮状态为下载失败
                         button.innerHTML = '<i class="fa-solid fa-times mr-2"></i> 下载失败';
                    button.classList.remove('bg-accent', 'bg-secondary', 'bg-primary');
                         button.classList.add('bg-red-500');
                    setTimeout(() => {
                        button.innerHTML = originalText;
                             button.classList.remove('bg-red-500');
                        if (filename === '手机') {
                            button.classList.add('bg-accent');
                        } else if (filename === '平板') {
                            button.classList.add('bg-secondary');
                        } else {
                            button.classList.add('bg-primary');
                        }
                        button.disabled = false;
                    }, 2000);
                    }

                } catch (error) {
                    console.error('下载失败:', error);
                    // 更新按钮状态为下载失败
                    button.innerHTML = '<i class="fa-solid fa-times mr-2"></i> 下载失败';
                    button.classList.remove('bg-accent', 'bg-secondary', 'bg-primary');
                    button.classList.add('bg-red-500');
                    setTimeout(() => {
                        button.innerHTML = originalText;
                        button.classList.remove('bg-red-500');
                        if (filename === '手机') {
                            button.classList.add('bg-accent');
                        } else if (filename === '平板') {
                            button.classList.add('bg-secondary');
                        } else {
                            button.classList.add('bg-primary');
                        }
                        button.disabled = false;
                    }, 2000);
                }
            }

            // 导出canvas为图片
            /**
             * 导出canvas为图片文件
             * @param {HTMLCanvasElement} canvas - 要导出的Canvas元素
             * @param {string} filename - 下载文件的名称
             * @param {string} [format='image/png'] - 输出的图片格式 (e.g., 'image/jpeg', 'image/png')，默认为 image/png
             * @param {number} [quality=1.0] - 输出图片的质量 (仅适用于支持有损压缩的格式如 JPEG, 0.0 到 1.0)，默认为 1.0
             * @returns {Promise<boolean>} - 返回一个Promise，表示导出是否成功
             */
            async function exportCanvasAsImage(canvas, filename, format = 'image/png', quality = 1.0) {
                console.log(`尝试导出文件: ${filename}, 格式: ${format}, 质量: ${quality}`);
                return new Promise((resolve) => {
                    canvas.toBlob(function(blob) {
                        if (!blob || blob.size === 0) {
                            console.error('Canvas导出失败: 无法创建Blob或Blob为空。', blob);
                            alert('导出失败：无法创建图片数据或数据为空。');
                            resolve(false); // 导出失败
                    return;
                }
               
                        console.log(`成功创建Blob，大小: ${blob.size} 字节，类型: ${blob.type}`);

                        const url = URL.createObjectURL(blob);
                        console.log('创建Blob URL:', url);
                        const link = document.createElement('a');
                link.download = filename;
                        link.href = url;
                        
                        // 尝试触发下载
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                        // 延迟释放URL对象，给浏览器一点时间处理下载
                        setTimeout(() => {
                            URL.revokeObjectURL(url);
                            console.log('释放Blob URL:', url);
                        }, 100); // 延迟100ms释放

                        resolve(true); // 假定点击链接会触发下载，标记导出成功

                    }, format, quality);
                });
            }

            // 添加壁纸位置调整功能
            moveUpBtn.addEventListener('click', function(e) {
                e.preventDefault(); // 阻止默认行为
                if (yOffset > 0) {
                    yOffset -= 10;
                    updateOffsetDisplay();
                    if (currentWallpaper) {
                        // 更新电脑壁纸位置
                        drawLaptop(currentWallpaper);
                        // 更新预览图位置
                        previewImage.style.objectPosition = `center -${yOffset}px`;
                        // 更新朋友圈预览
                        drawMomentPreview(currentWallpaper, currentWallpaper || momentAvatarImg, yOffset);
                    }
                }
            });
            
            moveDownBtn.addEventListener('click', function(e) {
                e.preventDefault(); // 阻止默认行为
                yOffset += 10;
                updateOffsetDisplay();
                if (currentWallpaper) {
                    // 更新电脑壁纸位置
                    drawLaptop(currentWallpaper);
                    // 更新预览图位置
                    previewImage.style.objectPosition = `center -${yOffset}px`;
                    // 更新朋友圈预览
                    drawMomentPreview(currentWallpaper, currentWallpaper || momentAvatarImg, yOffset);
                }
            });

            // 添加触摸事件支持
            moveUpBtn.addEventListener('touchstart', function(e) {
                e.preventDefault(); // 阻止默认行为
                if (yOffset > 0) {
                    yOffset -= 10;
                    updateOffsetDisplay();
                    if (currentWallpaper) {
                        // 更新电脑壁纸位置
                        drawLaptop(currentWallpaper);
                        // 更新预览图位置
                        previewImage.style.objectPosition = `center -${yOffset}px`;
                        // 更新朋友圈预览
                        drawMomentPreview(currentWallpaper, currentWallpaper || momentAvatarImg, yOffset);
                    }
                }
            }, { passive: false });
            
            moveDownBtn.addEventListener('touchstart', function(e) {
                e.preventDefault(); // 阻止默认行为
                yOffset += 10;
                updateOffsetDisplay();
                if (currentWallpaper) {
                    // 更新电脑壁纸位置
                    drawLaptop(currentWallpaper);
                    // 更新预览图位置
                    previewImage.style.objectPosition = `center -${yOffset}px`;
                    // 更新朋友圈预览
                    drawMomentPreview(currentWallpaper, currentWallpaper || momentAvatarImg, yOffset);
                }
            }, { passive: false });
            
            function updateOffsetDisplay() {
                offsetValue.textContent = `偏移: ${yOffset}px`;
            }

            // 绘制微信头像
            /**
             * 绘制微信头像
             * @param {CanvasRenderingContext2D} ctx - Canvas上下文
             * @param {HTMLImageElement} img - 壁纸图片
             * @param {boolean} [isRound=true] - 是否绘制圆形头像
             * @param {number} [yOffset=0] - 壁纸上下偏移量
             * @param {number} [compensationOffset=0] - 额外的上下偏移补偿
             */
            function drawAvatar(ctx, img, isRound = true, yOffset = 0, compensationOffset = 0) {
                // 获取当前Canvas的实际尺寸
                const canvasWidth = ctx.canvas.width;
                const canvasHeight = ctx.canvas.height;
                
                // 清除画布，使用Canvas的实际尺寸
                ctx.clearRect(0, 0, canvasWidth, canvasHeight);
                
                ctx.save(); // 保存当前状态，以便绘制和裁剪后恢复
                
                if (isRound) {
                    // 创建圆形裁剪区域，使用Canvas的实际尺寸计算中心和半径
                    const radius = Math.min(canvasWidth, canvasHeight) / 2;
                    ctx.beginPath();
                    ctx.arc(canvasWidth / 2, canvasHeight / 2, radius, 0, Math.PI * 2);
                    ctx.clip();
                } else {
                    // 创建圆角矩形裁剪区域，使用Canvas的实际尺寸和等比例缩放的圆角半径
                    const targetRadius = avatarSize.roundRadius * (canvasWidth / avatarSize.width); // 圆角半径也等比例缩放
                    ctx.beginPath();
                    roundRect(ctx, 0, 0, canvasWidth, canvasHeight, targetRadius);
                    ctx.clip();
                }
                
                // 计算图片缩放和位置，使其居中填充整个Canvas区域
                const scale = Math.max(
                    canvasWidth / img.width,
                    canvasHeight / img.height
                );
                
                const x = (canvasWidth - img.width * scale) / 2;
                const y = (canvasHeight - img.height * scale) / 2 + yOffset + compensationOffset; // 应用yOffset和补偿偏移量
                
                ctx.drawImage(img, x, y, img.width * scale, img.height * scale);
                
                ctx.restore(); // 恢复到裁剪之前的状态
            }
            
            // 下载微信头像
            async function downloadAvatar() {
                if (!currentWallpaper || !currentWallpaper.complete || currentWallpaper.naturalWidth === 0) {
                    alert('壁纸未加载完成，无法下载！');
                    return;
                }
                
                // 会员权限检查
                try {
                    const permissionResponse = await fetch('/api/vip/check_download_permission.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            download_type: 'avatar',
                            wallpaper_id: window.location.search.includes('id=') ? 
                                new URLSearchParams(window.location.search).get('id') : 'local_upload'
                        })
                    });
                    
                    const permissionData = await permissionResponse.json();
                    
                    if (!permissionData.success) {
                        if (permissionData.error_code === 'QUOTA_EXCEEDED') {
                            alert(`下载配额不足！\n${permissionData.message}\n\n升级会员可享受更多下载权益。`);
                        } else if (permissionData.error_code === 'LOGIN_REQUIRED') {
                            alert('请先登录后再下载。');
                        } else {
                            alert(permissionData.message || '下载权限检查失败');
                        }
                        return;
                    }
                } catch (error) {
                    console.error('权限检查失败:', error);
                    alert('网络错误，请稍后重试。');
                    return;
                }
                
                const originalText = downloadAvatarButton.innerHTML;
                downloadAvatarButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> 下载中...';
                downloadAvatarButton.disabled = true;
                try {
                    // 下载圆形头像，使用PNG格式以保留透明背景
                    const roundCanvas = document.createElement('canvas');
                    // 分辨率提升2倍
                    roundCanvas.width = avatarSize.width * 2;
                    roundCanvas.height = avatarSize.height * 2;
                    const roundCtx = roundCanvas.getContext('2d');
                    // 在提升分辨率的Canvas上绘制头像，传入yOffset和补偿偏移量
                    drawAvatar(roundCtx, currentWallpaper, true, yOffset, -70); // 应用偏移补偿
                    // 导出为PNG格式以保留透明背景
                    const roundSuccess = await exportCanvasAsImage(roundCanvas, '圆形头像.png', 'image/png'); // 指定 PNG 格式

                    // 下载圆角方形头像，使用PNG格式以保留透明背景
                    const squareCanvas = document.createElement('canvas');
                     // 分辨率提升2倍
                    squareCanvas.width = avatarSize.width * 2;
                    squareCanvas.height = avatarSize.height * 2;
                    const squareCtx = squareCanvas.getContext('2d');
                     // 在提升分辨率的Canvas上绘制头像，传入yOffset和补偿偏移量
                    drawAvatar(squareCtx, currentWallpaper, false, yOffset, -70); // 应用偏移补偿
                    // 导出为PNG格式以保留透明背景
                    const squareSuccess = await exportCanvasAsImage(squareCanvas, '方形头像.png', 'image/png'); // 指定 PNG 格式

                    if (roundSuccess && squareSuccess) {
                        // 记录下载行为
                        try {
                            await fetch('/api/vip/record_download.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    download_type: 'avatar',
                                    wallpaper_id: window.location.search.includes('id=') ? 
                                        new URLSearchParams(window.location.search).get('id') : 'local_upload'
                                })
                            });
                        } catch (error) {
                            console.error('记录下载失败:', error);
                        }
                        
                        downloadAvatarButton.innerHTML = '<i class="fa-solid fa-check mr-2"></i> 下载成功';
                        downloadAvatarButton.classList.remove('bg-green-500');
                        downloadAvatarButton.classList.add('bg-green-600');
                        setTimeout(() => {
                            downloadAvatarButton.innerHTML = originalText;
                            downloadAvatarButton.classList.remove('bg-green-600');
                            downloadAvatarButton.classList.add('bg-green-500');
                            downloadAvatarButton.disabled = false;
                        }, 2000);
                    } else {
                         downloadAvatarButton.innerHTML = '<i class="fa-solid fa-times mr-2"></i> 下载失败';
                         downloadAvatarButton.classList.remove('bg-green-500');
                         downloadAvatarButton.classList.add('bg-red-500');
                         setTimeout(() => {
                             downloadAvatarButton.innerHTML = originalText;
                             downloadAvatarButton.classList.remove('bg-red-500');
                             downloadAvatarButton.classList.add('bg-green-500');
                             downloadAvatarButton.disabled = false;
                         }, 2000);
                    }

                } catch (error) {
                    console.error('下载失败:', error);
                    downloadAvatarButton.innerHTML = '<i class="fa-solid fa-times mr-2"></i> 下载失败';
                    downloadAvatarButton.classList.remove('bg-green-500');
                    downloadAvatarButton.classList.add('bg-red-500');
                    setTimeout(() => {
                        downloadAvatarButton.innerHTML = originalText;
                        downloadAvatarButton.classList.remove('bg-red-500');
                        downloadAvatarButton.classList.add('bg-green-500');
                        downloadAvatarButton.disabled = false;
                    }, 2000);
                }
            }
            
            // 下载朋友圈封面
            async function downloadCover() {
                if (!currentWallpaper || !currentWallpaper.complete || currentWallpaper.naturalWidth === 0) {
                    alert('壁纸未加载完成，无法下载！');
                    return;
                }
                
                // 会员权限检查
                try {
                    const permissionResponse = await fetch('/api/vip/check_download_permission.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            download_type: 'cover',
                            wallpaper_id: window.location.search.includes('id=') ? 
                                new URLSearchParams(window.location.search).get('id') : 'local_upload'
                        })
                    });
                    
                    const permissionData = await permissionResponse.json();
                    
                    if (!permissionData.success) {
                        if (permissionData.error_code === 'QUOTA_EXCEEDED') {
                            alert(`下载配额不足！\n${permissionData.message}\n\n升级会员可享受更多下载权益。`);
                        } else if (permissionData.error_code === 'LOGIN_REQUIRED') {
                            alert('请先登录后再下载。');
                        } else {
                            alert(permissionData.message || '下载权限检查失败');
                        }
                        return;
                    }
                } catch (error) {
                    console.error('权限检查失败:', error);
                    alert('网络错误，请稍后重试。');
                    return;
                }
                
                const originalText = downloadCoverButton.innerHTML;
                downloadCoverButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> 下载中...';
                downloadCoverButton.disabled = true;
                try {
                    // 新建临时canvas，宽高用最新momentWidth和momentHeight
                    const tempCanvas = document.createElement('canvas');
                    tempCanvas.width = momentWidth;
                    tempCanvas.height = momentHeight;
                    const tempCtx = tempCanvas.getContext('2d');
                    tempCtx.save();
                    roundRect(tempCtx, 0, 0, momentWidth, momentHeight, 20);
                    tempCtx.clip();
                    // 绘制壁纸
                    if (currentWallpaper) {
                        const imageRatio = currentWallpaper.width / currentWallpaper.height;
                        const screenRatio = momentWidth / momentHeight;
                        let drawWidth, drawHeight, drawX, drawY;
                        if (imageRatio > screenRatio) {
                            drawHeight = momentHeight;
                            drawWidth = drawHeight * imageRatio;
                            drawX = -(drawWidth - momentWidth) / 2;
                            drawY = yOffset; // 使用yOffset
                        } else {
                            drawWidth = momentWidth;
                            drawHeight = drawWidth / imageRatio;
                            drawX = 0;
                            drawY = -(drawHeight - momentHeight) / 2 + yOffset; // 使用yOffset
                        }
                        tempCtx.drawImage(currentWallpaper, drawX, drawY, drawWidth, drawHeight);
                    } else {
                        tempCtx.fillStyle = '#EEE';
                        tempCtx.fillRect(0, 0, momentWidth, momentHeight);
                    }
                    // 底部白色栏
                    tempCtx.fillStyle = '#fff';
                    tempCtx.fillRect(0, momentHeight - momentBarHeight, momentWidth, momentBarHeight);
                    // 底部白色栏左侧说明文字
                    tempCtx.save();
                    tempCtx.font = 'bold 16px sans-serif'; // 避免自定义字体导致异常
                    tempCtx.fillStyle = 'rgba(120,120,120,0.85)';
                    tempCtx.textAlign = 'left';
                    tempCtx.textBaseline = 'middle';
                    tempCtx.shadowColor = 'rgba(255,255,255,0.7)';
                    tempCtx.shadowBlur = 4;
                    tempCtx.fillText('朋友圈封面预览+头像', 24, momentHeight - momentBarHeight/2);
                    tempCtx.restore();
                    // 右下角头像框
                    const avatarX = momentWidth - momentAvatarSize - momentAvatarMargin;
                    const avatarY = momentHeight - momentBarHeight/2 - momentAvatarSize/2;
                    tempCtx.save();
                    roundRect(tempCtx, avatarX, avatarY, momentAvatarSize, momentAvatarSize, momentAvatarRadius);
                    tempCtx.clip();
                    if (currentWallpaper && currentWallpaper.complete) {
                        const scale = Math.max(momentAvatarSize / currentWallpaper.width, momentAvatarSize / currentWallpaper.height);
                        const ax = avatarX + (momentAvatarSize - currentWallpaper.width * scale) / 2;
                        const ay = avatarY + (momentAvatarSize - currentWallpaper.height * scale) / 2; // 移除yOffset应用，头像固定在右下角
                        tempCtx.drawImage(currentWallpaper, ax, ay, currentWallpaper.width * scale, currentWallpaper.height * scale);
                    } else {
                        tempCtx.fillStyle = '#CCC';
                        tempCtx.fillRect(avatarX, avatarY, momentAvatarSize, momentAvatarSize);
                    }
                    tempCtx.restore();
                    // 昵称
                    tempCtx.font = 'bold 36px sans-serif'; // 避免自定义字体导致异常
                    tempCtx.fillStyle = '#222';
                    tempCtx.textAlign = 'right';
                    tempCtx.textBaseline = 'middle';
                    tempCtx.fillText(momentName, avatarX - 20, momentHeight - momentBarHeight/2);
                    // 相机icon（右上角）
                    tempCtx.save();
                    if (cameraIconImg && cameraIconImg.complete && cameraIconImg.naturalWidth > 0) {
                    tempCtx.drawImage(cameraIconImg, momentWidth - 40 - 16, 40 - 16, 32, 32);
                }
                    tempCtx.restore();
                    // 边框
                    tempCtx.save();
                    roundRect(tempCtx, 0.5, 0.5, momentWidth-1, momentHeight-1, 20);
                    tempCtx.strokeStyle = '#e5e7eb';
                    tempCtx.lineWidth = 4;
                    tempCtx.stroke();
                    tempCtx.restore();
                    // 导出图片
                    const coverSuccess = await exportCanvasAsImage(tempCanvas, '朋友圈预览.png');

                    if (coverSuccess) {
                        // 记录下载行为
                        try {
                            await fetch('/api/vip/record_download.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    download_type: 'cover',
                                    wallpaper_id: window.location.search.includes('id=') ? 
                                        new URLSearchParams(window.location.search).get('id') : 'local_upload'
                                })
                            });
                        } catch (error) {
                            console.error('记录下载失败:', error);
                        }
                        
                        downloadCoverButton.innerHTML = '<i class="fa-solid fa-check mr-2"></i> 下载成功';
                        downloadCoverButton.classList.remove('bg-blue-500');
                        downloadCoverButton.classList.add('bg-blue-600');
                        setTimeout(() => {
                            downloadCoverButton.innerHTML = originalText;
                            downloadCoverButton.classList.remove('bg-blue-600');
                            downloadCoverButton.classList.add('bg-blue-500');
                            downloadCoverButton.disabled = false;
                        }, 2000);
                    } else {
                         downloadCoverButton.innerHTML = '<i class="fa-solid fa-times mr-2"></i> 下载失败';
                         downloadCoverButton.classList.remove('bg-blue-500');
                         downloadCoverButton.classList.add('bg-red-500');
                         setTimeout(() => {
                             downloadCoverButton.innerHTML = originalText;
                             downloadCoverButton.classList.remove('bg-red-500');
                             downloadCoverButton.classList.add('bg-blue-500');
                             downloadCoverButton.disabled = false;
                         }, 2000);
                    }

                } catch (error) {
                    console.error('下载失败:', error);
                    alert('下载失败：' + error.message);
                    downloadCoverButton.innerHTML = '<i class="fa-solid fa-times mr-2"></i> 下载失败';
                    downloadCoverButton.classList.remove('bg-blue-500');
                    downloadCoverButton.classList.add('bg-red-500');
                    setTimeout(() => {
                        downloadCoverButton.innerHTML = originalText;
                        downloadCoverButton.classList.remove('bg-red-500');
                        downloadCoverButton.classList.add('bg-blue-500');
                        downloadCoverButton.disabled = false;
                    }, 2000);
                }
            }
            
            // 组合下载
            async function downloadBoth() {
                if (!currentWallpaper || !currentWallpaper.complete || currentWallpaper.naturalWidth === 0) {
                    alert('壁纸未加载完成，无法下载！');
                    return;
                }
                
                // 会员权限检查
                try {
                    const permissionResponse = await fetch('/api/vip/check_download_permission.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            download_type: 'combined',
                            wallpaper_id: window.location.search.includes('id=') ? 
                                new URLSearchParams(window.location.search).get('id') : 'local_upload'
                        })
                    });
                    
                    const permissionData = await permissionResponse.json();
                    
                    if (!permissionData.success) {
                        if (permissionData.error_code === 'QUOTA_EXCEEDED') {
                            alert(`下载配额不足！\n${permissionData.message}\n\n升级会员可享受更多下载权益。`);
                        } else if (permissionData.error_code === 'LOGIN_REQUIRED') {
                            alert('请先登录后再下载。');
                        } else {
                            alert(permissionData.message || '下载权限检查失败');
                        }
                        return;
                    }
                } catch (error) {
                    console.error('权限检查失败:', error);
                    alert('网络错误，请稍后重试。');
                    return;
                }
                
                const originalText = downloadBothButton.innerHTML;
                downloadBothButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> 下载中...';
                downloadBothButton.disabled = true;
                try {
                    // === 下载圆形头像 (尺寸 600x600) ===
                    const avatarRoundCanvas = document.createElement('canvas');
                    avatarRoundCanvas.width = avatarSize.width * 2;
                    avatarRoundCanvas.height = avatarSize.height * 2;
                    const avatarRoundCtx = avatarRoundCanvas.getContext('2d');
                    // 在提升分辨率的Canvas上绘制头像，传入yOffset和补偿偏移量
                    drawAvatar(avatarRoundCtx, currentWallpaper, true, yOffset, -70); // 应用偏移补偿
                    // 导出为PNG格式以保留透明背景
                    const roundSuccess = await exportCanvasAsImage(avatarRoundCanvas, '圆形头像.png');

                    // === 下载圆角方形头像 (尺寸 600x600) ===
                    const avatarSquareCanvas = document.createElement('canvas');
                    // 分辨率提升2倍
                    avatarSquareCanvas.width = avatarSize.width * 2;
                    avatarSquareCanvas.height = avatarSize.height * 2;
                    const avatarSquareCtx = avatarSquareCanvas.getContext('2d');
                    // 在提升分辨率的Canvas上绘制头像，传入yOffset和补偿偏移量
                    drawAvatar(avatarSquareCtx, currentWallpaper, false, yOffset, -70); // 应用偏移补偿
                    // 导出为PNG格式以保留透明背景
                    const squareSuccess = await exportCanvasAsImage(avatarSquareCanvas, '方形头像.png');

                    // === 下载朋友圈完整预览 (封面，已支持yOffset) ===
                    // 新建临时canvas，宽高用最新momentWidth和momentHeight
                    const tempCanvas = document.createElement('canvas');
                    tempCanvas.width = momentWidth;
                    tempCanvas.height = momentHeight;
                    const tempCtx = tempCanvas.getContext('2d');
                    tempCtx.save();
                    roundRect(tempCtx, 0, 0, momentWidth, momentHeight, 20);
                    tempCtx.clip();
                    // 绘制壁纸
                    if (currentWallpaper) {
                        const imageRatio = currentWallpaper.width / currentWallpaper.height;
                        const screenRatio = momentWidth / momentHeight;
                        let drawWidth, drawHeight, drawX, drawY;
                        if (imageRatio > screenRatio) {
                            drawHeight = momentHeight;
                            drawWidth = drawHeight * imageRatio;
                            drawX = -(drawWidth - momentWidth) / 2;
                            drawY = yOffset; // 使用yOffset
                        } else {
                            drawWidth = momentWidth;
                            drawHeight = drawWidth / imageRatio;
                            drawX = 0;
                            drawY = -(drawHeight - momentHeight) / 2 + yOffset; // 使用yOffset
                        }
                        tempCtx.drawImage(currentWallpaper, drawX, drawY, drawWidth, drawHeight);
                    } else {
                        tempCtx.fillStyle = '#EEE';
                        tempCtx.fillRect(0, 0, momentWidth, momentHeight);
                    }
                    // 底部白色栏
                    tempCtx.fillStyle = '#fff';
                    tempCtx.fillRect(0, momentHeight - momentBarHeight, momentWidth, momentBarHeight);
                    // 底部白色栏左侧说明文字
                    tempCtx.save();
                    tempCtx.font = 'bold 16px sans-serif'; // 避免自定义字体导致异常
                    tempCtx.fillStyle = 'rgba(120,120,120,0.85)';
                    tempCtx.textAlign = 'left';
                    tempCtx.textBaseline = 'middle';
                    tempCtx.shadowColor = 'rgba(255,255,255,0.7)';
                    tempCtx.shadowBlur = 4;
                    tempCtx.fillText('朋友圈封面预览+头像', 24, momentHeight - momentBarHeight/2);
                    tempCtx.restore();
                    // 右下角头像框
                    const avatarX = momentWidth - momentAvatarSize - momentAvatarMargin;
                    const avatarY = momentHeight - momentBarHeight/2 - momentAvatarSize/2;
                    tempCtx.save();
                    roundRect(tempCtx, avatarX, avatarY, momentAvatarSize, momentAvatarSize, momentAvatarRadius);
                    tempCtx.clip();
                    if (currentWallpaper && currentWallpaper.complete) {
                        const scale = Math.max(momentAvatarSize / currentWallpaper.width, momentAvatarSize / currentWallpaper.height);
                        const ax = avatarX + (momentAvatarSize - currentWallpaper.width * scale) / 2;
                        const ay = avatarY + (momentAvatarSize - currentWallpaper.height * scale) / 2; // 移除yOffset应用，头像固定在右下角
                        tempCtx.drawImage(currentWallpaper, ax, ay, currentWallpaper.width * scale, currentWallpaper.height * scale);
                    } else {
                        tempCtx.fillStyle = '#CCC';
                        tempCtx.fillRect(avatarX, avatarY, momentAvatarSize, momentAvatarSize);
                    }
                    tempCtx.restore();
                    // 昵称
                    tempCtx.font = 'bold 36px sans-serif'; // 避免自定义字体导致异常
                    tempCtx.fillStyle = '#222';
                    tempCtx.textAlign = 'right';
                    tempCtx.textBaseline = 'middle';
                    tempCtx.fillText(momentName, avatarX - 20, momentHeight - momentBarHeight/2);
                    // 相机icon（右上角）
                    tempCtx.save();
                    if (cameraIconImg && cameraIconImg.complete && cameraIconImg.naturalWidth > 0) {
                    tempCtx.drawImage(cameraIconImg, momentWidth - 40 - 16, 40 - 16, 32, 32);
                }
                    tempCtx.restore();
                    // 边框
                    tempCtx.save();
                    roundRect(tempCtx, 0.5, 0.5, momentWidth-1, momentHeight-1, 20);
                    tempCtx.strokeStyle = '#e5e7eb';
                    tempCtx.lineWidth = 4;
                    tempCtx.stroke();
                    tempCtx.restore();
                    // 导出图片
                    const coverSuccess = await exportCanvasAsImage(tempCanvas, '朋友圈预览.png');

                    if (roundSuccess && squareSuccess && coverSuccess) {
                    // 所有下载完成后更新按钮状态
                    downloadBothButton.innerHTML = '<i class="fa-solid fa-check mr-2"></i> 下载成功';
                    downloadBothButton.classList.remove('bg-purple-500');
                    downloadBothButton.classList.add('bg-purple-600');
                    setTimeout(() => {
                        downloadBothButton.innerHTML = originalText;
                        downloadBothButton.classList.remove('bg-purple-600');
                        downloadBothButton.classList.add('bg-purple-500');
                        downloadBothButton.disabled = false;
                    }, 2000);
                    } else {
                         downloadBothButton.innerHTML = '<i class="fa-solid fa-times mr-2"></i> 下载失败';
                         downloadBothButton.classList.remove('bg-purple-500');
                         downloadBothButton.classList.add('bg-red-500');
                         setTimeout(() => {
                             downloadBothButton.innerHTML = originalText;
                             downloadBothButton.classList.remove('bg-red-500');
                             downloadBothButton.classList.add('bg-purple-500');
                             downloadBothButton.disabled = false;
                         }, 2000);
                    }

                } catch (error) {
                    console.error('下载失败:', error);
                    downloadBothButton.innerHTML = '<i class="fa-solid fa-times mr-2"></i> 下载失败';
                    downloadBothButton.classList.remove('bg-purple-500');
                    downloadBothButton.classList.add('bg-red-500');
                    setTimeout(() => {
                        downloadBothButton.innerHTML = originalText;
                        downloadBothButton.classList.remove('bg-red-500');
                        downloadBothButton.classList.add('bg-purple-500');
                        downloadBothButton.disabled = false;
                    }, 2000);
                }
            }

            // 绘制朋友圈封面预览
            /**
             * 绘制朋友圈封面预览
             * @param {HTMLImageElement} wallpaper - 壁纸图片
             * @param {HTMLImageElement} avatarImg - 头像图片
             * @param {number} yOffset - 壁纸上下偏移量
             */
            function drawMomentPreview(wallpaper, avatarImg, yOffset = 0) {
                // 先裁切圆角矩形
                momentCtx.clearRect(0, 0, momentWidth, momentHeight);
                momentCtx.save();
                momentCtx.beginPath();
                roundRect(momentCtx, 0, 0, momentWidth, momentHeight, 20);
                momentCtx.clip();
                // 绘制壁纸，直接填满canvas
                if (wallpaper) {
                    const imageRatio = wallpaper.width / wallpaper.height;
                    const screenRatio = momentWidth / momentHeight;
                    let drawWidth, drawHeight, drawX, drawY;
                    if (imageRatio > screenRatio) {
                        drawHeight = momentHeight;
                        drawWidth = drawHeight * imageRatio;
                        drawX = -(drawWidth - momentWidth) / 2;
                        drawY = yOffset; // 应用yOffset
                    } else {
                        drawWidth = momentWidth;
                        drawHeight = drawWidth / imageRatio;
                        drawX = 0;
                        drawY = -(drawHeight - momentHeight) / 2 + yOffset; // 应用yOffset
                    }
                    momentCtx.drawImage(wallpaper, drawX, drawY, drawWidth, drawHeight);
                } else {
                    momentCtx.fillStyle = '#EEE';
                    momentCtx.fillRect(0, 0, momentWidth, momentHeight);
                }
                // 底部白色栏
                momentCtx.fillStyle = '#fff';
                momentCtx.fillRect(0, momentHeight - momentBarHeight, momentWidth, momentBarHeight);
                // 底部白色栏左侧说明文字
                momentCtx.save();
                momentCtx.font = 'bold 16px Inter, sans-serif';
                momentCtx.fillStyle = 'rgba(120,120,120,0.85)';
                momentCtx.textAlign = 'left';
                momentCtx.textBaseline = 'middle';
                momentCtx.shadowColor = 'rgba(255,255,255,0.7)';
                momentCtx.shadowBlur = 4;
                momentCtx.fillText('朋友圈封面预览+头像', 24, momentHeight - momentBarHeight/2);
                momentCtx.restore();
                // 右下角头像框
                const avatarX = momentWidth - momentAvatarSize - momentAvatarMargin;
                const avatarY = momentHeight - momentBarHeight/2 - momentAvatarSize/2;
                momentCtx.save();
                momentCtx.beginPath();
                roundRect(momentCtx, avatarX, avatarY, momentAvatarSize, momentAvatarSize, momentAvatarRadius);
                momentCtx.clip();
                if (avatarImg && avatarImg.complete) {
                    const scale = Math.max(momentAvatarSize / avatarImg.width, momentAvatarSize / avatarImg.height);
                    const ax = avatarX + (momentAvatarSize - avatarImg.width * scale) / 2;
                    const ay = avatarY + (momentAvatarSize - avatarImg.height * scale) / 2; // 移除yOffset应用，头像固定在右下角
                    momentCtx.drawImage(avatarImg, ax, ay, avatarImg.width * scale, avatarImg.height * scale);
                } else {
                    momentCtx.fillStyle = '#CCC';
                    momentCtx.fillRect(avatarX, avatarY, momentAvatarSize, momentAvatarSize);
                }
                momentCtx.restore();
                // 昵称
                momentCtx.font = 'bold 36px Inter, sans-serif'; // 确认字体设置
                momentCtx.fillStyle = '#222';
                momentCtx.textAlign = 'right';
                momentCtx.textBaseline = 'middle';
                momentCtx.fillText(momentName, avatarX - 20, momentHeight - momentBarHeight/2);
                // 相机icon
                momentCtx.save();
                // 只绘制透明PNG，不绘制背景
                if (cameraIconImg && cameraIconImg.complete && cameraIconImg.naturalWidth > 0) {
                    momentCtx.drawImage(cameraIconImg, momentWidth - 40 - 16, 40 - 16, 32, 32);
                } else {
                     console.warn('Camera icon image not loaded or invalid for full preview.');
                     // 绘制一个简单的相机图标占位符
                     momentCtx.fillStyle = '#666';
                     momentCtx.fillRect(momentWidth - 40 - 16, 40 - 16, 32, 32);
                     momentCtx.fillStyle = '#fff';
                     momentCtx.fillRect(momentWidth - 40 - 12, 40 - 12, 24, 24);
                }
                momentCtx.restore();
                // 最后画一圈圆角边框
                momentCtx.save();
                momentCtx.beginPath();
                roundRect(momentCtx, 0.5, 0.5, momentWidth-1, momentHeight-1, 20);
                momentCtx.strokeStyle = '#e5e7eb';
                momentCtx.lineWidth = 4;
                momentCtx.stroke();
                momentCtx.restore();
            }

            // 新增朋友圈完整预览截图
            const momentFullCanvas = document.createElement('canvas');
            momentFullCanvas.width = momentWidth;
            momentFullCanvas.height = momentHeight;
            const momentFullCtx = momentFullCanvas.getContext('2d');
            // 绘制完整朋友圈预览截图
            /**
             * 绘制完整朋友圈预览截图
             * @param {HTMLImageElement} wallpaper - 壁纸图片
             * @param {HTMLImageElement} avatarImg - 头像图片
             * @param {number} yOffset - 壁纸上下偏移量
             */
            function drawMomentFullPreview(wallpaper, avatarImg, yOffset = 0) {
                momentFullCanvas.width = momentWidth;
                momentFullCanvas.height = momentHeight;
                momentFullCtx.clearRect(0, 0, momentWidth, momentHeight);
                momentFullCtx.save();
                momentFullCtx.beginPath();
                roundRect(momentFullCtx, 0, 0, momentWidth, momentHeight, 20);
                momentFullCtx.clip();
                // 绘制壁纸，直接填满canvas
                if (wallpaper) {
                    const imageRatio = wallpaper.width / wallpaper.height;
                    const screenRatio = momentWidth / momentHeight;
                    let drawWidth, drawHeight, drawX, drawY;
                    if (imageRatio > screenRatio) {
                        drawHeight = momentHeight;
                        drawWidth = drawHeight * imageRatio;
                        drawX = -(drawWidth - momentWidth) / 2;
                        drawY = yOffset; // 应用yOffset
                    } else {
                        drawWidth = momentWidth;
                        drawHeight = drawWidth / imageRatio;
                        drawX = 0;
                        drawY = -(drawHeight - momentHeight) / 2 + yOffset; // 应用yOffset
                    }
                    momentFullCtx.drawImage(wallpaper, drawX, drawY, drawWidth, drawHeight);
                } else {
                    momentFullCtx.fillStyle = '#EEE';
                    momentFullCtx.fillRect(0, 0, momentWidth, momentHeight);
                }
                // 底部白色栏
                momentFullCtx.fillStyle = '#fff';
                momentFullCtx.fillRect(0, momentHeight - momentBarHeight, momentWidth, momentBarHeight);
                // 底部白色栏左侧说明文字
                momentFullCtx.save();
                momentFullCtx.font = 'bold 16px Inter, sans-serif';
                momentFullCtx.fillStyle = 'rgba(120,120,120,0.85)';
                momentFullCtx.textAlign = 'left';
                momentFullCtx.textBaseline = 'middle';
                momentFullCtx.shadowColor = 'rgba(255,255,255,0.7)';
                momentFullCtx.shadowBlur = 4;
                momentFullCtx.fillText('朋友圈封面预览+头像', 24, momentHeight - momentBarHeight/2);
                momentFullCtx.restore();
                // 右下角头像框
                const avatarX = momentWidth - momentAvatarSize - momentAvatarMargin;
                const avatarY = momentHeight - momentBarHeight/2 - momentAvatarSize/2;
                momentFullCtx.save();
                momentFullCtx.beginPath();
                roundRect(momentFullCtx, avatarX, avatarY, momentAvatarSize, momentAvatarSize, momentAvatarRadius);
                momentFullCtx.clip();
                if (avatarImg && avatarImg.complete) {
                    const scale = Math.max(momentAvatarSize / avatarImg.width, momentAvatarSize / avatarImg.height);
                    const ax = avatarX + (momentAvatarSize - avatarImg.width * scale) / 2;
                    const ay = avatarY + (momentAvatarSize - avatarImg.height * scale) / 2; // 移除yOffset应用，头像固定在右下角
                    momentFullCtx.drawImage(avatarImg, ax, ay, avatarImg.width * scale, avatarImg.height * scale);
                } else {
                    momentFullCtx.fillStyle = '#CCC';
                    momentFullCtx.fillRect(avatarX, avatarY, momentAvatarSize, momentAvatarSize);
                }
                momentFullCtx.restore();
                // 昵称
                momentFullCtx.font = 'bold 36px Inter, sans-serif'; // 确认字体设置
                momentFullCtx.fillStyle = '#222';
                momentFullCtx.textAlign = 'right';
                momentFullCtx.textBaseline = 'middle';
                momentFullCtx.fillText(momentName, avatarX - 20, momentHeight - momentBarHeight/2);
                // 相机icon
                momentFullCtx.save();
                // 只绘制透明PNG，不绘制背景
                if (cameraIconImg && cameraIconImg.complete && cameraIconImg.naturalWidth > 0) {
                    momentFullCtx.drawImage(cameraIconImg, momentWidth - 40 - 16, 40 - 16, 32, 32);
                } else {
                     console.warn('Camera icon image not loaded or invalid for full preview.');
                }
                momentFullCtx.restore();
                // 边框
                momentFullCtx.save();
                momentFullCtx.beginPath();
                roundRect(momentFullCtx, 0.5, 0.5, momentWidth-1, momentHeight-1, 20);
                momentFullCtx.strokeStyle = '#e5e7eb';
                momentFullCtx.lineWidth = 4;
                momentFullCtx.stroke();
                momentFullCtx.restore();
            }

            // 新增：下载原图按钮
            /**
             * 下载当前预览的原始图片
             * @description 支持首页跳转和本地上传两种情况，自动识别图片来源
             */
            const downloadOriginalButton = document.getElementById('downloadOriginalButton');
            downloadOriginalButton.addEventListener('click', async function() {
                let url = '';
                
                // 优先通过壁纸ID从数据库获取原图路径
                const urlParams = new URLSearchParams(window.location.search);
                const wallpaperId = urlParams.get('id');
                
                if (wallpaperId) {
                    try {
                        const response = await fetch(`api/wallpaper_detail.php?id=${wallpaperId}`);
                        const result = await response.json();
                        if (result.code === 0 && result.data && result.data.path) {
                            url = result.data.path;
                            console.log('通过壁纸ID获取到原图路径:', url);
                        }
                    } catch (error) {
                        console.error('获取壁纸路径失败:', error);
                    }
                }
                
                // 如果通过ID获取失败，则使用原有逻辑（但优先查找原图路径）
                if (!url) {
                    // 检查URL参数中的image是否为原图路径
                    if (typeof imageUrl === 'string') {
                        if (imageUrl.startsWith('static/wallpapers/') || imageUrl.match(/static\/wallpapers\/\d{3}\//)) {
                            // 直接是原图路径
                            url = imageUrl;
                        } else if (imageUrl.includes('static/preview/')) {
                            // 是预览图路径，转换为原图路径
                            url = imageUrl.replace('static/preview/', 'static/wallpapers/');
                            console.log('预览图路径转换为原图路径:', imageUrl, '->', url);
                        } else if (imageUrl.includes('proxy.php?token=') || imageUrl.includes('image_proxy.php')) {
                            // Token化URL，保持原样
                            url = imageUrl;
                        } else if (imageUrl.startsWith('blob:')) {
                            // Blob URL，保持原样
                            url = imageUrl;
                        }
                    }
                    
                    // 如果imageUrl没有找到合适的路径，检查currentWallpaper
                    if (!url && currentWallpaper && typeof currentWallpaper.src === 'string') {
                        if (currentWallpaper.src.startsWith('static/wallpapers/') || currentWallpaper.src.match(/static\/wallpapers\/\d{3}\//)) {
                            url = currentWallpaper.src;
                        } else if (currentWallpaper.src.includes('static/preview/')) {
                            url = currentWallpaper.src.replace('static/preview/', 'static/wallpapers/');
                        } else if (currentWallpaper.src.includes('proxy.php?token=') || currentWallpaper.src.includes('image_proxy.php')) {
                            url = currentWallpaper.src;
                        } else if (currentWallpaper.src.startsWith('blob:')) {
                            url = currentWallpaper.src;
                        }
                    }
                }
                
                if (!url) {
                    alert('未找到原图，无法下载！\n请确保是从首页跳转或本地上传原图。');
                    return;
                }
                
                console.log('最终下载URL:', url);
                const originalText = downloadOriginalButton.innerHTML;
                downloadOriginalButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> 下载中...';
                downloadOriginalButton.disabled = true;
                // 处理不同类型的URL下载
                if (url.includes('proxy.php?token=') || url.includes('image_proxy.php')) {
                    // Token化URL需要通过fetch获取blob数据
                    try {
                        const response = await fetch(url);
                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                        }
                        const blob = await response.blob();
                        
                        // 从响应头获取文件名，或使用默认名称
                        const contentDisposition = response.headers.get('content-disposition');
                        let filename = '原图.jpg';
                        if (contentDisposition && contentDisposition.includes('filename=')) {
                            filename = contentDisposition.split('filename=')[1].replace(/"/g, '');
                        } else {
                            // 尝试从Content-Type推断扩展名
                            const contentType = response.headers.get('content-type');
                            if (contentType) {
                                if (contentType.includes('jpeg')) filename = '原图.jpg';
                                else if (contentType.includes('png')) filename = '原图.png';
                                else if (contentType.includes('webp')) filename = '原图.webp';
                            }
                        }
                        
                        // 创建blob URL并下载
                        const blobUrl = URL.createObjectURL(blob);
                        const link = document.createElement('a');
                        link.href = blobUrl;
                        link.download = filename;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        
                        // 清理blob URL
                        setTimeout(() => URL.revokeObjectURL(blobUrl), 1000);
                    } catch (error) {
                        console.error('下载Token化图片失败:', error);
                        alert('下载失败：' + error.message);
                        return;
                    }
                } else {
                    // 直接URL下载
                    const link = document.createElement('a');
                    link.href = url;
                    // 自动识别文件名
                    let filename = url.split('/').pop().split('?')[0] || '原图.jpg';
                    link.download = filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }
                // 状态恢复
                setTimeout(() => {
                    downloadOriginalButton.innerHTML = '<i class="fa-solid fa-check mr-2"></i> 下载成功';
                    downloadOriginalButton.classList.remove('bg-primary');
                    downloadOriginalButton.classList.add('bg-green-500');
                    setTimeout(() => {
                        downloadOriginalButton.innerHTML = originalText;
                        downloadOriginalButton.classList.remove('bg-green-500');
                        downloadOriginalButton.classList.add('bg-primary');
                        downloadOriginalButton.disabled = false;
                    }, 1500);
                }, 800);
            });

            // 定义图片加载和更新页面的统一函数
            /**
             * 根据图片URL加载图片，成功后更新全局currentWallpaper并绘制所有预览
             * @param {string} url - 要加载的图片的URL
             */
            function loadAndDisplayWallpaper(url) {
                const img = new Image();
                img.crossOrigin = 'anonymous'; // 解决跨域问题
                img.onload = function() {
                    currentWallpaper = img; // 成功加载后，将原图对象赋给currentWallpaper
                    previewImage.src = url; // 预览小窗口显示加载成功的URL对应的图片
                    previewControls.classList.remove('hidden'); // 显示控制区域
                    // 同步预览图片位置（仅对电脑预览有效，但这里更新小窗口预览图）
                    previewImage.style.objectPosition = `center -${yOffset}px`;
                    // 绘制所有设备canvas
                    drawPhone(currentWallpaper);
                    drawTablet(currentWallpaper);
                    drawLaptop(currentWallpaper);
                    // drawMomentPreview(currentWallpaper, currentWallpaper); // 朋友圈预览可能需要单独处理头像，暂不在这里统一绘制
                    drawMomentPreview(currentWallpaper, currentWallpaper || momentAvatarImg, yOffset); // 在加载壁纸后绘制朋友圈预览并传入yOffset

                    // 添加动画效果
                    phoneCanvas.classList.add('animate-pulse');
                    tabletCanvas.classList.add('animate-pulse');
                    laptopCanvas.classList.add('animate-pulse');
                    setTimeout(() => {
                        phoneCanvas.classList.remove('animate-pulse');
                        tabletCanvas.classList.remove('animate-pulse');
                        laptopCanvas.classList.remove('animate-pulse');
                    }, 1000);

                    console.log('壁纸加载成功：', url);
                };
                img.onerror = function() {
                    console.error('壁纸加载失败：', url);
                    alert('加载壁纸失败，请检查图片链接或重新上传！');
                    previewControls.classList.add('hidden'); // 隐藏控制区域
                    currentWallpaper = null; // 加载失败，清空currentWallpaper
                };
                img.src = url;
            }

            // 添加移动端自适应样式
            function updateCanvasSize() {
                const isMobile = window.innerWidth < 768; // 768px是Tailwind的md断点
                const container = document.querySelector('.container');
                const containerWidth = container.clientWidth;
                
                if (isMobile) {
                    // 移动端自适应缩放
                    const scale = Math.min(1, containerWidth / laptopWidth);
                    
                    // 设置canvas容器的最大宽度
                    const devicePreviews = document.querySelectorAll('.device-preview');
                    devicePreviews.forEach(preview => {
                        preview.style.maxWidth = '100%';
                        preview.style.margin = '0 auto';
                        preview.style.overflow = 'hidden'; // 防止内容溢出
                    });
                    
                    // 设置canvas的样式
                    [tabletCanvas, laptopCanvas, momentCanvas].forEach(canvas => {
                        canvas.style.width = '100%';
                        canvas.style.height = 'auto';
                        canvas.style.maxWidth = `${canvas.width * scale}px`;
                        canvas.style.transform = 'translateX(0)'; // 确保没有水平偏移
                    });

                    // 调整设备旋转容器的样式
                    const rotateContainers = document.querySelectorAll('.tablet-rotate, .laptop-rotate');
                    rotateContainers.forEach(container => {
                        container.style.transform = 'none'; // 移动端取消旋转效果
                        container.style.width = '100%';
                        container.style.overflow = 'hidden';
                    });
                } else {
                    // PC端恢复原始尺寸
                    [tabletCanvas, laptopCanvas, momentCanvas].forEach(canvas => {
                        canvas.style.width = '';
                        canvas.style.height = '';
                        canvas.style.maxWidth = '';
                        canvas.style.transform = '';
                    });

                    // 恢复设备旋转容器的样式
                    const rotateContainers = document.querySelectorAll('.tablet-rotate, .laptop-rotate');
                    rotateContainers.forEach(container => {
                        container.style.transform = '';
                        container.style.width = '';
                        container.style.overflow = '';
                    });
                }
            }

            // 新增：头像超清下载按钮事件监听器
            const downloadAvatarHDButton = document.getElementById('downloadAvatarHDButton');
            if (downloadAvatarHDButton) {
                downloadAvatarHDButton.addEventListener('click', async function() {
                    // 检查下载权限
                    const hasPermission = await checkDownloadPermission('avatar_hd');
                    if (!hasPermission) {
                        return;
                    }
                    
                    if (!currentWallpaper || !currentWallpaper.complete || currentWallpaper.naturalWidth === 0) {
                        alert('壁纸未加载完成，无法下载！');
                        return;
                    }
                    
                    const originalText = downloadAvatarHDButton.innerHTML;
                    downloadAvatarHDButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> 下载中...';
                    downloadAvatarHDButton.disabled = true;
                    
                    try {
                        // 创建高分辨率画布 (600x600)
                        const avatarCanvas = document.createElement('canvas');
                        avatarCanvas.width = 600;
                        avatarCanvas.height = 600;
                        const avatarCtx = avatarCanvas.getContext('2d');
                        
                        // 绘制方形头像
                        avatarCtx.save();
                        avatarCtx.beginPath();
                        avatarCtx.roundRect(0, 0, 600, 600, 40); // 圆角方形
                        avatarCtx.clip();
                        
                        // 计算图片缩放和位置
                        const scale = Math.max(600 / currentWallpaper.width, 600 / currentWallpaper.height);
                        const drawWidth = currentWallpaper.width * scale;
                        const drawHeight = currentWallpaper.height * scale;
                        const drawX = (600 - drawWidth) / 2;
                        const drawY = (600 - drawHeight) / 2 + (yOffset || 0) * scale;
                        
                        avatarCtx.drawImage(currentWallpaper, drawX, drawY, drawWidth, drawHeight);
                        avatarCtx.restore();
                        
                        // 下载方形头像
                        const squareSuccess = await exportCanvasAsImage(avatarCanvas, '头像-方形-超清.png');
                        
                        // 创建圆形头像
                        const circleCanvas = document.createElement('canvas');
                        circleCanvas.width = 600;
                        circleCanvas.height = 600;
                        const circleCtx = circleCanvas.getContext('2d');
                        
                        circleCtx.save();
                        circleCtx.beginPath();
                        circleCtx.arc(300, 300, 300, 0, Math.PI * 2);
                        circleCtx.clip();
                        circleCtx.drawImage(currentWallpaper, drawX, drawY, drawWidth, drawHeight);
                        circleCtx.restore();
                        
                        // 下载圆形头像
                        const circleSuccess = await exportCanvasAsImage(circleCanvas, '头像-圆形-超清.png');
                        
                        if (squareSuccess && circleSuccess) {
                            // 记录下载
                            await recordDownload('avatar_hd');
                            downloadAvatarHDButton.innerHTML = '<i class="fa-solid fa-check mr-2"></i> 下载成功';
                            downloadAvatarHDButton.classList.remove('bg-green-500');
                            downloadAvatarHDButton.classList.add('bg-green-600');
                        } else {
                            downloadAvatarHDButton.innerHTML = '<i class="fa-solid fa-times mr-2"></i> 下载失败';
                            downloadAvatarHDButton.classList.remove('bg-green-500');
                            downloadAvatarHDButton.classList.add('bg-red-500');
                        }
                        
                        setTimeout(() => {
                            downloadAvatarHDButton.innerHTML = originalText;
                            downloadAvatarHDButton.classList.remove('bg-green-600', 'bg-red-500');
                            downloadAvatarHDButton.classList.add('bg-green-500');
                            downloadAvatarHDButton.disabled = false;
                        }, 2000);
                        
                    } catch (error) {
                        console.error('下载失败:', error);
                        downloadAvatarHDButton.innerHTML = '<i class="fa-solid fa-times mr-2"></i> 下载失败';
                        downloadAvatarHDButton.classList.remove('bg-green-500');
                        downloadAvatarHDButton.classList.add('bg-red-500');
                        setTimeout(() => {
                            downloadAvatarHDButton.innerHTML = originalText;
                            downloadAvatarHDButton.classList.remove('bg-red-500');
                            downloadAvatarHDButton.classList.add('bg-green-500');
                            downloadAvatarHDButton.disabled = false;
                        }, 2000);
                    }
                });
            }
            
            // 监听窗口大小变化
            window.addEventListener('resize', updateCanvasSize);
            // 初始化时执行一次
            updateCanvasSize();
            
            // 页面加载时初始化下载次数显示
            checkUserPermissions().then(() => {
                console.log('用户权限检查完成，下载次数显示已更新');
            }).catch(error => {
                console.error('初始化用户权限失败:', error);
                // 显示默认状态（隐藏下载次数显示）
                const quotaDisplayEl = document.getElementById('download-quota-display');
                if (quotaDisplayEl) {
                    quotaDisplayEl.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
    
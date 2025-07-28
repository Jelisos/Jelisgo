<?php
// 加载系统设置
$settingsFile = 'config/system_settings.json';
if (file_exists($settingsFile)) {
    $settingsData = json_decode(file_get_contents($settingsFile), true);
    $settings = [
        'site_name' => $settingsData['basic']['site_name'] ?? '壁纸喵 ° 不吃鱼',
        'site_subtitle' => $settingsData['basic']['site_subtitle'] ?? '精美壁纸分享平台',
        'site_description' => $settingsData['seo']['description'] ?? '发现和分享精美壁纸',
        'seo_keywords' => $settingsData['seo']['keywords'] ?? '壁纸,高清壁纸,桌面壁纸'
    ];
} else {
    $settings = [
        'site_name' => '壁纸喵 ° 不吃鱼',
        'site_subtitle' => '精美壁纸分享平台',
        'site_description' => '发现和分享精美壁纸',
        'seo_keywords' => '壁纸,高清壁纸,桌面壁纸'
    ];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>使用条款 - <?php echo htmlspecialchars($settings['site_name']); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($settings['site_name']); ?>的使用条款和服务协议">
    <meta name="keywords" content="使用条款,服务协议,<?php echo htmlspecialchars($settings['seo_keywords']); ?>">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1A73E8',
                        secondary: '#4285F4',
                        accent: '#8AB4F8',
                        neutral: '#F0F2F5',
                        'neutral-dark': '#E0E3E9'
                    }
                }
            }
        }
    </script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- 自定义样式 -->
    <link rel="stylesheet" href="static/css/main.css">
    <link rel="stylesheet" href="static/css/svg-icons.css">
    <link rel="stylesheet" href="static/css/inter.css">
</head>
<body class="bg-neutral min-h-screen">
    <!-- 导航栏 -->
    <nav class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo和站点名称 -->
                <div class="flex items-center">
                    <a href="index.php" class="flex items-center space-x-3">
                        <img src="Jelisgo.ico" alt="图片" class="w-6 h-6 text-primary" />
                        <span class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($settings['site_name']); ?></span>
                    </a>
                </div>
                
                <!-- 导航链接 -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="index.php" class="text-gray-600 hover:text-primary transition-colors">首页</a>
                    <a href="about.php" class="text-gray-600 hover:text-primary transition-colors">关于我们</a>
                    <a href="terms.php" class="text-primary font-medium">使用条款</a>
                    <a href="privacy.php" class="text-gray-600 hover:text-primary transition-colors">隐私政策</a>
                </div>
                
                <!-- 移动端菜单按钮 -->
                <div class="md:hidden">
                    <button id="mobile-menu-toggle" class="text-gray-600 hover:text-primary">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- 移动端菜单 -->
        <div id="mobile-menu" class="hidden md:hidden bg-white border-t border-gray-200">
            <div class="px-4 py-2 space-y-1">
                <a href="index.php" class="block px-3 py-2 text-gray-600 hover:text-primary transition-colors">首页</a>
                <a href="about.php" class="block px-3 py-2 text-gray-600 hover:text-primary transition-colors">关于我们</a>
                <a href="terms.php" class="block px-3 py-2 text-primary font-medium">使用条款</a>
                <a href="privacy.php" class="block px-3 py-2 text-gray-600 hover:text-primary transition-colors">隐私政策</a>
            </div>
        </div>
    </nav>

    <!-- 主要内容 -->
    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- 页面标题 -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">使用条款</h1>
            <p class="text-xl text-gray-600">请仔细阅读以下条款和条件</p>
            <p class="text-sm text-gray-500 mt-2">最后更新时间：2024年1月1日</p>
        </div>

        <!-- 内容区域 -->
        <div class="bg-white rounded-xl shadow-sm p-8 space-y-8">
            <!-- 接受条款 -->
            <section>
                <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-handshake text-primary mr-3"></i>
                    接受条款
                </h2>
                <p class="text-gray-700 leading-relaxed">
                    欢迎使用<?php echo htmlspecialchars($settings['site_name']); ?>！通过访问和使用我们的网站，您同意遵守并受以下使用条款的约束。
                    如果您不同意这些条款，请不要使用我们的服务。
                </p>
            </section>

            <!-- 服务描述 -->
            <section>
                <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-cogs text-primary mr-3"></i>
                    服务描述
                </h2>
                <p class="text-gray-700 leading-relaxed mb-4">
                    <?php echo htmlspecialchars($settings['site_name']); ?>是一个壁纸分享平台，为用户提供以下服务：
                </p>
                <ul class="list-disc list-inside text-gray-700 space-y-2 ml-4">
                    <li>浏览和下载高质量壁纸</li>
                    <li>用户注册和个人账户管理</li>
                    <li>壁纸收藏和个性化推荐</li>
                </ul>
            </section>

            <!-- 用户责任 -->
            <section>
                <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-user-check text-primary mr-3"></i>
                    用户责任
                </h2>
                <div class="space-y-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">账户安全</h3>
                        <p class="text-gray-700">您有责任保护您的账户信息，包括用户名和密码。不得与他人分享您的登录凭据。</p>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">合法使用</h3>
                        <p class="text-gray-700">您同意仅将我们的服务用于合法目的，不得从事任何可能损害网站或其他用户的活动。</p>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">内容规范</h3>
                        <p class="text-gray-700">如果您上传或分享内容，必须确保内容不侵犯他人权利，不包含有害或非法材料。</p>
                    </div>
                </div>
            </section>

            <!-- 知识产权 -->
            <section>
                <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-copyright text-primary mr-3"></i>
                    知识产权
                </h2>
                <div class="space-y-4">
                    <p class="text-gray-700 leading-relaxed">
                        网站上的所有内容，包括但不限于文本、图像、图形、标志、软件和其他材料，均受版权、商标和其他知识产权法律保护。
                    </p>
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    <strong>重要提醒：</strong>下载的壁纸仅供个人使用，不得用于商业目的。如需商业使用，请确保获得相应授权。
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 禁止行为 -->
            <section>
                <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-ban text-red-500 mr-3"></i>
                    禁止行为
                </h2>
                <p class="text-gray-700 mb-4">在使用我们的服务时，您不得：</p>
                <ul class="list-disc list-inside text-gray-700 space-y-2 ml-4">
                    <li>上传包含病毒、恶意软件或有害代码的内容</li>
                    <li>尝试未经授权访问我们的系统或其他用户的账户</li>
                    <li>发布虚假、误导性或欺诈性信息</li>
                    <li>侵犯他人的隐私权或知识产权</li>
                    <li>进行垃圾邮件发送或其他形式的滥用行为</li>
                    <li>干扰或破坏网站的正常运行</li>
                </ul>
            </section>

            <!-- 免责声明 -->
            <section>
                <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-shield-alt text-primary mr-3"></i>
                    免责声明
                </h2>
                <div class="space-y-4">
                    <p class="text-gray-700 leading-relaxed">
                        我们的服务按"现状"提供，不提供任何明示或暗示的保证。我们不保证服务的连续性、准确性或完整性。
                    </p>
                    <p class="text-gray-700 leading-relaxed">
                        在法律允许的最大范围内，我们不对因使用或无法使用我们的服务而产生的任何直接、间接、偶然或后果性损害承担责任。
                    </p>
                </div>
            </section>

            <!-- 条款修改 -->
            <section>
                <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-edit text-primary mr-3"></i>
                    条款修改
                </h2>
                <p class="text-gray-700 leading-relaxed">
                    我们保留随时修改这些使用条款的权利。修改后的条款将在网站上公布，并自公布之日起生效。
                    继续使用我们的服务即表示您接受修改后的条款。
                </p>
            </section>

            <!-- 联系信息 -->
            <section>
                <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-envelope text-primary mr-3"></i>
                    联系我们
                </h2>
                <p class="text-gray-700 leading-relaxed mb-4">
                    如果您对这些使用条款有任何疑问或需要澄清，请通过以下方式联系我们：
                </p>
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-center space-x-3 mb-2">
                        <i class="fas fa-envelope text-gray-500"></i>
                        <span class="text-gray-700">邮箱：3030275630@qq.com</span>
                    </div>
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-clock text-gray-500"></i>
                        <span class="text-gray-700">工作时间：周一至周五 9:00-18:00</span>
                    </div>
                </div>
            </section>

            <!-- 快速导航 -->
            <section class="border-t border-gray-200 pt-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">相关页面</h3>
                <div class="flex flex-wrap gap-4">
                    <a href="privacy.php" class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                        <i class="fas fa-shield-alt mr-2"></i>
                        隐私政策
                    </a>
                    <a href="about.php" class="inline-flex items-center px-4 py-2 bg-secondary text-white rounded-lg hover:bg-secondary/90 transition-colors">
                        <i class="fas fa-info-circle mr-2"></i>
                        关于我们
                    </a>
                    <a href="index.php" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                        <i class="fas fa-home mr-2"></i>
                        返回首页
                    </a>
                </div>
            </section>
        </div>
    </main>

    <!-- 页脚 -->
    <footer class="bg-white border-t border-gray-200 mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="text-center text-gray-600">
                <p>&copy; 2024 <?php echo htmlspecialchars($settings['site_name']); ?>. 保留所有权利。</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script>
        // 移动端菜单切换
        document.getElementById('mobile-menu-toggle').addEventListener('click', function() {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('hidden');
        });
    </script>
</body>
</html>
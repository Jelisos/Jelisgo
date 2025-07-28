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
    <title>隐私政策 - <?php echo htmlspecialchars($settings['site_name']); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($settings['site_name']); ?>的隐私政策和数据保护说明">
    <meta name="keywords" content="隐私政策,数据保护,<?php echo htmlspecialchars($settings['seo_keywords']); ?>">
    
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
                    <a href="terms.php" class="text-gray-600 hover:text-primary transition-colors">使用条款</a>
                    <a href="privacy.php" class="text-primary font-medium">隐私政策</a>
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
                <a href="terms.php" class="block px-3 py-2 text-gray-600 hover:text-primary transition-colors">使用条款</a>
                <a href="privacy.php" class="block px-3 py-2 text-primary font-medium">隐私政策</a>
            </div>
        </div>
    </nav>

    <!-- 主要内容 -->
    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- 页面标题 -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">隐私政策</h1>
            <p class="text-xl text-gray-600">我们如何收集、使用和保护您的个人信息</p>
            <p class="text-sm text-gray-500 mt-2">最后更新时间：2024年1月1日</p>
        </div>

        <!-- 内容区域 -->
        <div class="bg-white rounded-xl shadow-sm p-8 space-y-8">
            <!-- 概述 -->
            <section>
                <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-shield-alt text-primary mr-3"></i>
                    隐私保护承诺
                </h2>
                <p class="text-gray-700 leading-relaxed">
                    <?php echo htmlspecialchars($settings['site_name']); ?>非常重视您的隐私权。本隐私政策说明了我们如何收集、使用、存储和保护您的个人信息。
                    我们承诺按照本政策和适用的法律法规处理您的个人信息。
                </p>
            </section>

            <!-- 信息收集 -->
            <section>
                <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-database text-primary mr-3"></i>
                    我们收集的信息
                </h2>
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">账户信息</h3>
                        <ul class="list-disc list-inside text-gray-700 space-y-1 ml-4">
                            <li>用户名和密码</li>
                            <li>电子邮件地址</li>
                            <li>个人资料信息（如头像、昵称等）</li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">使用信息</h3>
                        <ul class="list-disc list-inside text-gray-700 space-y-1 ml-4">
                            <li>浏览历史和搜索记录</li>
                            <li>下载和收藏的壁纸</li>
                            <li>设备信息和IP地址</li>
                            <li>访问时间和频率</li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">技术信息</h3>
                        <ul class="list-disc list-inside text-gray-700 space-y-1 ml-4">
                            <li>浏览器类型和版本</li>
                            <li>操作系统信息</li>
                            <li>屏幕分辨率</li>
                            <li>Cookie和类似技术</li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- 信息使用 -->
            <section>
                <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-cogs text-primary mr-3"></i>
                    信息使用目的
                </h2>
                <p class="text-gray-700 mb-4">我们收集您的信息用于以下目的：</p>
                <div class="grid md:grid-cols-2 gap-6">
                    <div class="bg-blue-50 rounded-lg p-4">
                        <h3 class="font-semibold text-blue-900 mb-2 flex items-center">
                            <i class="fas fa-user-cog text-blue-600 mr-2"></i>
                            服务提供
                        </h3>
                        <ul class="text-blue-800 text-sm space-y-1">
                            <li>• 创建和管理用户账户</li>
                            <li>• 提供个性化内容推荐</li>
                            <li>• 处理下载和收藏请求</li>
                        </ul>
                    </div>
                    <div class="bg-green-50 rounded-lg p-4">
                        <h3 class="font-semibold text-green-900 mb-2 flex items-center">
                            <i class="fas fa-chart-line text-green-600 mr-2"></i>
                            服务改进
                        </h3>
                        <ul class="text-green-800 text-sm space-y-1">
                            <li>• 分析用户行为和偏好</li>
                            <li>• 优化网站性能和功能</li>
                            <li>• 开发新功能和服务</li>
                        </ul>
                    </div>
                    <div class="bg-purple-50 rounded-lg p-4">
                        <h3 class="font-semibold text-purple-900 mb-2 flex items-center">
                            <i class="fas fa-shield-alt text-purple-600 mr-2"></i>
                            安全保障
                        </h3>
                        <ul class="text-purple-800 text-sm space-y-1">
                            <li>• 防止欺诈和滥用行为</li>
                            <li>• 维护网站安全</li>
                            <li>• 遵守法律法规要求</li>
                        </ul>
                    </div>
                    <div class="bg-orange-50 rounded-lg p-4">
                        <h3 class="font-semibold text-orange-900 mb-2 flex items-center">
                            <i class="fas fa-envelope text-orange-600 mr-2"></i>
                            沟通联系
                        </h3>
                        <ul class="text-orange-800 text-sm space-y-1">
                            <li>• 发送重要通知和更新</li>
                            <li>• 回应用户询问和反馈</li>
                            <li>• 提供客户支持服务</li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- 信息共享 -->
            <section>
                <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-share-alt text-primary mr-3"></i>
                    信息共享和披露
                </h2>
                <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-red-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700">
                                <strong>重要声明：</strong>我们不会出售、租赁或以其他方式向第三方提供您的个人信息，除非获得您的明确同意或法律要求。
                            </p>
                        </div>
                    </div>
                </div>
                <p class="text-gray-700 mb-4">在以下情况下，我们可能会共享您的信息：</p>
                <ul class="list-disc list-inside text-gray-700 space-y-2 ml-4">
                    <li>获得您的明确同意</li>
                    <li>法律法规要求或政府部门要求</li>
                    <li>保护我们的权利、财产或安全</li>
                    <li>与可信的服务提供商合作（如云存储服务）</li>
                    <li>业务转让或合并情况下</li>
                </ul>
            </section>

            <!-- Cookie政策 -->
            <section>
                <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-cookie-bite text-primary mr-3"></i>
                    Cookie和跟踪技术
                </h2>
                <div class="space-y-4">
                    <p class="text-gray-700 leading-relaxed">
                        我们使用Cookie和类似技术来改善您的浏览体验、分析网站使用情况并提供个性化服务。
                    </p>
                    <div class="grid md:grid-cols-3 gap-4">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="font-semibold text-gray-800 mb-2">必要Cookie</h3>
                            <p class="text-sm text-gray-600">确保网站基本功能正常运行，无法禁用。</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="font-semibold text-gray-800 mb-2">功能Cookie</h3>
                            <p class="text-sm text-gray-600">记住您的偏好设置，提供个性化体验。</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="font-semibold text-gray-800 mb-2">分析Cookie</h3>
                            <p class="text-sm text-gray-600">帮助我们了解网站使用情况，改进服务质量。</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 数据安全 -->
            <section>
                <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-lock text-primary mr-3"></i>
                    数据安全保护
                </h2>
                <p class="text-gray-700 mb-4">我们采取多种安全措施保护您的个人信息：</p>
                <div class="grid md:grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <div class="flex items-start space-x-3">
                            <i class="fas fa-shield-alt text-green-500 mt-1"></i>
                            <div>
                                <h3 class="font-semibold text-gray-800">技术保护</h3>
                                <p class="text-sm text-gray-600">SSL加密传输、防火墙保护、安全存储</p>
                            </div>
                        </div>
                        <div class="flex items-start space-x-3">
                            <i class="fas fa-user-shield text-blue-500 mt-1"></i>
                            <div>
                                <h3 class="font-semibold text-gray-800">访问控制</h3>
                                <p class="text-sm text-gray-600">严格的权限管理、员工培训、定期审计</p>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div class="flex items-start space-x-3">
                            <i class="fas fa-sync-alt text-purple-500 mt-1"></i>
                            <div>
                                <h3 class="font-semibold text-gray-800">定期更新</h3>
                                <p class="text-sm text-gray-600">系统安全更新、漏洞修复、监控预警</p>
                            </div>
                        </div>
                        <div class="flex items-start space-x-3">
                            <i class="fas fa-backup text-orange-500 mt-1"></i>
                            <div>
                                <h3 class="font-semibold text-gray-800">数据备份</h3>
                                <p class="text-sm text-gray-600">定期备份、灾难恢复、数据完整性检查</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 用户权利 -->
            <section>
                <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-user-check text-primary mr-3"></i>
                    您的权利
                </h2>
                <p class="text-gray-700 mb-4">根据适用的隐私法律，您享有以下权利：</p>
                <div class="space-y-4">
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-800 mb-2 flex items-center">
                            <i class="fas fa-eye text-blue-500 mr-2"></i>
                            访问权
                        </h3>
                        <p class="text-gray-600 text-sm">您有权了解我们收集了您的哪些个人信息以及如何使用这些信息。</p>
                    </div>
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-800 mb-2 flex items-center">
                            <i class="fas fa-edit text-green-500 mr-2"></i>
                            更正权
                        </h3>
                        <p class="text-gray-600 text-sm">如果您的个人信息不准确或不完整，您有权要求我们更正或补充。</p>
                    </div>
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-800 mb-2 flex items-center">
                            <i class="fas fa-trash text-red-500 mr-2"></i>
                            删除权
                        </h3>
                        <p class="text-gray-600 text-sm">在特定情况下，您有权要求我们删除您的个人信息。</p>
                    </div>
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-800 mb-2 flex items-center">
                            <i class="fas fa-download text-purple-500 mr-2"></i>
                            数据可携权
                        </h3>
                        <p class="text-gray-600 text-sm">您有权以结构化、常用和机器可读的格式获取您的个人信息。</p>
                    </div>
                </div>
            </section>

            <!-- 政策更新 -->
            <section>
                <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-sync-alt text-primary mr-3"></i>
                    政策更新
                </h2>
                <p class="text-gray-700 leading-relaxed">
                    我们可能会不时更新本隐私政策。重大变更将通过网站通知或电子邮件的方式告知您。
                    我们建议您定期查看本政策，以了解我们如何保护您的信息。
                </p>
            </section>

            <!-- 联系我们 -->
            <section>
                <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-envelope text-primary mr-3"></i>
                    联系我们
                </h2>
                <p class="text-gray-700 leading-relaxed mb-4">
                    如果您对本隐私政策有任何疑问，或希望行使您的权利，请通过以下方式联系我们：
                </p>
                <div class="bg-gray-50 rounded-lg p-6">
                    <div class="grid md:grid-cols-2 gap-4">
                        <div class="space-y-3">
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-envelope text-gray-500"></i>
                                <span class="text-gray-700">3030275630@qq.com</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-clock text-gray-500"></i>
                                <span class="text-gray-700">工作时间：周一至周五 9:00-18:00</span>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-reply text-gray-500"></i>
                                <span class="text-gray-700">我们将在3个工作日内回复</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-language text-gray-500"></i>
                                <span class="text-gray-700">支持中文和英文咨询</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 快速导航 -->
            <section class="border-t border-gray-200 pt-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">相关页面</h3>
                <div class="flex flex-wrap gap-4">
                    <a href="terms.php" class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                        <i class="fas fa-file-contract mr-2"></i>
                        使用条款
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
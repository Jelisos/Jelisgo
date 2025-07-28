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
    <title>关于我们 - <?php echo htmlspecialchars($settings['site_name']); ?></title>
    <meta name="description" content="了解<?php echo htmlspecialchars($settings['site_name']); ?>的使命、愿景和团队信息">
    <meta name="keywords" content="关于我们,<?php echo htmlspecialchars($settings['seo_keywords']); ?>">
    
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
                    <a href="about.php" class="text-primary font-medium">关于我们</a>
                    <a href="terms.php" class="text-gray-600 hover:text-primary transition-colors">使用条款</a>
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
                <a href="about.php" class="block px-3 py-2 text-primary font-medium">关于我们</a>
                <a href="terms.php" class="block px-3 py-2 text-gray-600 hover:text-primary transition-colors">使用条款</a>
                <a href="privacy.php" class="block px-3 py-2 text-gray-600 hover:text-primary transition-colors">隐私政策</a>
            </div>
        </div>
    </nav>

    <!-- 主要内容 -->
    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- 页面标题 -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">关于我们</h1>
            <p class="text-xl text-gray-600">了解我们的使命、愿景和价值观</p>
        </div>

        <!-- 内容区域 -->
        <div class="space-y-12">
            <!-- 我们的使命 -->
            <section class="bg-white rounded-xl shadow-sm p-8">
                <div class="flex items-center mb-6">
                    <div class="bg-primary/10 p-3 rounded-lg mr-4">
                        <i class="fas fa-bullseye text-primary text-2xl"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">我们的使命</h2>
                </div>
                <p class="text-gray-700 leading-relaxed text-lg">
                    我们致力于为用户提供最优质的壁纸分享平台，让每个人都能轻松找到心仪的高清壁纸。
                    通过精心策划的内容和用户友好的界面，我们希望为您的数字生活增添美感和个性。
                </p>
            </section>

            <!-- 我们的愿景 -->
            <section class="bg-white rounded-xl shadow-sm p-8">
                <div class="flex items-center mb-6">
                    <div class="bg-secondary/10 p-3 rounded-lg mr-4">
                        <i class="fas fa-eye text-secondary text-2xl"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">我们的愿景</h2>
                </div>
                <p class="text-gray-700 leading-relaxed text-lg">
                    成为全球领先的壁纸分享社区，连接创作者和用户，推动数字艺术的发展和传播。
                    我们相信美好的视觉体验能够激发创造力，提升生活品质。
                </p>
            </section>

            <!-- 核心价值观 -->
            <section class="bg-white rounded-xl shadow-sm p-8">
                <div class="flex items-center mb-6">
                    <div class="bg-accent/10 p-3 rounded-lg mr-4">
                        <i class="fas fa-heart text-accent text-2xl"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">核心价值观</h2>
                </div>
                <div class="grid md:grid-cols-2 gap-6">
                    <div class="flex items-start space-x-3">
                        <div class="bg-primary/10 p-2 rounded-lg mt-1">
                            <i class="fas fa-star text-primary"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 mb-2">品质至上</h3>
                            <p class="text-gray-600">我们只提供高质量的壁纸内容，确保每一张图片都经过精心筛选。</p>
                        </div>
                    </div>
                    <div class="flex items-start space-x-3">
                        <div class="bg-primary/10 p-2 rounded-lg mt-1">
                            <i class="fas fa-users text-primary"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 mb-2">用户为本</h3>
                            <p class="text-gray-600">用户体验是我们设计和开发的核心考量，持续优化产品功能。</p>
                        </div>
                    </div>
                    <div class="flex items-start space-x-3">
                        <div class="bg-primary/10 p-2 rounded-lg mt-1">
                            <i class="fas fa-shield-alt text-primary"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 mb-2">安全可靠</h3>
                            <p class="text-gray-600">保护用户隐私和数据安全，提供稳定可靠的服务。</p>
                        </div>
                    </div>
                    <div class="flex items-start space-x-3">
                        <div class="bg-primary/10 p-2 rounded-lg mt-1">
                            <i class="fas fa-lightbulb text-primary"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 mb-2">持续创新</h3>
                            <p class="text-gray-600">不断探索新技术和新功能，为用户带来更好的体验。</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 联系我们 -->
            <section class="bg-white rounded-xl shadow-sm p-8">
                <div class="flex items-center mb-6">
                    <div class="bg-green-100 p-3 rounded-lg mr-4">
                        <i class="fas fa-envelope text-green-600 text-2xl"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">联系我们</h2>
                </div>
                <div class="space-y-8">
                    <!-- 联系方式 -->
                    <div class="grid md:grid-cols-2 gap-8">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">联系方式</h3>
                            <p class="text-gray-700 leading-relaxed mb-6">
                                如果您有任何问题、建议或合作意向，欢迎随时与我们联系。
                                我们重视每一位用户的反馈，并将持续改进我们的服务。
                            </p>
                            <div class="space-y-4">
                                <div class="flex items-center space-x-3 p-3 bg-blue-50 rounded-lg">
                                    <i class="fas fa-envelope text-blue-600"></i>
                                    <div>
                                        <div class="font-medium text-gray-900">邮箱联系</div>
                                        <div class="text-gray-700">3030275630@qq.com</div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3 p-3 bg-green-50 rounded-lg">
                                    <i class="fas fa-comments text-green-600"></i>
                                    <div>
                                        <div class="font-medium text-gray-900">在线客服</div>
                                        <div class="text-gray-700">QQ: 3030275630</div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3 p-3 bg-purple-50 rounded-lg">
                                    <i class="fab fa-weixin text-purple-600"></i>
                                    <div>
                                        <div class="font-medium text-gray-900">微信客服</div>
                                        <div class="text-gray-700">Jelisos</div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3 p-3 bg-orange-50 rounded-lg">
                                    <i class="fas fa-clock text-orange-600"></i>
                                    <div>
                                        <div class="font-medium text-gray-900">服务时间</div>
                                        <div class="text-gray-700">周一至周日 9:00-22:00</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">在线留言</h3>
                            <form id="contact-form" class="space-y-4">
                                <div>
                                    <label for="contact-name" class="block text-sm font-medium text-gray-700 mb-1">姓名</label>
                                    <input type="text" id="contact-name" name="name" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="请输入您的姓名" required>
                                </div>
                                <div>
                                    <label for="contact-email" class="block text-sm font-medium text-gray-700 mb-1">邮箱</label>
                                    <input type="email" id="contact-email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="请输入您的邮箱" required>
                                </div>
                                <div>
                                    <label for="contact-subject" class="block text-sm font-medium text-gray-700 mb-1">主题</label>
                                    <select id="contact-subject" name="subject" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" required>
                                        <option value="">请选择咨询类型</option>
                                        <option value="general">一般咨询</option>
                                        <option value="technical">技术支持</option>
                                        <option value="cooperation">商务合作</option>
                                        <option value="feedback">意见反馈</option>
                                        <option value="report">举报投诉</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="contact-message" class="block text-sm font-medium text-gray-700 mb-1">留言内容</label>
                                    <textarea id="contact-message" name="message" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="请详细描述您的问题或建议..." required></textarea>
                                </div>
                                <button type="submit" class="w-full bg-primary text-white py-2 px-4 rounded-lg hover:bg-primary/90 transition-colors flex items-center justify-center">
                                    <i class="fas fa-paper-plane mr-2"></i>
                                    发送留言
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- 常见问题 -->
                    <div class="border-t border-gray-200 pt-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-6">常见问题</h3>
                        <div class="grid md:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <h4 class="font-medium text-gray-900 mb-2">如何下载壁纸？</h4>
                                    <p class="text-sm text-gray-600">点击壁纸进入详情页，然后点击"高清下载"按钮即可下载原图。</p>
                                </div>
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <h4 class="font-medium text-gray-900 mb-2">如何收藏壁纸？</h4>
                                    <p class="text-sm text-gray-600">需要先注册登录账户，然后在壁纸详情页点击"收藏"按钮。</p>
                                </div>
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <h4 class="font-medium text-gray-900 mb-2">壁纸可以商用吗？</h4>
                                    <p class="text-sm text-gray-600">大部分壁纸仅供个人使用，商用请查看具体壁纸的版权说明。</p>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <h4 class="font-medium text-gray-900 mb-2">如何上传壁纸？</h4>
                                    <p class="text-sm text-gray-600">注册登录后，点击页面上的"上传"按钮，按照提示上传您的作品。</p>
                                </div>
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <h4 class="font-medium text-gray-900 mb-2">忘记密码怎么办？</h4>
                                    <p class="text-sm text-gray-600">在登录页面点击"忘记密码"，输入邮箱后按提示重置密码。</p>
                                </div>
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <h4 class="font-medium text-gray-900 mb-2">如何举报不当内容？</h4>
                                    <p class="text-sm text-gray-600">在壁纸详情页点击举报按钮，或通过邮箱联系我们。</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 快速链接 -->
                    <div class="border-t border-gray-200 pt-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">快速链接</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <a href="terms.php" class="flex items-center justify-center p-3 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition-colors">
                                <i class="fas fa-file-contract mr-2"></i>
                                使用条款
                            </a>
                            <a href="privacy.php" class="flex items-center justify-center p-3 bg-green-50 text-green-700 rounded-lg hover:bg-green-100 transition-colors">
                                <i class="fas fa-shield-alt mr-2"></i>
                                隐私政策
                            </a>
                            <a href="index.php" class="flex items-center justify-center p-3 bg-purple-50 text-purple-700 rounded-lg hover:bg-purple-100 transition-colors">
                                <i class="fas fa-home mr-2"></i>
                                返回首页
                            </a>
                            <!--<a href="upload_wallpaper.php" class="flex items-center justify-center p-3 bg-orange-50 text-orange-700 rounded-lg hover:bg-orange-100 transition-colors">
                                <i class="fas fa-upload mr-2"></i>
                                上传壁纸
                            </a>-->
                        </div>
                    </div>
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
        
        // 联系表单处理
        const contactForm = document.getElementById('contact-form');
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // 获取表单数据
            const formData = {
                name: document.getElementById('contact-name').value.trim(),
                email: document.getElementById('contact-email').value.trim(),
                subject: document.getElementById('contact-subject').value,
                message: document.getElementById('contact-message').value.trim()
            };
            
            // 基本验证
            if (!formData.name || !formData.email || !formData.subject || !formData.message) {
                const errorMessage = document.createElement('div');
                errorMessage.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
                errorMessage.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>请填写所有必填字段';
                document.body.appendChild(errorMessage);
                setTimeout(() => errorMessage.remove(), 3000);
                return;
            }
            
            // 邮箱格式验证
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(formData.email)) {
                const errorMessage = document.createElement('div');
                errorMessage.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
                errorMessage.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>请输入正确的邮箱格式';
                document.body.appendChild(errorMessage);
                setTimeout(() => errorMessage.remove(), 3000);
                return;
            }
            
            // 显示提交中状态
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>发送中...';
            submitBtn.disabled = true;
            
            // 发送到后端API
            fetch('/api/contact_form.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 重置表单
                    this.reset();
                    
                    // 显示成功消息
                    const successMessage = document.createElement('div');
                    successMessage.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
                    successMessage.innerHTML = '<i class="fas fa-check-circle mr-2"></i>' + (data.message || '留言发送成功！我们会尽快回复您。');
                    document.body.appendChild(successMessage);
                    setTimeout(() => successMessage.remove(), 3000);
                } else {
                    // 显示错误消息
                    const errorMessage = document.createElement('div');
                    errorMessage.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
                    errorMessage.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>' + (data.message || '发送失败，请稍后重试');
                    document.body.appendChild(errorMessage);
                    setTimeout(() => errorMessage.remove(), 3000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const errorMessage = document.createElement('div');
                errorMessage.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
                errorMessage.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>网络错误，请检查网络连接后重试';
                document.body.appendChild(errorMessage);
                setTimeout(() => errorMessage.remove(), 3000);
            })
            .finally(() => {
                // 恢复按钮状态
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
    </script>
</body>
</html>
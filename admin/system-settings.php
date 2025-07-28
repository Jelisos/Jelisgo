<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统设置 - 管理后台</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="/admin/js/admin-common.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3b82f6',
                        secondary: '#64748b',
                        accent: '#f59e0b',
                        success: '#10b981',
                        warning: '#f59e0b',
                        error: '#ef4444',
                        dark: '#1e293b',
                        light: '#f8fafc'
                    }
                }
            }
        }
    </script>
    <style>
        .sidebar {
            transition: transform 0.3s ease-in-out;
        }
        .sidebar-collapsed {
            transform: translateX(-100%);
        }
        @media (min-width: 768px) {
            .sidebar-collapsed {
                transform: translateX(0);
                width: 4rem;
            }
            .sidebar-collapsed .sidebar-text {
                display: none;
            }
            .sidebar-collapsed .sidebar-icon {
                margin: 0 auto;
            }
        }
        .sidebar-item:hover {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        }
        .form-section {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">
    <!-- 侧边栏 -->
    <div id="sidebar" class="sidebar fixed left-0 top-0 h-full w-64 bg-gradient-to-b from-slate-800 to-slate-900 text-white z-50 shadow-2xl">
        <div class="p-6 border-b border-slate-700">
            <h1 class="sidebar-text text-xl font-bold text-center bg-gradient-to-r from-blue-400 to-purple-400 bg-clip-text text-transparent">
                管理后台
            </h1>
        </div>
        <nav class="mt-6">
            <a href="admin.html" class="sidebar-item flex items-center px-6 py-3 text-gray-300 hover:text-white transition-all duration-200">
                <i class="sidebar-icon fas fa-tachometer-alt w-5 text-center"></i>
                <span class="sidebar-text ml-3">仪表盘</span>
            </a>
            <a href="wallpaper-review.html" class="sidebar-item flex items-center px-6 py-3 text-gray-300 hover:text-white transition-all duration-200">
                <i class="sidebar-icon fas fa-image w-5 text-center"></i>
                <span class="sidebar-text ml-3">壁纸审核</span>
            </a>
            <a href="category-management.html" class="sidebar-item flex items-center px-6 py-3 text-gray-300 hover:text-white transition-all duration-200">
                <i class="sidebar-icon fas fa-tags w-5 text-center"></i>
                <span class="sidebar-text ml-3">分类管理</span>
            </a>
            <a href="user-management.html" class="sidebar-item flex items-center px-6 py-3 text-gray-300 hover:text-white transition-all duration-200">
                <i class="sidebar-icon fas fa-users w-5 text-center"></i>
                <span class="sidebar-text ml-3">用户管理</span>
            </a>
            <a href="exile-management.html" class="sidebar-item flex items-center px-6 py-3 text-gray-300 hover:text-white transition-all duration-200">
                <i class="sidebar-icon fas fa-ban w-5 text-center"></i>
                <span class="sidebar-text ml-3">流放管理</span>
            </a>
            <a href="system-settings.html" class="sidebar-item flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white transition-all duration-200">
                <i class="sidebar-icon fas fa-cog w-5 text-center"></i>
                <span class="sidebar-text ml-3">系统设置</span>
            </a>
            <a href="operation-logs.html" class="sidebar-item flex items-center px-6 py-3 text-gray-300 hover:text-white transition-all duration-200">
                <i class="sidebar-icon fas fa-clipboard-list w-5 text-center"></i>
                <span class="sidebar-text ml-3">操作日志</span>
            </a>
        </nav>
        
        <!-- 管理员信息区域 -->
        <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-slate-700">
            <div class="flex items-center space-x-3">
                <img src="https://picsum.photos/id/64/40/40" alt="管理员头像" class="w-10 h-10 rounded-full border-2 border-primary" id="admin-avatar">
                <div class="flex-1">
                    <div class="font-medium text-white" id="admin-username">超级管理员</div>
                    <div class="text-xs text-gray-400" id="admin-email">admin@example.com</div>
                </div>
                <button class="text-gray-400 hover:text-red-400 transition-colors" id="admin-logout-btn" onclick="handleAdminLogout()">
                    <i class="fa fa-sign-out"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- 主内容区域 -->
    <div id="main-content" class="ml-64 transition-all duration-300">
        <!-- 顶部导航栏 -->
        <header class="bg-white shadow-sm border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <button id="sidebar-toggle" class="md:hidden text-gray-600 hover:text-gray-900">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h2 class="text-2xl font-bold text-gray-800">系统设置</h2>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <button class="flex items-center space-x-2 text-gray-600 hover:text-gray-900" onclick="handleAdminLogout()">
                            <img src="https://via.placeholder.com/32" alt="头像" class="w-8 h-8 rounded-full" id="header-admin-avatar">
                            <span class="hidden md:block" id="header-admin-username">管理员</span>
                            <i class="fas fa-chevron-down text-sm"></i>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- 系统设置内容 -->
        <main class="p-6">
            <div class="max-w-4xl mx-auto space-y-6">
                <!-- 基本设置 -->
                <div class="form-section p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-cog text-blue-600 mr-2"></i>
                        基本设置
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">站点名称</label>
                            <input type="text" name="site_name" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="用于页面标题和OG标签">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">副标题</label>
                            <input type="text" name="site_subtitle" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="显示在标题后的副标题">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">联系邮箱</label>
                            <input type="email" name="contact_email" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="admin@example.com">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">备案号</label>
                            <input type="text" name="icp_number" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="请输入备案号">
                        </div>
                    </div>
                </div>

                <!-- SEO设置 -->
                <div class="form-section p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-search text-orange-600 mr-2"></i>
                        SEO优化设置
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">关键词</label>
                            <input type="text" name="seo_keywords" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="用逗号分隔，如：壁纸,高清壁纸,免费壁纸">
                            <p class="text-xs text-gray-500 mt-1">用于meta keywords标签，提升搜索引擎收录</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">描述</label>
                            <textarea name="seo_description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="网站描述，用于meta description和OG描述"></textarea>
                            <p class="text-xs text-gray-500 mt-1">建议150-160字符，用于搜索结果摘要显示</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">OG图片路径</label>
                            <input type="text" name="seo_og_image" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="/static/images/og-default.svg">
                            <p class="text-xs text-gray-500 mt-1">用于社交媒体分享时的预览图片，建议尺寸1200x630px</p>
                        </div>
                    </div>
                </div>

                <!-- 上传设置 -->
                <div class="form-section p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-upload text-green-600 mr-2"></i>
                        上传设置
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">最大文件大小 (MB)</label>
                            <input type="number" name="max_file_size" min="1" max="100" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" value="10">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">允许的文件类型</label>
                            <input type="text" name="allowed_file_types" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" value="jpg,jpeg,png,webp">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">图片质量压缩</label>
                            <select name="image_quality" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="high">高质量</option>
                                <option value="medium" selected>中等质量</option>
                                <option value="low">低质量</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">水印设置</label>
                            <div class="flex items-center space-x-4">
                                <label class="flex items-center">
                                    <input type="radio" name="watermark" value="none" class="mr-2">
                                    <span>无水印</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="watermark" value="text" class="mr-2" checked>
                                    <span>文字水印</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="watermark" value="image" class="mr-2">
                                    <span>图片水印</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 安全设置 -->
                <div class="form-section p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-shield-alt text-red-600 mr-2"></i>
                        安全设置
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">登录失败次数限制</label>
                            <input type="number" name="login_fail_limit" min="1" max="20" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" value="5">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">账户锁定时间 (分钟)</label>
                            <input type="number" name="account_lock_time" min="1" max="1440" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" value="30">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">密码最小长度</label>
                            <input type="number" name="min_password_length" min="4" max="32" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" value="4">
                        </div>
                        <!-- 验证码功能暂不实现 -->
                        <div class="md:col-span-2">
                            <p class="text-sm text-gray-500 italic">验证码功能正在开发中，敬请期待...</p>
                        </div>
                    </div>
                </div>

                <!-- 邮件设置 -->
                <div class="form-section p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-envelope text-purple-600 mr-2"></i>
                        邮件设置
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">SMTP服务器</label>
                            <input type="text" name="smtp_server" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" value="smtp.qq.com" placeholder="smtp.example.com">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">SMTP端口</label>
                            <input type="number" name="smtp_port" min="1" max="65535" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" value="465">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">发件人邮箱</label>
                            <input type="email" name="sender_email" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" value="tojelis@qq.com" placeholder="noreply@example.com">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">邮箱密码</label>
                            <input type="password" name="sender_password" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" value="gczcitspymfnbgai" placeholder="请输入邮箱密码">
                        </div>
                        <div class="md:col-span-2">
                            <label class="flex items-center">
                                <input type="checkbox" name="enable_ssl" class="mr-2" checked>
                                <span class="text-sm text-gray-700">启用SSL加密</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- 操作按钮 -->
                <div class="flex justify-end space-x-4">
                    <button class="reset-settings-btn px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        重置
                    </button>
                    <button class="save-settings-btn px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        保存设置
                    </button>
                </div>
            </div>
        </main>
    </div>

    <!-- 引入系统设置JavaScript -->
    <script src="../static/js/system-settings.js"></script>
    
    <script>
        // 侧边栏切换功能
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('main-content');
        const sidebarToggle = document.getElementById('sidebar-toggle');

        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('sidebar-collapsed');
            if (sidebar.classList.contains('sidebar-collapsed')) {
                mainContent.classList.remove('ml-64');
                mainContent.classList.add('ml-0');
            } else {
                mainContent.classList.remove('ml-0');
                mainContent.classList.add('ml-64');
            }
        });

        // 响应式处理
        function handleResize() {
            if (window.innerWidth < 768) {
                sidebar.classList.add('sidebar-collapsed');
                mainContent.classList.remove('ml-64');
                mainContent.classList.add('ml-0');
            } else {
                sidebar.classList.remove('sidebar-collapsed');
                mainContent.classList.remove('ml-0');
                mainContent.classList.add('ml-64');
            }
        }

        window.addEventListener('resize', handleResize);
        handleResize(); // 初始化
    </script>
</body>
</html>
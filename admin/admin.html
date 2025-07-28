<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>壁纸网站管理后台</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="/static/css/font-awesome.min.css" rel="stylesheet">
    <script src="/admin/js/admin-common.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1A73E8',
                        secondary: '#4285F4',
                        accent: '#8AB4F8',
                        neutral: '#F0F2F5',
                        'neutral-dark': '#E0E3E9',
                        dark: '#333333',
                        success: '#34A853',
                        warning: '#FBBC05',
                        danger: '#EA4335',
                        info: '#4285F4'
                    },
                    fontFamily: {
                        inter: ['Inter', 'system-ui', 'sans-serif'],
                    },
                },
            }
        }
    </script>
    <style type="text/tailwindcss">
        @layer utilities {
            .content-auto {
                content-visibility: auto;
            }
            .sidebar-link {
                @apply flex items-center space-x-3 px-4 py-3 rounded-lg transition-all duration-200;
            }
            .sidebar-link.active {
                @apply bg-primary/10 text-primary font-medium;
            }
            .sidebar-link:not(.active) {
                @apply text-gray-600 hover:bg-neutral-dark;
            }
            .table-row-hover {
                @apply hover:bg-gray-50 transition-colors;
            }
            .badge {
                @apply px-2 py-1 rounded-full text-xs font-medium;
            }
            .badge-pending {
                @apply bg-warning/20 text-warning;
            }
            .badge-success {
                @apply bg-success/20 text-success;
            }
            .badge-danger {
                @apply bg-danger/20 text-danger;
            }
            .badge-approved {
                @apply bg-success/20 text-success;
            }
            .badge-rejected {
                @apply bg-danger/20 text-danger;
            }
            
            /* 移动端适配 */
            @media (max-width: 768px) {
                .sidebar {
                    transform: translateX(-100%);
                    transition: transform 0.3s ease;
                }
                .sidebar.open {
                    transform: translateX(0);
                }
                .sidebar-overlay {
                    display: none;
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0, 0, 0, 0.5);
                    z-index: 40;
                }
                .sidebar-overlay.show {
                    display: block;
                }
            }
            
            /* 加载动画 */
            .loading-spinner {
                @apply inline-block w-5 h-5 border-2 border-gray-300 border-t-primary rounded-full animate-spin;
            }
        }
    </style>
</head>
<body class="font-inter bg-neutral text-dark flex h-screen overflow-hidden">
    <!-- 移动端遮罩层 -->
    <div class="sidebar-overlay" id="sidebar-overlay"></div>
    
    <!-- 侧边栏 -->
    <aside class="sidebar w-64 bg-white border-r border-gray-200 flex flex-col h-full transition-all duration-300 fixed md:relative z-50" id="sidebar">
        <div class="p-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <i class="fa fa-picture-o text-primary text-2xl"></i>
                    <span class="text-xl font-bold text-primary">壁纸管理系统</span>
                </div>
                <button class="md:hidden text-gray-500 hover:text-primary" id="close-sidebar">
                    <i class="fa fa-times text-xl"></i>
                </button>
            </div>
        </div>
        
        <nav class="flex-1 overflow-y-auto py-4">
            <div class="px-4 mb-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">主菜单</div>
            <a href="#" class="sidebar-link active">
                <i class="fa fa-tachometer"></i>
                <span>仪表盘</span>
            </a>
            <a href="wallpaper-review.html" class="sidebar-link">
                <i class="fa fa-image"></i>
                <span>用户壁纸审核</span>
                <span class="ml-auto bg-warning text-white text-xs px-2 py-0.5 rounded-full" id="pending-count">0</span>
            </a>
            <a href="category-management.html" class="sidebar-link">
                <i class="fa fa-folder"></i>
                <span>分类管理</span>
            </a>
            <a href="user-management.html" class="sidebar-link">
                <i class="fa fa-users"></i>
                <span>用户管理</span>
            </a>
            <a href="exile-management.html" class="sidebar-link">
                <i class="fa fa-ban"></i>
                <span>流放管理</span>
            </a>

            
            <div class="px-4 mt-8 mb-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">系统设置</div>
            <a href="system-settings.html" class="sidebar-link">
                <i class="fa fa-cog"></i>
                <span>系统设置</span>
            </a>
            <a href="operation-logs.html" class="sidebar-link">
                <i class="fa fa-history"></i>
                <span>操作日志</span>
            </a>
        </nav>
        
        <div class="p-4 border-t border-gray-200">
            <div class="flex items-center space-x-3">
                <img src="https://picsum.photos/id/64/40/40" alt="管理员头像" class="w-10 h-10 rounded-full border-2 border-primary" id="admin-avatar">
                <div class="flex-1">
                    <div class="font-medium" id="admin-username">超级管理员</div>
                    <div class="text-xs text-gray-500" id="admin-email">admin@example.com</div>
                </div>
                <button class="text-gray-500 hover:text-danger transition-colors" id="admin-logout-btn" onclick="handleAdminLogout()">
                    <i class="fa fa-sign-out"></i>
                </button>
            </div>
        </div>
    </aside>

    <!-- 主内容区 -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- 顶部导航 -->
        <header class="bg-white border-b border-gray-200 h-16 flex items-center justify-between px-6">
            <div class="flex items-center">
                <button id="toggle-sidebar" class="mr-4 text-gray-500 hover:text-primary transition-colors md:hidden">
                    <i class="fa fa-bars text-xl"></i>
                </button>
                <h1 class="text-xl font-bold">仪表盘</h1>
            </div>
            
            <div class="flex items-center space-x-4">
                <div class="relative hidden sm:block">
                    <button class="text-gray-500 hover:text-primary transition-colors">
                        <i class="fa fa-bell text-xl"></i>
                        <span class="absolute -top-1 -right-1 bg-danger text-white text-xs w-4 h-4 flex items-center justify-center rounded-full">3</span>
                    </button>
                </div>
                
                <div class="relative hidden sm:block">
                    <button class="text-gray-500 hover:text-primary transition-colors">
                        <i class="fa fa-envelope text-xl"></i>
                        <span class="absolute -top-1 -right-1 bg-primary text-white text-xs w-4 h-4 flex items-center justify-center rounded-full">5</span>
                    </button>
                </div>
                
                <div class="h-8 border-r border-gray-200 mx-1 hidden sm:block"></div>
                
                <div class="flex items-center space-x-2">
                    <img src="https://picsum.photos/id/64/40/40" alt="管理员头像" class="w-8 h-8 rounded-full" id="header-admin-avatar">
                    <span class="font-medium hidden sm:inline" id="header-admin-username">超级管理员</span>
                    <button class="px-3 py-1 text-sm bg-danger text-white rounded-lg hover:bg-danger/90 transition-colors" onclick="handleAdminLogout()">
                        <i class="fa fa-sign-out mr-1"></i> <span class="hidden sm:inline">退出登录</span>
                    </button>
                </div>
            </div>
        </header>
        
        <!-- 内容区域 -->
        <main class="flex-1 overflow-y-auto p-4 md:p-6 bg-neutral">
            <!-- 统计卡片 -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-6 md:mb-8" id="stats-container">
                <div class="bg-white rounded-xl p-4 md:p-6 shadow-sm border border-gray-200 transition-all hover:shadow-md" data-stat="total_wallpapers">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">总壁纸数</p>
                            <h3 class="text-2xl md:text-3xl font-bold mt-1" data-value="0">0</h3>
                            <p class="text-success text-sm mt-2 flex items-center" data-change>
                                <i class="fa fa-arrow-up mr-1"></i> <span data-percent>0%</span> <span class="text-gray-500 ml-1">较上月</span>
                            </p>
                        </div>
                        <div class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-primary/10 flex items-center justify-center text-primary">
                            <i class="fa fa-image text-lg md:text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl p-4 md:p-6 shadow-sm border border-gray-200 transition-all hover:shadow-md" data-stat="pending_reviews">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">待审核</p>
                            <h3 class="text-2xl md:text-3xl font-bold mt-1" data-value="0">0</h3>
                            <p class="text-warning text-sm mt-2 flex items-center" data-change>
                                <i class="fa fa-clock-o mr-1"></i> <span data-percent>0</span> <span class="text-gray-500 ml-1">待处理</span>
                            </p>
                        </div>
                        <div class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-warning/10 flex items-center justify-center text-warning">
                            <i class="fa fa-hourglass-half text-lg md:text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl p-4 md:p-6 shadow-sm border border-gray-200 transition-all hover:shadow-md" data-stat="total_users">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">总用户数</p>
                            <h3 class="text-2xl md:text-3xl font-bold mt-1" data-value="0">0</h3>
                            <p class="text-success text-sm mt-2 flex items-center" data-change>
                                <i class="fa fa-arrow-up mr-1"></i> <span data-percent>0%</span> <span class="text-gray-500 ml-1">较上月</span>
                            </p>
                        </div>
                        <div class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-success/10 flex items-center justify-center text-success">
                            <i class="fa fa-users text-lg md:text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl p-4 md:p-6 shadow-sm border border-gray-200 transition-all hover:shadow-md" data-stat="today_downloads">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">今日下载</p>
                            <h3 class="text-2xl md:text-3xl font-bold mt-1" data-value="0">0</h3>
                            <p class="text-info text-sm mt-2 flex items-center" data-change>
                                <i class="fa fa-download mr-1"></i> <span data-percent>0%</span> <span class="text-gray-500 ml-1">较昨日</span>
                            </p>
                        </div>
                        <div class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-info/10 flex items-center justify-center text-info">
                            <i class="fa fa-download text-lg md:text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 仪表盘内容区域 -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6">
                <!-- 最近活动 -->
                <div class="bg-white rounded-xl p-4 md:p-6 shadow-sm border border-gray-200" id="recent-activities-panel">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-bold">最近活动</h2>
                        <div class="flex space-x-2">
                            <input type="text" id="searchInput" placeholder="搜索壁纸..." 
                                   class="px-3 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <select id="statusFilter" class="px-3 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">全部状态</option>
                                <option value="pending">待审核</option>
                                <option value="approved">已通过</option>
                                <option value="rejected">已拒绝</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- 壁纸审核表格 -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">壁纸</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">标题</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">上传者</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">状态</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">上传时间</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                                </tr>
                            </thead>
                            <tbody id="wallpaperTableBody" class="bg-white divide-y divide-gray-200">
                                <tr id="loadingRow">
                                    <td colspan="6" class="px-6 py-4 text-center">
                                        <div class="flex justify-center items-center">
                                            <div class="loading-spinner"></div>
                                            <span class="ml-2 text-gray-500">加载中...</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- 分页控件 -->
                    <div class="flex items-center justify-between mt-4">
                        <div class="text-sm text-gray-700">
                            显示第 <span id="startItem">0</span> 到 <span id="endItem">0</span> 项，共 <span id="totalItems">0</span> 项
                        </div>
                        <div class="flex space-x-2">
                            <button id="prevBtn" class="px-3 py-1 border border-gray-300 rounded-md text-sm hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                上一页
                            </button>
                            <div id="pageNumbers" class="flex space-x-1"></div>
                            <button id="nextBtn" class="px-3 py-1 border border-gray-300 rounded-md text-sm hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                下一页
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- 系统状态 -->
                <div class="bg-white rounded-xl p-4 md:p-6 shadow-sm border border-gray-200">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-bold">系统状态</h2>
                        <span id="system-overall-status" class="text-success text-sm flex items-center">
                            <i class="fa fa-circle mr-1"></i> <span id="overall-status-text">正常运行</span>
                        </span>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">服务器状态</span>
                            <span id="server-status" class="text-success text-sm">正常</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">数据库连接</span>
                            <span id="database-status" class="text-success text-sm">正常</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">存储空间</span>
                            <span id="storage-status" class="text-warning text-sm">75% 已使用</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">CDN状态</span>
                            <span id="cdn-status" class="text-success text-sm">正常</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 壁纸审核部分 -->
            <section id="wallpaper-review-section" class="hidden">
                <div class="bg-white rounded-xl p-4 md:p-6 shadow-sm border border-gray-200 mb-6">
                    <h2 class="text-lg font-bold mb-4">壁纸审核</h2>
                    <p class="text-gray-600">在这里审核用户上传的壁纸</p>
                </div>
            </section>
            
            <!-- 分类管理部分 -->
            <section id="category-management-section" class="hidden">
                <div class="bg-white rounded-xl p-4 md:p-6 shadow-sm border border-gray-200 mb-6">
                    <h2 class="text-lg font-bold mb-4">分类管理</h2>
                    <p class="text-gray-600">在这里管理壁纸分类</p>
                </div>
            </section>
            
            <!-- 用户管理部分 -->
            <section id="user-management-section" class="hidden">
                <div class="bg-white rounded-xl p-4 md:p-6 shadow-sm border border-gray-200 mb-6">
                    <h2 class="text-lg font-bold mb-4">用户管理</h2>
                    <p class="text-gray-600">在这里管理用户</p>
                </div>
            </section>
            
            <!-- 流放管理部分 -->
            <section id="exile-management-section" class="hidden">
                <div class="bg-white rounded-xl p-4 md:p-6 shadow-sm border border-gray-200 mb-6">
                    <h2 class="text-lg font-bold mb-4">流放管理</h2>
                    <p class="text-gray-600">在这里管理被流放的用户</p>
                </div>
            </section>
            
            <!-- 系统设置部分 -->
            <section id="settings-section" class="hidden">
                <div class="bg-white rounded-xl p-4 md:p-6 shadow-sm border border-gray-200 mb-6">
                    <h2 class="text-lg font-bold mb-4">系统设置</h2>
                    <p class="text-gray-600">在这里管理系统设置</p>
                </div>
            </section>
            
            <!-- 操作日志部分 -->
            <section id="logs-section" class="hidden">
                <div class="bg-white rounded-xl p-4 md:p-6 shadow-sm border border-gray-200 mb-6">
                    <h2 class="text-lg font-bold mb-4">操作日志</h2>
                    <p class="text-gray-600">在这里查看操作日志</p>
                </div>
            </section>
        </main>
    </div>

    <!-- JavaScript 引用 -->
    <script src="../static/js/config.js"></script>
    <script src="../static/js/utils.js"></script>
    <script src="../static/js/modals.js"></script>
    
    <script>
        // 移动端侧边栏控制
        const toggleSidebar = document.getElementById('toggle-sidebar');
        const closeSidebar = document.getElementById('close-sidebar');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebar-overlay');
        
        function openSidebar() {
            sidebar.classList.add('open');
            sidebarOverlay.classList.add('show');
        }
        
        function closeSidebarFn() {
            sidebar.classList.remove('open');
            sidebarOverlay.classList.remove('show');
        }
        
        toggleSidebar?.addEventListener('click', openSidebar);
        closeSidebar?.addEventListener('click', closeSidebarFn);
        sidebarOverlay?.addEventListener('click', closeSidebarFn);
        
        /**
         * 管理后台仪表盘功能类
         * 文件: admin.html
         * 依赖: /api/admin_dashboard.php
         */
        class AdminDashboard {
            constructor() {
                this.refreshInterval = null;
                this.init();
            }
            
            init() {
                this.loadDashboardStats();
                this.loadRecentActivities();
                this.loadSystemStatus();
                
                // 每30秒刷新一次统计数据
                this.refreshInterval = setInterval(() => {
                    this.loadDashboardStats();
                    this.loadSystemStatus();
                }, 30000);
                
                // 页面隐藏时停止刷新，显示时恢复
                document.addEventListener('visibilitychange', () => {
                    if (document.hidden) {
                        if (this.refreshInterval) {
                            clearInterval(this.refreshInterval);
                            this.refreshInterval = null;
                        }
                    } else {
                        if (!this.refreshInterval) {
                            this.refreshInterval = setInterval(() => {
                                this.loadDashboardStats();
                            }, 30000);
                        }
                    }
                });
            }
            
            /**
             * 加载仪表盘统计数据
             */
            async loadDashboardStats() {
                try {
                    const userInfo = JSON.parse(localStorage.getItem('user') || '{}');
                    const userId = userInfo.id;
                    
                    if (!userId) {
                        console.error('[调试-仪表盘] 用户未登录');
                        this.showError('用户未登录');
                        return;
                    }
                    
                    const response = await fetch('/api/admin_dashboard.php?action=stats', {
                        headers: {
                            'Authorization': `Bearer ${userId}`
                        }
                    });
                    
                    // 检查401未授权错误 - 已禁用跳转
                    if (response.status === 401) {
                        console.warn('[调试-仪表盘] 未授权访问，但跳转已禁用');
                        // window.location.href = '/index.html'; // 已禁用
                        // return; // 继续执行
                    }
                    
                    const result = await response.json();
                    
                    if (result.code === 200) {
                        this.updateStatsCards(result.data);
                    } else {
                        console.error('[调试-仪表盘] 获取统计数据失败:', result.msg);
                        if (result.msg === '未登录') {
                            console.warn('[调试-系统状态] 检测到未登录状态，但跳转已禁用');
                            // window.location.href = '/index.html'; // 已禁用
                            // return; // 继续执行
                        }
                        this.showError('获取统计数据失败: ' + result.msg);
                    }
                } catch (error) {
                    console.error('[调试-仪表盘] 请求失败:', error);
                    this.showError('网络请求失败，请检查网络连接');
                }
            }
            
            /**
             * 更新统计卡片
             */
            updateStatsCards(stats) {
                // 更新总壁纸数
                const totalWallpapersEl = document.querySelector('[data-stat="total_wallpapers"] [data-value]');
                if (totalWallpapersEl) {
                    this.animateNumber(totalWallpapersEl, stats.total_wallpapers);
                }
                
                // 更新待审核数
                const pendingWallpapersEl = document.querySelector('[data-stat="pending_reviews"] [data-value]');
                if (pendingWallpapersEl) {
                    this.animateNumber(pendingWallpapersEl, stats.pending_wallpapers);
                }
                
                // 更新总用户数
                const totalUsersEl = document.querySelector('[data-stat="total_users"] [data-value]');
                if (totalUsersEl) {
                    this.animateNumber(totalUsersEl, stats.total_users);
                }
                
                // 更新今日下载数
                const todayDownloadsEl = document.querySelector('[data-stat="today_downloads"] [data-value]');
                if (todayDownloadsEl) {
                    this.animateNumber(todayDownloadsEl, stats.today_downloads);
                }
                
                // 更新侧边栏待审核数量徽章
                const pendingCount = document.getElementById('pending-count');
                if (pendingCount) {
                    pendingCount.textContent = stats.pending_wallpapers || 0;
                    // 如果有待审核的壁纸，显示徽章
                    if (stats.pending_wallpapers > 0) {
                        pendingCount.style.display = 'inline-block';
                    } else {
                        pendingCount.style.display = 'none';
                    }
                }
            }
            
            /**
             * 数字动画效果
             */
            animateNumber(element, targetNumber) {
                const currentNumber = parseInt(element.textContent.replace(/,/g, '')) || 0;
                const increment = Math.ceil((targetNumber - currentNumber) / 10);
                
                if (currentNumber === targetNumber) return;
                
                let current = currentNumber;
                const timer = setInterval(() => {
                    current += increment;
                    if ((increment > 0 && current >= targetNumber) || (increment < 0 && current <= targetNumber)) {
                        current = targetNumber;
                        clearInterval(timer);
                    }
                    element.textContent = current.toLocaleString();
                }, 50);
            }
            
            /**
             * 加载最近活动
             */
            async loadRecentActivities() {
                try {
                    const userInfo = JSON.parse(localStorage.getItem('user') || '{}');
                    const userId = userInfo.id;
                    
                    if (!userId) {
                        console.error('[调试-仪表盘] 用户未登录');
                        return;
                    }
                    
                    const response = await fetch('/api/admin_dashboard.php?action=recent_activities', {
                        headers: {
                            'Authorization': `Bearer ${userId}`
                        }
                    });
                    
                    // 检查401未授权错误 - 已禁用跳转
                    if (response.status === 401) {
                        console.warn('[调试-仪表盘] 未授权访问，但跳转已禁用');
                        // window.location.href = '/index.html'; // 已禁用
                        // return; // 继续执行
                    }
                    
                    const result = await response.json();
                    
                    if (result.code === 200) {
                        this.updateRecentActivities(result.data);
                    } else {
                        console.error('[调试-仪表盘] 获取最近活动失败:', result.msg);
                        if (result.msg === '未登录') {
                            console.warn('[调试-仪表盘] 检测到未登录状态，但跳转已禁用');
                            // window.location.href = '/index.html'; // 已禁用
                            // return; // 继续执行
                        }
                    }
                } catch (error) {
                    console.error('[调试-仪表盘] 加载最近活动失败:', error);
                }
            }
            
            /**
             * 更新最近活动
             */
            updateRecentActivities(activities) {
                // 更新最新用户
                const recentUsersEl = document.querySelector('#recent-users-list');
                if (recentUsersEl && activities.recent_users) {
                    if (activities.recent_users.length === 0) {
                        recentUsersEl.innerHTML = '<div class="text-center text-gray-500 py-4">暂无新用户注册</div>';
                    } else {
                        recentUsersEl.innerHTML = activities.recent_users.map(user => `
                            <div class="flex items-center justify-between py-3 border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                <div class="flex-1">
                                    <div class="flex items-center">
                                        <img src="https://picsum.photos/32/32?random=${user.id}" alt="用户头像" class="w-8 h-8 rounded-full mr-3">
                                        <div>
                                            <p class="font-medium text-gray-900">${user.username}</p>
                                            <p class="text-sm text-gray-500">${user.email}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-500">${this.formatDate(user.created_at)}</p>
                                </div>
                            </div>
                        `).join('');
                    }
                }
                
                // 更新管理员操作日志
                const adminActionsEl = document.querySelector('#recent-admin-actions-list');
                if (adminActionsEl && activities.admin_actions) {
                    if (activities.admin_actions.length === 0) {
                        adminActionsEl.innerHTML = '<div class="text-center text-gray-500 py-4">暂无管理员操作记录</div>';
                    } else {
                        adminActionsEl.innerHTML = activities.admin_actions.map(action => `
                            <div class="flex items-center justify-between py-3 border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                <div class="flex-1">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary mr-3">
                                            <i class="fas fa-${this.getActionIcon(action.action_type)} text-sm"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900">${action.action_description}</p>
                                            <p class="text-sm text-gray-500">操作者: ${action.admin_name}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-500">${this.formatDate(action.created_at)}</p>
                                </div>
                            </div>
                        `).join('');
                    }
                }
            }
            
            /**
             * 获取操作类型对应的图标
             */
            getActionIcon(actionType) {
                const icons = {
                    'approve': 'check',
                    'reject': 'times',
                    'delete': 'trash',
                    'ban': 'ban',
                    'unban': 'check-circle',
                    'login': 'sign-in-alt'
                };
                return icons[actionType] || 'cog';
            }
            
            /**
             * 格式化日期
             */
            formatDate(dateString) {
                if (!dateString) return '未知';
                const date = new Date(dateString);
                const now = new Date();
                const diff = now - date;
                
                if (diff < 60000) { // 1分钟内
                    return '刚刚';
                } else if (diff < 3600000) { // 1小时内
                    return Math.floor(diff / 60000) + '分钟前';
                } else if (diff < 86400000) { // 24小时内
                    return Math.floor(diff / 3600000) + '小时前';
                } else {
                    return date.toLocaleDateString('zh-CN');
                }
            }
            
            /**
             * 加载系统状态
             */
            async loadSystemStatus() {
                try {
                    const userInfo = JSON.parse(localStorage.getItem('user') || '{}');
                    const userId = userInfo.id;
                    
                    if (!userId) {
                        console.error('[调试-系统状态] 用户未登录');
                        this.showError('用户未登录');
                        return;
                    }
                    
                    const response = await fetch('/api/admin_dashboard.php?action=system_status', {
                        headers: {
                            'Authorization': `Bearer ${userId}`
                        }
                    });
                    
                    // 检查401未授权错误 - 已禁用跳转
                    if (response.status === 401) {
                        console.warn('[调试-系统状态] 未授权访问，但跳转已禁用');
                        // window.location.href = '/index.html'; // 已禁用
                        // return; // 继续执行
                    }
                    
                    const result = await response.json();
                    
                    if (result.code === 200) {
                        this.updateSystemStatus(result.data);
                    } else {
                        console.error('[调试-系统状态] 获取系统状态失败:', result.msg);
                        if (result.msg === '未登录') {
                            console.warn('[调试-系统状态] 检测到未登录状态，但跳转已禁用');
                            // window.location.href = '/index.html'; // 已禁用
                            // return; // 继续执行
                        }
                        this.showError('获取系统状态失败: ' + result.msg);
                    }
                } catch (error) {
                    console.error('[调试-系统状态] 请求失败:', error);
                    this.showError('获取系统状态失败，请检查网络连接');
                }
            }
            
            /**
             * 更新系统状态显示
             */
            updateSystemStatus(status) {
                // 更新总体状态
                const overallStatusEl = document.getElementById('overall-status-text');
                const systemOverallEl = document.getElementById('system-overall-status');
                if (overallStatusEl && systemOverallEl) {
                    overallStatusEl.textContent = status.overall_status || '正常运行';
                    // 根据状态设置颜色
                    systemOverallEl.className = `text-sm flex items-center ${
                        status.overall_status === '正常运行' ? 'text-success' : 'text-danger'
                    }`;
                }
                
                // 更新服务器状态
                const serverStatusEl = document.getElementById('server-status');
                if (serverStatusEl) {
                    serverStatusEl.textContent = status.server_status || '正常';
                    serverStatusEl.className = `text-sm ${
                        status.server_status === '正常' ? 'text-success' : 'text-danger'
                    }`;
                }
                
                // 更新数据库状态
                const databaseStatusEl = document.getElementById('database-status');
                if (databaseStatusEl) {
                    databaseStatusEl.textContent = status.database_status || '正常';
                    databaseStatusEl.className = `text-sm ${
                        status.database_status === '正常' ? 'text-success' : 'text-danger'
                    }`;
                }
                
                // 更新存储空间状态
                const storageStatusEl = document.getElementById('storage-status');
                if (storageStatusEl) {
                    storageStatusEl.textContent = status.storage_status || '75% 已使用';
                    // 根据使用率设置颜色
                    const usage = parseInt(status.storage_status) || 75;
                    storageStatusEl.className = `text-sm ${
                        usage < 80 ? 'text-success' : usage < 90 ? 'text-warning' : 'text-danger'
                    }`;
                }
                
                // 更新CDN状态
                const cdnStatusEl = document.getElementById('cdn-status');
                if (cdnStatusEl) {
                    cdnStatusEl.textContent = status.cdn_status || '正常';
                    cdnStatusEl.className = `text-sm ${
                        status.cdn_status === '正常' ? 'text-success' : 'text-danger'
                    }`;
                }
            }
            
            /**
             * 显示错误信息
             */
            showError(message) {
                console.error('[仪表盘错误]', message);
                // 可以在这里添加用户友好的错误提示
            }
            
            /**
             * 刷新数据
             */
            refresh() {
                this.loadDashboardStats();
                this.loadRecentActivities();
                this.loadSystemStatus();
            }
            
            /**
             * 销毁实例
             */
            destroy() {
                if (this.refreshInterval) {
                    clearInterval(this.refreshInterval);
                    this.refreshInterval = null;
                }
            }
        }
        

        
        // 退出登录功能已在admin-common.js中处理，这里移除重复绑定
        
        // 管理员后台初始化
        document.addEventListener('DOMContentLoaded', function() {
            console.log('[Admin] 页面加载完成，初始化模块');
            
            // 验证功能已移至 admin-common.js 统一管理
            // checkAdminLogin() 将在 admin-common.js 的 initAdminCommon() 中自动调用
            
            // 初始化仪表盘
            const dashboard = new AdminDashboard();
            dashboard.init();
            
            // 记录管理员访问日志
            logAdminAccess('admin_panel_access', 'Admin panel accessed successfully');
        });
        
        /**
         * 记录管理员操作日志
         * 注意: 验证相关函数已移至 admin-common.js 统一管理
         */
        function logAdminAccess(action, details = '') {
            const userInfo = JSON.parse(localStorage.getItem('user') || '{}');
            const userId = userInfo.id;
            
            if (!userId) {
                console.warn('无法记录日志: 用户未登录');
                return;
            }
            
            fetch('/api/admin_auth.php?action=log', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${userId}`
                },
                body: JSON.stringify({
                    action: action,
                    details: details,
                    page: 'admin.html'
                })
            })
            .catch(error => {
                console.error('记录管理员日志失败:', error);
            });
        }
        
        /**
         * 加载用户上传待审核数量
         * 文件: admin/admin.html
         * 功能: 获取用户上传的待审核壁纸数量并更新侧边栏显示
         */
        function loadPendingCount() {
            const userInfo = JSON.parse(localStorage.getItem('user') || '{}');
            const userId = userInfo.id;
            
            if (!userId) {
                console.error('获取用户上传待审核数量失败: 用户未登录');
                return;
            }
            
            fetch('/api/admin_user_wallpapers.php?action=pending_count', {
                headers: {
                    'Authorization': `Bearer ${userId}`
                }
            })
            .then(response => response.json())
            .then(result => {
                if (result.code === 200) {
                    const pendingCountElement = document.getElementById('pending-count');
                    if (pendingCountElement) {
                        pendingCountElement.textContent = result.data.count || 0;
                        // 如果数量为0，隐藏徽章
                        if (result.data.count === 0) {
                            pendingCountElement.style.display = 'none';
                        } else {
                            pendingCountElement.style.display = 'inline-block';
                        }
                    }
                }
            })
            .catch(error => {
                console.error('获取用户上传待审核数量失败:', error);
            });
        }
        

        
        // 页面加载时初始化数据
        // checkAdminLogin() 已在 admin-common.js 的 initAdminCommon() 中自动调用
        loadPendingCount();
        
        // 定期更新数量（每30秒）
        setInterval(() => {
            loadPendingCount();
        }, 30000);
    </script>
</body>
</html>
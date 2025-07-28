<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户管理 - 壁纸网站管理后台</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
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
            .badge-active {
                @apply bg-success/20 text-success;
            }
            .badge-banned {
                @apply bg-danger/20 text-danger;
            }
            .badge-pending {
                @apply bg-warning/20 text-warning;
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
            <a href="admin.html" class="sidebar-link">
                <i class="fa fa-tachometer"></i>
                <span>仪表盘</span>
            </a>
            <a href="wallpaper-review.html" class="sidebar-link">
                <i class="fa fa-image"></i>
                <span>壁纸审核</span>
                <span class="ml-auto bg-warning text-white text-xs px-2 py-0.5 rounded-full" id="pending-count">0</span>
            </a>
            <a href="category-management.html" class="sidebar-link">
                <i class="fa fa-folder"></i>
                <span>分类管理</span>
            </a>
            <a href="user-management.html" class="sidebar-link active">
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
                <h1 class="text-xl font-bold">用户管理</h1>
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
            <!-- 筛选和操作栏 -->
            <div class="bg-white rounded-xl p-4 md:p-6 shadow-sm border border-gray-200 mb-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="relative">
                            <input type="text" placeholder="搜索用户名、邮箱..." class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent w-full sm:w-64" id="search-input">
                            <i class="fa fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        </div>
                        
                        <select class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" id="status-filter">
                            <option value="all">全部状态</option>
                            <option value="active">正常</option>
                            <option value="banned">已封禁</option>
                            <option value="pending">待验证</option>
                        </select>
                        
                        <select class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" id="role-filter">
                            <option value="all">全部角色</option>
                            <option value="free">免费用户</option>
                            <option value="monthly">月度会员</option>
                            <option value="permanent">永久会员</option>
                            <option value="admin">管理员</option>
                        </select>
                    </div>
                    
                    <div class="flex gap-2">
                        <button class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors" id="add-user-btn">
                            <i class="fa fa-plus mr-2"></i>添加用户
                        </button>
                        <div class="relative" id="export-dropdown">
                            <button class="px-4 py-2 bg-warning text-white rounded-lg hover:bg-warning/90 transition-colors flex items-center" id="export-btn">
                                <i class="fa fa-download mr-2"></i>导出数据
                                <i class="fa fa-chevron-down ml-2 text-xs"></i>
                            </button>
                            <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-10 hidden" id="export-menu">
                                <div class="py-1">
                                    <button class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" data-encoding="utf8-bom">
                                        <i class="fa fa-file-excel mr-2 text-green-600"></i>Excel格式 (推荐)
                                    </button>
                                    <button class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" data-encoding="gb2312">
                                        <i class="fa fa-file-csv mr-2 text-blue-600"></i>GB2312编码
                                    </button>
                                    <button class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" data-encoding="utf8">
                                        <i class="fa fa-file-text mr-2 text-gray-600"></i>UTF-8编码
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 用户列表 -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="p-4 md:p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-bold">用户列表</h2>
                        <span class="text-sm text-gray-500" id="total-count">共 0 个用户</span>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">用户信息</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">联系方式</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">角色</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">状态</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">上传数</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">注册时间</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">最后登录</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="user-list">
                            <!-- 用户数据将通过JavaScript动态加载 -->
                        </tbody>
                    </table>
                </div>
                
                <!-- 分页 -->
                <div class="px-4 py-3 border-t border-gray-200 flex items-center justify-between">
                    <div class="text-sm text-gray-500">
                        显示第 <span id="page-start">1</span> 到 <span id="page-end">10</span> 条，共 <span id="total-items">0</span> 条记录
                    </div>
                    <div class="flex items-center space-x-2" id="pagination-controls">
                        <button class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50" id="prev-page">
                            上一页
                        </button>
                        <div class="flex space-x-1" id="page-numbers">
                            <!-- 页码按钮将动态生成 -->
                        </div>
                        <button class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50" id="next-page">
                            下一页
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- 用户详情模态框 -->
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden" id="user-detail-modal">
        <div class="bg-white rounded-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-bold">用户详情</h3>
                <button class="text-gray-500 hover:text-gray-700" id="close-detail-modal">
                    <i class="fa fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-4" id="user-detail-content">
                <!-- 用户详情内容将动态加载 -->
            </div>
        </div>
    </div>

    <!-- 添加/编辑用户模态框 -->
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden" id="user-modal">
        <div class="bg-white rounded-xl max-w-md w-full mx-4">
            <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-bold" id="modal-title">添加用户</h3>
                <button class="text-gray-500 hover:text-gray-700" id="close-modal">
                    <i class="fa fa-times text-xl"></i>
                </button>
            </div>
            <form id="user-form" class="p-4">
                <input type="hidden" id="user-id">
                
                <div class="mb-4">
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-2">用户名</label>
                    <input type="text" id="username" name="username" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="请输入用户名">
                </div>
                
                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">邮箱</label>
                    <input type="email" id="email" name="email" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="请输入邮箱地址">
                </div>
                
                <div class="mb-4" id="password-field">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">密码</label>
                    <input type="password" id="password" name="password" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="请输入密码">
                </div>
                
                <div class="mb-4">
                    <label for="role" class="block text-sm font-medium text-gray-700 mb-2">角色</label>
                    <select id="role" name="role" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="free">免费用户</option>
                        <option value="monthly">月度会员</option>
                        <option value="permanent">永久会员</option>
                        <option value="admin">管理员</option>
                    </select>
                </div>
                
                <div class="mb-6">
                    <label class="flex items-center">
                        <input type="checkbox" id="is-active" name="is_active" class="rounded border-gray-300 text-primary focus:ring-primary">
                        <span class="ml-2 text-sm text-gray-700">激活用户</span>
                    </label>
                </div>
                
                <div class="flex justify-end space-x-2">
                    <button type="button" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50" id="cancel-btn">
                        取消
                    </button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">
                        <span id="submit-text">添加</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

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
        
        // 全局变量
        let currentPage = 1;
        let totalPages = 1;
        let allUsers = [];
        let currentFilters = {
            status: 'all',
            search: ''
        };
        
        // 加载用户列表
        async function loadUsers(page = 1) {
            try {
                showLoading(true);
                
                const statusFilter = document.getElementById('status-filter').value;
                const roleFilter = document.getElementById('role-filter').value;
                const searchQuery = document.getElementById('search-input').value;
                
                const params = new URLSearchParams({
                    action: 'list',
                    page: page,
                    limit: 20,
                    status: statusFilter,
                    role: roleFilter,
                    search: searchQuery
                });
                
                const response = await fetch(`/api/admin_users.php?${params}`);
                const data = await response.json();
                
                if (data.success) {
                    allUsers = data.data.users;
                    currentPage = data.data.pagination.current_page;
                    totalPages = data.data.pagination.total_pages;
                    
                    renderUserList(data.data.users);
                    updatePagination(data.data.pagination);
                    document.getElementById('total-count').textContent = `共 ${data.data.pagination.total} 个用户`;
                    updateUserStats();
                } else {
                    showMessage(data.message || '加载用户列表失败', 'error');
                }
            } catch (error) {
                console.error('加载用户列表失败:', error);
                showMessage('网络错误，请稍后重试', 'error');
            } finally {
                showLoading(false);
            }
        }
        
        // 渲染用户列表
        function renderUserList(users) {
            const tbody = document.getElementById('user-list');
            
            if (!users || users.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                            暂无用户数据
                        </td>
                    </tr>
                `;
                return;
            }
            
            tbody.innerHTML = users.map(user => {
                // 处理用户状态，优先使用user_status字段
                const userStatus = user.user_status || user.status || 'active';
                const wallpaperCount = user.wallpaper_count || user.upload_count || 0;
                const lastLogin = user.last_login_time || user.last_login;
                const userRole = user.is_admin === 1 ? 'admin' : (user.membership_type || 'free');
                
                return `
                    <tr class="table-row-hover">
                        <td class="px-4 py-3">
                            <div class="flex items-center space-x-3">
                                <img src="${user.avatar || 'https://picsum.photos/id/64/40/40'}" alt="${user.username}" class="w-10 h-10 rounded-full">
                                <div>
                                    <div class="font-medium">${user.username}</div>
                                    <div class="text-sm text-gray-500">ID: ${user.id}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div>
                                <div class="text-sm">${user.email}</div>
                                <div class="text-sm text-gray-500">${user.phone || '-'}</div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-sm">${getRoleText(userRole)}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="badge badge-${userStatus}">${getStatusText(userStatus)}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-sm font-medium">${wallpaperCount}</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">
                            ${formatDate(user.created_at)}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">
                            ${lastLogin ? formatDate(lastLogin) : '从未登录'}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex space-x-2">
                                <button class="text-primary hover:text-primary/80" onclick="showUserDetail(${user.id})">
                                    <i class="fa fa-eye"></i>
                                </button>
                                <button class="text-info hover:text-info/80" onclick="editUser(${user.id})">
                                    <i class="fa fa-edit"></i>
                                </button>
                                <button class="text-${(userStatus === 'banned' || userStatus === 'suspended') ? 'success' : 'warning'} hover:opacity-80" onclick="toggleUserStatus(${user.id}, '${userStatus}')">
                                    <i class="fa fa-${(userStatus === 'banned' || userStatus === 'suspended') ? 'unlock' : 'ban'}"></i>
                                </button>
                                <button class="text-danger hover:text-danger/80" onclick="deleteUser(${user.id})">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }
        
        // 获取角色文本
        function getRoleText(role) {
            const roleMap = {
                'free': '免费用户',
                'monthly': '月度会员',
                'permanent': '永久会员',
                'admin': '管理员'
            };
            return roleMap[role] || role;
        }
        
        // 获取状态文本
        function getStatusText(status) {
            const statusMap = {
                'active': '正常',
                'banned': '已封禁',
                'suspended': '已封禁', // 数据库中存储为suspended
                'pending': '待验证'
            };
            return statusMap[status] || status;
        }
        
        // 格式化日期
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString('zh-CN');
        }
        
        // 显示用户详情
        async function showUserDetail(userId) {
            try {
                showLoading(true);
                
                const response = await fetch(`/api/admin_users.php?action=detail&id=${userId}`);
                const result = await response.json();
                
                if (result.success) {
                    const user = result.data.user;
                    const content = document.getElementById('user-detail-content');
                    
                    // 处理用户状态和角色
                    const userStatus = user.user_status || user.status || 'active';
                    const userRole = user.is_admin === 1 ? 'admin' : (user.membership_type || 'free');
                    const lastLogin = user.last_login_time || user.last_login;
                    
                    content.innerHTML = `
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <div class="text-center">
                                    <img src="${user.avatar || 'https://picsum.photos/id/64/120/120'}" alt="${user.username}" class="w-24 h-24 rounded-full mx-auto mb-4">
                                    <h4 class="text-lg font-bold">${user.username}</h4>
                                    <p class="text-gray-500">${user.email}</p>
                                </div>
                                
                                <div class="space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">用户ID:</span>
                                        <span>${user.id}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">角色:</span>
                                        <span>${getRoleText(userRole)}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">状态:</span>
                                        <span class="badge badge-${userStatus}">${getStatusText(userStatus)}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">手机号:</span>
                                        <span>${user.phone || '-'}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="space-y-4">
                                <h5 class="font-bold">统计信息</h5>
                                <div class="space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">上传壁纸:</span>
                                        <span>${user.wallpaper_stats ? user.wallpaper_stats.total_wallpapers : (user.upload_count || 0)} 张</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">下载次数:</span>
                                        <span>${user.download_count || 0} 次</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">收藏数:</span>
                                        <span>${user.favorite_count || 0} 个</span>
                                    </div>
                                </div>
                                
                                ${user.wallpaper_stats ? `
                                <h5 class="font-bold">壁纸详情</h5>
                                <div class="space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">已通过:</span>
                                        <span class="text-success">${user.wallpaper_stats.approved_wallpapers || 0} 张</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">待审核:</span>
                                        <span class="text-warning">${user.wallpaper_stats.pending_wallpapers || 0} 张</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">已拒绝:</span>
                                        <span class="text-danger">${user.wallpaper_stats.rejected_wallpapers || 0} 张</span>
                                    </div>
                                </div>
                                ` : ''}
                                
                                <h5 class="font-bold">时间信息</h5>
                                <div class="space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">注册时间:</span>
                                        <span>${formatDate(user.created_at)}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">最后登录:</span>
                                        <span>${lastLogin ? formatDate(lastLogin) : '从未登录'}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">最后活动:</span>
                                        <span>${user.last_activity ? formatDate(user.last_activity) : '-'}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    document.getElementById('user-detail-modal').classList.remove('hidden');
                } else {
                    showMessage(result.message || '获取用户详情失败', 'error');
                }
            } catch (error) {
                console.error('加载用户详情失败:', error);
                showMessage('网络错误，请稍后重试', 'error');
            } finally {
                showLoading(false);
            }
        }
        
        // 关闭用户详情模态框
        document.getElementById('close-detail-modal').addEventListener('click', () => {
            document.getElementById('user-detail-modal').classList.add('hidden');
        });
        
        // 显示添加用户模态框
        document.getElementById('add-user-btn').addEventListener('click', () => {
            document.getElementById('modal-title').textContent = '添加用户';
            document.getElementById('submit-text').textContent = '添加';
            document.getElementById('user-form').reset();
            document.getElementById('user-id').value = '';
            document.getElementById('is-active').checked = true;
            document.getElementById('password-field').style.display = 'block';
            document.getElementById('password').required = true;
            document.getElementById('user-modal').classList.remove('hidden');
        });
        
        // 编辑用户
        async function editUser(userId) {
            try {
                showLoading(true);
                
                const response = await fetch(`/api/admin_users.php?action=detail&id=${userId}`);
                const result = await response.json();
                
                if (result.success) {
                    const user = result.data.user;
                    const userStatus = user.user_status || user.status || 'active';
                    const userRole = user.role || (user.is_admin === 1 ? 'admin' : 'user');
                    
                    document.getElementById('modal-title').textContent = '编辑用户';
                    document.getElementById('submit-text').textContent = '保存';
                    document.getElementById('user-id').value = user.id;
                    document.getElementById('username').value = user.username;
                    document.getElementById('email').value = user.email;
                    document.getElementById('role').value = userRole;
                    document.getElementById('is-active').checked = userStatus === 'active';
                    document.getElementById('password-field').style.display = 'none';
                    document.getElementById('password').required = false;
                    document.getElementById('user-modal').classList.remove('hidden');
                } else {
                    showMessage(result.message || '获取用户信息失败', 'error');
                }
            } catch (error) {
                console.error('加载用户详情失败:', error);
                showMessage('网络错误，请稍后重试', 'error');
            } finally {
                showLoading(false);
            }
        }
        
        // 关闭模态框
        function closeModal() {
            document.getElementById('user-modal').classList.add('hidden');
        }
        
        document.getElementById('close-modal').addEventListener('click', closeModal);
        document.getElementById('cancel-btn').addEventListener('click', closeModal);
        
        // 提交表单
        document.getElementById('user-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const userId = document.getElementById('user-id').value;
            
            const data = {
                username: formData.get('username'),
                email: formData.get('email'),
                role: formData.get('role'),
                is_active: formData.get('is_active') === 'on'
            };
            
            // 验证必填字段
            if (!data.username || !data.email) {
                showMessage('用户名和邮箱不能为空', 'error');
                return;
            }
            
            if (!userId) {
                data.password = formData.get('password');
                if (!data.password) {
                    showMessage('创建用户时密码不能为空', 'error');
                    return;
                }
            }
            
            if (userId) {
                data.id = userId;
            }
            
            try {
                showLoading(true);
                const url = '/api/admin_users.php';
                data.action = userId ? 'update' : 'create';
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                if (result.success) {
                    closeModal();
                    loadUsers(currentPage);
                    showMessage(userId ? '用户更新成功' : '用户创建成功', 'success');
                } else {
                    showMessage(result.message || '操作失败', 'error');
                }
            } catch (error) {
                console.error('保存用户失败:', error);
                showMessage('网络错误，请稍后重试', 'error');
            } finally {
                showLoading(false);
            }
        });
        
        // 切换用户状态
        async function toggleUserStatus(userId, currentStatus) {
            const newStatus = (currentStatus === 'banned' || currentStatus === 'suspended') ? 'active' : 'banned';
            const action = newStatus === 'banned' ? '封禁' : '解封';
            
            let reason = '';
            if (newStatus === 'banned') {
                reason = prompt('请输入封禁原因:', '违规行为');
                if (reason === null) {
                    return; // 用户取消
                }
                if (!reason.trim()) {
                    showMessage('封禁原因不能为空', 'error');
                    return;
                }
            }
            
            if (!confirm(`确定要${action}该用户吗？`)) {
                return;
            }
            
            try {
                showLoading(true);
                
                const response = await fetch('/api/admin_users.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: newStatus === 'banned' ? 'ban' : 'unban',
                        user_id: userId,
                        reason: reason || undefined
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    loadUsers(currentPage);
                    showMessage(`用户${action}成功`, 'success');
                } else {
                    showMessage(result.message || `${action}失败`, 'error');
                }
            } catch (error) {
                console.error(`${action}用户失败:`, error);
                showMessage('网络错误，请稍后重试', 'error');
            } finally {
                showLoading(false);
            }
        }
        
        // 删除用户
        async function deleteUser(userId) {
            if (!confirm('确定要删除该用户吗？此操作不可恢复！\n\n注意：如果用户有上传的壁纸，将无法删除。')) {
                return;
            }
            
            try {
                showLoading(true);
                
                const response = await fetch('/api/admin_users.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'delete',
                        user_id: userId
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    loadUsers(currentPage);
                    showMessage('用户删除成功', 'success');
                } else {
                    showMessage(result.message || '删除失败', 'error');
                }
            } catch (error) {
                console.error('删除用户失败:', error);
                showMessage('网络错误，请稍后重试', 'error');
            } finally {
                showLoading(false);
            }
        }
        
        // 导出数据功能
        const exportBtn = document.getElementById('export-btn');
        const exportMenu = document.getElementById('export-menu');
        const exportOptions = document.querySelectorAll('[data-encoding]');
        
        // 切换导出菜单显示
        exportBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            exportMenu.classList.toggle('hidden');
        });
        
        // 点击其他地方关闭菜单
        document.addEventListener('click', () => {
            exportMenu.classList.add('hidden');
        });
        
        // 阻止菜单内点击事件冒泡
        exportMenu.addEventListener('click', (e) => {
            e.stopPropagation();
        });
        
        // 处理导出选项点击
        exportOptions.forEach(option => {
            option.addEventListener('click', () => {
                const encoding = option.dataset.encoding;
                exportData(encoding);
                exportMenu.classList.add('hidden');
            });
        });
        
        // 执行导出
        function exportData(encoding = 'utf8-bom') {
            const statusFilter = document.getElementById('status-filter').value;
            const roleFilter = document.getElementById('role-filter').value;
            const searchQuery = document.getElementById('search-input').value;
            
            const params = new URLSearchParams({
                action: 'export',
                encoding: encoding,
                status: statusFilter,
                role: roleFilter,
                search: searchQuery
            });
            
            // 显示导出提示
            const encodingNames = {
                'utf8-bom': 'Excel格式',
                'gb2312': 'GB2312编码',
                'utf8': 'UTF-8编码'
            };
            
            showMessage(`正在导出${encodingNames[encoding]}数据...`, 'info');
            
            // 创建下载链接
            const downloadUrl = `/api/admin_users.php?${params}`;
            window.open(downloadUrl, '_blank');
        }
        
        // 搜索功能
        const searchInput = document.getElementById('search-input');
        const debouncedSearch = debounce(() => {
            currentFilters.search = searchInput.value.trim();
            currentPage = 1;
            loadUsers();
        }, 300);
        
        searchInput.addEventListener('input', debouncedSearch);
        
        // 筛选功能
        document.getElementById('status-filter').addEventListener('change', (e) => {
            currentFilters.status = e.target.value;
            currentPage = 1;
            loadUsers();
        });
        
        // 如果有角色筛选器
        const roleFilter = document.getElementById('role-filter');
        if (roleFilter) {
            roleFilter.addEventListener('change', (e) => {
                currentFilters.role = e.target.value;
                currentPage = 1;
                loadUsers();
            });
        }
        
        // 防抖函数
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
        
        // 更新分页
        function updatePagination(pagination) {
            currentPage = pagination.current_page;
            totalPages = pagination.total_pages;
            
            document.getElementById('page-start').textContent = pagination.start;
            document.getElementById('page-end').textContent = pagination.end;
            document.getElementById('total-items').textContent = pagination.total;
            
            // 更新分页按钮状态
            const prevBtn = document.getElementById('prev-page');
            const nextBtn = document.getElementById('next-page');
            
            if (prevBtn) {
                prevBtn.disabled = currentPage <= 1;
                prevBtn.classList.toggle('opacity-50', currentPage <= 1);
                prevBtn.classList.toggle('cursor-not-allowed', currentPage <= 1);
            }
            
            if (nextBtn) {
                nextBtn.disabled = currentPage >= totalPages;
                nextBtn.classList.toggle('opacity-50', currentPage >= totalPages);
                nextBtn.classList.toggle('cursor-not-allowed', currentPage >= totalPages);
            }
            
            // 生成页码按钮
            const pageNumbers = document.getElementById('page-numbers');
            if (pageNumbers) {
                let paginationHTML = '';
                const startPage = Math.max(1, currentPage - 2);
                const endPage = Math.min(totalPages, currentPage + 2);
                
                for (let i = startPage; i <= endPage; i++) {
                    if (i === currentPage) {
                        paginationHTML += `<button class="px-3 py-1 text-sm bg-primary text-white rounded">${i}</button>`;
                    } else {
                        paginationHTML += `<button onclick="goToPage(${i})" class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50">${i}</button>`;
                    }
                }
                
                pageNumbers.innerHTML = paginationHTML;
            }
        }
        
        // 跳转到指定页面
        function goToPage(page) {
            if (page < 1 || page > totalPages) return;
            loadUsers(page);
        }
        
        // 分页事件
        document.getElementById('prev-page').addEventListener('click', () => {
            if (currentPage > 1) {
                loadUsers(currentPage - 1);
            }
        });
        
        document.getElementById('next-page').addEventListener('click', () => {
            if (currentPage < totalPages) {
                loadUsers(currentPage + 1);
            }
        });
        
        // 显示加载状态
        function showLoading(show) {
            const loadingElements = document.querySelectorAll('.loading-spinner');
            loadingElements.forEach(el => {
                el.style.display = show ? 'inline-block' : 'none';
            });
        }
        
        // 显示消息
        function showMessage(message, type = 'info') {
            // 简单的消息显示，可以后续改为更好的UI组件
            alert(message);
        }
        
        // 更新用户统计
        function updateUserStats() {
            // 这里可以添加用户统计信息的更新逻辑
        }
        
        // 页面加载完成后初始化
         document.addEventListener('DOMContentLoaded', () => {
             loadUsers(1);
         });
     </script>
 </body>
</html>
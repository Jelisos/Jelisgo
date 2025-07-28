<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>操作日志 - 壁纸网站管理后台</title>
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
        .log-row:hover {
            background-color: #f8fafc;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .status-success {
            background-color: #dcfce7;
            color: #166534;
        }
        .status-warning {
            background-color: #fef3c7;
            color: #92400e;
        }
        .status-error {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .status-info {
            background-color: #dbeafe;
            color: #1e40af;
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
            <a href="system-settings.html" class="sidebar-item flex items-center px-6 py-3 text-gray-300 hover:text-white transition-all duration-200">
                <i class="sidebar-icon fas fa-cog w-5 text-center"></i>
                <span class="sidebar-text ml-3">系统设置</span>
            </a>
            <a href="operation-logs.html" class="sidebar-item flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white transition-all duration-200">
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
                    <h2 class="text-2xl font-bold text-gray-800">操作日志</h2>
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

        <!-- 操作日志内容 -->
        <main class="p-6">
            <!-- 搜索和筛选区域 -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">操作类型</label>
                        <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">全部类型</option>
                            <option value="login">登录</option>
                            <option value="logout">登出</option>
                            <option value="create">创建</option>
                            <option value="update">更新</option>
                            <option value="delete">删除</option>
                            <option value="upload">上传</option>
                            <option value="download">下载</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">操作人</label>
                        <input type="text" placeholder="输入用户名或ID" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">开始日期</label>
                        <input type="date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">结束日期</label>
                        <input type="date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
                <div class="flex justify-end mt-4 space-x-3">
                    <button class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        <i class="fas fa-redo mr-2"></i>重置
                    </button>
                    <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-search mr-2"></i>搜索
                    </button>
                    <button class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-download mr-2"></i>导出
                    </button>
                </div>
            </div>

            <!-- 日志列表 -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">操作记录</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">时间</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作人</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作类型</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作内容</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP地址</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">状态</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr class="log-row">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">2024-01-15 14:30:25</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <img class="h-8 w-8 rounded-full" src="https://via.placeholder.com/32" alt="">
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-gray-900">admin</div>
                                            <div class="text-sm text-gray-500">管理员</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="status-badge status-success">
                                        <i class="fas fa-sign-in-alt mr-1"></i>登录
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">管理员登录系统</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">192.168.1.100</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="status-badge status-success">成功</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button class="text-blue-600 hover:text-blue-900">详情</button>
                                </td>
                            </tr>
                            <tr class="log-row">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">2024-01-15 14:25:12</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <img class="h-8 w-8 rounded-full" src="https://via.placeholder.com/32" alt="">
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-gray-900">user123</div>
                                            <div class="text-sm text-gray-500">普通用户</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="status-badge status-info">
                                        <i class="fas fa-upload mr-1"></i>上传
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">上传壁纸：风景.jpg</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">192.168.1.101</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="status-badge status-success">成功</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button class="text-blue-600 hover:text-blue-900">详情</button>
                                </td>
                            </tr>
                            <tr class="log-row">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">2024-01-15 14:20:45</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <img class="h-8 w-8 rounded-full" src="https://via.placeholder.com/32" alt="">
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-gray-900">admin</div>
                                            <div class="text-sm text-gray-500">管理员</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="status-badge status-warning">
                                        <i class="fas fa-edit mr-1"></i>更新
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">修改用户权限：user123</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">192.168.1.100</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="status-badge status-success">成功</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button class="text-blue-600 hover:text-blue-900">详情</button>
                                </td>
                            </tr>
                            <tr class="log-row">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">2024-01-15 14:15:30</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <img class="h-8 w-8 rounded-full" src="https://via.placeholder.com/32" alt="">
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-gray-900">user456</div>
                                            <div class="text-sm text-gray-500">普通用户</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="status-badge status-error">
                                        <i class="fas fa-sign-in-alt mr-1"></i>登录
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">尝试登录系统</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">192.168.1.102</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="status-badge status-error">失败</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button class="text-blue-600 hover:text-blue-900">详情</button>
                                </td>
                            </tr>
                            <tr class="log-row">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">2024-01-15 14:10:15</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <img class="h-8 w-8 rounded-full" src="https://via.placeholder.com/32" alt="">
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-gray-900">admin</div>
                                            <div class="text-sm text-gray-500">管理员</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="status-badge status-error">
                                        <i class="fas fa-trash mr-1"></i>删除
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">删除违规壁纸：ID 12345</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">192.168.1.100</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="status-badge status-success">成功</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button class="text-blue-600 hover:text-blue-900">详情</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- 分页 -->
                <div class="bg-white px-6 py-3 border-t border-gray-200 flex items-center justify-between">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <button class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            上一页
                        </button>
                        <button class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            下一页
                        </button>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                显示第 <span class="font-medium">1</span> 到 <span class="font-medium">10</span> 条，共 <span class="font-medium">97</span> 条记录
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <button class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <button class="bg-blue-50 border-blue-500 text-blue-600 relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                    1
                                </button>
                                <button class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                    2
                                </button>
                                <button class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                    3
                                </button>
                                <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                                    ...
                                </span>
                                <button class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                    10
                                </button>
                                <button class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- 日志详情模态框 -->
    <div id="logDetailModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">操作详情</h3>
                <button id="closeModal" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">操作时间</label>
                        <p class="mt-1 text-sm text-gray-900">2024-01-15 14:30:25</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">操作人</label>
                        <p class="mt-1 text-sm text-gray-900">admin (管理员)</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">IP地址</label>
                        <p class="mt-1 text-sm text-gray-900">192.168.1.100</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">用户代理</label>
                        <p class="mt-1 text-sm text-gray-900">Chrome/120.0.0.0</p>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">操作内容</label>
                    <p class="mt-1 text-sm text-gray-900">管理员登录系统</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">详细信息</label>
                    <div class="mt-1 bg-gray-50 rounded-md p-3">
                        <pre class="text-xs text-gray-700">{
  "action": "login",
  "user_id": 1,
  "username": "admin",
  "ip_address": "192.168.1.100",
  "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
  "timestamp": "2024-01-15T14:30:25+08:00",
  "status": "success"
}</pre>
                    </div>
                </div>
            </div>
            <div class="flex justify-end mt-6">
                <button id="closeModalBtn" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                    关闭
                </button>
            </div>
        </div>
    </div>

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

        // 模态框功能
        const modal = document.getElementById('logDetailModal');
        const closeModal = document.getElementById('closeModal');
        const closeModalBtn = document.getElementById('closeModalBtn');
        const detailButtons = document.querySelectorAll('button:contains("详情")');

        // 打开模态框
        document.addEventListener('click', function(e) {
            if (e.target.textContent === '详情') {
                modal.classList.remove('hidden');
            }
        });

        // 关闭模态框
        closeModal.addEventListener('click', () => {
            modal.classList.add('hidden');
        });

        closeModalBtn.addEventListener('click', () => {
            modal.classList.add('hidden');
        });

        // 点击模态框外部关闭
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.add('hidden');
            }
        });

        // 搜索功能
        document.querySelector('button:contains("搜索")').addEventListener('click', function() {
            // 这里可以添加搜索逻辑
            alert('搜索功能待实现');
        });

        // 导出功能
        document.querySelector('button:contains("导出")').addEventListener('click', function() {
            // 这里可以添加导出逻辑
            alert('导出功能待实现');
        });

        // 重置功能
        document.querySelector('button:contains("重置")').addEventListener('click', function() {
            // 重置所有筛选条件
            document.querySelectorAll('select, input').forEach(element => {
                if (element.type === 'date' || element.type === 'text') {
                    element.value = '';
                } else if (element.tagName === 'SELECT') {
                    element.selectedIndex = 0;
                }
            });
        });
    </script>
</body>
</html>
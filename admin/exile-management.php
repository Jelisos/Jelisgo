<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>流放管理 - 壁纸网站管理后台</title>
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
            .badge-permanent {
                @apply bg-danger/20 text-danger;
            }
            .badge-temporary {
                @apply bg-warning/20 text-warning;
            }
            .badge-expired {
                @apply bg-gray-200 text-gray-600;
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
            <a href="user-management.html" class="sidebar-link">
                <i class="fa fa-users"></i>
                <span>用户管理</span>
            </a>
            <a href="exile-management.html" class="sidebar-link active">
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
                <h1 class="text-xl font-bold">流放管理</h1>
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
                <div class="flex flex-col gap-4">
                    <!-- 第一行：搜索和邮箱筛选 -->
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="relative flex-1">
                            <input type="text" placeholder="搜索壁纸标题或用户名..." class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent w-full" id="search-input">
                            <i class="fa fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        </div>
                        
                        <div class="relative flex-1">
                            <input type="text" placeholder="筛选用户邮箱（支持模糊匹配）..." class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent w-full" id="email-filter">
                            <i class="fa fa-envelope absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        </div>
                    </div>
                    
                    <!-- 第二行：状态筛选和操作按钮 -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center">
                            <div class="flex items-center gap-2">
                                <label class="text-sm font-medium text-gray-700 whitespace-nowrap">状态筛选：</label>
                                <select id="status-filter" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                    <option value="">全部状态</option>
                                    <option value="1">流放</option>
                                    <option value="0">正常</option>
                                </select>
                            </div>
                            
                            <button onclick="clearFilters()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                                <i class="fa fa-refresh mr-2"></i>清空筛选
                            </button>
                        </div>
                        
                        <div class="flex gap-2">
                            <button class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors" id="select-all-btn" onclick="toggleSelectAll()">
                                <i class="fa fa-check-square mr-2"></i>全选
                            </button>
                            <button class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors" id="batch-recall-btn" onclick="batchRecallWallpapers()" disabled>
                                <i class="fa fa-undo mr-2"></i>批量召回
                            </button>
                            <button class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors" id="batch-exile-btn" disabled>
                                <i class="fa fa-ban mr-2"></i>批量流放
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 流放列表 -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="p-4 md:p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-bold">流放记录</h2>
                        <span class="text-sm text-gray-500" id="total-count">共 0 条记录</span>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">选择</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">壁纸信息</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">用户邮箱</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作者类型</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">流放/召回时间</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">上传时间</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">状态</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="exile-list">
                            <!-- 流放数据将通过JavaScript动态加载 -->
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

    <!-- 添加流放模态框 -->
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden" id="exile-modal">
        <div class="bg-white rounded-xl max-w-md w-full mx-4">
            <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-bold">添加流放</h3>
                <button class="text-gray-500 hover:text-gray-700" id="close-modal">
                    <i class="fa fa-times text-xl"></i>
                </button>
            </div>
            <form id="exile-form" class="p-4">
                <div class="mb-4">
                    <label for="user-search" class="block text-sm font-medium text-gray-700 mb-2">选择用户</label>
                    <div class="relative">
                        <input type="text" id="user-search" placeholder="搜索用户名或邮箱" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <div class="absolute top-full left-0 right-0 bg-white border border-gray-300 rounded-lg mt-1 max-h-40 overflow-y-auto hidden" id="user-suggestions">
                            <!-- 用户建议列表 -->
                        </div>
                    </div>
                    <input type="hidden" id="selected-user-id" name="user_id">
                </div>
                
                <div class="mb-4">
                    <label for="exile-type" class="block text-sm font-medium text-gray-700 mb-2">流放类型</label>
                    <select id="exile-type" name="type" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="">请选择流放类型</option>
                        <option value="temporary">临时流放</option>
                        <option value="permanent">永久流放</option>
                    </select>
                </div>
                
                <div class="mb-4" id="duration-field" style="display: none;">
                    <label for="duration" class="block text-sm font-medium text-gray-700 mb-2">流放时长（天）</label>
                    <input type="number" id="duration" name="duration" min="1" max="365" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="请输入天数">
                </div>
                
                <div class="mb-4">
                    <label for="reason" class="block text-sm font-medium text-gray-700 mb-2">流放原因</label>
                    <select id="reason" name="reason" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="">请选择流放原因</option>
                        <option value="spam">发布垃圾内容</option>
                        <option value="inappropriate">发布不当内容</option>
                        <option value="violation">违反社区规定</option>
                        <option value="abuse">恶意行为</option>
                        <option value="other">其他原因</option>
                    </select>
                </div>
                
                <div class="mb-6">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">详细说明</label>
                    <textarea id="description" name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="请详细说明流放原因..."></textarea>
                </div>
                
                <div class="flex justify-end space-x-2">
                    <button type="button" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50" id="cancel-btn">
                        取消
                    </button>
                    <button type="submit" class="px-4 py-2 bg-danger text-white rounded-lg hover:bg-danger/90">
                        确认流放
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 壁纸预览模态框 -->
    <div id="preview-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg max-w-4xl max-h-[90vh] overflow-auto">
            <div class="flex justify-between items-center p-4 border-b">
                <h3 class="text-lg font-semibold" id="preview-title">壁纸预览</h3>
                <button onclick="closePreviewModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fa fa-times"></i>
                </button>
            </div>
            <div class="p-4">
                <img id="preview-image" src="" alt="壁纸预览" class="max-w-full max-h-[70vh] object-contain mx-auto">
            </div>
        </div>
    </div>

    <!-- 流放详情模态框 -->
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden" id="exile-detail-modal">
        <div class="bg-white rounded-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-bold">流放详情</h3>
                <button class="text-gray-500 hover:text-gray-700" id="close-detail-modal">
                    <i class="fa fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-4" id="exile-detail-content">
                <!-- 流放详情内容将动态加载 -->
            </div>
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
        
        // 加载被流放壁纸列表
        async function loadExiles(page = 1) {
            try {
                const emailFilter = document.getElementById('email-filter').value;
                const searchQuery = document.getElementById('search-input').value;
                const statusFilter = document.getElementById('status-filter').value;
                
                const params = new URLSearchParams({
                    page: page,
                    limit: 20
                });
                
                if (searchQuery) params.append('search', searchQuery);
                if (emailFilter) params.append('email', emailFilter);
                if (statusFilter !== '') params.append('status', statusFilter);
                
                // 获取用户信息用于Authorization头
                const userInfo = JSON.parse(localStorage.getItem('user') || '{}');
                const userId = userInfo.id;
                
                if (!userId) {
                    alert('请先登录');
                    window.location.href = '/index.php';
                    return;
                }
                
                const response = await fetch(`/api/admin/wallpaper_exile_status.php?${params}`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${userId}`
                    }
                });
                const data = await response.json();
                
                if (data.success) {
                    await renderExileList(data.data.records);
                    updatePagination(data.data.pagination);
                    // 根据筛选状态更新显示文本
                    const statusFilter = document.getElementById('status-filter').value;
                    let countText = '';
                    if (statusFilter === '1') {
                        countText = `共 ${data.data.pagination.total} 条流放记录`;
                    } else if (statusFilter === '0') {
                        countText = `共 ${data.data.pagination.total} 条正常记录`;
                    } else {
                        countText = `共 ${data.data.pagination.total} 条记录`;
                    }
                    document.getElementById('total-count').textContent = countText;
                } else {
                    console.error('加载失败:', data.message);
                    alert('加载失败: ' + data.message);
                }
            } catch (error) {
                console.error('加载流放壁纸列表失败:', error);
                alert('网络错误，请稍后重试');
            }
        }
        
        // 渲染被流放壁纸列表
        async function renderExileList(records) {
            const tbody = document.getElementById('exile-list');
            if (!records || records.length === 0) {
                // 根据筛选状态显示不同的空数据提示
                const statusFilter = document.getElementById('status-filter').value;
                let emptyText = '';
                if (statusFilter === '1') {
                    emptyText = '暂无流放记录';
                } else if (statusFilter === '0') {
                    emptyText = '暂无正常记录';
                } else {
                    emptyText = '暂无记录';
                }
                
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                            ${emptyText}
                        </td>
                    </tr>
                `;
                return;
            }
            
            tbody.innerHTML = records.map(record => `
                <tr class="table-row-hover">
                    <td class="px-4 py-3">
                        <input type="checkbox" class="wallpaper-checkbox" value="${record.wallpaper_id}" 
                               onchange="updateBatchButtons()">
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center space-x-3">
                            <img src="" alt="${record.wallpaper_info.title || '未命名'}" 
                                 class="w-12 h-12 object-cover rounded cursor-pointer hover:opacity-80 transition-opacity" 
                                 onerror="this.src='/static/icons/default-avatar.svg'"
                                 data-original-path="${record.wallpaper_info.file_path}"
                                 onclick="previewWallpaper('${record.wallpaper_info.file_path}', '${record.wallpaper_info.title}')"
                                 title="点击预览">
                            <div>
                                <div class="font-medium text-sm">${record.wallpaper_info.title || '未命名'}</div>
                                <div class="text-xs text-gray-500">ID: ${record.wallpaper_id}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center space-x-2">
                            <div class="w-8 h-8 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs">
                                ${(record.operator_info?.username || 'U').charAt(0).toUpperCase()}
                            </div>
                            <div>
                                <div class="font-medium text-sm">${record.operator_info?.username || '未知用户'}</div>
                                <div class="text-xs text-gray-500">${record.operator_info?.email || ''}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                            最后操作者
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500">
                        ${formatDate(record.last_operation_time)}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500">
                        ${formatDate(record.wallpaper_info.upload_time)}
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 text-xs rounded-full ${
                            record.status === 1 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'
                        }">
                            ${record.status_text}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex space-x-2">
                            ${record.status === 1 ? 
                                `<button class="text-green-600 hover:text-green-800 text-sm" 
                                        onclick="recallSingleWallpaper(${record.wallpaper_id})" 
                                        title="召回">
                                    <i class="fa fa-undo"></i>
                                </button>` : 
                                `<button class="text-red-600 hover:text-red-800 text-sm" 
                                        onclick="exileSingleWallpaper(${record.wallpaper_id})" 
                                        title="流放">
                                    <i class="fa fa-ban"></i>
                                </button>`
                            }
                            <button class="text-gray-600 hover:text-gray-800 text-sm" 
                                    onclick="showExileDetail(${record.wallpaper_id})" 
                                    title="详情">
                                <i class="fa fa-info-circle"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
            
            // 异步加载所有图片
            await loadAllImages();
        }
        
        // 加载所有图片
        async function loadAllImages() {
            const images = document.querySelectorAll('img[data-original-path]');
            const loadPromises = Array.from(images).map(async (img) => {
                const originalPath = img.getAttribute('data-original-path');
                if (originalPath) {
                    try {
                        const compressedUrl = await getCompressedImageUrl(originalPath);
                        img.src = compressedUrl;
                    } catch (error) {
                        console.error('加载图片失败:', error);
                        img.src = '/static/icons/default-avatar.svg';
                    }
                }
            });
            
            await Promise.all(loadPromises);
        }
        
        // 更新批量操作按钮状态
        function updateBatchButtons() {
            const checkboxes = document.querySelectorAll('.wallpaper-checkbox:checked');
            const batchRecallBtn = document.getElementById('batch-recall-btn');
            const batchExileBtn = document.getElementById('batch-exile-btn');
            const selectAllBtn = document.getElementById('select-all-btn');
            
            if (batchRecallBtn) {
                batchRecallBtn.disabled = checkboxes.length === 0;
                batchRecallBtn.innerHTML = checkboxes.length > 0 ? 
                    `<i class="fa fa-undo mr-2"></i>批量召回 (${checkboxes.length})` : 
                    '<i class="fa fa-undo mr-2"></i>批量召回';
            }
            
            if (batchExileBtn) {
                batchExileBtn.disabled = checkboxes.length === 0;
                batchExileBtn.innerHTML = checkboxes.length > 0 ? 
                    `<i class="fa fa-ban mr-2"></i>批量流放 (${checkboxes.length})` : 
                    '<i class="fa fa-ban mr-2"></i>批量流放';
            }
        }
        
        // 全选/取消全选
        function toggleSelectAll() {
            const selectAllBtn = document.getElementById('select-all-btn');
            const checkboxes = document.querySelectorAll('.wallpaper-checkbox');
            
            // 检查当前是否有复选框被选中，如果都没选中则执行全选，否则执行取消全选
            const checkedBoxes = document.querySelectorAll('.wallpaper-checkbox:checked');
            const shouldSelectAll = checkedBoxes.length === 0;
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = shouldSelectAll;
            });
            
            selectAllBtn.innerHTML = shouldSelectAll ? 
                '<i class="fa fa-square-o mr-2"></i>取消全选' : 
                '<i class="fa fa-check-square mr-2"></i>全选';
            updateBatchButtons();
        }
        
        // 获取压缩图片URL（与首页逻辑保持一致）
        async function getCompressedImageUrl(originalPath) {
            try {
                if (!originalPath || typeof originalPath !== 'string') {
                    throw new Error('无效的图片路径');
                }
                
                // 规范化路径，确保以static开头
                const normalizedPath = originalPath.startsWith('/') ? originalPath : `/${originalPath}`;
                const pathParts = normalizedPath.substring(1).split('/');
                const filename = pathParts.pop();
                const nameWithoutExt = filename.split('.')[0];
                
                // 构建预览路径（与ImageCompressor逻辑一致）
                let previewDir = '001'; // 默认目录
                if (pathParts.length >= 2 && pathParts[0] === 'static' && pathParts[1] === 'wallpapers') {
                    // 如果是wallpapers目录下的文件，使用相同的子目录结构
                    previewDir = pathParts[2] || '001';
                }
                
                const compressedFilename = `${nameWithoutExt}.jpeg`;
                const fullPath = `/static/preview/${previewDir}/${compressedFilename}`;
                
                // 检查压缩图片是否存在
                try {
                    const response = await fetch(fullPath, { method: 'HEAD' });
                    if (response.ok) {
                        return fullPath;
                    }
                } catch (error) {
                    console.warn('压缩图片不存在，使用原始路径:', error);
                }
                
                return originalPath;
            } catch (error) {
                console.error('获取压缩图片失败:', error);
                return originalPath;
            }
        }
        
        // 预览壁纸
         async function previewWallpaper(filePath, title) {
             const modal = document.getElementById('preview-modal');
             const img = document.getElementById('preview-image');
             const titleEl = document.getElementById('preview-title');
             
             // 使用与首页一致的图片路径逻辑
             const compressedUrl = await getCompressedImageUrl(filePath);
             img.src = compressedUrl;
             titleEl.textContent = title || '未命名壁纸';
             modal.classList.remove('hidden');
         }
         
         // 清空筛选
         function clearFilters() {
             document.getElementById('email-filter').value = '';
             document.getElementById('search-input').value = '';
             document.getElementById('status-filter').value = '';
             loadExiles(1);
         }
         
         // 关闭预览模态框
         function closePreviewModal() {
             document.getElementById('preview-modal').classList.add('hidden');
         }
        
        // 格式化日期
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString('zh-CN');
        }
        
        // 显示流放详情
        async function showExileDetail(wallpaperId) {
            try {
                // 获取用户信息用于Authorization头
                const userInfo = JSON.parse(localStorage.getItem('user') || '{}');
                const userId = userInfo.id;
                
                if (!userId) {
                    alert('请先登录');
                    window.location.href = '/index.php';
                    return;
                }
                
                const response = await fetch(`/api/admin/get_wallpaper_status.php?wallpaper_id=${wallpaperId}`, {
                    method: 'GET',
                    headers: {
                        'Authorization': `Bearer ${userId}`
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const wallpaper = data.data;
                    const historyHtml = wallpaper.history.map(h => `
                        <div class="border-b pb-2 mb-2">
                            <div class="text-sm font-medium">${h.status === 1 ? '流放' : '召回'}</div>
                            <div class="text-xs text-gray-500">操作者: ${h.operator_name || '系统'}</div>
                            <div class="text-xs text-gray-500">时间: ${formatDate(h.operation_time)}</div>
                            <div class="text-xs text-gray-500">原因: ${h.reason || '无'}</div>
                        </div>
                    `).join('');
                    
                    // 创建详情模态框内容
                    const modalContent = `
                        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" id="detail-modal">
                            <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4 max-h-[80vh] overflow-y-auto">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-semibold">壁纸详情</h3>
                                    <button onclick="closeDetailModal()" class="text-gray-500 hover:text-gray-700">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <img src="" alt="${wallpaper.wallpaper_info.title}" 
                                             class="w-full h-48 object-cover rounded" 
                                             onerror="this.src='/static/images/相机.png'"
                                             data-original-path="${wallpaper.wallpaper_info.file_path}">
                                    </div>
                                    <div>
                                        <h4 class="font-medium mb-2">基本信息</h4>
                                        <p><strong>标题:</strong> ${wallpaper.wallpaper_info.title || '未命名'}</p>
                                        <p><strong>ID:</strong> ${wallpaper.wallpaper_info.id}</p>
                                        <p><strong>上传时间:</strong> ${formatDate(wallpaper.wallpaper_info.upload_time)}</p>
                                        <p><strong>当前状态:</strong> 
                                            <span class="px-2 py-1 text-xs rounded-full ${
                                                wallpaper.current_status === 1 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'
                                            }">
                                                ${wallpaper.current_status === 1 ? '已流放' : '正常'}
                                            </span>
                                        </p>
                                        <h4 class="font-medium mt-4 mb-2">最后操作者信息</h4>
                                        <p><strong>用户名:</strong> ${wallpaper.operator_info?.username || '未知用户'}</p>
                                        <p><strong>邮箱:</strong> ${wallpaper.operator_info?.email || '未知邮箱'}</p>
                                        <p><strong>操作类型:</strong> 最后操作者</p>
                                    </div>
                                </div>
                                <div class="mt-6">
                                    <h4 class="font-medium mb-3">操作历史</h4>
                                    <div class="max-h-40 overflow-y-auto">
                                        ${historyHtml || '<p class="text-gray-500">暂无操作记录</p>'}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    // 添加到页面
                    document.body.insertAdjacentHTML('beforeend', modalContent);
                    
                    // 异步加载详情模态框中的图片
                    const modalImg = document.querySelector('#detail-modal img[data-original-path]');
                    if (modalImg) {
                        const originalPath = modalImg.getAttribute('data-original-path');
                        if (originalPath) {
                            try {
                                const compressedUrl = await getCompressedImageUrl(originalPath);
                                modalImg.src = compressedUrl;
                            } catch (error) {
                                console.error('加载详情图片失败:', error);
                                modalImg.src = '/static/images/相机.png';
                            }
                        }
                    }
                } else {
                    alert('获取详情失败: ' + data.message);
                }
            } catch (error) {
                console.error('获取壁纸详情失败:', error);
                alert('网络错误，请稍后重试');
            }
        }
        
        // 关闭详情模态框
        function closeDetailModal() {
            const modal = document.getElementById('detail-modal');
            if (modal) {
                modal.remove();
            }
        }
        
        // 单个召回壁纸
        async function recallSingleWallpaper(wallpaperId) {
            if (!confirm('确定要召回这张壁纸吗？')) {
                return;
            }
            
            try {
                // 获取用户信息用于Authorization头
                const userInfo = JSON.parse(localStorage.getItem('user') || '{}');
                const userId = userInfo.id;
                
                if (!userId) {
                    alert('请先登录');
                    window.location.href = '/index.php';
                    return;
                }
                
                const response = await fetch('/api/admin/update_wallpaper_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${userId}`
                    },
                    body: JSON.stringify({
                        wallpaper_id: wallpaperId,
                        status: 0,
                        comment: '管理员召回操作'
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // 显示简短的成功提示，无需用户点击
                    console.log('单个召回操作成功');
                    loadExiles(currentPage); // 重新加载列表
                    showTemporaryMessage('操作成功', 'success');
                } else {
                    alert('召回失败: ' + data.message);
                }
            } catch (error) {
                console.error('召回壁纸失败:', error);
                alert('网络错误，请稍后重试');
            }
        }
        
        // 单个流放壁纸
        async function exileSingleWallpaper(wallpaperId) {
            if (!confirm('确定要流放这张壁纸吗？')) {
                return;
            }
            
            try {
                // 获取用户信息用于Authorization头
                const userInfo = JSON.parse(localStorage.getItem('user') || '{}');
                const userId = userInfo.id;
                
                if (!userId) {
                    alert('请先登录');
                    window.location.href = '/index.php';
                    return;
                }
                
                const response = await fetch('/api/admin/update_wallpaper_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${userId}`
                    },
                    body: JSON.stringify({
                        wallpaper_id: wallpaperId,
                        status: 1,
                        comment: '管理员流放操作'
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // 显示简短的成功提示，无需用户点击
                    console.log('单个流放操作成功');
                    loadExiles(currentPage); // 重新加载列表
                    showTemporaryMessage('操作成功', 'success');
                } else {
                    alert('流放失败: ' + data.message);
                }
            } catch (error) {
                console.error('流放壁纸失败:', error);
                alert('网络错误，请稍后重试');
            }
        }
        
        // 批量召回壁纸
        async function batchRecallWallpapers() {
            const checkboxes = document.querySelectorAll('.wallpaper-checkbox:checked');
            if (checkboxes.length === 0) {
                alert('请选择要召回的壁纸');
                return;
            }
            
            if (!confirm(`确定要召回选中的 ${checkboxes.length} 张壁纸吗？`)) {
                return;
            }
            
            const wallpaperIds = Array.from(checkboxes).map(cb => parseInt(cb.value));
            
            try {
                // 获取用户信息用于Authorization头
                const userInfo = JSON.parse(localStorage.getItem('user') || '{}');
                const userId = userInfo.id;
                
                if (!userId) {
                    alert('请先登录');
                    window.location.href = '/index.php';
                    return;
                }
                
                const response = await fetch('/api/admin/batch_update_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${userId}`
                    },
                    body: JSON.stringify({
                        wallpaper_ids: wallpaperIds,
                        status: 0,
                        comment: '管理员批量召回操作'
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // 显示简短的成功提示，无需用户点击
                    console.log('批量召回操作成功');
                    loadExiles(currentPage); // 重新加载列表
                    
                    // 重置选择状态
                    document.getElementById('select-all-btn').textContent = '全选';
                    updateBatchButtons();
                    
                    // 显示临时成功提示
                    showTemporaryMessage('操作成功', 'success');
                } else {
                    alert('批量召回失败: ' + data.message);
                }
            } catch (error) {
                 console.error('批量召回失败:', error);
                 alert('网络错误，请稍后重试');
             }
         }
        
        // 批量流放壁纸
        async function batchExileWallpapers() {
            const checkboxes = document.querySelectorAll('.wallpaper-checkbox:checked');
            if (checkboxes.length === 0) {
                alert('请选择要流放的壁纸');
                return;
            }
            
            if (!confirm(`确定要流放选中的 ${checkboxes.length} 张壁纸吗？`)) {
                return;
            }
            
            const wallpaperIds = Array.from(checkboxes).map(cb => parseInt(cb.value));
            
            try {
                // 获取用户信息用于Authorization头
                const userInfo = JSON.parse(localStorage.getItem('user') || '{}');
                const userId = userInfo.id;
                
                if (!userId) {
                    alert('请先登录');
                    window.location.href = '/index.php';
                    return;
                }
                
                const response = await fetch('/api/admin/batch_update_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${userId}`
                    },
                    body: JSON.stringify({
                        wallpaper_ids: wallpaperIds,
                        status: 1,
                        comment: '管理员批量流放操作'
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // 显示简短的成功提示，无需用户点击
                    console.log('批量流放操作成功');
                    loadExiles(currentPage); // 重新加载列表
                    
                    // 重置选择状态
                    document.getElementById('select-all-btn').textContent = '全选';
                    updateBatchButtons();
                    
                    // 显示临时成功提示
                    showTemporaryMessage('操作成功', 'success');
                } else {
                    alert('批量流放失败: ' + data.message);
                }
            } catch (error) {
                 console.error('批量流放失败:', error);
                 alert('网络错误，请稍后重试');
             }
         }
        
        // 关闭流放详情模态框（如果存在）
        const closeDetailModalBtn = document.getElementById('close-detail-modal');
        if (closeDetailModalBtn) {
            closeDetailModalBtn.addEventListener('click', () => {
                document.getElementById('exile-detail-modal').classList.add('hidden');
            });
        }
        
        // 关闭模态框函数（如果模态框存在）
        function closeModal() {
            const exileModal = document.getElementById('exile-modal');
            if (exileModal) {
                exileModal.classList.add('hidden');
            }
        }
        
        // 模态框相关事件绑定（如果元素存在）
        const closeModalBtn = document.getElementById('close-modal');
        const cancelBtn = document.getElementById('cancel-btn');
        const exileType = document.getElementById('exile-type');
        const userSearch = document.getElementById('user-search');
        const exileForm = document.getElementById('exile-form');
        
        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', closeModal);
        }
        if (cancelBtn) {
            cancelBtn.addEventListener('click', closeModal);
        }
        
        // 流放类型变化处理（如果元素存在）
        if (exileType) {
            exileType.addEventListener('change', (e) => {
                const durationField = document.getElementById('duration-field');
                if (durationField) {
                    if (e.target.value === 'temporary') {
                        durationField.style.display = 'block';
                        const duration = document.getElementById('duration');
                        if (duration) duration.required = true;
                    } else {
                        durationField.style.display = 'none';
                        const duration = document.getElementById('duration');
                        if (duration) duration.required = false;
                    }
                }
            });
        }
        
        // 用户搜索（如果元素存在）
        let searchTimeout;
        if (userSearch) {
            userSearch.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                const query = e.target.value.trim();
                
                const suggestions = document.getElementById('user-suggestions');
                if (query.length < 2) {
                    if (suggestions) suggestions.classList.add('hidden');
                    return;
                }
                
                searchTimeout = setTimeout(async () => {
                    try {
                        const response = await fetch(`/api/admin/search-users.php?q=${encodeURIComponent(query)}`);
                        const data = await response.json();
                        
                        if (data.success && suggestions) {
                            suggestions.innerHTML = data.users.map(user => `
                                <div class="p-2 hover:bg-gray-50 cursor-pointer flex items-center space-x-2" onclick="selectUser(${user.id}, '${user.username}', '${user.email}')">
                                    <img src="${user.avatar || 'https://picsum.photos/id/64/30/30'}" alt="${user.username}" class="w-6 h-6 rounded-full">
                                    <div>
                                        <div class="text-sm font-medium">${user.username}</div>
                                        <div class="text-xs text-gray-500">${user.email}</div>
                                    </div>
                                </div>
                            `).join('');
                            suggestions.classList.remove('hidden');
                        }
                    } catch (error) {
                        console.error('搜索用户失败:', error);
                    }
                }, 300);
            });
        }
        
        // 选择用户函数
        function selectUser(userId, username, email) {
            const selectedUserId = document.getElementById('selected-user-id');
            const userSearchInput = document.getElementById('user-search');
            const suggestions = document.getElementById('user-suggestions');
            
            if (selectedUserId) selectedUserId.value = userId;
            if (userSearchInput) userSearchInput.value = `${username} (${email})`;
            if (suggestions) suggestions.classList.add('hidden');
        }
        
        // 提交表单（如果表单存在）
        if (exileForm) {
            exileForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const formData = new FormData(e.target);
                const data = {
                    user_id: formData.get('user_id'),
                    type: formData.get('type'),
                    reason: formData.get('reason'),
                    description: formData.get('description')
                };
                
                if (data.type === 'temporary') {
                    data.duration = parseInt(formData.get('duration'));
                }
                
                if (!data.user_id) {
                    alert('请选择要流放的用户');
                    return;
                }
                
                try {
                    const response = await fetch('/api/admin/create-exile.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(data)
                    });
                    
                    const result = await response.json();
                    if (result.success) {
                        closeModal();
                        loadExiles(currentPage);
                    } else {
                        alert(result.message || '操作失败');
                    }
                } catch (error) {
                    console.error('添加流放失败:', error);
                    alert('操作失败，请重试');
                }
            });
        }
        
        // 筛选和搜索（如果元素存在）
        const emailFilter = document.getElementById('email-filter');
        const searchInput = document.getElementById('search-input');
        const statusFilter = document.getElementById('status-filter');
        
        if (emailFilter) {
            emailFilter.addEventListener('input', debounce(() => loadExiles(1), 500));
        }
        if (searchInput) {
            searchInput.addEventListener('input', debounce(() => loadExiles(1), 500));
        }
        if (statusFilter) {
            statusFilter.addEventListener('change', () => loadExiles(1));
        }
        
        // 点击模态框外部关闭
        const previewModal = document.getElementById('preview-modal');
        if (previewModal) {
            previewModal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closePreviewModal();
                }
            });
        }
        
        // 显示临时消息提示
        function showTemporaryMessage(message, type = 'success', duration = 2000) {
            // 创建提示容器
            let messageContainer = document.getElementById('temp-message-container');
            if (!messageContainer) {
                messageContainer = document.createElement('div');
                messageContainer.id = 'temp-message-container';
                messageContainer.style.position = 'fixed';
                messageContainer.style.top = '20px';
                messageContainer.style.right = '20px';
                messageContainer.style.zIndex = '9999';
                document.body.appendChild(messageContainer);
            }
            
            // 创建消息元素
            const messageElement = document.createElement('div');
            messageElement.textContent = message;
            messageElement.style.padding = '12px 20px';
            messageElement.style.marginBottom = '10px';
            messageElement.style.borderRadius = '6px';
            messageElement.style.color = '#fff';
            messageElement.style.fontSize = '14px';
            messageElement.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
            messageElement.style.transition = 'all 0.3s ease';
            messageElement.style.transform = 'translateX(100%)';
            messageElement.style.opacity = '0';
            
            // 根据类型设置背景色
            if (type === 'success') {
                messageElement.style.backgroundColor = '#10b981';
            } else if (type === 'error') {
                messageElement.style.backgroundColor = '#ef4444';
            } else {
                messageElement.style.backgroundColor = '#3b82f6';
            }
            
            messageContainer.appendChild(messageElement);
            
            // 显示动画
            setTimeout(() => {
                messageElement.style.transform = 'translateX(0)';
                messageElement.style.opacity = '1';
            }, 10);
            
            // 自动移除
            setTimeout(() => {
                messageElement.style.transform = 'translateX(100%)';
                messageElement.style.opacity = '0';
                setTimeout(() => {
                    if (messageElement.parentNode) {
                        messageElement.parentNode.removeChild(messageElement);
                    }
                }, 300);
            }, duration);
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
            
            prevBtn.disabled = currentPage <= 1;
            nextBtn.disabled = currentPage >= totalPages;
            
            // 生成页码按钮
            const pageNumbers = document.getElementById('page-numbers');
            pageNumbers.innerHTML = '';
            
            // 计算显示的页码范围
            let startPage = Math.max(1, currentPage - 2);
            let endPage = Math.min(totalPages, currentPage + 2);
            
            // 如果总页数小于等于5，显示所有页码
            if (totalPages <= 5) {
                startPage = 1;
                endPage = totalPages;
            }
            
            // 生成页码按钮
            for (let i = startPage; i <= endPage; i++) {
                const pageBtn = document.createElement('button');
                pageBtn.className = `px-3 py-1 text-sm border rounded ${
                    i === currentPage ? 
                    'bg-primary text-white border-primary' : 
                    'border-gray-300 hover:bg-gray-50'
                }`;
                pageBtn.textContent = i;
                pageBtn.onclick = () => loadExiles(i);
                pageNumbers.appendChild(pageBtn);
            }
        }
        
        // 分页事件
        document.getElementById('prev-page').addEventListener('click', () => {
            if (currentPage > 1) {
                loadExiles(currentPage - 1);
            }
        });
        
        document.getElementById('next-page').addEventListener('click', () => {
            if (currentPage < totalPages) {
                loadExiles(currentPage + 1);
            }
        });
        
        // 页面加载完成后初始化
        document.addEventListener('DOMContentLoaded', function() {
            // 首先检查管理员登录状态
            checkAdminLogin();
            // 然后加载流放列表
            loadExiles();
            
            // 为批量流放按钮添加事件监听器
            const batchExileBtn = document.getElementById('batch-exile-btn');
            if (batchExileBtn) {
                batchExileBtn.addEventListener('click', batchExileWallpapers);
            }
        });
    </script>
</body>
</html>
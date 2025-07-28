<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>分类管理 - 壁纸网站管理后台</title>
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
            .badge-inactive {
                @apply bg-gray/20 text-gray-600;
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
            <a href="category-management.html" class="sidebar-link active">
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
                <h1 class="text-xl font-bold">分类管理</h1>
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
            <!-- 操作栏 -->
            <div class="bg-white rounded-xl p-4 md:p-6 shadow-sm border border-gray-200 mb-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="relative">
                            <input type="text" placeholder="搜索分类名称..." class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent w-full sm:w-64" id="search-input">
                            <i class="fa fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        </div>
                        
                        <select class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" id="status-filter">
                            <option value="">全部状态</option>
                            <option value="active">启用</option>
                            <option value="inactive">禁用</option>
                        </select>
                    </div>
                    
                    <button class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors" id="add-category-btn">
                        <i class="fa fa-plus mr-2"></i>添加分类
                    </button>
                </div>
            </div>
            
            <!-- 分类列表 -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="p-4 md:p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-bold">分类列表</h2>
                        <span class="text-sm text-gray-500" id="total-count">共 0 个分类</span>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">分类名称</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">描述</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">壁纸数量</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">排序</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">状态</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">创建时间</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="category-list">
                            <!-- 分类数据将通过JavaScript动态加载 -->
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

    <!-- 添加/编辑分类模态框 -->
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden" id="category-modal">
        <div class="bg-white rounded-xl max-w-md w-full mx-4">
            <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-bold" id="modal-title">添加分类</h3>
                <button class="text-gray-500 hover:text-gray-700" id="close-modal">
                    <i class="fa fa-times text-xl"></i>
                </button>
            </div>
            <form id="category-form" class="p-4">
                <input type="hidden" id="category-id">
                
                <div class="mb-4">
                    <label for="category-name" class="block text-sm font-medium text-gray-700 mb-2">分类名称</label>
                    <input type="text" id="category-name" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="请输入分类名称">
                </div>
                
                <div class="mb-4">
                    <label for="category-description" class="block text-sm font-medium text-gray-700 mb-2">分类描述</label>
                    <textarea id="category-description" name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="请输入分类描述（可选）"></textarea>
                </div>
                
                <div class="mb-4">
                    <label for="category-sort" class="block text-sm font-medium text-gray-700 mb-2">排序权重</label>
                    <input type="number" id="category-sort" name="sort_order" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="数字越大排序越靠前">
                </div>
                
                <div class="mb-6">
                    <label class="flex items-center">
                        <input type="checkbox" id="category-status" name="is_active" class="rounded border-gray-300 text-primary focus:ring-primary">
                        <span class="ml-2 text-sm text-gray-700">启用此分类</span>
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
        
        // 加载分类列表
        async function loadCategories(page = 1) {
            try {
                const statusFilter = document.getElementById('status-filter').value;
                const searchQuery = document.getElementById('search-input').value;
                
                const params = new URLSearchParams({
                    page: page,
                    limit: 20,
                    status: statusFilter,
                    search: searchQuery
                });
                
                const response = await fetch(`/api/admin/categories-list.php?${params}`);
                const data = await response.json();
                
                if (data.success) {
                    renderCategoryList(data.categories);
                    updatePagination(data.pagination);
                    document.getElementById('total-count').textContent = `共 ${data.pagination.total} 个分类`;
                }
            } catch (error) {
                console.error('加载分类列表失败:', error);
            }
        }
        
        // 渲染分类列表
        function renderCategoryList(categories) {
            const tbody = document.getElementById('category-list');
            tbody.innerHTML = categories.map(category => `
                <tr class="table-row-hover">
                    <td class="px-4 py-3">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
                                <i class="fa fa-folder text-primary"></i>
                            </div>
                            <div>
                                <div class="font-medium">${category.name}</div>
                                <div class="text-sm text-gray-500">${category.slug}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="text-sm text-gray-600">${category.description || '-'}</div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-sm font-medium">${category.wallpaper_count}</span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-sm">${category.sort_order}</span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="badge badge-${category.is_active ? 'active' : 'inactive'}">
                            ${category.is_active ? '启用' : '禁用'}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500">
                        ${formatDate(category.created_at)}
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex space-x-2">
                            <button class="text-primary hover:text-primary/80" onclick="editCategory(${category.id})">
                                <i class="fa fa-edit"></i>
                            </button>
                            <button class="text-${category.is_active ? 'warning' : 'success'} hover:opacity-80" onclick="toggleCategoryStatus(${category.id}, ${category.is_active})">
                                <i class="fa fa-${category.is_active ? 'pause' : 'play'}"></i>
                            </button>
                            <button class="text-danger hover:text-danger/80" onclick="deleteCategory(${category.id})">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }
        
        // 格式化日期
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString('zh-CN');
        }
        
        // 显示添加分类模态框
        document.getElementById('add-category-btn').addEventListener('click', () => {
            document.getElementById('modal-title').textContent = '添加分类';
            document.getElementById('submit-text').textContent = '添加';
            document.getElementById('category-form').reset();
            document.getElementById('category-id').value = '';
            document.getElementById('category-status').checked = true;
            document.getElementById('category-modal').classList.remove('hidden');
        });
        
        // 编辑分类
        async function editCategory(categoryId) {
            try {
                const response = await fetch(`/api/admin/category-detail.php?id=${categoryId}`);
                const data = await response.json();
                
                if (data.success) {
                    const category = data.category;
                    document.getElementById('modal-title').textContent = '编辑分类';
                    document.getElementById('submit-text').textContent = '保存';
                    document.getElementById('category-id').value = category.id;
                    document.getElementById('category-name').value = category.name;
                    document.getElementById('category-description').value = category.description || '';
                    document.getElementById('category-sort').value = category.sort_order;
                    document.getElementById('category-status').checked = category.is_active;
                    document.getElementById('category-modal').classList.remove('hidden');
                }
            } catch (error) {
                console.error('加载分类详情失败:', error);
            }
        }
        
        // 关闭模态框
        function closeModal() {
            document.getElementById('category-modal').classList.add('hidden');
        }
        
        document.getElementById('close-modal').addEventListener('click', closeModal);
        document.getElementById('cancel-btn').addEventListener('click', closeModal);
        
        // 提交表单
        document.getElementById('category-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const categoryId = document.getElementById('category-id').value;
            
            const data = {
                name: formData.get('name'),
                description: formData.get('description'),
                sort_order: parseInt(formData.get('sort_order')) || 0,
                is_active: formData.get('is_active') === 'on'
            };
            
            if (categoryId) {
                data.id = categoryId;
            }
            
            try {
                const url = categoryId ? '/api/admin/update-category.php' : '/api/admin/create-category.php';
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
                    loadCategories(currentPage);
                } else {
                    alert(result.message || '操作失败');
                }
            } catch (error) {
                console.error('保存分类失败:', error);
                alert('保存失败，请重试');
            }
        });
        
        // 切换分类状态
        async function toggleCategoryStatus(categoryId, currentStatus) {
            const action = currentStatus ? '禁用' : '启用';
            if (confirm(`确定要${action}此分类吗？`)) {
                try {
                    const response = await fetch('/api/admin/toggle-category-status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ 
                            category_id: categoryId, 
                            is_active: !currentStatus 
                        })
                    });
                    
                    const data = await response.json();
                    if (data.success) {
                        loadCategories(currentPage);
                    }
                } catch (error) {
                    console.error('切换分类状态失败:', error);
                }
            }
        }
        
        // 删除分类
        async function deleteCategory(categoryId) {
            if (confirm('确定要删除此分类吗？删除后不可恢复！')) {
                try {
                    const response = await fetch('/api/admin/delete-category.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ category_id: categoryId })
                    });
                    
                    const data = await response.json();
                    if (data.success) {
                        loadCategories(currentPage);
                    } else {
                        alert(data.message || '删除失败');
                    }
                } catch (error) {
                    console.error('删除分类失败:', error);
                }
            }
        }
        
        // 筛选和搜索
        document.getElementById('status-filter').addEventListener('change', () => loadCategories(1));
        document.getElementById('search-input').addEventListener('input', debounce(() => loadCategories(1), 500));
        
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
            document.getElementById('prev-page').disabled = currentPage <= 1;
            document.getElementById('next-page').disabled = currentPage >= totalPages;
        }
        
        // 分页事件
        document.getElementById('prev-page').addEventListener('click', () => {
            if (currentPage > 1) {
                loadCategories(currentPage - 1);
            }
        });
        
        document.getElementById('next-page').addEventListener('click', () => {
            if (currentPage < totalPages) {
                loadCategories(currentPage + 1);
            }
        });
        
        // 页面加载完成后初始化
        document.addEventListener('DOMContentLoaded', () => {
            loadCategories(1);
        });
    </script>
</body>
</html>
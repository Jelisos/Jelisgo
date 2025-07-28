/**
 * 用户管理功能
 * 位置: static/js/admin-users.js
 */
class AdminUsers {
    constructor() {
        this.currentPage = 1;
        this.pageSize = 20;
        this.currentStatus = 'all';
        this.currentRole = 'all';
        this.searchKeyword = '';
        this.currentUserId = null;
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.loadUsers();
        this.loadStats();
    }
    
    /**
     * 绑定事件
     */
    bindEvents() {
        // 状态筛选
        document.getElementById('status-filter').addEventListener('change', (e) => {
            this.currentStatus = e.target.value;
            this.currentPage = 1;
            this.loadUsers();
        });
        
        // 角色筛选
        document.getElementById('role-filter').addEventListener('change', (e) => {
            this.currentRole = e.target.value;
            this.currentPage = 1;
            this.loadUsers();
        });
        
        // 搜索
        document.getElementById('search-input').addEventListener('input', (e) => {
            this.searchKeyword = e.target.value;
            this.currentPage = 1;
            this.debounceSearch();
        });
        
        // 刷新按钮
        document.getElementById('refresh-btn').addEventListener('click', () => {
            this.loadUsers();
            this.loadStats();
        });
        
        // 用户详情模态框
        document.getElementById('close-user-detail').addEventListener('click', () => {
            this.closeUserDetail();
        });
        
        // 封禁用户模态框
        document.getElementById('cancel-ban').addEventListener('click', () => {
            this.closeBanModal();
        });
        
        document.getElementById('confirm-ban').addEventListener('click', () => {
            this.confirmBan();
        });
        
        // 角色设置模态框
        document.getElementById('cancel-role').addEventListener('click', () => {
            this.closeRoleModal();
        });
        
        document.getElementById('confirm-role').addEventListener('click', () => {
            this.confirmRole();
        });
        
        // 点击模态框背景关闭
        document.getElementById('user-detail-modal').addEventListener('click', (e) => {
            if (e.target.id === 'user-detail-modal') {
                this.closeUserDetail();
            }
        });
        
        document.getElementById('ban-user-modal').addEventListener('click', (e) => {
            if (e.target.id === 'ban-user-modal') {
                this.closeBanModal();
            }
        });
        
        document.getElementById('role-modal').addEventListener('click', (e) => {
            if (e.target.id === 'role-modal') {
                this.closeRoleModal();
            }
        });
    }
    
    /**
     * 防抖搜索
     */
    debounceSearch() {
        clearTimeout(this.searchTimer);
        this.searchTimer = setTimeout(() => {
            this.loadUsers();
        }, 500);
    }
    
    /**
     * 加载用户列表
     */
    async loadUsers() {
        try {
            const params = new URLSearchParams({
                action: 'list',
                page: this.currentPage,
                limit: this.pageSize
            });
            
            if (this.currentStatus !== 'all') {
                params.append('status', this.currentStatus);
            }
            
            if (this.searchKeyword) {
                params.append('search', this.searchKeyword);
            }
            
            const response = await fetch(`/api/admin_users.php?${params}`);
            const result = await response.json();
            
            if (result.code === 200) {
                this.renderUserList(result.data.list);
                this.renderPagination(result.data);
            } else {
                this.showError('获取用户列表失败: ' + result.msg);
            }
        } catch (error) {
            this.showError('请求失败: ' + error.message);
        }
    }
    
    /**
     * 加载统计数据
     */
    async loadStats() {
        try {
            const response = await fetch('/api/admin_users.php?action=stats');
            const result = await response.json();
            
            if (result.code === 200) {
                this.updateStats(result.data);
            }
        } catch (error) {
            console.error('获取统计数据失败:', error);
        }
    }
    
    /**
     * 更新统计数据
     */
    updateStats(data) {
        document.getElementById('total-users').textContent = data.user_stats.total_users || 0;
        document.getElementById('active-users').textContent = data.user_stats.active_users || 0;
        document.getElementById('banned-users').textContent = data.user_stats.banned_users || 0;
        document.getElementById('today-new-users').textContent = data.today_new_users || 0;
    }
    
    /**
     * 渲染用户列表
     */
    renderUserList(users) {
        const tbody = document.getElementById('user-list');
        
        if (users.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-users text-4xl mb-4 block"></i>
                        暂无数据
                    </td>
                </tr>
            `;
            return;
        }
        
        tbody.innerHTML = users.map(user => `
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10">
                            <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                <i class="fas fa-user text-gray-600"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900">${user.username}</div>
                            <div class="text-sm text-gray-500">ID: ${user.id}</div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${user.email}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    ${this.renderRoleBadge(user.role)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    ${this.renderStatusBadge(user.user_status || 'active')}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${user.wallpaper_count || 0}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${this.formatDate(user.created_at)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    ${this.renderActionButtons(user)}
                </td>
            </tr>
        `).join('');
    }
    
    /**
     * 渲染角色徽章
     */
    renderRoleBadge(role) {
        const badges = {
            admin: '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">管理员</span>',
            user: '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">普通用户</span>'
        };
        return badges[role] || badges.user;
    }
    
    /**
     * 渲染状态徽章
     */
    renderStatusBadge(status) {
        const badges = {
            active: '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">正常</span>',
            banned: '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">封禁</span>'
        };
        return badges[status] || badges.active;
    }
    
    /**
     * 渲染操作按钮
     */
    renderActionButtons(user) {
        const status = user.user_status || 'active';
        const isCurrentUser = user.id === this.getCurrentUserId(); // 假设有获取当前用户ID的方法
        
        let buttons = `
            <button onclick="adminUsers.showUserDetail(${user.id})" 
                    class="text-blue-600 hover:text-blue-900 mr-3">
                <i class="fas fa-eye mr-1"></i>详情
            </button>
        `;
        
        if (!isCurrentUser) {
            if (status === 'active') {
                buttons += `
                    <button onclick="adminUsers.showBanModal(${user.id})" 
                            class="text-red-600 hover:text-red-900 mr-3">
                        <i class="fas fa-ban mr-1"></i>封禁
                    </button>
                `;
            } else {
                buttons += `
                    <button onclick="adminUsers.unbanUser(${user.id})" 
                            class="text-green-600 hover:text-green-900 mr-3">
                        <i class="fas fa-unlock mr-1"></i>解封
                    </button>
                `;
            }
            
            buttons += `
                <button onclick="adminUsers.showRoleModal(${user.id}, '${user.role}')" 
                        class="text-purple-600 hover:text-purple-900">
                    <i class="fas fa-user-cog mr-1"></i>角色
                </button>
            `;
        }
        
        return buttons;
    }
    
    /**
     * 渲染分页
     */
    renderPagination(data) {
        const { total, page, limit } = data;
        const totalPages = Math.ceil(total / limit);
        const start = (page - 1) * limit + 1;
        const end = Math.min(page * limit, total);
        
        // 更新信息显示
        document.getElementById('page-start').textContent = start;
        document.getElementById('page-end').textContent = end;
        document.getElementById('total-items').textContent = total;
        
        // 生成分页按钮
        const pagination = document.getElementById('pagination');
        let paginationHTML = '';
        
        // 上一页
        if (page > 1) {
            paginationHTML += `
                <button onclick="adminUsers.goToPage(${page - 1})" 
                        class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                    <i class="fas fa-chevron-left"></i>
                </button>
            `;
        }
        
        // 页码按钮
        const startPage = Math.max(1, page - 2);
        const endPage = Math.min(totalPages, page + 2);
        
        for (let i = startPage; i <= endPage; i++) {
            const isActive = i === page;
            paginationHTML += `
                <button onclick="adminUsers.goToPage(${i})" 
                        class="relative inline-flex items-center px-4 py-2 border text-sm font-medium ${
                            isActive 
                                ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' 
                                : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                        }">
                    ${i}
                </button>
            `;
        }
        
        // 下一页
        if (page < totalPages) {
            paginationHTML += `
                <button onclick="adminUsers.goToPage(${page + 1})" 
                        class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                    <i class="fas fa-chevron-right"></i>
                </button>
            `;
        }
        
        pagination.innerHTML = paginationHTML;
    }
    
    /**
     * 跳转页面
     */
    goToPage(page) {
        this.currentPage = page;
        this.loadUsers();
    }
    
    /**
     * 显示用户详情
     */
    async showUserDetail(userId) {
        try {
            const response = await fetch(`/api/admin_users.php?action=detail&id=${userId}`);
            const result = await response.json();
            
            if (result.code === 200) {
                this.renderUserDetail(result.data);
                document.getElementById('user-detail-modal').classList.remove('hidden');
            } else {
                this.showError('获取用户详情失败: ' + result.msg);
            }
        } catch (error) {
            this.showError('请求失败: ' + error.message);
        }
    }
    
    /**
     * 渲染用户详情
     */
    renderUserDetail(user) {
        const content = document.getElementById('user-detail-content');
        
        content.innerHTML = `
            <div class="space-y-6">
                <!-- 基本信息 -->
                <div>
                    <h4 class="text-lg font-medium text-gray-900 mb-4">基本信息</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">用户名</label>
                            <p class="mt-1 text-sm text-gray-900">${user.username}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">邮箱</label>
                            <p class="mt-1 text-sm text-gray-900">${user.email}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">角色</label>
                            <p class="mt-1">${this.renderRoleBadge(user.role)}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">状态</label>
                            <p class="mt-1">${this.renderStatusBadge(user.user_status || 'active')}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">注册时间</label>
                            <p class="mt-1 text-sm text-gray-900">${this.formatDate(user.created_at)}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">最后登录</label>
                            <p class="mt-1 text-sm text-gray-900">${user.last_login_time ? this.formatDate(user.last_login_time) : '从未登录'}</p>
                        </div>
                    </div>
                    ${user.ban_reason ? `
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700">封禁原因</label>
                            <p class="mt-1 text-sm text-red-600">${user.ban_reason}</p>
                        </div>
                    ` : ''}
                </div>
                
                <!-- 壁纸统计 -->
                <div>
                    <h4 class="text-lg font-medium text-gray-900 mb-4">壁纸统计</h4>
                    <div class="grid grid-cols-4 gap-4">
                        <div class="text-center">
                            <p class="text-2xl font-semibold text-gray-900">${user.wallpaper_stats?.total_wallpapers || 0}</p>
                            <p class="text-sm text-gray-500">总上传</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-semibold text-green-600">${user.wallpaper_stats?.approved_wallpapers || 0}</p>
                            <p class="text-sm text-gray-500">已通过</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-semibold text-yellow-600">${user.wallpaper_stats?.pending_wallpapers || 0}</p>
                            <p class="text-sm text-gray-500">待审核</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-semibold text-red-600">${user.wallpaper_stats?.rejected_wallpapers || 0}</p>
                            <p class="text-sm text-gray-500">已拒绝</p>
                        </div>
                    </div>
                </div>
                
                <!-- 最近上传 -->
                ${user.recent_wallpapers && user.recent_wallpapers.length > 0 ? `
                    <div>
                        <h4 class="text-lg font-medium text-gray-900 mb-4">最近上传</h4>
                        <div class="grid grid-cols-5 gap-4">
                            ${user.recent_wallpapers.slice(0, 5).map(wallpaper => `
                                <div class="text-center">
                                    <img src="/static/wallpapers/preview/${wallpaper.file_path.replace(/^static\/wallpapers\//, '')}" alt="${wallpaper.title}" 
                                         class="w-full h-20 object-cover rounded mb-2">
                                    <p class="text-xs text-gray-600 truncate">${wallpaper.title || '无标题'}</p>
                                    <p class="text-xs ${wallpaper.review_status === 'approved' ? 'text-green-600' : wallpaper.review_status === 'rejected' ? 'text-red-600' : 'text-yellow-600'}">
                                        ${wallpaper.review_status === 'approved' ? '已通过' : wallpaper.review_status === 'rejected' ? '已拒绝' : '待审核'}
                                    </p>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                ` : ''}
            </div>
        `;
    }
    
    /**
     * 关闭用户详情
     */
    closeUserDetail() {
        document.getElementById('user-detail-modal').classList.add('hidden');
    }
    
    /**
     * 显示封禁模态框
     */
    showBanModal(userId) {
        this.currentUserId = userId;
        document.getElementById('ban-reason').value = '';
        document.getElementById('ban-user-modal').classList.remove('hidden');
    }
    
    /**
     * 关闭封禁模态框
     */
    closeBanModal() {
        this.currentUserId = null;
        document.getElementById('ban-user-modal').classList.add('hidden');
    }
    
    /**
     * 确认封禁
     */
    async confirmBan() {
        const reason = document.getElementById('ban-reason').value.trim();
        
        if (!reason) {
            alert('请输入封禁原因');
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('action', 'ban');
            formData.append('user_id', this.currentUserId);
            formData.append('reason', reason);
            
            const response = await fetch('/api/admin_users.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.code === 200) {
                this.showSuccess('用户封禁成功');
                this.closeBanModal();
                this.loadUsers();
                this.loadStats();
            } else {
                this.showError('封禁失败: ' + result.msg);
            }
        } catch (error) {
            this.showError('请求失败: ' + error.message);
        }
    }
    
    /**
     * 解封用户
     */
    async unbanUser(userId) {
        if (!confirm('确定要解封这个用户吗？')) return;
        
        try {
            const formData = new FormData();
            formData.append('action', 'unban');
            formData.append('user_id', userId);
            
            const response = await fetch('/api/admin_users.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.code === 200) {
                this.showSuccess('用户解封成功');
                this.loadUsers();
                this.loadStats();
            } else {
                this.showError('解封失败: ' + result.msg);
            }
        } catch (error) {
            this.showError('请求失败: ' + error.message);
        }
    }
    
    /**
     * 显示角色设置模态框
     */
    showRoleModal(userId, currentRole) {
        this.currentUserId = userId;
        document.getElementById('new-role').value = currentRole;
        document.getElementById('role-modal').classList.remove('hidden');
    }
    
    /**
     * 关闭角色设置模态框
     */
    closeRoleModal() {
        this.currentUserId = null;
        document.getElementById('role-modal').classList.add('hidden');
    }
    
    /**
     * 确认设置角色
     */
    async confirmRole() {
        const newRole = document.getElementById('new-role').value;
        
        try {
            const formData = new FormData();
            formData.append('action', 'set_role');
            formData.append('user_id', this.currentUserId);
            formData.append('role', newRole);
            
            const response = await fetch('/api/admin_users.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.code === 200) {
                this.showSuccess('用户角色设置成功');
                this.closeRoleModal();
                this.loadUsers();
                this.loadStats();
            } else {
                this.showError('设置失败: ' + result.msg);
            }
        } catch (error) {
            this.showError('请求失败: ' + error.message);
        }
    }
    
    /**
     * 获取当前用户ID（模拟）
     */
    getCurrentUserId() {
        // 这里应该从session或其他地方获取当前登录用户的ID
        // 暂时返回null，表示不限制操作
        return null;
    }
    
    /**
     * 格式化日期
     */
    formatDate(dateStr) {
        if (!dateStr) return '未知';
        const date = new Date(dateStr);
        return date.toLocaleDateString('zh-CN') + ' ' + date.toLocaleTimeString('zh-CN', { hour12: false });
    }
    
    /**
     * 显示成功消息
     */
    showSuccess(message) {
        alert(message);
    }
    
    /**
     * 显示错误消息
     */
    showError(message) {
        alert(message);
    }
}

// 初始化
const adminUsers = new AdminUsers();
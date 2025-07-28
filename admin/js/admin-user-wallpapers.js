/**
 * 文件: admin/js/admin-user-wallpapers.js
 * 描述: 用户上传壁纸管理功能
 * 维护: 用户上传壁纸管理相关功能修改请编辑此文件
 */

class AdminUserWallpapers {
    constructor() {
        this.currentPage = 1;
        this.pageSize = 20;
        this.currentStatus = '';
        this.currentCategory = '';
        this.searchKeyword = '';
        this.API_BASE_URL = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1' 
            ? 'http://localhost/api' 
            : '/api';
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.loadWallpapers();
    }
    
    bindEvents() {
        // 刷新按钮
        document.getElementById('refresh-btn').addEventListener('click', () => {
            this.currentPage = 1;
            this.loadWallpapers();
        });
        
        // 搜索按钮
        document.getElementById('search-btn').addEventListener('click', () => {
            this.handleSearch();
        });
        
        // 搜索输入框回车
        document.getElementById('search-input').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.handleSearch();
            }
        });
        
        // 筛选器变化
        document.getElementById('status-filter').addEventListener('change', () => {
            this.handleSearch();
        });
        
        document.getElementById('category-filter').addEventListener('change', () => {
            this.handleSearch();
        });
        
        // 分页按钮
        document.getElementById('prev-btn').addEventListener('click', () => {
            if (this.currentPage > 1) {
                this.currentPage--;
                this.loadWallpapers();
            }
        });
        
        document.getElementById('next-btn').addEventListener('click', () => {
            this.currentPage++;
            this.loadWallpapers();
        });
        
        // 模态框关闭
        document.getElementById('close-detail-modal').addEventListener('click', () => {
            this.closeDetailModal();
        });
        
        // 点击模态框外部关闭
        document.getElementById('detail-modal').addEventListener('click', (e) => {
            if (e.target.id === 'detail-modal') {
                this.closeDetailModal();
            }
        });
    }
    
    handleSearch() {
        this.currentPage = 1;
        this.currentStatus = document.getElementById('status-filter').value;
        this.currentCategory = document.getElementById('category-filter').value;
        this.searchKeyword = document.getElementById('search-input').value.trim();
        this.loadWallpapers();
    }
    
    async loadWallpapers() {
        try {
            this.showLoading();
            
            // 构建查询参数
            const params = new URLSearchParams({
                page: this.currentPage,
                limit: this.pageSize
            });
            
            if (this.currentStatus) params.append('status', this.currentStatus);
            if (this.currentCategory) params.append('category', this.currentCategory);
            if (this.searchKeyword) params.append('search', this.searchKeyword);
            
            const response = await fetch(`${this.API_BASE_URL}/admin_user_wallpapers.php?${params}`, {
                credentials: 'include'
            });
            
            const result = await response.json();
            
            if (result.code === 200) {
                this.renderWallpapers(result.data.wallpapers);
                this.renderPagination(result.data.pagination);
            } else {
                throw new Error(result.message || '获取数据失败');
            }
            
        } catch (error) {
            console.error('加载壁纸列表失败:', error);
            this.showError('加载失败，请重试');
        } finally {
            this.hideLoading();
        }
    }
    
    renderWallpapers(wallpapers) {
        const tbody = document.getElementById('wallpaper-tbody');
        const wallpaperList = document.getElementById('wallpaper-list');
        const emptyState = document.getElementById('empty-state');
        
        if (wallpapers.length === 0) {
            wallpaperList.classList.add('hidden');
            emptyState.classList.remove('hidden');
            return;
        }
        
        emptyState.classList.add('hidden');
        wallpaperList.classList.remove('hidden');
        
        tbody.innerHTML = wallpapers.map(wallpaper => `
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                    <img src="${wallpaper.file_path}" alt="${wallpaper.title}" 
                         class="wallpaper-thumbnail cursor-pointer" 
                         onclick="adminUserWallpapers.showDetail(${wallpaper.id})">
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm font-medium text-gray-900">${this.escapeHtml(wallpaper.title)}</div>
                    <div class="text-sm text-gray-500">${wallpaper.width} × ${wallpaper.height}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">${this.escapeHtml(wallpaper.user_email)}</div>
                    <div class="text-sm text-gray-500">ID: ${wallpaper.user_id}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="text-sm text-gray-900">${wallpaper.category || '-'}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 py-1 text-xs font-medium rounded-full status-${wallpaper.status}">
                        ${this.getStatusText(wallpaper.status)}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${this.formatDate(wallpaper.created_at)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <div class="flex space-x-2">
                        <button onclick="adminUserWallpapers.showDetail(${wallpaper.id})" 
                                class="text-blue-600 hover:text-blue-900">查看</button>
                        ${wallpaper.status === 'pending' ? `
                            <button onclick="adminUserWallpapers.approveWallpaper(${wallpaper.id})" 
                                    class="text-green-600 hover:text-green-900">通过</button>
                            <button onclick="adminUserWallpapers.rejectWallpaper(${wallpaper.id})" 
                                    class="text-red-600 hover:text-red-900">拒绝</button>
                        ` : ''}
                        <button onclick="adminUserWallpapers.deleteWallpaper(${wallpaper.id})" 
                                class="text-red-600 hover:text-red-900">删除</button>
                    </div>
                </td>
            </tr>
        `).join('');
    }
    
    renderPagination(pagination) {
        const pageInfo = document.getElementById('page-info');
        const prevBtn = document.getElementById('prev-btn');
        const nextBtn = document.getElementById('next-btn');
        const pageNumbers = document.getElementById('page-numbers');
        
        // 更新页面信息
        const start = (pagination.current_page - 1) * pagination.items_per_page + 1;
        const end = Math.min(start + pagination.items_per_page - 1, pagination.total_items);
        pageInfo.textContent = `${start}-${end} 共 ${pagination.total_items}`;
        
        // 更新按钮状态
        prevBtn.disabled = !pagination.has_prev;
        nextBtn.disabled = !pagination.has_next;
        
        // 生成页码
        pageNumbers.innerHTML = '';
        const maxVisiblePages = 5;
        let startPage = Math.max(1, pagination.current_page - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(pagination.total_pages, startPage + maxVisiblePages - 1);
        
        if (endPage - startPage + 1 < maxVisiblePages) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }
        
        for (let i = startPage; i <= endPage; i++) {
            const pageBtn = document.createElement('button');
            pageBtn.textContent = i;
            pageBtn.className = `px-3 py-2 border rounded-lg ${
                i === pagination.current_page 
                    ? 'bg-primary text-white border-primary' 
                    : 'border-gray-300 text-gray-600 hover:bg-gray-50'
            }`;
            pageBtn.addEventListener('click', () => {
                this.currentPage = i;
                this.loadWallpapers();
            });
            pageNumbers.appendChild(pageBtn);
        }
    }
    
    async showDetail(wallpaperId) {
        try {
            const response = await fetch(`${this.API_BASE_URL}/admin_user_wallpapers.php?action=detail&id=${wallpaperId}`, {
                credentials: 'include'
            });
            
            const result = await response.json();
            
            if (result.code === 200) {
                this.renderDetail(result.data);
                document.getElementById('detail-modal').classList.remove('hidden');
            } else {
                throw new Error(result.message || '获取详情失败');
            }
            
        } catch (error) {
            console.error('获取壁纸详情失败:', error);
            alert('获取详情失败，请重试');
        }
    }
    
    renderDetail(wallpaper) {
        const content = document.getElementById('detail-content');
        content.innerHTML = `
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div>
                    <img src="${wallpaper.file_path}" alt="${wallpaper.title}" 
                         class="w-full rounded-lg shadow-md">
                </div>
                <div class="space-y-4">
                    <div>
                        <h4 class="text-lg font-medium text-gray-900 mb-2">基本信息</h4>
                        <div class="space-y-2">
                            <div><span class="font-medium">标题:</span> ${this.escapeHtml(wallpaper.title)}</div>
                            <div><span class="font-medium">分类:</span> ${wallpaper.category || '-'}</div>
                            <div><span class="font-medium">尺寸:</span> ${wallpaper.width} × ${wallpaper.height}</div>
                            <div><span class="font-medium">格式:</span> ${wallpaper.format}</div>
                            <div><span class="font-medium">文件大小:</span> ${wallpaper.file_size}</div>
                            <div><span class="font-medium">状态:</span> 
                                <span class="px-2 py-1 text-xs font-medium rounded-full status-${wallpaper.status}">
                                    ${this.getStatusText(wallpaper.status)}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="text-lg font-medium text-gray-900 mb-2">用户信息</h4>
                        <div class="space-y-2">
                            <div><span class="font-medium">用户ID:</span> ${wallpaper.user_id}</div>
                            <div><span class="font-medium">邮箱:</span> ${this.escapeHtml(wallpaper.user_email)}</div>
                        </div>
                    </div>
                    
                    ${wallpaper.description ? `
                        <div>
                            <h4 class="text-lg font-medium text-gray-900 mb-2">描述</h4>
                            <p class="text-gray-700">${this.escapeHtml(wallpaper.description)}</p>
                        </div>
                    ` : ''}
                    
                    ${wallpaper.tags && wallpaper.tags.length > 0 ? `
                        <div>
                            <h4 class="text-lg font-medium text-gray-900 mb-2">标签</h4>
                            <div class="flex flex-wrap gap-2">
                                ${wallpaper.tags.map(tag => `
                                    <span class="px-2 py-1 bg-gray-100 text-gray-700 text-sm rounded">${this.escapeHtml(tag)}</span>
                                `).join('')}
                            </div>
                        </div>
                    ` : ''}
                    
                    <div>
                        <h4 class="text-lg font-medium text-gray-900 mb-2">统计信息</h4>
                        <div class="space-y-2">
                            <div><span class="font-medium">浏览次数:</span> ${wallpaper.views}</div>
                            <div><span class="font-medium">点赞数:</span> ${wallpaper.likes}</div>
                            <div><span class="font-medium">上传时间:</span> ${this.formatDate(wallpaper.created_at)}</div>
                            <div><span class="font-medium">更新时间:</span> ${this.formatDate(wallpaper.updated_at)}</div>
                        </div>
                    </div>
                    
                    ${wallpaper.status === 'pending' ? `
                        <div class="flex space-x-3 pt-4">
                            <button onclick="adminUserWallpapers.approveWallpaper(${wallpaper.id})" 
                                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                                通过审核
                            </button>
                            <button onclick="adminUserWallpapers.rejectWallpaper(${wallpaper.id})" 
                                    class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                                拒绝审核
                            </button>
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    }
    
    closeDetailModal() {
        document.getElementById('detail-modal').classList.add('hidden');
    }
    
    async approveWallpaper(wallpaperId) {
        if (!confirm('确定要通过这个壁纸的审核吗？')) return;
        
        try {
            const response = await fetch(`${this.API_BASE_URL}/admin_user_wallpapers.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'include',
                body: JSON.stringify({
                    action: 'approve',
                    id: wallpaperId
                })
            });
            
            const result = await response.json();
            
            if (result.code === 200) {
                alert('审核通过成功');
                this.closeDetailModal();
                this.loadWallpapers();
            } else {
                throw new Error(result.message || '操作失败');
            }
            
        } catch (error) {
            console.error('审核通过失败:', error);
            alert('操作失败，请重试');
        }
    }
    
    async rejectWallpaper(wallpaperId) {
        const reason = prompt('请输入拒绝原因（可选）:');
        if (reason === null) return; // 用户取消
        
        try {
            const response = await fetch(`${this.API_BASE_URL}/admin_user_wallpapers.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'include',
                body: JSON.stringify({
                    action: 'reject',
                    id: wallpaperId,
                    reason: reason
                })
            });
            
            const result = await response.json();
            
            if (result.code === 200) {
                alert('审核拒绝成功');
                this.closeDetailModal();
                this.loadWallpapers();
            } else {
                throw new Error(result.message || '操作失败');
            }
            
        } catch (error) {
            console.error('审核拒绝失败:', error);
            alert('操作失败，请重试');
        }
    }
    
    async deleteWallpaper(wallpaperId) {
        if (!confirm('确定要删除这个壁纸吗？此操作不可恢复！')) return;
        
        try {
            const response = await fetch(`${this.API_BASE_URL}/admin_user_wallpapers.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'include',
                body: JSON.stringify({
                    action: 'delete',
                    id: wallpaperId
                })
            });
            
            const result = await response.json();
            
            if (result.code === 200) {
                alert('删除成功');
                this.closeDetailModal();
                this.loadWallpapers();
            } else {
                throw new Error(result.message || '删除失败');
            }
            
        } catch (error) {
            console.error('删除失败:', error);
            alert('删除失败，请重试');
        }
    }
    
    showLoading() {
        document.getElementById('loading').classList.remove('hidden');
        document.getElementById('wallpaper-list').classList.add('hidden');
        document.getElementById('empty-state').classList.add('hidden');
    }
    
    hideLoading() {
        document.getElementById('loading').classList.add('hidden');
    }
    
    showError(message) {
        alert(message);
    }
    
    getStatusText(status) {
        const statusMap = {
            'pending': '待审核',
            'approved': '已通过',
            'rejected': '已拒绝'
        };
        return statusMap[status] || status;
    }
    
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString('zh-CN');
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// 全局实例
let adminUserWallpapers;

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', () => {
    adminUserWallpapers = new AdminUserWallpapers();
});
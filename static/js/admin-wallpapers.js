/**
 * 壁纸审核管理功能
 * 位置: static/js/admin-wallpapers.js
 */
class AdminWallpapers {
    constructor() {
        this.currentPage = 1;
        this.pageSize = 20;
        this.currentStatus = 'pending';
        this.searchKeyword = '';
        this.selectedWallpapers = new Set();
        this.currentRejectId = null;
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.loadWallpapers();
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
            this.loadWallpapers();
        });
        
        // 搜索
        document.getElementById('search-input').addEventListener('input', (e) => {
            this.searchKeyword = e.target.value;
            this.currentPage = 1;
            this.debounceSearch();
        });
        
        // 刷新按钮
        document.getElementById('refresh-btn').addEventListener('click', () => {
            this.loadWallpapers();
            this.loadStats();
        });
        
        // 全选
        document.getElementById('select-all').addEventListener('change', (e) => {
            this.toggleSelectAll(e.target.checked);
        });
        
        // 批量通过
        document.getElementById('batch-approve-btn').addEventListener('click', () => {
            this.batchApprove();
        });
        
        // 预览模态框关闭
        document.getElementById('close-preview').addEventListener('click', () => {
            this.closePreview();
        });
        
        // 拒绝模态框事件
        document.getElementById('cancel-reject').addEventListener('click', () => {
            this.closeRejectModal();
        });
        
        document.getElementById('confirm-reject').addEventListener('click', () => {
            this.confirmReject();
        });
        
        // 点击模态框背景关闭
        document.getElementById('preview-modal').addEventListener('click', (e) => {
            if (e.target.id === 'preview-modal') {
                this.closePreview();
            }
        });
        
        document.getElementById('reject-modal').addEventListener('click', (e) => {
            if (e.target.id === 'reject-modal') {
                this.closeRejectModal();
            }
        });
    }
    
    /**
     * 防抖搜索
     */
    debounceSearch() {
        clearTimeout(this.searchTimer);
        this.searchTimer = setTimeout(() => {
            this.loadWallpapers();
        }, 500);
    }
    
    /**
     * 加载壁纸列表
     */
    async loadWallpapers() {
        try {
            const params = new URLSearchParams({
                action: this.currentStatus === 'all' ? 'all' : this.currentStatus,
                page: this.currentPage,
                limit: this.pageSize
            });
            
            if (this.currentStatus !== 'all') {
                params.append('status', this.currentStatus);
            }
            
            if (this.searchKeyword) {
                params.append('search', this.searchKeyword);
            }
            
            const response = await fetch(`/api/admin_wallpapers.php?${params}`);
            const result = await response.json();
            
            if (result.code === 200) {
                this.renderWallpaperList(result.data.list);
                this.renderPagination(result.data);
            } else {
                this.showError('获取壁纸列表失败: ' + result.msg);
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
            const response = await fetch('/api/admin_wallpapers.php?action=all');
            const result = await response.json();
            
            if (result.code === 200) {
                this.updateStats(result.data.list);
            }
        } catch (error) {
            console.error('获取统计数据失败:', error);
        }
    }
    
    /**
     * 更新统计数据
     */
    updateStats(wallpapers) {
        const stats = {
            pending: 0,
            approved: 0,
            rejected: 0,
            total: wallpapers.length
        };
        
        wallpapers.forEach(wallpaper => {
            const status = wallpaper.review_status || 'pending';
            if (stats.hasOwnProperty(status)) {
                stats[status]++;
            }
        });
        
        document.getElementById('pending-count').textContent = stats.pending;
        document.getElementById('approved-count').textContent = stats.approved;
        document.getElementById('rejected-count').textContent = stats.rejected;
        document.getElementById('total-count').textContent = stats.total;
    }
    
    /**
     * 渲染壁纸列表
     */
    renderWallpaperList(wallpapers) {
        const tbody = document.getElementById('wallpaper-list');
        
        if (wallpapers.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-image text-4xl mb-4 block"></i>
                        暂无数据
                    </td>
                </tr>
            `;
            return;
        }
        
        tbody.innerHTML = wallpapers.map(wallpaper => `
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                    <input type="checkbox" class="wallpaper-checkbox rounded" 
                           value="${wallpaper.id}" 
                           onchange="adminWallpapers.toggleWallpaperSelect(${wallpaper.id}, this.checked)">
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <img src="/static/wallpapers/preview/${wallpaper.file_path.replace(/^static\/wallpapers\//, '')}" 
                         alt="${wallpaper.title}" 
                         class="w-16 h-16 object-cover rounded cursor-pointer hover:opacity-80"
                         onclick="adminWallpapers.previewWallpaper(${wallpaper.id})">
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm font-medium text-gray-900">${wallpaper.title || '无标题'}</div>
                    <div class="text-sm text-gray-500">${wallpaper.description || '无描述'}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${wallpaper.category || '未分类'}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${wallpaper.uploader_name || '未知'}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${this.formatDate(wallpaper.created_at)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    ${this.renderStatusBadge(wallpaper.review_status || 'pending')}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    ${this.renderActionButtons(wallpaper)}
                </td>
            </tr>
        `).join('');
        
        this.updateBatchButton();
    }
    
    /**
     * 渲染状态徽章
     */
    renderStatusBadge(status) {
        const badges = {
            pending: '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">待审核</span>',
            approved: '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">已通过</span>',
            rejected: '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">已拒绝</span>'
        };
        return badges[status] || badges.pending;
    }
    
    /**
     * 渲染操作按钮
     */
    renderActionButtons(wallpaper) {
        const status = wallpaper.review_status || 'pending';
        
        if (status === 'pending') {
            return `
                <button onclick="adminWallpapers.approveWallpaper(${wallpaper.id})" 
                        class="text-green-600 hover:text-green-900 mr-3">
                    <i class="fas fa-check mr-1"></i>通过
                </button>
                <button onclick="adminWallpapers.showRejectModal(${wallpaper.id})" 
                        class="text-red-600 hover:text-red-900">
                    <i class="fas fa-times mr-1"></i>拒绝
                </button>
            `;
        } else {
            return `
                <button onclick="adminWallpapers.previewWallpaper(${wallpaper.id})" 
                        class="text-blue-600 hover:text-blue-900">
                    <i class="fas fa-eye mr-1"></i>查看
                </button>
            `;
        }
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
                <button onclick="adminWallpapers.goToPage(${page - 1})" 
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
                <button onclick="adminWallpapers.goToPage(${i})" 
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
                <button onclick="adminWallpapers.goToPage(${page + 1})" 
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
        this.loadWallpapers();
    }
    
    /**
     * 切换壁纸选择
     */
    toggleWallpaperSelect(wallpaperId, checked) {
        if (checked) {
            this.selectedWallpapers.add(wallpaperId);
        } else {
            this.selectedWallpapers.delete(wallpaperId);
        }
        this.updateBatchButton();
    }
    
    /**
     * 全选/取消全选
     */
    toggleSelectAll(checked) {
        const checkboxes = document.querySelectorAll('.wallpaper-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = checked;
            const wallpaperId = parseInt(checkbox.value);
            if (checked) {
                this.selectedWallpapers.add(wallpaperId);
            } else {
                this.selectedWallpapers.delete(wallpaperId);
            }
        });
        this.updateBatchButton();
    }
    
    /**
     * 更新批量操作按钮状态
     */
    updateBatchButton() {
        const batchBtn = document.getElementById('batch-approve-btn');
        const hasSelected = this.selectedWallpapers.size > 0;
        batchBtn.disabled = !hasSelected;
        batchBtn.textContent = hasSelected 
            ? `批量通过 (${this.selectedWallpapers.size})` 
            : '批量通过';
    }
    
    /**
     * 预览壁纸
     */
    async previewWallpaper(wallpaperId) {
        try {
            const response = await fetch(`/api/admin_wallpapers.php?action=detail&id=${wallpaperId}`);
            const result = await response.json();
            
            if (result.code === 200) {
                const wallpaper = result.data;
                
                document.getElementById('preview-title').textContent = wallpaper.title || '无标题';
                // 2024-07-31 修复：避免路径重复，file_path已经包含了static/wallpapers/路径
                const filePath = wallpaper.file_path.replace(/^static\/wallpapers\//, '');
                document.getElementById('preview-image').src = '/static/wallpapers/preview/' + filePath;
                document.getElementById('preview-image').alt = wallpaper.title || '壁纸预览';
                
                // 显示详细信息
                const details = `
                    <div class="grid grid-cols-2 gap-4">
                        <div><strong>分类:</strong> ${wallpaper.category || '未分类'}</div>
                        <div><strong>尺寸:</strong> ${wallpaper.width}x${wallpaper.height}</div>
                        <div><strong>文件大小:</strong> ${wallpaper.file_size}</div>
                        <div><strong>格式:</strong> ${wallpaper.format}</div>
                        <div><strong>上传者:</strong> ${wallpaper.uploader_name || '未知'}</div>
                        <div><strong>上传时间:</strong> ${this.formatDate(wallpaper.created_at)}</div>
                    </div>
                    ${wallpaper.description ? `<div class="mt-4"><strong>描述:</strong> ${wallpaper.description}</div>` : ''}
                    ${wallpaper.review_notes ? `<div class="mt-4"><strong>审核备注:</strong> ${wallpaper.review_notes}</div>` : ''}
                `;
                
                document.getElementById('preview-details').innerHTML = details;
                document.getElementById('preview-modal').classList.remove('hidden');
            } else {
                this.showError('获取壁纸详情失败: ' + result.msg);
            }
        } catch (error) {
            this.showError('请求失败: ' + error.message);
        }
    }
    
    /**
     * 关闭预览
     */
    closePreview() {
        document.getElementById('preview-modal').classList.add('hidden');
    }
    
    /**
     * 审核通过
     */
    async approveWallpaper(wallpaperId) {
        if (!confirm('确定要通过这个壁纸的审核吗？')) return;
        
        try {
            const formData = new FormData();
            formData.append('action', 'approve');
            formData.append('wallpaper_id', wallpaperId);
            formData.append('notes', '管理员审核通过');
            
            const response = await fetch('/api/admin_wallpapers.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.code === 200) {
                this.showSuccess('审核通过成功');
                this.loadWallpapers();
                this.loadStats();
            } else {
                this.showError('审核失败: ' + result.msg);
            }
        } catch (error) {
            this.showError('请求失败: ' + error.message);
        }
    }
    
    /**
     * 显示拒绝模态框
     */
    showRejectModal(wallpaperId) {
        this.currentRejectId = wallpaperId;
        document.getElementById('reject-reason').value = '';
        document.getElementById('reject-modal').classList.remove('hidden');
    }
    
    /**
     * 关闭拒绝模态框
     */
    closeRejectModal() {
        this.currentRejectId = null;
        document.getElementById('reject-modal').classList.add('hidden');
    }
    
    /**
     * 确认拒绝
     */
    async confirmReject() {
        const reason = document.getElementById('reject-reason').value.trim();
        
        if (!reason) {
            alert('请输入拒绝原因');
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('action', 'reject');
            formData.append('wallpaper_id', this.currentRejectId);
            formData.append('reason', reason);
            
            const response = await fetch('/api/admin_wallpapers.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.code === 200) {
                this.showSuccess('审核拒绝成功');
                this.closeRejectModal();
                this.loadWallpapers();
                this.loadStats();
            } else {
                this.showError('审核失败: ' + result.msg);
            }
        } catch (error) {
            this.showError('请求失败: ' + error.message);
        }
    }
    
    /**
     * 批量审核通过
     */
    async batchApprove() {
        if (this.selectedWallpapers.size === 0) return;
        
        if (!confirm(`确定要批量通过选中的 ${this.selectedWallpapers.size} 个壁纸吗？`)) return;
        
        try {
            const formData = new FormData();
            formData.append('action', 'batch_approve');
            formData.append('wallpaper_ids', JSON.stringify(Array.from(this.selectedWallpapers)));
            formData.append('notes', '批量审核通过');
            
            const response = await fetch('/api/admin_wallpapers.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.code === 200) {
                this.showSuccess(`批量审核完成，共处理 ${result.data.count} 个壁纸`);
                this.selectedWallpapers.clear();
                document.getElementById('select-all').checked = false;
                this.loadWallpapers();
                this.loadStats();
            } else {
                this.showError('批量审核失败: ' + result.msg);
            }
        } catch (error) {
            this.showError('请求失败: ' + error.message);
        }
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
        // 简单的成功提示，可以后续优化为更好的UI组件
        alert(message);
    }
    
    /**
     * 显示错误消息
     */
    showError(message) {
        // 简单的错误提示，可以后续优化为更好的UI组件
        alert(message);
    }
}

// 初始化
const adminWallpapers = new AdminWallpapers();
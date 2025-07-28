/**
 * 管理后台仪表盘功能
 * 位置: static/js/admin-dashboard.js
 * 依赖: 无
 */
class AdminDashboard {
    constructor() {
        this.refreshInterval = null;
        this.init();
    }
    
    init() {
        this.loadDashboardStats();
        this.loadRecentActivities();
        
        // 每30秒刷新一次统计数据
        this.refreshInterval = setInterval(() => {
            this.loadDashboardStats();
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
            const response = await fetch('/api/admin_dashboard.php?action=stats');
            const result = await response.json();
            
            if (result.code === 200) {
                this.updateStatsCards(result.data);
            } else {
                console.error('[调试-仪表盘] 获取统计数据失败:', result.msg);
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
        const pendingWallpapersEl = document.querySelector('[data-stat="pending_wallpapers"] [data-value]');
        if (pendingWallpapersEl) {
            this.animateNumber(pendingWallpapersEl, stats.pending_wallpapers);
            // 如果有待审核的，添加提醒样式
            const card = pendingWallpapersEl.closest('.bg-white');
            if (card) {
                if (stats.pending_wallpapers > 0) {
                    card.classList.add('ring-2', 'ring-yellow-400');
                } else {
                    card.classList.remove('ring-2', 'ring-yellow-400');
                }
            }
        }
        
        // 更新用户数
        const totalUsersEl = document.querySelector('[data-stat="total_users"] [data-value]');
        if (totalUsersEl) {
            this.animateNumber(totalUsersEl, stats.total_users);
        }
        
        // 更新今日浏览量
        const todayViewsEl = document.querySelector('[data-stat="today_views"] [data-value]');
        if (todayViewsEl) {
            this.animateNumber(todayViewsEl, stats.today_views);
        }
        
        // 更新今日新增用户（如果有这个字段）
        if (stats.today_new_users !== undefined) {
            const todayNewUsersEl = document.querySelector('[data-stat="today_new_users"] [data-value]');
            if (todayNewUsersEl) {
                this.animateNumber(todayNewUsersEl, stats.today_new_users);
            }
        }
        
        // 更新今日新增壁纸（如果有这个字段）
        if (stats.today_new_wallpapers !== undefined) {
            const todayNewWallpapersEl = document.querySelector('[data-stat="today_new_wallpapers"] [data-value]');
            if (todayNewWallpapersEl) {
                this.animateNumber(todayNewWallpapersEl, stats.today_new_wallpapers);
            }
        }
        
        console.log('[调试-仪表盘] 统计数据更新完成:', stats);
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
            const response = await fetch('/api/admin_dashboard.php?action=recent_activities');
            const result = await response.json();
            
            if (result.code === 200) {
                this.updateRecentActivities(result.data);
            } else {
                console.error('[调试-仪表盘] 获取最近活动失败:', result.msg);
            }
        } catch (error) {
            console.error('[调试-仪表盘] 请求失败:', error);
        }
    }
    
    /**
     * 更新最近活动列表
     */
    updateRecentActivities(activities) {
        // 最近上传部分已移除
        
        // 更新最新用户
        const recentUsersEl = document.querySelector('#recent-users-list');
        if (recentUsersEl && activities.recent_users) {
            if (activities.recent_users.length === 0) {
                recentUsersEl.innerHTML = '<div class="text-center text-gray-500 py-4">暂无新用户注册</div>';
            } else {
                recentUsersEl.innerHTML = activities.recent_users.map(user => `
                    <div class="flex items-center justify-between py-3 border-b border-gray-100 hover:bg-gray-50 transition-colors">
                        <div class="flex-1">
                            <p class="font-medium text-gray-900">${this.escapeHtml(user.username)}</p>
                            <p class="text-sm text-gray-500">新用户注册</p>
                        </div>
                        <span class="text-xs text-gray-400 ml-2">${this.formatTime(user.created_at)}</span>
                    </div>
                `).join('');
            }
        }
        
        // 更新管理员操作
        const recentAdminActionsEl = document.querySelector('#recent-admin-actions-list');
        if (recentAdminActionsEl && activities.recent_admin_actions) {
            if (activities.recent_admin_actions.length === 0) {
                recentAdminActionsEl.innerHTML = '<div class="text-center text-gray-500 py-4">暂无管理员操作记录</div>';
            } else {
                recentAdminActionsEl.innerHTML = activities.recent_admin_actions.map(action => `
                    <div class="flex items-center justify-between py-3 border-b border-gray-100 hover:bg-gray-50 transition-colors">
                        <div class="flex-1">
                            <p class="font-medium text-gray-900">${this.escapeHtml(action.action)}</p>
                            <p class="text-sm text-gray-500">
                                ${this.escapeHtml(action.admin_name || '未知管理员')}
                                ${action.details ? ' - ' + this.escapeHtml(action.details) : ''}
                            </p>
                        </div>
                        <span class="text-xs text-gray-400 ml-2">${this.formatTime(action.created_at)}</span>
                    </div>
                `).join('');
            }
        }
        
        console.log('[调试-仪表盘] 最近活动更新完成');
    }
    
    /**
     * 格式化时间
     */
    formatTime(timeStr) {
        if (!timeStr) return '未知时间';
        
        const date = new Date(timeStr);
        const now = new Date();
        const diff = now - date;
        
        if (isNaN(diff)) return '时间格式错误';
        
        if (diff < 60000) { // 1分钟内
            return '刚刚';
        } else if (diff < 3600000) { // 1小时内
            return Math.floor(diff / 60000) + '分钟前';
        } else if (diff < 86400000) { // 24小时内
            return Math.floor(diff / 3600000) + '小时前';
        } else if (diff < 2592000000) { // 30天内
            return Math.floor(diff / 86400000) + '天前';
        } else {
            return date.toLocaleDateString('zh-CN');
        }
    }
    
    /**
     * HTML转义
     */
    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    /**
     * 显示错误信息
     */
    showError(message) {
        // 创建错误提示
        const errorDiv = document.createElement('div');
        errorDiv.className = 'fixed top-4 right-4 bg-red-500 text-white px-4 py-2 rounded shadow-lg z-50';
        errorDiv.textContent = message;
        
        document.body.appendChild(errorDiv);
        
        // 3秒后自动移除
        setTimeout(() => {
            if (errorDiv.parentNode) {
                errorDiv.parentNode.removeChild(errorDiv);
            }
        }, 3000);
    }
    
    /**
     * 手动刷新数据
     */
    refresh() {
        this.loadDashboardStats();
        this.loadRecentActivities();
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

// 导出供其他模块使用
window.AdminDashboard = AdminDashboard;

// 页面加载完成后自动初始化
document.addEventListener('DOMContentLoaded', function() {
    // 检查是否在管理后台页面
    if (document.querySelector('[data-stat]')) {
        window.adminDashboard = new AdminDashboard();
        console.log('[调试-仪表盘] 管理后台仪表盘已初始化');
    }
});
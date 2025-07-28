/**
 * 会员状态管理器 - 重构版本
 * 专门处理会员状态显示和更新
 * 
 * @author AI Assistant
 * @date 2025-01-27
 * @version 2.0
 */

class MembershipStatusManager {
    constructor() {
        this.apiUrl = '/api/vip/membership_status.php';
        this.cache = null;
        this.cacheExpiry = 5 * 60 * 1000; // 5分钟缓存
        this.lastUpdate = 0;
        this.isInitialized = false;
        
        console.log('[会员状态管理器] 初始化');
    }
    
    /**
     * 初始化管理器
     */
    async init() {
        if (this.isInitialized) {
            console.log('[会员状态管理器] 已初始化，跳过');
            return;
        }
        
        try {
            console.log('[会员状态管理器] 开始初始化');
            
            // 先检查本地存储中是否有用户信息
            const storedUser = localStorage.getItem('user');
            if (!storedUser) {
                console.log('[会员状态管理器] 本地存储中未找到用户信息，显示默认状态');
                this.showDefaultMembership();
                this.bindEvents();
                this.isInitialized = true;
                console.log('[会员状态管理器] 初始化完成（未登录状态）');
                return;
            }
            
            // 解析用户信息，确保有效
            try {
                const userObj = JSON.parse(storedUser);
                if (!userObj || !userObj.id) {
                    console.log('[会员状态管理器] 本地存储中的用户信息无效，显示默认状态');
                    this.showDefaultMembership();
                    this.bindEvents();
                    this.isInitialized = true;
                    return;
                }
            } catch (e) {
                console.log('[会员状态管理器] 解析用户信息失败，显示默认状态');
                this.showDefaultMembership();
                this.bindEvents();
                this.isInitialized = true;
                return;
            }
            
            await this.loadStatus();
            this.bindEvents();
            this.isInitialized = true;
            console.log('[会员状态管理器] 初始化完成');
        } catch (error) {
            console.error('[会员状态管理器] 初始化失败:', error);
            // 初始化失败时也显示默认状态
            this.showDefaultMembership();
        }
    }
    
    /**
     * 绑定事件监听器
     */
    bindEvents() {
        // 监听会员升级事件
        document.addEventListener('membership-upgraded', () => {
            console.log('[会员状态管理器] 收到会员升级事件');
            this.clearCache();
            this.loadStatus();
        });
        
        // 监听用户信息加载事件
        document.addEventListener('user-info-loaded', (event) => {
            console.log('[会员状态管理器] 收到用户信息加载事件', event.detail);
            // 用户信息已加载，清除缓存并重新加载会员状态
            this.clearCache();
            this.loadStatus();
        });
        
        // 监听页面可见性变化
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.checkAndRefresh();
            }
        });
    }
    
    /**
     * 检查缓存并刷新
     */
    checkAndRefresh() {
        const now = Date.now();
        if (now - this.lastUpdate > this.cacheExpiry) {
            console.log('[会员状态管理器] 缓存过期，刷新状态');
            this.loadStatus();
        }
    }
    
    /**
     * 清除缓存
     */
    clearCache() {
        this.cache = null;
        this.lastUpdate = 0;
        console.log('[会员状态管理器] 缓存已清除');
    }
    
    /**
     * 从服务器加载会员状态
     */
    async loadStatus(forceRefresh = false) {
        const now = Date.now();
        
        // 检查缓存
        if (!forceRefresh && this.cache && (now - this.lastUpdate < this.cacheExpiry)) {
            console.log('[会员状态管理器] 使用缓存数据');
            this.updateUI(this.cache);
            return this.cache;
        }
        
        // 先检查本地存储中是否有用户信息
        const storedUser = localStorage.getItem('user');
        if (!storedUser) {
            console.log('[会员状态管理器] 本地存储中未找到用户信息，显示默认状态');
            this.showDefaultMembership();
            return null;
        }
        
        // 验证用户信息的有效性
        let userObj;
        try {
            userObj = JSON.parse(storedUser);
            if (!userObj || !userObj.id) {
                console.log('[会员状态管理器] 本地存储中的用户信息无效，显示默认状态');
                this.showDefaultMembership();
                return null;
            }
        } catch (e) {
            console.log('[会员状态管理器] 解析用户信息失败，显示默认状态');
            this.showDefaultMembership();
            return null;
        }
        
        try {
            console.log('[会员状态管理器] 从服务器获取状态');
            
            const response = await fetch(this.apiUrl, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Cache-Control': 'no-cache',
                    'Authorization': `Bearer ${userObj.id}`
                },
                credentials: 'same-origin'
            });
            
            // 特殊处理401未授权错误
            if (response.status === 401) {
                console.log('[会员状态管理器] 用户未登录，显示默认状态');
                // 不抛出错误，而是显示默认状态
                this.showDefaultMembership();
                return null;
            }
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.message || '获取会员状态失败');
            }
            
            // 更新缓存
            this.cache = result.data;
            this.lastUpdate = now;
            
            console.log('[会员状态管理器] 状态获取成功:', result.data);
            
            // 更新UI
            this.updateUI(result.data);
            
            return result.data;
            
        } catch (error) {
            console.error('[会员状态管理器] 获取状态失败:', error);
            // 不显示错误提示，而是显示默认状态
            this.showDefaultMembership();
            return null;
        }
    }
    
    /**
     * 更新UI显示
     */
    updateUI(data) {
        console.log('[会员状态管理器] 更新UI:', data);
        
        try {
            // 更新侧边栏状态
            this.updateSidebarStatus(data);
            
            // 更新其他相关元素
            this.updateOtherElements(data);
            
            // 触发状态更新事件
            document.dispatchEvent(new CustomEvent('membership-status-updated', {
                detail: data
            }));
            
        } catch (error) {
            console.error('[会员状态管理器] UI更新失败:', error);
        }
    }
    
    /**
     * 更新侧边栏状态显示
     */
    updateSidebarStatus(data) {
        // 更新会员状态显示
        const membershipStatusEl = document.getElementById('membership-status');
        if (membershipStatusEl) {
            membershipStatusEl.textContent = data.membership.type_display;
            console.log('[会员状态管理器] 更新会员状态:', data.membership.type_display);
        }
        
        // 更新会员徽章
        const membershipBadgeEl = document.getElementById('membership-badge');
        if (membershipBadgeEl) {
            // 显示徽章
            membershipBadgeEl.classList.remove('hidden');
            // 清除旧的样式类
            membershipBadgeEl.className = membershipBadgeEl.className.replace(/bg-\S+|text-\S+|from-\S+|to-\S+/g, '');
            // 添加新的样式类和基础类
            membershipBadgeEl.className = 'px-2 py-1 text-xs rounded-full ' + data.membership.badge_class;
            membershipBadgeEl.textContent = data.membership.type_display;
            console.log('[会员状态管理器] 更新会员徽章:', data.membership.badge_class);
        }
        
        // 更新到期时间
        const membershipExpiryEl = document.getElementById('membership-expiry');
        if (membershipExpiryEl) {
            if (data.membership.type === 'permanent') {
                membershipExpiryEl.textContent = '永久有效';
                membershipExpiryEl.classList.remove('hidden');
            } else if (data.membership.expires_at_formatted) {
                membershipExpiryEl.textContent = `到期时间：${data.membership.expires_at_formatted}`;
                membershipExpiryEl.classList.remove('hidden');
            } else {
                membershipExpiryEl.classList.add('hidden');
            }
            console.log('[会员状态管理器] 更新到期时间');
        }
        
        // 更新剩余下载次数
        const remainingDownloadsEl = document.getElementById('remaining-downloads');
        if (remainingDownloadsEl) {
            // 确保显示正确的下载次数
            let remainingCount = '0';
            if (data.membership.type === 'permanent') {
                remainingCount = '无限制';
            } else {
                // 使用API返回的download.quota_display字段
                if (data.download && data.download.quota_display !== undefined) {
                    remainingCount = data.download.quota_display.toString();
                } else {
                    // 兼容旧版本API，直接使用download_quota字段
                    const currentQuota = data.download_quota || 0;
                    remainingCount = currentQuota >= 0 ? currentQuota.toString() : '0';
                }
            }
            remainingDownloadsEl.textContent = remainingCount;
            console.log('[会员状态管理器] 更新剩余下载次数:', remainingCount, '数据源:', data.download || data.download_quota);
        }
        
        // 更新会员权益显示
        this.updateMembershipBenefits(data);
        
        // 更新升级按钮显示
        this.updateUpgradeButtons(data);
    }
    
    /**
     * 更新会员权益显示
     */
    updateMembershipBenefits(data) {
        // 隐藏所有权益卡片
        const freeUserBenefits = document.getElementById('free-user-benefits');
        const monthlyMemberBenefits = document.getElementById('monthly-member-benefits');
        const permanentMemberBenefits = document.getElementById('permanent-member-benefits');
        
        if (freeUserBenefits) freeUserBenefits.classList.add('hidden');
        if (monthlyMemberBenefits) monthlyMemberBenefits.classList.add('hidden');
        if (permanentMemberBenefits) permanentMemberBenefits.classList.add('hidden');
        
        // 根据会员类型显示对应的权益卡片
        switch (data.membership.type) {
            case 'free':
                if (freeUserBenefits) {
                    freeUserBenefits.classList.remove('hidden');
                }
                break;
            case 'monthly':
                if (monthlyMemberBenefits) {
                    monthlyMemberBenefits.classList.remove('hidden');
                }
                break;
            case 'permanent':
                if (permanentMemberBenefits) {
                    permanentMemberBenefits.classList.remove('hidden');
                }
                break;
        }
        
        console.log('[会员状态管理器] 更新会员权益显示:', data.membership.type);
    }
    
    /**
     * 更新升级按钮显示
     */
    updateUpgradeButtons(data) {
        // 隐藏所有升级选项
        const freeUpgradeOptions = document.getElementById('free-upgrade-options');
        const monthlyUpgradeOption = document.getElementById('monthly-upgrade-option');
        const permanentMemberInfo = document.getElementById('permanent-member-info');
        
        if (freeUpgradeOptions) freeUpgradeOptions.classList.add('hidden');
        if (monthlyUpgradeOption) monthlyUpgradeOption.classList.add('hidden');
        if (permanentMemberInfo) permanentMemberInfo.classList.add('hidden');
        
        // 根据会员类型显示对应的升级选项
        switch (data.membership.type) {
            case 'free':
                if (freeUpgradeOptions) {
                    freeUpgradeOptions.classList.remove('hidden');
                }
                break;
            case 'monthly':
                if (monthlyUpgradeOption) {
                    monthlyUpgradeOption.classList.remove('hidden');
                }
                break;
            case 'permanent':
                if (permanentMemberInfo) {
                    permanentMemberInfo.classList.remove('hidden');
                }
                break;
        }
        
        console.log('[会员状态管理器] 更新升级按钮显示:', data.membership.type);
    }
    
    /**
     * 更新其他相关元素
     */
    updateOtherElements(data) {
        // 可以在这里添加其他需要更新的元素
        // 例如：页面标题、导航栏状态等
    }
    
    /**
     * 显示错误信息
     */
    showError(message) {
        console.error('[会员状态管理器] 错误:', message);
        
        // 可以在这里添加错误提示UI
        // 例如：显示toast通知
    }
    
    /**
     * 显示默认会员状态（未登录或获取失败时）
     */
    showDefaultMembership() {
        console.log('[会员状态管理器] 显示默认会员状态');
        
        // 隐藏会员相关元素
        const membershipStatusEl = document.getElementById('membership-status');
        const membershipBadgeEl = document.getElementById('membership-badge');
        const membershipExpiryEl = document.getElementById('membership-expiry');
        
        if (membershipStatusEl) membershipStatusEl.textContent = '免费用户';
        if (membershipBadgeEl) membershipBadgeEl.classList.add('hidden');
        if (membershipExpiryEl) membershipExpiryEl.classList.add('hidden');
        
        // 隐藏所有权益卡片，只显示免费用户权益
        const freeUserBenefits = document.getElementById('free-user-benefits');
        const monthlyMemberBenefits = document.getElementById('monthly-member-benefits');
        const permanentMemberBenefits = document.getElementById('permanent-member-benefits');
        
        if (freeUserBenefits) freeUserBenefits.classList.remove('hidden');
        if (monthlyMemberBenefits) monthlyMemberBenefits.classList.add('hidden');
        if (permanentMemberBenefits) permanentMemberBenefits.classList.add('hidden');
        
        // 显示免费用户的升级选项
        const freeUpgradeOptions = document.getElementById('free-upgrade-options');
        const monthlyUpgradeOption = document.getElementById('monthly-upgrade-option');
        
        if (freeUpgradeOptions) freeUpgradeOptions.classList.remove('hidden');
        if (monthlyUpgradeOption) monthlyUpgradeOption.classList.add('hidden');
    }
    
    /**
     * 手动刷新状态
     */
    async refresh() {
        console.log('[会员状态管理器] 手动刷新状态');
        this.clearCache();
        return await this.loadStatus(true);
    }
    
    /**
     * 获取当前状态
     */
    getCurrentStatus() {
        return this.cache;
    }
    
    /**
     * 销毁管理器
     */
    destroy() {
        this.clearCache();
        this.isInitialized = false;
        console.log('[会员状态管理器] 已销毁');
    }
}

// 创建全局实例
window.membershipStatusManager = new MembershipStatusManager();

// 页面加载完成后自动初始化
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(() => {
            window.membershipStatusManager.init();
        }, 500);
    });
} else {
    setTimeout(() => {
        window.membershipStatusManager.init();
    }, 500);
}

// 导出供其他脚本使用
if (typeof module !== 'undefined' && module.exports) {
    module.exports = MembershipStatusManager;
}

console.log('[会员状态管理器] 脚本加载完成');
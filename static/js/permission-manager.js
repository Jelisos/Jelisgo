/**
 * 权限管理器 - 统一权限控制模块
 * 专门处理用户权限判断和高级功能访问控制
 * 
 * @author AI Assistant
 * @date 2025-01-27
 * @version 1.0
 */

class PermissionManager {
    constructor() {
        this.permissionLevels = {
            GUEST: 'guest',
            FREE: 'free', 
            MONTHLY: 'monthly',
            PERMANENT: 'permanent',
            ADMIN: 'admin'
        };
        
        console.log('[权限管理器] 初始化');
    }
    
    /**
     * 获取用户权限等级
     * @returns {string} 权限等级
     */
    getUserPermissionLevel() {
        try {
            const userData = JSON.parse(localStorage.getItem('user') || '{}');
            
            // 未登录用户
            if (!userData || !userData.id) {
                return this.permissionLevels.GUEST;
            }
            
            // 管理员
            if (userData.is_admin === true || userData.is_admin === 1) {
                return this.permissionLevels.ADMIN;
            }
            
            // 永久会员
            if (userData.membership_type === 'permanent') {
                return this.permissionLevels.PERMANENT;
            }
            
            // 月度会员（需要检查是否过期）
            if (userData.membership_type === 'monthly') {
                // 如果有过期时间，检查是否过期
                if (userData.membership_expires_at) {
                    const expiryDate = new Date(userData.membership_expires_at);
                    const now = new Date();
                    if (now < expiryDate) {
                        return this.permissionLevels.MONTHLY;
                    } else {
                        // 已过期，降级为免费用户
                        return this.permissionLevels.FREE;
                    }
                } else {
                    // 没有过期时间，认为是有效的月度会员
                    return this.permissionLevels.MONTHLY;
                }
            }
            
            // 免费用户
            return this.permissionLevels.FREE;
            
        } catch (error) {
            console.error('[权限管理器] 获取用户权限等级失败:', error);
            return this.permissionLevels.GUEST;
        }
    }
    
    /**
     * 检查是否有高级功能访问权限（AI提示词、自定义链接等）
     * @returns {boolean} 是否有权限
     */
    hasAdvancedFeatureAccess() {
        const level = this.getUserPermissionLevel();
        return [
            this.permissionLevels.ADMIN,
            this.permissionLevels.PERMANENT,
            this.permissionLevels.MONTHLY
        ].includes(level);
    }
    
    /**
     * 检查是否为管理员
     * @returns {boolean} 是否为管理员
     */
    isAdmin() {
        return this.getUserPermissionLevel() === this.permissionLevels.ADMIN;
    }
    
    /**
     * 检查是否为会员（月度或永久）
     * @returns {boolean} 是否为会员
     */
    isMember() {
        const level = this.getUserPermissionLevel();
        return [
            this.permissionLevels.MONTHLY,
            this.permissionLevels.PERMANENT
        ].includes(level);
    }
    
    /**
     * 获取权限不足时的提示信息
     * @returns {string} 提示信息
     */
    getPermissionDeniedMessage() {
        return '权限不足';
    }
    
    /**
     * 获取用户权限等级的显示名称
     * @returns {string} 显示名称
     */
    getPermissionLevelDisplay() {
        const level = this.getUserPermissionLevel();
        const displayMap = {
            [this.permissionLevels.GUEST]: '未登录',
            [this.permissionLevels.FREE]: '免费用户',
            [this.permissionLevels.MONTHLY]: '月度会员',
            [this.permissionLevels.PERMANENT]: '永久会员',
            [this.permissionLevels.ADMIN]: '管理员'
        };
        return displayMap[level] || '未知';
    }
    
    /**
     * 监听用户登录状态变化
     */
    bindEvents() {
        // 监听用户登录事件
        document.addEventListener('user-login', () => {
            console.log('[权限管理器] 用户登录，权限等级:', this.getPermissionLevelDisplay());
        });
        
        // 监听用户退出事件
        document.addEventListener('user-logout', () => {
            console.log('[权限管理器] 用户退出，权限等级:', this.getPermissionLevelDisplay());
        });
        
        // 监听会员状态变化事件
        document.addEventListener('membership-status-changed', () => {
            console.log('[权限管理器] 会员状态变化，权限等级:', this.getPermissionLevelDisplay());
        });
    }
}

// 创建全局权限管理器实例
window.PermissionManager = new PermissionManager();

// 页面加载完成后绑定事件
document.addEventListener('DOMContentLoaded', function() {
    window.PermissionManager.bindEvents();
    console.log('[权限管理器] 初始化完成，当前权限等级:', window.PermissionManager.getPermissionLevelDisplay());
});
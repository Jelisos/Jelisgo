/**
 * 用户菜单功能
 * @author Claude
 * @date 2024-03-21
 */

// 用户菜单控制
const userMenu = document.getElementById('user-menu');
const userBtn = document.getElementById('user-btn');
const userDropdown = document.getElementById('user-dropdown');

// 切换用户下拉菜单
function toggleUserDropdown(e) {
    if (!userBtn || !userDropdown) return;
    
    e.stopPropagation(); // 阻止事件冒泡
    userDropdown.classList.toggle('hidden');
}

// 点击页面其他地方关闭下拉菜单
function closeUserDropdown(e) {
    if (!userMenu || !userDropdown) return;
    
    if (!userMenu.contains(e.target)) {
        userDropdown.classList.add('hidden');
    }
}

// 初始化用户菜单
function initUserMenu() {
    if (!userBtn || !userDropdown) return;
    
    // 绑定点击事件
    userBtn.addEventListener('click', toggleUserDropdown);
    document.addEventListener('click', closeUserDropdown);
}

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', initUserMenu);

/**
 * 用户菜单与登录状态管理 - 重构版本
 * 2025-01-27 重构：移除重复的登录状态检查逻辑，统一由auth.js管理
 * 仅保留用户菜单的基础交互功能
 */
document.addEventListener('DOMContentLoaded', () => {
    const logoutLink = document.getElementById('logout-link');

    /**
     * 退出登录事件绑定已移至auth.js统一管理，避免重复绑定
     * 2025-01-27 修复：移除重复的事件绑定，防止需要点击两次确定的问题
     */
    // 退出登录事件绑定已在auth.js中处理，此处不再重复绑定
});
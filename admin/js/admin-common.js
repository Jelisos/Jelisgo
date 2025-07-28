/**
 * 管理员后台通用验证模块
 * 文件: admin/js/admin-common.js
 * 功能: 统一管理员身份验证、权限检查、UI更新等功能
 * 更新: 2025-01-03 基于admin_verification_logic.md重构
 */

/**
 * 检查管理员登录状态
 * 验证用户是否已登录且具有管理员权限
 */
function checkAdminLogin() {
    console.log('[AdminAuth] 开始检查管理员登录状态');
    
    // 检查本地存储的用户信息
    const userInfo = JSON.parse(localStorage.getItem('user') || '{}');
    const userId = userInfo.id;
    
    if (!userId) {
        console.warn('[AdminAuth] 未找到用户信息，需要先登录');
        redirectToLogin('请先登录后再访问管理后台');
        return;
    }
    
    console.log('[AdminAuth] 找到用户信息，开始验证管理员权限:', userInfo);
    
    // 向后端验证管理员权限
    fetch('/api/admin_auth.php?action=verify', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${userId}`
        }
    })
    .then(response => response.json())
    .then(result => {
        console.log('[AdminAuth] 管理员权限验证结果:', result);
        
        if (result.code === 200) {
            console.log('[AdminAuth] 管理员权限验证成功');
            
            // 更新UI界面
            updateAdminUI(userInfo);
            
            // 记录管理员访问日志
            logAdminAccess('admin_access', 'Admin accessed backend');
        } else {
            console.warn('[AdminAuth] 管理员权限验证失败:', result.msg);
            redirectToLogin('需要管理员权限才能访问');
        }
    })
    .catch(error => {
        console.error('[AdminAuth] 管理员权限验证请求失败:', error);
        redirectToLogin('验证管理员权限时发生错误');
    });
}

/**
 * 更新管理员UI界面
 * @param {Object} adminData 管理员数据
 */
function updateAdminUI(adminData) {
    console.log('[AdminAuth] 更新管理员UI界面:', adminData);
    
    // 更新侧边栏管理员用户名
    const adminUsername = document.getElementById('admin-username');
    if (adminUsername) {
        adminUsername.textContent = adminData.username || '管理员';
    }
    
    // 更新头部管理员用户名
    const headerAdminUsername = document.getElementById('header-admin-username');
    if (headerAdminUsername) {
        headerAdminUsername.textContent = adminData.username || '管理员';
    }
    
    // 更新侧边栏管理员头像
    const adminAvatar = document.getElementById('admin-avatar');
    if (adminAvatar) {
        if (adminData.avatar && adminData.avatar !== '') {
            adminAvatar.src = adminData.avatar;
        } else {
            // 使用用户ID生成唯一的默认头像
            adminAvatar.src = `https://picsum.photos/40/40?random=${adminData.id || adminData.user_id}`;
        }
    }
    
    // 更新头部管理员头像
    const headerAdminAvatar = document.getElementById('header-admin-avatar');
    if (headerAdminAvatar) {
        if (adminData.avatar && adminData.avatar !== '') {
            headerAdminAvatar.src = adminData.avatar;
        } else {
            // 使用用户ID生成唯一的默认头像
            headerAdminAvatar.src = `https://picsum.photos/40/40?random=${adminData.id || adminData.user_id}`;
        }
    }
    
    // 更新管理员邮箱
    const adminEmail = document.getElementById('admin-email');
    if (adminEmail && adminData.email) {
        adminEmail.textContent = adminData.email;
    }
    
    console.log('[AdminAuth] UI界面更新完成');
}

/**
 * 统一的重定向到登录页面函数
 * @param {string} message 提示信息
 */
function redirectToLogin(message = '请重新登录') {
    console.log('[AdminAuth] 重定向到登录页面:', message);
    
    // 清除所有本地存储的用户信息
    localStorage.removeItem('user');
    sessionStorage.clear();
    
    // 显示提示信息
    if (message) {
        alert(message);
    }
    
    // 重定向到登录页面
    window.location.href = '/index.php';
}

/**
 * 处理管理员退出登录
 */
function handleAdminLogout() {
    console.log('[AdminAuth] 管理员退出登录');
    
    // 记录退出日志
    logAdminAccess('admin_logout', 'Admin logged out');
    
    // 清除本地存储
    localStorage.removeItem('user');
    sessionStorage.clear();
    
    // 重定向到登录页面
    window.location.href = '/index.php';
}

/**
 * 记录管理员操作日志
 * @param {string} action 操作类型
 * @param {string} details 操作详情
 */
function logAdminAccess(action, details = '') {
    const userInfo = JSON.parse(localStorage.getItem('user') || '{}');
    const userId = userInfo.id;
    
    if (!userId) {
        console.warn('[AdminAuth] 无法记录日志: 用户未登录');
        return;
    }
    
    fetch('/api/admin_auth.php?action=log', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${userId}`
        },
        body: JSON.stringify({
            action: action,
            details: details,
            page: window.location.pathname
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.code === 200) {
            console.log('[AdminAuth] 管理员日志记录成功:', action);
        } else {
            console.warn('[AdminAuth] 管理员日志记录失败:', result.msg);
        }
    })
    .catch(error => {
        console.error('[AdminAuth] 记录管理员日志失败:', error);
    });
}

/**
 * 加载待审核数量
 */
function loadPendingCount() {
    const userInfo = JSON.parse(localStorage.getItem('user') || '{}');
    const userId = userInfo.id;
    
    if (!userId) {
        console.warn('[AdminAuth] 无法加载待审核数量: 用户未登录');
        return;
    }
    
    fetch('/api/admin_user_wallpapers.php?action=pending_count', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${userId}`
        }
    })
        .then(response => response.json())
        .then(result => {
            if (result.code === 200) {
                const count = result.data.count || 0;
                const badge = document.getElementById('pending-count-badge');
                if (badge) {
                    if (count > 0) {
                        badge.textContent = count;
                        badge.style.display = 'inline-block';
                    } else {
                        badge.style.display = 'none';
                    }
                }
            }
        })
        .catch(error => {
            console.error('[AdminAuth] 加载待审核数量失败:', error);
        });
}

/**
 * 初始化管理员通用功能
 */
function initAdminCommon() {
    console.log('[AdminAuth] 初始化管理员通用功能');
    
    // 执行管理员身份验证
    checkAdminLogin();
    
    // 加载待审核数量
    loadPendingCount();
    
    // 绑定退出登录事件
    const logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('确定要退出登录吗？')) {
                handleAdminLogout();
            }
        });
    }
    
    // 定期更新待审核数量（每30秒）
    setInterval(loadPendingCount, 30000);
    
    console.log('[AdminAuth] 管理员通用功能初始化完成');
}

// 页面加载完成后自动初始化
document.addEventListener('DOMContentLoaded', function() {
    console.log('[AdminAuth] DOM加载完成，开始初始化');
    initAdminCommon();
});
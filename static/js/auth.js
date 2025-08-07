/**
 * 用户认证相关功能
 * @author Claude
 * @date 2024-03-21
 */

// API基础URL
const API_BASE_URL = '/api';

// 全局错误提示函数
function showError(msg) {
    // 如果是在登录/注册模态框中，尝试显示在表单错误提示区域
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    let errorMsg = null;
    
    // 优先查找当前活跃的表单
    if (registerForm && !document.getElementById('register-modal').classList.contains('hidden')) {
        errorMsg = registerForm.querySelector('.form-error-msg');
    } else if (loginForm && !document.getElementById('login-modal').classList.contains('hidden')) {
        errorMsg = loginForm.querySelector('.form-error-msg');
    }
    
    if (errorMsg) {
        errorMsg.textContent = msg;
        errorMsg.style.color = '#ef4444'; // 红色表示错误
    } else {
        // 否则使用alert显示
        alert(msg);
    }
}

// 全局成功提示函数
function showSuccess(msg) {
    // 如果是在登录/注册模态框中，尝试显示在表单错误提示区域（复用但改变样式）
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    let errorMsg = null;
    
    // 优先查找当前活跃的表单
    if (registerForm && !document.getElementById('register-modal').classList.contains('hidden')) {
        errorMsg = registerForm.querySelector('.form-error-msg');
    } else if (loginForm && !document.getElementById('login-modal').classList.contains('hidden')) {
        errorMsg = loginForm.querySelector('.form-error-msg');
    }
    
    if (errorMsg) {
        errorMsg.textContent = msg;
        errorMsg.style.color = '#22c55e'; // 绿色表示成功
    } else {
        // 否则使用alert显示
        alert(msg);
    }
}

// 模态框控制
const loginModal = document.getElementById('login-modal');
const registerModal = document.getElementById('register-modal');
const loginModalContent = document.getElementById('login-modal-content');
const registerModalContent = document.getElementById('register-modal-content');

// 打开登录模态框
function openLoginModal() {
    loginModal.classList.remove('hidden');
    // 清除登录窗口的错误提示
    const loginErrorMsg = loginModal.querySelector('.form-error-msg');
    if (loginErrorMsg) {
        loginErrorMsg.textContent = '';
    }
    setTimeout(() => {
        loginModalContent.classList.remove('scale-95', 'opacity-0');
        loginModalContent.classList.add('scale-100', 'opacity-100');
    }, 10);
}

// 关闭登录模态框
function closeLoginModal() {
    loginModalContent.classList.remove('scale-100', 'opacity-100');
    loginModalContent.classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        loginModal.classList.add('hidden');
    }, 300);
}

// 打开注册模态框
function openRegisterModal() {
    registerModal.classList.remove('hidden');
    // 清除注册窗口的错误提示
    const registerErrorMsg = registerModal.querySelector('.form-error-msg');
    if (registerErrorMsg) {
        registerErrorMsg.textContent = '';
    }
    setTimeout(() => {
        registerModalContent.classList.remove('scale-95', 'opacity-0');
        registerModalContent.classList.add('scale-100', 'opacity-100');
    }, 10);
}

// 关闭注册模态框
function closeRegisterModal() {
    registerModalContent.classList.remove('scale-100', 'opacity-100');
    registerModalContent.classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        registerModal.classList.add('hidden');
    }, 300);
}

// 打开个人设置模态框
function openSettingsModal() {
    const settingsModal = document.getElementById('settings-modal');
    const settingsModalContent = document.getElementById('settings-modal-content');
    if (settingsModal && settingsModalContent) {
        settingsModal.classList.remove('hidden');
        setTimeout(() => {
            settingsModalContent.classList.remove('scale-95', 'opacity-0');
            settingsModalContent.classList.add('scale-100', 'opacity-100');
        }, 10);
        populateSettingsModal(); // 打开时填充用户信息
    }
}

// 关闭个人设置模态框
function closeSettingsModal() {
    const settingsModal = document.getElementById('settings-modal');
    const settingsModalContent = document.getElementById('settings-modal-content');
    if (settingsModal && settingsModalContent) {
        settingsModalContent.classList.remove('scale-100', 'opacity-100');
        settingsModalContent.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            settingsModal.classList.add('hidden');
        }, 300);
    }
}

// 打开修改密码模态框
function openChangePasswordModal() {
    const changePasswordModal = document.getElementById('change-password-modal');
    const changePasswordModalContent = document.getElementById('change-password-modal-content');
    if (changePasswordModal && changePasswordModalContent) {
        changePasswordModal.classList.remove('hidden');
        setTimeout(() => {
            changePasswordModalContent.classList.remove('scale-95', 'opacity-0');
            changePasswordModalContent.classList.add('scale-100', 'opacity-100');
        }, 10);
    }
}

// 关闭修改密码模态框
function closeChangePasswordModal() {
    const changePasswordModal = document.getElementById('change-password-modal');
    const changePasswordModalContent = document.getElementById('change-password-modal-content');
    if (changePasswordModal && changePasswordModalContent) {
        changePasswordModalContent.classList.remove('scale-100', 'opacity-100');
        changePasswordModalContent.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            changePasswordModal.classList.add('hidden');
        }, 300);
    }
}

// 表单验证
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validatePassword(password) {
    // 至少4位，可以是字母、数字或字母数字组合
    return password.length >= 4;
}

// 填充个人设置模态框的用户信息
function populateSettingsModal() {
    const userData = JSON.parse(localStorage.getItem('user') || '{}');
    const usernameElement = document.getElementById('settings-username');
    const emailElement = document.getElementById('settings-email');

    if (usernameElement) {
        usernameElement.value = userData.username || '';
        // 使用户名可编辑
        usernameElement.readOnly = false;
        usernameElement.classList.remove('readonly'); // 如果有readonly的样式类可以移除
    }
    if (emailElement) {
        emailElement.value = userData.email || '';
        // 邮箱通常不可编辑，保持只读
        emailElement.readOnly = true;
    }
}

// 登录处理
async function handleLogin() {
    const username = document.getElementById('login-username').value;
    const password = document.getElementById('login-password').value;
    const submitBtn = document.getElementById('login-submit');
    const loginForm = document.getElementById('login-form');
    const errorMsg = loginForm ? loginForm.querySelector('.form-error-msg') : null;
    
    // 清空错误提示
    if (errorMsg) errorMsg.textContent = '';
    
    // 表单验证
    if (!username) {
        showError('请输入用户名或邮箱');
        return;
    }
    if (!password) {
        showError('请输入密码');
        return;
    }

    // 按钮防抖
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = '登录中...';
    }

    try {
        const response = await fetch(`${API_BASE_URL}/auth_unified.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            },
            credentials: 'same-origin',  // 添加credentials配置
            body: JSON.stringify({
                action: 'login',
                username: username,
                password
            })
        });

        const result = await response.json();

        if (result.code === 200) {
            // 存储用户信息
            localStorage.setItem('user', JSON.stringify(result.data));
            
            showSuccess('登录成功');
            
            // 关闭模态框并更新UI
            setTimeout(() => {
                closeLoginModal();
                // 更新UI显示
                updateUIAfterLogin(result.data);
            }, 800);
        } else {
            showError(result.message || '登录失败');
        }
    } catch (error) {
        console.error('登录错误:', error);
        showError('登录失败，请重试');
    } finally {
        // 恢复按钮状态
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = '登录';
        }
    }
}

// 注册处理
async function handleRegister() {
    const email = document.getElementById('register-email').value;
    const password = document.getElementById('register-password').value;
    const confirmPassword = document.getElementById('register-confirm-password').value;
    const humanVerification = document.getElementById('human-verification').checked;
    const submitBtn = document.getElementById('register-submit');
    const registerForm = document.getElementById('register-form');
    const errorMsg = registerForm ? registerForm.querySelector('.form-error-msg') : null;
    
    // 清空错误提示
    if (errorMsg) errorMsg.textContent = '';

    // 表单验证
    if (!email || !validateEmail(email)) {
        showError('请输入有效的邮箱地址');
        return;
    }
    if (!validatePassword(password)) {
        showError('密码至少4位');
        return;
    }
    if (password !== confirmPassword) {
        showError('两次输入的密码不一致');
        return;
    }
    if (!humanVerification) {
        showError('请确认您是真人');
        return;
    }

    // 按钮防抖
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = '注册中...';
    }

    try {
        const response = await fetch(`${API_BASE_URL}/auth_unified.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                action: 'register',
                email,
                password,
                human_verification: humanVerification
            })
        });

        const result = await response.json();

        if (result.code === 200) {
            // 存储用户信息
            localStorage.setItem('user', JSON.stringify(result.data));
            
            showSuccess('注册成功！');
            
            // 关闭模态框并更新UI
            setTimeout(() => {
                closeRegisterModal();
                // 更新UI显示
                updateUIAfterLogin(result.data);
            }, 800);
        } else {
            showError(result.message || '注册失败');
        }
    } catch (error) {
        console.error('注册错误:', error);
        showError('注册失败，请重试');
    } finally {
        // 恢复按钮状态
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = '注册';
        }
    }
}

// 更新UI显示
function updateUIAfterLogin(userData) {
    // 2025-01-27 修复：统一UI更新逻辑，整合user-menu.js的功能
    // 设置全局用户数据，供其他模块使用
    window.currentUser = userData;
    
    // 触发登录状态变化事件，通知其他模块
    notifyLoginStatusChange(true, userData);
    
    const usernameElement = document.getElementById('username');
    const userAvatar = document.getElementById('user-avatar');
    const logoutLink = document.getElementById('logout-link');
    const loginLink = document.getElementById('login-link');
    const registerLink = document.getElementById('register-link');
    const userDropdown = document.getElementById('user-dropdown');
    const adminPanelLink = document.getElementById('admin-panel-link');
    
    if (usernameElement) {
        usernameElement.textContent = userData.username;
        usernameElement.style.display = 'inline-block';
    }
    if (userAvatar) {
        userAvatar.src = userData.avatar ? userData.avatar : '/static/icons/default-avatar.svg';
        userAvatar.classList.remove('hidden');
        userAvatar.style.display = 'block';
        // 确保头像在移动端不被挤压
        userAvatar.style.minWidth = '2rem';
    }
    if (logoutLink) {
        logoutLink.classList.remove('hidden');
        logoutLink.style.display = 'block';
    }
    if (loginLink) {
        loginLink.classList.add('hidden');
        loginLink.style.display = 'none';
    }
    if (registerLink) {
        registerLink.classList.add('hidden');
        registerLink.style.display = 'none';
    }
    
    // 检查管理员权限并显示管理后台链接
    if (userData.is_admin === 1 && adminPanelLink) {
        adminPanelLink.classList.remove('hidden');
        adminPanelLink.addEventListener('click', function(e) {
            e.preventDefault();
            // 修正管理后台路径，确保指向admin目录下的admin.php
            // 使用完整路径避免路径解析问题
            window.location.href = window.location.origin + '/admin/admin.php';
        });
    }
    
    // 动态插入个人中心入口
    let centerLink = document.getElementById('center-link');
    if (!centerLink && userDropdown) {
        centerLink = document.createElement('a');
        centerLink.id = 'center-link';
        centerLink.href = 'dashboard.php';
        centerLink.className = 'block px-4 py-2 hover:bg-gray-100 cursor-pointer';
        centerLink.textContent = '个人中心';
        userDropdown.insertBefore(centerLink, logoutLink);
    }
    
    // 登录成功后同步游客点赞记录 - 已废弃
    // syncGuestLikes(); // 2025-01-27 修改：统一点赞逻辑，不再需要同步游客记录
    
    // 管理员跳转提示已移除 - 2025-06-30
}

// 登出处理
async function handleLogout() {
    console.log('开始登出处理');
    
    try {
        // 向后端发送登出请求
        const response = await fetch(`${API_BASE_URL}/auth_unified.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Cache-Control': 'no-cache, no-store, must-revalidate'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                action: 'logout'
            })
        });
        
        const result = await response.json();
        console.log('登出响应:', result);
        
    } catch (error) {
        console.error('登出请求失败:', error);
        // 即使后端请求失败，也要清除本地状态
    }
    
    // 清除本地存储中的用户信息
    localStorage.removeItem('user');
    console.log('已清除本地存储的用户数据');
    
    // 清除全局用户数据
    window.currentUser = null;
    
    // 更新UI显示为未登录状态
    showLoggedOutState();
    console.log('已更新UI为登出状态');
    
    // 通知其他模块登录状态变化
    notifyLoginStatusChange(false, null);
    
    // 显示成功消息
    alert('已成功退出登录');
    
    return false;
}

// 处理更新个人信息
async function handleUpdateProfile() {
    const newUsername = document.getElementById('settings-username').value.trim();

    if (!newUsername) {
        alert('用户名不能为空');
        return;
    }

    const userData = JSON.parse(localStorage.getItem('user') || '{}');
    if (!userData.username) {
        alert('请先登录');
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE_URL}/update_profile.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Authorization': `Bearer ${userData.id}`
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                username: newUsername
            })
        });

        const result = await response.json();

        if (result.code === 200) {
            // 更新本地存储的用户信息
            userData.username = newUsername;
            localStorage.setItem('user', JSON.stringify(userData));

            // 更新导航栏显示
            updateUIAfterLogin(userData);

            alert('个人信息更新成功！');
            // 关闭模态框
            closeSettingsModal();
        } else {
            alert(result.message || '更新失败');
        }
    } catch (error) {
        console.error('更新个人信息失败:', error);
        alert('更新失败，请重试');
    }
}

/**
 * 同步游客点赞记录到用户账户 - 已废弃
 * 2025-01-27 修改：统一点赞逻辑，不再需要同步游客记录
 */
// async function syncGuestLikes() {
//     // 功能已废弃，统一使用设备指纹和IP地址进行点赞识别
// }

/**
 * 通知登录状态变化
 * @param {boolean} isLoggedIn - 是否已登录
 * @param {Object|null} userData - 用户数据，未登录时为null
 */
function notifyLoginStatusChange(isLoggedIn, userData) {
    // 创建自定义事件
    const event = new CustomEvent('loginStatusChange', {
        detail: {
            isLoggedIn,
            userData
        }
    });
    
    // 分发事件
    document.dispatchEvent(event);
    console.log(`[Auth] 已通知登录状态变化: ${isLoggedIn ? '已登录' : '未登录'}`);
}

// 事件监听
document.addEventListener('DOMContentLoaded', () => {
    // 检查登录状态
    checkLoginStatus();
    
    // 监听来自dashboard的头像更新消息
    window.addEventListener('message', function(event) {
        if (event.data && event.data.type === 'updateAvatar') {
            const userAvatar = document.getElementById('user-avatar');
            if (userAvatar) {
                userAvatar.src = event.data.avatarSrc;
            }
            // 更新所有用户头像元素
            document.querySelectorAll('.user-avatar').forEach(avatar => {
                avatar.src = event.data.avatarSrc;
            });
        }
    });

    // 登录相关事件
    document.getElementById('login-submit')?.addEventListener('click', handleLogin);
    document.getElementById('close-login-modal')?.addEventListener('click', closeLoginModal);
    document.getElementById('switch-to-register')?.addEventListener('click', () => {
        closeLoginModal();
        openRegisterModal();
    });

    // 注册相关事件 - 注册处理已移至modals.js中统一管理
    // document.getElementById('register-submit')?.addEventListener('click', handleRegister);
    document.getElementById('close-register-modal')?.addEventListener('click', closeRegisterModal);
    document.getElementById('switch-to-login')?.addEventListener('click', () => {
        closeRegisterModal();
        openLoginModal();
    });

    // 登出事件
    const logoutLink = document.querySelector('#logout-link');
    logoutLink?.addEventListener('click', (e) => {
        e.preventDefault();
        handleLogout();
    });

    // 个人设置相关事件
    const settingsLink = document.getElementById('settings-link'); // 获取个人设置链接
    settingsLink?.addEventListener('click', (e) => {
        e.preventDefault();
        openSettingsModal();
    });
    
    document.getElementById('close-settings-modal')?.addEventListener('click', closeSettingsModal);
    
    // 修改密码相关事件
    document.getElementById('open-change-password-modal')?.addEventListener('click', () => {
        closeSettingsModal();
        openChangePasswordModal();
    });
    
    document.getElementById('close-change-password-modal')?.addEventListener('click', () => {
        closeChangePasswordModal();
        // 您可能希望在这里重新打开个人设置模态框，或者直接关闭所有模态框
        // 这里选择直接关闭所有，如果您希望返回设置页，可以调用 openSettingsModal();
        openSettingsModal(); // 返回个人设置页面
    });

    document.getElementById('save-new-password')?.addEventListener('click', handlePasswordChange);

    // 个人设置保存按钮事件
    const settingsModalContent = document.getElementById('settings-modal-content');
    if (settingsModalContent) {
        // 在模态框内容中查找并添加保存按钮
        let saveButton = settingsModalContent.querySelector('#settings-modal .items-center button');
        // 检查是否已经有修改密码按钮，在其上方添加保存按钮
        const changePasswordButton = document.getElementById('open-change-password-modal');
        if (changePasswordButton) {
             saveButton = document.createElement('button');
             saveButton.id = 'save-profile-changes';
             saveButton.classList.add('px-4', 'py-2', 'bg-green-500', 'text-white', 'text-base', 'font-medium', 'rounded-md', 'w-full', 'shadow-sm', 'hover:bg-green-600', 'focus:outline-none', 'focus:ring-2', 'focus:ring-green-300', 'mb-2'); // 添加mb-2以与修改密码按钮保持间距
             saveButton.textContent = '保存更改';
             changePasswordButton.parentNode.insertBefore(saveButton, changePasswordButton);
        } else { // 如果没有修改密码按钮，直接添加到items-center div的末尾
            const itemsCenterDiv = settingsModalContent.querySelector('#settings-modal .items-center');
            if (itemsCenterDiv) {
                 saveButton = document.createElement('button');
                 saveButton.id = 'save-profile-changes';
                 saveButton.classList.add('px-4', 'py-2', 'bg-green-500', 'text-white', 'text-base', 'font-medium', 'rounded-md', 'w-full', 'shadow-sm', 'hover:bg-green-600', 'focus:outline-none', 'focus:ring-2', 'focus:ring-green-300', 'mb-2');
                 saveButton.textContent = '保存更改';
                 itemsCenterDiv.insertBefore(saveButton, itemsCenterDiv.firstChild); // 添加到最前面
            }
        }

        if (saveButton) {
            saveButton.addEventListener('click', handleUpdateProfile);
        }
    }
});

// 处理密码修改
async function handlePasswordChange() {
    const currentPassword = document.getElementById('current-password').value;
    const newPassword = document.getElementById('new-password').value;
    const confirmNewPassword = document.getElementById('confirm-new-password').value;

    // 基本验证
    if (!currentPassword || !newPassword || !confirmNewPassword) {
        alert('请填写所有密码字段');
        return;
    }
    if (newPassword !== confirmNewPassword) {
        alert('两次输入的新密码不一致');
        return;
    }
    if (!validatePassword(newPassword)) {
        alert('新密码不符合要求（至少4位）');
        return;
    }

    const userData = JSON.parse(localStorage.getItem('user') || '{}');
    if (!userData.id) {
        alert('请先登录');
        return;
    }

    try {
        const response = await fetch('/api/auth_unified.php?action=changePassword', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Authorization': `Bearer ${userData.id}`
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                    currentPassword: currentPassword,
                    newPassword: newPassword
                })
        });

        const result = await response.json();

        if (result.code === 200) {
            alert('密码修改成功，请重新登录');
            localStorage.removeItem('user'); // 清除本地存储的用户信息
            closeChangePasswordModal(); // 关闭修改密码模态框
            showLoggedOutState(); // 更新UI为未登录状态
            window.location.href = '/'; // 重定向到首页
        } else {
            alert(result.message || '密码修改失败');
        }
    } catch (error) {
        console.error('修改密码失败:', error);
        alert('修改密码失败，请重试');
    }
}

// 检查登录状态
async function checkLoginStatus() {
    const userData = JSON.parse(localStorage.getItem('user') || '{}');
    if (userData.username) {
        // 2025-01-27 修复：验证服务器端登录状态，确保本地状态与服务器同步
        try {
            const response = await fetch(`${API_BASE_URL}/auth_unified.php?action=getUserInfo`, {
                method: 'GET',
                headers: {
                    'Cache-Control': 'no-cache, no-store, must-revalidate'
                },
                credentials: 'same-origin'
            });
            
            const result = await response.json();
            
            if (result.code === 200 && result.data) {
                // 服务器端确认用户已登录，更新本地数据并显示登录状态
                localStorage.setItem('user', JSON.stringify(result.data));
                updateUIAfterLogin(result.data);
                console.log('[Auth] 登录状态验证通过，已同步服务器数据');
            } else {
                // 服务器端确认用户未登录或会话已过期，清除本地状态
                localStorage.removeItem('user');
                showLoggedOutState();
                console.log('[Auth] 服务器端会话已过期，已清除本地状态');
            }
        } catch (error) {
            console.error('[Auth] 验证登录状态失败:', error);
            // 网络错误时保持当前状态，但在控制台记录错误
            updateUIAfterLogin(userData);
        }
    } else {
        showLoggedOutState();
    }
}

// 显示未登录状态
function showLoggedOutState() {
    // 清除全局用户数据
    window.currentUser = null;
    
    // 触发登录状态变化事件
    notifyLoginStatusChange(false, null);
    
    const usernameElement = document.getElementById('username');
    const userAvatar = document.getElementById('user-avatar');
    const logoutLink = document.getElementById('logout-link');
    const loginLink = document.getElementById('login-link');
    const registerLink = document.getElementById('register-link');
    const adminPanelLink = document.getElementById('admin-panel-link');
    const centerLink = document.getElementById('center-link');
    
    if (usernameElement) {
        usernameElement.textContent = '';
        usernameElement.style.display = 'none';
    }
    if (userAvatar) {
        userAvatar.src = '/static/icons/default-avatar.svg';
        userAvatar.classList.add('hidden');
        userAvatar.style.display = 'none';
    }
    if (logoutLink) {
        logoutLink.classList.add('hidden');
        logoutLink.style.display = 'none';
    }
    if (loginLink) {
        loginLink.classList.remove('hidden');
        loginLink.style.display = 'block';
    }
    if (registerLink) {
        registerLink.classList.remove('hidden');
        registerLink.style.display = 'block';
    }
    if (adminPanelLink) {
        adminPanelLink.classList.add('hidden');
    }
    if (centerLink) {
        centerLink.remove();
    }
}
/**
 * 文件: static/js/dashboard.js
 * 描述: 仪表盘页面的核心逻辑，包括侧边栏导航、用户信息加载、搜索功能、登出功能，以及头像上传、个人资料修改、密码修改等。
 * 依赖: utils.js, modals.js, user-menu.js
 * 作者: AI助手
 * 日期: 2024-07-29
 */

const Dashboard = {
    // API基础URL配置
    API_BASE_URL: window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1' 
        ? 'http://localhost/api' 
        : '/api',
        
    init: function() {
        console.log('[Dashboard] 初始化...');
        this.bindEvents();
        this.loadUserInfo();
        this.initSidebarTabSwitching();
        
        // 监听游客点赞记录同步事件
        document.addEventListener('guest-likes-synced', (event) => {
            console.log('[Dashboard] 检测到游客点赞记录同步，刷新我的喜欢页面');
            // 如果当前显示的是"我的喜欢"页面，则重新加载
            const likedSection = document.getElementById('liked-section');
            if (likedSection && !likedSection.classList.contains('hidden')) {
                this.loadLikedWallpapers();
            }
        });
        
        // 监听会员状态更新事件
        document.addEventListener('membership-updated', (event) => {
            console.log('[Dashboard] 会员状态已更新，刷新相关显示');
            // 重新加载用户信息以更新会员状态显示
            this.loadUserInfo();
        });
    },

    bindEvents: function() {
        // 搜索框回车跳首页并带参数
        const dashboardSearchInput = document.getElementById('dashboard-search-input');
        if (dashboardSearchInput) {
            dashboardSearchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    const kw = dashboardSearchInput.value.trim();
                    if (kw) {
                        window.location.href = `index.php?kw=${encodeURIComponent(kw)}`;
                    } else {
                        window.location.href = 'index.php';
                    }
                }
            });
        }

        // 2024-12-19 新增：绑定同步游客点赞按钮
        const syncGuestLikesBtn = document.getElementById('sync-guest-likes-btn');
        if (syncGuestLikesBtn) {
            syncGuestLikesBtn.addEventListener('click', function() {
                Dashboard.syncGuestLikes();
            });
        }

        // 绑定退出登录按钮
        const dashboardLogoutBtn = document.getElementById('dashboard-logout-btn');
        if (dashboardLogoutBtn) {
            dashboardLogoutBtn.addEventListener('click', function(e) {
                e.preventDefault();
                // 使用auth.js中的handleLogout函数，避免双重确认问题
                if (typeof handleLogout === 'function') {
                    handleLogout();
                    // 跳转到首页
                    window.location.href = 'index.php';
                } else {
                    // 如果handleLogout函数不可用，则使用原来的方式
                    console.log('handleLogout函数不可用，使用原始退出登录方式');
                    // 清除本地存储的任何用户相关数据
                    localStorage.removeItem('user');
                    localStorage.removeItem('user_info');
                    sessionStorage.removeItem('user_info');
                    // 跳转到首页
                    window.location.href = 'index.php';
                }
            });
        }

        // 移动端侧边栏控制逻辑已移至dashboard.php中，避免重复绑定

        // TODO: 其他仪表盘相关事件绑定，例如图片管理、个人资料修改、密码修改、头像上传等
        // 这些需要从dashboard.php中复制过来
        this.bindProfileSettingsEvents();
        this.bindAvatarUploadEvents();
    },

    loadUserInfo: async function() {
        try {
            // 先从本地存储获取用户信息
            const storedUser = localStorage.getItem('user');
            console.log('从本地存储获取用户信息:', storedUser);
            
            // 调用后端API验证用户状态
            let userData = storedUser ? JSON.parse(storedUser) : null;
            const response = await fetch('/api/auth_unified.php?action=getUserInfo', {
                headers: userData ? {
                    'Authorization': `Bearer ${userData.id}`
                } : {}
            });
            const result = await response.json();
            
            if (result.code !== 0 && result.code !== 200) {
                throw new Error(result.msg || '用户验证失败');
            }
            
            // 更新本地存储的用户信息
            localStorage.setItem('user', JSON.stringify(result.data));
            
            if (!storedUser) {
                // 本地存储中没有用户信息，显示未登录状态
                console.log('本地存储中未找到用户信息，显示未登录状态');
                Utils.showToastMessage('未登录或会话已过期，请登录后查看个人中心', 'warning');
                
                // 显示登录按钮
                const loginBtn = document.createElement('button');
                loginBtn.className = 'mt-4 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors';
                loginBtn.textContent = '立即登录';
                loginBtn.onclick = function() {
                    if (typeof openLoginModal === 'function') {
                        openLoginModal();
                    } else {
                        // 如果没有模态框函数，则跳转到首页
                        window.location.href = '/index.php';
                    }
                };
                
                // 清空主内容区并显示登录提示
                const mainContent = document.querySelector('main');
                if (mainContent) {
                    mainContent.innerHTML = `
                        <div class="flex flex-col items-center justify-center h-full py-12">
                            <img src="/static/icons/login-required.svg" alt="登录提示" class="w-32 h-32 mb-4">
                            <h2 class="text-xl font-bold mb-2">需要登录</h2>
                            <p class="text-gray-600 mb-4 text-center">请登录后查看您的个人中心内容</p>
                            <div id="login-button-container"></div>
                        </div>
                    `;
                    document.getElementById('login-button-container').appendChild(loginBtn);
                }
                return;
            }
            
            // 更新用户数据
            userData = result.data;
            console.log('更新后的用户数据:', userData);
            
            // 更新所有头像元素
            const avatarSrc = userData.avatar && userData.avatar.trim() !== '' 
                ? userData.avatar + '?t=' + Date.now()
                : '/static/icons/default-avatar.svg';
            
            // 更新页面中所有的用户头像
            document.querySelectorAll('.user-avatar').forEach(el => {
                el.src = avatarSrc;
            });
            
            // 更新个人设置中的头像预览
            const avatarPreview = document.getElementById('avatar-preview');
            if (avatarPreview) {
                avatarPreview.src = avatarSrc;
            }
            
            // 更新用户名
            document.querySelectorAll('.user-username').forEach(el => {
                el.textContent = userData.username;
            });
            
            // 更新用户名元素（兼容不同的类名）
            document.querySelectorAll('.user-name').forEach(element => {
                element.textContent = userData.username || '用户';
            });
            
            // 同步更新首页头像（如果存在）
            if (window.parent && window.parent !== window) {
                // 如果在iframe中，通知父页面更新头像
                window.parent.postMessage({
                    type: 'updateAvatar',
                    avatarSrc: avatarSrc,
                    username: userData.username
                }, '*');
            }
            
            // 会员状态显示将由 membership-manager.js 处理
            // 这里只处理管理员状态
            if (userData.is_admin) {
                document.querySelectorAll('.user-membership').forEach(el => {
                    el.textContent = '管理员';
                    el.className = 'user-membership text-red-600';
                });
            } else {
                // 非管理员用户的会员状态由会员管理器处理
                // 触发会员状态加载事件
                document.dispatchEvent(new CustomEvent('user-info-loaded', {
                    detail: userData
                }));
            }
            
            // 同步侧边栏用户信息
            const sidebarAvatar = document.getElementById('sidebar-user-avatar');
            const sidebarUsername = document.getElementById('sidebar-username');
            if (sidebarAvatar) {
                sidebarAvatar.src = avatarSrc;
            }
            if (sidebarUsername) {
                console.log('更新侧边栏用户名:', userData.username);
                sidebarUsername.textContent = userData.username;
            }
                
                // 2024-07-29 新增：如果用户是管理员，显示流放管理菜单项
            if (userData.is_admin) {
                const adminExileMenuItem = document.getElementById('admin-exile-menu-item');
                if (adminExileMenuItem) {
                    adminExileMenuItem.classList.remove('hidden');
                }
            }

            // 自动填充资料
            const profileUsernameInput = document.getElementById('profile-username');
            const profileEmailInput = document.getElementById('profile-email');
            if (profileUsernameInput) profileUsernameInput.value = userData.username;
            if (profileEmailInput) profileEmailInput.value = userData.email;
            
            // 加载用户上传数量
            this.loadUserUploadsCount();
        } catch (error) {
            console.error("加载用户信息失败: ", error);
            Utils.showToastMessage('网络错误，无法加载用户信息', 'error');
            
            // 显示登录按钮
            const loginBtn = document.createElement('button');
            loginBtn.className = 'mt-4 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors';
            loginBtn.textContent = '立即登录';
            loginBtn.onclick = function() {
                if (typeof openLoginModal === 'function') {
                    openLoginModal();
                } else {
                    // 如果没有模态框函数，则跳转到首页
                    window.location.href = '/index.php';
                }
            };
            
            // 清空主内容区并显示登录提示
            const mainContent = document.querySelector('main');
            if (mainContent) {
                mainContent.innerHTML = `
                    <div class="flex flex-col items-center justify-center h-full py-12">
                        <img src="/static/icons/login-required.svg" alt="登录提示" class="w-32 h-32 mb-4">
                        <h2 class="text-xl font-bold mb-2">出错了</h2>
                        <p class="text-gray-600 mb-4 text-center">获取用户信息失败，请重新登录</p>
                        <div id="login-button-container"></div>
                    </div>
                `;
                document.getElementById('login-button-container').appendChild(loginBtn);
            }
        }
    },

    initSidebarTabSwitching: function() {
        const tabMap = {
            '#dashboard': 'dashboard-section',
            '#liked': 'liked-section',
            '#collections': 'collections-section',
            '#downloads': 'downloads-section',
            '#uploads': 'uploads-section',
            '#history': 'history-section',
            '#settings': 'settings-section',
            '#exile-management': 'exile-management-section' // 2024-07-29 新增
        };
        const navLinks = document.querySelectorAll('aside nav a');
        const sections = Object.values(tabMap).map(id => document.getElementById(id));
        
        navLinks.forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                const hash = this.getAttribute('href');

                // 隐藏所有内容区域
                sections.forEach(sec => sec && sec.classList.add('hidden'));

                // 显示对应的内容区域
                if (tabMap[hash]) {
                    const showSec = document.getElementById(tabMap[hash]);
                    if (showSec) showSec.classList.remove('hidden');
                }

                // 激活菜单高亮
                navLinks.forEach(l => l.classList.remove('bg-primary/10', 'text-primary'));
                this.classList.add('bg-primary/10', 'text-primary');

                // 如果是流放管理，加载日志
                if (hash === '#exile-management' && typeof AdminExile !== 'undefined') {
                    AdminExile.loadOperationLogs();
                }
                
                // 如果是我的喜欢，加载喜欢的壁纸
                if (hash === '#liked') {
                    Dashboard.loadLikedWallpapers();
                }
            });
        });

        // 初始根据URL hash显示对应内容
        const initialHash = window.location.hash;
        if (initialHash && tabMap[initialHash]) {
            const initialSection = document.getElementById(tabMap[initialHash]);
            if (initialSection) initialSection.classList.remove('hidden');
            const activeLink = document.querySelector(`aside nav a[href="${initialHash}"]`);
            if (activeLink) {
                navLinks.forEach(l => l.classList.remove('bg-primary/10', 'text-primary'));
                activeLink.classList.add('bg-primary/10', 'text-primary');
            }
        } else {
            // 默认显示账户设置
            const settingsSection = document.getElementById('settings-section');
            if (settingsSection) settingsSection.classList.remove('hidden');
            const settingsLink = document.querySelector('a[href="#settings"]');
            if (settingsLink) settingsLink.classList.add('bg-primary/10', 'text-primary');
        }
    },

    bindProfileSettingsEvents: function() {
        // 资料修改
        const profileForm = document.getElementById('profile-form');
        if (profileForm) {
            profileForm.onsubmit = async function (e) {
                e.preventDefault();
                const username = document.getElementById('profile-username').value.trim();
                // 移除邮箱字段，因为邮箱不可修改
                const profileSuccess = document.getElementById('profile-success');
                const profileError = document.getElementById('profile-error');
                profileSuccess.classList.add('hidden');
                profileError.classList.add('hidden');

                try {
                    const userData = JSON.parse(localStorage.getItem('user') || '{}');
                    if (!userData.id) {
                        throw new Error('请先登录');
                    }

                    const response = await fetch('/api/update_profile.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'Authorization': `Bearer ${userData.id}`
                        },
                        credentials: 'same-origin',
                        body: `username=${encodeURIComponent(username)}`
                    });
                    const json = await response.json();
                    if (json.code === 0 || json.code === 200) {
                        profileSuccess.textContent = json.msg || json.message;
                        profileSuccess.classList.remove('hidden');
                        // 显示成功提示消息
                        Utils.showToastMessage(json.msg || json.message || '资料修改成功', 'success');
                        Dashboard.loadUserInfo(); // 刷新用户信息，更新侧边栏
                    } else {
                        profileError.textContent = json.msg || json.message || '资料修改失败';
                        profileError.classList.remove('hidden');
                        // 显示错误提示消息
                        Utils.showToastMessage(json.msg || json.message || '资料修改失败', 'error');
                    }
                } catch (error) {
                    console.error("更新个人资料失败: ", error);
                    profileError.textContent = '网络错误，更新失败';
                    profileError.classList.remove('hidden');
                }
            };
        }

        // 密码修改
        const passwordForm = document.getElementById('password-form');
        if (passwordForm) {
            passwordForm.onsubmit = async function (e) {
                e.preventDefault();
                const old_password = document.getElementById('old-password').value;
                const new_password = document.getElementById('new-password').value;
                const confirm_password = document.getElementById('confirm-password').value;
                const passwordSuccess = document.getElementById('password-success');
                const passwordError = document.getElementById('password-error');
                passwordSuccess.classList.add('hidden');
                passwordError.classList.add('hidden');

                if (new_password !== confirm_password) {
                    passwordError.textContent = '新密码和确认密码不一致';
                    passwordError.classList.remove('hidden');
                    return;
                }
                // 验证密码强度
                if (new_password.length < 4) {
                    passwordError.textContent = '密码必须至少4位';
                    passwordError.classList.remove('hidden');
                    return;
                }

                try {
                    const userData = JSON.parse(localStorage.getItem('user') || '{}');
                    if (!userData.id) {
                        throw new Error('请先登录');
                    }

                    const response = await fetch('/api/change_password.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Cache-Control': 'no-cache, no-store, must-revalidate',
                            'Authorization': `Bearer ${userData.id}`
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            old_password: old_password,
                            new_password: new_password,
                            confirm_password: confirm_password
                        })
                    });
                    const json = await response.json();
                    if (json.code === 200 || json.code === 0) {
                        passwordSuccess.textContent = json.message || json.msg;
                        passwordSuccess.classList.remove('hidden');
                        // 显示成功提示消息
                        Utils.showToastMessage(json.message || json.msg || '密码修改成功，请重新登录', 'success');
                        passwordForm.reset(); // 清空表单
                        
                        // 密码修改成功后，延迟1秒自动退出登录
                        setTimeout(() => {
                            Utils.showToastMessage('密码修改成功，请重新登录...', 'success');
                            
                            // 先清除本地存储
                            localStorage.clear(); // 清除所有本地存储，包括user_info
                            sessionStorage.clear(); // 清除所有会话存储
                            
                            // 确保清除window.currentUser
                            if (window.currentUser) {
                                window.currentUser = null;
                            }
                            
                            // 触发退出登录API
                            fetch('/api/logout.php', {
                                method: 'POST',
                                credentials: 'include' // 确保发送cookies
                            }).then(() => {
                                setTimeout(() => {
                                    window.location.href = 'index.php';
                                }, 1000);
                            }).catch(error => {
                                console.error('退出登录失败:', error);
                                // 即使API调用失败，也强制跳转
                                setTimeout(() => {
                                    window.location.href = 'index.php';
                                }, 1000);
                            });
                        }, 1000);
                    } else {
                        passwordError.textContent = json.message || json.msg;
                        passwordError.classList.remove('hidden');
                        // 显示错误提示消息
                        Utils.showToastMessage(json.message || json.msg || '密码修改失败', 'error');
                    }
                } catch (error) {
                    console.error("修改密码失败: ", error);
                    passwordError.textContent = '网络错误，修改失败';
                    passwordError.classList.remove('hidden');
                }
            };
        }
    },

    bindAvatarUploadEvents: function() {
        const avatarForm = document.getElementById('avatar-form');
        const avatarUploadBtn = avatarForm ? avatarForm.querySelector('button[type="submit"]') : null;
        let cropper = null;
        let croppedBlob = null;
        
        if (!avatarForm || !avatarUploadBtn) return; // 如果元素不存在，直接返回

        // 上传按钮初始禁用
        avatarUploadBtn.disabled = true;
        avatarUploadBtn.classList.add('opacity-50', 'cursor-not-allowed');
        
        // 初始化头像预览
        const avatarPreview = document.getElementById('avatar-preview');
        if (avatarPreview) {
            // 尝试从API获取用户信息来设置头像
            fetch('/api/auth_unified.php?action=getUserInfo')
                .then(response => response.json())
                .then(json => {
                    if ((json.code === 0 || json.code === 200) && json.data) {
                        if (json.data.avatar) {
                        avatarPreview.src = json.data.avatar + '?t=' + Date.now();
                    } else {
                        avatarPreview.src = '/static/icons/default-avatar.svg';
                    }
                    }
                })
                .catch(error => {
                    console.error('获取用户头像失败:', error);
                    // 设置默认头像
                    avatarPreview.src = '/static/icons/default-avatar.svg';
                });
        }

        // 头像选择与裁剪
        const chooseAvatarBtn = document.getElementById('choose-avatar');
        const avatarInput = document.getElementById('avatar-input');
        const avatarCropperModal = document.getElementById('avatar-cropper-modal');
        const cropperImg = document.getElementById('cropper-image');
        const cropperCancelBtn = document.getElementById('cropper-cancel');
        const cropperConfirmBtn = document.getElementById('cropper-confirm');

        if (chooseAvatarBtn) {
            chooseAvatarBtn.onclick = function () {
                avatarInput.click();
            };
        }

        if (avatarInput) {
            avatarInput.onchange = function () {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        if (avatarCropperModal) avatarCropperModal.classList.remove('hidden');
                        if (cropperImg) cropperImg.src = e.target.result;
                        if (cropper) cropper.destroy();
                        cropper = new Cropper(cropperImg, {
                            aspectRatio: 1,
                            viewMode: 1,
                            autoCropArea: 1,
                            movable: true,
                            zoomable: true,
                            rotatable: false,
                            scalable: false
                        });
                    };
                    reader.readAsDataURL(file);
                }
            };
        }

        if (cropperCancelBtn) {
            cropperCancelBtn.onclick = function () {
                if (avatarCropperModal) avatarCropperModal.classList.add('hidden');
                if (cropper) cropper.destroy();
                cropper = null;
                if (avatarInput) avatarInput.value = ''; // 清空文件输入框
                croppedBlob = null;
                avatarUploadBtn.disabled = true;
                avatarUploadBtn.classList.add('opacity-50', 'cursor-not-allowed');
            };
        }

        // 压缩图片函数
        const compressImage = async (blob, quality = 0.75) => {
            return new Promise((resolve, reject) => {
                const img = new Image();
                img.src = URL.createObjectURL(blob);
                img.onload = function() {
                    // 创建canvas
                    const canvas = document.createElement('canvas');
                    // 设置尺寸为256x256，与裁剪尺寸一致
                    canvas.width = 256;
                    canvas.height = 256;
                    const ctx = canvas.getContext('2d');
                    // 绘制图像
                    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                    // 转换为blob
                    canvas.toBlob((compressedBlob) => {
                        console.log(`压缩前: ${blob.size / 1024}KB, 压缩后: ${compressedBlob.size / 1024}KB`);
                        resolve(compressedBlob);
                    }, 'image/jpeg', quality); // 使用JPEG格式，质量为0.75
                };
                img.onerror = reject;
            });
        };

        if (cropperConfirmBtn) {
            cropperConfirmBtn.onclick = async function () {
                if (!cropper) return;
                cropper.getCroppedCanvas({ width: 256, height: 256 }).toBlob(async function (blob) {
                    try {
                        // 压缩图片
                        const compressedBlob = await compressImage(blob, 0.75); // 压缩率为75%
                        croppedBlob = compressedBlob;
                        const url = URL.createObjectURL(compressedBlob);
                        const avatarPreview = document.getElementById('avatar-preview');
                        if (avatarPreview) avatarPreview.src = url;
                        if (avatarCropperModal) avatarCropperModal.classList.add('hidden');
                        if (cropper) cropper.destroy();
                        cropper = null;
                        // 启用上传按钮
                        avatarUploadBtn.disabled = false;
                        avatarUploadBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                        // 显示压缩信息
                        Utils.showToastMessage(`图片已压缩 (${(blob.size / 1024).toFixed(1)}KB → ${(compressedBlob.size / 1024).toFixed(1)}KB)`, 'info');
                    } catch (error) {
                        console.error('图片压缩失败:', error);
                        // 如果压缩失败，使用原始裁剪图片
                        croppedBlob = blob;
                        const url = URL.createObjectURL(blob);
                        const avatarPreview = document.getElementById('avatar-preview');
                        if (avatarPreview) avatarPreview.src = url;
                        if (avatarCropperModal) avatarCropperModal.classList.add('hidden');
                        if (cropper) cropper.destroy();
                        cropper = null;
                        // 启用上传按钮
                        avatarUploadBtn.disabled = false;
                        avatarUploadBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                    }
                }, 'image/png');
            };
        }

        // 头像上传
        avatarForm.onsubmit = async function (e) {
            e.preventDefault();
            const avatarSuccess = document.getElementById('avatar-success');
            const avatarError = document.getElementById('avatar-error');
            avatarSuccess.classList.add('hidden');
            avatarError.classList.add('hidden');

            if (!croppedBlob) {
                avatarError.textContent = '请先选择并裁剪头像';
                avatarError.classList.remove('hidden');
                return;
            }
            avatarUploadBtn.disabled = true;
            avatarUploadBtn.textContent = '上传中...'; // 修改按钮文本
            avatarUploadBtn.classList.add('opacity-50', 'cursor-not-allowed');

            const formData = new FormData();
            formData.append('avatar', croppedBlob, 'avatar.png');

            try {
                const userData = JSON.parse(localStorage.getItem('user') || '{}');
                if (!userData.id) {
                    throw new Error('请先登录');
                }

                const response = await fetch('/api/upload_avatar.php', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${userData.id}`
                    },
                    credentials: 'same-origin',
                    body: formData
                });
                const json = await response.json();

                if (json.code === 200 || json.code === 0) {
                    avatarSuccess.textContent = json.message || json.msg;
                    avatarSuccess.classList.remove('hidden');
                    // 显示成功提示消息
                    Utils.showToastMessage(json.message || json.msg || '头像上传成功', 'success');
                    
                    // 统一的头像路径
                    const newAvatarSrc = json.data + '?t=' + Date.now();
                    
                    // 更新头像预览
                    const avatarPreview = document.getElementById('avatar-preview');
                    if (avatarPreview) avatarPreview.src = newAvatarSrc;

                    // 更新所有用户头像
                    document.querySelectorAll('.user-avatar').forEach(el => {
                        el.src = newAvatarSrc;
                    });
                    
                    // 更新侧边栏头像
                    const sidebarAvatar = document.getElementById('sidebar-user-avatar');
                    if (sidebarAvatar) {
                        sidebarAvatar.src = newAvatarSrc;
                    }
                    
                    // 更新本地存储中的用户信息
                    const userData = JSON.parse(localStorage.getItem('user') || '{}');
                    userData.avatar = json.data;
                    localStorage.setItem('user', JSON.stringify(userData));
                    
                    // 同步更新首页头像（如果存在）
                    if (window.parent && window.parent !== window) {
                        window.parent.postMessage({
                            type: 'updateAvatar',
                            avatarSrc: newAvatarSrc,
                            username: userData.username
                        }, '*');
                    }

                    // 重置状态
                    croppedBlob = null;
                    if (avatarInput) avatarInput.value = '';
                    avatarUploadBtn.disabled = true;
                    avatarUploadBtn.textContent = '上传头像'; // 恢复按钮文本
                    avatarUploadBtn.classList.add('opacity-50', 'cursor-not-allowed');
                } else {
                    avatarError.textContent = json.message || json.msg;
                    avatarError.classList.remove('hidden');
                    // 显示错误提示消息
                    Utils.showToastMessage(json.message || json.msg || '头像上传失败', 'error');
                    avatarUploadBtn.disabled = false;
                    avatarUploadBtn.textContent = '上传头像'; // 恢复按钮文本
                    avatarUploadBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            } catch (error) {
                console.error("头像上传失败: ", error);
                avatarError.textContent = '网络错误，头像上传失败';
                avatarError.classList.remove('hidden');
                // 显示错误提示消息
                Utils.showToastMessage('网络错误，头像上传失败', 'error');
                avatarUploadBtn.disabled = false;
                avatarUploadBtn.textContent = '上传头像'; // 恢复按钮文本
                avatarUploadBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        };
    },
    
    /**
     * 加载用户上传数量
     * 文件: static/js/dashboard.js
     * 功能: 获取当前用户上传的壁纸数量并更新侧边栏显示
     */
    loadUserUploadsCount: function() {
        const userData = JSON.parse(localStorage.getItem('user') || '{}');
        if (!userData.id) {
            console.error('未登录状态');
            return;
        }

        fetch('/api/my_user_uploads.php?action=count', {
            headers: {
                'Authorization': `Bearer ${userData.id}`
            },
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(result => {
            if (result.code === 200) {
                const userUploadsCountElement = document.getElementById('user-uploads-count');
                if (userUploadsCountElement) {
                    userUploadsCountElement.textContent = result.data.count || 0;
                }
            }
        })
        .catch(error => {
            console.error('获取用户上传数量失败:', error);
        });
    },
    
    /**
     * 加载我的喜欢壁纸
     * 文件: static/js/dashboard.js
     * 功能: 获取当前用户喜欢的壁纸并动态渲染到页面
     */
    loadLikedWallpapers: function() {
        const loadingElement = document.getElementById('liked-loading');
        const emptyElement = document.getElementById('liked-empty');
        const gridElement = document.getElementById('liked-wallpapers-grid');
        
        if (!loadingElement || !emptyElement || !gridElement) {
            console.error('我的喜欢页面元素未找到');
            return;
        }
        
        // 显示加载状态
        loadingElement.classList.remove('hidden');
        emptyElement.classList.add('hidden');
        gridElement.innerHTML = '';
        
        const userData = JSON.parse(localStorage.getItem('user') || '{}');
        if (!userData.id) {
            loadingElement.classList.add('hidden');
            emptyElement.classList.remove('hidden');
            Utils.showToastMessage('请先登录', 'error');
            return;
        }

        fetch(`${this.API_BASE_URL}/my_liked_wallpapers.php`, {
            headers: {
                'Authorization': `Bearer ${userData.id}`
            },
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(result => {
            loadingElement.classList.add('hidden');
            
            if (result.code === 200 && result.data && result.data.length > 0) {
                // 有喜欢的壁纸，渲染到网格中
                result.data.forEach(wallpaper => {
                    const wallpaperCard = this.createWallpaperCard(wallpaper);
                    gridElement.appendChild(wallpaperCard);
                });
            } else {
                // 没有喜欢的壁纸，显示空状态
                emptyElement.classList.remove('hidden');
            }
        })
        .catch(error => {
            console.error('加载我的喜欢失败:', error);
            loadingElement.classList.add('hidden');
            emptyElement.classList.remove('hidden');
            Utils.showToastMessage('加载我的喜欢失败', 'error');
        });
    },
    
    /**
     * 创建壁纸卡片元素
     * 文件: static/js/dashboard.js
     * 功能: 根据壁纸数据创建DOM元素
     */
    createWallpaperCard: function(wallpaper) {
        const cardDiv = document.createElement('div');
        cardDiv.className = 'masonry-item wallpaper-card-item bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300';
        cardDiv.setAttribute('data-wallpaper-id', wallpaper.id);
        
        cardDiv.innerHTML = `
            <div class="relative group">
                <img src="${wallpaper.thumbnail_url || wallpaper.image_url}" 
                     alt="${wallpaper.title}" 
                     class="w-full h-auto object-cover cursor-pointer"
                     onclick="WallpaperDetail.openModal(${wallpaper.id})">
                
                <!-- 悬停时显示的操作按钮 -->
                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 transition-all duration-300 flex items-center justify-center opacity-0 group-hover:opacity-100">
                    <button class="preview-btn bg-white text-gray-800 px-3 py-1 rounded-full text-sm font-medium hover:bg-gray-100 transition-colors mr-2"
                            onclick="WallpaperDetail.openModal(${wallpaper.id})">
                        <i class="fas fa-eye mr-1"></i>预览
                    </button>
                </div>
                
                <!-- 右上角的喜欢按钮 -->
                <button class="favorite-btn absolute top-2 right-2 w-8 h-8 bg-white bg-opacity-80 hover:bg-opacity-100 rounded-full flex items-center justify-center transition-all duration-200 shadow-sm"
                        onclick="Dashboard.toggleLike(${wallpaper.id}, this)">
                    <i class="fas fa-heart text-red-500"></i>
                </button>
            </div>
            
            <div class="p-4">
                <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2">${wallpaper.title}</h3>
                <div class="flex items-center justify-between text-sm text-gray-600">
                    <span class="flex items-center">
                        <i class="fas fa-eye mr-1"></i>
                        ${wallpaper.views || 0}
                    </span>
                    <span class="flex items-center">
                        <i class="fas fa-download mr-1"></i>
                        ${wallpaper.downloads || 0}
                    </span>
                    <span class="flex items-center">
                        <i class="fas fa-heart mr-1"></i>
                        ${wallpaper.likes || 0}
                    </span>
                </div>
            </div>
        `;
        
        return cardDiv;
    },
    
    /**
     * 切换喜欢状态
     * 文件: static/js/dashboard.js
     * 功能: 处理用户点击喜欢按钮的操作
     */
    toggleLike: function(wallpaperId, buttonElement) {
        const icon = buttonElement.querySelector('i');
        const isLiked = icon.classList.contains('text-red-500');
        
        // 发送请求到后端
        const action = isLiked ? 'unlike' : 'like';
        const userData = JSON.parse(localStorage.getItem('user') || '{}');
        if (!userData.id) {
            Utils.showToastMessage('请先登录', 'error');
            return;
        }

        fetch('/api/toggle_like_v2.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${userData.id}`
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                wallpaper_id: wallpaperId
            })
        })
        .then(response => response.json())
        .then(result => {
            if (result.code === 0) {
                if (result.action === 'unliked') {
                    // 从我的喜欢页面移除这个卡片
                    const cardElement = buttonElement.closest('.wallpaper-card-item');
                    if (cardElement) {
                        cardElement.remove();
                        
                        // 检查是否还有其他卡片，如果没有则显示空状态
                        const gridElement = document.getElementById('liked-wallpapers-grid');
                        const emptyElement = document.getElementById('liked-empty');
                        if (gridElement && gridElement.children.length === 0 && emptyElement) {
                            emptyElement.classList.remove('hidden');
                        }
                    }
                    Utils.showToastMessage('已取消喜欢', 'success');
                } else if (result.action === 'liked') {
                    // 更新按钮状态
                    icon.classList.add('text-red-500');
                    Utils.showToastMessage('已添加到喜欢', 'success');
                }
            } else {
                Utils.showToastMessage(result.msg || '操作失败', 'error');
            }
        })
        .catch(error => {
            console.error('切换喜欢状态失败:', error);
            Utils.showToastMessage('网络错误，操作失败', 'error');
        });
    }
};

// 确保在DOM加载完成后初始化Dashboard模块
// 这一部分会由dashboard.php中的DOMContentLoaded事件处理，这里不再直接调用
// document.addEventListener('DOMContentLoaded', function() {
//     Dashboard.init();
// });
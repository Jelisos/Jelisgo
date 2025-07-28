/**
 * 密码重置功能模块
 * 文件: static/js/password-reset.js
 * 描述: 处理密码重置流程的前端逻辑
 * 创建日期: 2025-01-27
 */

// 密码重置模块
const PasswordReset = {
    // 存储当前状态
    state: {
        email: '',
        verificationCode: '',
        resetToken: ''
    },
    
    // 初始化函数
    init: function() {
        console.log('[PasswordReset] 初始化密码重置模块');
        this.bindEvents();
    },
    
    // 绑定事件
    bindEvents: function() {
        // 忘记密码链接点击事件 - 已移除，现在使用直接链接到forgot-password.html
        
        // 返回登录按钮点击事件
        const backToLoginBtn = document.getElementById('back-to-login');
        if (backToLoginBtn) {
            backToLoginBtn.addEventListener('click', this.backToLogin.bind(this));
        }
        
        // 发送验证码按钮点击事件
        const sendResetCodeBtn = document.getElementById('send-reset-code');
        if (sendResetCodeBtn) {
            sendResetCodeBtn.addEventListener('click', this.sendResetCode.bind(this));
        }
        
        // 验证码提交按钮点击事件
        const verifyCodeSubmitBtn = document.getElementById('verify-code-submit');
        if (verifyCodeSubmitBtn) {
            verifyCodeSubmitBtn.addEventListener('click', this.verifyCode.bind(this));
        }
        
        // 重新发送验证码按钮点击事件
        const resendCodeBtn = document.getElementById('resend-code');
        if (resendCodeBtn) {
            resendCodeBtn.addEventListener('click', this.resendCode.bind(this));
        }
        
        // 重置密码提交按钮点击事件
        const resetPasswordSubmitBtn = document.getElementById('reset-password-submit');
        if (resetPasswordSubmitBtn) {
            resetPasswordSubmitBtn.addEventListener('click', this.resetPassword.bind(this));
        }
        
        // 关闭模态框按钮点击事件
        const closeModals = document.querySelectorAll('#close-forgot-password-modal, #close-verify-code-modal, #close-reset-password-modal');
        closeModals.forEach(btn => {
            btn.addEventListener('click', this.closeAllModals.bind(this));
        });
    },
    
    // 显示忘记密码模态框
    showForgotPasswordModal: function() {
        // 隐藏登录模态框
        const loginModal = document.getElementById('login-modal');
        if (loginModal) {
            loginModal.classList.add('hidden');
        }
        
        // 显示忘记密码模态框
        const forgotPasswordModal = document.getElementById('forgot-password-modal');
        if (forgotPasswordModal) {
            forgotPasswordModal.classList.remove('hidden');
        }
        
        // 清空表单和错误信息
        const resetEmailInput = document.getElementById('reset-email');
        if (resetEmailInput) {
            resetEmailInput.value = '';
        }
        
        const errorMsg = document.querySelector('#forgot-password-form .form-error-msg');
        if (errorMsg) {
            errorMsg.textContent = '';
        }
    },
    
    // 返回登录页面
    backToLogin: function() {
        // 隐藏忘记密码模态框
        const forgotPasswordModal = document.getElementById('forgot-password-modal');
        if (forgotPasswordModal) {
            forgotPasswordModal.classList.add('hidden');
        }
        
        // 显示登录模态框
        const loginModal = document.getElementById('login-modal');
        if (loginModal) {
            loginModal.classList.remove('hidden');
        }
    },
    
    // 发送重置密码验证码
    sendResetCode: function() {
        const resetEmailInput = document.getElementById('reset-email');
        const errorMsg = document.querySelector('#forgot-password-form .form-error-msg');
        
        if (!resetEmailInput || !errorMsg) return;
        
        const email = resetEmailInput.value.trim();
        
        // 验证邮箱格式
        if (!email) {
            errorMsg.textContent = '请输入邮箱地址';
            return;
        }
        
        if (!this.isValidEmail(email)) {
            errorMsg.textContent = '请输入有效的邮箱地址';
            return;
        }
        
        // 禁用按钮，显示加载状态
        const sendResetCodeBtn = document.getElementById('send-reset-code');
        if (sendResetCodeBtn) {
            sendResetCodeBtn.disabled = true;
            sendResetCodeBtn.textContent = '发送中...';
        }
        
        // 发送请求到后端
        fetch('/api/minaxg/request_reset.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email: email })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // 保存邮箱
                this.state.email = email;
                
                // 隐藏忘记密码模态框
                const forgotPasswordModal = document.getElementById('forgot-password-modal');
                if (forgotPasswordModal) {
                    forgotPasswordModal.classList.add('hidden');
                }
                
                // 显示验证码模态框
                const verifyCodeModal = document.getElementById('verify-code-modal');
                if (verifyCodeModal) {
                    verifyCodeModal.classList.remove('hidden');
                }
                
                // 清空验证码输入框和错误信息
                const verificationCodeInput = document.getElementById('verification-code');
                if (verificationCodeInput) {
                    verificationCodeInput.value = '';
                }
                
                const verifyErrorMsg = document.querySelector('#verify-code-form .form-error-msg');
                if (verifyErrorMsg) {
                    verifyErrorMsg.textContent = '';
                }
            } else {
                // 显示错误信息
                errorMsg.textContent = data.message || '发送验证码失败，请稍后重试';
            }
        })
        .catch(error => {
            console.error('发送验证码请求失败:', error);
            errorMsg.textContent = '网络错误，请稍后重试';
        })
        .finally(() => {
            // 恢复按钮状态
            if (sendResetCodeBtn) {
                sendResetCodeBtn.disabled = false;
                sendResetCodeBtn.textContent = '发送验证码';
            }
        });
    },
    
    // 验证验证码
    verifyCode: function() {
        const verificationCodeInput = document.getElementById('verification-code');
        const errorMsg = document.querySelector('#verify-code-form .form-error-msg');
        
        if (!verificationCodeInput || !errorMsg) return;
        
        const code = verificationCodeInput.value.trim();
        
        // 验证验证码格式
        if (!code) {
            errorMsg.textContent = '请输入验证码';
            return;
        }
        
        if (code.length !== 6) {
            errorMsg.textContent = '验证码必须是6位数字';
            return;
        }
        
        // 禁用按钮，显示加载状态
        const verifyCodeSubmitBtn = document.getElementById('verify-code-submit');
        if (verifyCodeSubmitBtn) {
            verifyCodeSubmitBtn.disabled = true;
            verifyCodeSubmitBtn.textContent = '验证中...';
        }
        
        // 发送请求到后端
        fetch('/api/minaxg/verify_code.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ 
                email: this.state.email,
                code: code 
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // 保存验证码和token
                this.state.verificationCode = code;
                this.state.resetToken = data.token || code;
                
                // 隐藏验证码模态框
                const verifyCodeModal = document.getElementById('verify-code-modal');
                if (verifyCodeModal) {
                    verifyCodeModal.classList.add('hidden');
                }
                
                // 显示重置密码模态框
                const resetPasswordModal = document.getElementById('reset-password-modal');
                if (resetPasswordModal) {
                    resetPasswordModal.classList.remove('hidden');
                }
                
                // 清空密码输入框和错误信息
                const newPasswordInput = document.getElementById('new-password');
                const confirmNewPasswordInput = document.getElementById('confirm-new-password');
                if (newPasswordInput) newPasswordInput.value = '';
                if (confirmNewPasswordInput) confirmNewPasswordInput.value = '';
                
                const resetErrorMsg = document.querySelector('#reset-password-form .form-error-msg');
                if (resetErrorMsg) {
                    resetErrorMsg.textContent = '';
                }
            } else {
                // 显示错误信息
                errorMsg.textContent = data.message || '验证码验证失败，请重试';
            }
        })
        .catch(error => {
            console.error('验证码验证请求失败:', error);
            errorMsg.textContent = '网络错误，请稍后重试';
        })
        .finally(() => {
            // 恢复按钮状态
            if (verifyCodeSubmitBtn) {
                verifyCodeSubmitBtn.disabled = false;
                verifyCodeSubmitBtn.textContent = '验证';
            }
        });
    },
    
    // 重新发送验证码
    resendCode: function() {
        const errorMsg = document.querySelector('#verify-code-form .form-error-msg');
        
        if (!errorMsg) return;
        
        // 禁用按钮，显示加载状态
        const resendCodeBtn = document.getElementById('resend-code');
        if (resendCodeBtn) {
            resendCodeBtn.disabled = true;
            resendCodeBtn.textContent = '发送中...';
        }
        
        // 发送请求到后端
        fetch('/api/minaxg/request_reset.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email: this.state.email })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // 显示成功信息
                errorMsg.textContent = '验证码已重新发送，请查收';
                errorMsg.style.color = '#10b981'; // 绿色
            } else {
                // 显示错误信息
                errorMsg.textContent = data.message || '重新发送验证码失败，请稍后重试';
                errorMsg.style.color = '#ef4444'; // 红色
            }
        })
        .catch(error => {
            console.error('重新发送验证码请求失败:', error);
            errorMsg.textContent = '网络错误，请稍后重试';
            errorMsg.style.color = '#ef4444'; // 红色
        })
        .finally(() => {
            // 恢复按钮状态
            if (resendCodeBtn) {
                resendCodeBtn.disabled = false;
                resendCodeBtn.textContent = '重新发送验证码';
            }
        });
    },
    
    // 重置密码
    resetPassword: function() {
        const newPasswordInput = document.getElementById('new-password');
        const confirmNewPasswordInput = document.getElementById('confirm-new-password');
        const errorMsg = document.querySelector('#reset-password-form .form-error-msg');
        
        if (!newPasswordInput || !confirmNewPasswordInput || !errorMsg) return;
        
        const newPassword = newPasswordInput.value;
        const confirmNewPassword = confirmNewPasswordInput.value;
        
        // 验证密码
        if (!newPassword) {
            errorMsg.textContent = '请输入新密码';
            return;
        }
        
        if (newPassword.length < 4) {
            errorMsg.textContent = '密码长度至少为4位';
            return;
        }
        
        if (newPassword !== confirmNewPassword) {
            errorMsg.textContent = '两次输入的密码不一致';
            return;
        }
        
        // 禁用按钮，显示加载状态
        const resetPasswordSubmitBtn = document.getElementById('reset-password-submit');
        if (resetPasswordSubmitBtn) {
            resetPasswordSubmitBtn.disabled = true;
            resetPasswordSubmitBtn.textContent = '重置中...';
        }
        
        // 发送请求到后端
        fetch('/api/minaxg/reset_password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ 
                password: newPassword,
                confirm_password: confirmNewPassword
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // 关闭所有模态框
                this.closeAllModals();
                
                // 显示登录模态框
                const loginModal = document.getElementById('login-modal');
                if (loginModal) {
                    loginModal.classList.remove('hidden');
                    
                    // 显示成功信息
                    const loginErrorMsg = document.querySelector('#login-form .form-error-msg');
                    if (loginErrorMsg) {
                        loginErrorMsg.textContent = '密码重置成功，请使用新密码登录';
                        loginErrorMsg.style.color = '#10b981'; // 绿色
                    }
                }
            } else {
                // 显示错误信息
                errorMsg.textContent = data.message || '密码重置失败，请稍后重试';
            }
        })
        .catch(error => {
            console.error('密码重置请求失败:', error);
            errorMsg.textContent = '网络错误，请稍后重试';
        })
        .finally(() => {
            // 恢复按钮状态
            if (resetPasswordSubmitBtn) {
                resetPasswordSubmitBtn.disabled = false;
                resetPasswordSubmitBtn.textContent = '重置密码';
            }
        });
    },
    
    // 关闭所有模态框
    closeAllModals: function() {
        const modals = document.querySelectorAll('#forgot-password-modal, #verify-code-modal, #reset-password-modal');
        modals.forEach(modal => {
            if (modal) {
                modal.classList.add('hidden');
            }
        });
    },
    
    // 验证邮箱格式
    isValidEmail: function(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
};

// 页面加载完成后初始化密码重置模块
document.addEventListener('DOMContentLoaded', function() {
    PasswordReset.init();
});
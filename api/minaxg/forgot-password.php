<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>忘记密码 - 壁纸网站</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .step-indicator {
            transition: all 0.3s ease;
        }
        .step-active {
            background-color: #3b82f6;
            color: white;
        }
        .step-completed {
            background-color: #10b981;
            color: white;
        }
        .step-inactive {
            background-color: #e5e7eb;
            color: #6b7280;
        }
        .form-container {
            transition: all 0.3s ease;
        }
        .form-hidden {
            display: none;
        }
        .error-message {
            color: #ef4444;
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }
        .success-message {
            color: #10b981;
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <!-- 返回首页链接 -->
        <div class="text-center mb-6">
            <a href="../../index.php" class="text-blue-600 hover:text-blue-800 text-sm flex items-center justify-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                返回首页
            </a>
        </div>

        <!-- 主容器 -->
        <div class="bg-white rounded-lg shadow-md p-8">
            <h1 class="text-2xl font-bold text-center text-gray-900 mb-6">忘记密码</h1>
            
            <!-- 步骤指示器 -->
            <div class="flex justify-between mb-8">
                <div class="flex items-center">
                    <div id="step1-indicator" class="step-indicator step-active w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium">1</div>
                    <span class="ml-2 text-sm text-gray-600">输入邮箱</span>
                </div>
                <div class="flex-1 h-px bg-gray-300 mx-4 mt-4"></div>
                <div class="flex items-center">
                    <div id="step2-indicator" class="step-indicator step-inactive w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium">2</div>
                    <span class="ml-2 text-sm text-gray-600">验证码</span>
                </div>
                <div class="flex-1 h-px bg-gray-300 mx-4 mt-4"></div>
                <div class="flex items-center">
                    <div id="step3-indicator" class="step-indicator step-inactive w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium">3</div>
                    <span class="ml-2 text-sm text-gray-600">重置密码</span>
                </div>
            </div>

            <!-- 步骤1: 输入邮箱 -->
            <div id="step1-form" class="form-container">
                <form id="email-form">
                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">邮箱地址</label>
                        <input type="email" id="email" name="email" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="请输入您的邮箱地址">
                    </div>
                    <div id="email-error" class="error-message"></div>
                    <button type="submit" id="send-code-btn" 
                            class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200">
                        发送验证码
                    </button>
                </form>
            </div>

            <!-- 步骤2: 验证码 -->
            <div id="step2-form" class="form-container form-hidden">
                <form id="verify-form">
                    <div class="mb-4">
                        <label for="verification-code" class="block text-sm font-medium text-gray-700 mb-2">验证码</label>
                        <input type="text" id="verification-code" name="verification-code" required maxlength="6"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-center text-lg tracking-widest"
                               placeholder="请输入6位验证码">
                        <p class="text-sm text-gray-500 mt-2">验证码已发送到您的邮箱，请查收</p>
                    </div>
                    <div id="verify-error" class="error-message"></div>
                    <div class="flex space-x-3">
                        <button type="button" id="back-to-email-btn" 
                                class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition duration-200">
                            返回上一步
                        </button>
                        <button type="submit" id="verify-code-btn" 
                                class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200">
                            验证
                        </button>
                    </div>
                </form>
            </div>

            <!-- 步骤3: 重置密码 -->
            <div id="step3-form" class="form-container form-hidden">
                <form id="reset-form">
                    <div class="mb-4">
                        <label for="new-password" class="block text-sm font-medium text-gray-700 mb-2">新密码</label>
                        <input type="password" id="new-password" name="new-password" required minlength="4"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="请输入新密码（至少4位）">
                    </div>
                    <div class="mb-4">
                        <label for="confirm-password" class="block text-sm font-medium text-gray-700 mb-2">确认密码</label>
                        <input type="password" id="confirm-password" name="confirm-password" required minlength="4"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="请再次输入新密码">
                    </div>
                    <div id="reset-error" class="error-message"></div>
                    <div class="flex space-x-3">
                        <button type="button" id="back-to-verify-btn" 
                                class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition duration-200">
                            返回上一步
                        </button>
                        <button type="submit" id="reset-password-btn" 
                                class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200">
                            重置密码
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // 全局变量
        let currentStep = 1;
        let userEmail = '';
        let resetToken = '';

        // DOM元素
        const step1Form = document.getElementById('step1-form');
        const step2Form = document.getElementById('step2-form');
        const step3Form = document.getElementById('step3-form');
        const step1Indicator = document.getElementById('step1-indicator');
        const step2Indicator = document.getElementById('step2-indicator');
        const step3Indicator = document.getElementById('step3-indicator');

        // 工具函数
        function showError(elementId, message) {
            const errorElement = document.getElementById(elementId);
            if (errorElement) {
                errorElement.textContent = message;
            }
        }

        function clearError(elementId) {
            showError(elementId, '');
        }

        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        function updateStepIndicator(step) {
            // 重置所有指示器
            [step1Indicator, step2Indicator, step3Indicator].forEach((indicator, index) => {
                indicator.className = 'step-indicator w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium';
                if (index + 1 < step) {
                    indicator.classList.add('step-completed');
                } else if (index + 1 === step) {
                    indicator.classList.add('step-active');
                } else {
                    indicator.classList.add('step-inactive');
                }
            });
        }

        function showStep(step) {
            // 隐藏所有表单
            [step1Form, step2Form, step3Form].forEach(form => {
                form.classList.add('form-hidden');
            });

            // 显示当前步骤的表单
            switch(step) {
                case 1:
                    step1Form.classList.remove('form-hidden');
                    break;
                case 2:
                    step2Form.classList.remove('form-hidden');
                    break;
                case 3:
                    step3Form.classList.remove('form-hidden');
                    break;
            }

            currentStep = step;
            updateStepIndicator(step);
        }

        // API调用函数
        async function sendResetCode(email) {
            try {
                const response = await fetch('request_reset.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ email })
                });

                const result = await response.json();
                return result;
            } catch (error) {
                console.error('发送验证码失败:', error);
                return { success: false, message: '网络错误，请稍后重试' };
            }
        }

        async function verifyCode(email, code) {
            try {
                const response = await fetch('verify_code.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ email, code })
                });

                const result = await response.json();
                return result;
            } catch (error) {
                console.error('验证码验证失败:', error);
                return { success: false, message: '网络错误，请稍后重试' };
            }
        }

        async function resetPassword(token, password, confirmPassword) {
            try {
                const response = await fetch('reset_password.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ 
                        token,
                        password, 
                        confirm_password: confirmPassword 
                    })
                });

                const result = await response.json();
                return result;
            } catch (error) {
                console.error('密码重置失败:', error);
                return { success: false, message: '网络错误，请稍后重试' };
            }
        }

        // 事件处理函数
        document.getElementById('email-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const email = document.getElementById('email').value.trim();
            clearError('email-error');

            if (!email) {
                showError('email-error', '请输入邮箱地址');
                return;
            }

            if (!isValidEmail(email)) {
                showError('email-error', '邮箱格式不正确');
                return;
            }

            const button = document.getElementById('send-code-btn');
            const originalText = button.textContent;
            button.textContent = '发送中...';
            button.disabled = true;

            const result = await sendResetCode(email);

            if (result.success) {
                userEmail = email;
                showStep(2);
            } else {
                showError('email-error', result.message || '发送失败，请稍后重试');
            }

            button.textContent = originalText;
            button.disabled = false;
        });

        document.getElementById('verify-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const code = document.getElementById('verification-code').value.trim();
            clearError('verify-error');

            if (!code) {
                showError('verify-error', '请输入验证码');
                return;
            }

            if (code.length !== 6) {
                showError('verify-error', '验证码必须是6位数字');
                return;
            }

            const button = document.getElementById('verify-code-btn');
            const originalText = button.textContent;
            button.textContent = '验证中...';
            button.disabled = true;

            const result = await verifyCode(userEmail, code);

            if (result.success) {
                resetToken = result.token || code; // 使用返回的token或验证码作为token
                showStep(3);
            } else {
                showError('verify-error', result.message || '验证失败，请重试');
            }

            button.textContent = originalText;
            button.disabled = false;
        });

        document.getElementById('reset-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const password = document.getElementById('new-password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            clearError('reset-error');

            if (!password) {
                showError('reset-error', '请输入新密码');
                return;
            }

            if (password.length < 4) {
                showError('reset-error', '密码长度至少4位');
                return;
            }

            if (password !== confirmPassword) {
                showError('reset-error', '两次输入的密码不一致');
                return;
            }

            const button = document.getElementById('reset-password-btn');
            const originalText = button.textContent;
            button.textContent = '重置中...';
            button.disabled = true;

            const result = await resetPassword(resetToken, password, confirmPassword);

            if (result.success) {
                alert('密码重置成功！请使用新密码登录。');
                window.location.href = '../../index.php';
            } else {
                showError('reset-error', result.message || '重置失败，请重试');
            }

            button.textContent = originalText;
            button.disabled = false;
        });

        // 返回按钮事件
        document.getElementById('back-to-email-btn').addEventListener('click', () => {
            showStep(1);
        });

        document.getElementById('back-to-verify-btn').addEventListener('click', () => {
            showStep(2);
        });

        // 初始化
        showStep(1);
    </script>
</body>
</html>
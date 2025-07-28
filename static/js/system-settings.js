/**
 * 系统设置管理JavaScript
 * 处理系统设置页面的交互逻辑
 */

class SystemSettings {
    constructor() {
        this.apiUrl = '/api/system_settings.php';
        this.settings = {};
        this.init();
    }

    // 初始化
    init() {
        this.bindEvents();
        this.loadSettings();
    }

    // 绑定事件
    bindEvents() {
        // 保存按钮事件
        const saveBtn = document.querySelector('.save-settings-btn');
        if (saveBtn) {
            saveBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.saveSettings();
            });
        }

        // 重置按钮事件
        const resetBtn = document.querySelector('.reset-settings-btn');
        if (resetBtn) {
            resetBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.resetSettings();
            });
        }

        // 表单验证事件
        this.bindFormValidation();
        
        // 安全设置和邮件设置字段的实时验证
        this.initEventListeners();
    }

    // 初始化事件监听器
    initEventListeners() {
        // 邮箱字段实时验证
        const emailFields = document.querySelectorAll('input[name="contact_email"], input[name="sender_email"]');
        emailFields.forEach(field => {
            field.addEventListener('blur', () => this.validateEmail(field));
        });

        // 数字字段实时验证
        const numberFields = document.querySelectorAll('input[name="login_fail_limit"], input[name="account_lock_time"], input[name="min_password_length"], input[name="smtp_port"]');
        numberFields.forEach(field => {
            field.addEventListener('blur', () => this.validateNumber(field));
        });

        // 文件大小字段验证
        const fileSizeField = document.querySelector('input[name="max_file_size"]');
        if (fileSizeField) {
            fileSizeField.addEventListener('blur', () => this.validateNumber(fileSizeField));
        }
    }

    // 绑定表单验证
    bindFormValidation() {
        // 保存按钮
        const saveBtn = document.querySelector('.save-settings-btn');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => this.saveSettings());
        }

        // 重置按钮
        const resetBtn = document.querySelector('.reset-settings-btn');
        if (resetBtn) {
            resetBtn.addEventListener('click', () => this.resetSettings());
        }

        // 邮箱验证
        const emailInputs = document.querySelectorAll('input[type="email"]');
        emailInputs.forEach(input => {
            input.addEventListener('blur', () => {
                this.validateEmail(input);
            });
        });

        // 数字验证
        const numberInputs = document.querySelectorAll('input[type="number"]');
        numberInputs.forEach(input => {
            input.addEventListener('blur', () => {
                this.validateNumber(input);
            });
        });
    }

    // 加载设置
    async loadSettings() {
        try {
            this.showLoading(true);
            
            const response = await fetch(this.apiUrl, {
                method: 'GET',
                headers: {
                    'Authorization': 'Bearer admin-token',
                    'Content-Type': 'application/json'
                }
            });

            const result = await response.json();
            
            if (result.success) {
                this.settings = result.data;
                this.populateForm();
            } else {
                this.showMessage('加载设置失败: ' + result.message, 'error');
            }
        } catch (error) {
            console.error('加载设置失败:', error);
            this.showMessage('加载设置失败，请检查网络连接', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    // 填充表单
    populateForm() {
        console.log('populateForm called with settings:', this.settings);
        
        // 基本设置
        if (this.settings.basic) {
            this.setInputValue('site_name', this.settings.basic.site_name);
            this.setInputValue('site_subtitle', this.settings.basic.site_subtitle);
            this.setInputValue('contact_email', this.settings.basic.contact_email);
            this.setInputValue('icp_number', this.settings.basic.icp_number);
        }

        // SEO设置
        if (this.settings.seo) {
            this.setInputValue('seo_keywords', this.settings.seo.keywords);
            this.setInputValue('seo_description', this.settings.seo.description);
            this.setInputValue('seo_og_image', this.settings.seo.og_image);
        }

        // 上传设置
        if (this.settings.upload) {
            this.setInputValue('max_file_size', this.settings.upload.max_file_size);
            this.setInputValue('allowed_file_types', this.settings.upload.allowed_file_types);
            this.setSelectValue('image_quality', this.settings.upload.image_quality);
            this.setRadioValue('watermark', this.settings.upload.watermark_type);
        }

        // 安全设置
        if (this.settings.security) {
            this.setInputValue('login_fail_limit', this.settings.security.login_fail_limit);
            this.setInputValue('account_lock_time', this.settings.security.account_lock_time);
            this.setInputValue('min_password_length', this.settings.security.min_password_length);
        }

        // 邮件设置
        if (this.settings.email) {
            this.setInputValue('smtp_server', this.settings.email.smtp_server);
            this.setInputValue('smtp_port', this.settings.email.smtp_port);
            this.setInputValue('sender_email', this.settings.email.sender_email);
            this.setInputValue('sender_password', this.settings.email.sender_password);
            this.setCheckboxValue('enable_ssl', this.settings.email.enable_ssl);
        }
    }

    // 收集表单数据
    collectFormData() {
        return {
            basic: {
                site_name: this.getInputValue('site_name'),
                site_subtitle: this.getInputValue('site_subtitle'),
                contact_email: this.getInputValue('contact_email'),
                icp_number: this.getInputValue('icp_number')
            },
            seo: {
                keywords: this.getInputValue('seo_keywords'),
                description: this.getInputValue('seo_description'),
                og_image: this.getInputValue('seo_og_image')
            },
            upload: {
                max_file_size: parseInt(this.getInputValue('max_file_size')) || 10,
                allowed_file_types: this.getInputValue('allowed_file_types'),
                image_quality: this.getSelectValue('image_quality'),
                watermark_type: this.getRadioValue('watermark')
            },
            security: {
                login_fail_limit: parseInt(this.getInputValue('login_fail_limit')) || 5,
                account_lock_time: parseInt(this.getInputValue('account_lock_time')) || 30,
                min_password_length: parseInt(this.getInputValue('min_password_length')) || 8
            },
            email: {
                smtp_server: this.getInputValue('smtp_server'),
                smtp_port: parseInt(this.getInputValue('smtp_port')) || 587,
                sender_email: this.getInputValue('sender_email'),
                sender_password: this.getInputValue('sender_password'),
                enable_ssl: this.getCheckboxValue('enable_ssl')
            }
        };
    }

    // 保存设置
    async saveSettings() {
        try {
            // 验证表单
            if (!this.validateForm()) {
                return;
            }

            this.showLoading(true);
            
            const formData = this.collectFormData();
            
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer admin-token',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });

            const result = await response.json();
            
            if (result.success) {
                this.settings = formData;
                this.showMessage('设置保存成功！', 'success');
            } else {
                this.showMessage('保存失败: ' + result.message, 'error');
            }
        } catch (error) {
            console.error('保存设置失败:', error);
            this.showMessage('保存失败，请检查网络连接', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    // 重置设置
    resetSettings() {
        if (confirm('确定要重置所有设置吗？此操作不可撤销。')) {
            this.populateForm();
            this.showMessage('设置已重置', 'info');
        }
    }

    // 表单验证
    validateForm() {
        let isValid = true;
        
        // 验证必填字段
        const requiredFields = [
            { name: 'site_name', label: '站点名称' },
            { name: 'contact_email', label: '联系邮箱' }
        ];
        
        requiredFields.forEach(field => {
            const value = this.getInputValue(field.name);
            if (!value || !value.trim()) {
                this.showFieldError(field.name, field.label + '不能为空');
                isValid = false;
            } else {
                this.clearFieldError(field.name);
            }
        });
        
        // 验证安全设置数值范围
        const securityFields = [
            { name: 'login_fail_limit', min: 1, max: 10, label: '登录失败限制次数' },
            { name: 'account_lock_time', min: 1, max: 1440, label: '账户锁定时间' },
            { name: 'min_password_length', min: 6, max: 32, label: '最小密码长度' }
        ];
        
        securityFields.forEach(field => {
            const value = parseInt(this.getInputValue(field.name));
            if (isNaN(value) || value < field.min || value > field.max) {
                this.showFieldError(field.name, `${field.label}必须在${field.min}到${field.max}之间`);
                isValid = false;
            } else {
                this.clearFieldError(field.name);
            }
        });
        
        // 验证邮件设置
        const smtpPort = parseInt(this.getInputValue('smtp_port'));
        if (isNaN(smtpPort) || smtpPort < 1 || smtpPort > 65535) {
            this.showFieldError('smtp_port', 'SMTP端口必须在1到65535之间');
            isValid = false;
        } else {
            this.clearFieldError('smtp_port');
        }
        
        // 验证邮箱格式
        const emailFields = ['contact_email', 'sender_email'];
        emailFields.forEach(fieldName => {
            const email = this.getInputValue(fieldName);
            if (email && !this.isValidEmail(email)) {
                this.showFieldError(fieldName, '请输入有效的邮箱地址');
                isValid = false;
            } else if (email) {
                this.clearFieldError(fieldName);
            }
        });
        
        return isValid;
    }

    // 邮箱验证
    validateEmail(input) {
        const email = input.value.trim();
        if (email && !this.isValidEmail(email)) {
            this.showFieldError(input.name || input.id, '请输入有效的邮箱地址');
            return false;
        } else {
            this.clearFieldError(input.name || input.id);
            return true;
        }
    }

    // 数字验证
    validateNumber(input) {
        const value = parseInt(input.value);
        const min = parseInt(input.min) || 0;
        const max = parseInt(input.max) || Infinity;
        
        if (isNaN(value) || value < min || value > max) {
            this.showFieldError(input.name || input.id, `请输入${min}到${max}之间的数字`);
            return false;
        } else {
            this.clearFieldError(input.name || input.id);
            return true;
        }
    }

    // 工具方法
    setInputValue(name, value) {
        const input = document.querySelector(`input[name="${name}"], input[id="${name}"], textarea[name="${name}"], textarea[id="${name}"]`);
        if (input) input.value = value || '';
    }

    getInputValue(name) {
        const input = document.querySelector(`input[name="${name}"], input[id="${name}"], textarea[name="${name}"], textarea[id="${name}"]`);
        return input ? input.value : '';
    }

    setSelectValue(name, value) {
        const select = document.querySelector(`select[name="${name}"], select[id="${name}"]`);
        if (select) select.value = value || '';
    }

    getSelectValue(name) {
        const select = document.querySelector(`select[name="${name}"], select[id="${name}"]`);
        return select ? select.value : '';
    }

    setRadioValue(name, value) {
        const radio = document.querySelector(`input[name="${name}"][value="${value}"]`);
        if (radio) radio.checked = true;
    }

    getRadioValue(name) {
        const radio = document.querySelector(`input[name="${name}"]:checked`);
        return radio ? radio.value : '';
    }

    setCheckboxValue(name, value) {
        const checkbox = document.querySelector(`input[name="${name}"], input[id="${name}"]`);
        if (checkbox) checkbox.checked = !!value;
    }

    getCheckboxValue(name) {
        const checkbox = document.querySelector(`input[name="${name}"], input[id="${name}"]`);
        return checkbox ? checkbox.checked : false;
    }

    showFieldError(fieldName, message) {
        const field = document.querySelector(`input[name="${fieldName}"], input[id="${fieldName}"], select[name="${fieldName}"], select[id="${fieldName}"], textarea[name="${fieldName}"], textarea[id="${fieldName}"]`);
        if (field) {
            field.classList.add('border-red-500');
            
            // 移除旧的错误消息
            const oldError = field.parentNode.querySelector('.field-error');
            if (oldError) oldError.remove();
            
            // 添加新的错误消息
            const errorDiv = document.createElement('div');
            errorDiv.className = 'field-error text-red-500 text-sm mt-1';
            errorDiv.textContent = message;
            field.parentNode.appendChild(errorDiv);
        }
    }

    clearFieldError(fieldName) {
        const field = document.querySelector(`input[name="${fieldName}"], input[id="${fieldName}"], select[name="${fieldName}"], select[id="${fieldName}"], textarea[name="${fieldName}"], textarea[id="${fieldName}"]`);
        if (field) {
            field.classList.remove('border-red-500');
            const errorDiv = field.parentNode.querySelector('.field-error');
            if (errorDiv) errorDiv.remove();
        }
    }

    showMessage(message, type = 'info') {
        // 移除旧消息
        const oldMessage = document.querySelector('.system-message');
        if (oldMessage) oldMessage.remove();
        
        // 创建新消息
        const messageDiv = document.createElement('div');
        messageDiv.className = `system-message fixed top-4 right-4 px-4 py-2 rounded-lg shadow-lg z-50 ${this.getMessageClass(type)}`;
        messageDiv.textContent = message;
        
        document.body.appendChild(messageDiv);
        
        // 3秒后自动移除
        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.parentNode.removeChild(messageDiv);
            }
        }, 3000);
    }

    getMessageClass(type) {
        switch (type) {
            case 'success': return 'bg-green-500 text-white';
            case 'error': return 'bg-red-500 text-white';
            case 'warning': return 'bg-yellow-500 text-white';
            default: return 'bg-blue-500 text-white';
        }
    }

    showLoading(show) {
        const saveBtn = document.querySelector('.save-settings-btn');
        if (saveBtn) {
            if (show) {
                saveBtn.disabled = true;
                saveBtn.textContent = '保存中...';
            } else {
                saveBtn.disabled = false;
                saveBtn.textContent = '保存设置';
            }
        }
    }

    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
}

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', () => {
    new SystemSettings();
});
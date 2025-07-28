/**
 * 壁纸上传功能模块
 */

class WallpaperUploader {
    constructor() {
        this.selectedFile = null;
        this.isUploading = false;
        this.API_BASE_URL = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1' 
            ? 'http://localhost/api' 
            : '/api';
        
        this.initElements();
        this.bindEvents();
        this.checkLoginStatus();
    }

    initElements() {
        // 获取DOM元素
        this.uploadArea = document.getElementById('upload-area');
        this.fileInput = document.getElementById('file-input');
        this.browseBtn = document.getElementById('browse-btn');
        this.changeFileBtn = document.getElementById('change-file-btn');
        this.uploadPlaceholder = document.getElementById('upload-placeholder');
        this.previewArea = document.getElementById('preview-area');
        this.previewImage = document.getElementById('preview-image');
        this.fileInfo = document.getElementById('file-info');
        this.uploadForm = document.getElementById('upload-form');
        this.submitBtn = document.getElementById('submit-btn');
        this.uploadProgress = document.getElementById('upload-progress');
        this.progressBar = document.getElementById('progress-bar');
        this.progressText = document.getElementById('progress-text');
        this.successModal = document.getElementById('success-modal');
        this.uploadAnotherBtn = document.getElementById('upload-another-btn');
        
        // 表单字段
        this.titleInput = document.getElementById('title');
        this.categorySelect = document.getElementById('category');
        this.tagsInput = document.getElementById('tags');
        this.descriptionTextarea = document.getElementById('description');
        this.promptInput = document.getElementById('prompt');
    }

    bindEvents() {
        // 文件选择事件
        this.browseBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.openFileDialog();
        });
        this.changeFileBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.openFileDialog();
        });
        this.fileInput.addEventListener('change', (e) => this.handleFileSelect(e));
        
        // 拖拽事件
        this.uploadArea.addEventListener('click', (e) => {
            // 只有点击上传区域本身时才触发文件选择，避免点击按钮时重复触发
            if (e.target === this.uploadArea && !this.selectedFile) {
                this.openFileDialog();
            }
        });
        this.uploadArea.addEventListener('dragover', (e) => this.handleDragOver(e));
        this.uploadArea.addEventListener('dragleave', (e) => this.handleDragLeave(e));
        this.uploadArea.addEventListener('drop', (e) => this.handleDrop(e));
        
        // 表单提交事件
        this.uploadForm.addEventListener('submit', (e) => this.handleSubmit(e));
        
        // 成功模态框事件
        this.uploadAnotherBtn.addEventListener('click', () => this.resetForm());
    }

    async checkLoginStatus() {
        try {
            console.log('[UPLOAD] 开始检查登录状态');
            
            // 从localStorage获取用户信息
            const userInfo = JSON.parse(localStorage.getItem('user') || '{}');
            if (!userInfo.id) {
                console.log('[UPLOAD] localStorage中无用户信息，跳转到登录页');
                alert('请先登录后再上传壁纸');
                window.location.href = 'index.php';
                return false;
            }
            
            // 验证用户在数据库中的状态
            const response = await fetch(`${this.API_BASE_URL}/auth_unified.php?action=validateUser&user_id=${userInfo.id}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${userInfo.id}`
                }
            });
            
            console.log('[UPLOAD] 用户验证响应状态:', response.status);
            const result = await response.json();
            console.log('[UPLOAD] 用户验证结果:', result);
            
            if (result.code === 200 && result.data) {
                console.log('[UPLOAD] 用户验证通过:', result.data.username);
                return true;
            } else {
                console.log('[UPLOAD] 用户验证失败，清除本地信息并跳转到登录页');
                localStorage.removeItem('user');
                alert('用户身份验证失败，请重新登录');
                window.location.href = 'index.php';
                return false;
            }
        } catch (error) {
            console.error('[UPLOAD] 检查登录状态失败:', error);
            localStorage.removeItem('user');
            alert('检查登录状态失败，请重新登录');
            window.location.href = 'index.php';
            return false;
        }
    }

    openFileDialog() {
        this.fileInput.click();
    }

    handleFileSelect(event) {
        const file = event.target.files[0];
        if (file) {
            this.processFile(file);
        }
    }

    handleDragOver(event) {
        event.preventDefault();
        this.uploadArea.classList.add('dragover');
    }

    handleDragLeave(event) {
        event.preventDefault();
        this.uploadArea.classList.remove('dragover');
    }

    handleDrop(event) {
        event.preventDefault();
        this.uploadArea.classList.remove('dragover');
        
        const files = event.dataTransfer.files;
        if (files.length > 0) {
            this.processFile(files[0]);
        }
    }

    processFile(file) {
        // 验证文件类型
        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            alert('只支持 JPG, PNG, WebP 格式的图片');
            return;
        }
        
        // 验证文件大小 (1MB - 10MB)
        const minSize = 1 * 1024 * 1024; // 1MB
        const maxSize = 10 * 1024 * 1024; // 10MB
        if (file.size < minSize) {
            alert('为保证图片质量，文件大小不能小于 1MB');
            return;
        }
        if (file.size > maxSize) {
            alert('文件大小不能超过 10MB');
            return;
        }
        
        this.selectedFile = file;
        this.showPreview(file);
    }

    showPreview(file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            this.previewImage.src = e.target.result;
            this.fileInfo.textContent = `${file.name} (${this.formatFileSize(file.size)})`;
            
            // 切换显示状态
            this.uploadPlaceholder.classList.add('hidden');
            this.previewArea.classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    async handleSubmit(event) {
        event.preventDefault();
        
        if (this.isUploading) return;
        
        // 验证表单
        if (!this.selectedFile) {
            alert('请选择要上传的壁纸文件');
            return;
        }
        
        const title = this.titleInput.value.trim();
        if (!title) {
            alert('请输入壁纸标题');
            this.titleInput.focus();
            return;
        }
        
        await this.uploadFile();
    }

    async uploadFile() {
        this.isUploading = true;
        this.showProgress();
        
        try {
            // 压缩图片
            const compressedFile = await this.compressImage(this.selectedFile);
            
            // 获取用户信息进行身份验证
            const userInfo = JSON.parse(localStorage.getItem('user') || '{}');
            if (!userInfo.id) {
                alert('用户身份信息缺失，请重新登录');
                window.location.href = 'index.php';
                return;
            }
            
            // 创建FormData
            const formData = new FormData();
            formData.append('action', 'upload');
            formData.append('user_id', userInfo.id); // 添加用户ID用于数据库验证
            formData.append('wallpaper', this.selectedFile); // 原图
            formData.append('compressed_wallpaper', compressedFile); // 压缩图
            formData.append('title', this.titleInput.value.trim());
            formData.append('category', this.categorySelect.value);
            formData.append('tags', this.tagsInput.value.trim());
            formData.append('description', this.descriptionTextarea.value.trim());
            formData.append('prompt', this.promptInput.value.trim());
            
            // 创建XMLHttpRequest以支持进度监控
            const xhr = new XMLHttpRequest();
            
            // 监听上传进度
            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    this.updateProgress(percentComplete);
                }
            });
            
            // 处理响应
            xhr.addEventListener('load', () => {
                try {
                    const result = JSON.parse(xhr.responseText);
                    if (result.code === 200) {
                        this.showSuccess();
                    } else {
                        throw new Error(result.message || '上传失败');
                    }
                } catch (error) {
                    console.error('解析响应失败:', error);
                    alert('上传失败，请重试');
                }
                this.hideProgress();
                this.isUploading = false;
            });
            
            xhr.addEventListener('error', () => {
                alert('网络错误，上传失败');
                this.hideProgress();
                this.isUploading = false;
            });
            
            // 发送请求到用户上传API
            xhr.open('POST', `${this.API_BASE_URL}/user_upload_wallpaper.php`);
            xhr.withCredentials = true;
            
            // 2024-12-19 新增：添加Authorization头支持统一认证
        const userInfo = JSON.parse(localStorage.getItem('user') || '{}');
        if (userInfo.id) {
            xhr.setRequestHeader('Authorization', `Bearer ${userInfo.id}`);
        }
            
            xhr.send(formData);
            
        } catch (error) {
            console.error('上传错误:', error);
            alert('上传失败，请重试');
            this.hideProgress();
            this.isUploading = false;
        }
    }

    showProgress() {
        this.uploadProgress.classList.remove('hidden');
        this.submitBtn.disabled = true;
        this.submitBtn.textContent = '上传中...';
        this.updateProgress(0);
    }

    updateProgress(percent) {
        const roundedPercent = Math.round(percent);
        this.progressBar.style.width = `${roundedPercent}%`;
        this.progressText.textContent = `${roundedPercent}%`;
    }

    hideProgress() {
        this.uploadProgress.classList.add('hidden');
        this.submitBtn.disabled = false;
        this.submitBtn.textContent = '上传壁纸';
    }

    showSuccess() {
        this.successModal.classList.remove('hidden');
    }

    resetForm() {
        // 隐藏成功模态框
        this.successModal.classList.add('hidden');
        
        // 重置文件选择
        this.selectedFile = null;
        this.fileInput.value = '';
        
        // 重置预览
        this.previewArea.classList.add('hidden');
        this.uploadPlaceholder.classList.remove('hidden');
        this.previewImage.src = '';
        this.fileInfo.textContent = '';
        
        // 重置表单
        this.uploadForm.reset();
        
        // 重置状态
        this.isUploading = false;
        this.hideProgress();
    }

    /**
     * 压缩图片
     * @param {File} file - 原始图片文件
     * @returns {Promise<File>} - 压缩后的图片文件
     */
    async compressImage(file) {
        return new Promise((resolve, reject) => {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            const img = new Image();
            
            img.onload = () => {
                // 压缩配置 - 与首页预览图保持一致
                const config = {
                    maxWidth: 1200,
                    maxHeight: 900,
                    quality: 0.95  // 95% 质量
                };
                
                // 计算压缩后的尺寸
                let { width, height } = this.calculateCompressedSize(
                    img.width, img.height, 
                    config.maxWidth, config.maxHeight
                );
                
                // 设置canvas尺寸
                canvas.width = width;
                canvas.height = height;
                
                // 绘制压缩后的图片
                ctx.drawImage(img, 0, 0, width, height);
                
                // 转换为Blob
                canvas.toBlob((blob) => {
                    if (blob) {
                        // 创建新的File对象
                        const compressedFile = new File([blob], 
                            `compressed_${file.name}`, 
                            { type: 'image/jpeg' }
                        );
                        resolve(compressedFile);
                    } else {
                        reject(new Error('图片压缩失败'));
                    }
                }, 'image/jpeg', config.quality);
            };
            
            img.onerror = () => reject(new Error('图片加载失败'));
            img.src = URL.createObjectURL(file);
        });
    }

    /**
     * 计算压缩后的尺寸
     * @param {number} originalWidth - 原始宽度
     * @param {number} originalHeight - 原始高度
     * @param {number} maxWidth - 最大宽度
     * @param {number} maxHeight - 最大高度
     * @returns {Object} - {width, height}
     */
    calculateCompressedSize(originalWidth, originalHeight, maxWidth, maxHeight) {
        let width = originalWidth;
        let height = originalHeight;
        
        // 如果原图尺寸小于最大尺寸，不需要压缩
        if (width <= maxWidth && height <= maxHeight) {
            return { width, height };
        }
        
        // 计算缩放比例
        const widthRatio = maxWidth / width;
        const heightRatio = maxHeight / height;
        const ratio = Math.min(widthRatio, heightRatio);
        
        width = Math.round(width * ratio);
        height = Math.round(height * ratio);
        
        return { width, height };
    }
}

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', () => {
    new WallpaperUploader();
});
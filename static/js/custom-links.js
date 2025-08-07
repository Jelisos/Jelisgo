/**
 * 自定义链接管理模块
 * 文件位置：/static/js/custom-links.js
 * 
 * 功能：壁纸详情页面自定义链接的前端交互逻辑
 */

class CustomLinksManager {
    constructor() {
        this.wallpaperId = null;
        this.isAdmin = false;
        this.links = [];
        this.currentModal = null;
        
        this.initInstance();
    }
    
    /**
     * 静态初始化方法 - 单例模式
     * @returns {CustomLinksManager} 自定义链接管理器实例
     */
    static init() {
        // 确保只创建一个实例
        if (!window.customLinksManagerInstance) {
            window.customLinksManagerInstance = new CustomLinksManager();
            console.log('[CustomLinksManager] 模块已初始化');
        }
        return window.customLinksManagerInstance;
    }
    
    /**
     * 销毁实例 - 用于清理资源
     */
    static destroy() {
        if (window.customLinksManagerInstance) {
            // 移除事件监听器
            document.removeEventListener('wallpaper-detail-show', window.customLinksManagerInstance.handleDetailShow);
            window.customLinksManagerInstance = null;
            console.log('[CustomLinksManager] 模块已销毁');
        }
    }
    
    /**
     * 初始化实例
     */
    initInstance() {
        // 检查管理员权限
        this.checkAdminStatus();
        
        // 绑定事件处理器到实例
        this.handleDetailShow = this.handleDetailShow.bind(this);
        
        // 监听详情页面显示事件
        document.addEventListener('wallpaper-detail-show', this.handleDetailShow);
    }
    
    /**
     * 处理详情页面显示事件
     * @param {CustomEvent} event 详情页面显示事件
     */
    handleDetailShow(event) {
        try {
            this.wallpaperId = event.detail?.id;
            console.log('[CustomLinksManager] 接收到详情页面显示事件，壁纸ID:', this.wallpaperId);
            if (this.wallpaperId) {
                // 初始化现有的自定义链接区域
                this.initLinksSection();
                
                // 加载链接数据
                this.loadLinks();
            }
        } catch (error) {
            console.error('[CustomLinksManager] 处理详情页面显示事件时出错:', error);
            // 静默失败，不影响其他功能
        }
    }
    
    /**
     * 检查管理员状态
     */
    checkAdminStatus() {
        try {
            const userData = localStorage.getItem('user');
            if (userData) {
                const user = JSON.parse(userData);
                this.isAdmin = user.is_admin === true || user.is_admin === 1;
            }
        } catch (e) {
            console.warn('检查管理员状态失败:', e);
            this.isAdmin = false;
        }
    }
    
    /**
     * 获取认证token（用户ID）
     */
    getAuthToken() {
        try {
            const userData = localStorage.getItem('user');
            if (userData) {
                const user = JSON.parse(userData);
                return user.id;
            }
        } catch (e) {
            console.warn('获取认证token失败:', e);
        }
        return null;
    }
    
    /**
     * 获取当前壁纸ID
     */
    getCurrentWallpaperId() {
        // 从详情模态框的数据属性获取
        const detailModal = document.getElementById('wallpaper-detail-modal');
        if (detailModal && detailModal.dataset.wallpaperId) {
            return parseInt(detailModal.dataset.wallpaperId);
        }
        
        // 从全局变量获取（如果存在）
        if (window.currentWallpaperId) {
            return parseInt(window.currentWallpaperId);
        }
        
        return null;
    }
    
    /**
     * 初始化现有的自定义链接区域
     */
    initLinksSection() {
        try {
            // 查找现有的自定义链接区域
            const linksSection = document.getElementById('custom-links-section');
            if (!linksSection) {
                console.warn('未找到自定义链接区域');
                return;
            }
            
            // 设置管理员权限相关的样式类
            linksSection.className = `custom-links-section ${this.isAdmin ? 'admin' : 'non-admin'}`;
            
            // 显示区域
            linksSection.classList.remove('hidden');
            
            // 根据管理员权限显示/隐藏添加按钮
            const addBtn = document.getElementById('add-custom-link-btn');
            if (addBtn) {
                if (this.isAdmin) {
                    addBtn.classList.remove('hidden');
                } else {
                    addBtn.classList.add('hidden');
                }
            }
            
            // 绑定事件
            this.bindEvents();
        } catch (error) {
            console.error('[CustomLinksManager] 初始化链接区域时出错:', error);
            // 静默失败，不影响其他功能
        }
    }
    

    
    /**
     * 绑定事件
     */
    bindEvents() {
        try {
            if (this.isAdmin) {
                // 添加链接按钮
                const addBtn = document.getElementById('add-custom-link-btn');
                if (addBtn) {
                    addBtn.addEventListener('click', () => this.showAddModal());
                }
            }
        } catch (error) {
            console.error('[CustomLinksManager] 绑定事件时出错:', error);
            // 静默失败，不影响其他功能
        }
    }
    
    /**
     * 加载链接数据
     */
    async loadLinks() {
        if (!this.wallpaperId) {
            console.warn('[CustomLinksManager] 壁纸ID为空，无法加载链接');
            return;
        }

        // 检查用户权限
        if (!window.PermissionManager || !window.PermissionManager.hasAdvancedFeatureAccess()) {
            this.renderPermissionDenied();
            return;
        }

        try {
            const response = await fetch(`/api/custom_links.php?action=get&wallpaper_id=${this.wallpaperId}`);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                this.links = result.data || [];
                this.renderLinks();
            } else {
                console.error('[CustomLinksManager] 加载链接失败:', result.message);
                // 静默失败，显示空状态而不是错误
                this.links = [];
                this.renderLinks();
            }
        } catch (error) {
            console.error('[CustomLinksManager] 网络请求失败:', error);
            // 静默失败，显示空状态
            this.links = [];
            this.renderLinks();
        }
    }
    
    /**
     * 渲染链接列表
     */
    renderLinks() {
        try {
            const container = document.getElementById('custom-links-list');
            if (!container) {
                console.warn('未找到链接列表容器');
                return;
            }
            
            if (this.links.length === 0) {
                container.innerHTML = '<div class="custom-links-empty">暂无自定义链接</div>';
                return;
            }
            
            container.innerHTML = this.links.map(link => {
                return this.getLinkHTML(link);
            }).join('');
            
            // 绑定链接事件
            this.bindLinkEvents();
        } catch (error) {
            console.error('[CustomLinksManager] 渲染链接列表时出错:', error);
            // 静默失败，显示错误状态
            const container = document.getElementById('custom-links-list');
            if (container) {
                container.innerHTML = '<div class="custom-links-error">加载链接时出现错误</div>';
            }
        }
    }
    
    /**
     * 渲染权限不足提示
     */
    renderPermissionDenied() {
        try {
            const container = document.getElementById('custom-links-list');
            const permissionDenied = document.getElementById('links-permission-denied');
            
            if (container) {
                container.style.display = 'none';
            }
            
            if (permissionDenied) {
                permissionDenied.classList.remove('hidden');
                permissionDenied.style.display = 'block';
            }
            
            // 隐藏添加按钮
            const addBtn = document.getElementById('add-custom-link-btn');
            if (addBtn) {
                addBtn.style.display = 'none';
            }
        } catch (error) {
            console.error('[CustomLinksManager] 渲染权限不足提示时出错:', error);
        }
    }
    
    /**
     * 获取单个链接HTML
     */
    getLinkHTML(link) {
        return `
            <div class="custom-link-item ${link.color_class}" data-link-id="${link.id}">
                <div class="link-content">
                    <a href="${link.url}" target="_blank" rel="nofollow" class="link-title">
                        ${this.escapeHtml(link.title)}
                    </a>
                    <div class="link-priority">
                        <span class="priority-badge ${link.color_class}"></span>
                        <span>${link.description ? this.escapeHtml(link.description) : '暂无描述'}</span>
                    </div>
                </div>
                ${this.isAdmin ? `
                    <div class="link-actions">
                        <button class="action-btn edit-btn" onclick="window.customLinksManagerInstance.editLink(${link.id})">
                            编辑
                        </button>
                        <button class="action-btn delete-btn" onclick="window.customLinksManagerInstance.deleteLink(${link.id})">
                            删除
                        </button>
                    </div>
                ` : ''}
            </div>
        `;
    }
    
    /**
     * 绑定链接事件
     */
    bindLinkEvents() {
        try {
            // 链接点击统计
            const linkTitles = document.querySelectorAll('.custom-link-item .link-title');
            linkTitles.forEach(link => {
                link.addEventListener('click', (e) => {
                    // 获取链接ID并统计点击
                    const linkItem = link.closest('.custom-link-item');
                    const linkId = linkItem.getAttribute('data-link-id');
                    if (linkId) {
                        this.recordLinkClick(linkId);
                    }
                });
            });
        } catch (error) {
            console.error('[CustomLinksManager] 绑定链接事件时出错:', error);
            // 静默失败，不影响其他功能
        }
    }
    
    /**
     * 记录链接点击统计
     */
    async recordLinkClick(linkId) {
        try {
            const response = await fetch('/api/custom_links.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'click',
                    link_id: linkId
                })
            });
            
            // 静默处理，不显示错误信息
            if (!response.ok) {
                console.warn('点击统计失败:', response.status);
            }
        } catch (error) {
            console.warn('点击统计请求失败:', error);
            // 静默失败，不影响用户体验
        }
    }
    
    /**
     * 显示添加链接模态框
     */
    showAddModal() {
        try {
            const modal = this.createModal('添加链接', this.getFormHTML());
            document.body.appendChild(modal);
            this.currentModal = modal;
            
            // 绑定表单事件
            this.bindFormEvents('add');
        } catch (error) {
            console.error('[CustomLinksManager] 显示添加模态框时出错:', error);
            this.showError('无法打开添加链接窗口');
        }
    }
    
    /**
     * 显示编辑链接模态框
     */
    editLink(linkId) {
        try {
            const link = this.links.find(l => l.id === linkId);
            if (!link) {
                this.showError('链接不存在');
                return;
            }
            
            const modal = this.createModal('编辑链接', this.getFormHTML(link));
            document.body.appendChild(modal);
            this.currentModal = modal;
            
            // 绑定表单事件
            this.bindFormEvents('edit', linkId);
        } catch (error) {
            console.error('[CustomLinksManager] 显示编辑模态框时出错:', error);
            this.showError('无法打开编辑链接窗口');
        }
    }
    
    /**
     * 删除链接
     */
    async deleteLink(linkId) {
        if (!confirm('确定要删除这个链接吗？')) {
            return;
        }
        
        try {
            const token = this.getAuthToken();
            if (!token) {
                this.showError('请先登录');
                return;
            }
            
            const response = await fetch(`/api/custom_links.php/${linkId}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showSuccess('链接删除成功');
                this.loadLinks(); // 重新加载列表
            } else {
                this.showError(result.message || '删除失败');
            }
        } catch (error) {
            console.error('删除链接异常:', error);
            this.showError('网络错误，请稍后重试');
        }
    }
    
    /**
     * 创建模态框
     */
    createModal(title, content) {
        const modal = document.createElement('div');
        modal.className = 'link-modal';
        modal.innerHTML = `
            <div class="link-modal-content">
                <div class="link-modal-header">
                    <h3 class="link-modal-title">${title}</h3>
                    <button class="close-modal" onclick="window.customLinksManagerInstance.closeModal()">&times;</button>
                </div>
                ${content}
            </div>
        `;
        
        // 点击背景关闭
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                this.closeModal();
            }
        });
        
        return modal;
    }
    
    /**
     * 获取表单HTML
     */
    getFormHTML(link = null) {
        const isEdit = !!link;
        
        return `
            <form class="link-form" id="linkForm">
                <div class="form-group">
                    <label class="form-label">链接标题 *</label>
                    <input type="text" class="form-input" id="linkTitle" 
                           value="${isEdit ? this.escapeHtml(link.title) : ''}" 
                           placeholder="请输入链接标题" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">链接地址 *</label>
                    <input type="url" class="form-input" id="linkUrl" 
                           value="${isEdit ? this.escapeHtml(link.url) : ''}" 
                           placeholder="https://example.com" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">重要程度</label>
                    <div class="priority-options">
                        ${[0,1,2,3,4,5].map(priority => `
                            <label class="priority-option ${isEdit && link.priority === priority ? 'selected' : (!isEdit && priority === 1 ? 'selected' : '')}">
                                <input type="radio" name="priority" value="${priority}" 
                                       ${isEdit && link.priority === priority ? 'checked' : (!isEdit && priority === 1 ? 'checked' : '')}>
                                <span class="priority-badge priority-${priority}"></span>
                                <span class="priority-label">${this.getPriorityText(priority)}</span>
                            </label>
                        `).join('')}
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">排序权重</label>
                    <select class="form-input" id="linkSortOrder">
                        <option value="0" ${isEdit && link.sort_order === 0 ? 'selected' : (!isEdit ? 'selected' : '')}>0 - 最高优先级</option>
                        <option value="1" ${isEdit && link.sort_order === 1 ? 'selected' : ''}>1 - 高优先级</option>
                        <option value="2" ${isEdit && link.sort_order === 2 ? 'selected' : ''}>2 - 中高优先级</option>
                        <option value="3" ${isEdit && link.sort_order === 3 ? 'selected' : ''}>3 - 中等优先级</option>
                        <option value="4" ${isEdit && link.sort_order === 4 ? 'selected' : ''}>4 - 低优先级</option>
                        <option value="5" ${isEdit && link.sort_order === 5 ? 'selected' : ''}>5 - 最低优先级</option>
                    </select>
                    <small class="form-hint">数字越小排序越靠前，0为最高优先级</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">描述</label>
                    <textarea class="form-textarea" id="linkDescription" 
                              placeholder="请输入链接描述（可选）">${isEdit ? this.escapeHtml(link.description || '') : ''}</textarea>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="window.customLinksManagerInstance.closeModal()">
                        取消
                    </button>
                    <button type="submit" class="btn-primary" id="submitBtn">
                        ${isEdit ? '更新' : '添加'}
                    </button>
                </div>
            </form>
        `;
    }
    
    /**
     * 绑定表单事件
     */
    bindFormEvents(mode, linkId = null) {
        try {
            const form = document.getElementById('linkForm');
            const priorityOptions = document.querySelectorAll('.priority-option');
            
            // 优先级选择
            priorityOptions.forEach(option => {
                option.addEventListener('click', () => {
                    priorityOptions.forEach(opt => opt.classList.remove('selected'));
                    option.classList.add('selected');
                    option.querySelector('input[type="radio"]').checked = true;
                });
            });
            
            // 表单提交
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                try {
                    const priority = parseInt(document.querySelector('input[name="priority"]:checked').value);
                    const formData = {
                        wallpaper_id: priority === 0 ? 0 : this.wallpaperId, // 通用链接使用wallpaper_id=0
                        title: document.getElementById('linkTitle').value.trim(),
                        url: document.getElementById('linkUrl').value.trim(),
                        priority: priority,
                        description: document.getElementById('linkDescription').value.trim(),
                        sort_order: parseInt(document.getElementById('linkSortOrder').value)
                    };
                    
                    // 验证
                    if (!formData.title || !formData.url) {
                        this.showError('请填写必填字段');
                        return;
                    }
                    
                    // 提交
                    if (mode === 'add') {
                        await this.addLink(formData);
                    } else {
                        await this.updateLink(linkId, formData);
                    }
                } catch (error) {
                    console.error('[CustomLinksManager] 表单提交时出错:', error);
                    this.showError('提交表单时出现错误');
                }
            });
        } catch (error) {
            console.error('[CustomLinksManager] 绑定表单事件时出错:', error);
            this.showError('表单初始化失败');
        }
    }
    
    /**
     * 添加链接
     */
    async addLink(data) {
        try {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="loading-spinner"></span> 添加中...';
            
            const token = this.getAuthToken();
            if (!token) {
                this.showError('请先登录');
                return;
            }
            
            const response = await fetch('/api/custom_links.php', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showSuccess('链接添加成功');
                this.closeModal();
                this.loadLinks(); // 重新加载列表
            } else {
                this.showError(result.message || '添加失败');
            }
        } catch (error) {
            console.error('添加链接异常:', error);
            this.showError('网络错误，请稍后重试');
        } finally {
            const submitBtn = document.getElementById('submitBtn');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '添加';
            }
        }
    }
    
    /**
     * 更新链接
     */
    async updateLink(linkId, data) {
        try {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="loading-spinner"></span> 更新中...';
            
            const token = this.getAuthToken();
            if (!token) {
                this.showError('请先登录');
                return;
            }
            
            const response = await fetch(`/api/custom_links.php/${linkId}`, {
                method: 'PUT',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showSuccess('链接更新成功');
                this.closeModal();
                this.loadLinks(); // 重新加载列表
            } else {
                this.showError(result.message || '更新失败');
            }
        } catch (error) {
            console.error('更新链接异常:', error);
            this.showError('网络错误，请稍后重试');
        } finally {
            const submitBtn = document.getElementById('submitBtn');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '更新';
            }
        }
    }
    
    /**
     * 关闭模态框
     */
    closeModal() {
        try {
            if (this.currentModal) {
                document.body.removeChild(this.currentModal);
                this.currentModal = null;
            }
        } catch (error) {
            console.error('[CustomLinksManager] 关闭模态框时出错:', error);
            // 尝试清理状态
            this.currentModal = null;
        }
    }
    
    /**
     * 获取优先级文本
     */
    getPriorityText(priority) {
        const texts = {
            0: '通用',
            1: '首要',
            2: '重要',
            3: '一般',
            4: '次要',
            5: '边缘'
        };
        return texts[priority] || '一般';
    }
    
    /**
     * HTML转义
     */
    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    /**
     * 显示成功消息
     */
    showSuccess(message) {
        // 这里可以集成现有的通知系统
        console.log('成功:', message);
        alert(message); // 临时使用alert，后续可以替换为更好的通知组件
    }
    
    /**
     * 显示错误消息
     */
    showError(message) {
        // 这里可以集成现有的通知系统
        console.error('错误:', message);
        alert(message); // 临时使用alert，后续可以替换为更好的通知组件
    }
}

// 导出模块，避免全局污染
window.CustomLinksManager = CustomLinksManager;

// 模块说明：
// 此模块已重构为完全独立的模块，不会自动初始化
// 使用方式：在需要时调用 CustomLinksManager.init() 进行初始化
// 这样可以避免与其他模块的初始化冲突
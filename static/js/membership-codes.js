/**
 * 会员码管理模块
 * 用于管理员生成和管理会员码
 */
class MembershipCodesManager {
    constructor() {
        this.modal = null;
        this.currentPage = 1;
        this.pageSize = 10;
        this.currentFilter = 'all';
        this.currentTypeFilter = 'all';
        this.allCodes = [];
        this.selectedCodes = new Set();
        this.init();
    }

    init() {
        this.modal = document.getElementById('membership-codes-modal');
        this.bindEvents();
    }

    bindEvents() {
        // 关闭模态框
        const closeBtn = document.getElementById('close-membership-codes-modal');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.closeModal());
        }

        // 生成会员码表单提交
        const generateForm = document.getElementById('generate-codes-form');
        if (generateForm) {
            generateForm.addEventListener('submit', (e) => this.handleGenerateCodes(e));
        }

        // 复制会员码
        const copyBtn = document.getElementById('copy-codes');
        if (copyBtn) {
            copyBtn.addEventListener('click', () => this.copyGeneratedCodes());
        }

        // 点击模态框外部关闭
        if (this.modal) {
            this.modal.addEventListener('click', (e) => {
                if (e.target === this.modal) {
                    this.closeModal();
                }
            });
        }
        
        // 会员类型筛选功能
        const typeFilterSelect = document.getElementById('type-filter');
        if (typeFilterSelect) {
            typeFilterSelect.addEventListener('change', (e) => {
                this.currentTypeFilter = e.target.value;
                this.currentPage = 1;
                this.selectedCodes.clear();
                this.updateSelectAllCheckbox();
                this.updateBatchDeleteButton();
                this.renderFilteredCodes();
            });
        }
        
        // 状态筛选功能
        const filterSelect = document.getElementById('codes-filter');
        if (filterSelect) {
            filterSelect.addEventListener('change', (e) => {
                this.currentFilter = e.target.value;
                this.currentPage = 1;
                this.selectedCodes.clear();
                this.updateSelectAllCheckbox();
                this.updateBatchDeleteButton();
                this.renderFilteredCodes();
            });
        }
        
        // 全选功能
        const selectAllCheckbox = document.getElementById('select-all-codes');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', (e) => {
                this.handleSelectAll(e.target.checked);
            });
        }
        
        // 批量删除功能
        const batchDeleteBtn = document.getElementById('batch-delete-btn');
        if (batchDeleteBtn) {
            batchDeleteBtn.addEventListener('click', () => {
                this.handleBatchDelete();
            });
        }
        
        // 分页功能
        const prevBtn = document.getElementById('prev-page');
        const nextBtn = document.getElementById('next-page');
        if (prevBtn) {
            prevBtn.addEventListener('click', () => this.prevPage());
        }
        if (nextBtn) {
            nextBtn.addEventListener('click', () => this.nextPage());
        }
    }

    async showModal() {
        if (!this.modal) return;
        
        // 检查管理员权限
        if (!await this.checkAdminPermission()) {
            alert('您没有管理员权限');
            return;
        }

        this.modal.classList.remove('hidden');
        
        // 设置默认选择1元会员
        const codeTypeSelect = document.getElementById('code-type');
        if (codeTypeSelect) {
            codeTypeSelect.value = 'monthly';
        }
        
        await this.loadStats();
        await this.loadRecentCodes();
    }

    closeModal() {
        if (this.modal) {
            this.modal.classList.add('hidden');
            this.hideGeneratedResult();
        }
    }

    async checkAdminPermission() {
        try {
            const userData = JSON.parse(localStorage.getItem('user') || '{}');
            if (!userData.id) {
                return false;
            }
            
            const response = await fetch('/api/auth_unified.php?action=getUserInfo', {
                headers: {
                    'Authorization': `Bearer ${userData.id}`
                }
            });
            const result = await response.json();
            return result.code === 200 && result.data && result.data.is_admin === 1;
        } catch (error) {
            console.error('检查管理员权限失败:', error);
            return false;
        }
    }

    async handleGenerateCodes(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const membershipType = formData.get('membership_type') || document.getElementById('code-type').value;
        const count = parseInt(formData.get('count') || document.getElementById('code-count').value);
        
        if (!membershipType) {
            alert('请选择会员类型');
            return;
        }
        
        if (count < 1 || count > 100) {
            alert('生成数量必须在1-100之间');
            return;
        }

        try {
            const submitBtn = e.target.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin mr-2"></i>生成中...';

            const userData = JSON.parse(localStorage.getItem('user') || '{}');
            if (!userData.id) {
                throw new Error('请先登录');
            }
            
            const response = await fetch('/api/vip/generate_membership_codes.php', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Authorization': `Bearer ${userData.id}`
                },
                body: `membership_type=${encodeURIComponent(membershipType)}&count=${count}`
            });

            const result = await response.json();

            if (result.success && result.codes) {
                this.showGeneratedResult(result.codes || [], membershipType, count);
                await this.loadStats();
                await this.loadRecentCodes();
                
                // 重置表单
                e.target.reset();
                document.getElementById('code-count').value = '1';
            } else {
                throw new Error(result.message || '生成失败');
            }

        } catch (error) {
            console.error('生成会员码失败:', error);
            alert('生成会员码失败: ' + error.message);
        } finally {
            const submitBtn = e.target.querySelector('button[type="submit"]');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa fa-plus mr-2"></i>生成会员码';
        }
    }

    showGeneratedResult(codes, membershipType, count) {
        const resultDiv = document.getElementById('generated-codes-result');
        const codesList = document.getElementById('generated-codes-list');
        
        if (resultDiv && codesList) {
            const typeText = membershipType === 'monthly' ? '1元会员' : '永久会员';
            
            // 确保codes是数组，修复[object Object]问题
            let codesArray = [];
            if (Array.isArray(codes)) {
                // 提取会员码字符串
                codesArray = codes.map(item => {
                    if (typeof item === 'object' && item.code) {
                        return item.code;
                    } else if (typeof item === 'string') {
                        return item;
                    } else {
                        return String(item);
                    }
                });
            } else if (typeof codes === 'string') {
                codesArray = [codes];
            } else if (codes && typeof codes === 'object') {
                // 如果是对象，尝试提取codes属性或值
                if (codes.codes && Array.isArray(codes.codes)) {
                    codesArray = codes.codes.map(item => {
                        if (typeof item === 'object' && item.code) {
                            return item.code;
                        } else if (typeof item === 'string') {
                            return item;
                        } else {
                            return String(item);
                        }
                    });
                } else {
                    // 尝试获取对象的所有值，过滤出字符串类型的会员码
                    const values = Object.values(codes);
                    codesArray = values.filter(val => typeof val === 'string' && val.length === 12);
                }
            }
            
            // 如果仍然没有获取到有效的会员码数组，尝试从响应中直接提取
            if (codesArray.length === 0 && codes) {
                console.log('原始codes数据:', codes);
                // 可能需要根据实际API响应格式调整
                if (typeof codes === 'object' && codes.data && Array.isArray(codes.data)) {
                    codesArray = codes.data.map(item => {
                        if (typeof item === 'object' && item.code) {
                            return item.code;
                        } else if (typeof item === 'string') {
                            return item;
                        } else {
                            return String(item);
                        }
                    });
                }
            }
            
            codesList.textContent = codesArray.join('\n');
            
            // 更新成功消息
            const successTitle = resultDiv.querySelector('h5');
            if (successTitle) {
                successTitle.textContent = `成功生成 ${count} 个 ${typeText} 会员码`;
            }
            
            resultDiv.classList.remove('hidden');
            this.generatedCodes = codesArray;
        }
    }

    hideGeneratedResult() {
        const resultDiv = document.getElementById('generated-codes-result');
        if (resultDiv) {
            resultDiv.classList.add('hidden');
        }
        this.generatedCodes = null;
    }

    copyGeneratedCodes() {
        if (this.generatedCodes && this.generatedCodes.length > 0) {
            // 确保是字符串数组
            const codeStrings = this.generatedCodes.map(code => {
                if (typeof code === 'object' && code.code) {
                    return code.code;
                } else if (typeof code === 'string') {
                    return code;
                } else {
                    return String(code);
                }
            });
            
            const text = codeStrings.join('\n');
            navigator.clipboard.writeText(text).then(() => {
                const copyBtn = document.getElementById('copy-codes');
                const originalText = copyBtn.innerHTML;
                copyBtn.innerHTML = '<i class="fa fa-check mr-1"></i>已复制';
                copyBtn.classList.remove('bg-green-600', 'hover:bg-green-700');
                copyBtn.classList.add('bg-green-700');
                
                setTimeout(() => {
                    copyBtn.innerHTML = originalText;
                    copyBtn.classList.remove('bg-green-700');
                    copyBtn.classList.add('bg-green-600', 'hover:bg-green-700');
                }, 2000);
            }).catch(err => {
                console.error('复制失败:', err);
                alert('复制失败，请手动复制');
            });
        }
    }

    async loadStats() {
        try {
            const userData = JSON.parse(localStorage.getItem('user') || '{}');
            if (!userData.id) {
                console.error('用户未登录');
                return;
            }
            
            const response = await fetch('/api/vip/get_membership_stats.php', {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${userData.id}`
                }
            });
            const result = await response.json();
            
            if (result.success) {
                this.renderStats(result.data);
            }
        } catch (error) {
            console.error('加载统计数据失败:', error);
        }
    }

    renderStats(stats) {
        const statsContainer = document.getElementById('codes-stats');
        if (!statsContainer) return;

        const statsMap = {
            monthly_active: { label: '1元会员可用', color: 'bg-blue-100 text-blue-800' },
            monthly_used: { label: '1元会员已用', color: 'bg-gray-100 text-gray-800' },
            permanent_active: { label: '永久会员可用', color: 'bg-purple-100 text-purple-800' },
            permanent_used: { label: '永久会员已用', color: 'bg-gray-100 text-gray-800' }
        };

        let html = '';
        Object.entries(statsMap).forEach(([key, config]) => {
            const count = stats[key] || 0;
            html += `
                <div class="${config.color} p-3 rounded-lg text-center">
                    <div class="text-2xl font-bold">${count}</div>
                    <div class="text-sm">${config.label}</div>
                </div>
            `;
        });

        statsContainer.innerHTML = html;
    }

    async loadRecentCodes() {
        try {
            const userData = JSON.parse(localStorage.getItem('user') || '{}');
            if (!userData.id) {
                console.error('用户未登录');
                return;
            }
            
            const response = await fetch('/api/vip/get_recent_codes.php?limit=100', {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${userData.id}`
                }
            });
            const result = await response.json();
            
            if (result.success) {
                this.allCodes = result.data.codes || [];
                this.renderFilteredCodes();
            }
        } catch (error) {
            console.error('加载最近会员码失败:', error);
        }
    }

    getFilteredCodes() {
        let filteredCodes = this.allCodes;
        
        // 按会员类型筛选
        if (this.currentTypeFilter !== 'all') {
            filteredCodes = filteredCodes.filter(code => code.membership_type === this.currentTypeFilter);
        }
        
        // 按状态筛选
        if (this.currentFilter !== 'all') {
            filteredCodes = filteredCodes.filter(code => code.status === this.currentFilter);
        }
        
        return filteredCodes;
    }
    
    renderFilteredCodes() {
        const filteredCodes = this.getFilteredCodes();
        const totalPages = Math.ceil(filteredCodes.length / this.pageSize);
        
        // 确保当前页码有效
        if (this.currentPage > totalPages && totalPages > 0) {
            this.currentPage = totalPages;
        }
        
        const startIndex = (this.currentPage - 1) * this.pageSize;
        const endIndex = startIndex + this.pageSize;
        const currentPageCodes = filteredCodes.slice(startIndex, endIndex);
        
        this.renderRecentCodes(currentPageCodes);
        this.updatePagination(this.currentPage, totalPages, filteredCodes.length);
        this.updateSelectAllCheckbox();
        this.updateBatchDeleteButton();
    }
    
    renderRecentCodes(codes) {
        const tbody = document.getElementById('recent-codes-list');
        if (!tbody) return;

        if (codes.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">暂无会员码</td></tr>';
            return;
        }

        const statusMap = {
            active: '<span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">可用</span>',
            used: '<span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">已使用</span>',
            expired: '<span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">已过期</span>',
            disabled: '<span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">已禁用</span>'
        };

        const typeMap = {
            monthly: '1元会员',
            permanent: '永久会员'
        };

        const html = codes.map(code => `
            <tr class="border-t">
                <td class="px-4 py-2">
                    <input type="checkbox" class="code-checkbox" data-code-id="${code.id}" 
                           ${this.selectedCodes.has(code.id) ? 'checked' : ''}>
                </td>
                <td class="px-4 py-2 font-mono text-sm">${code.code}</td>
                <td class="px-4 py-2">${typeMap[code.membership_type] || code.membership_type}</td>
                <td class="px-4 py-2">${statusMap[code.status] || code.status}</td>
                <td class="px-4 py-2 text-sm text-gray-600">${this.formatDate(code.created_at)}</td>
                <td class="px-4 py-2 text-sm text-gray-600">${this.formatDate(code.expires_at)}</td>
            </tr>
        `).join('');

        tbody.innerHTML = html;
        
        // 绑定复选框事件
        this.bindCheckboxEvents();
    }
    
    updatePagination(currentPage, totalPages, totalCount) {
        const paginationInfo = document.getElementById('pagination-info');
        const prevBtn = document.getElementById('prev-page');
        const nextBtn = document.getElementById('next-page');
        
        if (paginationInfo) {
            const startItem = totalCount === 0 ? 0 : (currentPage - 1) * this.pageSize + 1;
            const endItem = Math.min(currentPage * this.pageSize, totalCount);
            paginationInfo.textContent = `显示 ${startItem}-${endItem} 条，共 ${totalCount} 条 (第 ${currentPage}/${totalPages} 页)`;
        }
        
        if (prevBtn) {
            prevBtn.disabled = currentPage <= 1;
            prevBtn.classList.toggle('opacity-50', currentPage <= 1);
        }
        
        if (nextBtn) {
            nextBtn.disabled = currentPage >= totalPages;
            nextBtn.classList.toggle('opacity-50', currentPage >= totalPages);
        }
    }
    
    prevPage() {
        if (this.currentPage > 1) {
            this.currentPage--;
            this.renderFilteredCodes();
        }
    }
    
    nextPage() {
        const filteredCodes = this.getFilteredCodes();
        const totalPages = Math.ceil(filteredCodes.length / this.pageSize);
        if (this.currentPage < totalPages) {
            this.currentPage++;
            this.renderFilteredCodes();
        }
    }
    
    // 绑定复选框事件
    bindCheckboxEvents() {
        const checkboxes = document.querySelectorAll('.code-checkbox');
        console.log('绑定复选框事件，找到复选框数量:', checkboxes.length);
        
        checkboxes.forEach(checkbox => {
            // 移除所有事件监听器，通过克隆节点的方式
            const newCheckbox = checkbox.cloneNode(true);
            checkbox.parentNode.replaceChild(newCheckbox, checkbox);
            
            // 确保复选框状态与selectedCodes集合一致
            const codeId = parseInt(newCheckbox.dataset.codeId);
            newCheckbox.checked = this.selectedCodes.has(codeId);
            
            // 添加新的事件监听器
            newCheckbox.addEventListener('change', (e) => {
                const codeId = parseInt(e.target.dataset.codeId);
                console.log('复选框状态变化:', { codeId, checked: e.target.checked });
                
                if (e.target.checked) {
                    this.selectedCodes.add(codeId);
                } else {
                    this.selectedCodes.delete(codeId);
                }
                
                console.log('当前选中的会员码:', Array.from(this.selectedCodes));
                this.updateSelectAllCheckbox();
                this.updateBatchDeleteButton();
            });
        });
    }
    
    // 处理全选
    handleSelectAll(checked) {
        const currentPageCodes = this.getCurrentPageCodes();
        
        console.log('全选操作:', { checked, currentPageCodesCount: currentPageCodes.length });
        
        // 只操作当前页面显示的会员码
        currentPageCodes.forEach(code => {
            if (checked) {
                this.selectedCodes.add(code.id);
            } else {
                this.selectedCodes.delete(code.id);
            }
        });
        
        console.log('全选后的选中状态:', Array.from(this.selectedCodes));
        
        // 更新页面上的复选框
        const checkboxes = document.querySelectorAll('.code-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = checked;
        });
        
        this.updateBatchDeleteButton();
        this.updateSelectAllCheckbox();
    }
    
    // 更新全选复选框状态
    updateSelectAllCheckbox() {
        const selectAllCheckbox = document.getElementById('select-all-codes');
        if (!selectAllCheckbox) return;
        
        const currentPageCodes = this.getCurrentPageCodes();
        if (currentPageCodes.length === 0) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
            return;
        }
        
        const selectedInCurrentPage = currentPageCodes.filter(code => this.selectedCodes.has(code.id));
        
        if (selectedInCurrentPage.length === 0) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
        } else if (selectedInCurrentPage.length === currentPageCodes.length) {
            selectAllCheckbox.checked = true;
            selectAllCheckbox.indeterminate = false;
        } else {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = true;
        }
    }
    
    // 获取当前页面的会员码
    getCurrentPageCodes() {
        const filteredCodes = this.getFilteredCodes();
        const startIndex = (this.currentPage - 1) * this.pageSize;
        const endIndex = startIndex + this.pageSize;
        return filteredCodes.slice(startIndex, endIndex);
    }
    
    // 更新批量删除按钮状态
    updateBatchDeleteButton() {
        const batchDeleteBtn = document.getElementById('batch-delete-btn');
        if (!batchDeleteBtn) return;
        
        // 获取所有选中的会员码数量
        const totalSelected = this.selectedCodes.size;
        
        // 确保按钮状态正确更新
        if (totalSelected === 0) {
            batchDeleteBtn.disabled = true;
            batchDeleteBtn.classList.add('opacity-50', 'cursor-not-allowed');
            batchDeleteBtn.classList.remove('hover:bg-red-600');
            batchDeleteBtn.textContent = '删除选中';
        } else {
            batchDeleteBtn.disabled = false;
            batchDeleteBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            batchDeleteBtn.classList.add('hover:bg-red-600');
            batchDeleteBtn.textContent = `删除选中 (${totalSelected})`;
        }
        
        // 计算当前筛选条件下被选中的会员码数量
        const filteredCodes = this.getFilteredCodes();
        const selectedInFiltered = filteredCodes.filter(code => this.selectedCodes.has(code.id)).length;
        
        console.log('更新删除按钮状态:', {
            totalSelected: totalSelected,
            filteredSelected: selectedInFiltered,
            disabled: batchDeleteBtn.disabled
        });
    }
    
    // 处理批量删除
    async handleBatchDelete() {
        if (this.selectedCodes.size === 0) {
            alert('请至少选择一个会员码进行删除');
            return;
        }
        
        // 获取所有选中的会员码ID
        const selectedCodesArray = Array.from(this.selectedCodes);
        
        // 从所有会员码中查找选中的ID对应的信息
        const selectedCodesInfo = this.allCodes.filter(code => selectedCodesArray.includes(code.id));
        
        // 检查是否所有选中的ID都能在allCodes中找到对应信息
        if (selectedCodesInfo.length < selectedCodesArray.length) {
            console.warn(`选中的会员码数量(${selectedCodesArray.length})与找到的信息数量(${selectedCodesInfo.length})不匹配`);
            // 继续执行，不阻止删除操作
        }
        
        // 获取当前筛选条件下选中的会员码数量
        const filteredCodes = this.getFilteredCodes();
        const filteredSelectedCount = filteredCodes.filter(code => this.selectedCodes.has(code.id)).length;
        
        console.log(`当前筛选条件下选中: ${filteredSelectedCount}, 总共选中: ${selectedCodesArray.length}`);
        
        const confirmMessage = `确定要删除选中的 ${selectedCodesArray.length} 个会员码吗？`;
        if (confirm(confirmMessage)) {
            await this.sendDeleteRequest(selectedCodesArray);
        }
    }
    
    // 发送删除请求
    async sendDeleteRequest(selectedIds) {
        if (!selectedIds || selectedIds.length === 0) {
            console.error('没有要删除的会员码ID');
            alert('删除失败: 未找到要删除的会员码');
            return;
        }
        
        try {
            console.log('发送删除请求:', { code_ids: selectedIds });
            
            const userData = JSON.parse(localStorage.getItem('user') || '{}');
            if (!userData.id) {
                throw new Error('请先登录');
            }
            
            const response = await fetch('/api/vip/delete_membership_codes.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${userData.id}`
                },
                body: JSON.stringify({
                    code_ids: selectedIds
                })
            });
            
            console.log('API响应状态:', response.status, response.statusText);
            
            if (!response.ok) {
                const errorText = await response.text();
                console.error('API错误响应:', errorText);
                alert(`删除失败：HTTP ${response.status} - ${response.statusText}`);
                return;
            }
            
            const result = await response.json();
            console.log('API响应结果:', result);
            
            if (result.success) {
                alert(`成功删除 ${result.deleted_count || selectedIds.length} 个会员码`);
                // 从选中集合中移除已删除的项
                selectedIds.forEach(id => this.selectedCodes.delete(id));
                this.updateSelectAllCheckbox();
                this.updateBatchDeleteButton();
                this.loadRecentCodes();
                this.loadStats();
            } else {
                console.error('删除失败:', result.message);
                alert('删除失败：' + (result.message || '未知错误'));
            }
        } catch (error) {
            console.error('删除会员码失败:', error);
            alert('删除失败，请稍后重试：' + error.message);
        }
    }
    
    formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleString('zh-CN', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
}

// 全局函数，供HTML调用
function openMembershipCodesModal() {
    if (window.membershipCodesManager) {
        window.membershipCodesManager.showModal();
    }
}

// 初始化
document.addEventListener('DOMContentLoaded', function() {
    window.membershipCodesManager = new MembershipCodesManager();
    
    // 检查用户是否为管理员，显示/隐藏管理员功能
    checkAndShowAdminFeatures();
});

async function checkAndShowAdminFeatures() {
    try {
        const userData = JSON.parse(localStorage.getItem('user') || '{}');
        if (!userData.id) {
            return;
        }
        
        const response = await fetch('/api/auth_unified.php?action=getUserInfo', {
            headers: {
                'Authorization': `Bearer ${userData.id}`
            }
        });
        const result = await response.json();
        
        if (result.code === 200 && result.data && result.data.is_admin === 1) {
            // 显示管理员专用功能
            const adminMembershipCodes = document.getElementById('admin-membership-codes');
            if (adminMembershipCodes) {
                adminMembershipCodes.classList.remove('hidden');
            }
        }
    } catch (error) {
        console.error('检查管理员权限失败:', error);
    }
}
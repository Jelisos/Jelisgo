/**
 * 会员升级功能模块
 * @author Claude
 * @date 2025-01-27
 */

// 会员升级相关功能
class MembershipManager {
    constructor() {
        this.modal = document.getElementById('membershipModal');
        this.codeInput = document.getElementById('membershipCode');
        this.upgradeBtn = document.getElementById('upgradeBtn');
        this.getCodeBtn = document.getElementById('getCodeBtn');
        this.closeBtn = document.getElementById('closeMembershipModal');
        this.membershipType = null;
        
        this.initEventListeners();
    }
    
    initEventListeners() {
        // 升级按钮点击
        if (this.upgradeBtn) {
            this.upgradeBtn.addEventListener('click', () => this.handleUpgrade());
        }
        
        // 获取会员码按钮点击
        if (this.getCodeBtn) {
            this.getCodeBtn.addEventListener('click', () => this.handleGetCode());
        }
        
        // 关闭模态框
        if (this.closeBtn) {
            this.closeBtn.addEventListener('click', () => this.closeModal());
        }
        
        // 点击背景关闭
        if (this.modal) {
            this.modal.addEventListener('click', (e) => {
                if (e.target === this.modal) this.closeModal();
            });
        }
        
        // 回车键提交
        if (this.codeInput) {
            this.codeInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') this.handleUpgrade();
            });
        }
    }
    
    showModal(membershipType) {
        this.membershipType = membershipType;
        if (this.modal) {
            this.modal.classList.remove('hidden');
        }
        if (this.codeInput) {
            this.codeInput.focus();
        }
    }
    
    closeModal() {
        if (this.modal) {
            this.modal.classList.add('hidden');
        }
        if (this.codeInput) {
            this.codeInput.value = '';
        }
    }
    
    async handleUpgrade() {
        const code = this.codeInput ? this.codeInput.value.trim().toUpperCase() : '';
        
        if (!code) {
            alert('请输入会员码');
            return;
        }
        
        if (code.length !== 12) {
            alert('会员码必须是12位');
            return;
        }
        
        try {
            if (this.upgradeBtn) {
                this.upgradeBtn.disabled = true;
                this.upgradeBtn.textContent = '升级中...';
            }
            
            const userData = JSON.parse(localStorage.getItem('user') || '{}');
            if (!userData.id) {
                throw new Error('请先登录');
            }

            const response = await fetch('/api/vip/redeem_membership_code.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Cache-Control': 'no-cache, no-store, must-revalidate',
                    'Authorization': `Bearer ${userData.id}`
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    code: code,
                    membership_type: this.membershipType || 'monthly',
                    user_id: userData.id
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert('升级成功！');
                this.closeModal();
                location.reload(); // 刷新页面显示新的会员状态
            } else {
                alert('升级失败：' + (result.message || '未知错误'));
            }
        } catch (error) {
            console.error('会员升级错误:', error);
            alert('网络错误，请稍后重试');
        } finally {
            if (this.upgradeBtn) {
                this.upgradeBtn.disabled = false;
                this.upgradeBtn.textContent = '升级';
            }
        }
    }
    
    handleGetCode() {
        // 显示提示信息
        alert('前往小红书商店下一单可获取会员码');
        
        // 跳转到小红书商品页面
        window.open('https://www.xiaohongshu.com/goods-detail/68364be97bc1a50001452f52?xsec_token=XBF5tMSotvGdhHmhZRberyb6bCK0r2QCfHGV4XtPbuqMo%3D&xsec_source=app_share&instation_link=xhsdiscover%3A%2F%2Fgoods_detail%2F68364be97bc1a50001452f52%3Ftrade_ext%3DeyJjaGFubmVsSW5mbyI6bnVsbCwiZHNUb2tlbkluZm8iOm51bGwsInNoYXJlTGluayI6Imh0dHBzOi8vd3d3LnhpYW9ob25nc2h1LmNvbS9nb29kcy1kZXRhaWwvNjgzNjRiZTk3YmMxYTUwMDAxNDUyZjUyP2FwcHVpZD02MTJiOWRkYzAwMDAwMDAwMDEwMDkzYzciLCJsaXZlSW5mbyI6bnVsbCwic2hvcEluZm8iOm51bGwsImdvb2RzTm90ZUluZm8iOm51bGwsImNoYXRJbmZvIjpudWxsLCJzZWFyY2hJbmZvIjpudWxsLCJwcmVmZXIiOm51bGx9%26rate_limit_meta%3DitemId%253D68364be97bc1a50001452f51%26rn%3Dtrue&xhsshare=CopyLink&appuid=612b9ddc00000000010093c7&apptime=1750649922&share_id=ee83ec77afd04f14a5d8665efac6ad0a&share_channel=copy_link', '_blank');
    }
}

// 下载权限检查
class DownloadManager {
    static async checkPermission(downloadType) {
        try {
            const response = await fetch('/api/vip/check_download_permission.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ download_type: downloadType })
            });
            
            const result = await response.json();
            return result;
        } catch (error) {
            console.error('检查下载权限错误:', error);
            return { allowed: false, reason: '网络错误' };
        }
    }
    
    static async handleDownload(downloadType, downloadFunction) {
        const permission = await this.checkPermission(downloadType);
        
        if (!permission.allowed) {
            if (permission.reason === '需要会员权限') {
                alert('此功能需要会员权限，请先升级会员');
                // 可以在这里触发会员升级模态框
                if (window.membershipManager) {
                    window.membershipManager.showModal('monthly');
                }
            } else {
                alert('下载失败：' + permission.reason);
            }
            return;
        }
        
        // 执行下载
        try {
            await downloadFunction();
            
            // 记录下载
            await fetch('/api/vip/record_download.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ download_type: downloadType })
            });
        } catch (error) {
            console.error('下载错误:', error);
            alert('下载失败，请稍后重试');
        }
    }
}

// 初始化
document.addEventListener('DOMContentLoaded', function() {
    console.log('[Membership] 初始化会员升级功能');
    
    // 创建全局会员管理器实例
    window.membershipManager = new MembershipManager();
    
    // 绑定会员升级按钮
    document.querySelectorAll('.upgrade-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const membershipType = e.target.dataset.membershipType;
            if (window.membershipManager) {
                window.membershipManager.showModal(membershipType);
            }
        });
    });
    
    // 绑定受限制的下载按钮（如果存在）
    const downloadHDAllButton = document.getElementById('downloadHDAllButton');
    if (downloadHDAllButton) {
        downloadHDAllButton.addEventListener('click', () => {
            DownloadManager.handleDownload('hd_combo', () => {
                // 原有的下载逻辑
                if (typeof downloadHDAll === 'function') {
                    downloadHDAll();
                }
            });
        });
    }
    
    const downloadAvatarButton = document.getElementById('downloadAvatarButton');
    if (downloadAvatarButton) {
        downloadAvatarButton.addEventListener('click', () => {
            DownloadManager.handleDownload('avatar', () => {
                // 原有的头像下载逻辑
                if (typeof downloadAvatar === 'function') {
                    downloadAvatar();
                }
            });
        });
    }
});

// 导出类供其他模块使用
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { MembershipManager, DownloadManager };
}
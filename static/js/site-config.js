/**
 * 站点配置管理模块
 * 从系统设置API获取配置信息并应用到页面
 */
class SiteConfig {
    constructor() {
        this.config = null;
        this.defaultConfig = {
            site_name: '壁纸喵 ° 不吃鱼',
            site_subtitle: '你的专属壁纸库',
            contact_email: 'admin@example.com',
            icp_number: ''
        };
    }

    /**
     * 初始化站点配置
     */
    async init() {
        try {
            await this.loadConfig();
            this.applyConfig();
        } catch (error) {
            console.warn('[SiteConfig] 加载配置失败，使用默认配置:', error);
            this.config = this.defaultConfig;
            this.applyConfig();
        }
    }

    /**
     * 从API加载配置
     */
    async loadConfig() {
        const response = await fetch('/api/system_settings.php', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const result = await response.json();
        if (result.success) {
            this.config = result.data.basic;
        } else {
            throw new Error(result.message || '获取配置失败');
        }
    }

    /**
     * 应用配置到页面元素
     */
    applyConfig() {
        if (!this.config) {
            console.warn('[SiteConfig] 配置为空，跳过应用');
            return;
        }

        // 更新页面标题
        this.updatePageTitle();
        
        // 更新导航栏品牌名称
        this.updateBrandName();
        
        // 更新页脚品牌名称
        this.updateFooterBrand();
        
        // 更新meta描述（现在由SEO设置管理）
        // this.updateMetaDescription();
        
        // 更新ICP备案号
        this.updateICPNumber();

        console.log('[SiteConfig] 配置已应用:', this.config);
    }

    /**
     * 更新页面标题
     * 注意：HTML title标签现在由PHP服务器端渲染，这里只更新动态内容
     */
    updatePageTitle() {
        // 构建新标题
        const newTitle = `${this.config.site_name} - ${this.config.site_subtitle}`;
        
        // 更新document.title（用于JavaScript动态页面切换）
        document.title = newTitle;
        
        // 更新meta标签（如果需要动态更新）
        const metaTitle = document.querySelector('meta[property="og:title"]');
        if (metaTitle) {
            metaTitle.setAttribute('content', newTitle);
        }
    }

    /**
     * 更新导航栏品牌名称
     */
    updateBrandName() {
        // 主导航栏品牌名称 - 优先使用ID选择器
        const navBrand = document.getElementById('nav-brand-name');
        if (navBrand && this.config.site_name) {
            navBrand.textContent = this.config.site_name;
        }
        
        // 兼容性处理：如果没有ID，则使用类选择器
        if (!navBrand) {
            const brandElements = document.querySelectorAll('nav .text-xl.font-bold.text-primary');
            brandElements.forEach(element => {
                if (element.textContent.includes('Jelisgo') || element.textContent.includes('Wallpaper')) {
                    element.textContent = this.config.site_name;
                }
            });
        }
    }

    /**
     * 更新页脚品牌名称
     */
    updateFooterBrand() {
        const footerBrand = document.getElementById('footer-brand-name');
        if (footerBrand && this.config.site_name) {
            footerBrand.textContent = this.config.site_name;
        }
    }

    /**
     * 更新meta描述
     * 注意：现在meta描述由SEO设置管理，此函数已停用
     */
    // updateMetaDescription() {
    //     let metaDesc = document.querySelector('meta[name="description"]');
    //     if (!metaDesc) {
    //         metaDesc = document.createElement('meta');
    //         metaDesc.setAttribute('name', 'description');
    //         document.head.appendChild(metaDesc);
    //     }
    //     metaDesc.setAttribute('content', this.config.site_description);
    //     
    //     // 更新OG描述
    //     let ogDesc = document.querySelector('meta[property="og:description"]');
    //     if (!ogDesc) {
    //         ogDesc = document.createElement('meta');
    //         ogDesc.setAttribute('property', 'og:description');
    //         document.head.appendChild(ogDesc);
    //     }
    //     ogDesc.setAttribute('content', this.config.site_description);
    // }

    /**
     * 更新ICP备案号
     */
    updateICPNumber() {
        if (!this.config.icp_number) {
            return;
        }

        // 查找页脚版权信息区域
        const copyrightElement = document.querySelector('footer .text-center.text-gray-500.text-sm p');
        if (copyrightElement) {
            const currentText = copyrightElement.textContent;
            const icpText = ` | ${this.config.icp_number}`;
            if (!currentText.includes(this.config.icp_number)) {
                copyrightElement.textContent = currentText + icpText;
            }
        }
    }

    /**
     * 获取当前配置
     */
    getConfig() {
        return this.config || this.defaultConfig;
    }

    /**
     * 获取特定配置项
     */
    get(key) {
        const config = this.getConfig();
        return config[key];
    }
}

// 创建全局实例
window.siteConfig = new SiteConfig();

// 页面加载完成后自动初始化
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.siteConfig.init();
    });
} else {
    // 如果页面已经加载完成，立即初始化
    window.siteConfig.init();
}
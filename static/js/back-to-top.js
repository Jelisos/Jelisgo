/**
 * 回到顶部功能模块
 * 文件: static/js/back-to-top.js
 * 描述: 提供页面回到顶部的功能，包括按钮显示/隐藏逻辑和平滑滚动
 * 依赖: 无
 * 作者: AI助手
 * 日期: 2025-01-27
 */

/**
 * 防抖函数 - 限制函数执行频率
 * @param {Function} func 要执行的函数
 * @param {number} wait 等待时间（毫秒）
 * @returns {Function} 防抖后的函数
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * 回到顶部功能类
 */
class BackToTop {
    /**
     * 构造函数
     * @param {Object} options 配置选项
     */
    constructor(options = {}) {
        // 配置选项
        this.options = {
            buttonId: 'back-to-top',
            threshold: 200,
            debounceDelay: 16,
            ...options
        };
        
        // 状态变量
        this.button = null;
        this.isVisible = false;
        this.isInitialized = false;
        
        // 初始化
        this.init();
    }
    
    /**
     * 初始化功能
     */
    init() {
        // 查找按钮元素
        this.button = document.getElementById(this.options.buttonId);
        
        if (!this.button) {
            console.warn(`[BackToTop] 未找到ID为 "${this.options.buttonId}" 的按钮元素`);
            return;
        }
        
        // 绑定事件
        this.bindEvents();
        
        // 检查初始状态
        this.checkInitialState();
        
        this.isInitialized = true;
    }
    
    /**
     * 绑定事件监听器
     */
    bindEvents() {
        // 滚动事件（使用防抖优化性能）
        this.handleScrollDebounced = debounce(() => {
            this.handleScroll();
        }, this.options.debounceDelay);
        
        window.addEventListener('scroll', this.handleScrollDebounced, { passive: true });
        
        // 点击事件
        this.button.addEventListener('click', (e) => {
            e.preventDefault();
            this.scrollToTop();
        });
    }
    
    /**
     * 处理滚动事件
     */
    handleScroll() {
        if (!this.isInitialized) return;
        
        const scrollY = window.scrollY || window.pageYOffset;
        const shouldShow = scrollY > this.options.threshold;
        
        if (shouldShow && !this.isVisible) {
            this.show();
        } else if (!shouldShow && this.isVisible) {
            this.hide();
        }
    }
    
    /**
     * 显示按钮
     */
    show() {
        if (!this.button || this.isVisible) return;
        
        this.button.classList.remove('opacity-0', 'pointer-events-none');
        this.button.classList.add('opacity-100');
        this.isVisible = true;
        
        // 设置aria属性以提高无障碍访问性
        this.button.setAttribute('aria-hidden', 'false');
    }
    
    /**
     * 隐藏按钮
     */
    hide() {
        if (!this.button || !this.isVisible) return;
        
        this.button.classList.add('opacity-0', 'pointer-events-none');
        this.button.classList.remove('opacity-100');
        this.isVisible = false;
        
        // 设置aria属性以提高无障碍访问性
        this.button.setAttribute('aria-hidden', 'true');
    }
    
    /**
     * 滚动到页面顶部
     */
    scrollToTop() {
        // 检查浏览器支持
        if ('scrollTo' in window) {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        } else {
            // 降级处理：不支持smooth滚动的浏览器
            window.scrollTo(0, 0);
        }
    }
    
    /**
     * 检查初始状态
     */
    checkInitialState() {
        // 延迟检查，确保页面完全加载
        setTimeout(() => {
            this.handleScroll();
        }, 100);
    }
    
    /**
     * 销毁实例，清理事件监听器
     */
    destroy() {
        if (this.handleScrollDebounced) {
            window.removeEventListener('scroll', this.handleScrollDebounced);
        }
        
        this.button = null;
        this.isInitialized = false;
        this.isVisible = false;
    }
    
    /**
     * 更新配置
     * @param {Object} newOptions 新的配置选项
     */
    updateOptions(newOptions) {
        this.options = { ...this.options, ...newOptions };
        this.handleScroll(); // 重新检查状态
    }
}

// 自动初始化（如果在浏览器环境中）
if (typeof window !== 'undefined') {
    // 等待DOM加载完成
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            window.backToTopInstance = new BackToTop();
        });
    } else {
        // DOM已经加载完成
        window.backToTopInstance = new BackToTop();
    }
}

// 导出类（用于模块化环境）
if (typeof module !== 'undefined' && module.exports) {
    module.exports = BackToTop;
}

// 全局暴露（用于直接引用）
if (typeof window !== 'undefined') {
    window.BackToTop = BackToTop;
}
/**
 * 智能图片预加载器
 * 文件: static/js/intelligent-preloader.js
 * 描述: 实现智能预加载、网络状态检测和图片优先级队列
 * 作者: AI Assistant
 * 创建时间: 2025-01-27
 */

/**
 * 滚动速度追踪器
 * 监听用户滚动行为，计算滚动速度并分类
 */
class ScrollSpeedTracker {
    constructor() {
        this.lastScrollTop = 0;
        this.lastScrollTime = Date.now();
        this.scrollSpeeds = [];
        this.maxSpeedHistory = 10;
        
        this.init();
    }
    
    init() {
        let ticking = false;
        
        window.addEventListener('scroll', () => {
            if (!ticking) {
                requestAnimationFrame(() => {
                    this.updateScrollSpeed();
                    ticking = false;
                });
                ticking = true;
            }
        });
    }
    
    updateScrollSpeed() {
        const currentTime = Date.now();
        const currentScrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        const timeDiff = currentTime - this.lastScrollTime;
        const scrollDiff = Math.abs(currentScrollTop - this.lastScrollTop);
        
        if (timeDiff > 0) {
            const speed = scrollDiff / timeDiff; // pixels per millisecond
            this.scrollSpeeds.push(speed);
            
            // 保持历史记录在合理范围内
            if (this.scrollSpeeds.length > this.maxSpeedHistory) {
                this.scrollSpeeds.shift();
            }
        }
        
        this.lastScrollTop = currentScrollTop;
        this.lastScrollTime = currentTime;
    }
    
    getAverageSpeed() {
        if (this.scrollSpeeds.length === 0) return 0;
        
        const sum = this.scrollSpeeds.reduce((a, b) => a + b, 0);
        return sum / this.scrollSpeeds.length;
    }
    
    getSpeedCategory() {
        const avgSpeed = this.getAverageSpeed();
        
        if (avgSpeed < 0.1) return 'slow';      // 慢速滚动
        if (avgSpeed < 0.5) return 'medium';    // 中速滚动
        return 'fast';                          // 快速滚动
    }
}

/**
 * 网络状态检测器
 * 检测网络连接类型和速度，优化预加载策略
 */
class NetworkDetector {
    constructor() {
        this.connectionType = 'unknown';
        this.effectiveType = 'unknown';
        this.downlink = 0;
        this.rtt = 0;
        this.networkCategory = 'medium';
        this.lastSpeedTest = 0;
        this.speedTestInterval = 30000; // 30秒测试一次
        
        this.init();
    }
    
    init() {
        // 检测网络连接信息
        this.detectConnection();
        
        // 监听网络状态变化
        if ('connection' in navigator) {
            navigator.connection.addEventListener('change', () => {
                this.detectConnection();
                this.categorizeNetwork();
            });
        }
        
        // 监听在线/离线状态
        window.addEventListener('online', () => {
            console.log('[NetworkDetector] 网络已连接');
            this.detectConnection();
        });
        
        window.addEventListener('offline', () => {
            console.log('[NetworkDetector] 网络已断开');
            this.networkCategory = 'offline';
        });
        
        // 定期测试网络速度
        this.scheduleSpeedTest();
    }
    
    detectConnection() {
        if ('connection' in navigator) {
            const connection = navigator.connection;
            this.connectionType = connection.type || 'unknown';
            this.effectiveType = connection.effectiveType || 'unknown';
            this.downlink = connection.downlink || 0;
            this.rtt = connection.rtt || 0;
        }
        
        this.categorizeNetwork();
    }
    
    categorizeNetwork() {
        // 基于连接类型分类
        if (this.effectiveType === 'slow-2g' || this.effectiveType === '2g') {
            this.networkCategory = 'slow';
        } else if (this.effectiveType === '3g') {
            this.networkCategory = 'medium';
        } else if (this.effectiveType === '4g' || this.downlink > 1.5) {
            this.networkCategory = 'fast';
        } else {
            this.networkCategory = 'medium';
        }
        
        console.log(`[NetworkDetector] 网络类型: ${this.networkCategory}, 连接: ${this.effectiveType}, 下行: ${this.downlink}Mbps`);
    }
    
    scheduleSpeedTest() {
        const now = Date.now();
        if (now - this.lastSpeedTest > this.speedTestInterval) {
            this.startSpeedTest();
        }
        
        // 定期调度速度测试
        setTimeout(() => this.scheduleSpeedTest(), this.speedTestInterval);
    }
    
    async startSpeedTest() {
        try {
            this.lastSpeedTest = Date.now();
            
            // 使用小图片测试网络速度
            const testImageUrl = 'static/icons/fa-picture-o.svg'; // 使用现有的小图标
            const startTime = performance.now();
            
            await this.loadTestImage(testImageUrl);
            
            const endTime = performance.now();
            const loadTime = endTime - startTime;
            
            // 根据加载时间调整网络分类
            if (loadTime > 2000) {
                this.networkCategory = 'slow';
            } else if (loadTime > 800) {
                this.networkCategory = 'medium';
            } else {
                this.networkCategory = 'fast';
            }
            
            console.log(`[NetworkDetector] 速度测试完成: ${loadTime.toFixed(2)}ms, 分类: ${this.networkCategory}`);
        } catch (error) {
            console.warn('[NetworkDetector] 网络速度测试失败:', error);
            // 测试失败时保持当前分类或降级为medium
            if (this.networkCategory === 'unknown') {
                this.networkCategory = 'medium';
            }
        }
    }
    
    loadTestImage(url) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.onload = resolve;
            img.onerror = reject;
            img.src = url + '?t=' + Date.now(); // 避免缓存
        });
    }
    
    getNetworkCategory() {
        return this.networkCategory;
    }
    
    getConnectionInfo() {
        return {
            type: this.connectionType,
            effectiveType: this.effectiveType,
            downlink: this.downlink,
            rtt: this.rtt,
            category: this.networkCategory
        };
    }
}

/**
 * 图片优先级队列
 * 管理图片加载优先级，优先加载可视区域图片
 */
class ImagePriorityQueue {
    constructor() {
        this.queue = [];
        this.loading = new Set();
        this.loaded = new Set();
        this.maxConcurrent = 3;
        this.observer = null;
        this.processTimeout = null;
        
        this.init();
    }
    
    init() {
        // 创建Intersection Observer
        this.observer = new IntersectionObserver(
            (entries) => this.handleIntersection(entries),
            {
                rootMargin: '200px 0px',  // 提前200px开始观察
                threshold: [0, 0.1, 0.5, 1.0]
            }
        );
    }
    
    handleIntersection(entries) {
        entries.forEach(entry => {
            const img = entry.target;
            const priority = this.calculatePriority(entry);
            
            this.updateImagePriority(img, priority, entry.isIntersecting);
        });
        
        // 防抖处理队列
        if (this.processTimeout) {
            clearTimeout(this.processTimeout);
        }
        this.processTimeout = setTimeout(() => this.processQueue(), 100);
    }
    
    calculatePriority(entry) {
        const { intersectionRatio, boundingClientRect } = entry;
        const viewportHeight = window.innerHeight;
        const elementTop = boundingClientRect.top;
        
        // 基础优先级：可视区域内的图片优先级最高
        if (intersectionRatio > 0) {
            return 100 + intersectionRatio * 100; // 100-200
        }
        
        // 距离可视区域的距离越近，优先级越高
        const distance = Math.abs(elementTop);
        const maxDistance = viewportHeight * 3; // 3个屏幕高度内
        
        if (distance > maxDistance) {
            return 0; // 超出范围，不预加载
        }
        
        return Math.max(1, 100 - (distance / maxDistance) * 99); // 1-99
    }
    
    updateImagePriority(img, priority, isVisible) {
        const imageId = img.dataset.imageId || img.dataset.src || img.src;
        
        // 移除旧的队列项
        this.queue = this.queue.filter(item => item.imageId !== imageId);
        
        // 添加新的队列项
        if (priority > 0 && !this.loaded.has(imageId) && !this.loading.has(imageId)) {
            this.queue.push({
                imageId,
                img,
                priority,
                isVisible,
                addedTime: Date.now()
            });
            
            // 按优先级排序
            this.queue.sort((a, b) => {
                // 可视区域内的图片优先
                if (a.isVisible !== b.isVisible) {
                    return b.isVisible - a.isVisible;
                }
                // 优先级高的优先
                if (a.priority !== b.priority) {
                    return b.priority - a.priority;
                }
                // 添加时间早的优先
                return a.addedTime - b.addedTime;
            });
        }
    }
    
    async processQueue() {
        // 控制并发加载数量
        while (this.loading.size < this.maxConcurrent && this.queue.length > 0) {
            const item = this.queue.shift();
            
            if (!this.loaded.has(item.imageId) && !this.loading.has(item.imageId)) {
                this.loading.add(item.imageId);
                this.loadImage(item);
            }
        }
    }
    
    async loadImage(item) {
        try {
            console.log(`[PriorityQueue] 开始加载图片: ${item.imageId}, 优先级: ${item.priority.toFixed(2)}`);
            
            const img = item.img;
            const src = img.dataset.src || img.src;
            
            // 如果图片已经有src且不是data-src，说明已经加载
            if (img.src && img.src !== '' && !img.dataset.src) {
                this.loaded.add(item.imageId);
                return;
            }
            
            // 创建新的图片对象进行预加载
            const preloadImg = new Image();
            
            await new Promise((resolve, reject) => {
                const timeout = setTimeout(() => {
                    reject(new Error('图片加载超时'));
                }, 10000); // 10秒超时
                
                preloadImg.onload = () => {
                    clearTimeout(timeout);
                    resolve();
                };
                preloadImg.onerror = () => {
                    clearTimeout(timeout);
                    reject(new Error('图片加载失败'));
                };
                preloadImg.src = src;
            });
            
            // 预加载成功，更新原图片
            if (img.dataset.src) {
                img.src = src;
                img.removeAttribute('data-src');
            }
            
            this.loaded.add(item.imageId);
            console.log(`[PriorityQueue] 图片加载成功: ${item.imageId}`);
            
        } catch (error) {
            console.warn(`[PriorityQueue] 图片加载失败: ${item.imageId}`, error);
        } finally {
            this.loading.delete(item.imageId);
            
            // 继续处理队列
            setTimeout(() => this.processQueue(), 50);
        }
    }
    
    observeImage(img) {
        if (this.observer && img) {
            // 设置唯一标识
            if (!img.dataset.imageId) {
                img.dataset.imageId = 'img_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            }
            this.observer.observe(img);
        }
    }
    
    unobserveImage(img) {
        if (this.observer && img) {
            this.observer.unobserve(img);
        }
    }
    
    getQueueStatus() {
        return {
            queueLength: this.queue.length,
            loading: this.loading.size,
            loaded: this.loaded.size,
            maxConcurrent: this.maxConcurrent
        };
    }
    
    // 清理已完成的加载记录（避免内存泄漏）
    cleanup() {
        // 保留最近1000个已加载记录
        if (this.loaded.size > 1000) {
            const loadedArray = Array.from(this.loaded);
            this.loaded.clear();
            loadedArray.slice(-500).forEach(id => this.loaded.add(id));
        }
    }
}

/**
 * 预加载配置管理器
 * 根据滚动速度和网络状况提供最优的预加载策略
 */
class PreloadConfig {
    constructor() {
        this.strategies = {
            slow: {
                preloadCount: 8,        // 慢速滚动时预加载8张
                preloadDistance: 1000,  // 提前1000px开始预加载
                batchSize: 3,           // 每批处理3张
                delay: 100              // 批次间延迟100ms
            },
            medium: {
                preloadCount: 12,       // 中速滚动时预加载12张
                preloadDistance: 1500,  // 提前1500px开始预加载
                batchSize: 4,           // 每批处理4张
                delay: 50               // 批次间延迟50ms
            },
            fast: {
                preloadCount: 16,       // 快速滚动时预加载16张
                preloadDistance: 2000,  // 提前2000px开始预加载
                batchSize: 6,           // 每批处理6张
                delay: 30               // 批次间延迟30ms
            }
        };
    }
    
    getStrategy(speedCategory, networkType = 'fast') {
        let strategy = { ...this.strategies[speedCategory] };
        
        // 根据网络状况调整策略
        if (networkType === 'slow') {
            strategy.preloadCount = Math.floor(strategy.preloadCount * 0.5);
            strategy.batchSize = Math.max(1, Math.floor(strategy.batchSize * 0.5));
            strategy.delay *= 2;
        } else if (networkType === 'medium') {
            strategy.preloadCount = Math.floor(strategy.preloadCount * 0.75);
            strategy.delay *= 1.5;
        } else if (networkType === 'offline') {
            // 离线状态下禁用预加载
            strategy.preloadCount = 0;
            strategy.batchSize = 0;
        }
        
        return strategy;
    }
}

/**
 * 智能预加载管理器 - 主控制器
 * 集成滚动速度检测、网络状态检测和图片优先级队列
 */
class IntelligentPreloader {
    constructor() {
        this.scrollSpeedTracker = new ScrollSpeedTracker();
        this.networkDetector = new NetworkDetector();
        this.priorityQueue = new ImagePriorityQueue();
        this.preloadConfig = new PreloadConfig();
        
        this.isEnabled = true;
        this.lastUpdate = 0;
        this.updateInterval = 1000; // 每秒更新一次策略
        this.cleanupInterval = 60000; // 每分钟清理一次
        
        this.init();
    }
    
    init() {
        // 定期更新预加载策略
        setInterval(() => {
            if (this.isEnabled) {
                this.updateStrategy();
            }
        }, this.updateInterval);
        
        // 定期清理内存
        setInterval(() => {
            this.priorityQueue.cleanup();
        }, this.cleanupInterval);
        
        console.log('[IntelligentPreloader] 智能预加载器已启动');
    }
    
    updateStrategy() {
        const speedCategory = this.scrollSpeedTracker.getSpeedCategory();
        const networkCategory = this.networkDetector.getNetworkCategory();
        const strategy = this.preloadConfig.getStrategy(speedCategory, networkCategory);
        
        // 更新优先级队列的并发数
        this.priorityQueue.maxConcurrent = strategy.batchSize;
        
        console.log(`[IntelligentPreloader] 策略更新 - 滚动: ${speedCategory}, 网络: ${networkCategory}, 并发: ${strategy.batchSize}`);
    }
    
    preloadImages(images) {
        if (!this.isEnabled) return;
        
        if (Array.isArray(images)) {
            images.forEach(img => {
                if (img && img.tagName === 'IMG') {
                    this.priorityQueue.observeImage(img);
                }
            });
        } else if (images && images.tagName === 'IMG') {
            this.priorityQueue.observeImage(images);
        }
    }
    
    enable() {
        this.isEnabled = true;
        console.log('[IntelligentPreloader] 已启用');
    }
    
    disable() {
        this.isEnabled = false;
        console.log('[IntelligentPreloader] 已禁用');
    }
    
    getStatus() {
        return {
            enabled: this.isEnabled,
            scrollSpeed: this.scrollSpeedTracker.getSpeedCategory(),
            network: this.networkDetector.getConnectionInfo(),
            queue: this.priorityQueue.getQueueStatus()
        };
    }
    
    // 获取当前策略信息
    getCurrentStrategy() {
        const speedCategory = this.scrollSpeedTracker.getSpeedCategory();
        const networkCategory = this.networkDetector.getNetworkCategory();
        return this.preloadConfig.getStrategy(speedCategory, networkCategory);
    }
}

// 导出类供其他模块使用
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        IntelligentPreloader,
        ScrollSpeedTracker,
        NetworkDetector,
        ImagePriorityQueue,
        PreloadConfig
    };
}
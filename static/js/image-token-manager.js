/**
 * 文件: static/js/image-token-manager.js
 * 描述: 图片Token管理器
 * 功能: Token缓存、批量获取、URL构建
 * 创建时间: 2025-01-27
 * 维护: AI助手
 */

class ImageTokenManager {
    constructor() {
        this.tokenCache = new Map(); // Token缓存
        this.pendingRequests = new Map(); // 正在请求的Token
        this.batchQueue = []; // 批量请求队列
        this.batchTimer = null; // 批量请求定时器
        this.batchDelay = 100; // 批量请求延迟（毫秒）
        this.maxBatchSize = 20; // 最大批量请求数量
        this.cacheExpiry = 24 * 60 * 60 * 1000; // 缓存过期时间（24小时）
        
        // 从localStorage恢复缓存
        this.loadCacheFromStorage();
    }
    
    /**
     * 从localStorage加载缓存
     */
    loadCacheFromStorage() {
        try {
            const cached = localStorage.getItem('imageTokenCache');
            if (cached) {
                const data = JSON.parse(cached);
                const now = Date.now();
                
                // 过滤过期的缓存
                Object.entries(data).forEach(([key, value]) => {
                    if (value.timestamp && (now - value.timestamp) < this.cacheExpiry) {
                        this.tokenCache.set(key, value);
                    }
                });
            }
        } catch (error) {
            console.warn('加载Token缓存失败:', error);
        }
    }
    
    /**
     * 保存缓存到localStorage
     */
    saveCacheToStorage() {
        try {
            const cacheData = {};
            this.tokenCache.forEach((value, key) => {
                cacheData[key] = value;
            });
            localStorage.setItem('imageTokenCache', JSON.stringify(cacheData));
        } catch (error) {
            console.warn('保存Token缓存失败:', error);
        }
    }
    
    /**
     * 生成缓存键
     * @param {string} wallpaperId 壁纸ID
     * @param {string} pathType 路径类型
     * @returns {string} 缓存键
     */
    getCacheKey(wallpaperId, pathType = 'preview') {
        return `${wallpaperId}_${pathType}`;
    }
    
    /**
     * 获取Token（优先从缓存）
     * @param {string} wallpaperId 壁纸ID
     * @param {string} pathType 路径类型 (preview|original)
     * @param {string} imagePath 图片路径（可选）
     * @returns {Promise<string|null>} Token或null
     */
    async getToken(wallpaperId, pathType = 'preview', imagePath = '') {
        const cacheKey = this.getCacheKey(wallpaperId, pathType);
        
        // 检查缓存
        const cached = this.tokenCache.get(cacheKey);
        if (cached && this.isCacheValid(cached)) {
            return cached.token;
        }
        
        // 检查是否正在请求
        if (this.pendingRequests.has(cacheKey)) {
            return await this.pendingRequests.get(cacheKey);
        }
        
        // 确保imagePath存在
        if (!imagePath) {
            imagePath = this.buildDefaultImagePath(wallpaperId, pathType);
        }
        
        // 创建请求Promise
        const requestPromise = this.requestToken(wallpaperId, pathType, imagePath);
        this.pendingRequests.set(cacheKey, requestPromise);
        
        try {
            const token = await requestPromise;
            this.pendingRequests.delete(cacheKey);
            return token;
        } catch (error) {
            this.pendingRequests.delete(cacheKey);
            throw error;
        }
    }
    
    /**
     * 请求单个Token
     * @param {string} wallpaperId 壁纸ID
     * @param {string} pathType 路径类型
     * @param {string} imagePath 图片路径
     * @returns {Promise<string|null>} Token或null
     */
    async requestToken(wallpaperId, pathType, imagePath) {
        try {
            const params = new URLSearchParams({
                action: 'get',
                wallpaper_id: wallpaperId,
                path_type: pathType
            });
            
            if (imagePath) {
                params.append('image_path', imagePath);
            }
            
            const response = await fetch(`/api/image_token.php?${params}`);
            const data = await response.json();
            
            if (data.code === 0 && data.data && data.data.token) {
                // 缓存Token
                const cacheKey = this.getCacheKey(wallpaperId, pathType);
                const cacheData = {
                    token: data.data.token,
                    timestamp: Date.now(),
                    wallpaper_id: wallpaperId,
                    path_type: pathType,
                    image_path: data.data.image_path
                };
                
                this.tokenCache.set(cacheKey, cacheData);
                this.saveCacheToStorage();
                
                return data.data.token;
            } else {
                console.error('获取Token失败:', data.message);
                return null;
            }
        } catch (error) {
            console.error('请求Token失败:', error);
            return null;
        }
    }
    
    /**
     * 批量获取Token
     * @param {Array} wallpapers 壁纸列表 [{wallpaper_id, path_type, image_path}]
     * @returns {Promise<Object>} Token映射对象
     */
    async getBatchTokens(wallpapers) {
        const needRequest = [];
        const result = {};
        
        // 检查缓存，收集需要请求的项目
        wallpapers.forEach(item => {
            const { wallpaper_id, path_type = 'preview' } = item;
            const cacheKey = this.getCacheKey(wallpaper_id, path_type);
            const cached = this.tokenCache.get(cacheKey);
            
            if (cached && this.isCacheValid(cached)) {
                result[cacheKey] = cached.token;
            } else {
                needRequest.push(item);
            }
        });
        
        // 如果没有需要请求的，直接返回缓存结果
        if (needRequest.length === 0) {
            return result;
        }
        
        try {
            const response = await fetch('/api/image_token.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'batch',
                    wallpapers: needRequest
                })
            });
            
            const data = await response.json();
            
            if (data.code === 0 && data.data && data.data.tokens) {
                // 缓存新获取的Token
                Object.entries(data.data.tokens).forEach(([key, token]) => {
                    const [wallpaper_id, path_type] = key.split('_');
                    const cacheData = {
                        token: token,
                        timestamp: Date.now(),
                        wallpaper_id: wallpaper_id,
                        path_type: path_type
                    };
                    
                    this.tokenCache.set(key, cacheData);
                    result[key] = token;
                });
                
                this.saveCacheToStorage();
                
                // 输出错误信息（如果有）
                if (data.data.errors && data.data.errors.length > 0) {
                    console.warn('批量获取Token部分失败:', data.data.errors);
                }
            } else {
                console.error('批量获取Token失败:', data.message);
            }
        } catch (error) {
            console.error('批量请求Token失败:', error);
        }
        
        return result;
    }
    
    /**
     * 添加到批量队列（延迟批量处理）
     * @param {string} wallpaperId 壁纸ID
     * @param {string} pathType 路径类型
     * @param {string} imagePath 图片路径
     * @returns {Promise<string|null>} Token或null
     */
    queueBatchRequest(wallpaperId, pathType = 'preview', imagePath = '') {
        return new Promise((resolve, reject) => {
            const cacheKey = this.getCacheKey(wallpaperId, pathType);
            
            // 检查缓存
            const cached = this.tokenCache.get(cacheKey);
            if (cached && this.isCacheValid(cached)) {
                resolve(cached.token);
                return;
            }
            
            // 添加到队列
            this.batchQueue.push({
                wallpaper_id: wallpaperId,
                path_type: pathType,
                image_path: imagePath,
                resolve: resolve,
                reject: reject
            });
            
            // 设置批量处理定时器
            if (this.batchTimer) {
                clearTimeout(this.batchTimer);
            }
            
            this.batchTimer = setTimeout(() => {
                this.processBatchQueue();
            }, this.batchDelay);
        });
    }
    
    /**
     * 处理批量队列
     */
    async processBatchQueue() {
        if (this.batchQueue.length === 0) {
            return;
        }
        
        const batch = this.batchQueue.splice(0, this.maxBatchSize);
        const wallpapers = batch.map(item => ({
            wallpaper_id: item.wallpaper_id,
            path_type: item.path_type,
            image_path: item.image_path
        }));
        
        try {
            const tokens = await this.getBatchTokens(wallpapers);
            
            // 解析Promise
            batch.forEach(item => {
                const cacheKey = this.getCacheKey(item.wallpaper_id, item.path_type);
                const token = tokens[cacheKey];
                
                if (token) {
                    item.resolve(token);
                } else {
                    item.reject(new Error('Token获取失败'));
                }
            });
        } catch (error) {
            // 所有Promise都reject
            batch.forEach(item => {
                item.reject(error);
            });
        }
        
        // 如果还有队列，继续处理
        if (this.batchQueue.length > 0) {
            this.batchTimer = setTimeout(() => {
                this.processBatchQueue();
            }, this.batchDelay);
        }
    }
    
    /**
     * 构建Token化的图片URL
     * @param {string} wallpaperId 壁纸ID
     * @param {string} pathType 路径类型
     * @param {Object} options 选项 {quality, width, height, download, imagePath}
     * @returns {Promise<string>} Token化的URL
     */
    async buildTokenizedUrl(wallpaperId, pathType = 'preview', options = {}) {
        try {
            // 如果提供了imagePath，使用它；否则构建默认路径
            const imagePath = options.imagePath || this.buildDefaultImagePath(wallpaperId, pathType);
            
            // 确保imagePath存在，这是API的必需参数
            if (!imagePath) {
                throw new Error(`无法确定图片路径: wallpaperId=${wallpaperId}, pathType=${pathType}`);
            }
            
            const token = await this.getToken(wallpaperId, pathType, imagePath);
            if (!token) {
                throw new Error('无法获取Token');
            }
            
            const params = new URLSearchParams({ token });
            
            // 添加可选参数
            if (options.quality) params.append('quality', options.quality);
            if (options.width) params.append('w', options.width);
            if (options.height) params.append('h', options.height);
            if (options.download) params.append('download', '1');
            
            return `/api/image_proxy.php?${params}`;
        } catch (error) {
            console.error('构建Token化URL失败:', error);
            // 返回fallback URL
            return this.buildFallbackUrl(wallpaperId, pathType, options);
        }
    }
    
    /**
     * 构建默认图片路径
     * @param {string} wallpaperId 壁纸ID
     * @param {string} pathType 路径类型
     * @returns {string} 默认图片路径
     */
    buildDefaultImagePath(wallpaperId, pathType = 'preview') {
        return pathType === 'preview' 
            ? `static/preview/${wallpaperId}/image.jpeg`
            : `static/wallpapers/${wallpaperId}/image.jpg`;
    }
    
    /**
     * 构建fallback URL（不使用Token）
     * @param {string} wallpaperId 壁纸ID
     * @param {string} pathType 路径类型
     * @param {Object} options 选项
     * @returns {string} 普通的图片URL
     */
    buildFallbackUrl(wallpaperId, pathType = 'preview', options = {}) {
        const basePath = options.imagePath || this.buildDefaultImagePath(wallpaperId, pathType);
        
        const params = new URLSearchParams({ path: basePath });
        
        if (options.quality) params.append('quality', options.quality);
        if (options.width) params.append('w', options.width);
        if (options.height) params.append('h', options.height);
        if (options.download) params.append('download', '1');
        
        return `/api/image_proxy.php?${params}`;
    }
    
    /**
     * 检查缓存是否有效
     * @param {Object} cached 缓存数据
     * @returns {boolean} 是否有效
     */
    isCacheValid(cached) {
        if (!cached || !cached.timestamp) {
            return false;
        }
        
        const now = Date.now();
        return (now - cached.timestamp) < this.cacheExpiry;
    }
    
    /**
     * 清除过期缓存
     */
    clearExpiredCache() {
        const now = Date.now();
        const toDelete = [];
        
        this.tokenCache.forEach((value, key) => {
            if (!this.isCacheValid(value)) {
                toDelete.push(key);
            }
        });
        
        toDelete.forEach(key => {
            this.tokenCache.delete(key);
        });
        
        if (toDelete.length > 0) {
            this.saveCacheToStorage();
            console.log(`清除了${toDelete.length}个过期Token缓存`);
        }
    }
    
    /**
     * 刷新Token（强制重新获取）
     * @param {string} wallpaperId 壁纸ID
     * @param {string} pathType 路径类型
     * @returns {Promise<string|null>} 新Token或null
     */
    async refreshToken(wallpaperId, pathType = 'preview') {
        const cacheKey = this.getCacheKey(wallpaperId, pathType);
        
        try {
            const params = new URLSearchParams({
                action: 'refresh',
                wallpaper_id: wallpaperId,
                path_type: pathType
            });
            
            const response = await fetch(`/api/image_token.php?${params}`);
            const data = await response.json();
            
            if (data.code === 0 && data.data && data.data.token) {
                // 更新缓存
                const cacheData = {
                    token: data.data.token,
                    timestamp: Date.now(),
                    wallpaper_id: wallpaperId,
                    path_type: pathType,
                    image_path: data.data.image_path
                };
                
                this.tokenCache.set(cacheKey, cacheData);
                this.saveCacheToStorage();
                
                return data.data.token;
            } else {
                console.error('刷新Token失败:', data.message);
                return null;
            }
        } catch (error) {
            console.error('刷新Token请求失败:', error);
            return null;
        }
    }
    
    /**
     * 清除所有缓存
     */
    clearAllCache() {
        this.tokenCache.clear();
        localStorage.removeItem('imageTokenCache');
        console.log('已清除所有Token缓存');
    }
    
    /**
     * 获取缓存统计信息
     * @returns {Object} 统计信息
     */
    getCacheStats() {
        const now = Date.now();
        let validCount = 0;
        let expiredCount = 0;
        
        this.tokenCache.forEach(value => {
            if (this.isCacheValid(value)) {
                validCount++;
            } else {
                expiredCount++;
            }
        });
        
        return {
            total: this.tokenCache.size,
            valid: validCount,
            expired: expiredCount,
            pendingRequests: this.pendingRequests.size,
            queueLength: this.batchQueue.length
        };
    }
}

// 创建全局实例
window.ImageTokenManager = window.ImageTokenManager || new ImageTokenManager();

// 定期清理过期缓存
setInterval(() => {
    window.ImageTokenManager.clearExpiredCache();
}, 60 * 60 * 1000); // 每小时清理一次

// 导出
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ImageTokenManager;
}
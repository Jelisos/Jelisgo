/**
 * 文件: static/js/image-compressor.js
 * 描述: 图片压缩和优化工具
 * 依赖: 无
 * 维护: 负责图片的压缩、格式转换和缓存管理
 */

/**
 * 图片压缩器模块
 * 提供客户端图片压缩和优化功能
 */
const ImageCompressor = {
    // 压缩配置 - 2025-01-27 优化图片质量和尺寸
    config: {
        // 预览图配置 - 优化预览体验
        preview: {
            maxWidth: 1200, // 从800提升到1200
            maxHeight: 900, // 从600提升到900
            quality: 0.95,  // 从0.85提升到0.95
            format: 'jpeg'
        },
        // 原图配置
        original: {
            maxWidth: 1920,
            maxHeight: 1080,
            quality: 0.95,
            format: 'jpeg'
        }
    },

    // 缓存管理
    cache: new Map(),
    cacheSize: 0,
    maxCacheSize: 50 * 1024 * 1024, // 50MB

    /**
     * 获取压缩后的图片URL
     * @param {string} originalPath - 原图路径
     * @param {string} type - 压缩类型 (preview|original)
     * @returns {Promise<string>} 压缩后的图片URL
     */
    async getCompressedImageUrl(originalPath, type = 'preview') {
        console.log(`[ImageCompressor] getCompressedImageUrl: 开始处理 ${originalPath} (${type})`);
        const cacheKey = `${originalPath}_${type}`;
        
        // 检查缓存
        if (this.cache.has(cacheKey)) {
            const cachedUrl = this.cache.get(cacheKey);
            console.log(`[ImageCompressor] getCompressedImageUrl: 缓存命中，返回 ${cachedUrl}`);
            return cachedUrl;
        }

        try {
            // 检查是否支持WebP
            const supportsWebP = await this.checkWebPSupport();
            const config = this.config[type];
            
            // 确保类型有效
            if (!config) {
                console.warn(`[ImageCompressor] getCompressedImageUrl: 不支持的压缩类型 '${type}'，返回原始路径`);
                this.addToCache(cacheKey, originalPath);
                return originalPath;
            }

            console.log(`[ImageCompressor] getCompressedImageUrl: WebP支持: ${supportsWebP}, 配置:`, config);
            
            // 如果不支持WebP，使用JPEG
            if (!supportsWebP && config.format === 'webp') {
                config.format = 'jpeg';
                console.log(`[ImageCompressor] getCompressedImageUrl: 浏览器不支持WebP，回退到JPEG格式`);
            }

            // 尝试加载压缩版本
            console.log(`[ImageCompressor] getCompressedImageUrl: 尝试加载服务器端压缩版本...`);
            const compressedUrl = await this.tryLoadCompressedVersion(originalPath, type, config);
            
            if (compressedUrl) {
                console.log(`[ImageCompressor] getCompressedImageUrl: 成功获取压缩URL: ${compressedUrl}`);
                this.addToCache(cacheKey, compressedUrl);
                return compressedUrl;
            }

            // 如果没有压缩版本，返回原图
            console.log(`[ImageCompressor] getCompressedImageUrl: 未找到服务器端压缩版本，返回原始路径: ${originalPath}`);
            this.addToCache(cacheKey, originalPath);
            return originalPath;

        } catch (error) {
            console.error(`[ImageCompressor] 获取压缩图片失败: ${originalPath} (${type})`, error);
            return originalPath; // 发生错误时返回原图
        }
    },

    /**
     * 尝试加载压缩版本
     * @param {string} originalPath - 原图路径
     * @param {string} type - 压缩类型
     * @param {Object} config - 压缩配置
     * @returns {Promise<string|null>} 压缩图片URL或null
     */
    async tryLoadCompressedVersion(originalPath, type, config) {
        console.log(`[ImageCompressor] tryLoadCompressedVersion: 处理 ${originalPath} (${type})`);
        // 构建压缩版本的路径
        const compressedPath = this.buildCompressedPath(originalPath, type, config);
        console.log(`[ImageCompressor] tryLoadCompressedVersion: 构建的压缩路径: ${compressedPath}`);
        
        // 2024-07-16 修改: 不再进行客户端动态生成，如果无法构建路径或文件不存在，直接返回null让上层回退到原图
        if (compressedPath === null) {
            console.log(`[ImageCompressor] tryLoadCompressedVersion: 无法构建服务器端压缩路径，不进行动态生成。`);
            return null;
        }
        
        // 检查压缩版本是否存在
        console.log(`[ImageCompressor] tryLoadCompressedVersion: 检查压缩图片 ${compressedPath} 是否存在...`);
        const exists = await this.checkImageExists(compressedPath);
        console.log(`[ImageCompressor] tryLoadCompressedVersion: 压缩图片 ${compressedPath} 存在: ${exists}`);
        
        if (exists) {
            return compressedPath;
        }

        console.log(`[ImageCompressor] tryLoadCompressedVersion: 服务器端压缩图片不存在。`);
        return null; // 不存在则返回null，让上层函数回退到原图
    },

    /**
     * 简单哈希函数
     * @param {string} str - 输入字符串
     * @returns {number} 哈希值
     */
    simpleHash(str) {
        let hash = 0;
        for (let i = 0; i < str.length; i++) {
            const char = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash; // 转换为32位整数
        }
        return Math.abs(hash);
    },

    /**
     * 计算图片分组编号
     * @param {string} filename - 文件名
     * @returns {string} 分组编号 (001-100)
     */
    calculateGroupNumber(filename) {
        const hash = this.simpleHash(filename);
        const groupNumber = (hash % 100) + 1;
        return String(groupNumber).padStart(3, '0');
    },

    /**
     * 构建压缩图片路径
     * @param {string} originalPath - 原图路径
     * @param {string} type - 压缩类型
     * @param {Object} config - 压缩配置
     * @returns {string} 压缩图片路径
     */
    buildCompressedPath(originalPath, type, config) {
        console.log(`[ImageCompressor] buildCompressedPath: 原始路径: ${originalPath}, 类型: ${type}`);
        
        // 规范化路径处理
        const normalizedPath = originalPath.startsWith('/') ? originalPath.substring(1) : originalPath;
        const pathParts = normalizedPath.split('/');
        const filename = pathParts.pop();
        const nameWithoutExt = filename.split('.')[0];
        
        console.log(`[ImageCompressor] buildCompressedPath: 文件名: ${filename}, 无扩展名: ${nameWithoutExt}`);
        
        if (type === 'preview') {
            // 2025-01-27 修复: 根据原始文件的目录结构构建预览路径
            const extension = config.format;
            const compressedFilename = `${nameWithoutExt}.${extension}`;
            
            // 从原始路径中提取目录结构
            let previewDir = '001'; // 默认目录
            if (pathParts.length >= 2 && pathParts[0] === 'static' && pathParts[1] === 'wallpapers') {
                // 如果是wallpapers目录下的文件，使用相同的子目录结构
                previewDir = pathParts[2] || '001';
            }
            
            const fullPath = `static/preview/${previewDir}/${compressedFilename}`;
            
            console.log(`[ImageCompressor] buildCompressedPath: 动态分组${previewDir}, 完整路径: ${fullPath}`);
            return fullPath;
        } else if (type === 'original') {
            // 原图保持原有路径结构
            const extension = config.format;
            const compressedFilename = `${nameWithoutExt}.${extension}`;
            const directory = pathParts.join('/');
            const fullPath = `${directory}/${compressedFilename}`;
            
            console.log(`[ImageCompressor] buildCompressedPath: 原图路径: ${fullPath}`);
            return fullPath;
        }
        
        console.warn(`[ImageCompressor] buildCompressedPath: 未知类型: ${type}`);
        return null;
    },

    /**
     * 检查图片是否存在
     * @param {string} imagePath - 图片路径
     * @returns {Promise<boolean>} 是否存在
     */
    async checkImageExists(imagePath) {
        console.log(`[ImageCompressor] checkImageExists: 检查路径: ${imagePath}`);
        try {
            const response = await fetch(imagePath, { method: 'HEAD' });
            const exists = response.ok;
            console.log(`[ImageCompressor] checkImageExists: ${imagePath} 存在: ${exists}, 状态码: ${response.status}`);
            return exists;
        } catch (error) {
            console.error(`[ImageCompressor] checkImageExists: 检查 ${imagePath} 失败`, error);
            return false;
        }
    },

    /**
     * 检查WebP支持
     * @returns {Promise<boolean>} 是否支持WebP
     */
    async checkWebPSupport() {
        if (this._webpSupport !== undefined) {
            return this._webpSupport;
        }

        try {
            const canvas = document.createElement('canvas');
            canvas.width = 1;
            canvas.height = 1;
            
            const dataUrl = canvas.toDataURL('image/webp');
            this._webpSupport = dataUrl.startsWith('data:image/webp');
            
            return this._webpSupport;
        } catch (error) {
            this._webpSupport = false;
            return false;
        }
    },

    /**
     * 添加到缓存
     * @param {string} key - 缓存键
     * @param {string} value - 缓存值
     */
    addToCache(key, value) {
        // 简单的缓存大小估算
        const estimatedSize = key.length + value.length;
        
        // 如果缓存已满，清理最旧的条目
        if (this.cacheSize + estimatedSize > this.maxCacheSize) {
            this.clearOldCache();
        }

        this.cache.set(key, value);
        this.cacheSize += estimatedSize;
    },

    /**
     * 清理旧缓存
     */
    clearOldCache() {
        const entries = Array.from(this.cache.entries());
        const toDelete = Math.ceil(entries.length * 0.3); // 删除30%的缓存
        
        for (let i = 0; i < toDelete; i++) {
            const [key] = entries[i];
            this.cache.delete(key);
        }
        
        // 重新计算缓存大小
        this.cacheSize = Array.from(this.cache.entries())
            .reduce((size, [key, value]) => size + key.length + value.length, 0);
    },

    /**
     * 清空缓存
     */
    clearCache() {
        this.cache.clear();
        this.cacheSize = 0;
    },

    /**
     * 预加载图片
     * @param {Array} imagePaths - 图片路径数组
     * @param {string} type - 压缩类型
     */
    async preloadImages(imagePaths, type = 'preview') {
        const promises = imagePaths.map(path => 
            this.getCompressedImageUrl(path, type).catch(error => {
                console.warn(`[ImageCompressor] 预加载失败: ${path}`, error);
                return path;
            })
        );

        await Promise.all(promises);
        console.log(`[ImageCompressor] 预加载完成: ${imagePaths.length} 张图片`);
    }
};

// 导出模块
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ImageCompressor;
}

// 全局暴露
window.ImageCompressor = ImageCompressor;

console.log('[ImageCompressor] 模块已加载');
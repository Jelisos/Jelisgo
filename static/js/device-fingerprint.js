/**
 * 设备指纹生成工具库
 * 文件: static/js/device-fingerprint.js
 * 描述: 用于生成唯一设备指纹，支持点赞系统的统一用户识别
 * 功能: 收集浏览器特征信息，生成稳定的设备指纹
 */

class DeviceFingerprint {
    constructor() {
        this.fingerprint = null;
        this.features = {};
        this.storageKey = 'device_fingerprint_v3'; // 2025-01-27 修复：更新版本号，废弃旧的不稳定指纹
    }

    /**
     * 获取或生成设备指纹
     * @param {boolean} forceRegenerate - 是否强制重新生成
     * @returns {Promise<string>} 设备指纹字符串
     */
    async getFingerprint(forceRegenerate = false) {
        // 尝试从本地存储获取已有指纹
        if (!forceRegenerate) {
            const stored = this.getStoredFingerprint();
            if (stored && stored.fingerprint && stored.timestamp > Date.now() - 30 * 24 * 60 * 60 * 1000) {
                this.fingerprint = stored.fingerprint;
                return this.fingerprint;
            }
        }

        // 生成新的设备指纹
        await this.collectFeatures();
        this.fingerprint = this.generateFingerprint();
        this.storeFingerprint();
        
        return this.fingerprint;
    }

    /**
     * 收集设备特征信息
     */
    async collectFeatures() {
        this.features = {
            // 基础浏览器信息
            userAgent: navigator.userAgent,
            language: navigator.language,
            languages: navigator.languages ? navigator.languages.join(',') : '',
            platform: navigator.platform,
            cookieEnabled: navigator.cookieEnabled,
            doNotTrack: navigator.doNotTrack,
            
            // 屏幕信息
            screenResolution: `${screen.width}x${screen.height}`,
            screenColorDepth: screen.colorDepth,
            screenPixelDepth: screen.pixelDepth,
            availableScreenResolution: `${screen.availWidth}x${screen.availHeight}`,
            
            // 时区信息
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
            timezoneOffset: new Date().getTimezoneOffset(),
            
            // Canvas指纹
            canvas: this.getCanvasFingerprint(),
            
            // WebGL指纹
            webgl: this.getWebGLFingerprint(),
            
            // 音频指纹
            audio: await this.getAudioFingerprint(),
            
            // 字体检测
            fonts: this.getFontFingerprint(),
            
            // 插件信息（简化版，避免隐私问题）
            pluginsCount: navigator.plugins ? navigator.plugins.length : 0,
            
            // 硬件信息
            hardwareConcurrency: navigator.hardwareConcurrency || 0,
            deviceMemory: navigator.deviceMemory || 0,
            
            // 网络信息
            connection: this.getConnectionInfo(),
            
            // 存储支持
            localStorage: this.checkLocalStorage(),
            sessionStorage: this.checkSessionStorage(),
            indexedDB: this.checkIndexedDB()
        };
    }

    /**
     * 生成Canvas指纹
     */
    getCanvasFingerprint() {
        try {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            
            canvas.width = 200;
            canvas.height = 50;
            
            // 绘制文本
            ctx.textBaseline = 'top';
            ctx.font = '14px Arial';
            ctx.fillStyle = '#f60';
            ctx.fillRect(125, 1, 62, 20);
            ctx.fillStyle = '#069';
            ctx.fillText('Device Fingerprint 🔒', 2, 15);
            ctx.fillStyle = 'rgba(102, 204, 0, 0.7)';
            ctx.fillText('Device Fingerprint 🔒', 4, 17);
            
            // 绘制几何图形
            ctx.globalCompositeOperation = 'multiply';
            ctx.fillStyle = 'rgb(255,0,255)';
            ctx.beginPath();
            ctx.arc(50, 25, 20, 0, Math.PI * 2, true);
            ctx.closePath();
            ctx.fill();
            
            return canvas.toDataURL();
        } catch (e) {
            return 'canvas_error';
        }
    }

    /**
     * 生成WebGL指纹
     */
    getWebGLFingerprint() {
        try {
            const canvas = document.createElement('canvas');
            const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
            
            if (!gl) {
                return 'webgl_not_supported';
            }
            
            const result = {
                vendor: gl.getParameter(gl.VENDOR),
                renderer: gl.getParameter(gl.RENDERER),
                version: gl.getParameter(gl.VERSION),
                shadingLanguageVersion: gl.getParameter(gl.SHADING_LANGUAGE_VERSION),
                maxTextureSize: gl.getParameter(gl.MAX_TEXTURE_SIZE),
                maxViewportDims: gl.getParameter(gl.MAX_VIEWPORT_DIMS)
            };
            
            return JSON.stringify(result);
        } catch (e) {
            return 'webgl_error';
        }
    }

    /**
     * 生成音频指纹
     */
    async getAudioFingerprint() {
        return new Promise((resolve) => {
            try {
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioContext.createOscillator();
                const analyser = audioContext.createAnalyser();
                const gainNode = audioContext.createGain();
                const scriptProcessor = audioContext.createScriptProcessor(4096, 1, 1);
                
                oscillator.type = 'triangle';
                oscillator.frequency.setValueAtTime(10000, audioContext.currentTime);
                
                gainNode.gain.setValueAtTime(0, audioContext.currentTime);
                
                oscillator.connect(analyser);
                analyser.connect(scriptProcessor);
                scriptProcessor.connect(gainNode);
                gainNode.connect(audioContext.destination);
                
                scriptProcessor.onaudioprocess = function(bins) {
                    const array = new Float32Array(analyser.frequencyBinCount);
                    analyser.getFloatFrequencyData(array);
                    
                    let fingerprint = 0;
                    for (let i = 0; i < array.length; i++) {
                        fingerprint += Math.abs(array[i]);
                    }
                    
                    oscillator.stop();
                    scriptProcessor.disconnect();
                    audioContext.close();
                    
                    resolve(fingerprint.toString());
                };
                
                oscillator.start(0);
                
                // 超时处理
                setTimeout(() => {
                    resolve('audio_timeout');
                }, 1000);
                
            } catch (e) {
                resolve('audio_error');
            }
        });
    }

    /**
     * 检测字体指纹
     */
    getFontFingerprint() {
        const testFonts = [
            'Arial', 'Helvetica', 'Times New Roman', 'Courier New', 'Verdana',
            'Georgia', 'Palatino', 'Garamond', 'Bookman', 'Comic Sans MS',
            'Trebuchet MS', 'Arial Black', 'Impact', 'Microsoft Sans Serif',
            'Tahoma', 'Lucida Console', 'Monaco', 'Consolas'
        ];
        
        const baseFonts = ['monospace', 'sans-serif', 'serif'];
        const testString = 'mmmmmmmmmmlli';
        const testSize = '72px';
        
        const canvas = document.createElement('canvas');
        const context = canvas.getContext('2d');
        context.textBaseline = 'top';
        context.font = testSize + ' monospace';
        
        const baselineWidths = {};
        baseFonts.forEach(baseFont => {
            context.font = testSize + ' ' + baseFont;
            baselineWidths[baseFont] = context.measureText(testString).width;
        });
        
        const availableFonts = [];
        testFonts.forEach(font => {
            baseFonts.forEach(baseFont => {
                context.font = testSize + ' ' + font + ', ' + baseFont;
                const width = context.measureText(testString).width;
                if (width !== baselineWidths[baseFont]) {
                    availableFonts.push(font);
                }
            });
        });
        
        return availableFonts.join(',');
    }

    /**
     * 获取网络连接信息
     */
    getConnectionInfo() {
        if (navigator.connection) {
            return {
                effectiveType: navigator.connection.effectiveType,
                downlink: navigator.connection.downlink,
                rtt: navigator.connection.rtt
            };
        }
        return 'connection_not_available';
    }

    /**
     * 检查存储支持
     */
    checkLocalStorage() {
        try {
            localStorage.setItem('test', 'test');
            localStorage.removeItem('test');
            return true;
        } catch (e) {
            return false;
        }
    }

    checkSessionStorage() {
        try {
            sessionStorage.setItem('test', 'test');
            sessionStorage.removeItem('test');
            return true;
        } catch (e) {
            return false;
        }
    }

    checkIndexedDB() {
        return !!window.indexedDB;
    }

    /**
     * 生成设备指纹
     * 2025-01-27 修复：移除时间戳，确保设备指纹稳定性
     */
    generateFingerprint() {
        const featuresString = JSON.stringify(this.features);
        return this.hashCode(featuresString).toString(36);
    }

    /**
     * 简单哈希函数
     */
    hashCode(str) {
        let hash = 0;
        if (str.length === 0) return hash;
        
        for (let i = 0; i < str.length; i++) {
            const char = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash; // 转换为32位整数
        }
        
        return Math.abs(hash);
    }

    /**
     * 存储指纹到本地
     */
    storeFingerprint() {
        if (this.checkLocalStorage()) {
            const data = {
                fingerprint: this.fingerprint,
                timestamp: Date.now(),
                features: this.features
            };
            localStorage.setItem(this.storageKey, JSON.stringify(data));
        }
    }

    /**
     * 从本地获取存储的指纹
     */
    getStoredFingerprint() {
        if (this.checkLocalStorage()) {
            try {
                const stored = localStorage.getItem(this.storageKey);
                return stored ? JSON.parse(stored) : null;
            } catch (e) {
                return null;
            }
        }
        return null;
    }

    /**
     * 清除存储的指纹
     */
    clearStoredFingerprint() {
        if (this.checkLocalStorage()) {
            localStorage.removeItem(this.storageKey);
        }
    }

    /**
     * 获取指纹详细信息（调试用）
     */
    getDetails() {
        return {
            fingerprint: this.fingerprint,
            features: this.features,
            stored: this.getStoredFingerprint()
        };
    }
}

// 全局实例
window.deviceFingerprint = new DeviceFingerprint();

// 便捷方法
window.getDeviceFingerprint = async function(forceRegenerate = false) {
    return await window.deviceFingerprint.getFingerprint(forceRegenerate);
};

// 导出（如果支持模块化）
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DeviceFingerprint;
}

// AMD支持
if (typeof define === 'function' && define.amd) {
    define([], function() {
        return DeviceFingerprint;
    });
}

console.log('设备指纹工具库已加载 - Device Fingerprint Library Loaded');
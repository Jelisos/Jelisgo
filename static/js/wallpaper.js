/**
 * 文件: static/js/wallpaper.js
 * 描述: 壁纸管理模块（已废弃，功能已迁移至image-loader.js）
 * 依赖: 无
 * 维护: 此文件中的功能已全部整合到image-loader.js中，建议删除此文件
 */

// ==================== 注意：此文件功能已迁移 ====================
// 所有壁纸相关功能（状态管理、视图切换、搜索、事件监听等）已整合到image-loader.js
// 为避免冲突，此文件中的功能已被禁用

console.warn('wallpaper.js中的功能已迁移至image-loader.js，此文件将被废弃');

// 原有的WallpaperManager对象已迁移至image-loader.js
// 为保持兼容性，提供一个空的WallpaperManager对象
const WallpaperManager = {
    // 状态变量（已迁移）
    state: {
        allWallpapers: [],
        filteredWallpapers: [],
        displayedWallpapers: new Set(),
        currentPage: 0,
        isLoading: false,
        currentViewMode: 'grid',
        searchKeyword: ''
    },

    // 所有方法已迁移至image-loader.js，这里提供空实现以保持兼容性
    getWallpaperListUrl() {
        console.log('getWallpaperListUrl功能已迁移至image-loader.js');
        return '';
    },

    async init() {
        console.log('WallpaperManager.init功能已迁移至image-loader.js');
    },

    initView() {
        console.log('initView功能已迁移至image-loader.js');
    },

    async switchToListView() {
        console.log('switchToListView功能已迁移至image-loader.js');
    },

    async switchToGridView() {
        console.log('switchToGridView功能已迁移至image-loader.js');
    },

    async loadWallpaperList() {
        console.log('loadWallpaperList功能已迁移至image-loader.js');
    },

    initEventListeners() {
        console.log('initEventListeners功能已迁移至image-loader.js');
    },

    async handleSearch(keyword) {
        console.log('handleSearch功能已迁移至image-loader.js');
    },

    handleWallpaperAction(wallpaperPath, action) {
        console.log('handleWallpaperAction功能已迁移至image-loader.js');
    },

    handleWallpaperClick(wallpaperPath) {
        console.log('handleWallpaperClick功能已迁移至image-loader.js');
    },

    saveWallpaperState(wallpaperPath, state) {
        console.log('saveWallpaperState功能已迁移至image-loader.js');
    },

    getNextPageWallpapers() {
        console.log('getNextPageWallpapers功能已迁移至image-loader.js');
        return null;
    },

    resetPagination() {
        console.log('resetPagination功能已迁移至image-loader.js');
    },

    renderWallpaperCards(wallpapers, append = false) {
        console.log('renderWallpaperCards功能已迁移至image-loader.js');
    }
};

// 导出WallpaperManager以保持兼容性
if (typeof module !== 'undefined' && module.exports) {
    module.exports = WallpaperManager;
}

// 全局暴露以保持兼容性
window.WallpaperManager = WallpaperManager;
/**
 * 文件: static/js/image-loader.js
 * 描述: 首页图片加载和压缩模块
 * 依赖: utils.js, config.js, image-compressor.js, intelligent-preloader.js, image-token-manager.js
 * 维护: 负责首页壁纸的加载、显示、压缩和缓存管理
 */

/**
 * 图片加载器模块
 * 负责首页壁纸的加载、显示、压缩和性能优化
 */
const ImageLoader = {
    // 滚动加载的阈值（距离底部多少像素开始加载）
    SCROLL_THRESHOLD: 300, // 2025-01-27 修复：降低滚动加载阈值，提前触发加载更多
    // 2025-01-27 新增：滚动触发位置配置（页面高度的比例）
    SCROLL_TRIGGER_RATIO: 0.67, // 滚动到页面2/3处触发加载更多

    // 状态管理
    state: {
        allWallpapers: [],
        filteredWallpapers: [],
        displayedWallpapers: new Set(),
        currentPage: 0,
        itemsPerPage: 24,  // 2025-01-27 修复：增加单次加载量，减少加载次数
        isLoading: false,
        isPreloading: false,  // 2025-01-27 添加预加载状态跟踪
        currentViewMode: 'grid',
        currentDisplayMode: 'normal', // 2024-07-28 新增：当前显示模式 ('normal' 或 'exiled_list')
        searchKeyword: '',
        currentCategory: 'all',
        categories: new Set(['all']),
        preloadedImages: new Set(),  // 预加载的图片集合
        intersectionObserver: null,   // 懒加载观察器
        userFavorites: new Set(), // 2024-07-16 新增：存储用户收藏的壁纸ID
        // userLikes 已删除
        isUserAdmin: false, // 2024-07-28 新增：用户是否为管理员
        exiledWallpaperIds: new Set(), // 2024-07-28 新增：存储被流放壁纸的ID
        exiledWallpapersData: [], // 2024-12-19 新增：存储流放壁纸的完整数据（包含时间）
        isPageReady: false, // 2024-12-19 新增：页面是否加载完成，防止快速点击
        viewSwitchDebounce: null, // 2024-12-19 新增：视图切换防抖定时器
        sessionSeed: null, // 2025-01-27 新增：会话随机种子，确保每次刷新页面图片顺序不同
        intelligentPreloader: null // 2025-01-27 新增：智能预加载器实例
    },
    
    /**
     * 初始化图片加载器
     */
    async init() {
        try {
            // 2025-01-27 新增：检查URL参数，恢复搜索状态
            this._checkUrlParams();
            
            // 新增：恢复视图状态
            this._restoreViewState();
            
            // 2024-07-28 新增：加载初始数据和用户权限
            await this._loadInitialDataAndPermissions();

            // 添加登录状态变化事件监听
            document.addEventListener('loginStatusChange', (event) => {
                const { isLoggedIn, userData } = event.detail;
                console.log('[ImageLoader] 收到登录状态变化事件:', isLoggedIn);
                
                // 更新管理员状态
                if (isLoggedIn && userData) {
                    this.state.isUserAdmin = userData.is_admin === 1;
                } else {
                    this.state.isUserAdmin = false;
                }
                
                console.log('[ImageLoader] 更新用户管理员状态:', this.state.isUserAdmin);
            });

            // 初始化懒加载观察器
            this.initIntersectionObserver();
            
            // 2025-01-27 新增：初始化智能预加载器
            this.initIntelligentPreloader();
            
            // 先尝试从缓存加载
            if (!this.loadFromCache()) {
                await this.loadWallpaperDataFromAPI();
            }
            
            if (this.state.allWallpapers.length === 0) {
                throw new Error('API返回数据为空');
            }
            
            // 2024-07-16 新增：加载用户收藏数据
            await this._loadUserFavorites();
            
            // 提取分类
            this.extractCategories();
            
            // 2024-12-19 修复：在所有数据加载完成后，重新过滤壁纸以确保被流放图片不会显示
            this.filterWallpapers();
            
            // 初始化UI
            this.initUI();
            
            // 绑定事件
            this.bindEvents();
            
            // 新增：应用恢复的视图状态到UI
            this._applyRestoredViewState();
            
            // 渲染初始内容
            await this.renderWallpapers();
            
            // 预加载下一页图片
            this.preloadNextPage();
            
            // 2024-12-19 新增：设置页面就绪状态，允许视图切换
            setTimeout(() => {
                this.state.isPageReady = true;
                console.log('[调试-页面状态] 页面加载完成，允许视图切换');
            }, 500); // 延迟500ms确保所有内容都已渲染
            
            console.log('[ImageLoader] 初始化完成');
            
        } catch (error) {
            console.error('[ImageLoader] 数据加载失败:', error);
            this.showErrorMessage('数据加载失败，请刷新页面重试');
            return;
        }
    },


    

    
    /**
     * 从数据库API加载壁纸数据
     */
    async loadWallpaperDataFromAPI() {
        console.log('[ImageLoader] Loading data from database API...');
        
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 10000); // 10秒超时
        
        try {
            // 构建API URL
            let apiUrl = '/api/wallpaper_data.php?action=list&limit=1000';
            
            // 如果有搜索关键词，添加搜索参数并忽略视图模式限制
            if (this.state.searchKeyword) {
                apiUrl += `&search=${encodeURIComponent(this.state.searchKeyword)}`;
                // 搜索时加载所有数据，不受流放状态限制
                apiUrl += '&exile_status=all';
                console.log('[ImageLoader] 搜索模式：直接从数据库搜索，关键词:', this.state.searchKeyword);
            } else {
                // 没有搜索时，根据当前显示模式过滤
                if (this.state.currentDisplayMode === 'exiled_list') {
                    apiUrl += '&exile_status=exiled';
                    console.log('[ImageLoader] 流放模式：只加载已流放数据');
                } else {
                    // 对于正常模式和收藏模式，加载所有数据以保证分类完整性
                    apiUrl += '&exile_status=all';
                    console.log('[ImageLoader] 正常/收藏模式：加载所有数据，前端过滤');
                }
            }
            
            const response = await fetch(apiUrl, {
                signal: controller.signal,
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });
            
            clearTimeout(timeoutId);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const result = await response.json();
            
            if (!result || typeof result !== 'object') {
                throw new Error('API返回数据格式错误');
            }
            
            if (result.code !== 0) {
                throw new Error(`API错误: ${result.message || '未知错误'}`);
            }
            
            const wallpapers = result.data?.wallpapers;
            if (!Array.isArray(wallpapers)) {
                throw new Error('API返回的壁纸数据不是数组格式');
            }
            
            // 转换API数据格式以兼容现有前端逻辑
            this.state.allWallpapers = wallpapers.map(wallpaper => ({
            id: wallpaper.id,
            filename: wallpaper.filename,
            path: wallpaper.path,
            name: wallpaper.name || wallpaper.title,
            title: wallpaper.title,
            category: wallpaper.category,
            tags: wallpaper.tags || [],
            width: wallpaper.width,
            height: wallpaper.height,
            size: wallpaper.size,
            format: wallpaper.format,
            description: wallpaper.description || '',
            views: wallpaper.views || 0,
            likes: wallpaper.likes || 0,
            created_at: wallpaper.created_at,
            // 2025-01-27 修复：保留流放状态相关字段
            exile_status: wallpaper.exile_status || 0,
            exile_info: wallpaper.exile_info || null,
            is_exiled: wallpaper.is_exiled || false
        }));
        
        // 清理旧的localStorage数据
        localStorage.removeItem('wallpaperOrder');
        
        // 初始化过滤数组
        this.state.filteredWallpapers = [];
        
        // 缓存数据
        this.cacheWallpaperData(this.state.allWallpapers);
        
        console.log(`[ImageLoader] Loaded ${this.state.allWallpapers.length} wallpapers from API`);
        
        } catch (error) {
            clearTimeout(timeoutId);
            if (error.name === 'AbortError') {
                throw new Error('API请求超时');
            }
            throw error;
        }
    },
    


    /**
     * 缓存壁纸数据
     */
    cacheWallpaperData(wallpapers) {
        try {
            const cacheData = {
                wallpapers: wallpapers,
                timestamp: Date.now(),
                version: '1.0'
            };
            localStorage.setItem('wallpaper_cache', JSON.stringify(cacheData));
            console.log('[ImageLoader] 数据已缓存');
        } catch (error) {
            console.warn('[ImageLoader] 缓存失败:', error);
        }
    },

    /**
     * 从缓存加载数据
     */
    loadFromCache() {
        try {
            const cached = localStorage.getItem('wallpaper_cache');
            if (!cached) return false;
            
            const cacheData = JSON.parse(cached);
            const cacheAge = Date.now() - cacheData.timestamp;
            const maxAge = 30 * 60 * 1000; // 30分钟
            
            if (cacheAge > maxAge) {
                localStorage.removeItem('wallpaper_cache');
                console.log('[ImageLoader] 缓存已过期');
                return false;
            }
            
            if (Array.isArray(cacheData.wallpapers) && cacheData.wallpapers.length > 0) {
                this.state.allWallpapers = cacheData.wallpapers;
                this.state.filteredWallpapers = [];
                console.log(`[ImageLoader] 从缓存加载 ${this.state.allWallpapers.length} 张壁纸`);
                return true;
            }
            
            return false;
        } catch (error) {
            console.warn('[ImageLoader] 缓存读取失败:', error);
            localStorage.removeItem('wallpaper_cache');
            return false;
        }
    },

    /**
     * 显示错误消息
     */
    showErrorMessage(message) {
        const container = document.getElementById('wallpaper-container');
        if (container) {
            container.innerHTML = `
                <div class="error-message text-center py-20">
                    <div class="text-red-500 text-xl mb-4">⚠️ ${message}</div>
                    <button onclick="location.reload()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                        重新加载
                    </button>
                </div>
            `;
        }
        console.error('[ImageLoader]', message);
    },

    /**
     * 提取分类信息
     */
    extractCategories() {
        this.state.categories.clear();
        this.state.categories.add('all');
        
        this.state.allWallpapers.forEach(wallpaper => {
            if (wallpaper.category) {
                this.state.categories.add(wallpaper.category);
            }
        });
        
        this.renderCategoryNav();
    },

    /**
     * 渲染分类导航
     */
    renderCategoryNav() {
        const container = document.getElementById('category-nav-container');
        if (!container) return;
        
        const categories = Array.from(this.state.categories);
        container.innerHTML = categories.map(category => {
            const isActive = category === this.state.currentCategory;
            const displayName = category === 'all' ? '全部' : category;
            
            return `
                <button class="category-btn px-4 py-2 rounded-full text-sm font-medium transition-all whitespace-nowrap ${
                    isActive 
                        ? 'bg-primary text-white' 
                        : 'bg-white text-gray-700 hover:bg-gray-100'
                }" data-category="${category}">
                    ${displayName}
                </button>
            `;
        }).join('');
    },

    /**
     * 初始化UI组件
     */
    initUI() {
        // 确保容器存在
        const container = document.getElementById('wallpaper-container');
        if (!container) {
            return;
        }
        
        // 设置初始视图模式
        this.updateViewMode(this.state.currentViewMode);

        // 2024-07-28 新增：根据管理员权限显示流放图片按钮
        const exiledListViewBtn = document.getElementById('exiled-list-view-btn');
        if (exiledListViewBtn && this.state.isUserAdmin) {
            exiledListViewBtn.classList.remove('hidden');
        }
    },

    /**
     * 绑定事件监听器
     */
    bindEvents() {
        // 搜索功能
        const searchInput = document.getElementById('search-input');
        const mobileSearchInput = document.getElementById('mobile-search-input');
        
        if (searchInput) {
            searchInput.addEventListener('input', this.debounce((e) => {
                this.handleSearch(e.target.value);
            }, 300));
        }
        
        if (mobileSearchInput) {
            mobileSearchInput.addEventListener('input', this.debounce((e) => {
                this.handleSearch(e.target.value);
            }, 300));
        }
        
        // 视图切换 - 2024-12-19 修复：添加防抖和页面就绪检查
        const gridViewBtn = document.getElementById('grid-view-btn');
        const listViewBtn = document.getElementById('list-view-btn');
        
        if (gridViewBtn) {
            gridViewBtn.addEventListener('click', () => this._handleViewSwitch('grid'));
        }
        
        if (listViewBtn) {
            listViewBtn.addEventListener('click', () => this._handleViewSwitch('list'));
        }
        
        // 2024-07-28 新增：流放图片视图切换
        const exiledListViewBtn = document.getElementById('exiled-list-view-btn');
        if (exiledListViewBtn) {
            exiledListViewBtn.addEventListener('click', () => this._handleViewSwitch('exiled_list'));
        }
        
        // 加载更多
        const loadMoreBtn = document.getElementById('load-more-btn');
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', () => this.loadMore());
        }
        
        // 分类点击事件（事件委托）
        const categoryContainer = document.getElementById('category-nav-container');
        if (categoryContainer) {
            categoryContainer.addEventListener('click', (e) => {
                if (e.target.classList.contains('category-btn')) {
                    const category = e.target.dataset.category;
                    this.handleCategoryChange(category);
                }
            });
        }

        // 页面滚动监听，用于加载更多和预加载
        // 2025-01-27 修复：保存滚动处理函数引用，以便后续能够正确移除
        this._scrollHandlerLoadMore = this.debounce(() => this._handleScrollForLoadMore(), 200);
        this._scrollHandlerPreload = this.debounce(() => this.preloadNextPage(), 300);
        
        window.addEventListener('scroll', this._scrollHandlerLoadMore);
        window.addEventListener('scroll', this._scrollHandlerPreload);

        // 点赞状态变化事件监听器已删除

        // 2024-07-26 新增：监听详情页收藏状态变化事件
        document.addEventListener('wallpaper-favorite-status-changed', (event) => {
            const { wallpaperId, action } = event.detail;
            this._updateCardFavoriteStatus(wallpaperId, action);
        });
    },

    /**
     * 处理滚动事件以触发加载更多
     * 2025-01-27 优化：支持按比例触发，默认滚动到2/3处触发
     */
    _handleScrollForLoadMore() {
        // 文档的总高度
        const documentHeight = document.documentElement.scrollHeight;
        // 视口高度
        const viewportHeight = window.innerHeight;
        // 当前滚动位置
        const scrollPosition = window.scrollY || document.documentElement.scrollTop;
        
        // 计算滚动进度（0-1之间）
        const scrollableHeight = documentHeight - viewportHeight;
        const scrollProgress = scrollableHeight > 0 ? scrollPosition / scrollableHeight : 0;
        
        // 当滚动进度达到设定比例时触发加载更多
        // 用户可通过修改 SCROLL_TRIGGER_RATIO 调整触发位置
        // 0.5 = 页面中间, 0.67 = 页面2/3处, 0.8 = 页面4/5处
        if (scrollProgress >= this.SCROLL_TRIGGER_RATIO) {
            this.loadMore();
        }
    },

    /**
     * 2025-01-27 新增：检查URL参数，恢复搜索状态
     */
    _checkUrlParams() {
        try {
            const urlParams = new URLSearchParams(window.location.search);
            const searchParam = urlParams.get('search') || urlParams.get('kw');
            
            if (searchParam) {
                this.state.searchKeyword = searchParam.trim().toLowerCase();
                // 同步更新搜索输入框的值
                this._updateSearchInputs(searchParam);
                console.log('[ImageLoader] 从URL恢复搜索关键词:', this.state.searchKeyword);
            } else {
                // 如果URL中没有搜索参数，确保清空搜索状态和输入框
                this.state.searchKeyword = '';
                this._updateSearchInputs('');
            }
        } catch (error) {
            console.warn('[ImageLoader] 解析URL参数失败:', error);
            this.state.searchKeyword = '';
            this._updateSearchInputs('');
        }
    },

    /**
     * 2025-01-27 新增：更新搜索输入框的值
     */
    _updateSearchInputs(value) {
        const searchInput = document.getElementById('search-input');
        const mobileSearchInput = document.getElementById('mobile-search-input');
        
        if (searchInput) {
            searchInput.value = value;
        }
        if (mobileSearchInput) {
            mobileSearchInput.value = value;
        }
    },

    /**
     * 新增：保存视图状态到localStorage
     */
    _saveViewState() {
        try {
            const viewState = {
                currentViewMode: this.state.currentViewMode,
                currentDisplayMode: this.state.currentDisplayMode,
                timestamp: Date.now()
            };
            localStorage.setItem('wallpaper_view_state', JSON.stringify(viewState));
            console.log('[ImageLoader] 已保存视图状态:', viewState);
        } catch (error) {
            console.warn('[ImageLoader] 保存视图状态失败:', error);
        }
    },

    /**
     * 新增：从localStorage恢复视图状态
     */
    _restoreViewState() {
        try {
            const saved = localStorage.getItem('wallpaper_view_state');
            if (!saved) return;
            
            const viewState = JSON.parse(saved);
            const stateAge = Date.now() - viewState.timestamp;
            const maxAge = 24 * 60 * 60 * 1000; // 24小时
            
            // 如果状态过期，清除并使用默认状态
            if (stateAge > maxAge) {
                localStorage.removeItem('wallpaper_view_state');
                console.log('[ImageLoader] 视图状态已过期，使用默认状态');
                return;
            }
            
            // 恢复视图状态
            if (viewState.currentViewMode) {
                this.state.currentViewMode = viewState.currentViewMode;
            }
            if (viewState.currentDisplayMode) {
                this.state.currentDisplayMode = viewState.currentDisplayMode;
            }
            
            console.log('[ImageLoader] 已恢复视图状态:', {
                currentViewMode: this.state.currentViewMode,
                currentDisplayMode: this.state.currentDisplayMode
            });
            
        } catch (error) {
            console.warn('[ImageLoader] 恢复视图状态失败:', error);
            // 清除损坏的状态数据
            localStorage.removeItem('wallpaper_view_state');
        }
    },

    /**
     * 新增：应用恢复的视图状态到UI
     */
    _applyRestoredViewState() {
        try {
            // 如果是流放视图，需要特殊处理
            if (this.state.currentDisplayMode === 'exiled_list') {
                console.log('[ImageLoader] 应用流放视图状态');
                
                // 更新容器样式
                const container = document.getElementById('wallpaper-container');
                if (container) {
                    container.className = 'exiled-view-grid min-h-[400px]';
                }
                
                // 高亮流放视图按钮
                this._highlightExiledViewButton();
                
            } else {
                // 应用普通视图状态
                this.updateViewMode(this.state.currentViewMode);
            }
            
            // 新增：更新页面标题以匹配当前视图状态
            this._updatePageTitle();
            
            console.log('[ImageLoader] 已应用恢复的视图状态到UI');
            
        } catch (error) {
            console.warn('[ImageLoader] 应用视图状态到UI失败:', error);
        }
    },

    /**
     * 处理搜索
     */
    async handleSearch(keyword) {
        const trimmedKeyword = keyword.trim().toLowerCase();
        this.state.searchKeyword = trimmedKeyword;
        
        // 2025-01-27 修复：更新URL参数，保持搜索状态
        this._updateUrlParams(trimmedKeyword);
        
        // 2025-01-27 修复：同步更新所有搜索输入框的值
        this._updateSearchInputs(trimmedKeyword);
        
        // 2025-01-27 优化：使用统一的状态重置逻辑
        this._resetViewState();
        
        // 如果有搜索关键词，直接从数据库搜索
        if (this.state.searchKeyword) {
            await this.loadWallpaperDataFromAPI();
        } else {
            // 如果清空搜索，重新加载所有数据
            await this.loadWallpaperDataFromAPI();
        }
        
        this.filterWallpapers();
        await this.renderWallpapers();
    },

    /**
     * 2025-01-27 新增：更新URL参数
     */
    _updateUrlParams(searchKeyword) {
        try {
            const url = new URL(window.location);
            
            if (searchKeyword) {
                url.searchParams.set('search', searchKeyword);
            } else {
                url.searchParams.delete('search');
                url.searchParams.delete('kw'); // 也删除旧的kw参数
            }
            
            // 更新URL但不刷新页面
            window.history.replaceState({}, '', url);
        } catch (error) {
            console.warn('[ImageLoader] 更新URL参数失败:', error);
        }
    },

    /**
     * 2025-01-27 新增：清空搜索状态
     */
    _clearSearchState() {
        this.state.searchKeyword = '';
        this._updateSearchInputs('');
        this._updateUrlParams('');
        console.log('[ImageLoader] 已清空搜索状态');
    },

    /**
     * 处理分类变更
     */
    async handleCategoryChange(category) {
        if (this.state.currentCategory === category) return;
        
        this.state.currentCategory = category;
        // 2025-01-27 优化：使用统一的状态重置逻辑
        this._resetViewState();
        
        this.filterWallpapers();
        this.renderCategoryNav();
        await this.renderWallpapers();
    },

    /**
     * 过滤壁纸
     */
    filterWallpapers() {
        let filtered = [...this.state.allWallpapers];
        
        console.log(`[ImageLoader] 开始过滤壁纸，原始数据: ${filtered.length}张`);
        console.log(`[ImageLoader] 当前显示模式: ${this.state.currentDisplayMode}`);
        console.log(`[ImageLoader] 搜索关键词: '${this.state.searchKeyword}'`);
        
        // 如果有搜索关键词，直接使用从数据库返回的搜索结果，不再进行本地过滤
        if (this.state.searchKeyword) {
            // 搜索结果已经从数据库返回，直接使用，不受视图模式和分类限制
            this.state.filteredWallpapers = filtered;
            console.log(`[ImageLoader] 搜索模式，直接使用API结果: ${filtered.length}张`);
            return;
        }
        
        // 没有搜索时，按原有逻辑进行过滤
        // 2025-01-27 修复：根据显示模式过滤流放壁纸，使用wallpaper对象的exile_status属性
        if (this.state.currentDisplayMode === 'normal') {
            const beforeFilter = filtered.length;
            // 首页只显示未流放的壁纸（exile_status为0或undefined）
            // 2025-01-27 修复：确保正确处理数字类型的exile_status
            filtered = filtered.filter(w => {
                const exileStatus = parseInt(w.exile_status) || 0;
                return exileStatus === 0;
            });
            console.log(`[ImageLoader] normal模式过滤: ${beforeFilter} -> ${filtered.length}张`);
            
            // 调试：显示前5张壁纸的exile_status
            if (this.state.allWallpapers.length > 0) {
                console.log('[ImageLoader] 前5张壁纸的exile_status:');
                this.state.allWallpapers.slice(0, 5).forEach((w, i) => {
                    console.log(`  ${i+1}. ID: ${w.id}, exile_status: ${w.exile_status} (类型: ${typeof w.exile_status})`);
                });
            }
        } else if (this.state.currentDisplayMode === 'exiled_list') {
            const beforeFilter = filtered.length;
            // 流放列表只显示已流放的壁纸（exile_status为1）
            filtered = filtered.filter(w => {
                const exileStatus = parseInt(w.exile_status) || 0;
                return exileStatus === 1;
            });
            console.log(`[ImageLoader] exiled_list模式过滤: ${beforeFilter} -> ${filtered.length}张`);
            
            // 按流放时间排序，最新流放的在前面
            if (filtered.length > 0) {
                filtered.sort((a, b) => {
                    const timeA = a.exile_info?.operation_time ? new Date(a.exile_info.operation_time) : new Date(0);
                    const timeB = b.exile_info?.operation_time ? new Date(b.exile_info.operation_time) : new Date(0);
                    return timeB - timeA; // 降序排列，最新的在前面
                });
            }
        } else if (this.state.currentDisplayMode === 'favorites_only') {
            const beforeFilter = filtered.length;
            // 列表视图只显示收藏的内容，不受流放限制
            const favoritedWallpapers = this.state.userFavorites || new Set();
            
            // 如果用户已登录且有收藏，显示收藏的壁纸（不管是否被流放）
            if (favoritedWallpapers.size > 0) {
                filtered = filtered.filter(w => 
                    favoritedWallpapers.has(w.id)
                );
            } else {
                // 如果没有收藏，显示所有未被流放的壁纸
                filtered = filtered.filter(w => {
                    const exileStatus = parseInt(w.exile_status) || 0;
                    return exileStatus === 0;
                });
            }
            console.log(`[ImageLoader] favorites_only模式过滤: ${beforeFilter} -> ${filtered.length}张`);
        }
        
        // 2025-01-27 修复：分类过滤只在正常模式下生效，流放列表和收藏列表不受分类影响
        if (this.state.currentCategory !== 'all' && this.state.currentDisplayMode === 'normal') {
            const beforeCategoryFilter = filtered.length;
            filtered = filtered.filter(w => w.category === this.state.currentCategory);
            console.log(`[ImageLoader] 分类过滤 (${this.state.currentCategory}): ${beforeCategoryFilter} -> ${filtered.length}张`);
        }
        
        this.state.filteredWallpapers = filtered;
        console.log(`[ImageLoader] 最终过滤结果: ${filtered.length}张壁纸`);
    },

    /**
     * 2025-01-27 新增：统一状态重置函数，确保视图切换时状态完全清理
     */
    _resetViewState() {
        const prevDisplayedCount = this.state.displayedWallpapers.size;
        this.state.currentPage = 0;
        this.state.displayedWallpapers.clear();
        this.state.hasMoreWallpapers = true;
        
        // 2025-01-27 优化：重新生成会话种子，确保视图切换后图片顺序不同
        this.state.sessionSeed = Date.now().toString();
        
        // 清空容器
        const container = document.getElementById('wallpaper-container');
        if (container) {
            container.innerHTML = '';
        }
        
        // 2025-01-27 新增：调试日志
        console.log(`[ImageLoader] 状态重置 - 清理了${prevDisplayedCount}张已显示图片，重新生成随机种子`);
    },

    /**
     * 2025-01-27 新增：检查用户是否已登录
     * @returns {boolean} 用户是否已登录
     */
    _isUserLoggedIn() {
        // 检查window.currentUser是否存在（由user-menu.js设置）
        if (window.currentUser && window.currentUser.id) {
            return true;
        }
        
        // 检查localStorage中的用户数据
        const userData = JSON.parse(localStorage.getItem('user') || '{}');
        return userData.id && userData.sessionId;
    },

    // 点赞功能已移除 - 原 _loadUserLikes 和 _loadUserLikesFromDatabase 函数

    /**
     * 2024-12-19 新增：统一的视图切换处理函数，包含防抖和状态检查
     */
    _handleViewSwitch(mode) {
        // 检查页面是否就绪
        if (!this.state.isPageReady) {
            console.log('[调试-视图切换] 页面尚未就绪，忽略点击');
            return;
        }
        
        // 防抖处理
        if (this.state.viewSwitchDebounce) {
            clearTimeout(this.state.viewSwitchDebounce);
        }
        
        this.state.viewSwitchDebounce = setTimeout(async () => {
            if (mode === 'exiled_list') {
                this._handleExiledListView();
            } else {
                await this.switchViewMode(mode);
            }
        }, 100); // 100ms防抖
    },

    /**
     * 切换视图模式
     */
    async switchViewMode(mode) {
        if (this.state.currentViewMode === mode && this.state.currentDisplayMode === 'normal') return;
        
        // 记录之前的显示模式
        const previousDisplayMode = this.state.currentDisplayMode;
        
        // 2025-01-27 修复：切换视图时清空搜索状态
        this._clearSearchState();
        
        // 2025-01-27 优化：统一状态重置逻辑，确保去重正确
        this._resetViewState();
        
        this.state.currentViewMode = mode;
        
        // 2024-12-19 新增：列表视图显示收藏内容
        if (mode === 'list') {
            this.state.currentDisplayMode = 'favorites_only';
        } else if (mode === 'grid') {
            this.state.currentDisplayMode = 'normal';
        }

        // 新增：保存视图状态
        this._saveViewState();
        
        // 新增：更新页面标题
        this._updatePageTitle();

        this.updateViewMode(mode);
        
        // 2025-01-27 修复：如果显示模式发生变化，需要重新加载数据
        if (previousDisplayMode !== this.state.currentDisplayMode) {
            try {
                console.log(`[ImageLoader] 显示模式从 ${previousDisplayMode} 切换到 ${this.state.currentDisplayMode}，重新加载数据`);
                await this.loadWallpaperDataFromAPI();
                this.filterWallpapers();
                this.renderWallpapers();
            } catch (error) {
                console.error('[ImageLoader] 切换视图模式时重新加载数据失败:', error);
                this.showErrorMessage('切换视图失败，请重试');
            }
        } else {
            this.filterWallpapers(); // 重新过滤以应用新的displayMode
            this.renderWallpapers();
        }
        
        this.updateLoadMoreButton(); // 更新加载更多按钮状态
    },

    /**
     * 更新视图模式UI
     */
    updateViewMode(mode) {
        const container = document.getElementById('wallpaper-container');
        const gridBtn = document.getElementById('grid-view-btn');
        const listBtn = document.getElementById('list-view-btn');
        const exiledBtn = document.getElementById('exiled-list-view-btn'); // 2024-07-28 新增
        
        if (!container) return;
        
        // 2024-12-19 修复：根据显示模式和视图模式设置正确的CSS类
        let containerClass = 'min-h-[400px]';
        if (this.state.currentDisplayMode === 'exiled_list') {
            containerClass = 'exiled-view-grid min-h-[400px]';
        } else if (mode === 'grid') {
            containerClass = 'masonry-grid min-h-[400px]';
        } else {
            containerClass = 'list-view-grid min-h-[400px]';
        }
        container.className = containerClass;
        
        // 更新按钮状态
        if (gridBtn && listBtn && exiledBtn) {
            // 重置所有按钮状态
            gridBtn.className = 'p-2 rounded bg-white hover:bg-neutral-dark transition-colors';
            listBtn.className = 'p-2 rounded bg-white hover:bg-neutral-dark transition-colors';
            exiledBtn.className = 'p-2 rounded bg-white hover:bg-neutral-dark transition-colors';

            // 设置当前活动按钮状态
            if (mode === 'grid' && this.state.currentDisplayMode === 'normal') {
                gridBtn.className = 'p-2 rounded bg-primary text-white';
            } else if (mode === 'list' && this.state.currentDisplayMode === 'normal') {
                listBtn.className = 'p-2 rounded bg-primary text-white';
            }
            // 流放列表按钮的高亮状态在_handleExiledListView中单独处理
        }
    },

    /**
     * 渲染壁纸
     */
    async renderWallpapers(append = false) {
        const container = document.getElementById('wallpaper-container');
        if (!container) return;
        
        if (!append) {
            // 2025-01-27 优化：使用统一的状态重置函数
            this._resetViewState();
        }
        
        // 2025-01-27 修复：从改进的分页函数获取壁纸
        const wallpapersToShow = this._getPaginatedRandomWallpapers();
        
        if (wallpapersToShow.length === 0 && !append) {
            container.innerHTML = '<div class="col-span-full text-center py-12 text-gray-500">暂无壁纸</div>';
            return;
        }
        
        // 创建壁纸卡片
        const fragment = document.createDocumentFragment();
        
        // 2025-01-27 优化：双重去重保护，确保绝对不会有重复图片
        const uniqueWallpapers = wallpapersToShow.filter(wallpaper => {
            if (this.state.displayedWallpapers.has(wallpaper.id)) {
                console.warn(`[ImageLoader] 检测到重复图片ID: ${wallpaper.id}，已跳过`);
                return false;
            }
            return true;
        });
        
        console.log(`[ImageLoader] 去重后实际渲染: ${uniqueWallpapers.length}张`);
        
        for (const wallpaper of uniqueWallpapers) {
            const card = await this.createWallpaperCard(wallpaper);
            fragment.appendChild(card);
            this.state.displayedWallpapers.add(wallpaper.id);

            // 2024-07-26 修复：直接在此处检查收藏状态，确保与当前wallpaper关联
            const favoriteButton = card.querySelector('.card-favorite-btn');
            if (favoriteButton) {
                const wallpaperId = parseInt(favoriteButton.dataset.wallpaperId);
                if (!isNaN(wallpaperId)) {
                    await this._checkCardFavoriteStatus(wallpaperId, favoriteButton);
                }
            }
            // 点赞状态检查已删除
        }
        
        container.appendChild(fragment);
        
        // 更新加载更多按钮状态
        this.updateLoadMoreButton();
    },

    /**
     * 创建壁纸卡片
     */
    async createWallpaperCard(wallpaper) {
        const card = document.createElement('div');
        // 2024-12-19 修复：所有视图模式都使用瀑布流布局，添加masonry-item类
        card.className = 'masonry-item wallpaper-card-item bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow cursor-pointer';
        card.dataset.wallpaperId = wallpaper.id;
        
        // 2024-07-16 新增：检查当前壁纸是否被用户收藏，并设置初始收藏状态
        const isFavorited = this.state.userFavorites.has(wallpaper.id);
        const favoriteIconSrc = isFavorited ? 'static/icons/fa-star.svg' : 'static/icons/fa-star-o.svg';
        const favoriteIconClass = isFavorited ? 'favorited' : '';
        
        // 创建占位符，先显示加载状态
        card.innerHTML = `
            <div class="relative overflow-hidden rounded-lg">
                <div class="w-full bg-gray-200 animate-pulse flex items-center justify-center" style="min-height: 200px; height: auto;">
                    <div class="text-gray-400 text-sm">加载中...</div>
                </div>
                <div class="absolute inset-0 bg-black/0 hover:bg-black/20 transition-colors flex items-center justify-center opacity-0 hover:opacity-100">
                    <button class="preview-btn text-white px-4 py-2 rounded-lg font-medium">
                        预览
                    </button>
                </div>
                <!-- 2024-07-26 添加：首页收藏按钮 -->
                <button class="card-favorite-btn absolute top-2 right-2 p-2 rounded-full bg-white/80 backdrop-blur-sm shadow-md text-gray-500 hover:text-red-500 transition-colors z-10" data-wallpaper-id="${wallpaper.id}">
                    <img src="${favoriteIconSrc}" alt="收藏" class="w-4 h-4 card-favorite-icon ${favoriteIconClass}">
                </button>
                <!-- 点赞按钮已删除 -->
            </div>
                <!-- 2025-01-27 修改：移除底部信息，流放/召回按钮移至右下角 -->
                <button class="card-exile-recall-btn absolute bottom-2 right-2 p-1 rounded-full bg-white/80 backdrop-blur-sm shadow-sm text-gray-500 hover:text-red-500 transition-colors z-10" data-wallpaper-id="${wallpaper.id}" data-action="${this.state.exiledWallpaperIds.has(wallpaper.id) ? 'recall' : 'exile'}">
                    <img src="static/icons/${this.state.exiledWallpaperIds.has(wallpaper.id) ? 'zh.png' : 'lf.png'}" alt="${this.state.exiledWallpaperIds.has(wallpaper.id) ? '召回' : '流放'}" class="w-5 h-5">
                </button>
        `;
        
        // 异步加载图片
        this.loadCardImage(card, wallpaper);
        
        // 绑定点击事件（整个卡片点击显示详情）
        card.addEventListener('click', async (e) => {
            // 2024-07-26 修复：如果点击的是收藏按钮，则不触发卡片详情
            const favoriteButton = e.target.closest('.card-favorite-btn'); // 2024-07-26 修复：在 card 层面处理收藏点击
            // 点赞按钮检测已删除
            // 2024-07-28 新增：流放/召回按钮
            const exileRecallButton = e.target.closest('.card-exile-recall-btn');

            if (favoriteButton) {
                e.stopPropagation(); // 2024-07-26 修复：立即阻止事件冒泡
                e.preventDefault();  // 2024-07-26 修复：立即阻止默认行为
                await this._handleCardFavoriteClick(favoriteButton); // 2024-07-26 修复：直接传递点击的按钮元素
                return;
            }

            // 点赞按钮点击处理已删除

            if (exileRecallButton) {
                e.stopPropagation();
                e.preventDefault();
                await this._handleExileRecallClick(exileRecallButton);
                return;
            }

            // 如果点击的不是收藏或流放/召回按钮，才触发卡片详情
            this.handleWallpaperClick(wallpaper);
        });
        
        return card;
    },

    /**
     * 初始化懒加载观察器
     */
    initIntersectionObserver() {
        // 2025-01-27 修复滚动卡顿：清理旧的观察器，避免重复绑定
        if (this.state.intersectionObserver) {
            this.state.intersectionObserver.disconnect();
        }
        
        if (!('IntersectionObserver' in window)) {
            return;
        }
        
        this.state.intersectionObserver = new IntersectionObserver(
            (entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        const src = img.dataset.src;
                        if (src && !img.src) {
                            img.src = src;
                            img.removeAttribute('data-src');
                            this.state.intersectionObserver.unobserve(img);
                        }
                    }
                });
            },
            {
                rootMargin: '50px 0px',  // 提前50px开始加载
                threshold: 0.1
            }
        );
    },

    /**
     * 2025-01-27 新增：初始化智能预加载器
     */
    initIntelligentPreloader() {
        try {
            // 检查智能预加载器是否可用
            if (typeof IntelligentPreloader === 'undefined') {
                console.warn('[ImageLoader] IntelligentPreloader未加载，使用传统预加载');
                return;
            }
            
            // 创建智能预加载器实例
            this.state.intelligentPreloader = new IntelligentPreloader();
            
            // 配置预加载器回调
            this.state.intelligentPreloader.onImageLoad = (imageUrl, success) => {
                if (success) {
                    console.log(`[智能预加载] 图片加载成功: ${imageUrl}`);
                } else {
                    console.warn(`[智能预加载] 图片加载失败: ${imageUrl}`);
                }
            };
            
            console.log('[ImageLoader] 智能预加载器初始化完成');
            
        } catch (error) {
            console.error('[ImageLoader] 智能预加载器初始化失败:', error);
        }
    },

    /**
     * 异步加载卡片图片
     */
    async loadCardImage(card, wallpaper) {
        try {
            const placeholder = card.querySelector('.animate-pulse');
            if (!placeholder) {
                return;
            }
            
            if (!wallpaper.path || typeof wallpaper.path !== 'string') {
                throw new Error(`无效的图片路径: ${wallpaper.name}`);
            }
            
            const img = document.createElement('img');
            // SEO优化：生成更描述性的alt属性
            const altText = this.generateImageAltText(wallpaper);
            img.alt = this.escapeHtml(altText);
            // 2024-12-19 修复：确保图片自适应高度，避免留白
            // 2024-12-25 修复：移除hover:scale-105避免收藏图标变大问题
            img.className = 'w-full h-auto object-cover block';
            
            // 图片加载完成后替换占位符 - 2024-07-16 修复：先设置onload/onerror，再设置src
            img.onload = () => {
                // 2024-07-16 修复：使用 requestAnimationFrame 确保 DOM 更新与渲染同步
                if (placeholder && placeholder.parentNode) {
                    placeholder.replaceWith(img);
                }
            };
            
            // 错误处理
            img.onerror = (e) => {
                // 2024-07-16 修复：尝试使用原始路径
                if (img.src !== wallpaper.path && wallpaper.path.startsWith('static/')) { // 避免无限循环尝试和非项目内路径
                    img.src = wallpaper.path;
                    return; // 给原始路径一次机会加载
                }
                
                // 如果原始路径也失败，显示占位图标
                // 2024-07-16 修复：使用 requestAnimationFrame 确保 DOM 更新与渲染同步
                img.src = 'static/icons/fa-picture-o.svg';
                img.className = 'w-full h-48 object-contain bg-gray-100';
                if (placeholder && placeholder.parentNode) {
                    placeholder.replaceWith(img);
                }
                const errorText = placeholder.querySelector('.text-gray-400');
                if (errorText) errorText.textContent = '加载失败';
            };

            // 获取压缩图片URL（使用真实壁纸ID进行Token化）
            const imageUrl = await this.getCompressedImageUrl(wallpaper.path, wallpaper.id);
            
            // 直接设置src，利用浏览器原生懒加载
            img.src = imageUrl;
            
            // 2024-07-16 修复：针对blob URL或缓存图片，确保onload事件能正确触发UI更新
            // 检查图片是否已经完成加载（针对立即加载的blob URL或缓存图片）
            if (img.complete) {
                // 2024-07-16 修复：使用 requestAnimationFrame 确保 DOM 更新与渲染同步
                if (placeholder && placeholder.parentNode) {
                    placeholder.replaceWith(img);
                }
            }
            
        } catch (error) {
            // 显示错误状态
            const placeholder = card.querySelector('.animate-pulse');
            if (placeholder) {
                placeholder.innerHTML = '<div class="text-red-400 text-sm">加载失败</div>';
                placeholder.classList.remove('animate-pulse');
            }
        }
    },

    /**
     * 预加载下一页图片
     * 2025-01-27 优化：集成智能预加载器
     */
    async preloadNextPage() {
        // 只有在正常模式下才预加载下一页
        if (this.state.currentDisplayMode !== 'normal') return;
        
        // 2025-01-27 修复卡顿：限制预加载，避免过多并发请求
        if (this.state.isPreloading) {
            return;
        }
        this.state.isPreloading = true;
        
        try {
            const nextStartIndex = (this.state.currentPage + 1) * this.state.itemsPerPage;
            const nextPageWallpapers = this.state.filteredWallpapers.slice(nextStartIndex, nextStartIndex + this.state.itemsPerPage);
            
            if (nextPageWallpapers.length === 0) {
                return;
            }
            
            // 2025-01-27 新增：使用智能预加载器
            if (this.state.intelligentPreloader) {
                // 准备图片URL列表
                const imageUrls = [];
                for (const wallpaper of nextPageWallpapers) {
                    if (!this.state.preloadedImages.has(wallpaper.id)) {
                        try {
                            const imageUrl = await this.getCompressedImageUrl(wallpaper.path, wallpaper.id);
                            imageUrls.push(imageUrl);
                            this.state.preloadedImages.add(wallpaper.id);
                        } catch (e) {
                            console.warn(`[预加载] 获取压缩图片URL失败: ${wallpaper.path}`);
                        }
                    }
                }
                
                // 使用智能预加载器进行预加载
                if (imageUrls.length > 0) {
                    this.state.intelligentPreloader.preloadImages(imageUrls);
                    console.log(`[智能预加载] 开始预加载 ${imageUrls.length} 张图片`);
                }
            } else {
                // 回退到传统预加载方式
                const batchSize = 5; // 控制并发数
                for (let i = 0; i < nextPageWallpapers.length; i += batchSize) {
                    const batch = nextPageWallpapers.slice(i, i + batchSize);
                    const batchPromises = batch.map(async (wallpaper) => {
                        if (!this.state.preloadedImages.has(wallpaper.id)) {
                            try {
                                const img = new Image();
                                const imageUrl = await this.getCompressedImageUrl(wallpaper.path, wallpaper.id);
                                img.src = imageUrl;
                                this.state.preloadedImages.add(wallpaper.id);
                                await new Promise((resolve, reject) => {
                                    img.onload = resolve;
                                    img.onerror = () => {
                                        // 预加载失败不抛出错误，记录日志即可
                                    };
                                });
                            } catch (e) {
                                // 预加载失败，不影响主流程
                            }
                        }
                    });
                    await Promise.all(batchPromises);
                    // 批次间添加小延迟，避免阻塞主线程
                    await new Promise(resolve => setTimeout(resolve, 50));
                }
            }
            
        } finally {
            this.state.isPreloading = false;
        }
    },

    /**
     * 获取压缩后的图片URL
     * 2025-01-27 修复：使用真实壁纸ID进行Token化访问
     * 使用ImageCompressor进行图片压缩和优化
     */
    async getCompressedImageUrl(originalPath, wallpaperId = null) {
        try {
            if (!originalPath || typeof originalPath !== 'string') {
                throw new Error('无效的图片路径');
            }
            
            // 规范化路径，确保以static开头 (如果原始路径没有斜杠开头)
            const normalizedPath = originalPath.startsWith('/') ? originalPath : `/${originalPath}`;
            
            // 2025-01-27 修复：使用真实壁纸ID进行Token化访问
            if (window.ImageTokenManager && wallpaperId) {
                try {
                    // 直接使用传入的真实壁纸ID，而不是从路径提取目录ID
                    console.log(`[ImageLoader] 使用真实壁纸ID进行Token化: ${wallpaperId}`);
                    
                    // 构建Token化的URL，使用original类型以获取更好的图片质量
                    const tokenizedUrl = await window.ImageTokenManager.buildTokenizedUrl(
                        wallpaperId, 
                        'original', 
                        { 
                            quality: 85,
                            imagePath: normalizedPath // 传递完整的图片路径
                        }
                    );
                    
                    if (tokenizedUrl && !tokenizedUrl.includes('buildFallbackUrl')) {
                        console.log(`[ImageLoader] 成功使用Token化URL: ID=${wallpaperId}, 路径: ${normalizedPath}`);
                        return tokenizedUrl;
                    }
                } catch (tokenError) {
                    console.warn('[ImageLoader] Token化访问失败，回退到传统方式:', tokenError);
                }
            }
            
            // 回退到传统的ImageCompressor方式
            // 2024-07-24 修改: 将类型从'thumbnail'改为'preview'，以便ImageCompressor获取预览图
            const compressedUrl = await ImageCompressor.getCompressedImageUrl(normalizedPath, 'preview');
            
            console.log(`[ImageLoader] 使用传统方式加载图片: ${normalizedPath} -> ${compressedUrl}`);
            return compressedUrl;
        } catch (error) {
            console.warn('[ImageLoader] 获取压缩图片URL失败:', error);
            return originalPath;
        }
    },
    
    /**
     * 2025-01-27 新增：从图片路径中提取壁纸ID
     * @param {string} imagePath 图片路径
     * @returns {string|null} 壁纸ID或null
     */
    _extractWallpaperIdFromPath(imagePath) {
        try {
            // 匹配路径中的数字目录 (如 /001/, /002/)
            const match = imagePath.match(/\/(\d{3})\//i);
            if (match) {
                return match[1];
            }
            
            // 如果没有匹配到，尝试从文件名提取
            const filename = imagePath.split('/').pop();
            if (filename) {
                const nameMatch = filename.match(/^(\d{3})/);
                if (nameMatch) {
                    return nameMatch[1];
                }
            }
            
            return null;
        } catch (error) {
            console.warn('[ImageLoader] 提取壁纸ID失败:', error);
            return null;
        }
    },

    /**
     * 处理壁纸点击
     */
    handleWallpaperClick(wallpaper) {
        // 触发壁纸详情显示事件
        const event = new CustomEvent('wallpaper-detail-show', {
            detail: wallpaper
        });
        document.dispatchEvent(event);
    },

    /**
     * 加载更多壁纸
     */
    async loadMore() {
        if (this.state.isLoading) return;
        
        // 2025-01-27 新增：如果正在进行流放/召回操作，阻止加载更多
        if (this.state.isExileRecallInProgress) {
            console.log('[ImageLoader] 流放/召回操作进行中，跳过loadMore');
            return;
        }
        
        // 2025-01-27 修复：更准确地检查是否有更多数据可以显示
        const undisplayedWallpapers = this.state.filteredWallpapers.filter(
            w => !this.state.displayedWallpapers.has(w.id)
        );
        if (undisplayedWallpapers.length === 0) {
            this.updateLoadMoreButton();
            return;
        }

        this.state.isLoading = true;
        this.updateLoadMoreButton(); // 更新加载更多按钮状态
        
        try {
            await this.renderWallpapers(true); // 总是追加
            // this.preloadNextPage(); // 预加载已在loadWallpaperData或renderWallpapers中处理
        } catch (error) {
            console.error("加载更多壁纸失败:", error);
        } finally {
            this.state.isLoading = false;
            this.updateLoadMoreButton();
        }
    },

    /**
     * 更新加载更多按钮状态
     */
    updateLoadMoreButton() {
        const loadMoreBtn = document.getElementById('load-more-btn');
        if (!loadMoreBtn) return;

        // 2024-12-19 修改：正常模式和流放视图都显示加载更多按钮
        if (this.state.currentDisplayMode === 'normal' || this.state.currentDisplayMode === 'exiled_list') {
            loadMoreBtn.style.display = 'flex';
        } else {
            loadMoreBtn.style.display = 'none';
            return;
        }
        
        // 2025-01-27 修复：更准确地判断是否有更多未显示的壁纸
        const undisplayedWallpapers = this.state.filteredWallpapers.filter(
            w => !this.state.displayedWallpapers.has(w.id)
        );
        const hasMoreWallpapersToDisplay = undisplayedWallpapers.length > 0;
        
        loadMoreBtn.disabled = this.state.isLoading || !hasMoreWallpapersToDisplay; // 如果正在加载或没有更多壁纸，则禁用按钮
        
        if (this.state.isLoading) {
            loadMoreBtn.innerHTML = '<span>加载中...</span>';
        } else if (!hasMoreWallpapersToDisplay) {
            loadMoreBtn.innerHTML = '<span>没有更多了</span>';
        } else {
            loadMoreBtn.innerHTML = '<span>加载更多</span><img src="static/icons/fa-refresh.svg" alt="刷新" class="w-3 h-3 text-gray-400" />';
        }
    },

    /**
     * 显示错误信息
     */
    showError(message) {
        const errorContainer = document.getElementById('error-message-container');
        if (errorContainer) {
            errorContainer.textContent = message;
            errorContainer.style.display = 'block';
            setTimeout(() => {
                errorContainer.style.display = 'none';
            }, 5000);
        } else {
            alert(message);
        }
    },

    /**
     * HTML转义
     */
    escapeHtml(text) {
        // 2024-12-19 修复：处理undefined或null的情况
        if (text === undefined || text === null) {
            return '';
        }
        // 确保text是字符串类型
        text = String(text);
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    },

    /**
     * 防抖函数
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    /**
     * 检查用户收藏状态并更新首页卡片UI
     * @param {number} wallpaperId - 壁纸ID
     * @param {HTMLElement} favoriteBtn - 收藏按钮元素
     */
    async _checkCardFavoriteStatus(wallpaperId, favoriteBtn) {
        const favoriteIcon = favoriteBtn.querySelector('.card-favorite-icon');
        if (!favoriteIcon) return;

        try {
            const response = await this._fetchJson('api/my_favorites.php', 'GET');
            if (response.code === 0 && response.data) {
                const isFavorited = response.data.some(favWallpaper => favWallpaper.id === wallpaperId);
                if (isFavorited) {
                    favoriteIcon.src = 'static/icons/fa-star.svg';
                    favoriteBtn.classList.add('favorited');
                } else {
                    favoriteIcon.src = 'static/icons/fa-star-o.svg';
                    favoriteBtn.classList.remove('favorited');
                }
            } else if (response.code === 401) {
                // 用户未登录，恢复默认状态
                favoriteIcon.src = 'static/icons/fa-star-o.svg';
                favoriteBtn.classList.remove('favorited');
            }
        } catch (error) {
            // 忽略错误，不显示
        }
    },

    /**
     * 处理首页卡片收藏/取消收藏点击事件
     * @param {HTMLElement} button - 收藏按钮元素
     */
    async _handleCardFavoriteClick(button) {
        const wallpaperId = parseInt(button.dataset.wallpaperId);
        
        if (isNaN(wallpaperId)) {
            return;
        }

        const favoriteIcon = button.querySelector('.card-favorite-icon');
        if (!favoriteIcon) {
            return;
        }

        const originalIconSrc = favoriteIcon.src;
        const isCurrentlyFavorited = originalIconSrc.includes('fa-star.svg');

        // 临时禁用按钮并显示处理中状态
        button.disabled = true;
        favoriteIcon.src = 'static/icons/loading.svg'; // 可以用一个旋转的loading图标

        try {
            const response = await this._fetchJson('api/toggle_favorite.php', 'POST', {
                wallpaper_id: wallpaperId
            });

            if (response.code === 0) {
                if (response.action === 'favorited') {
                    favoriteIcon.src = 'static/icons/fa-star.svg';
                    button.classList.add('favorited');
                } else if (response.action === 'unfavorited') {
                    favoriteIcon.src = 'static/icons/fa-star-o.svg';
                    button.classList.remove('favorited');
                }

                // 2024-07-26 新增：收藏状态改变后，派发自定义事件通知其他模块
                document.dispatchEvent(new CustomEvent('wallpaper-favorite-status-changed', {
                    detail: {
                        wallpaperId: wallpaperId,
                        action: response.action
                    }
                }));

            } else if (response.code === 401) {
                alert('收藏需要登录，请先登录！');
                favoriteIcon.src = originalIconSrc; // 恢复图标
            } else {
                alert(`操作失败: ${response.msg}`);
                favoriteIcon.src = originalIconSrc; // 恢复图标
            }
        } catch (error) {
            alert('网络请求失败，请稍后重试。');
            favoriteIcon.src = originalIconSrc; // 恢复图标
        } finally {
            button.disabled = false;
        }
    },

    // 点赞功能已移除 - 原 _checkCardLikeStatus 函数

    // 点赞功能已移除 - 原 _updateCardLikeStatus 函数

    // 点赞功能已移除 - 原 _handleCardLikeClick 函数

    /**
     * 获取JSON数据
     * @param {string} url - 请求URL
     * @param {string} method - 请求方法
     * @param {Object} data - 请求数据
     * @returns {Promise<Object>} - 返回解析后的JSON对象
     */
    async _fetchJson(url, method, data = null) {
        try {
            // 构建请求头
            const headers = {
                'Content-Type': 'application/json'
            };
            
            // 添加Authorization头（如果用户已登录）
            const userData = JSON.parse(localStorage.getItem('user') || '{}');
            if (userData.id) {
                headers['Authorization'] = `Bearer ${userData.id}`;
            }
            
            const response = await fetch(url, {
                method: method,
                headers: headers,
                body: data ? JSON.stringify(data) : null
            });
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return await response.json();
        } catch (error) {
            throw error;
        }
    },

    /**
     * 随机打乱数组（Fisher-Yates (Knuth) shuffle算法）
     * @param {Array} array - 要打乱的数组
     * @returns {Array} - 打乱后的新数组
     */
    _shuffleArray(array) {
        let currentIndex = array.length, randomIndex;
        while (currentIndex !== 0) {
            randomIndex = Math.floor(Math.random() * currentIndex);
            currentIndex--;
            [array[currentIndex], array[randomIndex]] = [
                array[randomIndex], array[currentIndex]];
        }
        return array;
    },

    /**
     * 2025-01-27 重构：获取分页的壁纸（修复图片加载不全问题）
     * 采用顺序分页+随机打乱的策略，确保所有图片都能被加载
     * @returns {Array} - 当前页的壁纸列表
     */
    _getPaginatedRandomWallpapers() {
        // 流放列表模式：按时间顺序分页
        if (this.state.currentDisplayMode === 'exiled_list') {
            const wallpapersToPickFrom = this.state.filteredWallpapers.filter(
                w => !this.state.displayedWallpapers.has(w.id)
            );
            // 2025-01-27 修复：流放列表初始显示时加载更多图片，避免显示不全
            const isInitialLoad = this.state.displayedWallpapers.size === 0;
            const numToTake = isInitialLoad ? 
                Math.min(this.state.itemsPerPage * 2, wallpapersToPickFrom.length) : // 初始加载2页的量
                Math.min(this.state.itemsPerPage, wallpapersToPickFrom.length);     // 后续加载正常分页
            return wallpapersToPickFrom.slice(0, numToTake);
        }

        // 正常模式：使用改进的分页策略确保所有图片都能被加载
        const wallpapersToPickFrom = this.state.filteredWallpapers.filter(
            w => !this.state.displayedWallpapers.has(w.id)
        );

        if (wallpapersToPickFrom.length === 0) {
            return [];
        }

        // 计算当前应该显示的页数（基于已显示的数量）
        const currentPageIndex = Math.floor(this.state.displayedWallpapers.size / this.state.itemsPerPage);
        const numToTake = Math.min(this.state.itemsPerPage, wallpapersToPickFrom.length);

        // 2025-01-27 优化：每次页面刷新都使用不同的随机种子，确保首页图片不重复
        const shuffledWallpapers = [...wallpapersToPickFrom];
        
        // 使用时间戳+页面索引+分类+搜索关键词作为随机种子
        // 确保每次刷新页面都有不同的随机顺序，但同一会话内的分页保持一致
        if (!this.state.sessionSeed) {
            this.state.sessionSeed = Date.now().toString();
        }
        const seed = this.state.sessionSeed + this.state.currentCategory + this.state.searchKeyword + currentPageIndex;
        this._shuffleArrayWithSeed(shuffledWallpapers, seed);
        
        return shuffledWallpapers.slice(0, numToTake);
    },

    /**
     * 2025-01-27 新增：基于种子的数组随机打乱
     * 确保相同种子产生相同的随机顺序
     */
    _shuffleArrayWithSeed(array, seed) {
        // 简单的种子随机数生成器
        let seedNum = 0;
        for (let i = 0; i < seed.length; i++) {
            seedNum += seed.charCodeAt(i);
        }
        
        // Fisher-Yates 洗牌算法，使用种子随机数
        for (let i = array.length - 1; i > 0; i--) {
            seedNum = (seedNum * 9301 + 49297) % 233280; // 线性同余生成器
            const j = Math.floor((seedNum / 233280) * (i + 1));
            [array[i], array[j]] = [array[j], array[i]];
        }
    },

    /**
     * 处理详情页收藏状态变化事件
     * @param {number} wallpaperId - 壁纸ID
     * @param {string} action - 操作类型 ('favorited' 或 'unfavorited')
     */
    _updateCardFavoriteStatus(wallpaperId, action) {
        // 查找对应的壁纸卡片
        const card = document.querySelector(`.wallpaper-card-item[data-wallpaper-id="${wallpaperId}"]`);
        if (!card) {
            console.warn(`[ImageLoader] _updateCardFavoriteStatus: 未找到壁纸ID为 ${wallpaperId} 的卡片。`);
            return;
        }

        const favoriteIcon = card.querySelector('.card-favorite-icon');
        const favoriteBtn = card.querySelector('.card-favorite-btn');

        if (!favoriteIcon || !favoriteBtn) {
            console.warn(`[ImageLoader] _updateCardFavoriteStatus: 壁纸ID ${wallpaperId} 的卡片缺少收藏相关元素。`);
            return;
        }

        if (action === 'favorited') {
            favoriteIcon.src = 'static/icons/fa-star.svg'; // 变为实心
            favoriteIcon.classList.add('favorited');
            favoriteBtn.classList.add('favorited');
        } else if (action === 'unfavorited') {
            favoriteIcon.src = 'static/icons/fa-star-o.svg'; // 变为空心
            favoriteIcon.classList.remove('favorited');
            favoriteBtn.classList.remove('favorited');
        }
    },

    /**
     * 2024-07-16 新增：加载用户收藏数据
     */
    async _loadUserFavorites() {
        try {
            // 2024-07-16 调试：确保请求的URL和方法正确
            const response = await this._fetchJson('api/my_favorites.php', 'GET');
            
            // 2024-07-16 调试：检查响应结构，确保data是数组且包含id
            if (response.code === 0 && Array.isArray(response.data)) {
                // 将收藏的wallpaper_id转换为Set，方便快速查找
                this.state.userFavorites = new Set(response.data.map(item => item.id));
                console.log('[ImageLoader] 用户收藏加载成功:', this.state.userFavorites);
            } else {
                console.error('[ImageLoader] 加载用户收藏失败或数据格式不正确:', response.msg || response);
                this.state.userFavorites = new Set(); // 失败时清空，避免影响后续判断
            }
        } catch (error) {
            console.error('[ImageLoader] 加载用户收藏请求错误:', error);
            this.state.userFavorites = new Set(); // 错误时清空
        }
    },

    // 点赞功能已移除 - 原 _loadUserLikes 函数

    /**
     * 2024-07-28 新增：加载初始数据和用户权限
     */
    async _loadInitialDataAndPermissions() {
        try {
            // 1. 获取用户权限信息 - 从localStorage中获取，避免重复请求
            const storedUser = localStorage.getItem('user');
            if (storedUser) {
                try {
                    const userData = JSON.parse(storedUser);
                    this.state.isUserAdmin = userData.is_admin === 1;
                    console.log('[ImageLoader] 从localStorage获取用户管理员状态 (isUserAdmin):', this.state.isUserAdmin);
                } catch (e) {
                    console.error('[ImageLoader] 解析本地存储的用户信息失败:', e);
                    this.state.isUserAdmin = false;
                }
            } else {
                // 未登录，isUserAdmin 默认为 false
                console.log('[ImageLoader] 用户未登录，无法获取管理员权限。');
                this.state.isUserAdmin = false;
            }

            // 2. 获取被流放的壁纸ID列表
            // 2024-12-19 修复：所有用户都需要获取流放壁纸ID列表，以正确显示图标状态
            const exiledResponse = await this._fetchJson('api/get_exiled_wallpaper_ids.php', 'GET');
            console.log('[ImageLoader] exiledResponse:', exiledResponse);
            if (exiledResponse.code === 200 && Array.isArray(exiledResponse.data)) {
                // 2024-12-19 修改：处理新的数据结构，包含ID和流放时间
                this.state.exiledWallpaperIds = new Set(exiledResponse.data.map(item => item.id));
                this.state.exiledWallpapersData = exiledResponse.data; // 保存完整数据用于排序
                console.log('[ImageLoader] 已加载流放壁纸ID:', this.state.exiledWallpaperIds);
                console.log('[ImageLoader] 流放壁纸数据:', this.state.exiledWallpapersData);
            } else {
                console.warn('[ImageLoader] 获取流放壁纸ID列表失败:', exiledResponse.message);
                this.state.exiledWallpaperIds = new Set(); // 失败时清空，避免残留旧数据
                this.state.exiledWallpapersData = []; // 清空流放数据
            }

        } catch (error) {
            console.error('[ImageLoader] 加载初始数据和权限失败:', error);
            this.state.isUserAdmin = false;
            this.state.exiledWallpaperIds = new Set();
        }
    },

    /**
     * 2024-07-28 新增：处理流放/召回按钮点击
     * @param {HTMLElement} button - 被点击的按钮元素
     */
    async _handleExileRecallClick(button) {
        const wallpaperId = parseInt(button.dataset.wallpaperId);
        const action = button.dataset.action; // 'exile' or 'recall'

        if (isNaN(wallpaperId)) {
            console.error("无效的壁纸ID");
            return;
        }

        // 2025-01-27 修复：强制实时权限验证，确保用户登录状态有效
        // 每次操作都验证用户的实际登录状态，不依赖本地存储
        let hasPermission = false;
        
        try {
            const userResponse = await this._fetchJson('api/auth_unified.php?action=getUserInfo', 'GET');
            if (userResponse.code === 401) {
                // 未登录用户 - 不更新本地存储，避免影响登出状态
                Utils.showToastMessage('请先登录后再进行操作', 'warning');
                return;
            } else if (userResponse.code === 200 && userResponse.data) {
                const isAdmin = userResponse.data.is_admin === 1;
                const isPermanentMember = userResponse.data.membership_type === 'permanent';
                
                // 更新本地状态
                this.state.isUserAdmin = isAdmin;
                
                if (isAdmin) {
                    // 管理员拥有最高权限
                    hasPermission = true;
                    console.log('[ImageLoader] 管理员权限验证通过');
                } else if (isPermanentMember) {
                    // 永久会员权限
                    hasPermission = true;
                    console.log('[ImageLoader] 永久会员权限验证通过');
                } else {
                    // 普通用户无权限
                    Utils.showToastMessage('此功能需要永久会员权限', 'error');
                    return;
                }
                
                // 重要修复：只有在用户确实已登录时才更新本地存储
                // 检查本地存储中是否已有用户数据，如果没有则说明用户已退出登录
                const currentLocalUser = localStorage.getItem('user');
                if (currentLocalUser) {
                    // 只有在本地存储中有用户数据时才更新，避免在用户退出登录后重新写入数据
                    localStorage.setItem('user', JSON.stringify(userResponse.data));
                    console.log('[ImageLoader] 权限状态已验证并同步到本地存储');
                } else {
                    console.log('[ImageLoader] 用户已退出登录，不更新本地存储');
                }
            } else {
                // 其他情况
                Utils.showToastMessage('权限验证失败，请重新登录', 'error');
                return;
            }
        } catch (error) {
            console.error('权限检查失败:', error);
            Utils.showToastMessage('网络错误，请稍后重试', 'error');
            return;
        }

        let confirmMessage = '';
        if (action === 'exile') {
            confirmMessage = '是否确定流放此图？流放后将不在首页显示。';
        } else if (action === 'recall') {
            confirmMessage = '是否确定召回此图？召回后将有机会在首页重新显示。';
        }

        // 对于流放操作，需要确认
        if (action === 'exile' && !await modals.confirm(confirmMessage)) {
            return; // 用户取消操作
        }

        button.disabled = true;
        const originalButtonHtml = button.innerHTML;
        button.innerHTML = `<i class="fa fa-spinner fa-spin"></i>`; // 显示加载动画

        let response = null; // 在try块外部声明response并初始化为null
        try {
            const requestBody = { action: `${action}_wallpaper`, wallpaper_id: wallpaperId };
            response = await this._fetchJson(
                'api/wallpaper.php',
                'POST',
                requestBody
            );

            if (response.code === 200) {
                Utils.showToastMessage(response.message, 'success');
                // 2024-12-19 修改：操作成功后重新获取最新的流放数据
                await this._refreshExiledData();
                
                // 2025-01-27 新增：设置流放/召回操作进行中的标志，阻止loadMore触发
        this.state.isExileRecallInProgress = true;
        
        // 暂时禁用滚动监听器，防止卡片移除时触发页面跳转
        this._temporarilyDisableScrollHandlers();
                
                // 2025-01-27 修复：首先更新内存中的壁纸数据状态
                const wallpaperIndex = this.state.allWallpapers.findIndex(w => w.id === wallpaperId);
                if (wallpaperIndex !== -1) {
                    if (action === 'exile') {
                        this.state.allWallpapers[wallpaperIndex].exile_status = 1;
                        this.state.allWallpapers[wallpaperIndex].is_exiled = true;
                        // 更新流放信息
                        this.state.allWallpapers[wallpaperIndex].exile_info = {
                            operation_time: new Date().toISOString()
                        };
                    } else if (action === 'recall') {
                        this.state.allWallpapers[wallpaperIndex].exile_status = 0;
                        this.state.allWallpapers[wallpaperIndex].is_exiled = false;
                        this.state.allWallpapers[wallpaperIndex].exile_info = null;
                    }
                    console.log(`[ImageLoader] 已更新壁纸 ${wallpaperId} 的内存状态: exile_status=${this.state.allWallpapers[wallpaperIndex].exile_status}`);
                }
                
                // 更新前端状态并移除卡片 (或更新按钮状态)
                if (action === 'exile') {
                    // 在正常模式下，流放后移除卡片
                    if (this.state.currentDisplayMode !== 'exiled_list') {
                        const cardElement = document.querySelector(`.wallpaper-card-item[data-wallpaper-id="${wallpaperId}"]`);
                        if (cardElement) {
                            cardElement.remove();
                            this.state.displayedWallpapers.delete(wallpaperId);
                        }
                    } else {
                        // 在流放列表模式下，更新按钮为"召回"状态
                        button.dataset.action = 'recall';
                        button.innerHTML = `<img src="static/icons/zh.png" alt="召回" class="w-5 h-5">`;
                    }

                } else if (action === 'recall') {
                    // 2025-01-27 修复：召回操作需要移除所有该图片的卡片，防止重复显示
                    const allCardElements = document.querySelectorAll(`.wallpaper-card-item[data-wallpaper-id="${wallpaperId}"]`);
                    allCardElements.forEach(cardElement => {
                        cardElement.remove();
                        console.log(`[ImageLoader] 已移除壁纸 ${wallpaperId} 的卡片`);
                    });
                    this.state.displayedWallpapers.delete(wallpaperId);
                    
                    // 在正常模式下，更新按钮为"流放"状态（如果还有其他卡片的话）
                    if (this.state.currentDisplayMode !== 'exiled_list') {
                        button.dataset.action = 'exile';
                        button.innerHTML = `<img src="static/icons/lf.png" alt="流放" class="w-5 h-5">`;
                    }
                }
                
                // 重新过滤壁纸，确保状态变化后的数据一致性
                this.filterWallpapers();
                
                // 2025-01-27 新增：如果是在流放列表中召回，需要检查是否还有流放图片，如果没有则显示空状态
                if (action === 'recall' && this.state.currentDisplayMode === 'exiled_list') {
                    if (this.state.filteredWallpapers.length === 0) {
                        const container = document.getElementById('wallpaper-container');
                        if (container) {
                            container.innerHTML = `
                                <div class="text-center py-20">
                                    <div class="text-gray-500 text-xl mb-4">📭 流放列表为空</div>
                                    <div class="text-gray-400">当前没有被流放的图片</div>
                                </div>
                            `;
                        }
                    }
                }
                
                // 延迟恢复滚动监听器，避免页面高度变化触发滚动事件
        setTimeout(() => {
            this._restoreScrollHandlers();
            // 2025-01-27 新增：清除流放/召回操作进行中的标志
            this.state.isExileRecallInProgress = false;
        }, 200);

            } else {
                Utils.showToastMessage(response.message || '操作失败', 'error');
            }
        } catch (error) {
            console.error("操作壁纸失败: ", error);
            Utils.showToastMessage('网络或服务器错误，操作失败', 'error');
        } finally {
            button.disabled = false;
            // 如果操作失败，恢复按钮图标
            if (!response || response.code !== 200) {
                 button.innerHTML = originalButtonHtml;
                 // 2025-01-27 新增：操作失败时也要清除流放/召回操作标志
                 this.state.isExileRecallInProgress = false;
            }
        }
    },

    /**
     * 2024-12-19 新增：刷新流放数据
     */
    async _refreshExiledData() {
        try {
            const exiledResponse = await this._fetchJson('api/get_exiled_wallpaper_ids.php', 'GET');
            if (exiledResponse.code === 200 && Array.isArray(exiledResponse.data)) {
                // 更新流放壁纸数据
                this.state.exiledWallpaperIds = new Set(exiledResponse.data.map(item => item.id));
                this.state.exiledWallpapersData = exiledResponse.data;
                console.log('[ImageLoader] 已刷新流放壁纸数据:', this.state.exiledWallpaperIds);
            } else {
                console.warn('[ImageLoader] 刷新流放壁纸数据失败:', exiledResponse.message);
            }
        } catch (error) {
            console.error('[ImageLoader] 刷新流放数据失败:', error);
        }
    },

    /**
     * 2025-01-27 新增：暂时禁用滚动事件监听器，防止卡片移除时触发页面跳转
     */
    _temporarilyDisableScrollHandlers() {
        if (this._scrollHandlerLoadMore && this._scrollHandlerPreload) {
            // 移除滚动事件监听器
            window.removeEventListener('scroll', this._scrollHandlerLoadMore);
            window.removeEventListener('scroll', this._scrollHandlerPreload);
            
            console.log('[ImageLoader] 已暂时禁用滚动监听器');
        }
    },

    /**
     * 2025-01-27 新增：恢复滚动事件监听器
     */
    _restoreScrollHandlers() {
        if (this._scrollHandlerLoadMore && this._scrollHandlerPreload) {
            // 重新绑定滚动事件监听器
            window.addEventListener('scroll', this._scrollHandlerLoadMore);
            window.addEventListener('scroll', this._scrollHandlerPreload);
            
            console.log('[ImageLoader] 已恢复滚动监听器');
        }
    },

    /**
     * SEO优化：生成更描述性的图片alt属性
     */
    generateImageAltText(wallpaper) {
        const parts = [];
        
        // 基础名称
        if (wallpaper.name) {
            parts.push(wallpaper.name);
        }
        
        // 添加分类信息
        if (wallpaper.category && wallpaper.category !== '全部') {
            parts.push(`${wallpaper.category}壁纸`);
        }
        
        // 添加尺寸信息
        if (wallpaper.width && wallpaper.height) {
            parts.push(`${wallpaper.width}x${wallpaper.height}`);
        }
        
        // 添加通用描述
        parts.push('高清壁纸');
        parts.push('免费下载');
        
        return parts.join(' - ');
    },

    /**
     * 2024-07-28 新增：处理流放图片视图
     */
    async _handleExiledListView() {
        if (this.state.currentDisplayMode === 'exiled_list') return; // 如果已经在流放列表视图，则不重复加载

        // 2025-01-27 修复：切换到流放视图时清空搜索状态
        this._clearSearchState();
        
        // 2025-01-27 优化：使用统一的状态重置逻辑
        this._resetViewState();
        this.state.currentDisplayMode = 'exiled_list';
        
        // 新增：保存视图状态
        this._saveViewState();
        
        // 新增：更新页面标题
        this._updatePageTitle();

        // 2024-12-19 修复：更新容器CSS类为流放视图专用样式
        const container = document.getElementById('wallpaper-container');
        if (container) {
            container.className = 'exiled-view-grid min-h-[400px]';
        }

        // 禁用网格和列表视图按钮，高亮流放视图按钮
        const gridBtn = document.getElementById('grid-view-btn');
        const listBtn = document.getElementById('list-view-btn');
        const exiledBtn = document.getElementById('exiled-list-view-btn');

        if (gridBtn) gridBtn.className = 'p-2 rounded bg-white hover:bg-neutral-dark transition-colors';
        if (listBtn) listBtn.className = 'p-2 rounded bg-white hover:bg-neutral-dark transition-colors';
        if (exiledBtn) exiledBtn.className = 'p-2 rounded bg-red-100 border-2 border-red-300 text-red-600 shadow-lg hover:bg-red-200 transition-all';

        // 2025-01-27 修复：重新从API加载流放数据，确保显示所有流放图片
        try {
            await this.loadWallpaperDataFromAPI(); // 重新加载数据，会自动添加exile_status=exiled参数
            this.filterWallpapers(); // 过滤数据
            await this.renderWallpapers(false); // 不追加，清空并重新渲染
            this.updateLoadMoreButton(); // 更新加载更多按钮状态
            
            console.log(`[ImageLoader] 流放视图加载完成，共显示 ${this.state.filteredWallpapers.length} 张流放图片`);
        } catch (error) {
            console.error('[ImageLoader] 加载流放数据失败:', error);
            this.showErrorMessage('加载流放数据失败，请重试');
        }
    },
    
    /**
     * 新增：高亮流放视图按钮
     */
    _highlightExiledViewButton() {
        const gridBtn = document.getElementById('grid-view-btn');
        const listBtn = document.getElementById('list-view-btn');
        const exiledBtn = document.getElementById('exiled-list-view-btn');

        if (gridBtn) gridBtn.className = 'p-2 rounded bg-white hover:bg-neutral-dark transition-colors';
        if (listBtn) listBtn.className = 'p-2 rounded bg-white hover:bg-neutral-dark transition-colors';
        if (exiledBtn) exiledBtn.className = 'p-2 rounded bg-red-100 border-2 border-red-300 text-red-600 shadow-lg hover:bg-red-200 transition-all';
        
        console.log('[ImageLoader] 已高亮流放视图按钮');
    },
    
    /**
     * 新增：更新页面标题
     */
    _updatePageTitle() {
        const titleElement = document.querySelector('h2');
        if (!titleElement) return;
        
        let newTitle = '';
        
        if (this.state.currentDisplayMode === 'exiled_list') {
            newTitle = '流放视图';
        } else if (this.state.currentDisplayMode === 'favorites_only') {
            newTitle = '我的收藏';
        } else {
            newTitle = '热门壁纸';
        }
        
        titleElement.textContent = newTitle;
        console.log(`[ImageLoader] 页面标题已更新为: ${newTitle}`);
    }
};

// 导出模块
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ImageLoader;
}

// 全局暴露
window.ImageLoader = ImageLoader;
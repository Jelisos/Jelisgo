/**
 * 文件: static/js/wallpaper-detail.js
 * 描述: 壁纸详情显示模块
 * 依赖: image-compressor.js, image-token-manager.js
 * 维护: 负责壁纸详情模态框的显示和交互
 */

/**
 * 壁纸详情模块
 * 负责壁纸详情的显示、下载和分享功能
 */
const WallpaperDetail = {
    // 当前显示的壁纸
    currentWallpaper: null,
    
    // 模态框元素
    modal: null,
    modalContent: null,
    
    /**
     * 初始化壁纸详情模块
     */
    init() {
        // 获取模态框元素
        this.modal = document.getElementById('wallpaper-detail-modal');
        this.modalContent = document.getElementById('wallpaper-detail-modal-content');
        
        if (!this.modal || !this.modalContent) {
            console.error('[WallpaperDetail] 找不到壁纸详情模态框元素');
            return;
        }
        
        // 绑定事件
        this.bindEvents();
    },
    
    /**
     * 绑定事件监听器
     */
    bindEvents() {
        // 监听壁纸详情显示事件
        document.addEventListener('wallpaper-detail-show', (event) => {
            this.showDetail(event.detail);
        });
        
        // 关闭按钮
        const closeBtn = document.getElementById('close-detail-modal');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.hideDetail());
        }
        
        // 点击模态框背景关闭
        this.modal.addEventListener('click', (event) => {
            if (event.target === this.modal) {
                this.hideDetail();
            }
        });
        
        // ESC键关闭
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !this.modal.classList.contains('hidden')) {
                this.hideDetail();
            }
        });
        
        // 2024-07-28 修复: 下载按钮改为下载原始图片
        // 默认下载按钮绑定事件
        const downloadBtn = document.getElementById('download-btn');
        if (downloadBtn) {
            downloadBtn.addEventListener('click', async () => {
                if (!this.currentWallpaper || !this.currentWallpaper.path) {
                    console.warn('[WallpaperDetail] 没有当前壁纸信息或路径，无法下载。');
                    return;
                }

                const originalButtonText = downloadBtn.innerHTML;
                downloadBtn.innerHTML = '<span>下载中...</span>';
                downloadBtn.disabled = true;

                try {
                    let imageUrlToDownload;
                    
                    // 环境检测：本地使用TOKEN化，线上直接使用file_path
                    if (Utils.isLocalhost()) {
                        // 本地环境：使用TOKEN化下载
                        if (window.ImageTokenManager && this.currentWallpaper.id) {
                            try {
                                imageUrlToDownload = await window.ImageTokenManager.buildTokenizedUrl(
                                    this.currentWallpaper.id, 
                                    'original', 
                                    { 
                                        quality: 85,
                                        download: true,
                                        imagePath: this.currentWallpaper.path
                                    }
                                );
                                console.log('[WallpaperDetail] 本地环境使用TOKEN化下载URL:', imageUrlToDownload);
                            } catch (error) {
                                console.warn('[WallpaperDetail] 本地环境TOKEN化下载失败，使用原始路径:', error);
                                imageUrlToDownload = this.currentWallpaper.path;
                            }
                        } else {
                            imageUrlToDownload = this.currentWallpaper.path;
                        }
                    } else {
                        // 线上环境：直接使用wallpapers表的file_path
                        imageUrlToDownload = this.currentWallpaper.path;
                        console.log('[WallpaperDetail] 线上环境直接使用原图路径:', imageUrlToDownload);
                    }
                    
                    // 类型检查：确保返回值是字符串
                    if (typeof imageUrlToDownload !== 'string' || !imageUrlToDownload) {
                        console.warn('[WallpaperDetail] 下载URL无效，使用原始路径');
                        imageUrlToDownload = this.currentWallpaper.path;
                    } 
                    
                    const link = document.createElement('a');
                    link.href = imageUrlToDownload;
                    // 确保文件名包含原始扩展名
                    // 后端返回的 name 字段是文件名，path 是路径
                    const filename = this.currentWallpaper.name || 'wallpaper';
                    const originalExtMatch = imageUrlToDownload.match(/\.([0-9a-z]+)(?:[?#]|$)/i); // 匹配文件扩展名
                    const originalExt = originalExtMatch ? originalExtMatch[1] : 'jpg'; // 默认jpg
                    
                    link.download = `${filename}.${originalExt}`;
                    
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);

                    if (imageUrlToDownload.startsWith('blob:')) {
                        URL.revokeObjectURL(imageUrlToDownload);
                    }
                    downloadBtn.innerHTML = '<span>下载完成!</span>';
                    downloadBtn.classList.remove('bg-primary');
                    downloadBtn.classList.add('bg-green-500');
                    setTimeout(() => {
                        downloadBtn.innerHTML = originalButtonText;
                        downloadBtn.classList.remove('bg-green-500');
                        downloadBtn.classList.add('bg-primary');
                        downloadBtn.disabled = false;
                    }, 1500);

                } catch (error) {
                    console.error('[WallpaperDetail] 下载原始图片失败:', error);
                    alert('下载失败，请稍后重试');
                    downloadBtn.innerHTML = '<span>下载失败!</span>';
                    downloadBtn.classList.remove('bg-primary');
                    downloadBtn.classList.add('bg-red-500');
                    setTimeout(() => {
                        downloadBtn.innerHTML = originalButtonText;
                        downloadBtn.classList.remove('bg-red-500');
                        downloadBtn.classList.add('bg-primary');
                        downloadBtn.disabled = false;
                    }, 1500);
                }
            });
        } else {
            console.warn('[WallpaperDetail] 无法找到下载按钮元素 (ID: download-btn) 在 bindEvents 中。');
        }
        
        // 2024-07-25 修复：预览按钮链接到yulan.php并传递壁纸ID，让yulan.php直接从数据库获取原图路径
        const previewBtn = document.getElementById('preview-btn');
        if (previewBtn) {
            previewBtn.addEventListener('click', async () => {
                if (this.currentWallpaper && this.currentWallpaper.id) {
                    // 直接传递壁纸ID，让yulan.php从数据库获取原图路径
                    console.log('[WallpaperDetail] 传递壁纸ID到预览页:', this.currentWallpaper.id);
                    const yulanUrl = `yulan.php?id=${this.currentWallpaper.id}`;
                    window.open(yulanUrl, '_blank');
                } else {
                    console.warn('[WallpaperDetail] 没有当前壁纸信息或ID，无法预览。');
                }
            });
        } else {
            console.warn('[WallpaperDetail] 无法找到预览按钮元素 (ID: preview-btn) 在 bindEvents 中。');
        }
        
        // 点赞功能已删除
        
        // 收藏按钮
        const favoriteBtn = document.getElementById('favorite-btn');
        if (favoriteBtn) {
            favoriteBtn.addEventListener('click', () => this._handleFavoriteClick());
        }
        
        // 设置壁纸按钮
        const setWallpaperBtn = document.getElementById('set-wallpaper-btn');
        if (setWallpaperBtn) {
            setWallpaperBtn.addEventListener('click', () => this.setAsWallpaper());
        }

        // 点赞同步事件监听已删除

        // 2024-07-27 新增：监听壁纸卡片收藏状态变化事件，以同步详情页状态
        document.addEventListener('wallpaper-favorite-status-changed', (event) => {
            const { wallpaperId, action } = event.detail;
            if (this.currentWallpaper && this.currentWallpaper.id === wallpaperId) {
                const favoriteIcon = document.getElementById('favorite-icon'); // 获取详情页的收藏图标
                const favoriteBtn = document.getElementById('favorite-btn'); // 获取详情页的收藏按钮
                if (favoriteIcon) {
                    if (action === 'favorited') {
                        favoriteIcon.src = 'static/icons/fa-star.svg'; // 收藏后的图标
                        favoriteIcon.classList.add('favorited');
                        if (favoriteBtn) favoriteBtn.classList.add('favorited');
                    } else if (action === 'unfavorited') {
                        favoriteIcon.src = 'static/icons/fa-star-o.svg'; // 取消收藏后的图标
                        favoriteIcon.classList.remove('favorited');
                        if (favoriteBtn) favoriteBtn.classList.remove('favorited');
                    }
                }
            }
        });

        // 2024-07-28 新增: 复制提示词按钮事件
        const copyPromptBtn = document.getElementById('copy-prompt-btn');
        if (copyPromptBtn) {
            copyPromptBtn.addEventListener('click', () => this.copyPromptContent());
        }

        // 分享按钮事件
        const shareBtn = document.getElementById('share-btn');
        if (shareBtn) {
            shareBtn.addEventListener('click', () => this.showShareModal());
        }
        
        // 详情页按钮事件
        const detailPageBtn = document.getElementById('detail-page-btn');
        if (detailPageBtn) {
            detailPageBtn.addEventListener('click', () => this.openDetailPage());
        }
    },
    
    /**
     * 显示壁纸详情
     * @param {Object} wallpaper - 壁纸对象，至少包含 id 字段
     */
    async showDetail(wallpaper) {
        if (!wallpaper || !wallpaper.id) {
            console.error('[WallpaperDetail] 壁纸数据或ID为空');
            return;
        }

        // 2024-07-29 修复: 确保每次打开详情页时图片状态被重置并显示加载指示器
        const detailImage = document.getElementById('detail-image');
        if (detailImage) {
            detailImage.src = ''; // 清空图片src
            detailImage.style.opacity = '0'; // 隐藏图片
            detailImage.style.transition = 'opacity 300ms'; // 重新设置过渡效果
        }
        this.showLoadingState(); // 立即显示加载指示器

        const wallpaperId = wallpaper.id;
        console.log('[WallpaperDetail] showDetail: 正在请求壁纸ID:', wallpaperId);
        // 2024-07-30 调试: 记录传入的wallpaperId类型
        console.log('[WallpaperDetail] showDetail: wallpaperId 的类型:', typeof wallpaperId, '值为:', wallpaperId);

        try {
            // 2024-07-24 修复：立即显示模态框，避免内容加载时阻塞
            this.modal.classList.remove('hidden');
            this.modal.classList.add('show');
            
            // 2024-07-25 修复：防止页面抖动，动态设置body的padding-right CSS变量并添加类
            const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
            if (scrollbarWidth > 0) {
                document.documentElement.style.setProperty('--scrollbar-width', `${scrollbarWidth}px`);
            }
            document.body.classList.add('modal-open');

            // 3. 在下一帧添加内容动画类，确保浏览器有时间应用初始状态
            requestAnimationFrame(() => {
                void this.modalContent.offsetWidth;
                this.modalContent.classList.add('show');
            });

            // 2024-07-28 新增：显示加载状态并清空旧内容
            // this.clearModalContent(); // 此处清空内容，但不在这里隐藏加载状态，留给updateModalContent处理

            // 从后端接口获取壁纸详情数据
            const response = await this._fetchJson(`api/wallpaper_detail.php?id=${wallpaperId}`, 'GET');

            if (response.code === 0 && response.data) {
                this.currentWallpaper = response.data; // 设置当前壁纸信息为后端返回的完整数据
                
                // 2024-07-30 修复: 记录壁纸查看次数，并在成功时更新当前壁纸对象的views字段
                const recordViewResponse = await this._recordWallpaperView(wallpaperId); 
                if (recordViewResponse && recordViewResponse.code === 0 && recordViewResponse.msg === '查看记录成功') {
                    this.currentWallpaper.views = parseInt(this.currentWallpaper.views || '0') + 1;
                }

                // 更新模态框内容 (此时this.currentWallpaper.views已经是最新值)
                await this.updateModalContent(this.currentWallpaper); 
                
                // 更新收藏UI
                await this._updateFavoriteUI(wallpaperId);
            } else {
                console.error('[WallpaperDetail] 获取壁纸详情失败:', response.msg || '未知错误');
                this.hideDetail(); // 获取失败则关闭弹窗
                alert('获取壁纸详情失败，请稍后重试。原因: ' + (response.msg || '未知错误'));
            }

        } catch (error) {
            console.error('[WallpaperDetail] 显示详情或请求数据失败:', error);
            this.hideDetail(); // 异常则关闭弹窗
            alert('加载壁纸详情时发生错误，请稍后重试。' + error.message || error);
        } finally {
            this.hideLoadingState(); // 隐藏加载状态
        }
    },
    
    /**
     * 隐藏壁纸详情
     */
    hideDetail() {
        // 2024-07-24 修复：使用CSS类控制模态框隐藏和动画
        // 1. 移除内容和模态框的show类，触发隐藏动画
        this.modalContent.classList.remove('show');
        this.modal.classList.remove('show');
        
        // 2. 动画结束后完全隐藏模态框
        setTimeout(() => {
            this.modal.classList.add('hidden');
            // 2024-07-25 修复：移除body的modal-open类并清除CSS变量
            document.body.classList.remove('modal-open');
            document.documentElement.style.removeProperty('--scrollbar-width');
            document.body.style.overflow = ''; // 恢复默认overflow，防止其他样式干扰
            this.currentWallpaper = null;
        }, 300); // 与CSS过渡时间（300ms）保持一致
    },
    
    /**
     * 更新模态框内容
     * @param {Object} wallpaper - 壁纸对象
     */
    async updateModalContent(wallpaper) {
        if (!wallpaper) {
            console.warn('[WallpaperDetail] updateModalContent: 壁纸数据为空。');
            return;
        }

        // 2024-07-29 修复: 使用Image对象预加载，确保加载完成后隐藏加载指示器
        const detailImage = document.getElementById('detail-image');
        const imageUrl = await this._getDisplayImagePath(wallpaper.path, wallpaper.id);

        if (detailImage) {
            const tempImage = new Image();
            tempImage.src = imageUrl;

            tempImage.onload = () => {
                detailImage.src = imageUrl;
                detailImage.style.opacity = '1';
                this.hideLoadingState(); // 图片加载成功后隐藏加载指示器
                console.log('[WallpaperDetail] 图片加载成功:', imageUrl);
            };

            tempImage.onerror = () => {
                console.error('[WallpaperDetail] 图片加载失败:', imageUrl);
                detailImage.src = 'static/images/image-load-error.png'; // 显示错误图片
                detailImage.style.opacity = '1';
                this.hideLoadingState(); // 图片加载失败后也隐藏加载指示器
                alert('壁纸图片加载失败，请检查网络或图片源。');
            };

            // 如果图片已被浏览器缓存，onload可能不会触发，手动检查并触发
            if (tempImage.complete && tempImage.naturalWidth !== 0) {
                tempImage.onload(); // 立即调用 onload 逻辑
            }
        }

        document.getElementById('detail-title').textContent = wallpaper.name || '未知标题';
        document.getElementById('detail-file-size').textContent = this.formatFileSize(wallpaper.file_size);
        document.getElementById('detail-dimensions').textContent = `${wallpaper.width}x${wallpaper.height}`;
        // 2024-07-30 修复: 确保格式和上传时间正确显示
        document.getElementById('detail-format').textContent = wallpaper.format ? wallpaper.format.toUpperCase() : '未知';
        document.getElementById('detail-upload-time').textContent = wallpaper.upload_time ? this.formatDate(wallpaper.upload_time) : '未知';

        const categoryElement = document.getElementById('detail-category');
        if (categoryElement) {
            categoryElement.textContent = wallpaper.category_name || '未分类';
            // 2024-07-25 修复：动态设置分类颜色
            categoryElement.className = `ml-2 px-2 py-1 rounded-full text-sm ${this.getCategoryColorClass(wallpaper.category_name)}`;
        }

        const detailTitle = document.getElementById('detail-title');
        const detailDimensions = document.getElementById('detail-dimensions');
        const detailCategory = document.getElementById('detail-category');
        const detailTags = document.getElementById('detail-tags');
        const detailFileSize = document.getElementById('detail-file-size');
        const detailFormat = document.getElementById('detail-format');
        const detailUploadTime = document.getElementById('detail-upload-time');
        // detailLikes 已删除
        const detailViews = document.getElementById('detail-views');
        const promptContent = document.getElementById('prompt-content');
        
        // 2024-07-28 修复：将所有文本内容和标签更新推迟到下一帧，避免渲染阻塞
        requestAnimationFrame(() => {
            // 更新标题
            if (detailTitle) {
                detailTitle.textContent = wallpaper.name || '未知壁纸';
            }
            
            // 更新格式
            if (detailFormat) {
                detailFormat.textContent = wallpaper.format || '-';
            }

            // 更新上传时间
            if (detailUploadTime) {
                detailUploadTime.textContent = this.formatDate(wallpaper.upload_time) || '-';
            }

            // 更新分类
            if (detailCategory) {
                detailCategory.textContent = wallpaper.category || '未分类';
            }
            
            // 更新标签
            if (detailTags) {
                // 确保 tags 是一个数组，即使后端返回空字符串或null
                const tagsArray = Array.isArray(wallpaper.tags) ? wallpaper.tags : (wallpaper.tags ? wallpaper.tags.split(',') : []);
                detailTags.innerHTML = tagsArray.filter(tag => tag.trim() !== '').map(tag => 
                    `<span class="tag-item px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs mr-2">${this.escapeHtml(tag)}</span>`
                ).join('');
                if (tagsArray.length === 0) { // 如果没有标签，显示默认文本
                    detailTags.textContent = '暂无标签';
                }
            }

            // 更新文件大小
            if (detailFileSize) {
                detailFileSize.textContent = wallpaper.size || '-'; // 2024-07-28 修复: 直接使用后端提供的格式化字符串
            }

            // 更新查看统计
            if (detailViews) {
                detailViews.textContent = wallpaper.views !== undefined ? wallpaper.views : '0';
            }
            // 点赞统计已删除

            // 更新AI生图提示词 (此行将被移除，因为由 loadWallpaperPrompt 统一处理)
            // if (promptContent) {
            //     promptContent.textContent = wallpaper.prompt || '暂无提示词信息';
            // }
        });

        // 2024-07-28 新增: 加载提示词（需在更新UI后调用，确保DOM元素存在）
        this.loadWallpaperPrompt(wallpaper.id);
    },
    
    /**
     * 更新收藏按钮的UI状态
     * @param {number} wallpaperId - 壁纸ID
     */
    async _updateFavoriteUI(wallpaperId) {
        console.log('[WallpaperDetail] _updateFavoriteUI: 传入壁纸ID:', wallpaperId);
        await this._checkUserFavoriteStatus(wallpaperId);
    },
    
    /**
     * 检查用户收藏状态并更新UI
     * @param {number} wallpaperId - 壁纸ID
     */
    async _checkUserFavoriteStatus(wallpaperId) {
        const favoriteBtn = document.getElementById('favorite-btn');
        const favoriteIcon = document.getElementById('favorite-icon');
        const favoriteText = document.getElementById('favorite-text');

        if (!favoriteBtn || !favoriteIcon || !favoriteText) {
            console.warn('[WallpaperDetail] _checkUserFavoriteStatus: 无法找到收藏按钮相关元素。');
            return;
        }
        
        console.log('[WallpaperDetail] _checkUserFavoriteStatus: 检查壁纸ID:', wallpaperId);
        
        // 2025-02-01 优化：优先使用ImageLoader中的缓存数据，避免重复请求
        if (window.ImageLoader && window.ImageLoader.state.favoritesLoaded) {
            const isFavorited = window.ImageLoader.state.userFavorites.has(parseInt(wallpaperId));
            console.log('[WallpaperDetail] 使用ImageLoader缓存数据，收藏状态:', isFavorited);
            
            if (isFavorited) {
                favoriteIcon.src = 'static/icons/fa-star.svg';
                favoriteIcon.classList.add('favorited');
                favoriteText.textContent = '收藏';
                favoriteBtn.classList.add('favorited');
            } else {
                favoriteIcon.src = 'static/icons/fa-star-o.svg';
                favoriteIcon.classList.remove('favorited');
                favoriteText.textContent = '收藏';
                favoriteBtn.classList.remove('favorited');
            }
            return;
        }
        
        // 如果ImageLoader数据未加载，则请求API
        try {
            const response = await this._fetchJson('api/my_favorites.php', 'GET');
            console.log('[WallpaperDetail] _checkUserFavoriteStatus: my_favorites.php 响应:', response);

            if (response.code === 0 && response.data) {
                // 2024-07-16 修复：确保favWallpaper.id与wallpaperId类型一致，都转为整数进行比较
                const isFavorited = response.data.some(favWallpaper => {
                    const parsedFavId = parseInt(favWallpaper.id);
                    return parsedFavId === parseInt(wallpaperId);
                });
                console.log('[WallpaperDetail] _checkUserFavoriteStatus: isFavorited:', isFavorited, '壁纸ID:', wallpaperId);
                if (isFavorited) {
                    favoriteIcon.src = 'static/icons/fa-star.svg';
                    favoriteIcon.classList.add('favorited');
                    favoriteText.textContent = '收藏';
                    favoriteBtn.classList.add('favorited');
                } else {
                    favoriteIcon.src = 'static/icons/fa-star-o.svg';
                    favoriteIcon.classList.remove('favorited');
                    favoriteText.textContent = '收藏';
                    favoriteBtn.classList.remove('favorited');
                }
            } else if (response.code === 401) {
                // User not logged in, reset to default '收藏' state
                favoriteIcon.src = 'static/icons/fa-star-o.svg';
                favoriteIcon.classList.remove('favorited');
                favoriteText.textContent = '收藏';
                favoriteBtn.classList.remove('favorited');
            } else {
                favoriteText.textContent = '收藏'; // 恢复默认文本
            }
        } catch (error) {
            favoriteText.textContent = '收藏'; // 恢复默认文本
        }
    },
    
    // _checkUserLikeStatus 函数已删除

    // _handleLikeClick 函数已删除
    
    /**
     * 处理收藏/取消收藏点击事件
     */
    async _handleFavoriteClick() {
        if (!this.currentWallpaper || !this.currentWallpaper.id) {
            console.warn('[WallpaperDetail] _handleFavoriteClick: 无法收藏：缺少壁纸ID。');
            return;
        }

        const favoriteBtn = document.getElementById('favorite-btn');
        const favoriteIcon = document.getElementById('favorite-icon');
        const favoriteText = document.getElementById('favorite-text');
        
        if (favoriteBtn) {
            favoriteBtn.disabled = true;
        }

        console.log('[WallpaperDetail] _handleFavoriteClick: 尝试收藏/取消收藏壁纸ID:', this.currentWallpaper.id);
        try {
            const response = await this._fetchJson('api/toggle_favorite.php', 'POST', {
                wallpaper_id: this.currentWallpaper.id
            });

            console.log('[WallpaperDetail] _handleFavoriteClick: toggle_favorite.php 响应:', response);

            if (response.code === 0) {
                const wallpaperId = parseInt(this.currentWallpaper.id);
                
                // 根据返回的 action 更新UI
                if (response.action === 'favorited') {
                    favoriteIcon.src = 'static/icons/fa-star.svg'; // 变为实心
                    favoriteIcon.classList.add('favorited'); // 添加favorited类以应用绿色样式
                    favoriteText.textContent = '收藏'; // 更新文本
                    favoriteBtn.classList.add('favorited'); // 添加一个类来标记已收藏状态
                    
                    // 2025-02-01 新增：同步更新ImageLoader中的收藏状态
                    if (window.ImageLoader && window.ImageLoader.state.userFavorites) {
                        window.ImageLoader.state.userFavorites.add(wallpaperId);
                        console.log('[WallpaperDetail] 已添加到ImageLoader收藏列表:', wallpaperId);
                    }
                } else if (response.action === 'unfavorited') {
                    favoriteIcon.src = 'static/icons/fa-star-o.svg'; // 变为空心
                    favoriteIcon.classList.remove('favorited'); // 移除favorited类
                    favoriteText.textContent = '收藏'; // 更新文本
                    favoriteBtn.classList.remove('favorited'); // 移除已收藏状态类
                    
                    // 2025-02-01 新增：同步更新ImageLoader中的收藏状态
                    if (window.ImageLoader && window.ImageLoader.state.userFavorites) {
                        window.ImageLoader.state.userFavorites.delete(wallpaperId);
                        console.log('[WallpaperDetail] 已从ImageLoader收藏列表移除:', wallpaperId);
                    }
                }
                
                // 2024-07-26 新增：收藏状态改变后，派发自定义事件通知其他模块
                document.dispatchEvent(new CustomEvent('wallpaper-favorite-status-changed', {
                    detail: {
                        wallpaperId: this.currentWallpaper.id,
                        action: response.action
                    }
                }));

            } else if (response.code === 401) {
                alert('收藏需要登录，请先登录！');
                favoriteText.textContent = '收藏'; // 2024-07-26 修复：未登录时恢复按钮文本
            } else {
                console.error('[WallpaperDetail] _handleFavoriteClick: 收藏/取消收藏失败:', response.msg);
                alert(`操作失败: ${response.msg}`);
            }
        } catch (error) {
            console.error('[WallpaperDetail] _handleFavoriteClick: 收藏/取消收藏请求错误:', error);
            alert('网络请求失败，请稍后重试。');
        } finally {
            if (favoriteBtn) {
                favoriteBtn.disabled = false;
            }
        }
    },
    
    /**
     * 显示分享选择模态框
     */
    showShareModal() {
        if (!this.currentWallpaper) return;
        
        // 创建分享模态框HTML
        const shareModalHTML = `
            <div id="share-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg p-6 max-w-sm w-full mx-4">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">分享壁纸</h3>
                        <button id="close-share-modal" class="text-gray-500 hover:text-gray-700">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="grid grid-cols-3 gap-4 mb-4">
                        <button id="share-wechat" class="flex flex-col items-center p-3 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center mb-2">
                                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8.5 12c-.8 0-1.5-.7-1.5-1.5s.7-1.5 1.5-1.5 1.5.7 1.5 1.5-.7 1.5-1.5 1.5zm7 0c-.8 0-1.5-.7-1.5-1.5s.7-1.5 1.5-1.5 1.5.7 1.5 1.5-.7 1.5-1.5 1.5zm-3.5-9C6.5 3 2 6.6 2 11.1c0 2.4 1.2 4.5 3.2 5.9-.2-.6-.3-1.3-.3-2 0-4.4 4-8 9-8 .3 0 .6 0 .9.1C13.8 4.8 11.2 3 8 3zm7.5 6c-3.9 0-7 2.7-7 6s3.1 6 7 6c.8 0 1.6-.1 2.3-.3l2.2 1.3-.6-2.2c1.3-1 2.1-2.4 2.1-4.1 0-3.3-3.1-6.7-6-6.7z"/>
                                </svg>
                            </div>
                            <span class="text-sm">微信</span>
                        </button>
                        <button id="share-qq" class="flex flex-col items-center p-3 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center mb-2">
                                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm3.5 14.5c-.8.8-2.1 1.3-3.5 1.3s-2.7-.5-3.5-1.3c-.2-.2-.2-.5 0-.7s.5-.2.7 0c.6.6 1.4 1 2.8 1s2.2-.4 2.8-1c.2-.2.5-.2.7 0s.2.5 0 .7zM9 11c-.6 0-1-.4-1-1s.4-1 1-1 1 .4 1 1-.4 1-1 1zm6 0c-.6 0-1-.4-1-1s.4-1 1-1 1 .4 1 1-.4 1-1 1z"/>
                                </svg>
                            </div>
                            <span class="text-sm">QQ</span>
                        </button>
                        <button id="share-doubao" class="flex flex-col items-center p-3 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="w-12 h-12 bg-purple-500 rounded-full flex items-center justify-center mb-2">
                                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                </svg>
                            </div>
                            <span class="text-sm">豆包</span>
                        </button>
                    </div>
                    <div class="border-t pt-4">
                        <button id="share-copy-link" class="w-full py-2 px-4 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                            复制链接
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        // 添加到页面
        document.body.insertAdjacentHTML('beforeend', shareModalHTML);
        
        // 绑定事件
        this.bindShareModalEvents();
    },

    /**
     * 绑定分享模态框事件
     */
    bindShareModalEvents() {
        const shareModal = document.getElementById('share-modal');
        const closeBtn = document.getElementById('close-share-modal');
        const wechatBtn = document.getElementById('share-wechat');
        const qqBtn = document.getElementById('share-qq');
        const doubaoBtn = document.getElementById('share-doubao');
        const copyLinkBtn = document.getElementById('share-copy-link');
        
        // 关闭模态框
        const closeModal = () => {
            if (shareModal) {
                shareModal.remove();
            }
        };
        
        if (closeBtn) {
            closeBtn.addEventListener('click', closeModal);
        }
        
        // 点击背景关闭
        if (shareModal) {
            shareModal.addEventListener('click', (e) => {
                if (e.target === shareModal) {
                    closeModal();
                }
            });
        }
        
        // 微信分享
        if (wechatBtn) {
            wechatBtn.addEventListener('click', () => {
                this.shareToWechat();
                closeModal();
            });
        }
        
        // QQ分享
        if (qqBtn) {
            qqBtn.addEventListener('click', () => {
                this.shareToQQ();
                closeModal();
            });
        }
        
        // 豆包分享
        if (doubaoBtn) {
            doubaoBtn.addEventListener('click', () => {
                this.shareToDoubao();
                closeModal();
            });
        }
        
        // 复制链接
        if (copyLinkBtn) {
            copyLinkBtn.addEventListener('click', () => {
                this.copyShareLink();
                closeModal();
            });
        }
    },

    /**
     * 分享到微信
     */
    shareToWechat() {
        if (!this.currentWallpaper) return;
        
        const shareUrl = window.location.href;
        const title = this.currentWallpaper.name;
        const desc = `分享一张精美壁纸：${title}`;
        
        // 检测是否在微信环境
        if (navigator.userAgent.toLowerCase().includes('micromessenger')) {
            // 在微信内，提示用户使用右上角分享
            alert('请点击右上角"..."按钮分享到朋友圈或发送给朋友');
        } else {
            // 不在微信内，生成二维码或复制链接
            this.showQRCode(shareUrl, '微信扫码分享');
        }
    },

    /**
     * 分享到QQ
     */
    shareToQQ() {
        if (!this.currentWallpaper) return;
        
        const shareUrl = encodeURIComponent(window.location.href);
        const title = encodeURIComponent(this.currentWallpaper.name);
        const desc = encodeURIComponent(`分享一张精美壁纸：${this.currentWallpaper.name}`);
        
        // QQ分享链接
        const qqShareUrl = `https://connect.qq.com/widget/shareqq/index.html?url=${shareUrl}&title=${title}&desc=${desc}&summary=${desc}&site=壁纸网站`;
        
        // 打开QQ分享窗口
        window.open(qqShareUrl, '_blank', 'width=600,height=400');
    },

    /**
     * 分享到豆包
     */
    shareToDoubao() {
        if (!this.currentWallpaper) return;
        
        const shareText = `发现了一张很棒的壁纸：${this.currentWallpaper.name}\n${window.location.href}`;
        
        // 复制分享文本到剪贴板
        navigator.clipboard.writeText(shareText).then(() => {
            alert('分享内容已复制到剪贴板，请打开豆包APP粘贴分享');
        }).catch(() => {
            // 降级方案：显示分享文本
            prompt('请复制以下内容到豆包APP分享：', shareText);
        });
    },

    /**
     * 复制分享链接
     */
    copyShareLink() {
        const shareUrl = window.location.href;
        
        navigator.clipboard.writeText(shareUrl).then(() => {
            alert('链接已复制到剪贴板');
        }).catch(() => {
            // 降级方案
            prompt('请复制以下链接：', shareUrl);
        });
    },

    /**
     * 显示二维码
     */
    showQRCode(url, title = '扫码分享') {
        // 检查是否有二维码库
        if (typeof QRious !== 'undefined') {
            const qrModalHTML = `
                <div id="qr-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div class="bg-white rounded-lg p-6 max-w-sm w-full mx-4 text-center">
                        <h3 class="text-lg font-semibold mb-4">${title}</h3>
                        <canvas id="qr-canvas" class="mx-auto mb-4"></canvas>
                        <button id="close-qr-modal" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">关闭</button>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', qrModalHTML);
            
            // 生成二维码
            const qr = new QRious({
                element: document.getElementById('qr-canvas'),
                value: url,
                size: 200
            });
            
            // 绑定关闭事件
            const qrModal = document.getElementById('qr-modal');
            const closeQrBtn = document.getElementById('close-qr-modal');
            
            const closeQrModal = () => {
                if (qrModal) {
                    qrModal.remove();
                }
            };
            
            if (closeQrBtn) {
                closeQrBtn.addEventListener('click', closeQrModal);
            }
            
            if (qrModal) {
                qrModal.addEventListener('click', (e) => {
                    if (e.target === qrModal) {
                        closeQrModal();
                    }
                });
            }
        } else {
            // 没有二维码库，直接复制链接
            this.copyShareLink();
        }
    },

    /**
     * 分享壁纸（保留原有方法作为备用）
     */
    shareWallpaper() {
        if (!this.currentWallpaper) return;
        
        const shareData = {
            title: this.currentWallpaper.name,
            text: `分享一张精美壁纸：${this.currentWallpaper.name}`,
            url: window.location.href
        };
        
        if (navigator.share) {
            // 使用原生分享API
            navigator.share(shareData).catch(error => {});
        } else {
            // 显示分享选择模态框
            this.showShareModal();
        }
    },
    
    /**
     * 设置为壁纸
     */
    setAsWallpaper() {
        if (!this.currentWallpaper) return;
        
        // 这是一个浏览器限制的功能，大多数现代浏览器不支持
        alert('请右键点击图片选择"设为壁纸"或手动下载后设置');
    },
    
    /**
     * 打开独立详情页面
     */
    openDetailPage() {
        if (!this.currentWallpaper || !this.currentWallpaper.id) {
            console.warn('[WallpaperDetail] 没有当前壁纸信息，无法打开详情页');
            return;
        }
        
        // 使用传统的URL格式
        const detailUrl = `wallpaper_detail.php?id=${this.currentWallpaper.id}`;
        window.open(detailUrl, '_blank');
        console.log('[WallpaperDetail] 打开详情页:', detailUrl);
    },
    
    /**
     * 格式化文件大小
     * @param {number} bytes - 字节数
     * @returns {string} 格式化后的文件大小
     */
    formatFileSize(bytes) {
        if (bytes === 0) return '0 B';
        
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    },
    
    /**
     * 格式化日期
     * @param {string} dateString - 日期字符串
     * @returns {string} 格式化后的日期
     */
    formatDate(dateString) {
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('zh-CN', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        } catch (error) {
            return dateString;
        }
    },
    
    /**
     * HTML转义
     * @param {string} text - 要转义的文本
     * @returns {string} 转义后的文本
     */
    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            '\'': '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) {
            return map[m];
        });
    },

    /**
     * 统一的JSON Fetch请求封装
     * @param {string} url - 请求URL
     * @param {string} method - 请求方法 (GET, POST等)
     * @param {Object} [data] - POST请求的数据
     * @returns {Promise<Object>} - JSON响应数据
     * @throws {Error} - 请求失败时抛出错误
     */
    async _fetchJson(url, method, data = null) {
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
            },
        };
        
        // 2024-12-19 新增：添加Authorization头支持统一认证
        const userInfo = JSON.parse(localStorage.getItem('user') || '{}');
        if (userInfo.id) {
            options.headers['Authorization'] = `Bearer ${userInfo.id}`;
        }
        if (data) {
            // For POST requests, convert data to FormData if needed (e.g., for file uploads)
            // For simple JSON, use JSON.stringify
            if (method === 'POST') {
                // 2024-07-30 调试: 记录即将发送的POST请求体
                console.log('[WallpaperDetail] _fetchJson: POST请求体:', JSON.stringify(data));
                options.body = JSON.stringify(data);
            }
        }

        const response = await fetch(url, options);
        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`HTTP error! status: ${response.status}, message: ${errorText}`);
        }
        return response.json();
    },

    /**
     * 触发壁纸详情显示事件
     */
    handleWallpaperClick(wallpaper) {
        // 2024-07-16 调试：打印wallpaperId的类型，以便追踪数据源
        console.log(`[ImageLoader] handleWallpaperClick: 准备显示详情页，壁纸ID: ${wallpaper.id} (类型: ${typeof wallpaper.id})`);
        const event = new CustomEvent('wallpaper-detail-show', {
            detail: wallpaper
        });
        document.dispatchEvent(event);
    },

    /**
     * 清空模态框内容，重置显示状态
     * @private
     */
    clearModalContent() {
        const detailImage = document.getElementById('detail-image');
        const detailTitle = document.getElementById('detail-title');
        const detailFileSize = document.getElementById('detail-file-size');
        const detailDimensions = document.getElementById('detail-dimensions');
        const detailFormat = document.getElementById('detail-format');
        const detailUploadTime = document.getElementById('detail-upload-time');
        const detailCategory = document.getElementById('detail-category');
        const detailTags = document.getElementById('detail-tags');
        const detailViews = document.getElementById('detail-views');
        const promptContent = document.getElementById('prompt-content');

        if (detailImage) detailImage.src = '';
        if (detailTitle) detailTitle.textContent = '';
        if (detailFileSize) detailFileSize.textContent = '';
        if (detailDimensions) detailDimensions.textContent = '';
        if (detailFormat) detailFormat.textContent = '';
        if (detailUploadTime) detailUploadTime.textContent = '';
        if (detailCategory) detailCategory.textContent = '未分类';
        if (detailTags) detailTags.innerHTML = '';
        if (detailViews) detailViews.textContent = '0';
        // 点赞统计清空已删除
        if (promptContent) promptContent.textContent = '暂无提示词信息';

        // 2024-07-29 修复: 确保图片在内容清空时隐藏，加载完成后再显示
        if (detailImage) {
            detailImage.style.opacity = '0';
            detailImage.style.transition = 'none'; // 暂时移除过渡，避免清空时闪烁
        }
    },

    /**
     * 显示图片加载指示器
     * @private
     */
    showLoadingState() {
        const loadingIndicator = document.getElementById('image-loading-indicator');
        if (loadingIndicator) {
            loadingIndicator.classList.remove('hidden');
        }
    },

    /**
     * 隐藏图片加载指示器
     * @private
     */
    hideLoadingState() {
        const loadingIndicator = document.getElementById('image-loading-indicator');
        if (loadingIndicator) {
            loadingIndicator.classList.add('hidden');
        }
    },

    /**
     * 根据原始路径获取显示用的图片路径（使用真实壁纸ID进行Token化访问）
     * @param {string} originalPath - 原始图片路径
     * @param {string|number} wallpaperId - 真实的壁纸ID
     * @returns {Promise<string>} - 返回压缩后的图片路径或原始路径
     * @private
     */
    async _getDisplayImagePath(originalPath, wallpaperId) {
        if (!originalPath) {
            console.warn('[WallpaperDetail] _getDisplayImagePath: 原始路径为空');
            return '';
        }
        
        // 使用真实的壁纸ID进行Token化访问
        const compressedPath = await ImageLoader.getCompressedImageUrl(originalPath, wallpaperId);
        console.log(`[WallpaperDetail] 使用真实壁纸ID加载图片: ID=${wallpaperId}, 路径: ${originalPath} -> ${compressedPath}`);
        return compressedPath || originalPath;
    },

    /**
     * 从图片路径中提取壁纸ID
     * @param {string} imagePath - 图片路径
     * @returns {string|null} - 壁纸ID或null
     * @private
     */
    _extractWallpaperIdFromPath(imagePath) {
        if (!imagePath) return null;
        
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
            console.warn('[WallpaperDetail] 提取壁纸ID失败:', error);
            return null;
        }
    },

    /**
     * 获取分类对应的颜色类名
     * @param {string} categoryName - 分类名称
     * @returns {string} - Tailwind CSS 颜色类名
     */
    getCategoryColorClass(categoryName) {
        const colors = {
            '自然风光': 'bg-green-100 text-green-800',
            '城市建筑': 'bg-blue-100 text-blue-800',
            '抽象艺术': 'bg-purple-100 text-purple-800',
            '动物萌宠': 'bg-yellow-100 text-yellow-800',
            '科技未来': 'bg-indigo-100 text-indigo-800',
            '游戏动漫': 'bg-pink-100 text-pink-800',
            '人物写真': 'bg-red-100 text-red-800',
            '汽车交通': 'bg-gray-200 text-gray-800',
            '美食饮品': 'bg-orange-100 text-orange-800',
            '体育运动': 'bg-teal-100 text-teal-800',
            '简约设计': 'bg-neutral-200 text-neutral-800',
            '卡通插画': 'bg-lime-100 text-lime-800',
            '节日庆典': 'bg-rose-100 text-rose-800',
            '军事历史': 'bg-amber-100 text-amber-800',
            '太空宇宙': 'bg-cyan-100 text-cyan-800',
            '影视娱乐': 'bg-fuchsia-100 text-fuchsia-800',
            '平面设计': 'bg-emerald-100 text-emerald-800',
            '未分类': 'bg-gray-100 text-gray-800'
        };
        return colors[categoryName] || colors['未分类'];
    },

    /**
     * 记录壁纸查看次数
     * @param {number} wallpaperId - 壁纸ID
     * @private
     */
    async _recordWallpaperView(wallpaperId) {
        if (!wallpaperId) {
            console.warn('[WallpaperDetail] _recordWallpaperView: 缺少壁纸ID，无法记录查看。');
            return null; // 返回null以便调用者判断
        }
        // 2024-07-30 调试: 记录发送的数据
        console.log('[WallpaperDetail] _recordWallpaperView: 准备发送的数据:', { wallpaper_id: wallpaperId });
        try {
            const response = await this._fetchJson('api/record_view.php', 'POST', { wallpaper_id: wallpaperId });
            if (response.code === 0) {
                console.log('[WallpaperDetail] 查看记录成功:', response.msg);
            } else {
                console.warn('[WallpaperDetail] 查看记录失败:', response.msg);
            }
            return response; // 返回响应，供调用者检查
        } catch (error) {
            console.error('[WallpaperDetail] 记录查看请求错误:', error);
            return null; // 发生错误时返回null
        }
    },

    /**
     * 加载壁纸提示词
     * @param {string} wallpaperId - 壁纸ID
     */
    async loadWallpaperPrompt(wallpaperId) {
        // 检查用户权限
        if (!window.PermissionManager || !window.PermissionManager.hasAdvancedFeatureAccess()) {
            this.updatePromptUI({ content: '', is_locked: 1, hasPermission: false });
            return;
        }
        
        try {
            const response = await this._fetchJson(`api/wallpaper_prompt.php?id=${wallpaperId}`, 'GET');
            if (response.code === 200 && response.data) {
                this.updatePromptUI({ ...response.data, hasPermission: true });
            } else if (response.code === 200 && response.msg === '暂无提示词') {
                // 暂无提示词，显示空内容并禁用编辑
                this.updatePromptUI({ content: '', is_locked: 1, hasPermission: true });
            } else {
                console.error('[WallpaperDetail] 获取提示词失败:', response.msg);
                // 即使失败也显示空内容
                this.updatePromptUI({ content: '', is_locked: 1, hasPermission: true });
            }
        } catch (error) {
            console.error('[WallpaperDetail] 加载提示词时发生错误:', error);
            this.updatePromptUI({ content: '', is_locked: 1, hasPermission: true });
        }
    },

    /**
     * 更新提示词UI
     * @param {Object} promptData - 提示词数据 {content: string, is_locked: number, hasPermission: boolean}
     */
    updatePromptUI(promptData) {
        const promptTextElement = document.getElementById('prompt-content'); // 修正：对应 HTML 中的 prompt-content
        const promptViewDiv = document.getElementById('prompt-view');
        const promptEditBtnArea = document.getElementById('prompt-edit-btn-area');
        const editPromptBtn = document.getElementById('edit-prompt-btn');
        const toggleLockBtn = document.getElementById('toggle-prompt-lock');
        const promptLockIcon = document.getElementById('prompt-lock-icon');
        const promptLockText = document.getElementById('prompt-lock-text');
        const promptEditDiv = document.getElementById('prompt-edit');
        const promptPermissionDenied = document.getElementById('prompt-permission-denied');

        if (!promptTextElement || !promptViewDiv || !promptEditBtnArea || !editPromptBtn || !toggleLockBtn || !promptLockIcon || !promptLockText || !promptEditDiv) {
            console.warn('[WallpaperDetail] 提示词相关DOM元素未找到。');
            return;
        }

        // 检查权限
        if (promptData.hasPermission === false) {
            // 权限不足，显示权限不足提示
            promptViewDiv.classList.add('hidden');
            promptEditBtnArea.classList.add('hidden');
            toggleLockBtn.classList.add('hidden');
            if (promptPermissionDenied) {
                promptPermissionDenied.classList.remove('hidden');
            }
            return;
        }

        // 有权限，显示正常内容
        promptViewDiv.classList.remove('hidden');
        if (promptPermissionDenied) {
            promptPermissionDenied.classList.add('hidden');
        }

        promptTextElement.innerText = promptData.content || '暂无提示词信息';
        promptLockIcon.src = promptData.is_locked ? 'static/icons/fa-lock.svg' : 'static/icons/fa-unlock.svg';
        promptLockText.innerText = promptData.is_locked ? '已锁定' : '已解锁';

        // 只有管理员才能看到编辑和锁定按钮
        const isAdmin = window.currentUser && window.currentUser.is_admin === 1; // 假设 isAdmin 为 1 表示管理员
        if (isAdmin) {
            promptEditBtnArea.classList.remove('hidden'); // 显示编辑按钮区域
            toggleLockBtn.classList.remove('hidden'); // 显示切换锁定按钮
            
            // 如果提示词被锁定，编辑按钮不可用
            editPromptBtn.disabled = promptData.is_locked;
            editPromptBtn.classList.toggle('opacity-50', promptData.is_locked);
            editPromptBtn.classList.toggle('cursor-not-allowed', promptData.is_locked);
        } else {
            promptEditBtnArea.classList.add('hidden'); // 隐藏编辑按钮区域
            toggleLockBtn.classList.add('hidden'); // 隐藏切换锁定按钮
        }

        // 确保编辑模式是隐藏的，显示查看模式
        promptEditDiv.classList.add('hidden');

        // 绑定事件监听器 (确保只绑定一次)
        if (!editPromptBtn.dataset.listenerAdded) {
            editPromptBtn.addEventListener('click', () => this.editPrompt());
            editPromptBtn.dataset.listenerAdded = true;
        }
        if (!toggleLockBtn.dataset.listenerAdded) {
            toggleLockBtn.addEventListener('click', () => this.togglePromptLock());
            toggleLockBtn.dataset.listenerAdded = true;
        }
    },

    /**
     * 编辑提示词
     */
    async editPrompt() {
        const promptViewDiv = document.getElementById('prompt-view');
        const promptEditDiv = document.getElementById('prompt-edit');
        const promptTextarea = document.getElementById('prompt-textarea');
        const saveBtn = document.getElementById('save-prompt');
        const cancelBtn = document.getElementById('cancel-prompt-edit');
        const currentContent = document.getElementById('prompt-content').innerText === '暂无提示词信息' ? '' : document.getElementById('prompt-content').innerText;

        // 切换到编辑模式
        promptViewDiv.classList.add('hidden');
        promptEditDiv.classList.remove('hidden');
        promptTextarea.value = currentContent;

        // 清除旧的事件监听器以防止重复绑定
        const newSaveBtn = saveBtn.cloneNode(true);
        const newCancelBtn = cancelBtn.cloneNode(true);
        saveBtn.parentNode.replaceChild(newSaveBtn, saveBtn);
        cancelBtn.parentNode.replaceChild(newCancelBtn, cancelBtn);

        newSaveBtn.addEventListener('click', async () => {
            const newContent = promptTextarea.value;
            const wallpaperId = this.currentWallpaper.id;
            // 获取当前的锁定状态 (从已更新的UI中获取)
            const isLocked = document.getElementById('prompt-lock-text').innerText === '已锁定' ? 1 : 0;
            
            try {
                const response = await this._fetchJson('api/wallpaper_prompt.php', 'POST', {
                    wallpaper_id: wallpaperId,
                    content: newContent,
                    is_locked: isLocked
                });
                if (response.code === 200) {
                    alert('提示词保存成功！');
                    this.loadWallpaperPrompt(wallpaperId); // 重新加载以更新UI
                } else {
                    alert('提示词保存失败: ' + response.msg);
                }
            } catch (error) {
                console.error('保存提示词时出错:', error);
                alert('保存提示词时发生网络错误或服务器错误。');
            }
        });

        newCancelBtn.addEventListener('click', () => {
            this.loadWallpaperPrompt(this.currentWallpaper.id); // 取消编辑，重新加载原始数据
        });
    },

    /**
     * 切换提示词锁定状态
     */
    async togglePromptLock() {
        const wallpaperId = this.currentWallpaper.id;
        const promptLockText = document.getElementById('prompt-lock-text');
        const currentContent = document.getElementById('prompt-content').innerText === '暂无提示词信息' ? '' : document.getElementById('prompt-content').innerText;
        const newIsLocked = (promptLockText.innerText === '已锁定') ? 0 : 1; // 切换状态

        try {
            const response = await this._fetchJson('api/wallpaper_prompt.php', 'POST', {
                wallpaper_id: wallpaperId,
                content: currentContent, // 发送当前内容以避免内容丢失
                is_locked: newIsLocked
            });

            if (response.code === 200) {
                alert(`提示词已${newIsLocked === 1 ? '锁定' : '解锁'}！`);
                this.loadWallpaperPrompt(wallpaperId); // 重新加载以更新UI
            } else {
                alert('操作失败: ' + response.msg);
            }
        } catch (error) {
            console.error('切换锁定状态时出错:', error);
            alert('切换锁定状态时发生网络错误或服务器错误。');
        }
    },

    /**
     * 复制提示词内容到剪贴板
     */
    async copyPromptContent() {
        const promptTextElement = document.getElementById('prompt-content');
        const copySuccessMessage = document.getElementById('copy-success-message'); // 获取新添加的提示元素

        if (!promptTextElement || !copySuccessMessage) {
            console.warn('[WallpaperDetail] 复制失败: 未找到提示词内容或提示元素。');
            return;
        }

        const contentToCopy = promptTextElement.innerText.trim();
        if (!contentToCopy || contentToCopy === '暂无提示词信息') {
            this._displayCopyMessage('没有提示词内容可供复制。', 'error'); // 使用新辅助函数显示错误
            return;
        }

        try {
            await navigator.clipboard.writeText(contentToCopy);
            this._displayCopyMessage('复制成功!', 'success'); // 使用新辅助函数显示成功
        } catch (err) {
            console.error('[WallpaperDetail] 复制提示词失败:', err);
            this._displayCopyMessage('复制失败！', 'error'); // 使用新辅助函数显示错误
        }
    },

    /**
     * 在复制图标旁边显示临时的复制消息
     * @param {string} message - 要显示的消息内容
     * @param {string} type - 消息类型，可选 'success' 或 'error'，用于设置样式
     * @param {number} duration - 消息显示时长（毫秒），默认为 1500ms
     */
    _displayCopyMessage(message, type = 'success', duration = 1500) {
        const copySuccessMessage = document.getElementById('copy-success-message');
        if (!copySuccessMessage) {
            console.warn('[WallpaperDetail] 未找到复制成功提示元素。');
            return;
        }

        copySuccessMessage.textContent = message;

        // 根据类型设置文本颜色
        copySuccessMessage.classList.remove('text-green-500', 'text-red-500', 'text-orange-500'); // 清除旧颜色
        if (type === 'success') {
            copySuccessMessage.classList.add('text-green-500');
        } else if (type === 'error') {
            copySuccessMessage.classList.add('text-red-500');
        } else {
            copySuccessMessage.classList.add('text-orange-500'); // 默认颜色，例如用于"没有内容"
        }

        copySuccessMessage.style.opacity = '1';

        // 如果已经有定时器在运行，清除它，以防快速点击导致消息被覆盖或提前消失
        if (this._copyMessageTimer) {
            clearTimeout(this._copyMessageTimer);
        }

        this._copyMessageTimer = setTimeout(() => {
            copySuccessMessage.style.opacity = '0';
            // 动画结束后清空文本，以便下次显示新内容
            copySuccessMessage.addEventListener('transitionend', () => {
                copySuccessMessage.textContent = '';
                copySuccessMessage.classList.remove('text-green-500', 'text-red-500', 'text-orange-500'); // 清除颜色
            }, { once: true });
        }, duration);
    }
};

// 导出模块
if (typeof module !== 'undefined' && module.exports) {
    module.exports = WallpaperDetail;
}

// 全局暴露
window.WallpaperDetail = WallpaperDetail;

console.log('[WallpaperDetail] 模块已加载');
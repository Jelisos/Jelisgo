<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户壁纸审核 - 管理后台</title>
    <link rel="stylesheet" href="../static/css/tailwind.min.css">
    <link rel="stylesheet" href="../static/css/font-awesome.min.css">
    <link rel="stylesheet" href="../static/css/main.css">
    <style>
        .wallpaper-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        .wallpaper-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.2s;
        }
        .wallpaper-card:hover {
            transform: translateY(-2px);
        }
        .wallpaper-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .status-pending {
            background-color: #fbbf24;
            color: white;
        }
        .status-approved {
            background-color: #10b981;
            color: white;
        }
        .status-rejected {
            background-color: #ef4444;
            color: white;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- 顶部导航栏 -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="admin.html" class="text-gray-600 hover:text-gray-900 mr-4">
                        <i class="fa fa-arrow-left"></i> 返回仪表盘
                    </a>
                    <h1 class="text-xl font-semibold text-gray-900">用户壁纸审核</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-600">待审核: <span id="pending-count" class="font-semibold text-orange-600">0</span></span>
                </div>
            </div>
        </div>
    </nav>

    <!-- 主要内容区域 -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- 筛选和搜索栏 -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div class="flex flex-wrap gap-4 items-center">
                <div class="flex-1 min-w-64">
                    <input type="text" id="search-input" placeholder="搜索壁纸标题或上传者..." 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <select id="status-filter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">所有状态</option>
                        <option value="pending">待审核</option>
                        <option value="approved">已通过</option>
                        <option value="rejected">已拒绝</option>
                    </select>
                </div>
                <button id="refresh-btn" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fa fa-refresh mr-2"></i>刷新
                </button>
            </div>
        </div>

        <!-- 壁纸列表 -->
        <div id="wallpaper-container" class="wallpaper-grid">
            <!-- 壁纸卡片将通过JavaScript动态加载 -->
        </div>

        <!-- 加载状态 -->
        <div id="loading" class="text-center py-8 hidden">
            <i class="fa fa-spinner fa-spin text-2xl text-gray-400"></i>
            <p class="text-gray-600 mt-2">加载中...</p>
        </div>

        <!-- 空状态 -->
        <div id="empty-state" class="text-center py-12 hidden">
            <i class="fa fa-image text-4xl text-gray-300 mb-4"></i>
            <p class="text-gray-600">暂无壁纸数据</p>
        </div>
    </div>

    <!-- 壁纸详情模态框 -->
    <div id="wallpaper-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg max-w-4xl w-full max-h-screen overflow-y-auto">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">壁纸详情</h3>
                        <button id="close-modal" class="text-gray-400 hover:text-gray-600">
                            <i class="fa fa-times text-xl"></i>
                        </button>
                    </div>
                    <div id="modal-content">
                        <!-- 模态框内容将通过JavaScript动态填充 -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 成功/错误提示 -->
    <div id="notification" class="fixed top-4 right-4 z-50 hidden">
        <div class="bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg">
            <span id="notification-text"></span>
        </div>
    </div>

    <script src="../static/js/utils.js"></script>
    <script src="js/admin-user-wallpapers.js"></script>
    <script>
        // 初始化页面
        document.addEventListener('DOMContentLoaded', function() {
            const wallpaperReview = new WallpaperReview();
            wallpaperReview.init();
        });

        /**
         * 用户壁纸审核管理类
         * 文件: admin/wallpaper-review.html
         * 描述: 独立的用户壁纸审核页面
         * 依赖: js/admin-user-wallpapers.js
         */
        class WallpaperReview {
            constructor() {
                this.currentPage = 1;
                this.pageSize = 20;
                this.searchTerm = '';
                this.statusFilter = '';
            }

            /**
             * 初始化页面
             */
            init() {
                this.bindEvents();
                this.loadWallpapers();
            }

            /**
             * 绑定事件监听器
             */
            bindEvents() {
                // 搜索输入
                document.getElementById('search-input').addEventListener('input', (e) => {
                    this.searchTerm = e.target.value;
                    this.currentPage = 1;
                    this.loadWallpapers();
                });

                // 状态筛选
                document.getElementById('status-filter').addEventListener('change', (e) => {
                    this.statusFilter = e.target.value;
                    this.currentPage = 1;
                    this.loadWallpapers();
                });

                // 刷新按钮
                document.getElementById('refresh-btn').addEventListener('click', () => {
                    this.loadWallpapers();
                });

                // 关闭模态框
                document.getElementById('close-modal').addEventListener('click', () => {
                    this.closeModal();
                });

                // 点击模态框背景关闭
                document.getElementById('wallpaper-modal').addEventListener('click', (e) => {
                    if (e.target.id === 'wallpaper-modal') {
                        this.closeModal();
                    }
                });
            }

            /**
             * 加载壁纸列表
             */
            async loadWallpapers() {
                this.showLoading(true);
                
                try {
                    const params = new URLSearchParams({
                        action: 'list',
                        page: this.currentPage,
                        limit: this.pageSize
                    });

                    if (this.searchTerm) {
                        params.append('search', this.searchTerm);
                    }
                    if (this.statusFilter) {
                        params.append('status', this.statusFilter);
                    }

                    const response = await fetch(`../api/admin_user_wallpapers.php?${params}`);
                    const data = await response.json();

                    if (data.success) {
                        this.renderWallpapers(data.data.wallpapers);
                        this.updatePendingCount(data.data.pending_count || 0);
                    } else {
                        this.showError(data.message || '加载失败');
                    }
                } catch (error) {
                    console.error('加载壁纸失败:', error);
                    this.showError('网络错误，请稍后重试');
                } finally {
                    this.showLoading(false);
                }
            }

            /**
             * 渲染壁纸列表
             */
            renderWallpapers(wallpapers) {
                const container = document.getElementById('wallpaper-container');
                const emptyState = document.getElementById('empty-state');

                if (!wallpapers || wallpapers.length === 0) {
                    container.innerHTML = '';
                    emptyState.classList.remove('hidden');
                    return;
                }

                emptyState.classList.add('hidden');
                container.innerHTML = wallpapers.map(wallpaper => this.createWallpaperCard(wallpaper)).join('');
            }

            /**
             * 创建壁纸卡片HTML
             */
            createWallpaperCard(wallpaper) {
                const statusClass = `status-${wallpaper.status}`;
                const statusText = {
                    'pending': '待审核',
                    'approved': '已通过', 
                    'rejected': '已拒绝'
                }[wallpaper.status] || wallpaper.status;

                return `
                    <div class="wallpaper-card">
                        <img src="${wallpaper.file_path}" alt="${wallpaper.title}" class="wallpaper-image" 
                             onerror="this.src='../static/icons/fa-picture-o.svg'">
                        <div class="p-4">
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="font-semibold text-gray-900 truncate">${wallpaper.title}</h3>
                                <span class="px-2 py-1 text-xs rounded-full ${statusClass}">${statusText}</span>
                            </div>
                            <p class="text-sm text-gray-600 mb-2">上传者: ${wallpaper.uploader_username || '未知'}</p>
                            <p class="text-xs text-gray-500 mb-3">${wallpaper.upload_time}</p>
                            <div class="flex gap-2">
                                <button onclick="wallpaperReviewInstance.viewWallpaper(${wallpaper.id})" 
                                        class="flex-1 px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700">
                                    查看
                                </button>
                                ${wallpaper.status === 'pending' ? `
                                    <button onclick="wallpaperReviewInstance.approveWallpaper(${wallpaper.id})" 
                                            class="flex-1 px-3 py-1 bg-green-600 text-white text-sm rounded hover:bg-green-700">
                                        通过
                                    </button>
                                    <button onclick="wallpaperReviewInstance.rejectWallpaper(${wallpaper.id})" 
                                            class="flex-1 px-3 py-1 bg-red-600 text-white text-sm rounded hover:bg-red-700">
                                        拒绝
                                    </button>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `;
            }

            /**
             * 查看壁纸详情
             */
            async viewWallpaper(id) {
                try {
                    const response = await fetch(`../api/admin_user_wallpapers.php?action=detail&id=${id}`);
                    const data = await response.json();

                    if (data.success) {
                        this.showWallpaperModal(data.data);
                    } else {
                        this.showError(data.message || '获取详情失败');
                    }
                } catch (error) {
                    console.error('获取壁纸详情失败:', error);
                    this.showError('网络错误，请稍后重试');
                }
            }

            /**
             * 显示壁纸详情模态框
             */
            showWallpaperModal(wallpaper) {
                const modalContent = document.getElementById('modal-content');
                const statusClass = `status-${wallpaper.status}`;
                const statusText = {
                    'pending': '待审核',
                    'approved': '已通过',
                    'rejected': '已拒绝'
                }[wallpaper.status] || wallpaper.status;

                modalContent.innerHTML = `
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div>
                            <img src="${wallpaper.file_path}" alt="${wallpaper.title}" 
                                 class="w-full rounded-lg shadow-lg" 
                                 onerror="this.src='../static/icons/fa-picture-o.svg'">
                        </div>
                        <div>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">标题</label>
                                    <p class="mt-1 text-sm text-gray-900">${wallpaper.title}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">描述</label>
                                    <p class="mt-1 text-sm text-gray-900">${wallpaper.description || '无描述'}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">上传者</label>
                                    <p class="mt-1 text-sm text-gray-900">${wallpaper.uploader_username || '未知'}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">上传时间</label>
                                    <p class="mt-1 text-sm text-gray-900">${wallpaper.upload_time}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">状态</label>
                                    <span class="inline-block mt-1 px-3 py-1 text-sm rounded-full ${statusClass}">${statusText}</span>
                                </div>
                                ${wallpaper.reject_reason ? `
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">拒绝原因</label>
                                        <p class="mt-1 text-sm text-red-600">${wallpaper.reject_reason}</p>
                                    </div>
                                ` : ''}
                                ${wallpaper.status === 'pending' ? `
                                    <div class="flex gap-3 pt-4">
                                        <button onclick="wallpaperReviewInstance.approveWallpaper(${wallpaper.id}); wallpaperReviewInstance.closeModal();" 
                                                class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                                            通过审核
                                        </button>
                                        <button onclick="wallpaperReviewInstance.rejectWallpaper(${wallpaper.id}); wallpaperReviewInstance.closeModal();" 
                                                class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                                            拒绝审核
                                        </button>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `;

                document.getElementById('wallpaper-modal').classList.remove('hidden');
            }

            /**
             * 关闭模态框
             */
            closeModal() {
                document.getElementById('wallpaper-modal').classList.add('hidden');
            }

            /**
             * 通过壁纸
             */
            async approveWallpaper(id) {
                if (!confirm('确定要通过这个壁纸吗？')) {
                    return;
                }

                try {
                    const response = await fetch('../api/admin_user_wallpapers.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'approve',
                            id: id
                        })
                    });

                    const data = await response.json();
                    if (data.success) {
                        this.showSuccess('用户壁纸已通过审核');
                        this.loadWallpapers();
                    } else {
                        this.showError(data.message || '操作失败');
                    }
                } catch (error) {
                    console.error('通过壁纸失败:', error);
                    this.showError('网络错误，请稍后重试');
                }
            }

            /**
             * 拒绝壁纸
             */
            async rejectWallpaper(id) {
                const reason = prompt('请输入拒绝原因:');
                if (!reason) {
                    return;
                }

                try {
                    const response = await fetch('../api/admin_user_wallpapers.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'reject',
                            id: id,
                            reason: reason
                        })
                    });

                    const data = await response.json();
                    if (data.success) {
                        this.showSuccess('用户壁纸已拒绝');
                        this.loadWallpapers();
                    } else {
                        this.showError(data.message || '操作失败');
                    }
                } catch (error) {
                    console.error('拒绝壁纸失败:', error);
                    this.showError('网络错误，请稍后重试');
                }
            }

            /**
             * 更新待审核数量
             */
            updatePendingCount(count) {
                document.getElementById('pending-count').textContent = count;
            }

            /**
             * 显示加载状态
             */
            showLoading(show) {
                const loading = document.getElementById('loading');
                if (show) {
                    loading.classList.remove('hidden');
                } else {
                    loading.classList.add('hidden');
                }
            }

            /**
             * 显示成功消息
             */
            showSuccess(message) {
                this.showNotification(message, 'success');
            }

            /**
             * 显示错误消息
             */
            showError(message) {
                this.showNotification(message, 'error');
            }

            /**
             * 显示通知
             */
            showNotification(message, type = 'success') {
                const notification = document.getElementById('notification');
                const notificationText = document.getElementById('notification-text');
                
                notificationText.textContent = message;
                
                // 设置样式
                const bgColor = type === 'success' ? 'bg-green-500' : 'bg-red-500';
                notification.firstElementChild.className = `${bgColor} text-white px-6 py-3 rounded-lg shadow-lg`;
                
                notification.classList.remove('hidden');
                
                // 3秒后自动隐藏
                setTimeout(() => {
                    notification.classList.add('hidden');
                }, 3000);
            }
        }

        // 创建全局实例供按钮调用
        let wallpaperReviewInstance;
        document.addEventListener('DOMContentLoaded', function() {
            wallpaperReviewInstance = new WallpaperReview();
            wallpaperReviewInstance.init();
        });
    </script>
</body>
</html>
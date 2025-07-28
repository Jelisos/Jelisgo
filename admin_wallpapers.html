<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>壁纸审核管理 - 管理后台</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar-active { background-color: #3B82F6; color: white; }
        .preview-modal { backdrop-filter: blur(5px); }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- 侧边栏 -->
        <div class="w-64 bg-white shadow-lg">
            <div class="p-6">
                <h1 class="text-xl font-bold text-gray-800">管理后台</h1>
            </div>
            <nav class="mt-6">
                <a href="admin.html" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-tachometer-alt mr-3"></i>
                    仪表盘
                </a>
                <a href="admin_wallpapers.html" class="flex items-center px-6 py-3 sidebar-active">
                    <i class="fas fa-image mr-3"></i>
                    壁纸审核
                </a>
                <a href="admin_users.html" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-users mr-3"></i>
                    用户管理
                </a>
                <a href="admin_logs.html" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-list-alt mr-3"></i>
                    操作日志
                </a>
            </nav>
        </div>

        <!-- 主内容区 -->
        <div class="flex-1 overflow-hidden">
            <!-- 顶部导航 -->
            <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="flex items-center justify-between px-6 py-4">
                    <h2 class="text-2xl font-semibold text-gray-800">壁纸审核管理</h2>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-600">管理员</span>
                        <a href="index.php" class="text-blue-600 hover:text-blue-800">返回前台</a>
                    </div>
                </div>
            </header>

            <!-- 内容区域 -->
            <main class="flex-1 overflow-y-auto p-6">
                <!-- 统计卡片 -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                                <i class="fas fa-clock text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">待审核</p>
                                <p class="text-2xl font-semibold text-gray-900" id="pending-count">0</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 text-green-600">
                                <i class="fas fa-check text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">已通过</p>
                                <p class="text-2xl font-semibold text-gray-900" id="approved-count">0</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-red-100 text-red-600">
                                <i class="fas fa-times text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">已拒绝</p>
                                <p class="text-2xl font-semibold text-gray-900" id="rejected-count">0</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                                <i class="fas fa-images text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">总数</p>
                                <p class="text-2xl font-semibold text-gray-900" id="total-count">0</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 筛选和搜索 -->
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center space-x-4 mb-4 sm:mb-0">
                                <select id="status-filter" class="border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="pending">待审核</option>
                                    <option value="all">全部</option>
                                    <option value="approved">已通过</option>
                                    <option value="rejected">已拒绝</option>
                                </select>
                                
                                <button id="batch-approve-btn" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 disabled:opacity-50" disabled>
                                    <i class="fas fa-check mr-2"></i>批量通过
                                </button>
                            </div>
                            
                            <div class="flex items-center space-x-4">
                                <div class="relative">
                                    <input type="text" id="search-input" placeholder="搜索壁纸标题..." 
                                           class="border border-gray-300 rounded-md pl-10 pr-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                                </div>
                                <button id="refresh-btn" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                    <i class="fas fa-sync-alt mr-2"></i>刷新
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 壁纸列表 -->
                <div class="bg-white rounded-lg shadow">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <input type="checkbox" id="select-all" class="rounded">
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">预览</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">标题</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">分类</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">上传者</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">上传时间</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">状态</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                                </tr>
                            </thead>
                            <tbody id="wallpaper-list" class="bg-white divide-y divide-gray-200">
                                <!-- 动态加载内容 -->
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- 分页 -->
                    <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="flex-1 flex justify-between sm:hidden">
                                <button id="prev-page-mobile" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    上一页
                                </button>
                                <button id="next-page-mobile" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    下一页
                                </button>
                            </div>
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        显示第 <span id="page-start">1</span> 到 <span id="page-end">20</span> 条，
                                        共 <span id="total-items">0</span> 条记录
                                    </p>
                                </div>
                                <div>
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" id="pagination">
                                        <!-- 动态生成分页按钮 -->
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- 预览模态框 -->
    <div id="preview-modal" class="fixed inset-0 bg-black bg-opacity-75 hidden z-50 preview-modal">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg max-w-4xl max-h-full overflow-auto">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 id="preview-title" class="text-lg font-medium text-gray-900">壁纸预览</h3>
                        <button id="close-preview" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    <div class="text-center">
                        <img id="preview-image" src="" alt="" class="max-w-full max-h-96 object-contain mx-auto">
                    </div>
                    <div id="preview-details" class="mt-4 text-sm text-gray-600">
                        <!-- 壁纸详细信息 -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 审核拒绝模态框 -->
    <div id="reject-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg max-w-md w-full">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">审核拒绝</h3>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">拒绝原因</label>
                        <textarea id="reject-reason" rows="4" 
                                  class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                  placeholder="请输入拒绝原因..."></textarea>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button id="cancel-reject" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            取消
                        </button>
                        <button id="confirm-reject" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                            确认拒绝
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="static/js/admin-wallpapers.js"></script>
</body>
</html>
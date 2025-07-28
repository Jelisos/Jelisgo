<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户管理 - 管理后台</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar-active { background-color: #3B82F6; color: white; }
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
                <a href="admin_wallpapers.html" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-image mr-3"></i>
                    壁纸审核
                </a>
                <a href="admin_users.html" class="flex items-center px-6 py-3 sidebar-active">
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
                    <h2 class="text-2xl font-semibold text-gray-800">用户管理</h2>
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
                            <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                                <i class="fas fa-users text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">总用户数</p>
                                <p class="text-2xl font-semibold text-gray-900" id="total-users">0</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 text-green-600">
                                <i class="fas fa-user-check text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">活跃用户</p>
                                <p class="text-2xl font-semibold text-gray-900" id="active-users">0</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-red-100 text-red-600">
                                <i class="fas fa-user-slash text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">封禁用户</p>
                                <p class="text-2xl font-semibold text-gray-900" id="banned-users">0</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                                <i class="fas fa-user-plus text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">今日新增</p>
                                <p class="text-2xl font-semibold text-gray-900" id="today-new-users">0</p>
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
                                    <option value="all">全部用户</option>
                                    <option value="active">活跃用户</option>
                                    <option value="banned">封禁用户</option>
                                </select>
                                
                                <select id="role-filter" class="border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="all">全部角色</option>
                                    <option value="user">普通用户</option>
                                    <option value="admin">管理员</option>
                                </select>
                            </div>
                            
                            <div class="flex items-center space-x-4">
                                <div class="relative">
                                    <input type="text" id="search-input" placeholder="搜索用户名或邮箱..." 
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

                <!-- 用户列表 -->
                <div class="bg-white rounded-lg shadow">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">用户</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">邮箱</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">角色</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">状态</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">上传数量</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">注册时间</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                                </tr>
                            </thead>
                            <tbody id="user-list" class="bg-white divide-y divide-gray-200">
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

    <!-- 用户详情模态框 -->
    <div id="user-detail-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg max-w-2xl w-full max-h-full overflow-auto">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">用户详情</h3>
                        <button id="close-user-detail" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    <div id="user-detail-content">
                        <!-- 动态加载用户详情 -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 封禁用户模态框 -->
    <div id="ban-user-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg max-w-md w-full">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">封禁用户</h3>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">封禁原因</label>
                        <textarea id="ban-reason" rows="4" 
                                  class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                  placeholder="请输入封禁原因..."></textarea>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button id="cancel-ban" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            取消
                        </button>
                        <button id="confirm-ban" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                            确认封禁
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 设置角色模态框 -->
    <div id="role-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg max-w-md w-full">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">设置用户角色</h3>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">选择角色</label>
                        <select id="new-role" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="user">普通用户</option>
                            <option value="admin">管理员</option>
                        </select>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button id="cancel-role" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            取消
                        </button>
                        <button id="confirm-role" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            确认设置
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="static/js/admin-users.js"></script>
</body>
</html>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session管理 - 管理后台</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #666;
            font-size: 1.1em;
        }
        
        .actions {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            margin-right: 10px;
            margin-bottom: 10px;
            transition: background-color 0.3s ease;
        }
        
        .btn:hover {
            background: #5a67d8;
        }
        
        .btn-danger {
            background: #e53e3e;
        }
        
        .btn-danger:hover {
            background: #c53030;
        }
        
        .sessions-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table-header {
            background: #667eea;
            color: white;
            padding: 20px;
            font-size: 1.2em;
            font-weight: bold;
        }
        
        .table-content {
            max-height: 500px;
            overflow-y: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #555;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .status-active {
            color: #38a169;
            font-weight: bold;
        }
        
        .status-expired {
            color: #e53e3e;
            font-weight: bold;
        }
        
        .ip-address {
            font-family: monospace;
            background: #f1f5f9;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.9em;
        }
        
        .user-info {
            font-weight: 600;
            color: #667eea;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .error {
            background: #fed7d7;
            color: #c53030;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }
        
        .success {
            background: #c6f6d5;
            color: #2f855a;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }
        
        .refresh-info {
            text-align: center;
            color: #666;
            margin-top: 20px;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Session管理系统</h1>
            <p>实时监控和管理用户会话</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number" id="totalSessions">-</div>
                <div class="stat-label">总Session数</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="activeSessions">-</div>
                <div class="stat-label">活跃Session</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="expiredSessions">-</div>
                <div class="stat-label">过期Session</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="loggedInSessions">-</div>
                <div class="stat-label">已登录用户</div>
            </div>
        </div>
        
        <div class="actions">
            <h3 style="margin-bottom: 15px;">管理操作</h3>
            <button class="btn" onclick="refreshData()">刷新数据</button>
            <button class="btn" onclick="cleanupExpiredSessions()">清理过期Session</button>
            <button class="btn btn-danger" onclick="clearAllSessions()">清空所有Session</button>
        </div>
        
        <div id="message"></div>
        
        <div class="sessions-table">
            <div class="table-header">
                活跃Session列表
            </div>
            <div class="table-content">
                <table>
                    <thead>
                        <tr>
                            <th>Session ID</th>
                            <th>用户信息</th>
                            <th>IP地址</th>
                            <th>创建时间</th>
                            <th>最后活动</th>
                            <th>过期时间</th>
                            <th>状态</th>
                        </tr>
                    </thead>
                    <tbody id="sessionsTableBody">
                        <tr>
                            <td colspan="7" class="loading">加载中...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="refresh-info">
            数据每30秒自动刷新 | 最后更新: <span id="lastUpdate">-</span>
        </div>
    </div>
    
    <script>
        let autoRefreshInterval;
        
        // 页面加载时初始化
        document.addEventListener('DOMContentLoaded', function() {
            refreshData();
            startAutoRefresh();
        });
        
        // 开始自动刷新
        function startAutoRefresh() {
            autoRefreshInterval = setInterval(refreshData, 30000); // 30秒刷新一次
        }
        
        // 停止自动刷新
        function stopAutoRefresh() {
            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
            }
        }
        
        // 刷新数据
        async function refreshData() {
            try {
                const response = await fetch('/api/admin/session_stats.php');
                const data = await response.json();
                
                if (data.success) {
                    updateStats(data.stats);
                    updateSessionsTable(data.sessions);
                    updateLastUpdateTime();
                } else {
                    showMessage('获取数据失败: ' + data.message, 'error');
                }
            } catch (error) {
                showMessage('网络错误: ' + error.message, 'error');
            }
        }
        
        // 更新统计数据
        function updateStats(stats) {
            document.getElementById('totalSessions').textContent = stats.total || 0;
            document.getElementById('activeSessions').textContent = stats.active || 0;
            document.getElementById('expiredSessions').textContent = stats.expired || 0;
            document.getElementById('loggedInSessions').textContent = stats.logged_in || 0;
        }
        
        // 更新Session表格
        function updateSessionsTable(sessions) {
            const tbody = document.getElementById('sessionsTableBody');
            
            if (!sessions || sessions.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="loading">暂无活跃Session</td></tr>';
                return;
            }
            
            tbody.innerHTML = sessions.map(session => {
                const isExpired = new Date(session.expires_at) <= new Date();
                const statusClass = isExpired ? 'status-expired' : 'status-active';
                const statusText = isExpired ? '已过期' : '活跃';
                
                return `
                    <tr>
                        <td><code>${session.session_id.substring(0, 16)}...</code></td>
                        <td class="user-info">${session.username || '游客'}</td>
                        <td><span class="ip-address">${session.ip_address || '-'}</span></td>
                        <td>${formatDateTime(session.created_at)}</td>
                        <td>${formatDateTime(session.updated_at)}</td>
                        <td>${formatDateTime(session.expires_at)}</td>
                        <td><span class="${statusClass}">${statusText}</span></td>
                    </tr>
                `;
            }).join('');
        }
        
        // 格式化日期时间
        function formatDateTime(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleString('zh-CN', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        }
        
        // 更新最后更新时间
        function updateLastUpdateTime() {
            document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString('zh-CN');
        }
        
        // 清理过期Session
        async function cleanupExpiredSessions() {
            if (!confirm('确定要清理所有过期的Session吗？')) {
                return;
            }
            
            try {
                const response = await fetch('/api/admin/session_cleanup.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ action: 'cleanup' })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showMessage(`成功清理了 ${data.cleaned_count} 个过期Session`, 'success');
                    refreshData();
                } else {
                    showMessage('清理失败: ' + data.message, 'error');
                }
            } catch (error) {
                showMessage('操作失败: ' + error.message, 'error');
            }
        }
        
        // 清空所有Session
        async function clearAllSessions() {
            if (!confirm('警告：这将清空所有Session，包括当前登录的用户！\n确定要继续吗？')) {
                return;
            }
            
            try {
                const response = await fetch('/api/admin/session_cleanup.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ action: 'clear_all' })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showMessage(`成功清空了 ${data.cleared_count} 个Session`, 'success');
                    refreshData();
                } else {
                    showMessage('清空失败: ' + data.message, 'error');
                }
            } catch (error) {
                showMessage('操作失败: ' + error.message, 'error');
            }
        }
        
        // 显示消息
        function showMessage(message, type = 'info') {
            const messageDiv = document.getElementById('message');
            messageDiv.className = type;
            messageDiv.textContent = message;
            messageDiv.style.display = 'block';
            
            setTimeout(() => {
                messageDiv.style.display = 'none';
            }, 5000);
        }
        
        // 页面卸载时停止自动刷新
        window.addEventListener('beforeunload', stopAutoRefresh);
    </script>
</body>
</html>
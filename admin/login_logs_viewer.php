<?php
/**
 * 登录记录查看器 - Session写入机制分离效果展示
 * 管理员可以查看独立的用户登录历史记录
 */

session_start();

// 简单的管理员权限检查
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: /index.php');
    exit;
}

require_once __DIR__ . '/../api/login_logger.php';

$logger = LoginLogger::getInstance();

// 处理AJAX请求
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['ajax']) {
        case 'stats':
            $stats = $logger->getLoginStats(null, 30); // 30天统计
            echo json_encode($stats);
            break;
            
        case 'recent_logs':
            $limit = $_GET['limit'] ?? 20;
            $offset = $_GET['offset'] ?? 0;
            
            try {
                $pdo = Database::getInstance()->getConnection();
                $stmt = $pdo->prepare("
                    SELECT l.*, u.username, u.email 
                    FROM user_login_logs l 
                    LEFT JOIN users u ON l.user_id = u.id 
                    ORDER BY l.login_time DESC 
                    LIMIT ? OFFSET ?
                ");
                $stmt->execute([$limit, $offset]);
                $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($logs);
            } catch (Exception $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }
            break;
            
        case 'user_sessions':
            $userId = $_GET['user_id'] ?? null;
            if ($userId) {
                $sessions = $logger->getUserActiveSessions($userId);
                echo json_encode($sessions);
            } else {
                echo json_encode([]);
            }
            break;
    }
    exit;
}

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录记录管理 - Session写入机制分离展示</title>
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
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 1.1em;
            opacity: 0.9;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
            font-size: 0.9em;
        }
        
        .section {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .section h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        tr:hover {
            background-color: #f8f9fa;
        }
        
        .status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: bold;
        }
        
        .status.success { background: #d4edda; color: #155724; }
        .status.failed { background: #f8d7da; color: #721c24; }
        .status.logout { background: #d1ecf1; color: #0c5460; }
        .status.expired { background: #fff3cd; color: #856404; }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .controls {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
            transition: background-color 0.3s ease;
        }
        
        .btn-primary {
            background-color: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #5a6fd8;
        }
        
        .highlight {
            background: linear-gradient(120deg, #a8edea 0%, #fed6e3 100%);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }
        
        .highlight h3 {
            color: #333;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 登录记录管理系统</h1>
            <p>Session写入机制分离 - 独立的用户登录历史追踪</p>
        </div>
        
        <div class="highlight">
            <h3>✨ Session写入机制分离成功！</h3>
            <p>现在登录记录已完全独立于Session系统，支持详细的用户行为追踪、安全审计和数据分析。Session系统专注于会话管理，登录记录系统专注于历史追踪，两者解耦运行。</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number" id="total-logins">-</div>
                <div class="stat-label">总登录次数</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="unique-users">-</div>
                <div class="stat-label">独立用户数</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="successful-logins">-</div>
                <div class="stat-label">成功登录</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="failed-logins">-</div>
                <div class="stat-label">失败登录</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="unique-ips">-</div>
                <div class="stat-label">独立IP数</div>
            </div>
        </div>
        
        <div class="section">
            <h2>📊 最近登录记录</h2>
            <div class="controls">
                <button class="btn btn-primary" onclick="loadRecentLogs()">刷新数据</button>
                <span>显示最近 20 条记录</span>
            </div>
            <div class="table-container">
                <table id="logs-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>用户</th>
                            <th>邮箱</th>
                            <th>登录时间</th>
                            <th>登出时间</th>
                            <th>IP地址</th>
                            <th>状态</th>
                            <th>登录方式</th>
                            <th>失败原因</th>
                            <th>Session ID</th>
                        </tr>
                    </thead>
                    <tbody id="logs-tbody">
                        <tr><td colspan="10" class="loading">加载中...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        // 加载统计数据
        function loadStats() {
            fetch('?ajax=stats')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('total-logins').textContent = data.total_logins || 0;
                    document.getElementById('unique-users').textContent = data.unique_users || 0;
                    document.getElementById('successful-logins').textContent = data.successful_logins || 0;
                    document.getElementById('failed-logins').textContent = data.failed_logins || 0;
                    document.getElementById('unique-ips').textContent = data.unique_ips || 0;
                })
                .catch(error => {
                    console.error('加载统计数据失败:', error);
                });
        }
        
        // 加载最近登录记录
        function loadRecentLogs() {
            const tbody = document.getElementById('logs-tbody');
            tbody.innerHTML = '<tr><td colspan="10" class="loading">加载中...</td></tr>';
            
            fetch('?ajax=recent_logs&limit=20')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        tbody.innerHTML = `<tr><td colspan="10" style="color: red;">错误: ${data.error}</td></tr>`;
                        return;
                    }
                    
                    if (data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="10" class="loading">暂无登录记录</td></tr>';
                        return;
                    }
                    
                    tbody.innerHTML = data.map(log => {
                        const statusClass = log.login_status;
                        const sessionId = log.session_id ? log.session_id.substring(0, 12) + '...' : '-';
                        
                        return `
                            <tr>
                                <td>${log.id}</td>
                                <td>${log.username || '-'}</td>
                                <td>${log.email || '-'}</td>
                                <td>${log.login_time}</td>
                                <td>${log.logout_time || '-'}</td>
                                <td>${log.ip_address}</td>
                                <td><span class="status ${statusClass}">${log.login_status}</span></td>
                                <td>${log.login_method}</td>
                                <td>${log.failure_reason || '-'}</td>
                                <td title="${log.session_id}">${sessionId}</td>
                            </tr>
                        `;
                    }).join('');
                })
                .catch(error => {
                    console.error('加载登录记录失败:', error);
                    tbody.innerHTML = '<tr><td colspan="10" style="color: red;">加载失败</td></tr>';
                });
        }
        
        // 页面加载时初始化
        document.addEventListener('DOMContentLoaded', function() {
            loadStats();
            loadRecentLogs();
            
            // 每30秒自动刷新一次
            setInterval(() => {
                loadStats();
                loadRecentLogs();
            }, 30000);
        });
    </script>
</body>
</html>
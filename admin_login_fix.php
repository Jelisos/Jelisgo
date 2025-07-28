<?php
/**
 * 管理员权限修复脚本
 * 用于设置正确的管理员会话权限
 * 修复会员码删除功能的权限问题
 * 
 * @author AI Assistant
 * @date 2024-06-24
 * @updated 2024-06-24
 */

// 启用错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 启动会话
session_start();

// 设置响应头
header('Content-Type: text/html; charset=utf-8');

// 处理POST请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'set_admin') {
        // 设置管理员权限
        $_SESSION['user_id'] = 1; // 假设管理员ID为1
        $_SESSION['username'] = 'admin';
        $_SESSION['is_admin'] = true;
        $_SESSION['is_vip'] = true;
        
        $message = '管理员权限设置成功！';
        $success = true;
    } elseif ($action === 'clear_session') {
        // 清除会话
        session_destroy();
        session_start();
        
        $message = '会话已清除！';
        $success = true;
    }
}

// 获取当前会话信息
$current_session = [
    'user_id' => $_SESSION['user_id'] ?? null,
    'username' => $_SESSION['username'] ?? null,
    'is_admin' => $_SESSION['is_admin'] ?? false,
    'is_vip' => $_SESSION['is_vip'] ?? false
];

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员登录修复工具</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .session-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #007bff;
        }
        .session-info h3 {
            margin-top: 0;
            color: #007bff;
        }
        .session-item {
            margin: 8px 0;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        .session-item:last-child {
            border-bottom: none;
        }
        .label {
            font-weight: bold;
            color: #555;
            display: inline-block;
            width: 120px;
        }
        .value {
            color: #333;
        }
        .value.true {
            color: #28a745;
            font-weight: bold;
        }
        .value.false {
            color: #dc3545;
        }
        .buttons {
            text-align: center;
            margin-top: 30px;
        }
        .btn {
            padding: 12px 24px;
            margin: 0 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        .btn:hover {
            opacity: 0.8;
        }
        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            text-align: center;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 管理员登录修复工具</h1>
        
        <?php if (isset($message)): ?>
            <div class="message <?php echo $success ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="session-info">
            <h3>📊 当前会话状态</h3>
            <div class="session-item">
                <span class="label">用户ID:</span>
                <span class="value"><?php echo $current_session['user_id'] ?? '未设置'; ?></span>
            </div>
            <div class="session-item">
                <span class="label">用户名:</span>
                <span class="value"><?php echo htmlspecialchars($current_session['username'] ?? '未设置'); ?></span>
            </div>
            <div class="session-item">
                <span class="label">管理员权限:</span>
                <span class="value <?php echo $current_session['is_admin'] ? 'true' : 'false'; ?>">
                    <?php echo $current_session['is_admin'] ? '✅ 是' : '❌ 否'; ?>
                </span>
            </div>
            <div class="session-item">
                <span class="label">VIP权限:</span>
                <span class="value <?php echo $current_session['is_vip'] ? 'true' : 'false'; ?>">
                    <?php echo $current_session['is_vip'] ? '✅ 是' : '❌ 否'; ?>
                </span>
            </div>
        </div>
        
        <div class="buttons">
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="set_admin">
                <button type="submit" class="btn btn-primary">🔑 设置管理员权限</button>
            </form>
            
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="clear_session">
                <button type="submit" class="btn btn-danger">🗑️ 清除会话</button>
            </form>
            
            <a href="dashboard.html" class="btn btn-secondary">📊 返回仪表盘</a>
        </div>
        
        <div style="margin-top: 30px; padding: 15px; background: #fff3cd; border-radius: 5px; border-left: 4px solid #ffc107;">
            <h4 style="margin-top: 0; color: #856404;">💡 使用说明</h4>
            <ul style="color: #856404; margin-bottom: 0;">
                <li><strong>设置管理员权限</strong>：为当前会话设置管理员和VIP权限</li>
                <li><strong>清除会话</strong>：清除所有会话数据，重新开始</li>
                <li><strong>返回仪表盘</strong>：设置权限后返回主页面测试功能</li>
            </ul>
        </div>
    </div>
</body>
</html>
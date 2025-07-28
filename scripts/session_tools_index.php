<?php
// 设置页面编码
header('Content-Type: text/html; charset=utf-8');

// 定义工具列表
$tools = [
    [
        'name' => '会话修复主工具',
        'description' => '综合性工具，提供会话问题检测和修复功能',
        'path' => '/scripts/session_repair_tool.php',
        'icon' => 'tools',
        'primary' => true
    ],
    [
        'name' => '个人中心链接修复',
        'description' => '修复auth.js中的个人中心链接和dashboard.js中的fetch调用',
        'path' => '/scripts/fix_session_center_link.php',
        'icon' => 'link'
    ],
    [
        'name' => '会话处理修复',
        'description' => '修复auth_unified.php中的会话处理问题',
        'path' => '/scripts/fix_auth_unified.php',
        'icon' => 'code'
    ],
    [
        'name' => '会话信息查看器',
        'description' => '查看当前会话的详细信息，包括会话配置、Cookie和数据库记录',
        'path' => '/scripts/session_info.php',
        'icon' => 'info-circle'
    ],
    [
        'name' => '会话数据库检查',
        'description' => '检查数据库中的会话记录，管理会话数据',
        'path' => '/scripts/sql_test/session_db_check.php',
        'icon' => 'database'
    ],
    [
        'name' => '会话修复指南',
        'description' => '详细的会话问题修复指南和技术文档',
        'path' => '/txt-md/session_repair_guide.md',
        'icon' => 'file-text',
        'type' => 'markdown'
    ]
];

// 获取当前会话状态
session_start();
$sessionId = session_id();
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '未设置';
$isLoggedIn = !empty($_SESSION['user_id']);

// 检测潜在问题
$potentialIssues = [];

// 检查会话ID是否为空
if (empty($sessionId)) {
    $potentialIssues[] = ["type" => "error", "message" => "会话ID为空，可能无法正确创建会话"];
}

// 检查用户ID是否设置
if (!$isLoggedIn) {
    $potentialIssues[] = ["type" => "warning", "message" => "会话中未设置用户ID，可能未登录或会话状态丢失"];
}

// 检查auth.js文件中的个人中心链接
$authJsPath = __DIR__ . '/../static/js/auth.js';
if (file_exists($authJsPath)) {
    $authJsContent = file_get_contents($authJsPath);
    if (strpos($authJsContent, 'href="dashboard.php"') !== false) {
        $potentialIssues[] = ["type" => "warning", "message" => "auth.js中的个人中心链接使用相对路径，可能导致会话丢失"];
    }
}

// 输出HTML页面
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>会话修复工具集</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            color: #333;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        header {
            background-color: #2c3e50;
            color: white;
            padding: 20px;
            text-align: center;
        }
        h1 {
            margin: 0;
            font-size: 2em;
        }
        .subtitle {
            color: #ecf0f1;
            margin-top: 10px;
        }
        .session-status {
            background-color: #34495e;
            color: white;
            padding: 10px 20px;
            text-align: center;
            font-size: 0.9em;
        }
        .session-status span {
            margin: 0 10px;
        }
        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }
        .status-active {
            background-color: #2ecc71;
        }
        .status-inactive {
            background-color: #e74c3c;
        }
        .tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .tool-card {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .tool-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .tool-card.primary {
            grid-column: 1 / -1;
            display: grid;
            grid-template-columns: 1fr 2fr;
        }
        .tool-header {
            padding: 15px;
            background-color: #3498db;
            color: white;
        }
        .tool-card.primary .tool-header {
            background-color: #2980b9;
        }
        .tool-body {
            padding: 15px;
        }
        .tool-icon {
            font-size: 2em;
            margin-bottom: 10px;
        }
        .tool-card.primary .tool-icon {
            font-size: 3em;
        }
        .tool-title {
            font-size: 1.2em;
            font-weight: bold;
            margin: 0 0 10px 0;
        }
        .tool-card.primary .tool-title {
            font-size: 1.5em;
        }
        .tool-description {
            color: #666;
            margin-bottom: 15px;
        }
        .tool-link {
            display: inline-block;
            padding: 8px 16px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }
        .tool-link:hover {
            background-color: #2980b9;
        }
        .issues-section {
            margin: 20px 0;
            padding: 15px;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .issue-item {
            margin: 10px 0;
            padding: 10px;
            border-radius: 3px;
        }
        .issue-warning {
            background-color: #fcf8e3;
            border-left: 4px solid #f39c12;
        }
        .issue-error {
            background-color: #f2dede;
            border-left: 4px solid #e74c3c;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            padding: 20px;
            background-color: #2c3e50;
            color: #ecf0f1;
        }
        .quick-links {
            margin-top: 20px;
            text-align: center;
        }
        .quick-link {
            display: inline-block;
            padding: 8px 16px;
            background-color: #34495e;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 5px;
            transition: background-color 0.3s ease;
        }
        .quick-link:hover {
            background-color: #2c3e50;
        }
    </style>
</head>
<body>
    <header>
        <h1>会话修复工具集</h1>
        <p class="subtitle">用于诊断和修复会话相关问题的工具集合</p>
    </header>
    
    <div class="session-status">
        <span>
            <i class="fas fa-id-card"></i> 会话ID: <?php echo htmlspecialchars(substr($sessionId, 0, 10) . '...'); ?>
        </span>
        <span>
            <i class="fas fa-user"></i> 用户ID: <?php echo htmlspecialchars($userId); ?>
        </span>
        <span>
            <div class="status-indicator <?php echo $isLoggedIn ? 'status-active' : 'status-inactive'; ?>"></div>
            状态: <?php echo $isLoggedIn ? '已登录' : '未登录'; ?>
        </span>
    </div>
    
    <div class="container">
        <?php if (!empty($potentialIssues)): ?>
        <div class="issues-section">
            <h2><i class="fas fa-exclamation-triangle"></i> 检测到的潜在问题</h2>
            <?php foreach ($potentialIssues as $issue): ?>
                <div class="issue-item issue-<?php echo $issue['type']; ?>">
                    <?php echo htmlspecialchars($issue['message']); ?>
                </div>
            <?php endforeach; ?>
            <p>建议使用下方的修复工具解决这些问题</p>
        </div>
        <?php endif; ?>
        
        <div class="tools-grid">
            <?php foreach ($tools as $tool): ?>
                <div class="tool-card <?php echo isset($tool['primary']) && $tool['primary'] ? 'primary' : ''; ?>">
                    <div class="tool-header">
                        <div class="tool-icon">
                            <i class="fas fa-<?php echo $tool['icon']; ?>"></i>
                        </div>
                        <h2 class="tool-title"><?php echo htmlspecialchars($tool['name']); ?></h2>
                    </div>
                    <div class="tool-body">
                        <p class="tool-description"><?php echo htmlspecialchars($tool['description']); ?></p>
                        <a href="<?php echo htmlspecialchars($tool['path']); ?>" class="tool-link">
                            <?php if (isset($tool['type']) && $tool['type'] === 'markdown'): ?>
                                查看文档
                            <?php else: ?>
                                打开工具
                            <?php endif; ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="quick-links">
            <h3>快速链接</h3>
            <a href="/" class="quick-link"><i class="fas fa-home"></i> 首页</a>
            <a href="/dashboard.php" class="quick-link"><i class="fas fa-user"></i> 个人中心</a>
            <a href="/scripts/session_info.php" class="quick-link"><i class="fas fa-info-circle"></i> 会话信息</a>
            <a href="/scripts/sql_test/session_db_check.php" class="quick-link"><i class="fas fa-database"></i> 数据库检查</a>
        </div>
    </div>
    
    <div class="footer">
        <p>会话修复工具集 - 用于诊断和修复会话相关问题</p>
        <p>当前时间: <?php echo date('Y-m-d H:i:s'); ?></p>
    </div>
</body>
</html>
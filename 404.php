<?php
/**
 * 文件: 404.php
 * 描述: 404错误页面
 * 功能: 当访问不存在的页面或资源时显示
 * 创建时间: 2025-01-30
 */

// 读取系统设置
$configFile = 'config/system_settings.json';
$defaultSettings = [
    'basic' => [
        'site_name' => '壁纸喵 ° 不吃鱼',
        'site_subtitle' => '你的专属ai壁纸库'
    ]
];

if (file_exists($configFile)) {
    $content = file_get_contents($configFile);
    $settings = json_decode($content, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $siteName = $settings['basic']['site_name'] ?? $defaultSettings['basic']['site_name'];
    } else {
        $siteName = $defaultSettings['basic']['site_name'];
    }
} else {
    $siteName = $defaultSettings['basic']['site_name'];
}

$pageTitle = '页面未找到 - ' . $siteName;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="robots" content="noindex, nofollow">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }
        
        .error-container {
            text-align: center;
            background: white;
            padding: 3rem 2rem;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 90%;
        }
        
        .error-code {
            font-size: 6rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 1rem;
            line-height: 1;
        }
        
        .error-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #333;
        }
        
        .error-message {
            color: #666;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .btn {
            display: inline-block;
            padding: 0.75rem 2rem;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin: 0 0.5rem;
        }
        
        .btn:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102,126,234,0.3);
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #545b62;
            box-shadow: 0 10px 20px rgba(108,117,125,0.3);
        }
        
        @media (max-width: 480px) {
            .error-code {
                font-size: 4rem;
            }
            
            .error-container {
                padding: 2rem 1rem;
            }
            
            .btn {
                display: block;
                margin: 0.5rem 0;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">404</div>
        <h1 class="error-title">页面未找到</h1>
        <p class="error-message">
            抱歉，您访问的页面不存在或已被删除。<br>
            可能是链接地址有误或者内容已被移除。
        </p>
        <div class="error-actions">
            <a href="/" class="btn">返回首页</a>
            <a href="javascript:history.back()" class="btn btn-secondary">返回上页</a>
        </div>
    </div>
</body>
</html>
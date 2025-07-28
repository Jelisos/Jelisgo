<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>浏览器缓存清理工具</title>
    <style>
        body {
            font-family: 'Microsoft YaHei', Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
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
        .info-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .method-item {
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        .method-item:last-child {
            border-bottom: none;
        }
        .btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 5px;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #0069d9;
        }
        .btn-danger {
            background: #dc3545;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .btn-success {
            background: #28a745;
        }
        .btn-success:hover {
            background: #218838;
        }
        .status {
            margin-top: 20px;
            padding: 15px;
            border-radius: 5px;
            display: none;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .code-block {
            background: #f1f1f1;
            padding: 15px;
            border-radius: 5px;
            font-family: Consolas, monospace;
            margin: 15px 0;
            overflow-x: auto;
        }
        .note {
            font-size: 14px;
            color: #6c757d;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>浏览器缓存清理工具</h1>
        
        <div class="info-box">
            <h3>问题说明</h3>
            <p>当使用Ctrl+F5强制刷新页面时，有时浏览器仍然会使用缓存的内容，导致无法看到最新的页面更改。</p>
            <p>本工具提供了几种方法来强制清除浏览器缓存，确保您能看到最新的网页内容。</p>
        </div>

        <div class="info-box">
            <h3>清除缓存方法</h3>
            
            <div class="method-item">
                <h4>方法1: 使用JavaScript清除缓存并刷新</h4>
                <p>点击下面的按钮，将通过JavaScript添加随机参数来绕过缓存并刷新页面：</p>
                <button id="clearCacheJS" class="btn">清除缓存并刷新</button>
            </div>
            
            <div class="method-item">
                <h4>方法2: 添加禁用缓存的Meta标签</h4>
                <p>将以下代码添加到您的HTML页面的&lt;head&gt;部分：</p>
                <div class="code-block">
&lt;meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" /&gt;
&lt;meta http-equiv="Pragma" content="no-cache" /&gt;
&lt;meta http-equiv="Expires" content="0" /&gt;</div>
                <button id="copyMetaTags" class="btn">复制代码</button>
            </div>
            
            <div class="method-item">
                <h4>方法3: 添加版本参数到资源URL</h4>
                <p>为CSS、JavaScript等资源添加版本参数，例如：</p>
                <div class="code-block">
&lt;link rel="stylesheet" href="styles.css?v=1.1" /&gt;
&lt;script src="script.js?v=1.1"&gt;&lt;/script&gt;</div>
                <button id="copyVersionCode" class="btn">复制代码</button>
            </div>

            <div class="method-item">
                <h4>方法4: 清除所有浏览器缓存</h4>
                <p>这将尝试清除浏览器的所有缓存数据（仅适用于当前网站）：</p>
                <button id="clearAllCache" class="btn btn-danger">清除所有缓存</button>
                <p class="note">注意：此方法需要用户授予相关权限，可能不适用于所有浏览器。</p>
            </div>
        </div>

        <div class="info-box">
            <h3>指定页面刷新</h3>
            <p>输入您想要强制刷新的页面URL（相对于网站根目录）：</p>
            <input type="text" id="pageUrl" placeholder="例如: index.php 或 /admin/dashboard.html" style="width: 100%; padding: 8px; margin-bottom: 10px;">
            <button id="refreshSpecificPage" class="btn btn-success">强制刷新指定页面</button>
        </div>

        <div id="statusMessage" class="status"></div>
    </div>

    <script>
        // 显示状态消息
        function showStatus(message, isSuccess = true) {
            const statusEl = document.getElementById('statusMessage');
            statusEl.textContent = message;
            statusEl.style.display = 'block';
            
            if (isSuccess) {
                statusEl.className = 'status success';
            } else {
                statusEl.className = 'status error';
            }
            
            // 5秒后自动隐藏
            setTimeout(() => {
                statusEl.style.display = 'none';
            }, 5000);
        }

        // 复制文本到剪贴板
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                showStatus('代码已复制到剪贴板');
            }).catch(err => {
                showStatus('复制失败: ' + err, false);
            });
        }

        // 方法1: 使用JavaScript清除缓存并刷新
        document.getElementById('clearCacheJS').addEventListener('click', function() {
            // 添加随机参数到当前URL
            const currentUrl = window.location.href;
            const separator = currentUrl.indexOf('?') !== -1 ? '&' : '?';
            const newUrl = currentUrl + separator + '_=' + new Date().getTime();
            
            // 刷新页面
            window.location.href = newUrl;
        });

        // 方法2: 复制Meta标签代码
        document.getElementById('copyMetaTags').addEventListener('click', function() {
            const metaTags = '<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />\n<meta http-equiv="Pragma" content="no-cache" />\n<meta http-equiv="Expires" content="0" />';
            copyToClipboard(metaTags);
        });

        // 方法3: 复制版本参数代码
        document.getElementById('copyVersionCode').addEventListener('click', function() {
            const versionCode = '<link rel="stylesheet" href="styles.css?v=1.1" />\n<script src="script.js?v=1.1"><\/script>';
            copyToClipboard(versionCode);
        });

        // 方法4: 清除所有浏览器缓存
        document.getElementById('clearAllCache').addEventListener('click', function() {
            try {
                // 尝试清除缓存存储
                if ('caches' in window) {
                    caches.keys().then(function(names) {
                        for (let name of names) {
                            caches.delete(name);
                        }
                    });
                }
                
                // 尝试清除应用缓存（已废弃但仍可能存在）
                if (window.applicationCache) {
                    window.applicationCache.abort();
                }
                
                // 尝试清除localStorage和sessionStorage
                localStorage.clear();
                sessionStorage.clear();
                
                // 清除cookies
                const cookies = document.cookie.split(";");
                for (let i = 0; i < cookies.length; i++) {
                    const cookie = cookies[i];
                    const eqPos = cookie.indexOf("=");
                    const name = eqPos > -1 ? cookie.substr(0, eqPos) : cookie;
                    document.cookie = name + "=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/";
                }
                
                showStatus('已尝试清除所有缓存，请刷新页面查看效果');
            } catch (error) {
                showStatus('清除缓存时出错: ' + error.message, false);
            }
        });

        // 强制刷新指定页面
        document.getElementById('refreshSpecificPage').addEventListener('click', function() {
            const pageUrl = document.getElementById('pageUrl').value.trim();
            
            if (!pageUrl) {
                showStatus('请输入有效的页面URL', false);
                return;
            }
            
            // 构建完整URL
            let fullUrl = pageUrl;
            if (!pageUrl.startsWith('http') && !pageUrl.startsWith('/')) {
                fullUrl = '/' + pageUrl;
            }
            
            // 添加时间戳参数
            const separator = fullUrl.indexOf('?') !== -1 ? '&' : '?';
            fullUrl += separator + '_nocache=' + new Date().getTime();
            
            // 获取网站根URL
            const rootUrl = window.location.origin;
            
            // 如果是相对URL，添加网站根
            if (!pageUrl.startsWith('http')) {
                fullUrl = rootUrl + fullUrl;
            }
            
            // 在新标签页中打开
            window.open(fullUrl, '_blank');
            showStatus('已在新标签页中打开强制刷新的页面');
        });
    </script>
</body>
</html>
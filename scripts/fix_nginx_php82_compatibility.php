<?php
/**
 * Nginx 1.26 + PHP 8.2 环境兼容性修复脚本
 * 修复线上环境图片加载问题
 * 
 * @author AI Assistant
 * @date 2025-01-27
 * @version 1.0
 */

// 设置错误报告
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// 创建日志目录
$logDir = __DIR__ . '/../LOGS';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

/**
 * 记录日志
 */
function writeLog($message) {
    global $logDir;
    $logFile = $logDir . '/nginx_php82_fix_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[{$timestamp}] {$message}\n", FILE_APPEND | LOCK_EX);
    echo "<p>[{$timestamp}] {$message}</p>";
}

/**
 * 修复 membership_status.php 文件
 */
function fixMembershipStatusFile() {
    $filePath = __DIR__ . '/../api/vip/membership_status.php';
    
    if (!file_exists($filePath)) {
        writeLog('错误: membership_status.php 文件不存在');
        return false;
    }
    
    // 备份原文件
    $backupPath = $filePath . '.backup.' . date('Y-m-d_H-i-s');
    if (!copy($filePath, $backupPath)) {
        writeLog('错误: 无法备份原文件');
        return false;
    }
    writeLog('已备份原文件到: ' . basename($backupPath));
    
    // 生成修复后的文件内容
    $fixedContent = generateFixedMembershipStatus();
    
    if (file_put_contents($filePath, $fixedContent) === false) {
        writeLog('错误: 无法写入修复后的文件');
        return false;
    }
    
    writeLog('✅ membership_status.php 文件修复成功');
    return true;
}

/**
 * 生成修复后的 membership_status.php 内容
 */
function generateFixedMembershipStatus() {
    return '<?php
// 设置错误报告 - 适配 PHP 8.2
ini_set("display_errors", 0);
ini_set("log_errors", 1);
ini_set("error_log", __DIR__ . "/../../LOGS/php_errors.log");

/**
 * 独立会员状态API - Nginx 1.26 + PHP 8.2 兼容版本
 * 专门用于获取用户会员状态显示信息
 * 
 * @author AI Assistant
 * @date 2025-01-27
 * @version 4.0 - Nginx/PHP 8.2 Compatible
 */

require_once __DIR__ . "/../../config.php";

// 检查用户登录状态
function checkUserLogin() {
    // 获取请求头中的Authorization信息 - PHP 8.2 兼容
    $headers = function_exists("getallheaders") ? getallheaders() : [];
    
    // 如果 getallheaders 不可用，手动获取 Authorization 头
    if (empty($headers) && isset($_SERVER["HTTP_AUTHORIZATION"])) {
        $headers["Authorization"] = $_SERVER["HTTP_AUTHORIZATION"];
    }
    
    $user_id = null;
    
    // 从请求中获取用户ID
    if (isset($_GET["user_id"]) && !empty($_GET["user_id"])) {
        // 从GET参数获取用户ID
        $user_id = intval($_GET["user_id"]);
    } elseif (isset($headers["Authorization"]) && !empty($headers["Authorization"])) {
        // 从Authorization头获取用户ID
        $auth_parts = explode(" ", $headers["Authorization"]);
        if (count($auth_parts) == 2 && $auth_parts[0] == "Bearer") {
            $user_id = intval($auth_parts[1]);
        }
    }
    
    if (!$user_id) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "用户未登录",
            "code" => 401
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    return $user_id;
}

// 设置响应头 - Nginx 兼容
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: https://www.jelisgo.cn");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

// 处理OPTIONS请求
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

// 只允许GET请求
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);
    echo json_encode([
        "success" => false, 
        "message" => "只允许GET请求",
        "code" => 405
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 获取数据库连接 - PHP 8.2 优化
 */
function getDbConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PWD, $options);
        return $pdo;
    } catch (PDOException $e) {
        error_log("数据库连接失败: " . $e->getMessage());
        throw new Exception("数据库连接失败: " . $e->getMessage());
    }
}

/**
 * 检查必要的表和字段是否存在
 */
function checkRequiredTables($pdo) {
    try {
        // 检查users表是否存在
        $stmt = $pdo->query("SHOW TABLES LIKE \'users\'");
        if ($stmt->rowCount() === 0) {
            error_log("users表不存在");
            return false;
        }
        
        // 检查downloads表是否存在
        $stmt = $pdo->query("SHOW TABLES LIKE \'downloads\'");
        if ($stmt->rowCount() === 0) {
            error_log("downloads表不存在");
            return false;
        }
        
        // 检查users表必要字段
        $requiredFields = ["id", "membership_type", "membership_expires_at"];
        foreach ($requiredFields as $field) {
            $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE \'$field\'");
            if ($stmt->rowCount() === 0) {
                error_log("users表缺少字段: " . $field);
                return false;
            }
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("检查表结构失败: " . $e->getMessage());
        return false;
    }
}

/**
 * 获取用户会员状态信息 - PHP 8.2 优化
 */
function getMembershipStatus($user_id) {
    try {
        $pdo = getDbConnection();
        
        // 检查必要的表和字段是否存在
        if (!checkRequiredTables($pdo)) {
            throw new Exception("数据库结构不完整，请检查users和downloads表及其字段");
        }
        
        // 获取用户基本信息和会员信息
        $stmt = $pdo->prepare("
            SELECT 
                id,
                username,
                email,
                membership_type,
                membership_expires_at,
                download_quota,
                created_at
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            throw new Exception("用户不存在");
        }
        
        // 检查会员是否过期
        $is_expired = false;
        $days_remaining = null;
        $current_type = $user["membership_type"];
        
        if ($current_type === "monthly" && $user["membership_expires_at"]) {
            $expires_at = new DateTime($user["membership_expires_at"]);
            $now = new DateTime();
            
            if ($now >= $expires_at) {
                // 会员已过期，更新为免费用户
                $update_stmt = $pdo->prepare("
                    UPDATE users 
                    SET membership_type = \'free\', 
                        membership_expires_at = NULL, 
                        download_quota = 3 
                    WHERE id = ?
                ");
                $update_stmt->execute([$user_id]);
                
                $current_type = "free";
                $is_expired = true;
                $days_remaining = 0;
            } else {
                $interval = $now->diff($expires_at);
                $days_remaining = $interval->days;
            }
        }
        
        // 使用用户的download_quota字段作为剩余下载次数
        $current_quota = intval($user["download_quota"] ?? 0);
        
        // 根据会员类型设置显示信息
        if ($current_type === "permanent") {
            $quota_display = "无限下载";
            $daily_limit = -1;
            $daily_used = 0;
            $daily_remaining = -1;
        } else {
            $quota_display = $current_quota;
            $daily_limit = $current_quota;
            $daily_used = 0; // 简化逻辑，不统计每日使用
            $daily_remaining = $current_quota;
        }
        
        // 会员类型显示名称
        $type_display_map = [
            "free" => "免费用户",
            "monthly" => "月度会员",
            "permanent" => "永久会员"
        ];
        
        // 会员徽章样式
        $badge_class_map = [
            "free" => "bg-gray-100 text-gray-600",
            "monthly" => "bg-blue-100 text-blue-600",
            "permanent" => "bg-gradient-to-r from-yellow-400 to-orange-500 text-white"
        ];
        
        return [
            "user_id" => $user["id"],
            "username" => $user["username"],
            "membership" => [
                "type" => $current_type,
                "type_display" => $type_display_map[$current_type] ?? "未知",
                "badge_class" => $badge_class_map[$current_type] ?? "bg-gray-100 text-gray-600",
                "is_member" => $current_type !== "free",
                "is_expired" => $is_expired,
                "expires_at" => $user["membership_expires_at"],
                "expires_at_formatted" => $user["membership_expires_at"] ? date("Y年m月d日", strtotime($user["membership_expires_at"])) : null,
                "days_remaining" => $days_remaining
            ],
            "download" => [
                "daily_limit" => $daily_limit,
                "daily_used" => $daily_used,
                "daily_remaining" => $daily_remaining,
                "quota_display" => $quota_display,
                "usage_display" => $daily_limit === -1 ? "无限下载" : "{$daily_used}/{$daily_limit}",
                "can_download" => $daily_limit === -1 || $daily_remaining > 0
            ],
            "permissions" => [
                "can_download_premium" => $current_type !== "free" && !$is_expired,
                "can_download_free" => true,
                "has_quota_limit" => $current_type !== "permanent"
            ]
        ];
    } catch (Exception $e) {
        error_log("获取会员状态失败: " . $e->getMessage());
        throw $e;
    }
}

try {
    // 检查用户登录状态
    $user_id = checkUserLogin();
    
    // 获取会员状态信息
    $status = getMembershipStatus($user_id);
    
    // 返回成功响应
    echo json_encode([
        "success" => true,
        "message" => "获取会员状态成功",
        "data" => $status,
        "code" => 200,
        "timestamp" => date("Y-m-d H:i:s"),
        "server_info" => [
            "php_version" => PHP_VERSION,
            "server_software" => $_SERVER["SERVER_SOFTWARE"] ?? "Unknown"
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // 记录错误日志
    error_log("会员状态API错误: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "获取会员状态失败: " . $e->getMessage(),
        "code" => 500,
        "timestamp" => date("Y-m-d H:i:s"),
        "debug_info" => [
            "php_version" => PHP_VERSION,
            "error_file" => __FILE__,
            "server_software" => $_SERVER["SERVER_SOFTWARE"] ?? "Unknown"
        ]
    ], JSON_UNESCAPED_UNICODE);
}
?>';
}

/**
 * 修复 image_proxy.php 文件的防盗链检查
 */
function fixImageProxyFile() {
    $filePath = __DIR__ . '/../image_proxy.php';
    
    if (!file_exists($filePath)) {
        writeLog('警告: image_proxy.php 文件不存在，跳过修复');
        return false;
    }
    
    // 备份原文件
    $backupPath = $filePath . '.backup.' . date('Y-m-d_H-i-s');
    if (!copy($filePath, $backupPath)) {
        writeLog('错误: 无法备份 image_proxy.php 文件');
        return false;
    }
    writeLog('已备份 image_proxy.php 到: ' . basename($backupPath));
    
    // 读取原文件内容
    $content = file_get_contents($filePath);
    
    // 修复防盗链检查函数
    $oldCheckReferer = '/function checkReferer\(\$referer\) \{[^}]+\}/s';
    $newCheckReferer = 'function checkReferer($referer) {
    if (empty($referer)) {
        return false;
    }
    
    // 解析 referer URL
    $parsed = parse_url($referer);
    if (!$parsed || !isset($parsed["host"])) {
        return false;
    }
    
    $referer_host = strtolower($parsed["host"]);
    
    // 允许的域名列表 - 支持 www 和非 www 版本
    $allowed_domains = [
        "jelisgo.cn",
        "www.jelisgo.cn",
        "localhost"
    ];
    
    // 检查是否匹配允许的域名
    foreach ($allowed_domains as $domain) {
        if ($referer_host === $domain) {
            return true;
        }
        
        // 检查子域名
        if (str_ends_with($referer_host, "." . $domain)) {
            return true;
        }
    }
    
    return false;
}';
    
    $updatedContent = preg_replace($oldCheckReferer, $newCheckReferer, $content);
    
    if ($updatedContent === $content) {
        writeLog('警告: image_proxy.php 中未找到 checkReferer 函数，可能已经修复或结构不同');
        return false;
    }
    
    if (file_put_contents($filePath, $updatedContent) === false) {
        writeLog('错误: 无法写入修复后的 image_proxy.php 文件');
        return false;
    }
    
    writeLog('✅ image_proxy.php 防盗链检查修复成功');
    return true;
}

/**
 * 创建 Nginx 配置建议文件
 */
function createNginxConfigSuggestions() {
    $configPath = __DIR__ . '/../txt-md/nginx_php82_config_suggestions.md';
    
    $configContent = '# Nginx 1.26 + PHP 8.2 配置建议

## 问题分析

线上环境使用 Nginx 1.26 和 PHP 8.2，需要确保以下配置正确：

## Nginx 配置建议

### 1. PHP-FPM 配置

```nginx
location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
    
    # 增加超时时间
    fastcgi_read_timeout 300;
    fastcgi_connect_timeout 300;
    fastcgi_send_timeout 300;
    
    # 确保 Authorization 头传递
    fastcgi_param HTTP_AUTHORIZATION $http_authorization;
}
```

### 2. CORS 配置

```nginx
location /api/ {
    # 处理 OPTIONS 请求
    if ($request_method = OPTIONS) {
        add_header Access-Control-Allow-Origin "https://www.jelisgo.cn";
        add_header Access-Control-Allow-Methods "GET, POST, OPTIONS";
        add_header Access-Control-Allow-Headers "Content-Type, Authorization";
        add_header Access-Control-Allow-Credentials "true";
        return 204;
    }
    
    # 添加 CORS 头
    add_header Access-Control-Allow-Origin "https://www.jelisgo.cn";
    add_header Access-Control-Allow-Credentials "true";
    
    try_files $uri $uri/ =404;
}
```

### 3. 域名重定向配置

```nginx
server {
    listen 80;
    listen 443 ssl;
    server_name jelisgo.cn;
    return 301 https://www.jelisgo.cn$request_uri;
}
```

## PHP 8.2 兼容性检查

### 1. 已修复的兼容性问题

- ✅ `getallheaders()` 函数在某些环境下不可用的问题
- ✅ PDO 连接选项优化
- ✅ JSON 编码使用 `JSON_UNESCAPED_UNICODE` 标志
- ✅ 错误处理和日志记录优化

### 2. 需要检查的 PHP 配置

```ini
; php.ini 建议配置
max_execution_time = 300
memory_limit = 256M
post_max_size = 50M
upload_max_filesize = 50M
log_errors = On
error_log = /var/log/php/error.log
```

## 部署检查清单

- [ ] 确认 PHP 8.2-FPM 正常运行
- [ ] 确认 Nginx 配置包含正确的 fastcgi_param
- [ ] 确认数据库连接参数正确
- [ ] 确认 LOGS 目录存在且可写
- [ ] 确认 SSL 证书配置正确
- [ ] 测试 API 端点响应

## 故障排除

### 1. 检查 PHP-FPM 状态

```bash
sudo systemctl status php8.2-fpm
sudo tail -f /var/log/php8.2-fpm.log
```

### 2. 检查 Nginx 错误日志

```bash
sudo tail -f /var/log/nginx/error.log
```

### 3. 检查 PHP 错误日志

```bash
tail -f /path/to/your/project/LOGS/php_errors.log
```
';
    
    if (file_put_contents($configPath, $configContent) !== false) {
        writeLog('✅ 已创建 Nginx + PHP 8.2 配置建议文档');
        return true;
    } else {
        writeLog('错误: 无法创建配置建议文档');
        return false;
    }
}

/**
 * 主修复函数
 */
function main() {
    echo "<html><head><meta charset='utf-8'><title>Nginx 1.26 + PHP 8.2 兼容性修复</title></head><body>";
    echo "<h1>🔧 Nginx 1.26 + PHP 8.2 环境兼容性修复</h1>";
    
    writeLog('开始执行 Nginx 1.26 + PHP 8.2 兼容性修复...');
    
    $results = [];
    
    // 修复 membership_status.php
    writeLog('正在修复 membership_status.php...');
    $results['membership_status'] = fixMembershipStatusFile();
    
    // 修复 image_proxy.php
    writeLog('正在修复 image_proxy.php...');
    $results['image_proxy'] = fixImageProxyFile();
    
    // 创建配置建议
    writeLog('正在创建配置建议文档...');
    $results['config_suggestions'] = createNginxConfigSuggestions();
    
    // 输出修复结果
    echo "<h2>📋 修复结果总结</h2>";
    echo "<ul>";
    foreach ($results as $component => $success) {
        $status = $success ? '✅ 成功' : '❌ 失败';
        echo "<li><strong>{$component}</strong>: {$status}</li>";
    }
    echo "</ul>";
    
    // 部署说明
    echo "<h2>🚀 部署说明</h2>";
    echo "<ol>";
    echo "<li>将修复后的文件上传到线上环境</li>";
    echo "<li>确保 Nginx 配置正确（参考配置建议文档）</li>";
    echo "<li>重启 PHP-FPM 服务: <code>sudo systemctl restart php8.2-fpm</code></li>";
    echo "<li>重新加载 Nginx 配置: <code>sudo nginx -s reload</code></li>";
    echo "<li>测试 API 端点: <code>https://www.jelisgo.cn/api/vip/membership_status.php?user_id=1</code></li>";
    echo "<li>检查错误日志: <code>/path/to/project/LOGS/php_errors.log</code></li>";
    echo "</ol>";
    
    writeLog('修复脚本执行完成');
    echo "</body></html>";
}

// 执行修复
main();
?>
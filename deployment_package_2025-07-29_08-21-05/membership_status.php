<?php
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
        $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
        if ($stmt->rowCount() === 0) {
            error_log("users表不存在");
            return false;
        }
        
        // 检查users表必要字段
        $requiredFields = ["id", "membership_type", "membership_expires_at"];
        foreach ($requiredFields as $field) {
            $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE '$field'");
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
            throw new Exception("数据库结构不完整，请检查users表及其字段");
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
                    SET membership_type = 'free', 
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
?>
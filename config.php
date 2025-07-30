<?php
/**
 * 数据库配置文件 - 线上环境兼容版本
 * 自动检测环境并使用相应配置
 */

// 检测是否为线上环境
function isOnlineEnvironment() {
    // 检查域名（最可靠的方式）
    $serverName = $_SERVER["SERVER_NAME"] ?? "";
    if (strpos($serverName, "jelisgo.cn") !== false) {
        return true;
    }
    
    // 检查HTTP_HOST
    $httpHost = $_SERVER["HTTP_HOST"] ?? "";
    if (strpos($httpHost, "jelisgo.cn") !== false) {
        return true;
    }
    
    // 检查文档根目录（Linux服务器特征）
    $docRoot = $_SERVER["DOCUMENT_ROOT"] ?? "";
    if (strpos($docRoot, "/www/wwwroot") !== false) {
        return true;
    }
    
    // 检查服务器软件（但排除本地开发环境）
    $serverSoftware = $_SERVER["SERVER_SOFTWARE"] ?? "";
    if (strpos($serverSoftware, "nginx") !== false && 
        strpos($docRoot, "XAMPP") === false && 
        strpos($docRoot, "xampp") === false) {
        return true;
    }
    
    return false;
}

// 根据环境设置数据库配置
if (isOnlineEnvironment()) {
    // 线上环境配置
    define("DB_HOST", "localhost");
    define("DB_NAME", "wallpaper_db");
    define("DB_USER", "jelis-bzm");
    define("DB_PWD", "KWGzspzsfrTyKRPL");
    
    // 记录日志
    error_log("[Config] 使用线上环境数据库配置");
} else {
    // 本地开发环境配置
    define("DB_HOST", "localhost");
    define("DB_NAME", "wallpaper_db");
    define("DB_USER", "root");
    define("DB_PWD", "");
    
    // 记录日志
    error_log("[Config] 使用本地开发环境数据库配置");
}

// 通用配置
define("DB_CHARSET", "utf8mb4");

// 测试数据库连接
function testDatabaseConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
            DB_USER,
            DB_PWD,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]
        );
        
        // 测试查询
        $stmt = $pdo->query("SELECT 1 as test");
        $result = $stmt->fetch();
        
        if ($result && $result["test"] == 1) {
            error_log("[Config] 数据库连接测试成功");
            return true;
        } else {
            error_log("[Config] 数据库连接测试失败: 查询结果异常");
            return false;
        }
    } catch (Exception $e) {
        error_log("[Config] 数据库连接测试失败: " . $e->getMessage());
        return false;
    }
}

// 自动测试连接（仅在直接访问此文件时）
if (basename($_SERVER["SCRIPT_NAME"]) === "config.php") {
    header("Content-Type: application/json; charset=utf-8");
    
    $testResult = testDatabaseConnection();
    echo json_encode([
        "success" => $testResult,
        "environment" => isOnlineEnvironment() ? "online" : "local",
        "config" => [
            "host" => DB_HOST,
            "database" => DB_NAME,
            "user" => DB_USER,
            "charset" => DB_CHARSET
        ],
        "timestamp" => date("Y-m-d H:i:s")
    ], JSON_UNESCAPED_UNICODE);
}
?>
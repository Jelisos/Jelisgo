<?php
/**
 * 数据库配置文件
 * @author Claude
 * @date 2024-03-21
 * @update 2025-07-01 添加PDO连接支持
 */

// 数据库连接配置
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PWD', '');
define('DB_NAME', 'wallpaper_db');

/**
 * 获取数据库连接 (mysqli)
 * @return mysqli|false 数据库连接对象或false
 */
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PWD, DB_NAME);
    
    if ($conn->connect_error) {
        error_log("数据库连接失败: " . $conn->connect_error);
        return false;
    }
    
    $conn->set_charset("utf8mb4");
    return $conn;
}

/**
 * 获取PDO数据库连接
 * @return PDO|false PDO连接对象或false
 */
function getPDOConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PWD,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        error_log("PDO数据库连接失败: " . $e->getMessage());
        return false;
    }
}

/**
 * 关闭数据库连接
 * @param mysqli $conn 数据库连接对象
 */
function closeDBConnection($conn) {
    if ($conn) {
        $conn->close();
    }
}

// 全局PDO连接实例（用于API接口）
$pdo = getPDOConnection();
if (!$pdo) {
    error_log("无法建立数据库连接");
}
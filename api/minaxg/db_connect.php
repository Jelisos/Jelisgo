<?php
// db_connect.php - 数据库连接
require_once 'config.php';

function get_db_connection() {
    try {
        $conn = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PWD);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->exec("set names utf8mb4");
        return $conn;
    } catch(PDOException $e) {
        die("数据库连接失败: " . $e->getMessage());
    }
}
?>
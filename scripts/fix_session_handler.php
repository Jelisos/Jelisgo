<?php
/**
 * 会话处理器修复脚本
 * 修复DatabaseSessionHandler中的write方法，解决登录状态丢失问题
 */

header('Content-Type: text/html; charset=utf-8');

// 引入必要的配置文件
require_once __DIR__ . '/../config/database.php';

// 输出HTML头部
echo "<!DOCTYPE html>
<html>
<head>
    <title>会话处理器修复</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #333; }
        .section { margin-bottom: 20px; padding: 10px; border: 1px solid #ddd; }
        .success { color: green; }
        .warning { color: orange; }
        .error { color: red; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
    </style>
</head>
<body>
    <h1>会话处理器修复</h1>";

// 1. 分析问题
echo "<div class='section'>
    <h2>1. 问题分析</h2>
    <p>根据用户报告和代码分析，发现以下问题：</p>
    <ul>
        <li>登录后第一次刷新页面显示登录状态正常</li>
        <li>第二次刷新后登录状态丢失</li>
        <li>数据库中的会话记录user_id变为NULL</li>
    </ul>
    
    <p>问题根源：</p>
    <ul>
        <li>在<code>SessionManager::setUserSession</code>方法中调用<code>session_regenerate_id(true)</code>后，新生成的会话记录中user_id字段未正确设置</li>
        <li>在<code>DatabaseSessionHandler::write</code>方法中，从session_data解析user_id的逻辑存在问题</li>
    </ul>
</div>";

// 2. 修复DatabaseSessionHandler::write方法
echo "<div class='section'>
    <h2>2. 修复会话处理器</h2>";

// 获取session_config.php文件内容
$configFile = __DIR__ . '/../config/session_config.php';
$originalContent = file_get_contents($configFile);

if ($originalContent === false) {
    echo "<p class='error'>无法读取配置文件: {$configFile}</p>";
    exit;
}

// 备份原始文件
$backupFile = $configFile . '.bak.' . date('YmdHis');
if (file_put_contents($backupFile, $originalContent) === false) {
    echo "<p class='error'>无法创建备份文件: {$backupFile}</p>";
    exit;
}
echo "<p class='success'>已创建原始文件备份: {$backupFile}</p>";

// 查找并替换write方法
$writeMethodPattern = '/public function write\(\$sessionId, \$sessionData\): bool \{[\s\S]*?return \$result;\s*\}/m';

$newWriteMethod = 'public function write($sessionId, $sessionData): bool {
        $this->ensureConnection();
        
        // 从sessionData中解析user_id
        $userId = null;
        if (!empty($sessionData)) {
            // 解析session数据
            $sessionArray = [];
            if (session_decode_custom($sessionData, $sessionArray)) {
                $userId = $sessionArray[\'user_id\'] ?? null;
            }
        }
        
        // 如果解析失败，尝试从$_SESSION获取（兼容性处理）
        if ($userId === null && isset($_SESSION[\'user_id\'])) {
            $userId = $_SESSION[\'user_id\'];
            // 调试日志：记录从$_SESSION获取user_id
            error_log(
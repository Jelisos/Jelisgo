<?php
/**
 * 同步游客点赞记录到用户账户
 * 文件: api/sync_guest_likes.php
 * 描述: 用户登录后，将未登录状态下的点赞记录同步到用户账户
 * 功能: 提升用户体验，保留登录前的点赞数据
 */
session_start();
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
include_once '../config.php';
include_once './write_log.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['code' => 1, 'msg' => '请求方式错误']);
    exit;
}

// 检查用户登录状态
$user_id = null;
if (isset($_SESSION['user_id']) && $_SESSION['user_id']) {
    $user_id = $_SESSION['user_id'];
} elseif (isset($_SESSION['user']['id']) && $_SESSION['user']['id']) {
    $user_id = $_SESSION['user']['id'];
}

if (!$user_id) {
    echo json_encode(['code' => 1, 'msg' => '用户未登录']);
    exit;
}

// 获取用户IP地址
$ip_address = $_SERVER['REMOTE_ADDR'];
if (!$ip_address) {
    $ip_address = '127.0.0.1';
}

$db = new mysqli(DB_HOST, DB_USER, DB_PWD, DB_NAME);
if ($db->connect_errno) {
    echo json_encode(['code' => 1, 'msg' => '数据库连接失败']);
    exit;
}

try {
    // 开始事务
    $db->autocommit(false);
    
    // 1. 查找当前IP地址下的游客点赞记录（没有user_id的记录）
    $stmt_select = $db->prepare('SELECT wallpaper_id FROM wallpaper_likes WHERE ip_address = ? AND user_id IS NULL');
    $stmt_select->bind_param('s', $ip_address);
    $stmt_select->execute();
    $result = $stmt_select->get_result();
    
    $guest_likes = [];
    while ($row = $result->fetch_assoc()) {
        $guest_likes[] = $row['wallpaper_id'];
    }
    $stmt_select->close();
    
    $synced_count = 0;
    $skipped_count = 0;
    
    foreach ($guest_likes as $wallpaper_id) {
        // 2. 检查用户是否已经点赞过这个壁纸
        $stmt_check = $db->prepare('SELECT COUNT(*) FROM wallpaper_likes WHERE wallpaper_id = ? AND user_id = ?');
        $stmt_check->bind_param('si', $wallpaper_id, $user_id);
        $stmt_check->execute();
        $stmt_check->bind_result($count);
        $stmt_check->fetch();
        $stmt_check->close();
        
        if ($count > 0) {
            // 用户已经点赞过，删除游客记录
            $stmt_delete = $db->prepare('DELETE FROM wallpaper_likes WHERE wallpaper_id = ? AND ip_address = ? AND user_id IS NULL');
            $stmt_delete->bind_param('ss', $wallpaper_id, $ip_address);
            $stmt_delete->execute();
            $stmt_delete->close();
            $skipped_count++;
        } else {
            // 3. 将游客点赞记录转换为用户点赞记录
            $stmt_update = $db->prepare('UPDATE wallpaper_likes SET user_id = ? WHERE wallpaper_id = ? AND ip_address = ? AND user_id IS NULL');
            $stmt_update->bind_param('iss', $user_id, $wallpaper_id, $ip_address);
            $stmt_update->execute();
            $stmt_update->close();
            $synced_count++;
        }
    }
    
    // 提交事务
    $db->commit();
    $db->autocommit(true);
    
    // 记录日志
    sendDebugLog("点赞同步成功: user_id={$user_id}, ip_address={$ip_address}, synced={$synced_count}, skipped={$skipped_count}", 'like_debug_log.txt', 'append', 'sync_success');
    
    echo json_encode([
        'code' => 0,
        'msg' => '同步成功',
        'data' => [
            'synced_count' => $synced_count,
            'skipped_count' => $skipped_count,
            'total_guest_likes' => count($guest_likes)
        ]
    ]);
    
} catch (Exception $e) {
    // 回滚事务
    $db->rollback();
    $db->autocommit(true);
    
    sendDebugLog("点赞同步失败: " . $e->getMessage(), 'like_debug_log.txt', 'append', 'sync_fail');
    echo json_encode(['code' => 1, 'msg' => '同步失败: ' . $e->getMessage()]);
}

$db->close();
exit;
?>
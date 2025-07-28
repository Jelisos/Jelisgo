<?php
/**
 * 文件: api/get_like_count_v2.php
 * 描述: 点赞系统重构版本 - 获取点赞数量接口
 * 功能: 高性能获取壁纸点赞数量，支持批量查询和缓存
 * 依赖: config.php, write_log.php
 * 维护: 点赞数量查询逻辑修改请编辑此文件
 */

ob_start();
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/write_log.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    echo json_encode(['code' => 1, 'msg' => '请求方式错误']);
    ob_end_flush();
    exit;
}

/**
 * 点赞数量查询控制器
 */
class LikeCountController {
    private $db;
    
    public function __construct() {
        $this->initDatabase();
    }
    
    /**
     * 初始化数据库连接
     */
    private function initDatabase() {
        $this->db = new mysqli(DB_HOST, DB_USER, DB_PWD, DB_NAME);
        if ($this->db->connect_errno) {
            throw new Exception('数据库连接失败: ' . $this->db->connect_error);
        }
        $this->db->set_charset('utf8mb4');
    }
    
    /**
     * 获取单个壁纸的点赞数量
     * @param string $wallpaper_id 壁纸ID
     * @return int 点赞数量
     */
    public function getSingleLikeCount($wallpaper_id) {
        // 优先从缓存表获取
        $sql = "SELECT likes_count FROM wallpaper_likes_cache WHERE wallpaper_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $wallpaper_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            return (int)$row['likes_count'];
        }
        $stmt->close();
        
        // 缓存表没有数据，从主表获取
        $sql = "SELECT likes FROM wallpapers WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $wallpaper_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $count = (int)$row['likes'];
            $stmt->close();
            
            // 同步到缓存表
            $this->syncToCache($wallpaper_id, $count);
            return $count;
        }
        $stmt->close();
        
        return 0;
    }
    
    /**
     * 批量获取多个壁纸的点赞数量
     * @param array $wallpaper_ids 壁纸ID数组
     * @return array 点赞数量数组
     */
    public function getBatchLikeCounts($wallpaper_ids) {
        if (empty($wallpaper_ids)) {
            return [];
        }
        
        $counts = [];
        $missing_ids = [];
        
        // 首先从缓存表批量获取
        $placeholders = str_repeat('?,', count($wallpaper_ids) - 1) . '?';
        $sql = "SELECT wallpaper_id, likes_count FROM wallpaper_likes_cache WHERE wallpaper_id IN ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        $types = str_repeat('s', count($wallpaper_ids));
        $stmt->bind_param($types, ...$wallpaper_ids);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $counts[$row['wallpaper_id']] = (int)$row['likes_count'];
        }
        $stmt->close();
        
        // 找出缓存中没有的ID
        foreach ($wallpaper_ids as $id) {
            if (!isset($counts[$id])) {
                $missing_ids[] = $id;
            }
        }
        
        // 从主表获取缺失的数据
        if (!empty($missing_ids)) {
            $placeholders = str_repeat('?,', count($missing_ids) - 1) . '?';
            $sql = "SELECT id, likes FROM wallpapers WHERE id IN ({$placeholders})";
            $stmt = $this->db->prepare($sql);
            $types = str_repeat('s', count($missing_ids));
            $stmt->bind_param($types, ...$missing_ids);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $sync_data = [];
            while ($row = $result->fetch_assoc()) {
                $count = (int)$row['likes'];
                $counts[$row['id']] = $count;
                $sync_data[$row['id']] = $count;
            }
            $stmt->close();
            
            // 批量同步到缓存表
            if (!empty($sync_data)) {
                $this->batchSyncToCache($sync_data);
            }
        }
        
        // 确保所有请求的ID都有返回值
        foreach ($wallpaper_ids as $id) {
            if (!isset($counts[$id])) {
                $counts[$id] = 0;
            }
        }
        
        return $counts;
    }
    
    /**
     * 获取热门壁纸点赞排行
     * @param int $limit 返回数量
     * @param string $time_range 时间范围 (all|week|month)
     * @return array 排行数据
     */
    public function getTopLikedWallpapers($limit = 10, $time_range = 'all') {
        $limit = min(100, max(1, $limit)); // 限制在1-100之间
        
        if ($time_range === 'all') {
            // 从缓存表获取总排行
            $sql = "SELECT wlc.wallpaper_id, wlc.likes_count, w.title, w.file_path 
                    FROM wallpaper_likes_cache wlc 
                    LEFT JOIN wallpapers w ON wlc.wallpaper_id = w.id 
                    WHERE wlc.likes_count > 0 
                    ORDER BY wlc.likes_count DESC 
                    LIMIT ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $limit);
        } else {
            // 根据时间范围统计
            $date_condition = '';
            if ($time_range === 'week') {
                $date_condition = 'AND wl.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
            } elseif ($time_range === 'month') {
                $date_condition = 'AND wl.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
            }
            
            $sql = "SELECT wl.wallpaper_id, COUNT(*) as likes_count, w.title, w.file_path 
                    FROM wallpaper_likes wl 
                    LEFT JOIN wallpapers w ON wl.wallpaper_id = w.id 
                    WHERE wl.status = 1 {$date_condition} 
                    GROUP BY wl.wallpaper_id 
                    ORDER BY likes_count DESC 
                    LIMIT ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $limit);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $rankings = [];
        $rank = 1;
        while ($row = $result->fetch_assoc()) {
            $rankings[] = [
                'rank' => $rank++,
                'wallpaper_id' => $row['wallpaper_id'],
                'likes_count' => (int)$row['likes_count'],
                'title' => $row['title'],
                'file_path' => $row['file_path']
            ];
        }
        $stmt->close();
        
        return $rankings;
    }
    
    /**
     * 获取点赞统计信息
     * @return array 统计数据
     */
    public function getLikesStatistics() {
        // 总点赞数
        $sql = "SELECT SUM(likes_count) as total_likes, COUNT(*) as wallpapers_with_likes FROM wallpaper_likes_cache WHERE likes_count > 0";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        $cache_stats = $result->fetch_assoc();
        $stmt->close();
        
        // 今日点赞数
        $sql = "SELECT COUNT(*) as today_likes FROM wallpaper_likes WHERE DATE(created_at) = CURDATE() AND status = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        $today_stats = $result->fetch_assoc();
        $stmt->close();
        
        // 本周点赞数
        $sql = "SELECT COUNT(*) as week_likes FROM wallpaper_likes WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND status = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        $week_stats = $result->fetch_assoc();
        $stmt->close();
        
        // 活跃用户数（本周有点赞行为的用户）
        $sql = "SELECT COUNT(DISTINCT COALESCE(user_id, CONCAT('ip_', ip_address), CONCAT('device_', device_fingerprint))) as active_users 
                FROM wallpaper_likes 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND status = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_stats = $result->fetch_assoc();
        $stmt->close();
        
        return [
            'total_likes' => (int)($cache_stats['total_likes'] ?? 0),
            'wallpapers_with_likes' => (int)($cache_stats['wallpapers_with_likes'] ?? 0),
            'today_likes' => (int)($today_stats['today_likes'] ?? 0),
            'week_likes' => (int)($week_stats['week_likes'] ?? 0),
            'active_users_week' => (int)($user_stats['active_users'] ?? 0)
        ];
    }
    
    /**
     * 同步单个数据到缓存表
     */
    private function syncToCache($wallpaper_id, $count) {
        $sql = "INSERT INTO wallpaper_likes_cache (wallpaper_id, likes_count) VALUES (?, ?) ON DUPLICATE KEY UPDATE likes_count = ?, last_updated = CURRENT_TIMESTAMP";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('sii', $wallpaper_id, $count, $count);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * 批量同步数据到缓存表
     */
    private function batchSyncToCache($sync_data) {
        if (empty($sync_data)) {
            return;
        }
        
        $values = [];
        $params = [];
        foreach ($sync_data as $wallpaper_id => $count) {
            $values[] = '(?, ?)';
            $params[] = $wallpaper_id;
            $params[] = $count;
        }
        
        $sql = "INSERT INTO wallpaper_likes_cache (wallpaper_id, likes_count) VALUES " . implode(',', $values) . 
               " ON DUPLICATE KEY UPDATE likes_count = VALUES(likes_count), last_updated = CURRENT_TIMESTAMP";
        
        $stmt = $this->db->prepare($sql);
        $types = str_repeat('si', count($sync_data));
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * 关闭数据库连接
     */
    public function close() {
        if ($this->db) {
            $this->db->close();
        }
    }
}

// 主要处理逻辑
try {
    // 获取请求参数
    $wallpaper_id = $_GET['wallpaper_id'] ?? $_POST['wallpaper_id'] ?? '';
    $wallpaper_ids = $_GET['wallpaper_ids'] ?? $_POST['wallpaper_ids'] ?? '';
    $action = $_GET['action'] ?? $_POST['action'] ?? 'single';
    $limit = min(100, max(1, (int)($_GET['limit'] ?? $_POST['limit'] ?? 10)));
    $time_range = $_GET['time_range'] ?? $_POST['time_range'] ?? 'all';
    
    $controller = new LikeCountController();
    
    switch ($action) {
        case 'single':
            if (!$wallpaper_id) {
                ob_clean();
                echo json_encode(['code' => 1, 'msg' => '参数错误: wallpaper_id 不能为空']);
                ob_end_flush();
                exit;
            }
            
            $count = $controller->getSingleLikeCount($wallpaper_id);
            $response = [
                'code' => 0,
                'msg' => '获取成功',
                'wallpaper_id' => $wallpaper_id,
                'likes_count' => $count
            ];
            break;
            
        case 'batch':
            $ids_array = [];
            if (is_string($wallpaper_ids)) {
                $ids_array = array_filter(explode(',', $wallpaper_ids));
            } elseif (is_array($wallpaper_ids)) {
                $ids_array = $wallpaper_ids;
            }
            
            if (empty($ids_array)) {
                ob_clean();
                echo json_encode(['code' => 1, 'msg' => '参数错误: wallpaper_ids 不能为空']);
                ob_end_flush();
                exit;
            }
            
            $counts = $controller->getBatchLikeCounts($ids_array);
            $response = [
                'code' => 0,
                'msg' => '获取成功',
                'data' => $counts
            ];
            break;
            
        case 'ranking':
            $rankings = $controller->getTopLikedWallpapers($limit, $time_range);
            $response = [
                'code' => 0,
                'msg' => '获取排行成功',
                'data' => [
                    'rankings' => $rankings,
                    'limit' => $limit,
                    'time_range' => $time_range
                ]
            ];
            break;
            
        case 'statistics':
            $stats = $controller->getLikesStatistics();
            $response = [
                'code' => 0,
                'msg' => '获取统计成功',
                'data' => $stats
            ];
            break;
            
        default:
            $response = [
                'code' => 1,
                'msg' => '不支持的操作类型'
            ];
            break;
    }
    
    $controller->close();
    
    ob_clean();
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    ob_end_flush();
    
} catch (Exception $e) {
    sendDebugLog("点赞数量查询异常: " . $e->getMessage(), 'like_debug_log.txt', 'append', 'get_like_count_v2_error');
    ob_clean();
    echo json_encode([
        'code' => 500,
        'msg' => '系统错误: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
}

exit;
?>
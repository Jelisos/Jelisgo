<?php
/**
 * 文件: api/check_like_v2.php
 * 描述: 点赞系统重构版本 - 检查点赞状态接口
 * 功能: 支持多种用户识别方式的点赞状态查询
 * 依赖: config.php, write_log.php
 * 维护: 点赞状态查询逻辑修改请编辑此文件
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
 * 点赞状态检查控制器
 */
class LikeStatusChecker {
    private $db;
    private $user_identifier;
    
    public function __construct() {
        $this->initDatabase();
        $this->user_identifier = $this->getUserIdentifier();
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
     * 获取用户标识信息
     * 统一使用未登录逻辑：device_fingerprint > ip_address
     */
    private function getUserIdentifier() {
        // 获取设备指纹
        $device_fingerprint = $_POST['device_fingerprint'] ?? $_GET['device_fingerprint'] ?? null;
        
        // 获取IP地址
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        
        // 设备指纹识别
        if ($device_fingerprint && strlen($device_fingerprint) >= 16) {
            return [
                'type' => 'device_fingerprint',
                'value' => $device_fingerprint,
                'priority' => 2
            ];
        }
        
        // IP地址识别（兜底）
        return [
            'type' => 'ip_address',
            'value' => $ip_address,
            'priority' => 3
        ];
    }
    
    /**
     * 检查单个壁纸的点赞状态
     * @param string $wallpaper_id 壁纸ID
     * @return bool 是否已点赞
     */
    public function checkSingleLike($wallpaper_id) {
        $type = $this->user_identifier['type'];
        $value = $this->user_identifier['value'];
        
        // 统一使用设备指纹或IP地址查询，不区分登录状态
        if ($type === 'device_fingerprint') {
            $sql = "SELECT 1 FROM wallpaper_likes WHERE wallpaper_id = ? AND device_fingerprint = ? LIMIT 1";
        } else {
            $sql = "SELECT 1 FROM wallpaper_likes WHERE wallpaper_id = ? AND ip_address = ? LIMIT 1";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ss', $wallpaper_id, $value);
        
        $stmt->execute();
        $result = $stmt->get_result();
        $has_like = $result->num_rows > 0;
        $stmt->close();
        return $has_like;
    }
    
    /**
     * 批量检查多个壁纸的点赞状态
     * @param array $wallpaper_ids 壁纸ID数组
     * @return array 点赞状态数组
     */
    public function checkBatchLikes($wallpaper_ids) {
        if (empty($wallpaper_ids)) {
            return [];
        }
        
        $identifier = $this->user_identifier;
        $placeholders = str_repeat('?,', count($wallpaper_ids) - 1) . '?';
        
        // 统一使用设备指纹或IP地址查询，不区分登录状态
        if ($identifier['type'] === 'device_fingerprint') {
            $sql = "SELECT wallpaper_id FROM wallpaper_likes WHERE wallpaper_id IN ({$placeholders}) AND device_fingerprint = ?";
        } else {
            $sql = "SELECT wallpaper_id FROM wallpaper_likes WHERE wallpaper_id IN ({$placeholders}) AND ip_address = ?";
        }
        
        $params = array_merge($wallpaper_ids, [$identifier['value']]);
        $types = str_repeat('s', count($wallpaper_ids)) . 's';
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $liked_ids = [];
        while ($row = $result->fetch_assoc()) {
            $liked_ids[] = $row['wallpaper_id'];
        }
        $stmt->close();
        
        // 构建返回数组
        $status_array = [];
        foreach ($wallpaper_ids as $id) {
            $status_array[$id] = in_array($id, $liked_ids);
        }
        
        return $status_array;
    }
    
    /**
     * 获取壁纸的点赞总数
     * @param string $wallpaper_id 壁纸ID
     * @return int 点赞总数
     */
    public function getLikesCount($wallpaper_id) {
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
     * 同步数据到缓存表
     */
    private function syncToCache($wallpaper_id, $count) {
        $sql = "INSERT INTO wallpaper_likes_cache (wallpaper_id, likes_count) VALUES (?, ?) ON DUPLICATE KEY UPDATE likes_count = ?, last_updated = CURRENT_TIMESTAMP";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('sii', $wallpaper_id, $count, $count);
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
    $include_count = $_GET['include_count'] ?? $_POST['include_count'] ?? 'false';
    
    $checker = new LikeStatusChecker();
    
    // 批量查询模式
    if ($wallpaper_ids) {
        $ids_array = [];
        if (is_string($wallpaper_ids)) {
            $ids_array = array_filter(explode(',', $wallpaper_ids));
        } elseif (is_array($wallpaper_ids)) {
            $ids_array = $wallpaper_ids;
        }
        
        if (empty($ids_array)) {
            ob_clean();
            echo json_encode(['code' => 1, 'msg' => '壁纸ID列表不能为空']);
            ob_end_flush();
            exit;
        }
        
        $status_array = $checker->checkBatchLikes($ids_array);
        $result = [
            'code' => 0,
            'msg' => '查询成功',
            'data' => $status_array
        ];
        
        // 如果需要包含点赞数
        if ($include_count === 'true') {
            $counts = [];
            foreach ($ids_array as $id) {
                $counts[$id] = $checker->getLikesCount($id);
            }
            $result['counts'] = $counts;
        }
        
    } elseif ($wallpaper_id) {
        // 单个查询模式
        $is_liked = $checker->checkSingleLike($wallpaper_id);
        $result = [
            'code' => 0,
            'msg' => '查询成功',
            'is_liked' => $is_liked
        ];
        
        // 如果需要包含点赞数
        if ($include_count === 'true') {
            $result['likes_count'] = $checker->getLikesCount($wallpaper_id);
        }
        
    } else {
        ob_clean();
        echo json_encode(['code' => 1, 'msg' => '参数错误: 需要提供wallpaper_id或wallpaper_ids']);
        ob_end_flush();
        exit;
    }
    
    $checker->close();
    
    ob_clean();
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    ob_end_flush();
    
} catch (Exception $e) {
    sendDebugLog("点赞状态查询异常: " . $e->getMessage(), 'like_debug_log.txt', 'append', 'check_like_v2_error');
    ob_clean();
    echo json_encode([
        'code' => 500,
        'msg' => '系统错误: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
}

exit;
?>
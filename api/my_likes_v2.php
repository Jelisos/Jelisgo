<?php
/**
 * 用户点赞查询接口V2 - 高性能版本
 * 文件: api/my_likes_v2.php
 * 描述: 重构后的用户点赞查询接口，支持缓存、统一用户识别和性能优化
 * 功能: 获取用户点赞的壁纸ID列表，支持分页和缓存
 */

ob_start();
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/write_log.php';

/**
 * 用户点赞查询控制器V2
 */
class UserLikesControllerV2 {
    private $db;
    private $user_identifier;
    private $cache_enabled = true;
    private $cache_ttl = 300; // 5分钟缓存
    
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
     * 参考旧版逻辑：简化为登录/未登录两种状态
     */
    private function getUserIdentifier() {
        // 检查登录状态
        $user_id = null;
        if (isset($_SESSION['user_id']) && $_SESSION['user_id']) {
            $user_id = $_SESSION['user_id'];
        } elseif (isset($_SESSION['user']['id']) && $_SESSION['user']['id']) {
            $user_id = $_SESSION['user']['id'];
        }
        
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        
        if ($user_id) {
            // 登录用户：使用user_id作为主要标识
            return [
                'type' => 'user_id',
                'value' => $user_id,
                'cache_key' => 'user_likes:' . $user_id
            ];
        } else {
            // 未登录用户：仅使用IP地址
            return [
                'type' => 'ip_address',
                'value' => $ip_address,
                'cache_key' => 'ip_likes:' . str_replace('.', '_', $ip_address)
            ];
        }
    }
    
    /**
     * 从缓存获取数据（模拟Redis缓存，实际项目中可替换为真实Redis）
     */
    private function getFromCache($cache_key) {
        if (!$this->cache_enabled) {
            return null;
        }
        
        // 使用文件缓存模拟（生产环境建议使用Redis）
        $cache_file = sys_get_temp_dir() . '/likes_cache_' . md5($cache_key) . '.json';
        
        if (file_exists($cache_file)) {
            $cache_data = json_decode(file_get_contents($cache_file), true);
            if ($cache_data && isset($cache_data['expires']) && $cache_data['expires'] > time()) {
                return $cache_data['data'];
            } else {
                // 缓存过期，删除文件
                unlink($cache_file);
            }
        }
        
        return null;
    }
    
    /**
     * 设置缓存数据
     */
    private function setCache($cache_key, $data) {
        if (!$this->cache_enabled) {
            return;
        }
        
        $cache_file = sys_get_temp_dir() . '/likes_cache_' . md5($cache_key) . '.json';
        $cache_data = [
            'data' => $data,
            'expires' => time() + $this->cache_ttl,
            'created' => time()
        ];
        
        file_put_contents($cache_file, json_encode($cache_data));
    }
    
    /**
     * 从数据库查询用户点赞列表
     * 参考旧版逻辑：登录用户查user_id，未登录用户查IP
     */
    private function queryFromDatabase($page = 1, $limit = 1000) {
        $identifier = $this->user_identifier;
        $offset = ($page - 1) * $limit;
        
        if ($identifier['type'] === 'user_id') {
            // 登录用户：查询user_id的点赞记录
            $sql = "SELECT wallpaper_id FROM wallpaper_likes WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('iii', $identifier['value'], $limit, $offset);
        } else {
            // 未登录用户：查询IP地址的点赞记录（user_id为NULL）
            $sql = "SELECT wallpaper_id FROM wallpaper_likes WHERE ip_address = ? AND user_id IS NULL ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('sii', $identifier['value'], $limit, $offset);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $likes = [];
        while ($row = $result->fetch_assoc()) {
            $likes[] = $row['wallpaper_id'];
        }
        
        $stmt->close();
        return $likes;
    }
    
    /**
     * 使用存储过程查询（高性能版本）
     * 参考旧版逻辑：简化为登录/未登录两种状态
     */
    private function queryUsingStoredProcedure() {
        $identifier = $this->user_identifier;
        
        $user_id = ($identifier['type'] === 'user_id') ? $identifier['value'] : null;
        $ip_address = ($identifier['type'] === 'ip_address') ? $identifier['value'] : null;
        
        // 简化存储过程调用，只传递user_id和ip_address
        $sql = "CALL GetUserLikes(?, ?, NULL)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('is', $user_id, $ip_address);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $likes = [];
        while ($row = $result->fetch_assoc()) {
            $likes[] = $row['wallpaper_id'];
        }
        
        $stmt->close();
        return $likes;
    }
    
    /**
     * 获取用户点赞统计信息
     */
    private function getUserLikesStats() {
        $identifier = $this->user_identifier;
        
        if ($identifier['type'] !== 'user_id') {
            return null; // 只有登录用户才有统计信息
        }
        
        $sql = "SELECT total_likes, last_like_at FROM user_likes_summary WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $identifier['value']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            return [
                'total_likes' => (int)$row['total_likes'],
                'last_like_at' => $row['last_like_at']
            ];
        }
        
        $stmt->close();
        return null;
    }
    
    /**
     * 检查特定壁纸的点赞状态
     * 参考旧版逻辑：登录用户查user_id，未登录用户查IP
     */
    public function checkLikeStatus($wallpaper_id) {
        $identifier = $this->user_identifier;
        
        if ($identifier['type'] === 'user_id') {
            // 登录用户：检查user_id的点赞记录
            $sql = "SELECT COUNT(*) as count FROM wallpaper_likes WHERE wallpaper_id = ? AND user_id = ? AND status = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('si', $wallpaper_id, $identifier['value']);
        } else {
            // 未登录用户：检查IP地址的点赞记录（user_id为NULL）
            $sql = "SELECT COUNT(*) as count FROM wallpaper_likes WHERE wallpaper_id = ? AND ip_address = ? AND user_id IS NULL AND status = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('ss', $wallpaper_id, $identifier['value']);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return (int)$row['count'] > 0;
    }
    
    /**
     * 获取用户点赞列表（主方法）
     */
    public function getUserLikes($use_cache = true, $use_stored_procedure = true, $page = 1, $limit = 1000) {
        try {
            $cache_key = $this->user_identifier['cache_key'] . ':page:' . $page;
            
            // 尝试从缓存获取
            if ($use_cache) {
                $cached_data = $this->getFromCache($cache_key);
                if ($cached_data !== null) {
                    return [
                        'code' => 0,
                        'msg' => 'success',
                        'data' => $cached_data['likes'],
                        'stats' => $cached_data['stats'] ?? null,
                        'cache_hit' => true,
                        'user_identifier_type' => $this->user_identifier['type']
                    ];
                }
            }
            
            // 从数据库查询
            if ($use_stored_procedure) {
                $likes = $this->queryUsingStoredProcedure();
            } else {
                $likes = $this->queryFromDatabase($page, $limit);
            }
            
            // 获取用户统计信息
            $stats = $this->getUserLikesStats();
            
            $result_data = [
                'likes' => $likes,
                'stats' => $stats,
                'count' => count($likes),
                'page' => $page,
                'limit' => $limit
            ];
            
            // 写入缓存
            if ($use_cache) {
                $this->setCache($cache_key, $result_data);
            }
            
            // 记录查询日志
            $identifier_info = $this->user_identifier['type'] . ':' . $this->user_identifier['value'];
            sendDebugLog(
                "用户点赞查询成功: identifier={$identifier_info}, count=" . count($likes) . ", page={$page}",
                'like_debug_log.txt',
                'append',
                'likes_query_v2_success'
            );
            
            return [
                'code' => 0,
                'msg' => 'success',
                'data' => $result_data['likes'],
                'stats' => $stats,
                'cache_hit' => false,
                'user_identifier_type' => $this->user_identifier['type'],
                'total_count' => count($likes)
            ];
            
        } catch (Exception $e) {
            sendDebugLog(
                "用户点赞查询异常: error={$e->getMessage()}",
                'like_debug_log.txt',
                'append',
                'likes_query_v2_error'
            );
            
            return [
                'code' => 500,
                'msg' => '查询失败: ' . $e->getMessage(),
                'data' => []
            ];
        } catch (mysqli_sql_exception $e) {
            sendDebugLog(
                "用户点赞查询数据库异常: error={$e->getMessage()}",
                'like_debug_log.txt',
                'append',
                'likes_query_v2_db_error'
            );
            
            return [
                'code' => 500,
                'msg' => '数据库查询失败: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }
}

// 处理请求
try {
    // 获取请求参数
    $input = json_decode(file_get_contents('php://input'), true);
    $wallpaper_id = isset($input['wallpaper_id']) ? trim($input['wallpaper_id']) : '';
    $use_cache = isset($input['use_cache']) ? (bool)$input['use_cache'] : true;
    $use_stored_procedure = isset($input['use_stored_procedure']) ? (bool)$input['use_stored_procedure'] : true;
    $page = isset($input['page']) ? max(1, (int)$input['page']) : 1;
    $limit = isset($input['limit']) ? min(1000, max(10, (int)$input['limit'])) : 1000;
    
    $controller = new UserLikesControllerV2();
    
    // 如果指定了wallpaper_id，检查单个壁纸的点赞状态
    if ($wallpaper_id) {
        $isLiked = $controller->checkLikeStatus($wallpaper_id);
        $result = [
            'code' => 0,
            'msg' => 'success',
            'data' => [
                'wallpaper_id' => $wallpaper_id,
                'is_liked' => $isLiked
            ]
        ];
    } else {
        // 获取用户所有点赞
        $result = $controller->getUserLikes($use_cache, $use_stored_procedure, $page, $limit);
    }
    
    ob_clean();
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    ob_end_flush();
    
} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'code' => 500,
        'msg' => '系统初始化失败: ' . $e->getMessage(),
        'data' => []
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
}

exit;
?>
<?php
/**
 * 用户点赞查询接口V2 - 修复字符集冲突版本
 * 文件: api/my_likes_v2_fixed.php
 * 描述: 临时修复字符集冲突问题的版本
 */

ob_start();
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/write_log.php';

/**
 * 用户点赞查询控制器V2 - 修复版
 */
class UserLikesControllerV2Fixed {
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
     * 优先级: user_id > device_fingerprint > ip_address
     */
    private function getUserIdentifier() {
        // 获取设备指纹
        $input = json_decode(file_get_contents('php://input'), true);
        $device_fingerprint = isset($input['device_fingerprint']) ? trim($input['device_fingerprint']) : '';
        
        // 检查登录状态
        $user_id = null;
        if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
            $user_id = (int)$_SESSION['user_id'];
        }
        
        // 获取IP地址
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        
        // 确定用户标识优先级
        if ($user_id) {
            return ['type' => 'user_id', 'value' => $user_id];
        } elseif (!empty($device_fingerprint)) {
            return ['type' => 'device_fingerprint', 'value' => $device_fingerprint];
        } else {
            return ['type' => 'ip_address', 'value' => $ip_address];
        }
    }
    
    /**
     * 获取用户点赞列表
     */
    public function getUserLikes($page = 1, $limit = 1000, $use_cache = true, $use_stored_procedure = false) {
        try {
            // 检查缓存
            if ($use_cache) {
                $cache_key = $this->getCacheKey($page, $limit);
                $cached_data = $this->getCache($cache_key);
                if ($cached_data !== null) {
                    return [
                        'code' => 0,
                        'msg' => '获取成功（缓存）',
                        'data' => $cached_data,
                        'cache_hit' => true,
                        'user_identifier_type' => $this->user_identifier['type']
                    ];
                }
            }
            
            // 从数据库查询
            $likes = $this->queryFromDatabase($page, $limit);
            
            // 设置缓存
            if ($use_cache) {
                $this->setCache($cache_key, $likes);
            }
            
            return [
                'code' => 0,
                'msg' => '获取成功',
                'data' => $likes,
                'cache_hit' => false,
                'user_identifier_type' => $this->user_identifier['type']
            ];
            
        } catch (Exception $e) {
            error_log("[my_likes_v2_fixed.php] 获取用户点赞失败: " . $e->getMessage());
            return [
                'code' => 1,
                'msg' => '查询失败: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }
    
    /**
     * 生成缓存键
     */
    private function getCacheKey($page, $limit) {
        $identifier = $this->user_identifier;
        return "user_likes_{$identifier['type']}_{$identifier['value']}_p{$page}_l{$limit}";
    }
    
    /**
     * 获取缓存数据
     */
    private function getCache($cache_key) {
        if (!$this->cache_enabled) {
            return null;
        }
        
        $cache_file = sys_get_temp_dir() . '/likes_cache_' . md5($cache_key) . '.json';
        
        if (file_exists($cache_file)) {
            $cache_content = file_get_contents($cache_file);
            $cache_data = json_decode($cache_content, true);
            
            if ($cache_data && isset($cache_data['expires']) && $cache_data['expires'] > time()) {
                return $cache_data['data'];
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
     * 从数据库查询用户点赞数据 - 修复字符集冲突版本
     */
    private function queryFromDatabase($page = 1, $limit = 1000) {
        $identifier = $this->user_identifier;
        $offset = ($page - 1) * $limit;
        
        switch ($identifier['type']) {
            case 'user_id':
                $sql = "SELECT wallpaper_id FROM wallpaper_likes WHERE user_id = ? AND status = 1 ORDER BY created_at DESC LIMIT ? OFFSET ?";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('iii', $identifier['value'], $limit, $offset);
                break;
                
            case 'device_fingerprint':
                $sql = "SELECT wallpaper_id FROM wallpaper_likes WHERE device_fingerprint COLLATE utf8mb4_unicode_ci = ? COLLATE utf8mb4_unicode_ci AND user_id IS NULL AND status = 1 ORDER BY created_at DESC LIMIT ? OFFSET ?";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('sii', $identifier['value'], $limit, $offset);
                break;
                
            case 'ip_address':
                $sql = "SELECT wallpaper_id FROM wallpaper_likes WHERE ip_address COLLATE utf8mb4_unicode_ci = ? COLLATE utf8mb4_unicode_ci AND user_id IS NULL AND device_fingerprint IS NULL AND status = 1 ORDER BY created_at DESC LIMIT ? OFFSET ?";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('sii', $identifier['value'], $limit, $offset);
                break;
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
     * 关闭数据库连接
     */
    public function close() {
        if ($this->db) {
            $this->db->close();
        }
    }
}

// 主执行逻辑
try {
    $controller = new UserLikesControllerV2Fixed();
    
    // 获取请求参数
    $input = json_decode(file_get_contents('php://input'), true);
    $page = isset($input['page']) ? (int)$input['page'] : 1;
    $limit = isset($input['limit']) ? (int)$input['limit'] : 1000;
    $use_cache = isset($input['use_cache']) ? (bool)$input['use_cache'] : true;
    $use_stored_procedure = isset($input['use_stored_procedure']) ? (bool)$input['use_stored_procedure'] : false;
    
    // 参数验证
    if ($page < 1) $page = 1;
    if ($limit < 1 || $limit > 10000) $limit = 1000;
    
    $result = $controller->getUserLikes($page, $limit, $use_cache, $use_stored_procedure);
    $controller->close();
    
    ob_clean();
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("[my_likes_v2_fixed.php] 系统错误: " . $e->getMessage());
    ob_clean();
    echo json_encode([
        'code' => 1,
        'msg' => '系统错误，请稍后重试',
        'data' => []
    ], JSON_UNESCAPED_UNICODE);
}
?>
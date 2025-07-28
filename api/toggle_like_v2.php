<?php
/**
 * 点赞系统V2 - 统一点赞切换接口
 * 文件: api/toggle_like_v2.php
 * 描述: 重构后的点赞接口，支持统一用户识别、性能优化和批量处理
 * 功能: 处理点赞/取消点赞操作，支持登录用户、设备指纹和IP识别
 */

ob_start();
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/write_log.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    echo json_encode(['code' => 1, 'msg' => '请求方式错误']);
    ob_end_flush();
    exit;
}

// 获取请求数据
$input = json_decode(file_get_contents('php://input'), true);
$wallpaper_id = isset($input['wallpaper_id']) ? (string)$input['wallpaper_id'] : '';
$device_fingerprint = isset($input['device_fingerprint']) ? trim($input['device_fingerprint']) : '';

if (!$wallpaper_id) {
    ob_clean();
    echo json_encode(['code' => 1, 'msg' => '参数错误: wallpaper_id 不能为空']);
    ob_end_flush();
    exit;
}

/**
 * 点赞控制器类
 */
class LikeControllerV2 {
    private $db;
    private $user_identifier;
    private $wallpaper_id;
    
    public function __construct($wallpaper_id) {
        $this->wallpaper_id = $wallpaper_id;
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
        global $device_fingerprint;
        
        // 优先使用设备指纹
        if ($device_fingerprint && strlen($device_fingerprint) >= 16) {
            return [
                'type' => 'device_fingerprint',
                'value' => $device_fingerprint,
                'priority' => 2
            ];
        }
        
        // 其次使用IP地址
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        return [
            'type' => 'ip_address',
            'value' => $ip_address,
            'priority' => 3
        ];
    }
    
    /**
     * 检查现有点赞记录
     * @return array|null 现有记录或null
     */
    private function checkExistingLike() {
        $type = $this->user_identifier['type'];
        $value = $this->user_identifier['value'];
        
        // 统一使用设备指纹或IP地址查询，不区分登录状态
        $column = $type;
        $stmt = $this->db->prepare("SELECT id FROM wallpaper_likes WHERE wallpaper_id = ? AND {$column} = ?");
        $stmt->bind_param('ss', $this->wallpaper_id, $value);
        
        $stmt->execute();
        $result = $stmt->get_result();
        $record = $result->fetch_assoc();
        $stmt->close();
        return $record;
    }
    
    /**
     * 执行点赞操作
     */
    private function performLike() {
        $identifier = $this->user_identifier;
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        
        // 检查是否有登录用户，如果有则记录user_id但不影响查询逻辑
        $user_id = null;
        if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
        } elseif (isset($_SESSION['user']['id']) && !empty($_SESSION['user']['id'])) {
            $user_id = $_SESSION['user']['id'];
        }
        
        // 准备插入数据
        $device_fp = ($identifier['type'] === 'device_fingerprint') ? $identifier['value'] : null;
        $ip_addr = ($identifier['type'] === 'ip_address') ? $identifier['value'] : $ip_address;
        
        // 使用 INSERT ... ON DUPLICATE KEY UPDATE 避免重复键错误
        $sql = "INSERT INTO wallpaper_likes (wallpaper_id, user_id, ip_address, device_fingerprint, status) VALUES (?, ?, ?, ?, 1) ON DUPLICATE KEY UPDATE status = 1, updated_at = CURRENT_TIMESTAMP";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('siss', $this->wallpaper_id, $user_id, $ip_addr, $device_fp);
        
        $stmt->execute();
        $success = $stmt->affected_rows > 0;
        $stmt->close();
        
        if ($success) {
            $this->updateLikesCache(1);
            $this->logOperation('like');
        }
        
        return $success;
    }
    
    /**
     * 执行取消点赞操作
     * 参考旧版逻辑：直接删除点赞记录
     */
    private function performUnlike() {
        $identifier = $this->user_identifier;
        
        // 统一使用设备指纹或IP地址进行删除，不区分登录状态
        $column = $identifier['type'];
        $sql = "DELETE FROM wallpaper_likes WHERE wallpaper_id = ? AND {$column} = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ss', $this->wallpaper_id, $identifier['value']);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $stmt->close();
            
            // 记录批量处理日志
            $this->logBatchOperation('unlike');
            
            // 实时更新缓存
            $this->updateLikesCache(-1);
            
            return true;
        } else {
            $stmt->close();
            return false;
        }
    }
    
    /**
     * 记录操作日志
     */
    private function logOperation($action) {
        // 使用现有的批量日志记录方法
        $this->logBatchOperation($action);
    }
    
    /**
     * 记录批量操作日志
     */
    private function logBatchOperation($action) {
        $identifier = $this->user_identifier;
        
        // 根据用户标识类型设置对应字段
        $user_id = ($identifier['type'] === 'user_id') ? $identifier['value'] : null;
        $ip_address = ($identifier['type'] === 'ip_address') ? $identifier['value'] : $_SERVER['REMOTE_ADDR'];
        $device_fingerprint = ($identifier['type'] === 'device_fingerprint') ? $identifier['value'] : null;
        
        $sql = "INSERT INTO wallpaper_likes_batch_log (wallpaper_id, action, user_id, ip_address, device_fingerprint) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ssiss', $this->wallpaper_id, $action, $user_id, $ip_address, $device_fingerprint);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * 更新点赞缓存
     */
    private function updateLikesCache($delta) {
        // 更新缓存表
        $sql = "INSERT INTO wallpaper_likes_cache (wallpaper_id, likes_count) VALUES (?, ?) ON DUPLICATE KEY UPDATE likes_count = GREATEST(0, likes_count + ?), last_updated = CURRENT_TIMESTAMP";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('sii', $this->wallpaper_id, $delta, $delta);
        $stmt->execute();
        $stmt->close();
        
        // 更新主表（保持兼容性）
        $sql = "UPDATE wallpapers SET likes = GREATEST(0, likes + ?) WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('is', $delta, $this->wallpaper_id);
        $stmt->execute();
        $stmt->close();
        
        // 更新用户统计（仅登录用户）
        if ($this->user_identifier['type'] === 'user_id') {
            $this->updateUserLikesSummary($delta);
        }
    }
    
    /**
     * 更新用户点赞统计
     */
    private function updateUserLikesSummary($delta) {
        $user_id = $this->user_identifier['value'];
        
        if ($delta > 0) {
            // 点赞：更新统计
            $sql = "INSERT INTO user_likes_summary (user_id, total_likes, last_like_at) VALUES (?, 1, CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE total_likes = total_likes + 1, last_like_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $user_id);
        } else {
            // 取消点赞：减少统计
            $sql = "UPDATE user_likes_summary SET total_likes = GREATEST(0, total_likes - 1), updated_at = CURRENT_TIMESTAMP WHERE user_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $user_id);
        }
        
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * 获取当前点赞数
     */
    private function getCurrentLikesCount() {
        // 优先从缓存表获取
        $sql = "SELECT likes_count FROM wallpaper_likes_cache WHERE wallpaper_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $this->wallpaper_id);
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
        $stmt->bind_param('s', $this->wallpaper_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $count = (int)$row['likes'];
            $stmt->close();
            
            // 同步到缓存表
            $this->syncToCache($count);
            return $count;
        }
        $stmt->close();
        
        return 0;
    }
    
    /**
     * 同步数据到缓存表
     */
    private function syncToCache($count) {
        $sql = "INSERT INTO wallpaper_likes_cache (wallpaper_id, likes_count) VALUES (?, ?) ON DUPLICATE KEY UPDATE likes_count = ?, last_updated = CURRENT_TIMESTAMP";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('sii', $this->wallpaper_id, $count, $count);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * 主要的切换点赞方法
     */
    public function toggleLike() {
        try {
            $this->db->autocommit(false);
            
            $isLiked = $this->checkExistingLike();
            $action = '';
            $success = false;
            
            if ($isLiked) {
                $success = $this->performUnlike();
                $action = 'unliked';
            } else {
                $success = $this->performLike();
                $action = 'liked';
            }
            
            if ($success) {
                $this->db->commit();
                $currentCount = $this->getCurrentLikesCount();
                
                // 记录操作日志
                $identifier_info = $this->user_identifier['type'] . ':' . $this->user_identifier['value'];
                sendDebugLog(
                    "点赞操作成功: wallpaper_id={$this->wallpaper_id}, action={$action}, identifier={$identifier_info}, new_count={$currentCount}",
                    'like_debug_log.txt',
                    'append',
                    'like_v2_success'
                );
                
                return [
                    'code' => 0,
                    'msg' => $action === 'liked' ? '点赞成功' : '取消点赞成功',
                    'action' => $action,
                    'likes_count' => $currentCount,
                    'user_identifier_type' => $this->user_identifier['type']
                ];
            } else {
                $this->db->rollback();
                return [
                    'code' => 1,
                    'msg' => '操作失败，请重试'
                ];
            }
        } catch (Exception $e) {
            $this->db->rollback();
            sendDebugLog(
                "点赞操作异常: wallpaper_id={$this->wallpaper_id}, error={$e->getMessage()}",
                'like_debug_log.txt',
                'append',
                'like_v2_error'
            );
            
            return [
                'code' => 500,
                'msg' => '系统错误，请稍后重试'
            ];
        } finally {
            $this->db->autocommit(true);
            $this->db->close();
        }
    }
}

// 执行点赞切换操作
try {
    $controller = new LikeControllerV2($wallpaper_id);
    $result = $controller->toggleLike();
    
    ob_clean();
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    ob_end_flush();
} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'code' => 500,
        'msg' => '系统初始化失败: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    ob_end_flush();
}

exit;
?>
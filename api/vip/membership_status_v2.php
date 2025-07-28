<?php
/**
 * 会员状态查询API v2
 * 基于localStorage用户ID直接查询users表
 * 文件: api/vip/membership_status_v2.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 处理OPTIONS预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 只允许POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => '只允许POST请求',
        'code' => 405
    ]);
    exit();
}

// 数据库连接函数
function getDbConnection() {
    try {
        $pdo = new PDO(
            'mysql:host=localhost;dbname=wallpaper_db;charset=utf8mb4',
            'root',
            '',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        error_log('数据库连接失败: ' . $e->getMessage());
        return null;
    }
}

// 获取用户会员状态
function getUserMembershipStatus($user_id) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return null;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                id, username, email, is_admin,
                membership_type, membership_expires_at, 
                download_quota, quota_reset_date, created_at
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return null;
        }
        
        // 检查会员是否过期
        $now = new DateTime();
        $membership_type = $user['membership_type'];
        $expires_at = $user['membership_expires_at'];
        
        // 处理月度会员过期
        if ($membership_type === 'monthly' && $expires_at) {
            $expiry_date = new DateTime($expires_at);
            if ($now > $expiry_date) {
                // 会员已过期，更新为免费用户
                $update_stmt = $pdo->prepare("
                    UPDATE users 
                    SET membership_type = 'free', download_quota = 3 
                    WHERE id = ?
                ");
                $update_stmt->execute([$user_id]);
                
                $user['membership_type'] = 'free';
                $user['download_quota'] = 3;
                $membership_type = 'free';
            }
        }
        
        // 计算剩余天数
        $days_remaining = 0;
        if ($membership_type === 'monthly' && $expires_at) {
            $expiry_date = new DateTime($expires_at);
            if ($now <= $expiry_date) {
                $interval = $now->diff($expiry_date);
                $days_remaining = $interval->days;
            }
        }
        
        // 设置会员类型显示和配额信息
        $membership_display = [
            'free' => '免费用户',
            'monthly' => '月度会员',
            'permanent' => '永久会员'
        ];
        
        $badge_class = [
            'free' => 'badge-secondary',
            'monthly' => 'badge-primary', 
            'permanent' => 'badge-success'
        ];
        
        // 计算下载配额信息
        $daily_limit = ($membership_type === 'permanent') ? -1 : intval($user['download_quota']);
        $daily_used = 0; // 这里可以根据需要查询今日下载次数
        $daily_remaining = ($membership_type === 'permanent') ? -1 : max(0, $daily_limit - $daily_used);
        
        $quota_display = ($membership_type === 'permanent') ? '无限制' : $user['download_quota'];
        $usage_display = ($membership_type === 'permanent') ? '无限制' : "{$daily_used}/{$daily_limit}";
        
        $can_download = ($membership_type === 'permanent') || ($daily_remaining > 0);
        
        // 设置权限
        $permissions = [
            'can_download_premium' => ($membership_type === 'monthly' || $membership_type === 'permanent' || $user['is_admin']),
            'can_download_free' => true,
            'has_quota_limit' => ($membership_type !== 'permanent')
        ];
        
        return [
            'user_id' => intval($user['id']),
            'username' => $user['username'],
            'membership' => [
                'type' => $membership_type,
                'display' => $membership_display[$membership_type],
                'badge_class' => $badge_class[$membership_type],
                'expires_at' => $expires_at,
                'days_remaining' => $days_remaining
            ],
            'download' => [
                'daily_limit' => $daily_limit,
                'daily_used' => $daily_used,
                'daily_remaining' => $daily_remaining,
                'quota_display' => $quota_display,
                'usage_display' => $usage_display,
                'can_download' => $can_download
            ],
            'permissions' => $permissions,
            'is_admin' => boolval($user['is_admin'])
        ];
        
    } catch (PDOException $e) {
        error_log('查询用户会员状态失败: ' . $e->getMessage());
        return null;
    }
}

try {
    // 获取POST数据
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['user_id'])) {
        echo json_encode([
            'success' => false,
            'message' => '缺少用户ID参数',
            'code' => 400
        ]);
        exit();
    }
    
    $user_id = intval($input['user_id']);
    
    if ($user_id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => '无效的用户ID',
            'code' => 400
        ]);
        exit();
    }
    
    // 获取用户会员状态
    $membership_data = getUserMembershipStatus($user_id);
    
    if ($membership_data === null) {
        echo json_encode([
            'success' => false,
            'message' => '用户不存在或查询失败',
            'code' => 404
        ]);
        exit();
    }
    
    // 返回成功响应
    echo json_encode([
        'success' => true,
        'message' => '获取会员状态成功',
        'data' => $membership_data,
        'code' => 200,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log('membership_status_v2.php 错误: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '服务器内部错误',
        'code' => 500
    ]);
}
?>
<?php
/**
 * 下载权限检查API v2
 * 基于localStorage用户ID直接验证下载权限
 * 文件: api/vip/check_download_permission_v2.php
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

// 检查下载权限
function checkDownloadPermission($user_id, $is_restricted) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return [
            'can_download' => false,
            'reason' => 'database_error',
            'message' => '数据库连接失败'
        ];
    }
    
    try {
        // 获取用户信息
        $stmt = $pdo->prepare("
            SELECT 
                id, username, is_admin,
                membership_type, membership_expires_at, 
                download_quota, quota_reset_date
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return [
                'can_download' => false,
                'reason' => 'user_not_found',
                'message' => '用户不存在'
            ];
        }
        
        // 管理员拥有所有权限
        if ($user['is_admin']) {
            return [
                'can_download' => true,
                'reason' => 'admin_privilege',
                'message' => '管理员权限'
            ];
        }
        
        $membership_type = $user['membership_type'];
        $expires_at = $user['membership_expires_at'];
        
        // 检查会员是否过期
        $now = new DateTime();
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
                
                $membership_type = 'free';
                $user['download_quota'] = 3;
                
                return [
                    'can_download' => false,
                    'reason' => 'membership_expired',
                    'message' => '会员已过期',
                    'suggestion' => '您的会员已过期，请重新购买会员'
                ];
            }
        }
        
        // 检查受限内容权限
        if ($is_restricted) {
            // 受限内容需要会员权限
            if ($membership_type === 'free') {
                return [
                    'can_download' => false,
                    'reason' => 'restricted_content',
                    'message' => '此功能仅限会员使用',
                    'suggestion' => '升级会员即可享受高清下载功能'
                ];
            }
        }
        
        // 永久会员无限制
        if ($membership_type === 'permanent') {
            return [
                'can_download' => true,
                'reason' => 'permanent_member',
                'message' => '永久会员无限下载'
            ];
        }
        
        // 检查下载配额（免费用户和月度会员）
        $download_quota = intval($user['download_quota']);
        
        // 简化权限检查：检查剩余额度
        if ($download_quota <= 0) {
            return [
                'can_download' => false,
                'reason' => 'quota_exceeded',
                'message' => '下载次数已用完',
                'suggestion' => '下载次数已用完，升级为永久会员没有下载限制',
                'daily_used' => 0,
                'daily_limit' => $download_quota
            ];
        }
        
        // 权限检查通过
        return [
            'can_download' => true,
            'reason' => 'quota_available',
            'message' => '下载权限验证通过',
            'daily_used' => 0,
            'daily_limit' => $download_quota,
            'daily_remaining' => $download_quota
        ];
        
    } catch (PDOException $e) {
        error_log('检查下载权限失败: ' . $e->getMessage());
        return [
            'can_download' => false,
            'reason' => 'database_error',
            'message' => '权限检查失败'
        ];
    }
}

// 获取用户会员信息
function getUserMembershipInfo($user_id) {
    $pdo = getDbConnection();
    if (!$pdo) {
        return null;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                membership_type, membership_expires_at, 
                download_quota, is_admin
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return null;
        }
        
        // 简化处理：不再查询下载记录表
        $today_downloads = 0;
        
        $membership_type = $user['membership_type'];
        $download_quota = intval($user['download_quota']);
        
        return [
            'membership_type' => $membership_type,
            'membership_expires_at' => $user['membership_expires_at'],
            'is_admin' => boolval($user['is_admin']),
            'daily_downloads_used' => $today_downloads,
            'daily_downloads_limit' => ($membership_type === 'permanent') ? -1 : $download_quota,
            'remaining_downloads' => ($membership_type === 'permanent') ? -1 : max(0, $download_quota - $today_downloads)
        ];
        
    } catch (PDOException $e) {
        error_log('获取用户会员信息失败: ' . $e->getMessage());
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
    $is_restricted = isset($input['is_restricted']) ? boolval($input['is_restricted']) : false;
    
    if ($user_id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => '无效的用户ID',
            'code' => 400
        ]);
        exit();
    }
    
    // 检查下载权限
    $permission_result = checkDownloadPermission($user_id, $is_restricted);
    
    // 获取用户会员信息
    $membership_info = getUserMembershipInfo($user_id);
    
    // 构建响应数据
    $response_data = [
        'can_download' => $permission_result['can_download'],
        'message' => $permission_result['message'],
        'reason' => $permission_result['reason']
    ];
    
    // 添加建议信息
    if (isset($permission_result['suggestion'])) {
        $response_data['suggestion'] = $permission_result['suggestion'];
    }
    
    // 添加下载统计信息
    if (isset($permission_result['daily_used'])) {
        $response_data['daily_used'] = $permission_result['daily_used'];
        $response_data['daily_limit'] = $permission_result['daily_limit'];
    }
    
    if (isset($permission_result['daily_remaining'])) {
        $response_data['daily_remaining'] = $permission_result['daily_remaining'];
    }
    
    // 添加会员信息
    if ($membership_info) {
        $response_data['membership'] = $membership_info;
    }
    
    // 返回响应
    echo json_encode([
        'success' => true,
        'message' => '权限检查完成',
        'data' => $response_data,
        'code' => 200,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log('check_download_permission_v2.php 错误: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '服务器内部错误',
        'code' => 500
    ]);
}
?>
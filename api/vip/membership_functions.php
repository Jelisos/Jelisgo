<?php
/**
 * 会员系统核心功能函数库
 * 提供会员系统的所有核心功能函数
 * 
 * @author AI Assistant
 * @date 2024-01-27
 * @updated 2024-01-27
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/auth_helper.php';

/**
 * 获取PDO数据库连接
 * @return PDO 数据库连接对象
 */
function getMembershipDbConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PWD, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
        ]);
        return $pdo;
    } catch (PDOException $e) {
        error_log("数据库连接失败: " . $e->getMessage());
        throw new Exception("数据库连接失败");
    }
}

/**
 * 执行SQL查询
 * @param string $sql SQL语句
 * @param array $params 参数数组
 * @return mixed 查询结果
 */
function executeQuery($sql, $params = []) {
    $pdo = getMembershipDbConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    if (stripos($sql, 'SELECT') === 0 || stripos($sql, 'SHOW') === 0 || stripos($sql, 'DESCRIBE') === 0) {
        return $stmt->fetch();
    }
    
    return $stmt->rowCount();
}

/**
 * 执行SQL查询并返回所有结果
 * @param string $sql SQL语句
 * @param array $params 参数数组
 * @return array 查询结果数组
 */
function executeQueryAll($sql, $params = []) {
    $pdo = getMembershipDbConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * 开始事务
 */
function beginTransaction() {
    $pdo = getMembershipDbConnection();
    $pdo->beginTransaction();
}

/**
 * 提交事务
 */
function commitTransaction() {
    $pdo = getMembershipDbConnection();
    $pdo->commit();
}

/**
 * 回滚事务
 */
function rollbackTransaction() {
    $pdo = getMembershipDbConnection();
    $pdo->rollback();
}

/**
 * 检查并重置用户配额（30天周期）
 * @param int $user_id 用户ID
 * @return bool 是否执行了重置
 */
function checkAndResetQuota($user_id) {
    $sql = "SELECT membership_type, download_quota, quota_reset_date, membership_expires_at 
            FROM users WHERE id = ?";
    $user = executeQuery($sql, [$user_id]);
    
    if (!$user || $user['membership_type'] !== 'monthly') {
        return false;
    }
    
    $now = new DateTime();
    $reset_date = new DateTime($user['quota_reset_date']);
    $expires_at = new DateTime($user['membership_expires_at']);
    
    // 检查是否需要重置配额
    if ($now >= $reset_date && $now <= $expires_at) {
        // 重置配额并设置下一个重置时间
        $next_reset = clone $reset_date;
        $next_reset->add(new DateInterval('P30D'));
        
        // 确保下次重置时间不超过会员到期时间
        if ($next_reset > $expires_at) {
            $next_reset = $expires_at;
        }
        
        $update_sql = "UPDATE users SET 
                       download_quota = 10, 
                       quota_reset_date = ? 
                       WHERE id = ?";
        executeQuery($update_sql, [$next_reset->format('Y-m-d H:i:s'), $user_id]);
        
        return true;
    }
    
    return false;
}

/**
 * 处理过期会员（定时任务）
 */
function handleExpiredMemberships() {
    $sql = "UPDATE users 
            SET membership_type = 'free', 
                download_quota = 0, 
                membership_expires_at = NULL,
                quota_reset_date = NULL
            WHERE membership_type = 'monthly' 
            AND membership_expires_at < NOW()";
    
    return executeQuery($sql);
}

/**
 * 检查用户下载权限
 * @param int $user_id 用户ID
 * @param string $download_type 下载类型
 * @return array 权限检查结果
 */
function checkDownloadPermission($user_id, $download_type) {
    // 先检查并重置配额
    checkAndResetQuota($user_id);
    
    $sql = "SELECT membership_type, download_quota, membership_expires_at 
            FROM users WHERE id = ?";
    $user = executeQuery($sql, [$user_id]);
    
    if (!$user) {
        return ['allowed' => false, 'reason' => '用户不存在'];
    }
    
    // 不受限制的下载类型
    $unrestricted_types = ['single_device', 'original', 'cover', 'other'];
    if (in_array($download_type, $unrestricted_types)) {
        return ['allowed' => true, 'reason' => '不受限制的下载类型'];
    }
    
    // 受限制的下载类型：hd_combo, avatar
    $restricted_types = ['hd_combo', 'avatar'];
    if (!in_array($download_type, $restricted_types)) {
        return ['allowed' => true, 'reason' => '未知下载类型，默认允许'];
    }
    
    // 检查会员权限
    if ($user['membership_type'] === 'free') {
        return ['allowed' => false, 'reason' => '需要会员权限'];
    }
    
    if ($user['membership_type'] === 'permanent') {
        return ['allowed' => true, 'reason' => '永久会员无限制'];
    }
    
    if ($user['membership_type'] === 'monthly') {
        // 检查是否过期
        if ($user['membership_expires_at'] && strtotime($user['membership_expires_at']) < time()) {
            return ['allowed' => false, 'reason' => '会员已过期'];
        }
        
        // 检查配额
        if ($user['download_quota'] <= 0) {
            return ['allowed' => false, 'reason' => '下载配额已用完'];
        }
        
        return ['allowed' => true, 'reason' => '1元会员有配额'];
    }
    
    return ['allowed' => false, 'reason' => '未知会员类型'];
}

/**
 * 扣减用户下载配额
 * @param int $user_id 用户ID
 * @param string $download_type 下载类型
 * @param string $wallpaper_id 壁纸ID
 * @return bool 是否成功扣减
 */
function consumeDownloadQuota($user_id, $download_type, $wallpaper_id = null) {
    // 检查权限
    $permission = checkDownloadPermission($user_id, $download_type);
    if (!$permission['allowed']) {
        return false;
    }
    
    // 记录下载日志
    $log_sql = "INSERT INTO user_download_logs 
                (user_id, download_type, wallpaper_id, membership_type, quota_consumed, download_date, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, CURDATE(), ?, ?)";
    
    $user = executeQuery("SELECT membership_type FROM users WHERE id = ?", [$user_id]);
    $quota_consumed = in_array($download_type, ['hd_combo', 'avatar']) && $user['membership_type'] === 'monthly' ? 1 : 0;
    
    executeQuery($log_sql, [
        $user_id, 
        $download_type, 
        $wallpaper_id, 
        $user['membership_type'], 
        $quota_consumed,
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
    
    // 如果是1元会员且是受限制的下载，扣减配额
    if ($quota_consumed) {
        $update_sql = "UPDATE users SET download_quota = download_quota - 1 WHERE id = ? AND download_quota > 0";
        executeQuery($update_sql, [$user_id]);
    }
    
    return true;
}

/**
 * 生成唯一会员码
 * @return string 12位会员码
 */
function generateUniqueCode() {
    do {
        $code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 12));
        $exists = executeQuery("SELECT id FROM membership_codes WHERE code = ?", [$code]);
    } while ($exists);
    
    return $code;
}

/**
 * 生成会员码
 * @param string $membership_type 会员类型
 * @param int $count 生成数量
 * @param string $batch_id 批次ID
 * @return array 生成的会员码列表
 */
function generateMembershipCodes($membership_type, $count = 1, $batch_id = null) {
    if (!$batch_id) {
        $batch_id = date('Ymd') . '_' . uniqid();
    }
    
    $codes = [];
    for ($i = 0; $i < $count; $i++) {
        $code = generateUniqueCode();
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 year')); // 会员码1年有效期
        
        $sql = "INSERT INTO membership_codes 
                (code, membership_type, expires_at, batch_id) 
                VALUES (?, ?, ?, ?)";
        
        executeQuery($sql, [$code, $membership_type, $expires_at, $batch_id]);
        $codes[] = $code;
    }
    
    return $codes;
}

/**
 * 验证并使用会员码
 * @param string $code 会员码
 * @param int $user_id 用户ID
 * @return array 验证结果
 */
function redeemMembershipCode($code, $user_id) {
    // 验证会员码
    $sql = "SELECT * FROM membership_codes WHERE code = ? AND status = 'active'";
    $membership_code = executeQuery($sql, [$code]);
    
    if (!$membership_code) {
        return ['success' => false, 'message' => '会员码不存在或已使用'];
    }
    
    // 检查是否过期
    if ($membership_code['expires_at'] && strtotime($membership_code['expires_at']) < time()) {
        return ['success' => false, 'message' => '会员码已过期'];
    }
    
    // 获取用户当前信息
    $user = executeQuery("SELECT * FROM users WHERE id = ?", [$user_id]);
    if (!$user) {
        return ['success' => false, 'message' => '用户不存在'];
    }
    
    // 开始事务
    try {
        $pdo = getMembershipDbConnection();
        $pdo->beginTransaction();
        
        // 更新会员码状态
        $update_code_sql = "UPDATE membership_codes 
                           SET status = 'used', used_by_user_id = ?, used_at = NOW() 
                           WHERE id = ?";
        $stmt = $pdo->prepare($update_code_sql);
        $stmt->execute([$user_id, $membership_code['id']]);
        
        // 更新用户会员信息
        if ($membership_code['membership_type'] === 'monthly') {
            $expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));
            $quota_reset = date('Y-m-d H:i:s', strtotime('+30 days'));
            
            $update_user_sql = "UPDATE users 
                               SET membership_type = 'monthly', 
                                   membership_expires_at = ?, 
                                   download_quota = 10, 
                                   quota_reset_date = ? 
                               WHERE id = ?";
            $stmt = $pdo->prepare($update_user_sql);
            $stmt->execute([$expires_at, $quota_reset, $user_id]);
        } else if ($membership_code['membership_type'] === 'permanent') {
            $update_user_sql = "UPDATE users 
                               SET membership_type = 'permanent', 
                                   membership_expires_at = NULL, 
                                   download_quota = -1, 
                                   quota_reset_date = NULL 
                               WHERE id = ?";
            $stmt = $pdo->prepare($update_user_sql);
            $stmt->execute([$user_id]);
        }
        
        $pdo->commit();
        
        return [
            'success' => true, 
            'message' => '会员升级成功', 
            'membership_type' => $membership_code['membership_type']
        ];
        
    } catch (Exception $e) {
        $pdo->rollback();
        return ['success' => false, 'message' => '升级失败：' . $e->getMessage()];
    }
}

/**
 * 获取用户会员状态
 * @param int $user_id 用户ID
 * @return array 会员状态信息
 */
function getUserMembershipStatus($user_id) {
    $sql = "SELECT membership_type, membership_expires_at, download_quota, quota_reset_date 
            FROM users WHERE id = ?";
    $user = executeQuery($sql, [$user_id]);
    
    if (!$user) {
        return ['error' => '用户不存在'];
    }
    
    return [
        'membership_type' => $user['membership_type'],
        'expires_at' => $user['membership_expires_at'],
        'download_quota' => $user['download_quota'],
        'quota_reset_date' => $user['quota_reset_date']
    ];
}

/**
 * 获取用户完整会员信息
 * @param int $user_id 用户ID
 * @return array 用户会员信息
 */
function getUserMembershipInfo($user_id) {
    $sql = "SELECT 
                id,
                username,
                membership_type,
                membership_expires_at,
                download_quota,
                quota_reset_date,
                created_at
            FROM users 
            WHERE id = ?";
    
    $user = executeQuery($sql, [$user_id]);
    
    if (!$user) {
        return [
            'membership_type' => 'free',
            'membership_expires_at' => null,
            'daily_download_limit' => 3,
            'daily_downloads_used' => 0,
            'last_quota_reset' => null,
            'download_quota' => 0
        ];
    }
    
    // 处理过期会员
    if ($user['membership_type'] === 'monthly' && $user['membership_expires_at']) {
        $expires_at = new DateTime($user['membership_expires_at']);
        $now = new DateTime();
        
        if ($now >= $expires_at) {
            // 会员已过期，更新为免费用户
            $update_sql = "UPDATE users 
                          SET membership_type = 'free', 
                              membership_expires_at = NULL, 
                              download_quota = 3 
                          WHERE id = ?";
            executeQuery($update_sql, [$user_id]);
            
            $user['membership_type'] = 'free';
            $user['membership_expires_at'] = null;
            $user['download_quota'] = 3;
        }
    }
    
    // 计算每日下载限制
    $daily_limit = 3; // 免费用户默认
    if ($user['membership_type'] === 'monthly') {
        $daily_limit = 10;
    } elseif ($user['membership_type'] === 'permanent') {
        $daily_limit = -1; // 无限制
    }
    
    // 获取今日下载次数
    $today = date('Y-m-d');
    $download_sql = "SELECT COUNT(*) as count 
                    FROM download_records 
                    WHERE user_id = ? 
                    AND DATE(download_date) = ?";
    
    $pdo = getMembershipDbConnection();
    $stmt = $pdo->prepare($download_sql);
    $stmt->execute([$user_id, $today]);
    $download_result = $stmt->fetch();
    $daily_used = $download_result ? intval($download_result['count']) : 0;
    
    return [
        'membership_type' => $user['membership_type'],
        'membership_expires_at' => $user['membership_expires_at'],
        'daily_download_limit' => $daily_limit,
        'daily_downloads_used' => $daily_used,
        'last_quota_reset' => $user['quota_reset_date'],
        'download_quota' => $user['download_quota'] ?? 0
    ];
}

/**
 * 获取会员码统计信息
 * @return array 统计数据
 */
function getMembershipCodesStats() {
    // 初始化统计数据
    $stats = [
        'monthly_active' => 0,
        'monthly_used' => 0,
        'monthly_expired' => 0,
        'permanent_active' => 0,
        'permanent_used' => 0,
        'permanent_expired' => 0
    ];
    
    // 按类型和状态统计
    $sql = "SELECT membership_type, status, COUNT(*) as count 
            FROM membership_codes 
            GROUP BY membership_type, status";
    $results = executeQueryAll($sql);
    
    foreach ($results as $row) {
        $type = $row['membership_type'];
        $status = $row['status'];
        $count = (int)$row['count'];
        
        // 将状态映射为前端期望的格式
        if ($status === 'active') {
            $stats[$type . '_active'] = $count;
        } elseif ($status === 'used') {
            $stats[$type . '_used'] = $count;
        } elseif ($status === 'expired') {
            $stats[$type . '_expired'] = $count;
        }
    }
    
    return $stats;
}

/**
 * 获取最近生成的会员码列表
 * @param int $limit 限制数量
 * @param int $offset 偏移量
 * @param string $status 状态筛选
 * @param string $membership_type 会员类型筛选
 * @return array 会员码列表
 */
function getRecentMembershipCodes($limit = 50, $offset = 0, $status = 'all', $membership_type = 'all') {
    $where_conditions = [];
    $params = [];
    
    // 状态筛选
    if ($status !== 'all') {
        if ($status === 'unused') {
            $where_conditions[] = "status = 'active'";
        } else {
            $where_conditions[] = "status = ?";
            $params[] = $status;
        }
    }
    
    // 会员类型筛选
    if ($membership_type !== 'all') {
        $where_conditions[] = "membership_type = ?";
        $params[] = $membership_type;
    }
    
    $where_clause = empty($where_conditions) ? '' : 'WHERE ' . implode(' AND ', $where_conditions);
    
    $sql = "SELECT code, membership_type, status, created_at, expires_at, used_at, used_by_user_id, batch_id
            FROM membership_codes 
            {$where_clause}
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $pdo = getMembershipDbConnection();
    $stmt = $pdo->prepare($sql);
    
    // 绑定参数
    for ($i = 0; $i < count($params); $i++) {
        if ($i >= count($params) - 2) { // limit 和 offset 参数
            $stmt->bindValue($i + 1, $params[$i], PDO::PARAM_INT);
        } else {
            $stmt->bindValue($i + 1, $params[$i], PDO::PARAM_STR);
        }
    }
    
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * 获取会员码总数（用于分页）
 * @param string $status 状态筛选
 * @param string $membership_type 会员类型筛选
 * @return int 总数
 */
function getRecentMembershipCodesCount($status = 'all', $membership_type = 'all') {
    $where_conditions = [];
    $params = [];
    
    // 状态筛选
    if ($status !== 'all') {
        if ($status === 'unused') {
            $where_conditions[] = "status = 'active'";
        } else {
            $where_conditions[] = "status = ?";
            $params[] = $status;
        }
    }
    
    // 会员类型筛选
    if ($membership_type !== 'all') {
        $where_conditions[] = "membership_type = ?";
        $params[] = $membership_type;
    }
    
    $where_clause = empty($where_conditions) ? '' : 'WHERE ' . implode(' AND ', $where_conditions);
    
    $sql = "SELECT COUNT(*) as total FROM membership_codes {$where_clause}";
    
    $pdo = getMembershipDbConnection();
    $stmt = $pdo->prepare($sql);
    
    // 绑定参数
    for ($i = 0; $i < count($params); $i++) {
        $stmt->bindValue($i + 1, $params[$i], PDO::PARAM_STR);
    }
    
    $stmt->execute();
    $result = $stmt->fetch();
    
    return $result ? (int)$result['total'] : 0;
}

// 操作日志功能已移除，如需记录可使用PHP内置error_log函数

?>
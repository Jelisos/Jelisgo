<?php
/**
 * 用户管理API
 * 位置: api/admin_users.php
 */
require_once 'response_helper.php';
require_once '../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 初始化PDO连接
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PWD,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
        ]
    );
} catch (PDOException $e) {
    error('数据库连接失败: ' . $e->getMessage(), 500);
}

// 简化的管理员验证（临时用于测试）
$admin_id = 1; // 临时设置为管理员ID 1

/**
 * 记录管理员操作日志
 */
function logAdminAction($admin_id, $action, $details) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO admin_logs (admin_id, action, details, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$admin_id, $action, $details]);
    } catch (Exception $e) {
        error_log("记录管理员日志失败: " . $e->getMessage());
    }
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $action = $_GET['action'] ?? '';
        
        if (empty($action)) {
            // 默认获取用户列表
            getUserList();
        } else {
            switch ($action) {
                case 'list':
                    getUserList();
                    break;
                case 'detail':
                    getUserDetail();
                    break;
                case 'stats':
                    getUserStats();
                    break;
                case 'export':
                    exportUsers();
                    break;
                default:
                    error('无效的操作');
            }
        }
        break;
        
    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? $_POST['action'] ?? '';
        
        switch ($action) {
            case 'create':
                createUser($input);
                break;
            case 'update':
                updateUser($input);
                break;
            case 'delete':
                deleteUser($input);
                break;
            case 'ban':
                banUser($input);
                break;
            case 'unban':
                unbanUser($input);
                break;
            case 'set_role':
                setUserRole($input);
                break;
            default:
                error('无效的操作');
        }
        break;
        
    default:
        error('不支持的请求方法');
}

/**
 * 获取用户列表
 */
function getUserList() {
    global $pdo;
    
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 20);
    $status = $_GET['status'] ?? 'all';
    $role = $_GET['role'] ?? 'all';
    $search = $_GET['search'] ?? '';
    $offset = ($page - 1) * $limit;
    
    try {
        $whereClause = [];
        $params = [];
        
        // 状态筛选
        if ($status !== 'all') {
            if ($status === 'banned') {
                // 前端使用'banned'，但数据库存储为'suspended'
                $whereClause[] = 'COALESCE(ues.status, \'active\') = ?';
                $params[] = 'suspended';
            } else {
                $whereClause[] = 'COALESCE(ues.status, \'active\') = ?';
                $params[] = $status;
            }
        }
        
        // 角色筛选
        if ($role !== 'all') {
            if ($role === 'admin') {
                $whereClause[] = 'u.is_admin = 1';
            } else {
                $whereClause[] = 'u.is_admin = 0 AND u.membership_type = ?';
                $params[] = $role;
            }
        }
        
        // 搜索条件
        if ($search) {
            $whereClause[] = '(u.username LIKE ? OR u.email LIKE ?)';
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        $whereSQL = empty($whereClause) ? '' : 'WHERE ' . implode(' AND ', $whereClause);
        
        // 获取总数
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM users u
            LEFT JOIN user_status_ext ues ON u.id = ues.user_id
            {$whereSQL}
        ");
        $stmt->execute($params);
        $total = $stmt->fetch()['total'];
        
        // 获取列表
        $stmt = $pdo->prepare("
            SELECT u.*, 
                   COALESCE(ues.status, 'active') as user_status,
                   ues.status_reason,
                   ues.operator_id,
                   ues.updated_at as status_updated_at,
                   (SELECT COUNT(*) FROM wallpapers WHERE user_id = u.id) as wallpaper_count
            FROM users u
            LEFT JOIN user_status_ext ues ON u.id = ues.user_id
            {$whereSQL}
            ORDER BY u.created_at DESC
            LIMIT {$limit} OFFSET {$offset}
        ");
        $stmt->execute($params);
        $users = $stmt->fetchAll();
        
        // 计算分页信息
        $totalPages = ceil($total / $limit);
        $start = ($page - 1) * $limit + 1;
        $end = min($page * $limit, $total);
        
        success([
            'users' => $users,
            'pagination' => [
                'total' => (int)$total,
                'current_page' => $page,
                'total_pages' => $totalPages,
                'limit' => $limit,
                'start' => $start,
                'end' => $end
            ]
        ]);
        
    } catch (Exception $e) {
        error('获取用户列表失败: ' . $e->getMessage());
    }
}

/**
 * 获取用户详情
 */
function getUserDetail() {
    global $pdo;
    
    $user_id = $_GET['id'] ?? '';
    
    if (!$user_id) {
        error('用户ID不能为空');
    }
    
    try {
        // 获取用户基本信息
        $stmt = $pdo->prepare("
            SELECT u.*, 
                   COALESCE(ues.status, 'active') as user_status,
                   ues.status_reason,
                   ues.operator_id,
                   ues.updated_at as status_updated_at
            FROM users u
            LEFT JOIN user_status_ext ues ON u.id = ues.user_id
            WHERE u.id = ?
        ");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            error('用户不存在');
        }
        
        // 获取用户上传的壁纸统计
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_wallpapers,
                SUM(CASE WHEN COALESCE(wrs.status, 'pending') = 'approved' THEN 1 ELSE 0 END) as approved_wallpapers,
                SUM(CASE WHEN COALESCE(wrs.status, 'pending') = 'pending' THEN 1 ELSE 0 END) as pending_wallpapers,
                SUM(CASE WHEN COALESCE(wrs.status, 'pending') = 'rejected' THEN 1 ELSE 0 END) as rejected_wallpapers
            FROM wallpapers w
            LEFT JOIN wallpaper_review_status wrs ON w.id = wrs.wallpaper_id
            WHERE w.user_id = ?
        ");
        $stmt->execute([$user_id]);
        $wallpaper_stats = $stmt->fetch();
        
        // 获取最近上传的壁纸
        $stmt = $pdo->prepare("
            SELECT w.*, COALESCE(wrs.status, 'pending') as review_status
            FROM wallpapers w
            LEFT JOIN wallpaper_review_status wrs ON w.id = wrs.wallpaper_id
            WHERE w.user_id = ?
            ORDER BY w.created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$user_id]);
        $recent_wallpapers = $stmt->fetchAll();
        
        $user['wallpaper_stats'] = $wallpaper_stats;
        $user['recent_wallpapers'] = $recent_wallpapers;
        
        // 格式化数据
        $user['status'] = $user['user_status'];
        $user['upload_count'] = $wallpaper_stats['total_wallpapers'];
        $user['ban_reason'] = $user['status_reason'];
        $user['last_login'] = $user['updated_at'] ?? null;
        
        success(['user' => $user]);
        
    } catch (Exception $e) {
        error('获取用户详情失败: ' . $e->getMessage());
    }
}

/**
 * 获取用户统计信息
 */
function getUserStats() {
    global $pdo;
    
    try {
        // 用户状态统计
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as total_users,
                SUM(CASE WHEN COALESCE(ues.status, 'active') = 'active' THEN 1 ELSE 0 END) as active_users,
                SUM(CASE WHEN COALESCE(ues.status, 'active') = 'banned' THEN 1 ELSE 0 END) as banned_users,
                SUM(CASE WHEN u.is_admin = 1 THEN 1 ELSE 0 END) as admin_users
            FROM users u
            LEFT JOIN user_status_ext ues ON u.id = ues.user_id
        ");
        $user_stats = $stmt->fetch();
        
        // 今日新增用户
        $stmt = $pdo->query("
            SELECT COUNT(*) as today_new_users
            FROM users
            WHERE DATE(created_at) = CURDATE()
        ");
        $today_stats = $stmt->fetch();
        
        // 最近7天新增用户趋势
        $stmt = $pdo->query("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as count
            FROM users
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date
        ");
        $trend_data = $stmt->fetchAll();
        
        success([
            'user_stats' => $user_stats,
            'today_new_users' => $today_stats['today_new_users'],
            'trend_data' => $trend_data
        ]);
        
    } catch (Exception $e) {
        error('获取用户统计失败: ' . $e->getMessage());
    }
}

/**
 * 创建用户
 */
function createUser($input) {
    global $pdo, $admin_id;
    
    $username = $input['username'] ?? '';
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';
    $role = $input['role'] ?? 'free'; // 默认为免费用户
    $is_active = $input['is_active'] ?? true;
    
    if (!$username || !$email || !$password) {
        error('用户名、邮箱和密码不能为空');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        error('邮箱格式不正确');
    }
    
    try {
        $pdo->beginTransaction();
        
        // 检查用户名是否已存在
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            error('用户名已存在');
        }
        
        // 检查邮箱是否已存在
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            error('邮箱已存在');
        }
        
        // 根据角色设置字段值
        $is_admin = ($role === 'admin') ? 1 : 0;
        $membership_type = ($role === 'admin') ? 'free' : $role; // 管理员默认为free类型
        
        // 创建用户
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('
            INSERT INTO users (username, email, password, is_admin, membership_type, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ');
        $stmt->execute([$username, $email, $hashedPassword, $is_admin, $membership_type]);
        $user_id = $pdo->lastInsertId();
        
        // 设置用户状态
        if (!$is_active) {
            $stmt = $pdo->prepare('
                INSERT INTO user_status_ext (user_id, status) 
                VALUES (?, "banned")
            ');
            $stmt->execute([$user_id]);
        }
        
        // 记录操作日志
        logAdminAction($admin_id, 'create_user', "创建用户: {$username} (ID: {$user_id})");
        
        $pdo->commit();
        success(['user_id' => $user_id], '用户创建成功');
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error('创建用户失败: ' . $e->getMessage());
    }
}

/**
 * 更新用户
 */
function updateUser($input) {
    global $pdo, $admin_id;
    
    $user_id = $input['id'] ?? '';
    $username = $input['username'] ?? '';
    $email = $input['email'] ?? '';
    $role = $input['role'] ?? 'user';
    $is_active = $input['is_active'] ?? true;
    
    if (!$user_id || !$username || !$email) {
        error('用户ID、用户名和邮箱不能为空');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        error('邮箱格式不正确');
    }
    
    try {
        $pdo->beginTransaction();
        
        // 检查用户是否存在
        $stmt = $pdo->prepare('SELECT username FROM users WHERE id = ?');
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        if (!$user) {
            error('用户不存在');
        }
        
        // 检查用户名是否被其他用户使用
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
        $stmt->execute([$username, $user_id]);
        if ($stmt->fetch()) {
            error('用户名已被其他用户使用');
        }
        
        // 检查邮箱是否被其他用户使用
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            error('邮箱已被其他用户使用');
        }
        
        // 根据角色设置字段值
        $is_admin = ($role === 'admin') ? 1 : 0;
        $membership_type = ($role === 'admin') ? 'free' : $role;
        
        // 更新用户信息
        $stmt = $pdo->prepare('
            UPDATE users 
            SET username = ?, email = ?, is_admin = ?, membership_type = ? 
            WHERE id = ?
        ');
        $stmt->execute([$username, $email, $is_admin, $membership_type, $user_id]);
        
        // 更新用户状态
        $status = $is_active ? 'active' : 'banned';
        $stmt = $pdo->prepare('
            INSERT INTO user_status_ext (user_id, status) 
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE status = VALUES(status)
        ');
        $stmt->execute([$user_id, $status]);
        
        // 记录操作日志
        logAdminAction($admin_id, 'update_user', "更新用户: {$username} (ID: {$user_id})");
        
        $pdo->commit();
        success(null, '用户更新成功');
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error('更新用户失败: ' . $e->getMessage());
    }
}

/**
 * 删除用户
 */
function deleteUser($input) {
    global $pdo, $admin_id;
    
    $user_id = $input['user_id'] ?? '';
    
    if (!$user_id) {
        error('用户ID不能为空');
    }
    
    try {
        $pdo->beginTransaction();
        
        // 检查用户是否存在
        $stmt = $pdo->prepare('SELECT username FROM users WHERE id = ?');
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        if (!$user) {
            error('用户不存在');
        }
        
        // 检查用户是否有上传的壁纸
        $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM wallpapers WHERE user_id = ?');
        $stmt->execute([$user_id]);
        $wallpaper_count = $stmt->fetch()['count'];
        
        if ($wallpaper_count > 0) {
            error('该用户有上传的壁纸，无法删除');
        }
        
        // 删除用户状态扩展信息
        $stmt = $pdo->prepare('DELETE FROM user_status_ext WHERE user_id = ?');
        $stmt->execute([$user_id]);
        
        // 删除用户
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$user_id]);
        
        // 记录操作日志
        logAdminAction($admin_id, 'delete_user', "删除用户: {$user['username']} (ID: {$user_id})");
        
        $pdo->commit();
        success(null, '用户删除成功');
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error('删除用户失败: ' . $e->getMessage());
    }
}

/**
 * 导出用户数据
 */
function exportUsers() {
    global $pdo;
    
    $status = $_GET['status'] ?? '';
    $role = $_GET['role'] ?? '';
    $search = $_GET['search'] ?? '';
    
    try {
        $whereClause = [];
        $params = [];
        
        // 状态筛选
        if ($status && $status !== 'all') {
            if ($status === 'banned') {
                // 前端使用'banned'，但数据库存储为'suspended'
                $whereClause[] = 'COALESCE(ues.status, "active") = ?';
                $params[] = 'suspended';
            } else {
                $whereClause[] = 'COALESCE(ues.status, "active") = ?';
                $params[] = $status;
            }
        }
        
        // 角色筛选
        if ($role && $role !== 'all') {
            if ($role === 'admin') {
                $whereClause[] = 'u.is_admin = 1';
            } else {
                $whereClause[] = 'u.is_admin = 0 AND u.membership_type = ?';
                $params[] = $role;
            }
        }
        
        // 搜索条件
        if ($search) {
            $whereClause[] = '(u.username LIKE ? OR u.email LIKE ?)';
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        $whereSQL = empty($whereClause) ? '' : 'WHERE ' . implode(' AND ', $whereClause);
        
        $stmt = $pdo->prepare("
            SELECT u.id, u.username, u.email, 
                   CASE WHEN u.is_admin = 1 THEN 'admin' ELSE u.membership_type END as role,
                   u.created_at, u.updated_at,
                   COALESCE(ues.status, 'active') as status,
                   (SELECT COUNT(*) FROM wallpapers WHERE user_id = u.id) as wallpaper_count
            FROM users u
            LEFT JOIN user_status_ext ues ON u.id = ues.user_id
            {$whereSQL}
            ORDER BY u.created_at DESC
        ");
        $stmt->execute($params);
        $users = $stmt->fetchAll();
        
        // 获取编码格式参数，默认为UTF-8 BOM（适合大多数情况）
        $encoding = $_GET['encoding'] ?? 'utf8-bom';
        
        // 根据编码设置相应的头信息
        switch ($encoding) {
            case 'gb2312':
                header('Content-Type: text/csv; charset=gb2312');
                $filename_suffix = '_gb2312';
                break;
            case 'utf8':
                header('Content-Type: text/csv; charset=utf-8');
                $filename_suffix = '_utf8';
                break;
            default: // utf8-bom
                header('Content-Type: text/csv; charset=utf-8');
                $filename_suffix = '';
                break;
        }
        
        header('Content-Disposition: attachment; filename="users_export_' . date('Y-m-d_H-i-s') . $filename_suffix . '.csv"');
        
        // 输出CSV内容
        $output = fopen('php://output', 'w');
        
        // 根据编码格式输出BOM或进行编码转换
        if ($encoding === 'utf8-bom') {
            // 输出UTF-8 BOM以支持Excel正确显示中文
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        }
        
        // 准备表头
        $headers = ['用户ID', '用户名', '邮箱', '角色', '状态', '注册时间', '最后更新', '上传数量'];
        
        // 根据编码转换表头
        if ($encoding === 'gb2312') {
            $headers = array_map(function($header) {
                return mb_convert_encoding($header, 'GB2312', 'UTF-8');
            }, $headers);
        }
        
        fputcsv($output, $headers);
        
        // 输出数据
        foreach ($users as $user) {
            $row = [
                $user['id'],
                $user['username'],
                $user['email'],
                $user['role'],
                $user['status'],
                $user['created_at'],
                $user['updated_at'] ?: '从未更新',
                $user['wallpaper_count']
            ];
            
            // 根据编码转换数据
            if ($encoding === 'gb2312') {
                $row = array_map(function($field) {
                    return is_string($field) ? mb_convert_encoding($field, 'GB2312', 'UTF-8') : $field;
                }, $row);
            }
            
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
        
    } catch (Exception $e) {
        error('导出用户数据失败: ' . $e->getMessage());
    }
}

/**
 * 封禁用户
 */
function banUser($input = null) {
    global $pdo, $admin_id;
    
    $user_id = $input['user_id'] ?? $_POST['user_id'] ?? '';
    $reason = $input['reason'] ?? $_POST['reason'] ?? '违规行为';
    
    if (!$user_id) {
        error('用户ID不能为空');
    }
    
    if (!$reason) {
        error('封禁原因不能为空');
    }
    
    try {
        $pdo->beginTransaction();
        
        // 检查用户是否存在
        $stmt = $pdo->prepare('SELECT id, username FROM users WHERE id = ?');
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            error('用户不存在');
        }
        
        // 更新或插入用户状态
        $stmt = $pdo->prepare("
            INSERT INTO user_status_ext 
            (user_id, status, status_reason, operator_id)
            VALUES (?, 'suspended', ?, ?)
            ON DUPLICATE KEY UPDATE
            status = 'suspended',
            status_reason = VALUES(status_reason),
            operator_id = VALUES(operator_id)
        ");
        $stmt->execute([$user_id, $reason, $admin_id]);
        
        // 记录操作日志
        logAdminAction($admin_id, 'ban_user', "用户: {$user['username']} (ID: {$user_id}), 原因: {$reason}");
        
        $pdo->commit();
        success(null, '用户封禁成功');
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error('封禁用户失败: ' . $e->getMessage());
    }
}

/**
 * 解封用户
 */
function unbanUser($input = null) {
    global $pdo, $admin_id;
    
    $user_id = $input['user_id'] ?? $_POST['user_id'] ?? '';
    
    if (!$user_id) {
        error('用户ID不能为空');
    }
    
    try {
        $pdo->beginTransaction();
        
        // 检查用户是否存在
        $stmt = $pdo->prepare('SELECT id, username FROM users WHERE id = ?');
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            error('用户不存在');
        }
        
        // 更新用户状态
        $stmt = $pdo->prepare("
            INSERT INTO user_status_ext 
            (user_id, status, status_reason, operator_id)
            VALUES (?, 'active', NULL, ?)
            ON DUPLICATE KEY UPDATE
            status = 'active',
            status_reason = NULL,
            operator_id = VALUES(operator_id)
        ");
        $stmt->execute([$user_id, $admin_id]);
        
        // 记录操作日志
        logAdminAction($admin_id, 'unban_user', "用户: {$user['username']} (ID: {$user_id})");
        
        $pdo->commit();
        success(null, '用户解封成功');
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error('解封用户失败: ' . $e->getMessage());
    }
}

/**
 * 设置用户角色
 */
function setUserRole($input = null) {
    global $pdo, $admin_id;
    
    $user_id = $input['user_id'] ?? $_POST['user_id'] ?? '';
    $role = $input['role'] ?? $_POST['role'] ?? '';
    
    if (!$user_id) {
        error('用户ID不能为空');
    }
    
    if (!in_array($role, ['free', 'monthly', 'permanent', 'admin'])) {
        error('无效的角色');
    }
    
    try {
        $pdo->beginTransaction();
        
        // 检查用户是否存在
        $stmt = $pdo->prepare('SELECT id, username, is_admin, membership_type FROM users WHERE id = ?');
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            error('用户不存在');
        }
        
        // 获取当前角色
        $current_role = $user['is_admin'] == 1 ? 'admin' : $user['membership_type'];
        
        if ($current_role === $role) {
            error('用户角色未发生变化');
        }
        
        // 根据角色设置字段值
        $is_admin = ($role === 'admin') ? 1 : 0;
        $membership_type = ($role === 'admin') ? 'free' : $role;
        
        // 更新用户角色
        $stmt = $pdo->prepare('UPDATE users SET is_admin = ?, membership_type = ? WHERE id = ?');
        $stmt->execute([$is_admin, $membership_type, $user_id]);
        
        // 记录操作日志
        logAdminAction($admin_id, 'set_user_role', "用户: {$user['username']} (ID: {$user_id}), 角色: {$current_role} -> {$role}");
        
        $pdo->commit();
        success(null, '用户角色设置成功');
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error('设置用户角色失败: ' . $e->getMessage());
    }
}
?>
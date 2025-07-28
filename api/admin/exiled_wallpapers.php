<?php
/**
 * 文件: api/admin/exiled_wallpapers.php
 * 描述: 获取被流放壁纸列表API - 支持按用户邮箱筛选
 * 功能: 专门针对被流放的壁纸进行管理
 * 权限: 管理员可访问
 * 创建时间: 2025-01-27
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../config/database.php';
require_once '../utils.php';
require_once '../admin_auth.php';

/**
 * 检查管理员权限 - 已废弃SESSION验证，统一使用Authorization头验证
 * 注意：此函数已改为使用admin_auth.php中的checkAdminAuth()函数
 * 验证方式：localStorage + Authorization头 + 数据库验证
 * @return int 返回管理员用户ID
 */
function checkAdminPermission() {
    // 使用统一的管理员验证函数
    return checkAdminAuth();
}

// sendResponse函数已在utils.php中定义，此处移除重复定义

// 验证请求方法
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(405, '不支持的请求方法');
}

try {
    // 检查管理员权限 - 使用统一的Authorization头验证
    // 注意：已完全废弃SESSION验证，改为LOCAL和数据库管理员验证
    $adminUserId = checkAdminPermission();
    if (!$adminUserId) {
        sendResponse(403, '权限不足');
    }
    
    // 获取数据库连接
    $conn = getDBConnection();
    if (!$conn) {
        sendResponse(500, '数据库连接失败');
    }
    
    // 获取请求参数
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = max(1, min(100, intval($_GET['limit'] ?? 20)));
    $email = trim($_GET['email'] ?? '');
    $search = trim($_GET['search'] ?? '');
    
    $offset = ($page - 1) * $limit;
    
    // 构建查询条件
    $whereConditions = ['wes.status = 1']; // 只查询被流放的壁纸
    $params = [];
    $types = '';
    
    // 邮箱筛选
    if (!empty($email)) {
        $whereConditions[] = 'u.email LIKE ?';
        $params[] = '%' . $email . '%';
        $types .= 's';
    }
    
    // 搜索功能（标题或用户名）
    if (!empty($search)) {
        $whereConditions[] = '(w.title LIKE ? OR u.username LIKE ?)';
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
        $types .= 'ss';
    }
    
    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    
    // 查询总数
    $countSql = "
        SELECT COUNT(*) as total
        FROM wallpaper_exile_status wes
        INNER JOIN wallpapers w ON wes.wallpaper_id = w.id
        LEFT JOIN users u ON w.user_id = u.id
        {$whereClause}
    ";
    
    $countStmt = $conn->prepare($countSql);
    if (!empty($params)) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $total = $countResult->fetch_assoc()['total'];
    
    // 查询列表数据
    $listSql = "
        SELECT 
            wes.id,
            wes.wallpaper_id,
            wes.created_at as exile_time,
            wes.last_operation_time,
            wes.comment,
            w.title,
            w.file_path,
            w.width,
            w.height,
            w.file_size,
            w.category,
            w.created_at as wallpaper_created_at,
            COALESCE(u.id, 0) as user_id,
            COALESCE(u.username, '未知用户') as username,
            COALESCE(u.email, 'unknown@example.com') as email,
            COALESCE(u.membership_type, 'free') as membership_type
        FROM wallpaper_exile_status wes
        INNER JOIN wallpapers w ON wes.wallpaper_id = w.id
        LEFT JOIN users u ON w.user_id = u.id
        {$whereClause}
        ORDER BY wes.last_operation_time DESC
        LIMIT ? OFFSET ?
    ";
    
    $listParams = $params;
    $listParams[] = $limit;
    $listParams[] = $offset;
    $listTypes = $types . 'ii';
    
    $listStmt = $conn->prepare($listSql);
    if (!empty($listParams)) {
        $listStmt->bind_param($listTypes, ...$listParams);
    }
    $listStmt->execute();
    $listResult = $listStmt->get_result();
    
    $wallpapers = [];
    while ($row = $listResult->fetch_assoc()) {
        $wallpapers[] = [
            'id' => (int)$row['id'],
            'wallpaper_id' => (int)$row['wallpaper_id'],
            'title' => $row['title'] ?: '未命名壁纸',
            'file_path' => $row['file_path'],
            'width' => (int)$row['width'],
            'height' => (int)$row['height'],
            'file_size' => $row['file_size'],
            'category' => $row['category'],
            'user_id' => (int)$row['user_id'],
            'username' => $row['username'],
            'email' => $row['email'],
            'membership_type' => $row['membership_type'],
            'exile_time' => $row['exile_time'],
            'last_operation_time' => $row['last_operation_time'],
            'wallpaper_created_at' => $row['wallpaper_created_at'],
            'comment' => $row['comment']
        ];
    }
    
    $totalPages = ceil($total / $limit);
    
    // 返回结果
    sendResponse(200, '获取成功', [
        'wallpapers' => $wallpapers,
        'pagination' => [
            'total' => (int)$total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => $totalPages,
            'has_prev' => $page > 1,
            'has_next' => $page < $totalPages
        ],
        'filters' => [
            'email' => $email,
            'search' => $search
        ]
    ]);
    
} catch (Exception $e) {
    error_log('获取流放壁纸列表错误: ' . $e->getMessage());
    sendResponse(500, '服务器错误: ' . $e->getMessage());
} finally {
    if (isset($conn)) {
        closeDBConnection($conn);
    }
}
?>
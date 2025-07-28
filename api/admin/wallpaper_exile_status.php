<?php
/**
 * 获取壁纸流放状态列表API
 * 功能：获取壁纸的流放状态信息，支持分页和筛选
 * 权限：管理员专用
 * 路径：/api/admin/wallpaper_exile_status.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error_code' => 'METHOD_NOT_ALLOWED',
        'message' => '仅支持GET请求'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // 引入必要文件
    require_once '../../config/database.php';
    require_once '../auth_unified.php';
    
    // 验证管理员权限
    $auth_result = validateAdminAuth();
    if (!$auth_result['success']) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error_code' => 'UNAUTHORIZED',
            'message' => $auth_result['message']
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 获取数据库连接
    $pdo = getPDOConnection();
    if (!$pdo) {
        throw new Exception('数据库连接失败');
    }

    // 获取请求参数
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 20;
    $status = isset($_GET['status']) ? intval($_GET['status']) : null;
    $operator_type = isset($_GET['operator_type']) ? $_GET['operator_type'] : null;
    $operation_source = isset($_GET['operation_source']) ? $_GET['operation_source'] : null;
    $wallpaper_id = isset($_GET['wallpaper_id']) ? intval($_GET['wallpaper_id']) : null;
    $search = isset($_GET['search']) ? trim($_GET['search']) : null;
    $email = isset($_GET['email']) ? trim($_GET['email']) : null;
    
    $offset = ($page - 1) * $limit;

    // 构建查询条件
    $where_conditions = [];
    $params = [];

    if ($status !== null) {
        $where_conditions[] = 'wes.status = :status';
        $params[':status'] = $status;
    }

    if ($operator_type) {
        $where_conditions[] = 'wes.operator_type = :operator_type';
        $params[':operator_type'] = $operator_type;
    }

    if ($operation_source) {
        $where_conditions[] = 'wes.operation_source = :operation_source';
        $params[':operation_source'] = $operation_source;
    }

    if ($wallpaper_id) {
        $where_conditions[] = 'wes.wallpaper_id = :wallpaper_id';
        $params[':wallpaper_id'] = $wallpaper_id;
    }
    
    if ($search) {
        $where_conditions[] = '(w.title LIKE :search OR wu.username LIKE :search OR wu.email LIKE :search)';
        $params[':search'] = '%' . $search . '%';
    }
    
    if ($email) {
        $where_conditions[] = 'wu.email LIKE :email';
        $params[':email'] = '%' . $email . '%';
    }

    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

    // 查询总数
    $count_sql = "
        SELECT COUNT(*) as total 
        FROM wallpaper_exile_status wes
        LEFT JOIN wallpapers w ON wes.wallpaper_id = w.id
        LEFT JOIN users u ON wes.last_operator_user_id = u.id
        LEFT JOIN users wu ON w.user_id = wu.id
        $where_clause
    ";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total = $count_stmt->fetch()['total'];

    // 查询数据
    $sql = "
        SELECT 
            wes.id,
            wes.wallpaper_id,
            wes.status,
            wes.last_operator_user_id,
            wes.operator_type,
            wes.operation_source,
            wes.last_operation_time,
            wes.created_at,
            wes.comment,
            w.title as wallpaper_title,
            w.file_path as wallpaper_file_path,
            w.created_at as wallpaper_upload_time,
            w.user_id as wallpaper_user_id,
            u.username as operator_username,
            u.email as operator_email,
            wu.username as wallpaper_uploader_username,
            wu.email as wallpaper_uploader_email,
            wu.membership_type as wallpaper_uploader_membership
        FROM wallpaper_exile_status wes
        LEFT JOIN wallpapers w ON wes.wallpaper_id = w.id
        LEFT JOIN users u ON wes.last_operator_user_id = u.id
        LEFT JOIN users wu ON w.user_id = wu.id
        $where_clause
        ORDER BY wes.last_operation_time DESC
        LIMIT :limit OFFSET :offset
    ";

    // 将LIMIT和OFFSET直接拼接到SQL中（因为PDO在某些情况下不支持在LIMIT中使用命名参数）
    $sql = str_replace('LIMIT :limit OFFSET :offset', "LIMIT $limit OFFSET $offset", $sql);
    
    $stmt = $pdo->prepare($sql);
    
    // 绑定其他参数
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $records = $stmt->fetchAll();

    // 格式化数据
    $formatted_records = array_map(function($record) {
        return [
            'id' => intval($record['id']),
            'wallpaper_id' => intval($record['wallpaper_id']),
            'status' => intval($record['status']),
            'status_text' => $record['status'] == 1 ? '流放' : '正常',
            'last_operator_user_id' => $record['last_operator_user_id'] ? intval($record['last_operator_user_id']) : null,
            'operator_type' => $record['operator_type'],
            'operator_type_text' => $record['operator_type'] == 'admin' ? '管理员' : '普通用户',
            'operation_source' => $record['operation_source'],
            'operation_source_text' => $record['operation_source'] == 'admin_panel' ? '管理后台' : '前端首页',
            'last_operation_time' => $record['last_operation_time'],
            'created_at' => $record['created_at'],
            'comment' => $record['comment'],
            'wallpaper_info' => [
                'title' => $record['wallpaper_title'],
                'file_path' => $record['wallpaper_file_path'],
                'upload_time' => $record['wallpaper_upload_time'],
                'user_id' => $record['wallpaper_user_id'] ? intval($record['wallpaper_user_id']) : null
            ],
            'operator_info' => [
                'username' => $record['operator_username'],
                'email' => $record['operator_email']
            ],
            'uploader_info' => [
                'username' => $record['wallpaper_uploader_username'],
                'email' => $record['wallpaper_uploader_email'],
                'membership_type' => $record['wallpaper_uploader_membership']
            ]
        ];
    }, $records);

    // 返回结果
    echo json_encode([
        'success' => true,
        'data' => [
            'records' => $formatted_records,
            'pagination' => [
                'current_page' => $page,
                'limit' => $limit,
                'total' => intval($total),
                'total_pages' => ceil($total / $limit),
                'start' => ($page - 1) * $limit + 1,
                'end' => min($page * $limit, intval($total))
            ]
        ],
        'message' => '获取成功'
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error_code' => 'DATABASE_ERROR',
        'message' => '数据库错误：' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error_code' => 'SYSTEM_ERROR',
        'message' => '系统错误：' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
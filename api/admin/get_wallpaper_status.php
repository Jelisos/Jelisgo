<?php
/**
 * 获取壁纸当前状态API
 * 功能：获取指定壁纸的当前流放状态
 * 权限：管理员专用
 * 路径：/api/admin/get_wallpaper_status.php
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
    $wallpaper_id = isset($_GET['wallpaper_id']) ? intval($_GET['wallpaper_id']) : null;
    
    if (!$wallpaper_id || $wallpaper_id <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error_code' => 'INVALID_WALLPAPER_ID',
            'message' => '无效的壁纸ID'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 验证壁纸是否存在
    $check_sql = "SELECT id, title, user_id, upload_time FROM wallpapers WHERE id = :wallpaper_id";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([':wallpaper_id' => $wallpaper_id]);
    $wallpaper = $check_stmt->fetch();

    if (!$wallpaper) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error_code' => 'WALLPAPER_NOT_FOUND',
            'message' => '壁纸不存在'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 获取当前流放状态
    $status_sql = "
        SELECT 
            wes.status,
            wes.last_operator_user_id,
            wes.operator_type,
            wes.operation_source,
            wes.last_operation_time,
            wes.created_at,
            wes.comment,
            u.username as operator_username
        FROM wallpaper_exile_status wes
        LEFT JOIN users u ON wes.last_operator_user_id = u.id
        WHERE wes.wallpaper_id = :wallpaper_id 
        ORDER BY wes.last_operation_time DESC 
        LIMIT 1
    ";
    $status_stmt = $pdo->prepare($status_sql);
    $status_stmt->execute([':wallpaper_id' => $wallpaper_id]);
    $status_record = $status_stmt->fetch();

    // 获取上传者信息
    $uploader_sql = "SELECT username, email, membership_type FROM users WHERE id = :user_id";
    $uploader_stmt = $pdo->prepare($uploader_sql);
    $uploader_stmt->execute([':user_id' => $wallpaper['user_id']]);
    $uploader = $uploader_stmt->fetch();

    // 构建响应数据
    $current_status = $status_record ? intval($status_record['status']) : 0;
    
    $response_data = [
        'wallpaper_id' => intval($wallpaper['id']),
        'wallpaper_info' => [
            'title' => $wallpaper['title'],
            'upload_time' => $wallpaper['upload_time'],
            'user_id' => $wallpaper['user_id'] ? intval($wallpaper['user_id']) : null
        ],
        'uploader_info' => $uploader ? [
            'username' => $uploader['username'],
            'email' => $uploader['email'],
            'membership_type' => $uploader['membership_type']
        ] : null,
        'current_status' => [
            'status' => $current_status,
            'status_text' => $current_status == 1 ? '流放' : '正常',
            'is_exiled' => $current_status == 1
        ]
    ];

    // 如果有流放记录，添加详细信息
    if ($status_record) {
        $response_data['exile_info'] = [
            'last_operator_user_id' => $status_record['last_operator_user_id'] ? intval($status_record['last_operator_user_id']) : null,
            'operator_username' => $status_record['operator_username'],
            'operator_type' => $status_record['operator_type'],
            'operator_type_text' => $status_record['operator_type'] == 'admin' ? '管理员' : '普通用户',
            'operation_source' => $status_record['operation_source'],
            'operation_source_text' => $status_record['operation_source'] == 'admin_panel' ? '管理后台' : '前端首页',
            'last_operation_time' => $status_record['last_operation_time'],
            'created_at' => $status_record['created_at'],
            'comment' => $status_record['comment']
        ];
    }

    // 获取历史记录（最近5条）
    $history_sql = "
        SELECT 
            wes.status,
            wes.last_operator_user_id,
            wes.operator_type,
            wes.operation_source,
            wes.last_operation_time,
            wes.comment,
            u.username as operator_username
        FROM wallpaper_exile_status wes
        LEFT JOIN users u ON wes.last_operator_user_id = u.id
        WHERE wes.wallpaper_id = :wallpaper_id 
        ORDER BY wes.last_operation_time DESC 
        LIMIT 5
    ";
    $history_stmt = $pdo->prepare($history_sql);
    $history_stmt->execute([':wallpaper_id' => $wallpaper_id]);
    $history_records = $history_stmt->fetchAll();

    $response_data['history'] = array_map(function($record) {
        return [
            'status' => intval($record['status']),
            'status_text' => $record['status'] == 1 ? '流放' : '正常',
            'operator_user_id' => $record['last_operator_user_id'] ? intval($record['last_operator_user_id']) : null,
            'operator_username' => $record['operator_username'],
            'operator_type' => $record['operator_type'],
            'operator_type_text' => $record['operator_type'] == 'admin' ? '管理员' : '普通用户',
            'operation_source' => $record['operation_source'],
            'operation_source_text' => $record['operation_source'] == 'admin_panel' ? '管理后台' : '前端首页',
            'operation_time' => $record['last_operation_time'],
            'comment' => $record['comment']
        ];
    }, $history_records);

    echo json_encode([
        'success' => true,
        'data' => $response_data,
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
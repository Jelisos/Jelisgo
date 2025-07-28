<?php
/**
 * 单个壁纸状态更新API
 * 功能：更新单张壁纸的流放状态
 * 权限：管理员专用
 * 路径：/api/admin/update_wallpaper_status.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error_code' => 'METHOD_NOT_ALLOWED',
        'message' => '仅支持POST请求'
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
    
    $admin_user_id = $auth_result['user_id'];
    
    // 获取数据库连接
    $pdo = getPDOConnection();
    if (!$pdo) {
        throw new Exception('数据库连接失败');
    }

    // 获取POST数据
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('无效的JSON数据');
    }

    // 验证必需参数
    if (!isset($input['wallpaper_id']) || !isset($input['status'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error_code' => 'MISSING_PARAMETERS',
            'message' => '缺少必需参数：wallpaper_id 和 status'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $wallpaper_id = intval($input['wallpaper_id']);
    $status = intval($input['status']);
    $comment = isset($input['comment']) ? trim($input['comment']) : null;

    // 验证参数
    if ($wallpaper_id <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error_code' => 'INVALID_WALLPAPER_ID',
            'message' => '无效的壁纸ID'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!in_array($status, [0, 1])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error_code' => 'INVALID_STATUS',
            'message' => '无效的状态值，只能是0（正常）或1（流放）'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 验证壁纸是否存在
    $check_sql = "SELECT id, title, user_id FROM wallpapers WHERE id = :wallpaper_id";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute(['wallpaper_id' => $wallpaper_id]);
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
    $current_status_sql = "SELECT status FROM wallpaper_exile_status WHERE wallpaper_id = :wallpaper_id ORDER BY last_operation_time DESC LIMIT 1";
    $current_status_stmt = $pdo->prepare($current_status_sql);
    $current_status_stmt->execute(['wallpaper_id' => $wallpaper_id]);
    $current_status_record = $current_status_stmt->fetch();
    $current_status = $current_status_record ? intval($current_status_record['status']) : 0;

    // 检查状态是否需要更改
    if ($current_status === $status) {
        echo json_encode([
            'success' => true,
            'data' => [
                'wallpaper_id' => $wallpaper_id,
                'wallpaper_title' => $wallpaper['title'],
                'old_status' => $current_status,
                'new_status' => $status,
                'status_text' => $status == 1 ? '流放' : '正常',
                'changed' => false
            ],
            'message' => '壁纸状态无需更改'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 开始事务
    $pdo->beginTransaction();

    try {
        if ($status == 1) {
            // 流放状态：插入或更新记录
            $upsert_sql = "
                INSERT INTO wallpaper_exile_status 
                (wallpaper_id, status, last_operator_user_id, operator_type, operation_source, comment, last_operation_time)
                VALUES 
                (:wallpaper_id, :status, :last_operator_user_id, 'admin', 'admin_panel', :comment, NOW())
                ON DUPLICATE KEY UPDATE
                status = VALUES(status),
                last_operator_user_id = VALUES(last_operator_user_id),
                operator_type = VALUES(operator_type),
                operation_source = VALUES(operation_source),
                comment = VALUES(comment),
                last_operation_time = NOW()
            ";

            $upsert_stmt = $pdo->prepare($upsert_sql);
            $upsert_stmt->execute([
                'wallpaper_id' => $wallpaper_id,
                'status' => $status,
                'last_operator_user_id' => $admin_user_id,
                'comment' => $comment
            ]);
        } else {
            // 正常状态：更新记录状态为0，保留历史记录
            $update_sql = "
                UPDATE wallpaper_exile_status 
                SET status = 0, 
                    last_operator_user_id = :last_operator_user_id,
                    operator_type = 'admin',
                    operation_source = 'admin_panel',
                    comment = :comment,
                    last_operation_time = NOW()
                WHERE wallpaper_id = :wallpaper_id
            ";
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute([
                'wallpaper_id' => $wallpaper_id,
                'last_operator_user_id' => $admin_user_id,
                'comment' => $comment
            ]);
            
            // 如果没有记录被更新，说明该壁纸本来就不在流放状态
            if ($update_stmt->rowCount() == 0) {
                // 插入一条召回记录
                $insert_sql = "
                    INSERT INTO wallpaper_exile_status 
                    (wallpaper_id, status, last_operator_user_id, operator_type, operation_source, comment, last_operation_time)
                    VALUES 
                    (:wallpaper_id, 0, :last_operator_user_id, 'admin', 'admin_panel', :comment, NOW())
                ";
                $insert_stmt = $pdo->prepare($insert_sql);
                $insert_stmt->execute([
                    'wallpaper_id' => $wallpaper_id,
                    'last_operator_user_id' => $admin_user_id,
                    'comment' => $comment
                ]);
            }
        }

        // 提交事务
        $pdo->commit();

        // 获取操作者信息
        $operator_sql = "SELECT username FROM users WHERE id = :user_id";
        $operator_stmt = $pdo->prepare($operator_sql);
        $operator_stmt->execute(['user_id' => $admin_user_id]);
        $operator = $operator_stmt->fetch();

        $response_data = [
            'wallpaper_id' => $wallpaper_id,
            'wallpaper_title' => $wallpaper['title'],
            'old_status' => $current_status,
            'new_status' => $status,
            'status_text' => $status == 1 ? '流放' : '正常',
            'changed' => true,
            'operation_info' => [
                'operator_user_id' => $admin_user_id,
                'operator_username' => $operator ? $operator['username'] : null,
                'operator_type' => 'admin',
                'operator_type_text' => '管理员',
                'operation_source' => 'admin_panel',
                'operation_source_text' => '管理后台',
                'comment' => $comment,
                'operation_time' => date('Y-m-d H:i:s')
            ]
        ];

        echo json_encode([
            'success' => true,
            'data' => $response_data,
            'message' => $status == 1 ? '壁纸流放成功' : '壁纸召回成功'
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        // 回滚事务
        $pdo->rollBack();
        throw $e;
    }

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
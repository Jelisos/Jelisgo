<?php
/**
 * 批量更新壁纸状态API
 * 功能：批量更新多张壁纸的流放状态
 * 权限：管理员专用
 * 路径：/api/admin/batch_update_status.php
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
    $raw_input = file_get_contents('php://input');
    $input = json_decode($raw_input, true);
    
    if (!$input) {
        // 如果JSON解析失败，尝试从$_POST获取数据
        if (!empty($_POST)) {
            $input = $_POST;
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error_code' => 'INVALID_JSON',
                'message' => '无效的JSON数据或POST数据',
                'debug_info' => [
                    'raw_input' => substr($raw_input, 0, 200),
                    'json_error' => json_last_error_msg(),
                    'post_data' => $_POST
                ]
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // 验证必需参数
    if (!isset($input['wallpaper_ids']) || !isset($input['status'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error_code' => 'MISSING_PARAMETERS',
            'message' => '缺少必需参数：wallpaper_ids 和 status'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $wallpaper_ids = $input['wallpaper_ids'];
    $status = intval($input['status']);
    $comment = isset($input['comment']) ? trim($input['comment']) : null;

    // 验证参数
    if (!is_array($wallpaper_ids) || empty($wallpaper_ids)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error_code' => 'INVALID_WALLPAPER_IDS',
            'message' => 'wallpaper_ids 必须是非空数组'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (count($wallpaper_ids) > 100) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error_code' => 'TOO_MANY_WALLPAPERS',
            'message' => '单次批量操作最多支持100张壁纸'
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

    // 验证所有壁纸ID都是有效的整数
    $valid_wallpaper_ids = [];
    foreach ($wallpaper_ids as $id) {
        $id = intval($id);
        if ($id > 0) {
            $valid_wallpaper_ids[] = $id;
        }
    }

    if (empty($valid_wallpaper_ids)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error_code' => 'NO_VALID_IDS',
            'message' => '没有有效的壁纸ID'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 验证壁纸是否存在
    $placeholders = str_repeat('?,', count($valid_wallpaper_ids) - 1) . '?';
    $check_sql = "SELECT id, title FROM wallpapers WHERE id IN ($placeholders)";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute($valid_wallpaper_ids);
    $existing_wallpapers = $check_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    if (count($existing_wallpapers) !== count($valid_wallpaper_ids)) {
        $missing_ids = array_diff($valid_wallpaper_ids, array_keys($existing_wallpapers));
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error_code' => 'WALLPAPERS_NOT_FOUND',
            'message' => '以下壁纸ID不存在：' . implode(', ', $missing_ids)
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 开始事务
    $pdo->beginTransaction();

    try {
        $success_count = 0;
        $failed_count = 0;
        $results = [];

        foreach ($valid_wallpaper_ids as $wallpaper_id) {
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

                $success_count++;
                $results[] = [
                    'wallpaper_id' => $wallpaper_id,
                    'wallpaper_title' => $existing_wallpapers[$wallpaper_id],
                    'status' => 'success',
                    'message' => $status == 1 ? '流放成功' : '召回成功'
                ];

            } catch (Exception $e) {
                $failed_count++;
                $results[] = [
                    'wallpaper_id' => $wallpaper_id,
                    'wallpaper_title' => $existing_wallpapers[$wallpaper_id] ?? '未知',
                    'status' => 'failed',
                    'message' => '操作失败：' . $e->getMessage()
                ];
            }
        }

        // 提交事务
        $pdo->commit();

        $response_data = [
            'total_count' => count($valid_wallpaper_ids),
            'success_count' => $success_count,
            'failed_count' => $failed_count,
            'operation_info' => [
                'status' => $status,
                'status_text' => $status == 1 ? '流放' : '召回',
                'operator_user_id' => $admin_user_id,
                'operator_type' => 'admin',
                'operator_type_text' => '管理员',
                'operation_source' => 'admin_panel',
                'operation_source_text' => '管理后台',
                'comment' => $comment,
                'operation_time' => date('Y-m-d H:i:s')
            ],
            'results' => $results
        ];

        $message = "批量操作完成：成功 {$success_count} 个";
        if ($failed_count > 0) {
            $message .= "，失败 {$failed_count} 个";
        }

        echo json_encode([
            'success' => true,
            'data' => $response_data,
            'message' => $message
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
<?php
/**
 * 批量更新壁纸状态API
 * 功能：批量更新多张壁纸的流放状态
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => '仅支持POST请求'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // 数据库连接
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

    // 获取POST数据
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('无效的JSON数据');
    }

    // 验证必需参数
    if (!isset($input['wallpaper_ids']) || !isset($input['status'])) {
        throw new Exception('缺少必需参数：wallpaper_ids 和 status');
    }

    $wallpaper_ids = $input['wallpaper_ids'];
    $status = intval($input['status']);
    $operator_user_id = isset($input['operator_user_id']) ? intval($input['operator_user_id']) : null;
    $operator_type = isset($input['operator_type']) ? $input['operator_type'] : 'user';
    $operation_source = isset($input['operation_source']) ? $input['operation_source'] : 'frontend';
    $comment = isset($input['comment']) ? trim($input['comment']) : null;

    // 验证参数
    if (!is_array($wallpaper_ids) || empty($wallpaper_ids)) {
        throw new Exception('wallpaper_ids 必须是非空数组');
    }

    if (count($wallpaper_ids) > 100) {
        throw new Exception('单次批量操作最多支持100张壁纸');
    }

    if (!in_array($status, [0, 1])) {
        throw new Exception('无效的状态值，只能是0（正常）或1（流放）');
    }

    if (!in_array($operator_type, ['user', 'admin'])) {
        throw new Exception('无效的操作者类型');
    }

    if (!in_array($operation_source, ['frontend', 'admin_panel'])) {
        throw new Exception('无效的操作来源');
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
        throw new Exception('没有有效的壁纸ID');
    }

    // 验证操作者用户是否存在（如果提供了用户ID）
    $operator_username = null;
    if ($operator_user_id) {
        $user_check_sql = "SELECT id, username FROM users WHERE id = :user_id";
        $user_check_stmt = $pdo->prepare($user_check_sql);
        $user_check_stmt->execute([':user_id' => $operator_user_id]);
        $user = $user_check_stmt->fetch();

        if (!$user) {
            throw new Exception('操作者用户不存在');
        }
        $operator_username = $user['username'];
    }

    // 验证壁纸是否存在
    $placeholders = str_repeat('?,', count($valid_wallpaper_ids) - 1) . '?';
    $check_sql = "SELECT id, title FROM wallpapers WHERE id IN ($placeholders)";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute($valid_wallpaper_ids);
    $existing_wallpapers = $check_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    if (count($existing_wallpapers) !== count($valid_wallpaper_ids)) {
        $missing_ids = array_diff($valid_wallpaper_ids, array_keys($existing_wallpapers));
        throw new Exception('以下壁纸ID不存在：' . implode(', ', $missing_ids));
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
                        (:wallpaper_id, :status, :last_operator_user_id, :operator_type, :operation_source, :comment, NOW())
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
                        ':wallpaper_id' => $wallpaper_id,
                        ':status' => $status,
                        ':last_operator_user_id' => $operator_user_id,
                        ':operator_type' => $operator_type,
                        ':operation_source' => $operation_source,
                        ':comment' => $comment
                    ]);
                } else {
                    // 正常状态：删除记录
                    $delete_sql = "DELETE FROM wallpaper_exile_status WHERE wallpaper_id = :wallpaper_id";
                    $delete_stmt = $pdo->prepare($delete_sql);
                    $delete_stmt->execute([':wallpaper_id' => $wallpaper_id]);
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
                'operator_user_id' => $operator_user_id,
                'operator_username' => $operator_username,
                'operator_type' => $operator_type,
                'operator_type_text' => $operator_type == 'admin' ? '管理员' : '普通用户',
                'operation_source' => $operation_source,
                'operation_source_text' => $operation_source == 'admin_panel' ? '管理后台' : '前端首页',
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
            'success' => $failed_count == 0,
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
        'message' => '数据库错误：' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
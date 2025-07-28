<?php
/**
 * 单个壁纸状态更新API
 * 功能：更新单张壁纸的流放状态
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
    if (!isset($input['wallpaper_id']) || !isset($input['status'])) {
        throw new Exception('缺少必需参数：wallpaper_id 和 status');
    }

    $wallpaper_id = intval($input['wallpaper_id']);
    $status = intval($input['status']);
    $operator_user_id = isset($input['operator_user_id']) ? intval($input['operator_user_id']) : null;
    $operator_type = isset($input['operator_type']) ? $input['operator_type'] : 'user';
    $operation_source = isset($input['operation_source']) ? $input['operation_source'] : 'frontend';
    $comment = isset($input['comment']) ? trim($input['comment']) : null;

    // 验证参数值
    if ($wallpaper_id <= 0) {
        throw new Exception('无效的壁纸ID');
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

    // 验证壁纸是否存在
    $check_sql = "SELECT id, title FROM wallpapers WHERE id = :wallpaper_id";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([':wallpaper_id' => $wallpaper_id]);
    $wallpaper = $check_stmt->fetch();

    if (!$wallpaper) {
        throw new Exception('壁纸不存在');
    }

    // 验证操作者用户是否存在（如果提供了用户ID）
    if ($operator_user_id) {
        $user_check_sql = "SELECT id, username FROM users WHERE id = :user_id";
        $user_check_stmt = $pdo->prepare($user_check_sql);
        $user_check_stmt->execute([':user_id' => $operator_user_id]);
        $user = $user_check_stmt->fetch();

        if (!$user) {
            throw new Exception('操作者用户不存在');
        }
    }

    // 开始事务
    $pdo->beginTransaction();

    try {
        // 使用UPSERT操作（INSERT ... ON DUPLICATE KEY UPDATE）
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

        // 如果状态是正常（0），则删除记录（因为我们只记录流放状态）
        if ($status == 0) {
            $delete_sql = "DELETE FROM wallpaper_exile_status WHERE wallpaper_id = :wallpaper_id";
            $delete_stmt = $pdo->prepare($delete_sql);
            $delete_stmt->execute([':wallpaper_id' => $wallpaper_id]);
        }

        // 提交事务
        $pdo->commit();

        // 获取更新后的状态信息
        $result_data = [
            'wallpaper_id' => $wallpaper_id,
            'status' => $status,
            'status_text' => $status == 1 ? '流放' : '正常',
            'wallpaper_title' => $wallpaper['title'],
            'operator_user_id' => $operator_user_id,
            'operator_type' => $operator_type,
            'operator_type_text' => $operator_type == 'admin' ? '管理员' : '普通用户',
            'operation_source' => $operation_source,
            'operation_source_text' => $operation_source == 'admin_panel' ? '管理后台' : '前端首页',
            'comment' => $comment,
            'operation_time' => date('Y-m-d H:i:s')
        ];

        if ($operator_user_id && isset($user)) {
            $result_data['operator_username'] = $user['username'];
        }

        echo json_encode([
            'success' => true,
            'data' => $result_data,
            'message' => $status == 1 ? '壁纸已流放' : '壁纸已召回'
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
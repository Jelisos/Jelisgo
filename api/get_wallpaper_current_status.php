<?php
/**
 * 获取壁纸当前状态API
 * 功能：获取指定壁纸的当前流放状态
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
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

    // 获取请求参数
    $wallpaper_id = null;
    $wallpaper_ids = null;

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (isset($_GET['wallpaper_id'])) {
            $wallpaper_id = intval($_GET['wallpaper_id']);
        } elseif (isset($_GET['wallpaper_ids'])) {
            $wallpaper_ids = explode(',', $_GET['wallpaper_ids']);
            $wallpaper_ids = array_map('intval', $wallpaper_ids);
            $wallpaper_ids = array_filter($wallpaper_ids, function($id) { return $id > 0; });
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if ($input) {
            if (isset($input['wallpaper_id'])) {
                $wallpaper_id = intval($input['wallpaper_id']);
            } elseif (isset($input['wallpaper_ids'])) {
                $wallpaper_ids = $input['wallpaper_ids'];
                if (is_array($wallpaper_ids)) {
                    $wallpaper_ids = array_map('intval', $wallpaper_ids);
                    $wallpaper_ids = array_filter($wallpaper_ids, function($id) { return $id > 0; });
                }
            }
        }
    }

    // 验证参数
    if (!$wallpaper_id && (!$wallpaper_ids || empty($wallpaper_ids))) {
        throw new Exception('请提供 wallpaper_id 或 wallpaper_ids 参数');
    }

    if ($wallpaper_ids && count($wallpaper_ids) > 100) {
        throw new Exception('单次查询最多支持100张壁纸');
    }

    // 构建查询
    if ($wallpaper_id) {
        // 单个壁纸查询
        $sql = "
            SELECT 
                w.id as wallpaper_id,
                w.title as wallpaper_title,
                w.file_name as wallpaper_file_name,
                w.file_path as wallpaper_file_path,
                COALESCE(wes.status, 0) as status,
                wes.last_operator_user_id,
                wes.operator_type,
                wes.operation_source,
                wes.last_operation_time,
                wes.created_at,
                wes.comment,
                u.username as operator_username
            FROM wallpapers w
            LEFT JOIN wallpaper_exile_status wes ON w.id = wes.wallpaper_id
            LEFT JOIN users u ON wes.last_operator_user_id = u.id
            WHERE w.id = :wallpaper_id
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':wallpaper_id' => $wallpaper_id]);
        $result = $stmt->fetch();
        
        if (!$result) {
            throw new Exception('壁纸不存在');
        }
        
        // 格式化单个结果
        $formatted_result = [
            'wallpaper_id' => intval($result['wallpaper_id']),
            'status' => intval($result['status']),
            'status_text' => $result['status'] == 1 ? '流放' : '正常',
            'is_exiled' => $result['status'] == 1,
            'wallpaper_info' => [
                'title' => $result['wallpaper_title'],
                'file_name' => $result['wallpaper_file_name'],
                'file_path' => $result['wallpaper_file_path']
            ]
        ];
        
        // 如果有流放记录，添加操作者信息
        if ($result['status'] == 1) {
            $formatted_result['last_operation'] = [
                'operator_user_id' => $result['last_operator_user_id'] ? intval($result['last_operator_user_id']) : null,
                'operator_username' => $result['operator_username'],
                'operator_type' => $result['operator_type'],
                'operator_type_text' => $result['operator_type'] == 'admin' ? '管理员' : '普通用户',
                'operation_source' => $result['operation_source'],
                'operation_source_text' => $result['operation_source'] == 'admin_panel' ? '管理后台' : '前端首页',
                'operation_time' => $result['last_operation_time'],
                'comment' => $result['comment']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $formatted_result,
            'message' => '获取成功'
        ], JSON_UNESCAPED_UNICODE);
        
    } else {
        // 批量壁纸查询
        $placeholders = str_repeat('?,', count($wallpaper_ids) - 1) . '?';
        $sql = "
            SELECT 
                w.id as wallpaper_id,
                w.title as wallpaper_title,
                w.file_name as wallpaper_file_name,
                w.file_path as wallpaper_file_path,
                COALESCE(wes.status, 0) as status,
                wes.last_operator_user_id,
                wes.operator_type,
                wes.operation_source,
                wes.last_operation_time,
                wes.created_at,
                wes.comment,
                u.username as operator_username
            FROM wallpapers w
            LEFT JOIN wallpaper_exile_status wes ON w.id = wes.wallpaper_id
            LEFT JOIN users u ON wes.last_operator_user_id = u.id
            WHERE w.id IN ($placeholders)
            ORDER BY w.id
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($wallpaper_ids);
        $results = $stmt->fetchAll();
        
        // 格式化批量结果
        $formatted_results = [];
        $found_ids = [];
        
        foreach ($results as $result) {
            $found_ids[] = intval($result['wallpaper_id']);
            
            $formatted_result = [
                'wallpaper_id' => intval($result['wallpaper_id']),
                'status' => intval($result['status']),
                'status_text' => $result['status'] == 1 ? '流放' : '正常',
                'is_exiled' => $result['status'] == 1,
                'wallpaper_info' => [
                    'title' => $result['wallpaper_title'],
                    'file_name' => $result['wallpaper_file_name'],
                    'file_path' => $result['wallpaper_file_path']
                ]
            ];
            
            // 如果有流放记录，添加操作者信息
            if ($result['status'] == 1) {
                $formatted_result['last_operation'] = [
                    'operator_user_id' => $result['last_operator_user_id'] ? intval($result['last_operator_user_id']) : null,
                    'operator_username' => $result['operator_username'],
                    'operator_type' => $result['operator_type'],
                    'operator_type_text' => $result['operator_type'] == 'admin' ? '管理员' : '普通用户',
                    'operation_source' => $result['operation_source'],
                    'operation_source_text' => $result['operation_source'] == 'admin_panel' ? '管理后台' : '前端首页',
                    'operation_time' => $result['last_operation_time'],
                    'comment' => $result['comment']
                ];
            }
            
            $formatted_results[] = $formatted_result;
        }
        
        // 检查是否有不存在的壁纸ID
        $missing_ids = array_diff($wallpaper_ids, $found_ids);
        
        $response_data = [
            'results' => $formatted_results,
            'summary' => [
                'total_requested' => count($wallpaper_ids),
                'total_found' => count($found_ids),
                'total_missing' => count($missing_ids),
                'exiled_count' => count(array_filter($formatted_results, function($r) { return $r['is_exiled']; })),
                'normal_count' => count(array_filter($formatted_results, function($r) { return !$r['is_exiled']; }))
            ]
        ];
        
        if (!empty($missing_ids)) {
            $response_data['missing_wallpaper_ids'] = $missing_ids;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $response_data,
            'message' => '获取成功'
        ], JSON_UNESCAPED_UNICODE);
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
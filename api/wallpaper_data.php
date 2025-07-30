<?php
/**
 * 文件: api/wallpaper_data.php
 * 描述: 壁纸数据API接口 - 数据库迁移第二阶段
 * 功能: 从数据库获取壁纸数据，替代list.json文件
 * 创建时间: 2025-01-27
 * 维护: AI助手
 * 更新: 2025-01-30 增强线上环境兼容性
 */

// 开启输出缓冲，防止意外输出影响JSON格式
ob_start();

// 设置错误处理，确保所有错误都以JSON格式返回
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// 增强的异常处理器 - 确保绝对不会输出HTML
set_exception_handler(function($exception) {
    // 清理所有输出缓冲
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // 清理任何可能的输出
    if (headers_sent()) {
        // 如果头已发送，尝试清理输出
        echo "\n\n";
    }
    
    // 强制设置JSON头
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
    }
    
    $error_response = [
        'code' => 1,
        'message' => '服务器内部错误: ' . $exception->getMessage(),
        'debug_info' => [
            'file' => basename($exception->getFile()),
            'line' => $exception->getLine(),
            'environment' => isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'unknown',
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ];
    
    echo json_encode($error_response, JSON_UNESCAPED_UNICODE);
    exit;
});

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 引入数据库配置
require_once __DIR__ . '/../config/database.php';
// 引入工具函数
require_once __DIR__ . '/utils.php';

// 日志记录函数
function logApiAccess($action, $params = [], $result = 'success') {
    $logFile = __DIR__ . '/../logs/wallpaper_api.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $logData = [
        'timestamp' => $timestamp,
        'ip' => $ip,
        'action' => $action,
        'params' => $params,
        'result' => $result,
        'user_agent' => substr($userAgent, 0, 200)
    ];
    
    file_put_contents($logFile, json_encode($logData, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
}

try {
    // 建立数据库连接（带重试机制）
    $conn = null;
    $maxRetries = 3;
    $retryDelay = 1; // 秒
    
    for ($i = 0; $i < $maxRetries; $i++) {
        $conn = getDBConnection();
        if ($conn) {
            break;
        }
        
        if ($i < $maxRetries - 1) {
            sleep($retryDelay);
            $retryDelay *= 2; // 指数退避
        }
    }
    
    if (!$conn) {
        // 记录详细的环境信息用于调试
        $debug_info = [
            'server_name' => $_SERVER['SERVER_NAME'] ?? 'unknown',
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'unknown',
            'php_version' => PHP_VERSION,
            'mysql_extension' => extension_loaded('mysqli') ? 'loaded' : 'not_loaded'
        ];
        
        throw new Exception('数据库连接失败，已重试' . $maxRetries . '次。调试信息: ' . json_encode($debug_info));
    }
    
    // 获取请求参数
    $action = $_GET['action'] ?? 'list';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = min(520, max(1, intval($_GET['limit'] ?? 20))); // 2025-01-27 修复：提高限制到520张以支持完整数据加载
    $category = $_GET['category'] ?? 'all';
    $search = trim($_GET['search'] ?? '');
    $exile_status = $_GET['exile_status'] ?? 'all'; // 新增：流放状态筛选 (all, normal, exiled)
    $offset = ($page - 1) * $limit;
    
    // 记录API访问
    logApiAccess($action, [
        'page' => $page,
        'limit' => $limit,
        'category' => $category,
        'search' => $search,
        'exile_status' => $exile_status
    ]);
    
    switch ($action) {
        case 'list':
            // 构建查询条件
            $whereConditions = [];
            $params = [];
            $types = '';
            
            // 分类筛选
            if ($category !== 'all' && !empty($category)) {
                $whereConditions[] = 'w.category = ?';
                $params[] = $category;
                $types .= 's';
            }
            
            // 搜索功能
            if (!empty($search)) {
                $whereConditions[] = '(w.title LIKE ? OR w.description LIKE ? OR w.tags LIKE ?)';
                $searchTerm = '%' . $search . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $types .= 'sss';
            }
            
            // 流放状态筛选
            if ($exile_status === 'exiled') {
                $whereConditions[] = 'wes.status = 1';
            } elseif ($exile_status === 'normal') {
                $whereConditions[] = '(wes.status IS NULL OR wes.status = 0)';
            }
            
            // 构建WHERE子句
            $whereClause = '';
            if (!empty($whereConditions)) {
                $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
            }
            
            // 查询总数
            $countSql = "SELECT COUNT(*) as total 
                        FROM wallpapers w 
                        LEFT JOIN wallpaper_exile_status wes ON w.id = wes.wallpaper_id 
                        {$whereClause}";
            $countStmt = $conn->prepare($countSql);
            if (!empty($params)) {
                $countStmt->bind_param($types, ...$params);
            }
            $countStmt->execute();
            $countResult = $countStmt->get_result();
            $totalCount = $countResult->fetch_assoc()['total'];
            $countStmt->close();
            
            // 查询数据
            $dataSql = "SELECT 
                            w.id,
                            w.title,
                            w.description,
                            w.file_path as path,
                            w.file_size as size,
                            w.width,
                            w.height,
                            w.category,
                            w.tags,
                            w.format,
                            w.views,
                            w.likes,
                            w.created_at,
                            w.updated_at,
                            COALESCE(wes.status, 0) as exile_status,
                            wes.last_operator_user_id,
                            wes.operator_type,
                            wes.operation_source,
                            wes.last_operation_time,
                            u.username as operator_username
                        FROM wallpapers w
                        LEFT JOIN wallpaper_exile_status wes ON w.id = wes.wallpaper_id
                        LEFT JOIN users u ON wes.last_operator_user_id = u.id
                        {$whereClause}
                        ORDER BY w.created_at DESC 
                        LIMIT ? OFFSET ?";
            
            $dataStmt = $conn->prepare($dataSql);
            $dataParams = $params;
            $dataParams[] = $limit;
            $dataParams[] = $offset;
            $dataTypes = $types . 'ii';
            
            if (!empty($dataParams)) {
                $dataStmt->bind_param($dataTypes, ...$dataParams);
            }
            $dataStmt->execute();
            $result = $dataStmt->get_result();
            
            $wallpapers = [];
            while ($row = $result->fetch_assoc()) {
                // 格式化数据以兼容前端
                $wallpaper = [
                    'id' => $row['id'],
                    'filename' => basename($row['path']),
                    'path' => $row['path'],
                    'name' => $row['title'],
                    'title' => $row['title'],
                    'category' => $row['category'],
                    'tags' => json_decode($row['tags'] ?? '[]', true),
                    'width' => intval($row['width']),
                    'height' => intval($row['height']),
                    'size' => $row['size'],
                    'format' => $row['format'],
                    'description' => $row['description'] ?? '',
                    'views' => intval($row['views']),
                    'likes' => intval($row['likes']),
                    'created_at' => date('Y-m-d', strtotime($row['created_at'])),
                    // 新增：流放状态信息
                    'exile_status' => intval($row['exile_status']),
                    'is_exiled' => intval($row['exile_status']) === 1,
                    'exile_info' => null
                ];
                
                // 如果壁纸被流放，添加流放信息
                if (intval($row['exile_status']) === 1) {
                    $wallpaper['exile_info'] = [
                        'operator_user_id' => $row['last_operator_user_id'] ? intval($row['last_operator_user_id']) : null,
                        'operator_username' => $row['operator_username'],
                        'operator_type' => $row['operator_type'],
                        'operator_type_text' => $row['operator_type'] === 'admin' ? '管理员' : '普通用户',
                        'operation_source' => $row['operation_source'],
                        'operation_source_text' => $row['operation_source'] === 'admin_panel' ? '管理后台' : '前端首页',
                        'operation_time' => $row['last_operation_time']
                    ];
                }
                
                $wallpapers[] = $wallpaper;
            }
            $dataStmt->close();
            
            // 返回分页数据
            $responseData = [
                'wallpapers' => $wallpapers,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $totalCount,
                    'total_pages' => ceil($totalCount / $limit),
                    'has_next' => $page < ceil($totalCount / $limit),
                    'has_prev' => $page > 1
                ],
                'filters' => [
                    'category' => $category,
                    'search' => $search,
                    'exile_status' => $exile_status
                ]
            ];
            
            sendResponse(0, '数据获取成功', $responseData);
            break;
            
        case 'categories':
            // 获取所有分类
            $sql = "SELECT DISTINCT category FROM wallpapers WHERE category IS NOT NULL AND category != '' ORDER BY category";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $categories = ['all'];
            while ($row = $result->fetch_assoc()) {
                if (!empty($row['category'])) {
                    $categories[] = $row['category'];
                }
            }
            $stmt->close();
            
            sendResponse(0, '分类获取成功', ['categories' => $categories]);
            break;
            
        case 'stats':
            // 获取统计信息
            $sql = "SELECT 
                        COUNT(*) as total_wallpapers,
                        COUNT(DISTINCT category) as total_categories,
                        SUM(views) as total_views,
                        SUM(likes) as total_likes
                    FROM wallpapers";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->get_result();
            $stats = $result->fetch_assoc();
            $stmt->close();
            
            sendResponse(0, '统计信息获取成功', $stats);
            break;
            
        default:
            sendResponse(1, '不支持的操作类型');
            break;
    }
    
} catch (Exception $e) {
    // 记录错误日志
    logApiAccess($action ?? 'unknown', $_GET, 'error: ' . $e->getMessage());
    
    // 清理所有输出缓冲
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // 强制确保JSON响应
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
    }
    
    // 返回错误响应
    echo json_encode([
        'code' => 1,
        'msg' => '服务器内部错误: ' . $e->getMessage(),
        'data' => null,
        'debug_info' => [
            'timestamp' => date('Y-m-d H:i:s'),
            'error_type' => 'catch_block',
            'environment' => $_SERVER['SERVER_NAME'] ?? 'unknown'
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
} finally {
    // 关闭数据库连接
    if (isset($conn) && $conn) {
        $conn->close();
    }
    
    // 确保输出缓冲被正确处理
    while (ob_get_level()) {
        ob_end_flush();
    }
}
?>
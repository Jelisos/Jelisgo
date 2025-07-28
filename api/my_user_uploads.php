<?php
/**
 * 文件: api/my_user_uploads.php
 * 描述: 获取当前登录用户上传的壁纸列表（从user_wallpapers表）- 统一认证版本
 * 维护: 用户上传壁纸列表功能相关修改请编辑此文件
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';
require_once 'utils.php';

// sendResponse函数已在utils.php中定义，此处移除重复定义

/**
 * 获取用户上传壁纸列表
 */
function getUserWallpapersList($pdo, $params, $userId) {
    $page = max(1, intval($params['page'] ?? 1));
    $limit = max(1, min(100, intval($params['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    
    $whereConditions = ['user_id = :user_id'];
    $bindParams = [':user_id' => $userId];
    
    // 状态筛选
    if (!empty($params['status'])) {
        $whereConditions[] = "status = :status";
        $bindParams[':status'] = $params['status'];
    }
    
    // 分类筛选
    if (!empty($params['category'])) {
        $whereConditions[] = "category = :category";
        $bindParams[':category'] = $params['category'];
    }
    
    // 搜索关键词
    if (!empty($params['search'])) {
        $searchTerm = '%' . $params['search'] . '%';
        $whereConditions[] = "(title LIKE :search OR description LIKE :search2 OR tags LIKE :search3)";
        $bindParams[':search'] = $searchTerm;
        $bindParams[':search2'] = $searchTerm;
        $bindParams[':search3'] = $searchTerm;
    }
    
    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    
    // 获取总数
    $countSql = "SELECT COUNT(*) FROM user_wallpapers $whereClause";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($bindParams);
    $totalItems = $countStmt->fetchColumn();
    
    // 获取列表数据
    $sql = "SELECT * FROM user_wallpapers 
            $whereClause 
            ORDER BY created_at DESC 
            LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    foreach ($bindParams as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $wallpapers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 处理标签和数据格式
    foreach ($wallpapers as &$wallpaper) {
        $wallpaper['tags'] = !empty($wallpaper['tags']) ? explode(',', $wallpaper['tags']) : [];
        $wallpaper['id'] = intval($wallpaper['id']);
        $wallpaper['width'] = intval($wallpaper['width']);
        $wallpaper['height'] = intval($wallpaper['height']);
        $wallpaper['views'] = intval($wallpaper['views']);
        $wallpaper['likes'] = intval($wallpaper['likes']);
    }
    
    $totalPages = ceil($totalItems / $limit);
    
    return [
        'wallpapers' => $wallpapers,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_items' => $totalItems,
            'items_per_page' => $limit,
            'has_prev' => $page > 1,
            'has_next' => $page < $totalPages
        ]
    ];
}

/**
 * 获取壁纸详情
 */
function getWallpaperDetail($pdo, $id, $userId) {
    $sql = "SELECT * FROM user_wallpapers WHERE id = :id AND user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    $wallpaper = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($wallpaper) {
        $wallpaper['tags'] = !empty($wallpaper['tags']) ? explode(',', $wallpaper['tags']) : [];
        $wallpaper['id'] = intval($wallpaper['id']);
        $wallpaper['width'] = intval($wallpaper['width']);
        $wallpaper['height'] = intval($wallpaper['height']);
        $wallpaper['views'] = intval($wallpaper['views']);
        $wallpaper['likes'] = intval($wallpaper['likes']);
    }
    
    return $wallpaper;
}

/**
 * 获取用户上传壁纸总数
 */
function getUserWallpapersCount($pdo, $userId) {
    try {
        $sql = "SELECT COUNT(*) as count FROM user_wallpapers WHERE user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        sendResponse(200, '获取成功', ['count' => intval($result['count'])]);
        
    } catch (Exception $e) {
        error_log('获取用户上传数量失败: ' . $e->getMessage());
        sendResponse(500, '获取失败');
    }
}

/**
 * 删除用户壁纸
 */
function deleteUserWallpaper($pdo, $id, $userId) {
    try {
        $pdo->beginTransaction();
        
        // 检查壁纸是否属于当前用户
        $sql = "SELECT file_path FROM user_wallpapers WHERE id = :id AND user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $wallpaper = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$wallpaper) {
            throw new Exception('壁纸不存在或无权限删除');
        }
        
        // 删除数据库记录
        $sql = "DELETE FROM user_wallpapers WHERE id = :id AND user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        // 删除文件
        $filePath = '../' . $wallpaper['file_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('删除用户壁纸失败: ' . $e->getMessage());
        return false;
    }
}

try {
    // 获取请求头中的Authorization信息
    $headers = getallheaders();
    $userId = null;
    
    // 从请求中获取用户ID
    if (isset($_GET['user_id']) && !empty($_GET['user_id'])) {
        // 从GET参数获取用户ID
        $userId = intval($_GET['user_id']);
    } elseif (isset($_POST['user_id']) && !empty($_POST['user_id'])) {
        // 从POST参数获取用户ID
        $userId = intval($_POST['user_id']);
    } elseif (isset($headers['Authorization']) && !empty($headers['Authorization'])) {
        // 从Authorization头获取用户ID
        $auth_parts = explode(' ', $headers['Authorization']);
        if (count($auth_parts) == 2 && $auth_parts[0] == 'Bearer') {
            $userId = intval($auth_parts[1]);
        }
    }
    
    // 检查用户是否登录
    if (!$userId) {
        sendResponse(401, '请先登录');
    }
    $pdo = getPDOConnection();
    if (!$pdo) {
        sendResponse(500, '数据库连接失败');
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? 'list';
        
        if ($action === 'count') {
            getUserWallpapersCount($pdo, $userId);
        } elseif ($action === 'detail') {
            $id = intval($_GET['id'] ?? 0);
            if ($id <= 0) {
                sendResponse(400, '无效的壁纸ID');
            }
            
            $wallpaper = getWallpaperDetail($pdo, $id, $userId);
            if (!$wallpaper) {
                sendResponse(404, '壁纸不存在');
            }
            
            sendResponse(200, '获取成功', $wallpaper);
            
        } else {
            // 默认获取列表
            $data = getUserWallpapersList($pdo, $_GET, $userId);
            sendResponse(200, '获取成功', $data);
        }
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        $id = intval($input['id'] ?? 0);
        
        if ($action === 'delete') {
            if ($id <= 0) {
                sendResponse(400, '无效的壁纸ID');
            }
            
            if (deleteUserWallpaper($pdo, $id, $userId)) {
                sendResponse(200, '删除成功');
            } else {
                sendResponse(500, '删除失败');
            }
        } else {
            sendResponse(400, '无效的操作');
        }
        
    } else {
        sendResponse(405, '不支持的请求方法');
    }
    
} catch (Exception $e) {
    error_log('用户上传壁纸API错误: ' . $e->getMessage());
    sendResponse(500, '服务器内部错误');
}
?>
<?php
/**
 * 文件: api/admin_user_wallpapers.php
 * 描述: 管理员用户上传壁纸管理API
 * 维护: 管理员壁纸管理功能修改请编辑此文件
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once 'admin_auth.php';

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

/**
 * 获取用户上传壁纸列表
 * @param PDO $pdo
 * @param array $params
 * @return array
 */
function getUserWallpapersList($pdo, $params) {
    $page = max(1, intval($params['page'] ?? 1));
    $limit = max(1, min(100, intval($params['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    
    $whereConditions = [];
    $bindParams = [];
    
    // 状态筛选
    if (!empty($params['status'])) {
        $whereConditions[] = "uw.status = :status";
        $bindParams[':status'] = $params['status'];
    }
    
    // 分类筛选
    if (!empty($params['category'])) {
        $whereConditions[] = "uw.category = :category";
        $bindParams[':category'] = $params['category'];
    }
    
    // 搜索关键词
    if (!empty($params['search'])) {
        $searchTerm = '%' . $params['search'] . '%';
        $whereConditions[] = "(uw.title LIKE :search OR uw.description LIKE :search2 OR uw.tags LIKE :search3)";
        $bindParams[':search'] = $searchTerm;
        $bindParams[':search2'] = $searchTerm;
        $bindParams[':search3'] = $searchTerm;
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // 获取总数
    $countSql = "SELECT COUNT(*) FROM user_wallpapers uw 
                 LEFT JOIN users u ON uw.user_id = u.id 
                 $whereClause";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($bindParams);
    $totalItems = $countStmt->fetchColumn();
    
    // 获取列表数据
    $sql = "SELECT uw.*, u.username, u.email as user_email 
            FROM user_wallpapers uw 
            LEFT JOIN users u ON uw.user_id = u.id 
            $whereClause 
            ORDER BY uw.created_at DESC 
            LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    foreach ($bindParams as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $wallpapers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 处理标签
    foreach ($wallpapers as &$wallpaper) {
        $wallpaper['tags'] = !empty($wallpaper['tags']) ? explode(',', $wallpaper['tags']) : [];
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
 * @param PDO $pdo
 * @param int $id
 * @return array|null
 */
function getWallpaperDetail($pdo, $id) {
    $sql = "SELECT uw.*, u.username, u.email as user_email 
            FROM user_wallpapers uw 
            LEFT JOIN users u ON uw.user_id = u.id 
            WHERE uw.id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
    $wallpaper = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($wallpaper) {
        $wallpaper['tags'] = !empty($wallpaper['tags']) ? explode(',', $wallpaper['tags']) : [];
    }
    
    return $wallpaper;
}

/**
 * 审核通过壁纸
 * @param PDO $pdo
 * @param int $id
 * @return bool
 */
function approveWallpaper($pdo, $id) {
    $sql = "UPDATE user_wallpapers SET status = 'approved', updated_at = NOW() WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    return $stmt->execute();
}

/**
 * 拒绝壁纸
 * @param PDO $pdo
 * @param int $id
 * @param string $reason
 * @return bool
 */
function rejectWallpaper($pdo, $id, $reason = '') {
    $sql = "UPDATE user_wallpapers SET status = 'rejected', reject_reason = :reason, updated_at = NOW() WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':reason', $reason);
    return $stmt->execute();
}

/**
 * 删除壁纸
 * @param PDO $pdo
 * @param int $id
 * @return bool
 */
function deleteWallpaper($pdo, $id) {
    try {
        $pdo->beginTransaction();
        
        // 获取文件路径
        $sql = "SELECT file_path FROM user_wallpapers WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $wallpaper = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$wallpaper) {
            throw new Exception('壁纸不存在');
        }
        
        // 删除数据库记录
        $sql = "DELETE FROM user_wallpapers WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
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
        error_log('删除壁纸失败: ' . $e->getMessage());
        return false;
    }
}

// sendResponse函数已在utils.php中定义，此处移除重复定义

try {
    // 检查管理员权限 - 使用统一的Authorization头验证
    // 注意：已完全废弃SESSION验证，改为LOCAL和数据库管理员验证
    $adminUserId = checkAdminPermission();
    if (!$adminUserId) {
        sendResponse(403, '权限不足');
    }
    
    $pdo = getPDOConnection();
    if (!$pdo) {
        sendResponse(500, '数据库连接失败');
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? 'list';
        
        if ($action === 'detail') {
            $id = intval($_GET['id'] ?? 0);
            if ($id <= 0) {
                sendResponse(400, '无效的壁纸ID');
            }
            
            $wallpaper = getWallpaperDetail($pdo, $id);
            if (!$wallpaper) {
                sendResponse(404, '壁纸不存在');
            }
            
            sendResponse(200, '获取成功', $wallpaper);
            
        } elseif ($action === 'count') {
            $count = getUserWallpapersCount($pdo, $_GET);
            sendResponse(200, '获取成功', ['count' => $count]);
            
        } elseif ($action === 'pending_count') {
            // 获取待审核壁纸数量
            $pendingParams = ['status' => 'pending'];
            $count = getUserWallpapersCount($pdo, $pendingParams);
            sendResponse(200, '获取成功', ['count' => $count]);
            
        } else {
            // 默认获取列表
            $data = getUserWallpapersList($pdo, $_GET);
            sendResponse(200, '获取成功', $data);
        }
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        $id = intval($input['id'] ?? 0);
        
        if ($id <= 0) {
            sendResponse(400, '无效的壁纸ID');
        }
        
        switch ($action) {
            case 'approve':
                if (approveWallpaper($pdo, $id)) {
                    sendResponse(200, '审核通过成功');
                } else {
                    sendResponse(500, '审核通过失败');
                }
                break;
                
            case 'reject':
                $reason = $input['reason'] ?? '';
                if (rejectWallpaper($pdo, $id, $reason)) {
                    sendResponse(200, '审核拒绝成功');
                } else {
                    sendResponse(500, '审核拒绝失败');
                }
                break;
                
            case 'delete':
                if (deleteWallpaper($pdo, $id)) {
                    sendResponse(200, '删除成功');
                } else {
                    sendResponse(500, '删除失败');
                }
                break;
                
            default:
                sendResponse(400, '无效的操作');
        }
        
    } else {
        sendResponse(405, '不支持的请求方法');
    }
    
} catch (Exception $e) {
    error_log('管理员用户壁纸API错误: ' . $e->getMessage());
    sendResponse(500, '服务器内部错误');
}

/**
 * 获取用户上传壁纸总数
 * @param PDO $pdo
 * @param array $params
 * @return int
 */
function getUserWallpapersCount($pdo, $params) {
    $whereConditions = [];
    $bindParams = [];
    
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
    
    // 搜索
    if (!empty($params['search'])) {
        $whereConditions[] = "(title LIKE :search OR description LIKE :search OR tags LIKE :search)";
        $bindParams[':search'] = '%' . $params['search'] . '%';
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    $sql = "SELECT COUNT(*) as count FROM user_wallpapers {$whereClause}";
    $stmt = $pdo->prepare($sql);
    
    foreach ($bindParams as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return intval($result['count']);
}
?>
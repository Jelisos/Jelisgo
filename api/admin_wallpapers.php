<?php
/**
 * 壁纸审核管理API
 * 位置: api/admin_wallpapers.php
 */
require_once 'admin_auth.php';
require_once 'response_helper.php';
require_once '../config/database.php';

// 获取数据库连接
$conn = getDBConnection();
if (!$conn) {
    error('数据库连接失败', 500);
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$admin_id = checkAdminAuth();

// 解析JSON输入（POST请求）
$input = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');
    if ($json) {
        $input = json_decode($json, true);
    }
    // 如果JSON解析失败，回退到$_POST
    if (!$input) {
        $input = $_POST;
    }
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'list':
                getAllWallpapers();
                break;
            case 'pending':
                getPendingWallpapers();
                break;
            case 'all':
                getAllWallpapers();
                break;
            case 'detail':
                getWallpaperDetail();
                break;
            case 'pending_count':
                getPendingCount();
                break;
            default:
                error('无效的操作');
        }
        break;
        
    case 'POST':
        $action = $input['action'] ?? $_POST['action'] ?? '';
        
        switch ($action) {
            case 'approve':
                approveWallpaper($input);
                break;
            case 'reject':
                rejectWallpaper($input);
                break;
            case 'batch_approve':
                batchApproveWallpapers($input);
                break;
            case 'batch_reject':
                batchRejectWallpapers($input);
                break;
            case 'delete':
                deleteWallpaper($input);
                break;
            default:
                error('无效的操作');
        }
        break;
        
    default:
        error('不支持的请求方法');
}

/**
 * 获取待审核壁纸列表
 */
function getPendingWallpapers() {
    global $conn;
    
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 20);
    $offset = ($page - 1) * $limit;
    
    try {
        // 获取待审核壁纸总数
        $result = $conn->query("
            SELECT COUNT(*) as total 
            FROM wallpapers w
            LEFT JOIN wallpaper_review_status wrs ON w.id = wrs.wallpaper_id
            WHERE COALESCE(wrs.status, 'pending') = 'pending'
        ");
        $total = $result->fetch_assoc()['total'];
        
        // 获取待审核壁纸列表
        $stmt = $conn->prepare("
            SELECT w.id, 
                   w.title,
                   w.description,
                   w.file_path as image_url,
                   w.file_path as thumbnail_url,
                   SUBSTRING_INDEX(w.file_path, '/', -1) as filename,
                   CONCAT(COALESCE(w.width, 0), 'x', COALESCE(w.height, 0)) as resolution,
                   w.file_size,
                   w.category_id,
                   c.name as category_name,
                   w.user_id as uploader_id,
                   w.created_at as upload_time,
                   COALESCE(wrs.status, 'pending') as status,
                   wrs.review_time,
                   wrs.review_notes,
                   u.username as uploader_name,
                   u.avatar as uploader_avatar
            FROM wallpapers w
            LEFT JOIN wallpaper_review_status wrs ON w.id = wrs.wallpaper_id
            LEFT JOIN users u ON w.user_id = u.id
            LEFT JOIN categories c ON w.category_id = c.id
            WHERE COALESCE(wrs.status, 'pending') = 'pending'
            ORDER BY w.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $wallpapers = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // 计算分页信息
        $totalPages = ceil($total / $limit);
        $start = ($page - 1) * $limit + 1;
        $end = min($page * $limit, $total);
        
        success([
            'wallpapers' => $wallpapers,
            'pagination' => [
                'total' => (int)$total,
                'current_page' => $page,
                'total_pages' => $totalPages,
                'limit' => $limit,
                'start' => $start,
                'end' => $end
            ]
        ]);
        
    } catch (Exception $e) {
        error('获取待审核壁纸失败: ' . $e->getMessage());
    }
}

/**
 * 获取所有壁纸列表
 */
function getAllWallpapers() {
    global $conn;
    
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 20);
    $status = $_GET['status'] ?? 'all';
    $offset = ($page - 1) * $limit;
    
    try {
        $whereClause = '';
        $types = '';
        $params = [];
        
        if ($status !== 'all') {
            $whereClause = 'WHERE COALESCE(wrs.status, \'pending\') = ?';
            $types .= 's';
            $params[] = $status;
        }
        
        // 获取总数
        $sql = "
            SELECT COUNT(*) as total 
            FROM wallpapers w
            LEFT JOIN wallpaper_review_status wrs ON w.id = wrs.wallpaper_id
            {$whereClause}
        ";
        
        if ($status !== 'all') {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('s', $status);
            $stmt->execute();
            $result = $stmt->get_result();
            $total = $result->fetch_assoc()['total'];
            $stmt->close();
        } else {
            $result = $conn->query($sql);
            $total = $result->fetch_assoc()['total'];
        }
        
        // 获取列表
        $types .= 'ii';
        $params[] = $limit;
        $params[] = $offset;
        
        $sql = "
            SELECT w.id, 
                   w.title,
                   w.description,
                   w.file_path as image_url,
                   w.file_path as thumbnail_url,
                   SUBSTRING_INDEX(w.file_path, '/', -1) as filename,
                   CONCAT(COALESCE(w.width, 0), 'x', COALESCE(w.height, 0)) as resolution,
                   w.file_size,
                   w.category_id,
                   c.name as category_name,
                   w.user_id as uploader_id,
                   w.created_at as upload_time,
                   COALESCE(wrs.status, 'pending') as status,
                   wrs.review_time,
                   wrs.review_notes,
                   u.username as uploader_name,
                   u.avatar as uploader_avatar,
                   reviewer.username as reviewer_name
            FROM wallpapers w
            LEFT JOIN wallpaper_review_status wrs ON w.id = wrs.wallpaper_id
            LEFT JOIN users u ON w.user_id = u.id
            LEFT JOIN users reviewer ON wrs.reviewer_id = reviewer.id
            LEFT JOIN categories c ON w.category_id = c.id
            {$whereClause}
            ORDER BY w.created_at DESC
            LIMIT ? OFFSET ?
        ";
        
        $stmt = $conn->prepare($sql);
        if ($status !== 'all') {
            $stmt->bind_param($types, ...$params);
        } else {
            $stmt->bind_param('ii', $limit, $offset);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $wallpapers = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // 计算分页信息
        $totalPages = ceil($total / $limit);
        $start = ($page - 1) * $limit + 1;
        $end = min($page * $limit, $total);
        
        success([
            'wallpapers' => $wallpapers,
            'pagination' => [
                'total' => (int)$total,
                'current_page' => $page,
                'total_pages' => $totalPages,
                'limit' => $limit,
                'start' => $start,
                'end' => $end
            ]
        ]);
        
    } catch (Exception $e) {
        error('获取壁纸列表失败: ' . $e->getMessage());
    }
}

/**
 * 获取壁纸详情
 */
function getWallpaperDetail() {
    global $conn;
    
    $wallpaper_id = $_GET['id'] ?? '';
    
    if (!$wallpaper_id) {
        error('壁纸ID不能为空');
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT w.id, 
                   w.title,
                   w.description,
                   w.file_path as image_url,
                   w.file_path as thumbnail_url,
                   SUBSTRING_INDEX(w.file_path, '/', -1) as filename,
                   CONCAT(COALESCE(w.width, 0), 'x', COALESCE(w.height, 0)) as resolution,
                   w.file_size,
                   w.category_id,
                   c.name as category_name,
                   w.user_id as uploader_id,
                   w.created_at as upload_time,
                   COALESCE(wrs.status, 'pending') as status,
                   wrs.review_time,
                   wrs.review_notes,
                   u.username as uploader_name,
                   u.avatar as uploader_avatar,
                   reviewer.username as reviewer_name
            FROM wallpapers w
            LEFT JOIN wallpaper_review_status wrs ON w.id = wrs.wallpaper_id
            LEFT JOIN users u ON w.user_id = u.id
            LEFT JOIN users reviewer ON wrs.reviewer_id = reviewer.id
            LEFT JOIN categories c ON w.category_id = c.id
            WHERE w.id = ?
        ");
        $stmt->bind_param('i', $wallpaper_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $wallpaper = $result->fetch_assoc();
        $stmt->close();
        
        if (!$wallpaper) {
            error('壁纸不存在');
        }
        
        success(['wallpaper' => $wallpaper]);
        
    } catch (Exception $e) {
        error('获取壁纸详情失败: ' . $e->getMessage());
    }
}

/**
 * 审核通过壁纸
 */
function approveWallpaper($input = null) {
    global $conn, $admin_id;
    
    $wallpaper_id = $input['wallpaper_id'] ?? $_POST['wallpaper_id'] ?? '';
    $notes = $input['notes'] ?? $_POST['notes'] ?? '';
    
    if (!$wallpaper_id) {
        error('壁纸ID不能为空');
    }
    
    try {
        $conn->autocommit(false);
        
        // 更新或插入审核状态
        $stmt = $conn->prepare("
            INSERT INTO wallpaper_review_status 
            (wallpaper_id, status, reviewer_id, review_time, review_notes)
            VALUES (?, 'approved', ?, NOW(), ?)
            ON DUPLICATE KEY UPDATE
            status = 'approved',
            reviewer_id = VALUES(reviewer_id),
            review_time = VALUES(review_time),
            review_notes = VALUES(review_notes)
        ");
        $stmt->bind_param('iis', $wallpaper_id, $admin_id, $reason);
        $stmt->execute();
        $stmt->close();
        
        // 记录操作日志
        logAdminAction($admin_id, 'approve_wallpaper', "壁纸ID: {$wallpaper_id}, 备注: {$notes}");
        
        $conn->commit();
        $conn->autocommit(true);
        success(null, '审核通过');
        
    } catch (Exception $e) {
        $conn->rollback();
        $conn->autocommit(true);
        error('审核失败: ' . $e->getMessage());
    }
}

/**
 * 审核拒绝壁纸
 */
function rejectWallpaper($input = null) {
    global $conn, $admin_id;
    
    $wallpaper_id = $input['wallpaper_id'] ?? $_POST['wallpaper_id'] ?? '';
    $reason = $input['reason'] ?? $_POST['reason'] ?? '';
    
    if (!$wallpaper_id) {
        error('壁纸ID不能为空');
    }
    
    if (!$reason) {
        error('拒绝原因不能为空');
    }
    
    try {
        $conn->autocommit(false);
        
        // 更新或插入审核状态
        $stmt = $conn->prepare("
            INSERT INTO wallpaper_review_status 
            (wallpaper_id, status, reviewer_id, review_time, review_notes)
            VALUES (?, 'rejected', ?, NOW(), ?)
            ON DUPLICATE KEY UPDATE
            status = 'rejected',
            reviewer_id = VALUES(reviewer_id),
            review_time = VALUES(review_time),
            review_notes = VALUES(review_notes)
        ");
        $stmt->bind_param('iis', $wallpaper_id, $admin_id, $notes);
        $stmt->execute();
        $stmt->close();
        
        // 记录操作日志
        logAdminAction($admin_id, 'reject_wallpaper', "壁纸ID: {$wallpaper_id}, 原因: {$reason}");
        
        $conn->commit();
        $conn->autocommit(true);
        success(null, '审核拒绝');
        
    } catch (Exception $e) {
        $conn->rollback();
        $conn->autocommit(true);
        error('审核失败: ' . $e->getMessage());
    }
}

/**
 * 批量审核通过
 */
function batchApproveWallpapers($input = null) {
    global $conn, $admin_id;
    
    $wallpaper_ids = $input['wallpaper_ids'] ?? $_POST['wallpaper_ids'] ?? [];
    $notes = $input['notes'] ?? $_POST['notes'] ?? '';
    
    if (empty($wallpaper_ids) || !is_array($wallpaper_ids)) {
        error('壁纸ID列表不能为空');
    }
    
    try {
        $conn->autocommit(false);
        
        $success_count = 0;
        foreach ($wallpaper_ids as $wallpaper_id) {
            $stmt = $conn->prepare("
                INSERT INTO wallpaper_review_status 
                (wallpaper_id, status, reviewer_id, review_time, review_notes)
                VALUES (?, 'approved', ?, NOW(), ?)
                ON DUPLICATE KEY UPDATE
                status = 'approved',
                reviewer_id = VALUES(reviewer_id),
                review_time = VALUES(review_time),
                review_notes = VALUES(review_notes)
            ");
            $stmt->bind_param('iis', $wallpaper_id, $admin_id, $notes);
            $stmt->execute();
            $stmt->close();
            $success_count++;
        }
        
        // 记录操作日志
        $ids_str = implode(',', $wallpaper_ids);
        logAdminAction($admin_id, 'batch_approve_wallpapers', "壁纸IDs: {$ids_str}, 数量: {$success_count}");
        
        $conn->commit();
        $conn->autocommit(true);
        success(['count' => $success_count], "批量审核完成，共处理 {$success_count} 个壁纸");
        
    } catch (Exception $e) {
        $conn->rollback();
        $conn->autocommit(true);
        error('批量审核失败: ' . $e->getMessage());
    }
}

/**
 * 批量审核拒绝
 */
function batchRejectWallpapers($input = null) {
    global $conn, $admin_id;
    
    $wallpaper_ids = $input['wallpaper_ids'] ?? $_POST['wallpaper_ids'] ?? [];
    $reason = $input['reason'] ?? $_POST['reason'] ?? '';
    
    if (empty($wallpaper_ids) || !is_array($wallpaper_ids)) {
        error('请选择要拒绝的壁纸');
    }
    
    try {
        $conn->autocommit(false);
        
        $success_count = 0;
        foreach ($wallpaper_ids as $wallpaper_id) {
            $stmt = $conn->prepare("
                INSERT INTO wallpaper_review_status 
                (wallpaper_id, status, reviewer_id, review_time, review_notes)
                VALUES (?, 'rejected', ?, NOW(), ?)
                ON DUPLICATE KEY UPDATE
                status = 'rejected',
                reviewer_id = VALUES(reviewer_id),
                review_time = VALUES(review_time),
                review_notes = VALUES(review_notes)
            ");
            $stmt->bind_param('iis', $wallpaper_id, $admin_id, $reason);
            $stmt->execute();
            $stmt->close();
            $success_count++;
        }
        
        // 记录操作日志
        $ids_str = implode(',', $wallpaper_ids);
        logAdminAction($admin_id, 'batch_reject_wallpapers', "壁纸IDs: {$ids_str}, 数量: {$success_count}");
        
        $conn->commit();
        $conn->autocommit(true);
        success(['count' => $success_count], "批量审核拒绝完成，共处理 {$success_count} 个壁纸");
        
    } catch (Exception $e) {
        $conn->rollback();
        $conn->autocommit(true);
        error('批量审核失败: ' . $e->getMessage());
    }
}

/**
 * 删除壁纸
 */
function deleteWallpaper($input = null) {
    global $conn, $admin_id;
    
    $wallpaper_id = $input['wallpaper_id'] ?? $_POST['wallpaper_id'] ?? '';
    
    if (!$wallpaper_id) {
        error('壁纸ID不能为空');
    }
    
    try {
        $conn->autocommit(false);
        
        // 获取壁纸信息
        $stmt = $conn->prepare("SELECT file_path FROM wallpapers WHERE id = ?");
        $stmt->bind_param('i', $wallpaper_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $wallpaper = $result->fetch_assoc();
        $stmt->close();
        
        if (!$wallpaper) {
            error('壁纸不存在');
        }
        
        // 删除文件
        $file_path = $_SERVER['DOCUMENT_ROOT'] . $wallpaper['file_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        // 删除数据库记录
        $stmt = $conn->prepare("DELETE FROM wallpapers WHERE id = ?");
        $stmt->bind_param('i', $wallpaper_id);
        $stmt->execute();
        $stmt->close();
        
        // 删除审核状态记录
        $stmt = $conn->prepare("DELETE FROM wallpaper_review_status WHERE wallpaper_id = ?");
        $stmt->bind_param('i', $wallpaper_id);
        $stmt->execute();
        $stmt->close();
        
        // 记录操作日志
        logAdminAction($admin_id, 'delete_wallpaper', "删除壁纸ID: {$wallpaper_id}");
        
        $conn->commit();
        $conn->autocommit(true);
        success(['message' => '壁纸删除成功']);
        
    } catch (Exception $e) {
        $conn->rollback();
        $conn->autocommit(true);
        error('删除壁纸失败: ' . $e->getMessage());
    }
}

/**
 * 获取待审核壁纸数量
 */
function getPendingCount() {
    global $conn;
    
    try {
        // 查询待审核壁纸数量（状态为pending或没有审核记录的壁纸）
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count
            FROM wallpapers w
            LEFT JOIN wallpaper_review_status wrs ON w.id = wrs.wallpaper_id
            WHERE COALESCE(wrs.status, 'pending') = 'pending'
        ");
        
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        success(['count' => (int)$row['count']]);
        
    } catch (Exception $e) {
        error('获取待审核数量失败: ' . $e->getMessage());
    }
}
?>
<?php
/**
 * 管理后台仪表盘数据接口
 * 位置: api/admin_dashboard.php
 * 依赖: admin_auth.php, utils.php, config/database.php
 */

require_once 'admin_auth.php';
require_once 'utils.php';
require_once __DIR__ . '/../config/database.php';

// 设置响应头
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 验证管理员权限
$admin_id = checkAdminAuth();

// 处理请求
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'stats':
                getDashboardStats();
                break;
            case 'recent_activities':
                getRecentActivities();
                break;
            case 'admin_logs':
                getAdminLogsData();
                break;
            case 'system_status':
                getSystemStatus();
                break;
            default:
                sendResponse(400, '无效的操作');
        }
        break;
    default:
        sendResponse(405, '不支持的请求方法');
}

/**
 * 获取仪表盘统计数据
 */
function getDashboardStats() {
    $conn = null;
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PWD, DB_NAME);
        $conn->set_charset("utf8mb4");
        
        if ($conn->connect_error) {
            sendResponse(500, '数据库连接失败');
            return;
        }
        
        // 总壁纸数
        $stmt = $conn->query("SELECT COUNT(*) as total FROM wallpapers");
        $total_wallpapers = $stmt ? $stmt->fetch_assoc()['total'] : 0;
        
        // 待审核壁纸数（使用审核状态表）
        $stmt = $conn->query("
            SELECT COUNT(*) as pending 
            FROM wallpapers w
            LEFT JOIN wallpaper_review_status wrs ON w.id = wrs.wallpaper_id
            WHERE COALESCE(wrs.status, 'pending') = 'pending'
        ");
        $pending_wallpapers = $stmt ? $stmt->fetch_assoc()['pending'] : 0;
        
        // 注册用户数
        $stmt = $conn->query("SELECT COUNT(*) as total FROM users");
        $total_users = $stmt ? $stmt->fetch_assoc()['total'] : 0;
        
        // 今日浏览量（检查是否有浏览记录表）
        $today_views = 0;
        $check_views = $conn->query("SHOW TABLES LIKE 'wallpaper_views'");
        if ($check_views && $check_views->num_rows > 0) {
            $stmt = $conn->query("
                SELECT COUNT(*) as views 
                FROM wallpaper_views 
                WHERE DATE(view_time) = CURDATE()
            ");
            $today_views = $stmt ? $stmt->fetch_assoc()['views'] : 0;
        }
        
        // 今日新增用户
        $stmt = $conn->query("
            SELECT COUNT(*) as new_users 
            FROM users 
            WHERE DATE(created_at) = CURDATE()
        ");
        $today_new_users = $stmt ? $stmt->fetch_assoc()['new_users'] : 0;
        
        // 今日新增壁纸
        $stmt = $conn->query("
            SELECT COUNT(*) as new_wallpapers 
            FROM wallpapers 
            WHERE DATE(created_at) = CURDATE()
        ");
        $today_new_wallpapers = $stmt ? $stmt->fetch_assoc()['new_wallpapers'] : 0;
        
        $stats = [
            'total_wallpapers' => (int)$total_wallpapers,
            'pending_wallpapers' => (int)$pending_wallpapers,
            'total_users' => (int)$total_users,
            'today_views' => (int)$today_views,
            'today_new_users' => (int)$today_new_users,
            'today_new_wallpapers' => (int)$today_new_wallpapers
        ];
        
        sendResponse(200, '获取统计数据成功', $stats);
        
    } catch (Exception $e) {
        sendDebugLog('获取仪表盘统计数据异常:', ['error' => $e->getMessage()], 'admin_dashboard_error');
        sendResponse(500, '获取统计数据失败: ' . $e->getMessage());
    } finally {
        if ($conn) {
            $conn->close();
        }
    }
}

/**
 * 获取最近活动
 */
function getRecentActivities() {
    $conn = null;
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PWD, DB_NAME);
        $conn->set_charset("utf8mb4");
        
        if ($conn->connect_error) {
            sendResponse(500, '数据库连接失败');
            return;
        }
        
        $activities = [];
        
        // 最新上传的壁纸（最近10个）
        $stmt = $conn->query("
            SELECT w.title, w.created_at, u.username 
            FROM wallpapers w 
            LEFT JOIN users u ON w.user_id = u.id 
            ORDER BY w.created_at DESC 
            LIMIT 10
        ");
        $recent_uploads = $stmt ? $stmt->fetch_all(MYSQLI_ASSOC) : [];
        
        // 最新注册用户（最近10个）
        $stmt = $conn->query("
            SELECT username, created_at 
            FROM users 
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        $recent_users = $stmt ? $stmt->fetch_all(MYSQLI_ASSOC) : [];
        
        // 最近管理员操作（使用新的操作日志表）
        $recent_admin_actions = [];
        $check_logs = $conn->query("SHOW TABLES LIKE 'admin_operation_logs'");
        if ($check_logs && $check_logs->num_rows > 0) {
            $stmt = $conn->query("
                SELECT 
                    aol.operation_type as action, 
                    CONCAT(aol.target_type, ' ID: ', aol.target_id) as details,
                    aol.created_at, 
                    u.username as admin_name
                FROM admin_operation_logs aol
                LEFT JOIN users u ON aol.admin_id = u.id
                ORDER BY aol.created_at DESC
                LIMIT 10
            ");
            $recent_admin_actions = $stmt ? $stmt->fetch_all(MYSQLI_ASSOC) : [];
        }
        
        $activities = [
            'recent_uploads' => $recent_uploads,
            'recent_users' => $recent_users,
            'recent_admin_actions' => $recent_admin_actions
        ];
        
        sendResponse(200, '获取最近活动成功', $activities);
        
    } catch (Exception $e) {
        sendDebugLog('获取最近活动异常:', ['error' => $e->getMessage()], 'admin_dashboard_error');
        sendResponse(500, '获取最近活动失败: ' . $e->getMessage());
    } finally {
        if ($conn) {
            $conn->close();
        }
    }
}

/**
 * 获取管理员操作日志数据
 */
function getAdminLogsData() {
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 20);
    $offset = ($page - 1) * $limit;
    
    $logs = getAdminLogs($limit, $offset);
    
    // 获取总数
    $conn = null;
    $total = 0;
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PWD, DB_NAME);
        $conn->set_charset("utf8mb4");
        
        if (!$conn->connect_error) {
            $check_logs = $conn->query("SHOW TABLES LIKE 'admin_operation_logs'");
        if ($check_logs && $check_logs->num_rows > 0) {
            $stmt = $conn->query("SELECT COUNT(*) as total FROM admin_operation_logs");
            $total = $stmt ? $stmt->fetch_assoc()['total'] : 0;
        }
        }
    } catch (Exception $e) {
        // 忽略错误，使用默认值
    } finally {
        if ($conn) {
            $conn->close();
        }
    }
    
    sendResponse(200, '获取管理员日志成功', [
        'list' => $logs,
        'total' => (int)$total,
        'page' => $page,
        'limit' => $limit
    ]);
}

/**
 * 获取系统状态
 */
function getSystemStatus() {
    try {
        $status = [];
        
        // 检查服务器状态
        $status['server_status'] = '正常';
        
        // 检查数据库连接状态
        $conn = null;
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PWD, DB_NAME);
            $conn->set_charset("utf8mb4");
            
            if ($conn->connect_error) {
                $status['database_status'] = '连接失败';
            } else {
                $status['database_status'] = '正常';
            }
        } catch (Exception $e) {
            $status['database_status'] = '异常';
        } finally {
            if ($conn) {
                $conn->close();
            }
        }
        
        // 检查存储空间（检查uploads目录）
        $uploads_dir = __DIR__ . '/../uploads';
        if (is_dir($uploads_dir)) {
            $total_space = disk_total_space($uploads_dir);
            $free_space = disk_free_space($uploads_dir);
            $used_space = $total_space - $free_space;
            $usage_percent = round(($used_space / $total_space) * 100, 1);
            $status['storage_status'] = $usage_percent . '% 已使用';
        } else {
            $status['storage_status'] = '无法检测';
        }
        
        // CDN状态（这里可以根据实际CDN服务进行检查）
        $status['cdn_status'] = '正常';
        
        // 总体状态判断
        $overall_status = '正常运行';
        if ($status['database_status'] !== '正常' || $status['cdn_status'] !== '正常') {
            $overall_status = '部分异常';
        }
        if ($status['server_status'] !== '正常') {
            $overall_status = '服务异常';
        }
        $status['overall_status'] = $overall_status;
        
        sendResponse(200, '获取系统状态成功', $status);
        
    } catch (Exception $e) {
        sendDebugLog('获取系统状态异常:', ['error' => $e->getMessage()], 'admin_dashboard_error');
        sendResponse(500, '获取系统状态失败: ' . $e->getMessage());
    }
}
?>
<?php
/**
 * 分类列表API
 * 文件: api/admin/categories-list.php
 * 功能: 获取分类列表，支持分页和筛选
 * 依赖: ../admin_auth.php, ../response_helper.php, ../../config/database.php
 */

require_once '../admin_auth.php';
require_once '../response_helper.php';
require_once '../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    error('不支持的请求方法');
}

$admin_id = checkAdminAuth();

$page = (int)($_GET['page'] ?? 1);
$limit = (int)($_GET['limit'] ?? 20);
$status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$offset = ($page - 1) * $limit;

try {
    $whereClause = [];
    $params = [];
    
    // 状态筛选
    if ($status !== 'all') {
        $whereClause[] = 'is_active = ?';
        $params[] = ($status === 'active') ? 1 : 0;
    }
    
    // 搜索条件
    if ($search) {
        $whereClause[] = '(name LIKE ? OR description LIKE ?)';
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }
    
    $whereSQL = empty($whereClause) ? '' : 'WHERE ' . implode(' AND ', $whereClause);
    
    // 获取总数
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM categories {$whereSQL}");
    $stmt->execute($params);
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // 获取分类列表
    $listParams = array_merge($params, [$limit, $offset]);
    $stmt = $pdo->prepare("
        SELECT id, name, description, sort_order, is_active, created_at, updated_at,
               (SELECT COUNT(*) FROM wallpapers WHERE category_id = categories.id) as wallpaper_count
        FROM categories 
        {$whereSQL}
        ORDER BY sort_order ASC, created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute($listParams);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 格式化数据
    foreach ($categories as &$category) {
        $category['is_active'] = (bool)$category['is_active'];
        $category['wallpaper_count'] = (int)$category['wallpaper_count'];
    }
    
    $totalPages = ceil($total / $limit);
    
    success([
        'categories' => $categories,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total' => (int)$total,
            'limit' => $limit,
            'start' => $offset + 1,
            'end' => min($offset + $limit, $total)
        ]
    ]);
    
} catch (PDOException $e) {
    error('获取分类列表失败: ' . $e->getMessage());
}
?>
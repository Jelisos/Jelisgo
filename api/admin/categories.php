<?php
/**
 * 分类管理API
 * 文件: api/admin/categories.php
 * 功能: 提供分类的增删改查功能
 * 依赖: ../admin_auth.php, ../response_helper.php, ../../config/database.php
 */

require_once '../admin_auth.php';
require_once '../response_helper.php';
require_once '../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$admin_id = checkAdminAuth();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $action = $_GET['action'] ?? 'list';
        
        switch ($action) {
            case 'list':
                getCategoryList();
                break;
            case 'detail':
                getCategoryDetail();
                break;
            default:
                error('无效的操作');
        }
        break;
        
    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? $_POST['action'] ?? '';
        
        switch ($action) {
            case 'create':
                createCategory($input);
                break;
            case 'update':
                updateCategory($input);
                break;
            case 'delete':
                deleteCategory($input);
                break;
            case 'toggle_status':
                toggleCategoryStatus($input);
                break;
            default:
                error('无效的操作');
        }
        break;
        
    default:
        error('不支持的请求方法');
}

/**
 * 获取分类列表
 */
function getCategoryList() {
    global $pdo;
    
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
}

/**
 * 获取分类详情
 */
function getCategoryDetail() {
    global $pdo;
    
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        error('分类ID不能为空');
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, name, description, sort_order, is_active, created_at, updated_at,
                   (SELECT COUNT(*) FROM wallpapers WHERE category_id = categories.id) as wallpaper_count
            FROM categories 
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$category) {
            error('分类不存在', 404);
        }
        
        $category['is_active'] = (bool)$category['is_active'];
        $category['wallpaper_count'] = (int)$category['wallpaper_count'];
        
        success(['category' => $category]);
        
    } catch (PDOException $e) {
        error('获取分类详情失败: ' . $e->getMessage());
    }
}

/**
 * 创建分类
 */
function createCategory($data) {
    global $pdo;
    
    $name = trim($data['name'] ?? '');
    $description = trim($data['description'] ?? '');
    $sort_order = (int)($data['sort_order'] ?? 0);
    $is_active = (bool)($data['is_active'] ?? true);
    
    if (!$name) {
        error('分类名称不能为空');
    }
    
    try {
        // 检查名称是否已存在
        $stmt = $pdo->prepare('SELECT id FROM categories WHERE name = ?');
        $stmt->execute([$name]);
        if ($stmt->fetch()) {
            error('分类名称已存在');
        }
        
        // 创建分类
        $stmt = $pdo->prepare("
            INSERT INTO categories (name, description, sort_order, is_active) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$name, $description, $sort_order, $is_active ? 1 : 0]);
        
        $categoryId = $pdo->lastInsertId();
        
        success(['id' => $categoryId], '分类创建成功');
        
    } catch (PDOException $e) {
        error('创建分类失败: ' . $e->getMessage());
    }
}

/**
 * 更新分类
 */
function updateCategory($data) {
    global $pdo;
    
    $id = (int)($data['id'] ?? 0);
    $name = trim($data['name'] ?? '');
    $description = trim($data['description'] ?? '');
    $sort_order = (int)($data['sort_order'] ?? 0);
    $is_active = (bool)($data['is_active'] ?? true);
    
    if (!$id) {
        error('分类ID不能为空');
    }
    
    if (!$name) {
        error('分类名称不能为空');
    }
    
    try {
        // 检查分类是否存在
        $stmt = $pdo->prepare('SELECT id FROM categories WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            error('分类不存在', 404);
        }
        
        // 检查名称是否已被其他分类使用
        $stmt = $pdo->prepare('SELECT id FROM categories WHERE name = ? AND id != ?');
        $stmt->execute([$name, $id]);
        if ($stmt->fetch()) {
            error('分类名称已存在');
        }
        
        // 更新分类
        $stmt = $pdo->prepare("
            UPDATE categories 
            SET name = ?, description = ?, sort_order = ?, is_active = ?
            WHERE id = ?
        ");
        $stmt->execute([$name, $description, $sort_order, $is_active ? 1 : 0, $id]);
        
        success(null, '分类更新成功');
        
    } catch (PDOException $e) {
        error('更新分类失败: ' . $e->getMessage());
    }
}

/**
 * 删除分类
 */
function deleteCategory($data) {
    global $pdo;
    
    $id = (int)($data['category_id'] ?? $data['id'] ?? 0);
    if (!$id) {
        error('分类ID不能为空');
    }
    
    try {
        // 检查分类是否存在
        $stmt = $pdo->prepare('SELECT id FROM categories WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            error('分类不存在', 404);
        }
        
        // 检查是否有壁纸使用此分类
        $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM wallpapers WHERE category_id = ?');
        $stmt->execute([$id]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($count > 0) {
            error("无法删除分类，还有 {$count} 个壁纸使用此分类");
        }
        
        // 删除分类
        $stmt = $pdo->prepare('DELETE FROM categories WHERE id = ?');
        $stmt->execute([$id]);
        
        success(null, '分类删除成功');
        
    } catch (PDOException $e) {
        error('删除分类失败: ' . $e->getMessage());
    }
}

/**
 * 切换分类状态
 */
function toggleCategoryStatus($data) {
    global $pdo;
    
    $id = (int)($data['category_id'] ?? $data['id'] ?? 0);
    $is_active = (bool)($data['is_active'] ?? false);
    
    if (!$id) {
        error('分类ID不能为空');
    }
    
    try {
        // 检查分类是否存在
        $stmt = $pdo->prepare('SELECT id FROM categories WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            error('分类不存在', 404);
        }
        
        // 更新状态
        $stmt = $pdo->prepare('UPDATE categories SET is_active = ? WHERE id = ?');
        $stmt->execute([$is_active ? 1 : 0, $id]);
        
        $status = $is_active ? '启用' : '禁用';
        success(null, "分类{$status}成功");
        
    } catch (PDOException $e) {
        error('切换分类状态失败: ' . $e->getMessage());
    }
}
?>
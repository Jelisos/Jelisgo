<?php
/**
 * 分类详情API
 * 文件: api/admin/category-detail.php
 * 功能: 获取单个分类的详细信息
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
?>
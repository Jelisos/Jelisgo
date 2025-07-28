<?php
/**
 * 删除分类API
 * 文件: api/admin/delete-category.php
 * 功能: 删除指定分类
 * 依赖: ../admin_auth.php, ../response_helper.php, ../../config/database.php
 */

require_once '../admin_auth.php';
require_once '../response_helper.php';
require_once '../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error('不支持的请求方法');
}

$admin_id = checkAdminAuth();

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    error('无效的JSON数据');
}

$id = (int)($input['category_id'] ?? $input['id'] ?? 0);
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
?>
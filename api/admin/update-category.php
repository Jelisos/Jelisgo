<?php
/**
 * 更新分类API
 * 文件: api/admin/update-category.php
 * 功能: 更新现有分类信息
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

$id = (int)($input['id'] ?? 0);
$name = trim($input['name'] ?? '');
$description = trim($input['description'] ?? '');
$sort_order = (int)($input['sort_order'] ?? 0);
$is_active = (bool)($input['is_active'] ?? true);

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
?>
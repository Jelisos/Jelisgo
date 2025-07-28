<?php
/**
 * 创建分类API
 * 文件: api/admin/create-category.php
 * 功能: 创建新的分类
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

$name = trim($input['name'] ?? '');
$description = trim($input['description'] ?? '');
$sort_order = (int)($input['sort_order'] ?? 0);
$is_active = (bool)($input['is_active'] ?? true);

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
?>
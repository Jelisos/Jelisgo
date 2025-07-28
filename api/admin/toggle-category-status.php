<?php
/**
 * 切换分类状态API
 * 文件: api/admin/toggle-category-status.php
 * 功能: 启用或禁用分类
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
$is_active = (bool)($input['is_active'] ?? false);

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
?>
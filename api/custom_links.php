<?php
/**
 * 自定义链接管理API
 * 文件位置：/api/custom_links.php
 * 
 * 功能：处理壁纸详情页面的自定义链接CRUD操作
 * 权限：仅管理员可操作
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth_unified.php';

class CustomLinksAPI {
    private $pdo;
    
    public function __construct() {
        try {
            $this->pdo = new PDO(
                "mysql:host=localhost;dbname=wallpaper_db;charset=utf8mb4",
                'root',
                '',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } catch (PDOException $e) {
            $this->sendError(500, '数据库连接失败');
        }
    }
    
    /**
     * 验证管理员权限
     */
    private function validateAdmin() {
        $authResult = validateAdminAuth();
        if (!$authResult['success']) {
            $this->sendError(401, $authResult['message']);
        }
        return $authResult['user_id'];
    }
    
    /**
     * 获取指定壁纸的自定义链接列表
     */
    public function getLinks($wallpaperId) {
        try {
            // 获取通用链接（wallpaper_id=0的链接，对所有壁纸可见）
            $sql = "SELECT * FROM wallpaper_custom_links WHERE wallpaper_id = 0 AND is_active = 1 ORDER BY sort_order ASC, priority ASC, created_at ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $universalLinks = $stmt->fetchAll();
            
            // 获取特定壁纸的链接（排除通用链接）
            $sql = "SELECT * FROM wallpaper_custom_links WHERE wallpaper_id = ? AND is_active = 1 ORDER BY sort_order ASC, priority ASC, created_at ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$wallpaperId]);
            $specificLinks = $stmt->fetchAll();
            
            // 合并链接，通用链接在前
            $links = array_merge($universalLinks, $specificLinks);
            
            $this->sendSuccess($links);
        } catch (PDOException $e) {
            $this->sendError(500, '获取链接失败');
        }
    }
    
    /**
     * 获取链接（旧版本保留）
     */
    public function getLinksOld($wallpaperId) {
        try {
            $sql = "SELECT * FROM wallpaper_custom_links WHERE wallpaper_id = ? AND is_active = 1 ORDER BY CASE WHEN priority = 0 THEN 0 ELSE 1 END, sort_order DESC, priority DESC, created_at ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$wallpaperId]);
            $links = $stmt->fetchAll();
            
            $this->sendSuccess($links);
        } catch (PDOException $e) {
            $this->sendError(500, '获取链接失败');
        }
    }
    
    /**
     * 添加新的自定义链接
     */
    public function addLink($data) {
        $this->validateAdmin();
        
        // 验证必填字段
        if (!isset($data['wallpaper_id']) || empty($data['title']) || empty($data['url'])) {
            $this->sendError(400, '缺少必填字段');
        }
        
        // 验证URL格式
        if (!filter_var($data['url'], FILTER_VALIDATE_URL)) {
            $this->sendError(400, 'URL格式无效');
        }
        
        // 验证优先级范围
        $priority = intval($data['priority'] ?? 1);
        if ($priority < 0 || $priority > 5) {
            $priority = 1;
        }
        
        // 设置颜色类
        $colorClass = 'priority-' . $priority;
        
        try {
            $sql = "INSERT INTO wallpaper_custom_links (wallpaper_id, title, url, priority, color_class, description, is_active, target_type, icon_class, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $data['wallpaper_id'],
                $data['title'],
                $data['url'],
                $priority,
                $colorClass,
                $data['description'] ?? '',
                $data['is_active'] ?? 1,
                $data['target_type'] ?? '_blank',
                $data['icon_class'] ?? null,
                $data['sort_order'] ?? 0
            ]);
            
            $linkId = $this->pdo->lastInsertId();
            
            // 返回新创建的链接信息
            $sql = "SELECT * FROM wallpaper_custom_links WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$linkId]);
            $newLink = $stmt->fetch();
            
            $this->sendSuccess($newLink, '链接添加成功');
        } catch (PDOException $e) {
            error_log('Custom Links Add Error: ' . $e->getMessage());
            $this->sendError(500, '添加链接失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 更新自定义链接
     */
    public function updateLink($linkId, $data) {
        $this->validateAdmin();
        
        // 验证链接是否存在
        $sql = "SELECT * FROM wallpaper_custom_links WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$linkId]);
        $existingLink = $stmt->fetch();
        
        if (!$existingLink) {
            $this->sendError(404, '链接不存在');
        }
        
        // 验证URL格式（如果提供）
        if (!empty($data['url']) && !filter_var($data['url'], FILTER_VALIDATE_URL)) {
            $this->sendError(400, 'URL格式无效');
        }
        
        // 构建更新字段
        $updateFields = [];
        $updateValues = [];
        
        if (!empty($data['title'])) {
            $updateFields[] = 'title = ?';
            $updateValues[] = $data['title'];
        }
        
        if (!empty($data['url'])) {
            $updateFields[] = 'url = ?';
            $updateValues[] = $data['url'];
        }
        
        if (isset($data['priority'])) {
            $priority = intval($data['priority']);
            if ($priority >= 0 && $priority <= 5) {
                $updateFields[] = 'priority = ?';
                $updateValues[] = $priority;
                $updateFields[] = 'color_class = ?';
                $updateValues[] = 'priority-' . $priority;
            }
        }
        
        if (isset($data['description'])) {
            $updateFields[] = 'description = ?';
            $updateValues[] = $data['description'];
        }
        
        if (isset($data['sort_order'])) {
            $sortOrder = intval($data['sort_order']);
            if ($sortOrder >= 0 && $sortOrder <= 5) {
                $updateFields[] = 'sort_order = ?';
                $updateValues[] = $sortOrder;
            }
        }
        
        // 处理wallpaper_id更新（支持通用链接）
        if (isset($data['wallpaper_id'])) {
            $wallpaperId = intval($data['wallpaper_id']);
            $updateFields[] = 'wallpaper_id = ?';
            $updateValues[] = $wallpaperId;
        }

        if (empty($updateFields)) {
            $this->sendError(400, '没有要更新的字段');
        }
        
        try {
            $sql = "UPDATE wallpaper_custom_links SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $updateValues[] = $linkId;
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($updateValues);
            
            // 返回更新后的链接信息
            $sql = "SELECT * FROM wallpaper_custom_links WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$linkId]);
            $updatedLink = $stmt->fetch();
            
            $this->sendSuccess($updatedLink, '链接更新成功');
        } catch (PDOException $e) {
            $this->sendError(500, '更新链接失败');
        }
    }
    
    /**
     * 删除自定义链接
     */
    public function deleteLink($linkId) {
        $this->validateAdmin();
        
        try {
            $sql = "DELETE FROM wallpaper_custom_links WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$linkId]);
            
            if ($stmt->rowCount() > 0) {
                $this->sendSuccess(null, '链接删除成功');
            } else {
                $this->sendError(404, '链接不存在');
            }
        } catch (PDOException $e) {
            $this->sendError(500, '删除链接失败');
        }
    }
    
    /**
     * 记录链接点击统计
     */
    public function recordClick($linkId) {
        try {
            $linkId = intval($linkId);
            if ($linkId <= 0) {
                $this->sendError(400, '无效的链接ID');
            }
            
            // 更新点击次数
            $sql = "UPDATE wallpaper_custom_links SET click_count = click_count + 1 WHERE id = ? AND is_active = 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$linkId]);
            
            if ($stmt->rowCount() > 0) {
                $this->sendSuccess(null, '点击统计成功');
            } else {
                $this->sendError(404, '链接不存在或已禁用');
            }
        } catch (PDOException $e) {
            $this->sendError(500, '统计点击失败');
        }
    }
    
    /**
     * 发送成功响应
     */
    private function sendSuccess($data = null, $message = '操作成功') {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    /**
     * 发送错误响应
     */
    private function sendError($code, $message) {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'message' => $message
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
}

// 处理请求
try {
    $api = new CustomLinksAPI();
    $method = $_SERVER['REQUEST_METHOD'];
    
    // 优先使用PATH_INFO，如果不存在则使用查询参数
    $path = $_SERVER['PATH_INFO'] ?? '';
    $pathParts = array_filter(explode('/', $path));
    
    switch ($method) {
        case 'GET':
            // 支持两种方式：
            // 1. GET /api/custom_links.php/wallpaper/{wallpaper_id}
            // 2. GET /api/custom_links.php?action=get&wallpaper_id={wallpaper_id}
            
            $wallpaperId = 0;
            
            // 方式1：PATH_INFO
            if (count($pathParts) >= 2 && $pathParts[1] === 'wallpaper') {
                $wallpaperId = intval($pathParts[2] ?? 0);
            }
            // 方式2：查询参数
            elseif (isset($_GET['action']) && $_GET['action'] === 'get' && isset($_GET['wallpaper_id'])) {
                $wallpaperId = intval($_GET['wallpaper_id']);
            }
            
            if ($wallpaperId > 0) {
                $api->getLinks($wallpaperId);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => '无效的壁纸ID或请求格式'], JSON_UNESCAPED_UNICODE);
            }
            break;
            
        case 'POST':
            // POST /api/custom_links.php
            $input = json_decode(file_get_contents('php://input'), true);
            if ($input) {
                // 检查是否是点击统计请求
                if (isset($input['action']) && $input['action'] === 'click') {
                    $api->recordClick($input['link_id']);
                } else {
                    $api->addLink($input);
                }
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => '无效的JSON数据'], JSON_UNESCAPED_UNICODE);
            }
            break;
            
        case 'PUT':
            // PUT /api/custom_links.php/{link_id}
            if (count($pathParts) >= 1) {
                $linkId = intval($pathParts[1] ?? 0);
                if ($linkId > 0) {
                    $input = json_decode(file_get_contents('php://input'), true);
                    if ($input) {
                        $api->updateLink($linkId, $input);
                    } else {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => '无效的JSON数据'], JSON_UNESCAPED_UNICODE);
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => '无效的链接ID'], JSON_UNESCAPED_UNICODE);
                }
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => '缺少链接ID'], JSON_UNESCAPED_UNICODE);
            }
            break;
            
        case 'DELETE':
            // DELETE /api/custom_links.php/{link_id}
            if (count($pathParts) >= 1) {
                $linkId = intval($pathParts[1] ?? 0);
                if ($linkId > 0) {
                    $api->deleteLink($linkId);
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => '无效的链接ID'], JSON_UNESCAPED_UNICODE);
                }
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => '缺少链接ID'], JSON_UNESCAPED_UNICODE);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => '不支持的请求方法'], JSON_UNESCAPED_UNICODE);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '服务器内部错误'
    ], JSON_UNESCAPED_UNICODE);
}
?>
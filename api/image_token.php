<?php
/**
 * 文件: api/image_token.php
 * 描述: 图片Token管理API接口
 * 功能: 生成、获取、批量处理图片访问Token
 * 创建时间: 2025-01-27
 * 维护: AI助手
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 引入数据库配置
require_once __DIR__ . '/../config/database.php';
// 引入工具函数
require_once __DIR__ . '/utils.php';

/**
 * 生成图片Token
 * @param string $wallpaperId 壁纸ID
 * @param string $imagePath 图片路径
 * @param string $pathType 路径类型 (original|preview)
 * @return string 生成的Token
 */
function generateImageToken($wallpaperId, $imagePath, $pathType) {
    $salt = 'jelisgo_image_token_2025';
    $timestamp = time();
    $randomBytes = bin2hex(random_bytes(16));
    
    $data = $wallpaperId . '|' . $imagePath . '|' . $pathType . '|' . $timestamp . '|' . $randomBytes;
    return hash('sha256', $data . $salt);
}

/**
 * 从路径中提取壁纸ID
 * @param string $imagePath 图片路径
 * @return string 壁纸ID
 */
function extractWallpaperId($imagePath) {
    // 匹配路径中的数字目录 (如 /001/, /002/)
    if (preg_match('/\/(\d{3})\//i', $imagePath, $matches)) {
        return $matches[1];
    }
    
    // 如果没有匹配到，尝试从文件名提取
    $filename = basename($imagePath, '.' . pathinfo($imagePath, PATHINFO_EXTENSION));
    if (preg_match('/^(\d{3})/', $filename, $matches)) {
        return $matches[1];
    }
    
    // 默认返回unknown
    return 'unknown';
}

/**
 * 判断路径类型
 * @param string $imagePath 图片路径
 * @return string 路径类型 (original|preview)
 */
function determinePathType($imagePath) {
    return strpos($imagePath, '/preview/') !== false ? 'preview' : 'original';
}

/**
 * 自动发现实际图片文件名
 * @param string $wallpaperId 壁纸ID
 * @param string $pathType 路径类型
 * @return string|null 实际的图片路径，找不到时返回null
 */
function discoverActualImagePath($wallpaperId, $pathType) {
    $baseDir = __DIR__ . '/../';
    
    // 构建目录路径
    if ($pathType === 'preview') {
        $directory = $baseDir . "static/preview/{$wallpaperId}/";
        $pathPrefix = "static/preview/{$wallpaperId}/";
    } else {
        $directory = $baseDir . "static/wallpapers/{$wallpaperId}/";
        $pathPrefix = "static/wallpapers/{$wallpaperId}/";
    }
    
    // 检查目录是否存在
    if (!is_dir($directory)) {
        return null;
    }
    
    // 获取目录中的第一个图片文件
    $files = scandir($directory);
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (in_array($extension, $imageExtensions)) {
            // 验证文件确实存在
            $fullPath = $directory . $file;
            if (file_exists($fullPath)) {
                return $pathPrefix . $file;
            }
        }
    }
    
    // 如果没有找到实际文件，返回null而不是默认路径
    return null;
}

/**
 * 保存Token到数据库
 * @param PDO $pdo 数据库连接
 * @param string $wallpaperId 壁纸ID
 * @param string $token Token
 * @param string $imagePath 图片路径
 * @param string $pathType 路径类型
 * @return bool 是否成功
 */
function saveTokenToDatabase($pdo, $wallpaperId, $token, $imagePath, $pathType) {
    try {
        // 使用REPLACE INTO实现覆盖旧Token的功能
        $sql = "REPLACE INTO image_tokens (wallpaper_id, token, image_path, path_type) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$wallpaperId, $token, $imagePath, $pathType]);
    } catch (PDOException $e) {
        error_log('保存Token失败: ' . $e->getMessage());
        return false;
    }
}

/**
 * 从数据库获取Token
 * @param PDO $pdo 数据库连接
 * @param string $wallpaperId 壁纸ID
 * @param string $pathType 路径类型
 * @return array|null Token信息
 */
function getTokenFromDatabase($pdo, $wallpaperId, $pathType) {
    try {
        $sql = "SELECT token, image_path, created_at FROM image_tokens WHERE wallpaper_id = ? AND path_type = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$wallpaperId, $pathType]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('获取Token失败: ' . $e->getMessage());
        return null;
    }
}

/**
 * 根据Token获取图片路径
 * @param PDO $pdo 数据库连接
 * @param string $token Token
 * @return array|null 图片信息
 */
function getImagePathByToken($pdo, $token) {
    try {
        $sql = "SELECT wallpaper_id, image_path, path_type FROM image_tokens WHERE token = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('根据Token获取路径失败: ' . $e->getMessage());
        return null;
    }
}

/**
 * 生成或获取Token
 * @param PDO $pdo 数据库连接
 * @param string $wallpaperId 壁纸ID
 * @param string $imagePath 图片路径
 * @param string $pathType 路径类型
 * @return array Token信息
 */
function getOrCreateToken($pdo, $wallpaperId, $imagePath, $pathType) {
    // 先尝试从数据库获取现有Token
    $existingToken = getTokenFromDatabase($pdo, $wallpaperId, $pathType);
    
    if ($existingToken) {
        // 如果传入的image_path与数据库中的不同，需要更新数据库
        if ($existingToken['image_path'] !== $imagePath) {
            // 更新数据库中的image_path
            try {
                $sql = "UPDATE image_tokens SET image_path = ? WHERE wallpaper_id = ? AND path_type = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$imagePath, $wallpaperId, $pathType]);
            } catch (PDOException $e) {
                error_log('更新Token路径失败: ' . $e->getMessage());
            }
        }
        
        return [
            'token' => $existingToken['token'],
            'wallpaper_id' => $wallpaperId,
            'path_type' => $pathType,
            'image_path' => $imagePath, // 使用传入的路径
            'created_at' => $existingToken['created_at'],
            'is_new' => false
        ];
    }
    
    // 生成新Token
    $newToken = generateImageToken($wallpaperId, $imagePath, $pathType);
    
    // 保存到数据库
    if (saveTokenToDatabase($pdo, $wallpaperId, $newToken, $imagePath, $pathType)) {
        return [
            'token' => $newToken,
            'wallpaper_id' => $wallpaperId,
            'path_type' => $pathType,
            'image_path' => $imagePath,
            'created_at' => date('Y-m-d H:i:s'),
            'is_new' => true
        ];
    }
    
    return null;
}

try {
    // 建立数据库连接
    $pdo = getPDOConnection();
    if (!$pdo) {
        throw new Exception('数据库连接失败');
    }
    
    // 获取请求参数
    $action = $_GET['action'] ?? $_POST['action'] ?? 'get';
    
    // 记录API访问（暂时注释掉）
    // logApiAccess('image_token', ['action' => $action]);
    
    switch ($action) {
        case 'get':
            // 获取单个Token
            $wallpaperId = $_GET['wallpaper_id'] ?? '';
            $pathType = $_GET['path_type'] ?? 'preview';
            $imagePath = $_GET['image_path'] ?? '';
            
            if (empty($wallpaperId)) {
                throw new Exception('缺少wallpaper_id参数');
            }
            
            // 如果没有提供image_path，自动发现实际路径
            if (empty($imagePath)) {
                $imagePath = discoverActualImagePath($wallpaperId, $pathType);
                if (empty($imagePath)) {
                    throw new Exception("无法找到壁纸文件: wallpaper_id={$wallpaperId}, path_type={$pathType}");
                }
            }
            
            $tokenInfo = getOrCreateToken($pdo, $wallpaperId, $imagePath, $pathType);
            
            if ($tokenInfo) {
                echo json_encode([
                    'code' => 0,
                    'message' => 'success',
                    'data' => $tokenInfo
                ], JSON_UNESCAPED_UNICODE);
            } else {
                throw new Exception('Token生成失败');
            }
            break;
            
        case 'batch':
            // 批量获取Token
            $input = json_decode(file_get_contents('php://input'), true);
            $wallpapers = $input['wallpapers'] ?? [];
            
            if (empty($wallpapers) || !is_array($wallpapers)) {
                throw new Exception('缺少wallpapers参数或格式错误');
            }
            
            $tokens = [];
            $errors = [];
            
            foreach ($wallpapers as $wallpaper) {
                $wallpaperId = $wallpaper['wallpaper_id'] ?? '';
                $pathType = $wallpaper['path_type'] ?? 'preview';
                $imagePath = $wallpaper['image_path'] ?? '';
                
                if (empty($wallpaperId)) {
                    $errors[] = '缺少wallpaper_id: ' . json_encode($wallpaper);
                    continue;
                }
                
                // 自动发现实际路径
                if (empty($imagePath)) {
                    $imagePath = discoverActualImagePath($wallpaperId, $pathType);
                }
                
                $tokenInfo = getOrCreateToken($pdo, $wallpaperId, $imagePath, $pathType);
                
                if ($tokenInfo) {
                    $key = $wallpaperId . '_' . $pathType;
                    $tokens[$key] = $tokenInfo['token'];
                } else {
                    $errors[] = "Token生成失败: {$wallpaperId}_{$pathType}";
                }
            }
            
            echo json_encode([
                'code' => 0,
                'message' => 'success',
                'data' => [
                    'tokens' => $tokens,
                    'errors' => $errors
                ]
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'refresh':
            // 刷新Token（强制生成新Token）
            $wallpaperId = $_GET['wallpaper_id'] ?? $_POST['wallpaper_id'] ?? '';
            $pathType = $_GET['path_type'] ?? $_POST['path_type'] ?? 'preview';
            $imagePath = $_GET['image_path'] ?? $_POST['image_path'] ?? '';
            
            if (empty($wallpaperId)) {
                throw new Exception('缺少wallpaper_id参数');
            }
            
            // 自动发现实际路径
            if (empty($imagePath)) {
                $imagePath = discoverActualImagePath($wallpaperId, $pathType);
            }
            
            // 生成新Token
            $newToken = generateImageToken($wallpaperId, $imagePath, $pathType);
            
            // 保存到数据库（会覆盖旧Token）
            if (saveTokenToDatabase($pdo, $wallpaperId, $newToken, $imagePath, $pathType)) {
                echo json_encode([
                    'code' => 0,
                    'message' => 'success',
                    'data' => [
                        'token' => $newToken,
                        'wallpaper_id' => $wallpaperId,
                        'path_type' => $pathType,
                        'image_path' => $imagePath,
                        'created_at' => date('Y-m-d H:i:s'),
                        'is_new' => true
                    ]
                ], JSON_UNESCAPED_UNICODE);
            } else {
                throw new Exception('Token刷新失败');
            }
            break;
            
        case 'sync':
            // 同步wallpapers主表，仅为主表中存在且image_tokens表中不存在的壁纸生成token
            $pathType = $_GET['path_type'] ?? 'original';
            $limit = intval($_GET['limit'] ?? 100); // 每次处理的数量限制
            $offset = intval($_GET['offset'] ?? 0); // 偏移量
            $clearTable = $_GET['clear'] ?? false; // 是否清空表
            
            try {
                // 如果需要清空表
                if ($clearTable) {
                    $clearSql = "TRUNCATE TABLE image_tokens";
                    $pdo->exec($clearSql);
                }
                
                // 仅获取主表中存在但image_tokens表中不存在的壁纸ID
                $sql = "SELECT CAST(w.id AS CHAR) as id, w.file_path 
                        FROM wallpapers w 
                        LEFT JOIN image_tokens it ON CAST(w.id AS CHAR) COLLATE utf8mb4_unicode_ci = it.wallpaper_id COLLATE utf8mb4_unicode_ci AND it.path_type = ? 
                        WHERE it.wallpaper_id IS NULL 
                        ORDER BY w.id 
                        LIMIT {$offset}, {$limit}";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$pathType]);
                $wallpapers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $processed = 0;
                $created = 0;
                $skipped = 0;
                $errors = [];
                
                foreach ($wallpapers as $wallpaper) {
                    $wallpaperId = $wallpaper['id'];
                    $imagePath = $wallpaper['file_path'];
                    
                    try {
                        // 验证文件确实存在
                        $fullFilePath = __DIR__ . '/../' . $imagePath;
                        if (file_exists($fullFilePath)) {
                            // 创建新token，仅为主表中存在的壁纸ID生成
                            $newToken = generateImageToken($wallpaperId, $imagePath, $pathType);
                            if (saveTokenToDatabase($pdo, $wallpaperId, $newToken, $imagePath, $pathType)) {
                                $created++;
                            }
                            $processed++;
                        } else {
                            $errors[] = "文件不存在: wallpaper_id={$wallpaperId}, path={$imagePath}";
                        }
                    } catch (Exception $e) {
                        $errors[] = "处理wallpaper_id={$wallpaperId}时出错: " . $e->getMessage();
                    }
                }
                
                // 获取主表总数和待处理数量
                $totalSql = "SELECT COUNT(*) FROM wallpapers";
                $totalStmt = $pdo->prepare($totalSql);
                $totalStmt->execute();
                $total = $totalStmt->fetchColumn();
                
                // 获取待处理的数量（主表中存在但tokens表中不存在的）
                $pendingSql = "SELECT COUNT(*) 
                               FROM wallpapers w 
                               LEFT JOIN image_tokens it ON CAST(w.id AS CHAR) COLLATE utf8mb4_unicode_ci = it.wallpaper_id COLLATE utf8mb4_unicode_ci AND it.path_type = ? 
                               WHERE it.wallpaper_id IS NULL";
                $pendingStmt = $pdo->prepare($pendingSql);
                $pendingStmt->execute([$pathType]);
                $pending = $pendingStmt->fetchColumn();
                
                echo json_encode([
                    'code' => 0,
                    'message' => 'success',
                    'data' => [
                        'processed' => $processed,
                        'created' => $created,
                        'skipped' => $skipped,
                        'errors' => $errors,
                        'total_wallpapers' => $total,
                        'pending_tokens' => $pending,
                        'current_offset' => $offset,
                        'has_more' => ($offset + $limit) < $pending,
                        'cleared_table' => $clearTable
                    ]
                ], JSON_UNESCAPED_UNICODE);
                
            } catch (PDOException $e) {
                throw new Exception('数据库查询失败: ' . $e->getMessage());
            }
            break;
            
        case 'auto_generate':
            // 自动生成TOKEN：当首页加载图片时，检测新增壁纸并生成TOKEN
            $wallpaperId = $_GET['wallpaper_id'] ?? $_POST['wallpaper_id'] ?? '';
            $pathType = $_GET['path_type'] ?? $_POST['path_type'] ?? 'original';
            
            if (empty($wallpaperId)) {
                throw new Exception('缺少wallpaper_id参数');
            }
            
            try {
                // 首先验证wallpaper_id是否存在于主表中
                $checkSql = "SELECT CAST(id AS CHAR) as id, file_path FROM wallpapers WHERE CAST(id AS CHAR) COLLATE utf8mb4_unicode_ci = ? COLLATE utf8mb4_unicode_ci";
                $checkStmt = $pdo->prepare($checkSql);
                $checkStmt->execute([$wallpaperId]);
                $wallpaper = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$wallpaper) {
                    throw new Exception("壁纸ID不存在于主表中: {$wallpaperId}");
                }
                
                // 检查image_tokens表中是否已存在
                $existingToken = getTokenFromDatabase($pdo, $wallpaperId, $pathType);
                
                if ($existingToken) {
                    // 已存在，返回现有TOKEN
                    echo json_encode([
                        'code' => 0,
                        'message' => 'token already exists',
                        'data' => [
                            'token' => $existingToken['token'],
                            'wallpaper_id' => $wallpaperId,
                            'path_type' => $pathType,
                            'image_path' => $existingToken['image_path'],
                            'created_at' => $existingToken['created_at'],
                            'is_new' => false
                        ]
                    ], JSON_UNESCAPED_UNICODE);
                } else {
                    // 不存在，生成新TOKEN
                    $imagePath = $wallpaper['file_path'];
                    
                    // 验证文件确实存在
                    $fullFilePath = __DIR__ . '/../' . $imagePath;
                    if (!file_exists($fullFilePath)) {
                        throw new Exception("文件不存在: {$imagePath}");
                    }
                    
                    // 生成新TOKEN
                    $newToken = generateImageToken($wallpaperId, $imagePath, $pathType);
                    
                    if (saveTokenToDatabase($pdo, $wallpaperId, $newToken, $imagePath, $pathType)) {
                        echo json_encode([
                            'code' => 0,
                            'message' => 'token generated successfully',
                            'data' => [
                                'token' => $newToken,
                                'wallpaper_id' => $wallpaperId,
                                'path_type' => $pathType,
                                'image_path' => $imagePath,
                                'created_at' => date('Y-m-d H:i:s'),
                                'is_new' => true
                            ]
                        ], JSON_UNESCAPED_UNICODE);
                    } else {
                        throw new Exception('TOKEN生成失败');
                    }
                }
                
            } catch (PDOException $e) {
                throw new Exception('数据库操作失败: ' . $e->getMessage());
            }
            break;
            
        case 'resolve':
            // 根据Token解析图片路径（供image_proxy.php使用）
            $token = $_GET['token'] ?? $_POST['token'] ?? '';
            
            if (empty($token)) {
                throw new Exception('缺少token参数');
            }
            
            $imageInfo = getImagePathByToken($pdo, $token);
            
            if ($imageInfo) {
                echo json_encode([
                    'code' => 0,
                    'message' => 'success',
                    'data' => $imageInfo
                ], JSON_UNESCAPED_UNICODE);
            } else {
                throw new Exception('Token无效或已过期');
            }
            break;
            
        case 'list':
            // 获取Token列表（用于测试和管理）
            $limit = intval($_GET['limit'] ?? 20);
            $offset = intval($_GET['offset'] ?? 0);
            $pathType = $_GET['path_type'] ?? '';
            
            try {
                // 构建查询条件
                $whereClause = '';
                $params = [];
                
                if (!empty($pathType)) {
                    $whereClause = 'WHERE path_type = ?';
                    $params[] = $pathType;
                }
                
                // 获取Token列表
                $sql = "SELECT wallpaper_id, token, image_path, path_type, created_at, updated_at 
                        FROM image_tokens {$whereClause} 
                        ORDER BY created_at DESC 
                        LIMIT {$offset}, {$limit}";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // 获取总数
                $countSql = "SELECT COUNT(*) FROM image_tokens {$whereClause}";
                $countStmt = $pdo->prepare($countSql);
                $countStmt->execute($params);
                $total = $countStmt->fetchColumn();
                
                echo json_encode([
                    'code' => 0,
                    'message' => 'success',
                    'data' => [
                        'tokens' => $tokens,
                        'total' => intval($total),
                        'limit' => $limit,
                        'offset' => $offset,
                        'has_more' => ($offset + $limit) < $total
                    ]
                ], JSON_UNESCAPED_UNICODE);
                
            } catch (PDOException $e) {
                throw new Exception('获取Token列表失败: ' . $e->getMessage());
            }
            break;
            
        default:
            throw new Exception('不支持的操作: ' . $action);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'code' => 1,
        'message' => $e->getMessage(),
        'data' => null
    ], JSON_UNESCAPED_UNICODE);
    
    // 记录错误日志
    error_log('Image Token API Error: ' . $e->getMessage());
}
?>
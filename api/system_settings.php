<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 引入数据库配置
require_once '../config/database.php';

// 系统设置配置文件路径
$configFile = '../config/system_settings.json';

// 验证管理员权限（简单的头部验证）
function checkAdminAuth() {
    $headers = getallheaders();
    if (!isset($headers['Authorization']) || $headers['Authorization'] !== 'Bearer admin-token') {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => '未授权访问']);
        exit;
    }
}

// 检查是否需要管理员权限（GET请求不需要认证）
function requiresAuth($method) {
    return $method !== 'GET';
}

// 读取系统设置
function getSystemSettings() {
    global $configFile;
    
    // 如果配置文件不存在，返回默认设置
    if (!file_exists($configFile)) {
        return getDefaultSettings();
    }
    
    $content = file_get_contents($configFile);
    $settings = json_decode($content, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return getDefaultSettings();
    }
    
    return $settings;
}

// 获取默认设置
function getDefaultSettings() {
    return [
        'basic' => [
            'site_name' => '壁纸喵 ° 不吃鱼',
            'site_subtitle' => '你的专属壁纸库',
            'contact_email' => 'admin@example.com',
            'icp_number' => ''
        ],
        'seo' => [
            'keywords' => '壁纸,高清壁纸,免费壁纸,桌面壁纸,手机壁纸,风景壁纸,动漫壁纸',
            'description' => '提供高质量免费壁纸下载，包含风景、动漫、抽象等多种分类，支持高清桌面和手机壁纸',
            'og_image' => '/static/images/og-default.svg'
        ],
        'upload' => [
            'max_file_size' => 10,
            'allowed_file_types' => 'jpg,jpeg,png,webp',
            'image_quality' => 'medium',
            'watermark_type' => 'text'
        ],
        'security' => [
            'login_fail_limit' => 5,
            'account_lock_time' => 30,
            'min_password_length' => 8
        ],
        'email' => [
            'smtp_server' => '',
            'smtp_port' => 587,
            'sender_email' => '',
            'sender_password' => '',
            'enable_ssl' => false
        ]
    ];
}

// 保存系统设置
function saveSystemSettings($settings) {
    global $configFile;
    
    // 确保config目录存在
    $configDir = dirname($configFile);
    if (!is_dir($configDir)) {
        mkdir($configDir, 0755, true);
    }
    
    // 验证设置数据
    $validatedSettings = validateSettings($settings);
    if ($validatedSettings === false) {
        return false;
    }
    
    // 保存到JSON文件
    $jsonContent = json_encode($validatedSettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    if (file_put_contents($configFile, $jsonContent, LOCK_EX) === false) {
        return false;
    }
    
    return true;
}

// 验证设置数据
function validateSettings($settings) {
    $defaults = getDefaultSettings();
    $validated = [];
    
    // 验证基本设置
    if (isset($settings['basic'])) {
        $validated['basic'] = [
            'site_name' => trim($settings['basic']['site_name'] ?? $defaults['basic']['site_name']),
            'site_subtitle' => trim($settings['basic']['site_subtitle'] ?? $defaults['basic']['site_subtitle']),
            'contact_email' => filter_var($settings['basic']['contact_email'] ?? $defaults['basic']['contact_email'], FILTER_VALIDATE_EMAIL) ?: $defaults['basic']['contact_email'],
            'icp_number' => trim($settings['basic']['icp_number'] ?? $defaults['basic']['icp_number'])
        ];
    } else {
        $validated['basic'] = $defaults['basic'];
    }
    
    // 验证上传设置
    if (isset($settings['upload'])) {
        $validated['upload'] = [
            'max_file_size' => max(1, min(100, intval($settings['upload']['max_file_size'] ?? $defaults['upload']['max_file_size']))),
            'allowed_file_types' => trim($settings['upload']['allowed_file_types'] ?? $defaults['upload']['allowed_file_types']),
            'image_quality' => in_array($settings['upload']['image_quality'] ?? '', ['high', 'medium', 'low']) ? $settings['upload']['image_quality'] : $defaults['upload']['image_quality'],
            'watermark_type' => in_array($settings['upload']['watermark_type'] ?? '', ['none', 'text', 'image']) ? $settings['upload']['watermark_type'] : $defaults['upload']['watermark_type']
        ];
    } else {
        $validated['upload'] = $defaults['upload'];
    }
    
    // 验证安全设置
    if (isset($settings['security'])) {
        $validated['security'] = [
            'login_fail_limit' => max(1, min(20, intval($settings['security']['login_fail_limit'] ?? $defaults['security']['login_fail_limit']))),
            'account_lock_time' => max(1, min(1440, intval($settings['security']['account_lock_time'] ?? $defaults['security']['account_lock_time']))),
            'min_password_length' => max(6, min(32, intval($settings['security']['min_password_length'] ?? $defaults['security']['min_password_length'])))
        ];
    } else {
        $validated['security'] = $defaults['security'];
    }
    
    // 验证邮件设置
    if (isset($settings['email'])) {
        $validated['email'] = [
            'smtp_server' => trim($settings['email']['smtp_server'] ?? $defaults['email']['smtp_server']),
            'smtp_port' => max(1, min(65535, intval($settings['email']['smtp_port'] ?? $defaults['email']['smtp_port']))),
            'sender_email' => filter_var($settings['email']['sender_email'] ?? $defaults['email']['sender_email'], FILTER_VALIDATE_EMAIL) ?: $defaults['email']['sender_email'],
            'sender_password' => $settings['email']['sender_password'] ?? $defaults['email']['sender_password'],
            'enable_ssl' => (bool)($settings['email']['enable_ssl'] ?? $defaults['email']['enable_ssl'])
        ];
    } else {
        $validated['email'] = $defaults['email'];
    }
    
    // 验证SEO设置
    if (isset($settings['seo'])) {
        $validated['seo'] = [
            'keywords' => trim($settings['seo']['keywords'] ?? $defaults['seo']['keywords']),
            'description' => trim($settings['seo']['description'] ?? $defaults['seo']['description']),
            'og_image' => trim($settings['seo']['og_image'] ?? $defaults['seo']['og_image'])
        ];
    } else {
        $validated['seo'] = $defaults['seo'];
    }
    
    return $validated;
}

// 记录操作日志
function logOperation($operation, $details = '') {
    global $pdo;
    
    try {
        // 假设admin_id为1（实际项目中应该从session或token中获取）
        $adminId = 1;
        $stmt = $pdo->prepare("INSERT INTO admin_operation_logs (admin_id, operation_type, target_type, target_id, operation_details, ip_address, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$adminId, $operation, 'system_settings', '1', $details, $_SERVER['REMOTE_ADDR'] ?? '']);
    } catch (Exception $e) {
        // 日志记录失败不影响主要功能
        error_log('Failed to log operation: ' . $e->getMessage());
    }
}

// 主要处理逻辑
try {
    // 只有非GET请求需要认证
    if (requiresAuth($_SERVER['REQUEST_METHOD'])) {
        checkAdminAuth();
    }
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            $settings = getSystemSettings();
            echo json_encode([
                'success' => true,
                'data' => $settings
            ]);
            break;
            
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => '无效的JSON数据']);
                break;
            }
            
            if (saveSystemSettings($input)) {
                logOperation('system_settings_update', '系统设置更新成功');
                echo json_encode(['success' => true, 'message' => '设置保存成功']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => '设置保存失败']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => '不支持的请求方法']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '服务器内部错误',
        'error' => $e->getMessage()
    ]);
}
?>
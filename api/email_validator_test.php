<?php
/**
 * 邮箱域名验证测试接口
 * 用于测试邮箱域名MX记录验证功能
 * @author AI
 * @date 2024
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/email_validator.php';

// 统一返回格式
function response($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// 只允许POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    response(['error' => '只允许POST请求']);
}

// 获取请求数据
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    response(['error' => '无效的JSON数据']);
}

$action = $data['action'] ?? '';
$email = $data['email'] ?? '';

switch ($action) {
    case 'validate':
        // 简单验证
        if (empty($email)) {
            response([
                'valid' => false,
                'email' => $email,
                'error' => '邮箱地址不能为空'
            ]);
        }
        
        $isValid = isEmailDomainValid($email);
        response([
            'valid' => $isValid,
            'email' => $email,
            'error' => $isValid ? null : '域名不存在或未配置邮件服务'
        ]);
        break;
        
    case 'validate_details':
        // 详细验证信息
        if (empty($email)) {
            response([
                'valid' => false,
                'email' => $email,
                'domain' => '',
                'has_mx' => false,
                'has_a' => false,
                'mx_records' => [],
                'error' => '邮箱地址不能为空'
            ]);
        }
        
        $result = validateEmailDomainWithDetails($email);
        response($result);
        break;
        
    case 'batch_validate':
        // 批量验证
        $emails = $data['emails'] ?? [];
        if (!is_array($emails)) {
            response(['error' => 'emails参数必须是数组']);
        }
        
        $results = [];
        foreach ($emails as $email) {
            $isValid = isEmailDomainValid($email);
            $results[] = [
                'email' => $email,
                'valid' => $isValid,
                'error' => $isValid ? null : '域名不存在或未配置邮件服务'
            ];
        }
        
        response(['results' => $results]);
        break;
        
    case 'get_mx_records':
        // 获取MX记录
        if (empty($email)) {
            response(['error' => '邮箱地址不能为空']);
        }
        
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            response(['error' => '邮箱格式不正确']);
        }
        
        $domain = $parts[1];
        $mxRecords = getDomainMXRecords($domain);
        
        response([
            'domain' => $domain,
            'mx_records' => $mxRecords,
            'has_mx' => !empty($mxRecords)
        ]);
        break;
        
    case 'test_common_domains':
        // 测试常见域名
        $commonDomains = [
            'gmail.com',
            'qq.com',
            '163.com',
            '126.com',
            'sina.com',
            'hotmail.com',
            'outlook.com',
            'yahoo.com',
            'live.com',
            'icloud.com'
        ];
        
        $results = [];
        foreach ($commonDomains as $domain) {
            $testEmail = 'test@' . $domain;
            $isValid = isEmailDomainValid($testEmail);
            $mxRecords = getDomainMXRecords($domain);
            
            $results[] = [
                'domain' => $domain,
                'email' => $testEmail,
                'valid' => $isValid,
                'has_mx' => checkdnsrr($domain, 'MX'),
                'has_a' => checkdnsrr($domain, 'A'),
                'mx_count' => count($mxRecords)
            ];
        }
        
        response(['results' => $results]);
        break;
        
    case 'test_invalid_domains':
        // 测试无效域名
        $invalidDomains = [
            'invalid-domain-123456.com',
            'example.com',
            'localhost',
            'test.test',
            'fake-domain-xyz.com',
            'nonexistent.domain'
        ];
        
        $results = [];
        foreach ($invalidDomains as $domain) {
            $testEmail = 'test@' . $domain;
            $isValid = isEmailDomainValid($testEmail);
            
            $results[] = [
                'domain' => $domain,
                'email' => $testEmail,
                'valid' => $isValid,
                'has_mx' => checkdnsrr($domain, 'MX'),
                'has_a' => checkdnsrr($domain, 'A'),
                'is_obviously_invalid' => isObviouslyInvalidDomain($domain)
            ];
        }
        
        response(['results' => $results]);
        break;
        
    default:
        response(['error' => '无效的action参数']);
}
?>
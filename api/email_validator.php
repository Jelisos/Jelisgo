<?php
/**
 * 邮箱域名验证工具
 * 通过检查MX记录验证邮箱域名的有效性
 * @author AI
 * @date 2024
 */

/**
 * 验证邮箱域名是否有效
 * 通过检查域名的MX记录来确认该域名是否配置了邮件服务器
 * 
 * @param string $email 邮箱地址
 * @return bool 域名是否有效
 */
function isEmailDomainValid($email) {
    // 基本邮箱格式验证
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    
    // 提取域名部分
    $parts = explode('@', $email);
    if (count($parts) !== 2) {
        return false;
    }
    
    $domain = trim($parts[1]);
    
    // 检查域名是否为空
    if (empty($domain)) {
        return false;
    }
    
    // 检查域名是否存在MX记录
    // MX记录表示该域名配置了邮件服务器
    $mxRecords = [];
    $hasMX = checkdnsrr($domain, 'MX');
    
    if ($hasMX) {
        return true;
    }
    
    // 如果没有MX记录，检查是否有A记录
    // 某些域名可能没有专门的MX记录，但有A记录也可以接收邮件
    $hasA = checkdnsrr($domain, 'A');
    
    return $hasA;
}

/**
 * 获取域名的MX记录详细信息
 * 
 * @param string $domain 域名
 * @return array MX记录信息
 */
function getDomainMXRecords($domain) {
    $mxRecords = [];
    $weights = [];
    
    if (getmxrr($domain, $mxRecords, $weights)) {
        $result = [];
        for ($i = 0; $i < count($mxRecords); $i++) {
            $result[] = [
                'host' => $mxRecords[$i],
                'priority' => $weights[$i]
            ];
        }
        // 按优先级排序（数字越小优先级越高）
        usort($result, function($a, $b) {
            return $a['priority'] - $b['priority'];
        });
        return $result;
    }
    
    return [];
}

/**
 * 验证邮箱域名并返回详细信息
 * 
 * @param string $email 邮箱地址
 * @return array 验证结果和详细信息
 */
function validateEmailDomainWithDetails($email) {
    $result = [
        'valid' => false,
        'email' => $email,
        'domain' => '',
        'has_mx' => false,
        'has_a' => false,
        'mx_records' => [],
        'error' => ''
    ];
    
    // 基本邮箱格式验证
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $result['error'] = '邮箱格式不正确';
        return $result;
    }
    
    // 提取域名
    $parts = explode('@', $email);
    if (count($parts) !== 2) {
        $result['error'] = '邮箱格式不正确';
        return $result;
    }
    
    $domain = trim($parts[1]);
    $result['domain'] = $domain;
    
    if (empty($domain)) {
        $result['error'] = '域名为空';
        return $result;
    }
    
    // 检查MX记录
    $result['has_mx'] = checkdnsrr($domain, 'MX');
    if ($result['has_mx']) {
        $result['mx_records'] = getDomainMXRecords($domain);
    }
    
    // 检查A记录
    $result['has_a'] = checkdnsrr($domain, 'A');
    
    // 判断域名是否有效
    if ($result['has_mx'] || $result['has_a']) {
        $result['valid'] = true;
    } else {
        $result['error'] = '该域名未配置邮件服务或不存在';
    }
    
    return $result;
}

/**
 * 常见的无效域名列表（可扩展）
 */
function getInvalidDomains() {
    return [
        'example.com',
        'test.com',
        'localhost',
        '127.0.0.1',
        'invalid.com',
        'fake.com'
    ];
}

/**
 * 检查是否为明显的无效域名
 * 
 * @param string $domain 域名
 * @return bool 是否为无效域名
 */
function isObviouslyInvalidDomain($domain) {
    $invalidDomains = getInvalidDomains();
    return in_array(strtolower($domain), $invalidDomains);
}
?>
<?php
/**
 * 用户退出登录接口 - 统一认证版本
 * @author AI
 * @return JSON
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 处理OPTIONS请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 在统一认证模式下，退出登录主要在前端通过清除localStorage完成
// 后端不再需要处理session

echo json_encode([
    'code' => 0,
    'msg' => '退出成功',
    'data' => null
], JSON_UNESCAPED_UNICODE);
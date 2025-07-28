<?php
/**
 * 统一API响应格式
 * 位置: api/response_helper.php
 */
function success($data = null, $msg = 'success') {
    echo json_encode([
        'success' => true,
        'code' => 200,
        'message' => $msg,
        'data' => $data
    ]);
    exit;
}

function error($msg = 'error', $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'code' => $code,
        'message' => $msg,
        'data' => null
    ]);
    exit;
}
?>
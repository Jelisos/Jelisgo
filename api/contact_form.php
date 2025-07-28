<?php
/**
 * 联系表单处理API
 * 功能：接收用户留言并发送邮件到指定邮箱
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// 只允许POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => '只允许POST请求'
    ]);
    exit;
}

// 引入SMTP邮件发送类
require_once __DIR__ . '/minaxg/smtp_mailer.php';

try {
    // 获取POST数据
    $input = json_decode(file_get_contents('php://input'), true);
    
    // 如果是表单数据，则从$_POST获取
    if (!$input) {
        $input = $_POST;
    }
    
    // 验证必填字段
    $required_fields = ['name', 'email', 'subject', 'message'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            throw new Exception("请填写{$field}字段");
        }
    }
    
    $name = trim($input['name']);
    $email = trim($input['email']);
    $subject = trim($input['subject']);
    $message = trim($input['message']);
    
    // 验证邮箱格式
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('邮箱格式不正确');
    }
    
    // 主题映射
    $subject_map = [
        'general' => '一般咨询',
        'technical' => '技术支持',
        'cooperation' => '商务合作',
        'feedback' => '意见反馈',
        'report' => '举报投诉'
    ];
    
    $subject_text = $subject_map[$subject] ?? $subject;
    
    // 构建邮件内容
    $email_subject = "[网站留言] {$subject_text} - 来自 {$name}";
    $email_body = "
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
            .content { background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
            .field { margin-bottom: 15px; }
            .label { font-weight: bold; color: #555; }
            .value { margin-top: 5px; padding: 10px; background: #f8f9fa; border-radius: 3px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>网站用户留言</h2>
                <p>收到来自网站的新留言，请及时处理。</p>
            </div>
            <div class='content'>
                <div class='field'>
                    <div class='label'>姓名：</div>
                    <div class='value'>{$name}</div>
                </div>
                <div class='field'>
                    <div class='label'>邮箱：</div>
                    <div class='value'>{$email}</div>
                </div>
                <div class='field'>
                    <div class='label'>咨询类型：</div>
                    <div class='value'>{$subject_text}</div>
                </div>
                <div class='field'>
                    <div class='label'>留言内容：</div>
                    <div class='value'>" . nl2br(htmlspecialchars($message)) . "</div>
                </div>
                <div class='field'>
                    <div class='label'>提交时间：</div>
                    <div class='value'>" . date('Y-m-d H:i:s') . "</div>
                </div>
                <div class='field'>
                    <div class='label'>IP地址：</div>
                    <div class='value'>" . ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown') . "</div>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // SMTP配置（使用系统设置中的邮箱配置）
    $smtp_host = 'smtp.qq.com';
    $smtp_port = 465;
    $smtp_username = 'tojelis@qq.com'; // 发送邮箱（使用系统设置中的配置）
    $smtp_password = 'gczcitspymfnbgai'; // 使用系统设置中已配置的授权码
    
    // 接收邮箱
    $to_email = '3030275630@qq.com';
    
    // 创建SMTP邮件发送实例
    $mailer = new SMTPMailer($smtp_host, $smtp_port, $smtp_username, $smtp_password, 'ssl');
    
    // 发送邮件
    $send_result = $mailer->sendMail($to_email, $email_subject, $email_body, '网站留言系统');
    
    if ($send_result) {
        // 记录日志
        $log_data = [
            'time' => date('Y-m-d H:i:s'),
            'name' => $name,
            'email' => $email,
            'subject' => $subject_text,
            'ip' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'status' => 'success'
        ];
        
        $log_file = __DIR__ . '/../logs/contact_form_' . date('Y-m') . '.log';
        $log_dir = dirname($log_file);
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        file_put_contents($log_file, json_encode($log_data) . "\n", FILE_APPEND | LOCK_EX);
        
        echo json_encode([
            'success' => true,
            'message' => '留言发送成功！我们会尽快回复您。'
        ]);
    } else {
        throw new Exception('邮件发送失败，请稍后重试');
    }
    
} catch (Exception $e) {
    // 记录错误日志
    $error_log = [
        'time' => date('Y-m-d H:i:s'),
        'error' => $e->getMessage(),
        'input' => $input ?? [],
        'ip' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    $log_file = __DIR__ . '/../logs/contact_form_errors_' . date('Y-m') . '.log';
    $log_dir = dirname($log_file);
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    file_put_contents($log_file, json_encode($error_log) . "\n", FILE_APPEND | LOCK_EX);
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
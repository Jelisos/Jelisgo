<?php
// functions.php - 辅助函数
require_once 'config.php';

function generate_verification_code() {
    return str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
}

function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

// 发送邮件函数（使用SMTP）
function send_verification_email($recipient_email, $code) {
    require_once 'smtp_mailer.php';
    
    $mailer = new SMTPMailer(
        SMTP_HOST,
        SMTP_PORT,
        SMTP_USERNAME,
        SMTP_PASSWORD,
        SMTP_SECURE
    );
    
    $subject = '重置您的账户密码';
    $message = "
    <div style='font-family: Arial, sans-serif; padding: 20px; border: 1px solid #eee; max-width: 600px; margin: 0 auto;'>
        <h3 style='color: #333;'>重置您的账户密码</h3>
        <p>您收到此邮件是因为您（或他人）请求重置您的账户密码。</p>
        <p>您的验证码是：<strong style='font-size: 24px; color: #e74c3c;'>$code</strong></p>
        <p>验证码有效期为5分钟。如果您没有请求密码重置，请忽略此邮件。</p>
        <p style='color: #666; font-size: 12px;'>© 2025 Wallpaper Haven</p>
    </div>
    ";
    
    return $mailer->sendMail($recipient_email, $subject, $message, SMTP_FROM_NAME);
}
?>
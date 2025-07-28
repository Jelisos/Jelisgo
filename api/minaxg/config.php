<?php
// config.php - 数据库和邮箱配置
define('DB_HOST', 'localhost');
define('DB_NAME', 'wallpaper_db');
define('DB_USER', 'root');
define('DB_PWD', '');

// QQ邮箱SMTP配置
define('SMTP_HOST', 'smtp.qq.com');
define('SMTP_PORT', 465);
define('SMTP_SECURE', 'ssl');
define('SMTP_USERNAME', 'tojelis@qq.com');
define('SMTP_PASSWORD', 'gczcitspymfnbgai');
define('SMTP_FROM', 'tojelis@qq.com');
define('SMTP_FROM_NAME', 'Jelisgo管理员');

// 验证码有效期（秒）
define('CODE_EXPIRATION', 300); // 5分钟
?>
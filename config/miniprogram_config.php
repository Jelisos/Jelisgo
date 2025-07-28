<?php
/**
 * 微信小程序配置文件
 * 包含小程序的基本配置信息
 */

// 微信小程序配置
$miniprogram_config = [
    // 小程序AppID
    'appid' => 'your_miniprogram_appid',
    
    // 小程序AppSecret
    'secret' => 'your_miniprogram_secret',
    
    // 小程序版本
    'version' => '1.0.0',
    
    // 小程序名称
    'name' => 'Jelisgo壁纸',
    
    // 小程序描述
    'description' => '发现精美壁纸，装点你的世界',
    
    // 分享配置
    'share' => [
        'title' => 'Jelisgo壁纸 - 发现精美壁纸',
        'desc' => '海量高清壁纸，总有一款适合你',
        'image_url' => 'http://localhost/images/share-cover.jpg'
    ],
    
    // 小程序码配置
    'qrcode' => [
        'width' => 430,
        'auto_color' => false,
        'line_color' => ['r' => 0, 'g' => 0, 'b' => 0],
        'is_hyaline' => false
    ],
    
    // API配置
    'api' => [
        'base_url' => 'http://localhost/api/',
        'timeout' => 30
    ]
];

// 环境配置
$environment = 'development'; // development, production

if ($environment === 'production') {
    // 生产环境配置
    $miniprogram_config['api']['base_url'] = 'https://your-domain.com/api/';
}

?>
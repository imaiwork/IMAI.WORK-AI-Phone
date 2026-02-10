<?php

return [
    // 抖音开放平台配置
    'client_key' => 'awyu04mkzz0hib3a',
    'client_secret' => '',
    'access_token' => '',
    
    // API密钥（用于签名）
    'api_key' => env('DOUYIN_API_KEY', ''),
    
    // API基础URL
    'api_base_url' => 'https://open.douyin.com',
    
    // 缓存配置
    'cache' => [
        'prefix' => 'douyin_',
        'expire' => 7200, // 2小时
    ],
    
    // 抖音应用配置
    'app_config' => [
        'scope' => 'content.publish,user.info',
        'response_type' => 'code',
        'grant_type' => 'authorization_code',
    ],
    
    // 内容发布相关配置
    'content' => [
        'max_title_length' => 55,
        'max_desc_length' => 2200,
        'support_formats' => ['mp4', 'mov', 'avi'],
        'max_file_size' => 1024 * 1024 * 100, // 100MB
    ],
    
    // API版本
    'version' => 'v1.0',
];
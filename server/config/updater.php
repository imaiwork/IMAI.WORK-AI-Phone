<?php

// 引入 tools.php 配置（获取远端 API 地址和 secret_token）
$toolsConfig = require __DIR__ . '/api_tools.php';

return [
    // 云端 API 地址
    'remote_api_url' => $toolsConfig['compare_server'] . '/cloud_api.php',

    'secret_token' => $toolsConfig['compare_server_secret_token'],

    // 请求超时配置（单位：秒）
    // timeout：整个请求最长等待时间，文件数量多时适当调大
    // connect_timeout：建立连接的超时，网络较慢时可调大
    'timeout'         => 1200,
    'connect_timeout' => 1200,

    // 本地项目根目录（自动获取）
    'local_dir' => root_path(),

    /**
     * 忽略列表：不显示在差异列表，也不参与 MD5 比对
     */
    'ignore' => [
        '.env',
        '.env.local',
        'install.lock',
        '.gitignore',
        'swoole_loader_helper.php',
        'runtime/',
        '.git/',
        '.idea/',
        '.vscode/',
        'node_modules/',
        'vendor/',
        'public/uploads/',
        'public/qrcode/',
        '__MACOSX/',
        '*.log',
        '*.swp',
        '*.bak',
        '*.DS_Store',
        'Thumbs.db',
        ".well-known/",
        ".user.ini",
        'public/overwrite_zips/'
    ],

    /**
     * 直接覆盖目录：同步时无条件全量覆盖，不做差异检测
     */
    'direct_overwrite' => [
        'public/pc/',
        'public/admin/',
        'extend/miniprogram-ci/mp-weixin/',
    ],

    // 数据库配置
    // target 为本地客户库，从环境变量读取
    'db' => [
        'target' => [
            'host'    => env('DATABASE.HOSTNAME', '127.0.0.1'),
            'port'    => env('DATABASE.HOSTPORT', '3306'),
            'user'    => env('DATABASE.USERNAME', ''),
            'pass'    => env('DATABASE.PASSWORD', ''),
            'name'    => env('DATABASE.DATABASE', ''),
            'charset' => env('DATABASE.CHARSET', 'utf8mb4'),
            'prefix'  => env('DATABASE.PREFIX', 'la_'),
        ],
    ],
];

<?php

// +----------------------------------------------------------------------
// | 缓存设置
// +----------------------------------------------------------------------

return [
    // 默认缓存驱动
    'default' => env('cache.driver', 'file'),

    // 缓存连接方式配置
    'stores'  => [
        'file' => [
            // 驱动方式
            'type'       => 'File',
            // 缓存保存目录
            'path'       => '',
            // 缓存前缀
            'prefix'     => 'la',
            // 缓存有效期 0表示永久缓存
            'expire'     => 0,
            // 缓存目录权限
            'path_mode'  => 0777,
            // 缓存文件权限
            'file_mode'  => 0777,
            // 缓存标签前缀
            'tag_prefix' => 'tag:',
            // 序列化机制 例如 ['serialize', 'unserialize']
            'serialize'  => [],
        ],
        // redis缓存
        'redis'  =>  [
            // 驱动方式
            'type'   => 'redis',
            // 服务器地址
            'host'   => env('REDIS.HOST','127.0.0.1'),
            // 端口
            'port'   => env('REDIS.PORT','6379'),
            // 密码
            'password' => env('REDIS.PASSWORD', ''),
            'select' => env('CACHE.SELECT', 2),
            // 缓存前缀
            'prefix' =>  env('CACHE.PREFIX', 'draw_'),
        ],
        'material_redis'  =>  [
            // 驱动方式
            'type'   => 'redis',
            // 服务器地址
            'host'   => env('REDIS.HOST','127.0.0.1'),
            // 端口
            'port'   => env('REDIS.PORT','6379'),
            // 密码
            'password' => env('REDIS.PASSWORD', ''),
            'select' => 6,
            // 缓存前缀
            'prefix' =>  env('CACHE.PREFIX', 'draw_'),
        ],
        'concurrent_redis'  =>  [
            // 驱动方式
            'type'   => 'redis',
            // 服务器地址
            'host'   => env('REDIS.HOST','127.0.0.1'),
            // 端口
            'port'   => env('REDIS.PORT','6379'),
            // 密码
            'password' => env('REDIS.PASSWORD', ''),
            'select' => 5,
            // 缓存前缀
            'prefix' =>  env('CACHE.PREFIX', 'draw_'),
        ],
    ],
];

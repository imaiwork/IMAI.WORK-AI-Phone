<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------

return [
    'default'     => 'redis',
    'connections' => [
        'sync'     => [
            'type' => 'sync',
        ],
        'database' => [
            'type'       => 'database',
            'queue'      => 'default',
            'table'      => 'jobs',
            'connection' => null,
        ],
        'redis'    => [
            'type'       => 'redis',
            'queue'      => 'default',
            'host'       => env('redis.HOST', '127.0.0.1'),
            'port'       => env('redis.PORT', 6379),
            'password'   => env('redis.PASSWORD', ''),
            'select'     => env('redis.SELECT', '11'),
            'timeout'    => env('redis.TIMEOUT', 0),
            // GEO 监测 cell 单次执行可达 120s(中台 web 口径):可见性超时默认 60s 太短,
            // 多 worker 时会把执行中的 cell 重新取出重复消费(重复扣费/批次计数虚增)
            'retry_after' => (int)env('redis.RETRY_AFTER', 300),
            'tries'    => env('redis.TRIES', 10),
            'persistent' => false,
            'prefix'     => env('redis.PREFIX', 'ai2024:'), // 使用环境变量
        ],
    ],
    'failed'      => [
        'type'  => 'none',
        'table' => 'failed_jobs',
    ],
];

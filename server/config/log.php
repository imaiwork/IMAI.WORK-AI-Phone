<?php

// +----------------------------------------------------------------------
// | 日志设置
// +----------------------------------------------------------------------
return [
    // 默认日志记录通道
    'default'      => env('log.channel', 'file'),
    // 日志记录级别
    'level'        => [],
    // 日志类型记录的通道 ['error'=>'email',...]
    'type_channel' => [],
    // 关闭全局日志写入
    'close'        => false,
    // 全局日志处理 支持闭包
    'processor'    => null,

    // 日志通道列表
    'channels'     => [
        'file' => [
            // 日志记录方式
            'type'           => 'File',
            // 日志保存目录
            'path'           => '',
            // 单文件日志写入
            'single'         => false,
            // 独立日志级别
            'apart_level'    => ['system', 'openai', 'sd', 'audio', 'error', 'sql', 'suno', 'hi_dream', 'wxchat', 'analyse', 'wxPay', 'qw', "phone_list", 'qwen', 'human', 'draw_video', 'sph', 'add_wechat'],

            // 最大日志文件数量
            'max_files'      => 0,
            // 使用JSON格式记录
            'json'           => false,
            // 日志处理
            'processor'      => null,
            // 关闭通道日志写入
            'close'          => false,
            // 日志输出格式化
            'format'         => '[%s][%s] %s',
            // 是否实时写入
            'realtime_write' => false,
        ],

         'ai' => [ // 聊天
            // 日志记录方式
            'type'           => 'File',
            'path'           => app()->getRootPath() . '/runtime/log/ai/',
            'json'           => false,
            'format'         => '[%s][%s] %s',
        ],
        'human' => [ // 聊天
            // 日志记录方式
            'type'           => 'File',
            'path'           => app()->getRootPath() . '/runtime/log/human/',
            'json'           => false,
            'format'         => '[%s][%s] %s',
        ],
        'socket' => [
            'type'           => 'File',
            'path'           => app()->getRootPath() . '/runtime/log/socket/' . date('Ymd'),
            'json'           => false,
            'single'         => false,
            'time_format'    => 'Y-m-d H:i:s',
            'format'         => '[%s][%s] %s',
            'apart_level'   =>  [
                'error', 'info', 'send', 'device', 'user', 'msg', 'msg_list', 'card', 'cron', 'bind', 'init', 'note', 'post',
                'task_delete', 'task_paused', 'task_recovery', 'task_send', 'task_record','channel', 'ws'
            ],
        ],
        'wechat_socket' => [
            'type'           => 'File',
            'path'           => app()->getRootPath() . '/runtime/log/wechat_socket/' . date('Ymd'),
            'json'           => false,
            'single'         => false,
            'time_format'    => 'Y-m-d H:i:s',
            'format'         => '[%s][%s] %s',
            'apart_level'   =>  ['error', 'info', 'send', 'device', 'user', 'msg', 'msg_list', 'notice', 'cron', 'bind', 'init', 'note', 'post'],
        ],
        'sv' => [
            'type'           => 'File',
            'path'           => app()->getRootPath() . '/runtime/log/sv/',
            'json'           => false,
            'single'         => false,
            'time_format'    => 'Y-m-d H:i:s',
            'format'         => '[%s][%s] %s',
        ],
        'jobs' => [
            'type'           => 'File',
            'path'           => app()->getRootPath() . '/runtime/log/jobs/',
            'json'           => false,
            'format'         => '[%s][%s] %s',
        ],
        'crontab' => [
            'type'           => 'File',
            'path'           => app()->getRootPath() . '/runtime/log/crontab/',
            'json'           => false,
            'format'         => '[%s][%s] %s',
        ],
        'device' => [
            'type'           => 'File',
            'path'           => app()->getRootPath() . '/runtime/log/device/',
            'json'           => false,
            'time_format'    => 'Y-m-d H:i:s',
            'format'         => '[%s][%s] %s',
            'apart_level'   =>  ['error', 'info', 'warning', 'publish', 'clues', 'add_wechat', 'active', 'take_over'],
        ],
        'clip' => [
            'type'           => 'File',
            'path'           => app()->getRootPath() . '/runtime/log/clip/',
            'json'           => false,
            'format'         => '[%s][%s] %s',
        ],
        'shanjian' => [
            'type'           => 'File',
            'path'           => app()->getRootPath() . '/runtime/log/shanjian/',
            'json'           => false,
            'format'         => '[%s][%s] %s',
        ],
        'publish' => [
            'type'           => 'File',
            'path'           => app()->getRootPath() . '/runtime/log/publish/',
            'json'           => false,
            'format'         => '[%s][%s] %s',
        ],
        // 其它日志通道配置
    ],

];

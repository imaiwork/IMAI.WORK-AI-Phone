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
            'path'           => app()->getRootPath() . '/runtime/log/socket/',
            'json'           => false,
            'single'         => false,
            'time_format'    => 'Y-m-d H:i:s',
            'format'         => '[%s][%s] %s',
            'apart_level'   =>  [
                'error', 'info', 'send', 'device', 'user', 'msg', 'msg_list', 'card', 'cron', 'bind', 'init', 'note', 'post','thumb',
                'task_delete', 'task_paused', 'task_recovery', 'task_send', 'task_record','channel', 'ws', 'heart', 'run','viral_rewrite',
                'precise_clues','add_wechat', 'group_buy'
            ],
        ],
        'wechat_socket' => [
            'type'           => 'File',
            'path'           => app()->getRootPath() . '/runtime/log/wechat_socket/',
            'json'           => false,
            'single'         => false,
            'time_format'    => 'Y-m-d H:i:s',
            'format'         => '[%s][%s] %s',
            'apart_level'   =>  ['error', 'info', 'send', 'device', 'user', 'msg', 'msg_list', 'notice', 'cron', 'bind', 'init', 'note', 'post'],
        ],
        'sv' => [//矩阵视频合成日志
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
            'apart_level'   =>  ['error', 'info', 'warning', 'publish', 'clues', 'add_wechat', 'active', 'take_over', 'verify_wechat', 'remove', 'unbind', 'update'],
        ],
        'map' => [//地图日志
            'type'           => 'File',
            'path'           => app()->getRootPath() . '/runtime/log/map/',
            'json'           => false,
            'format'         => '[%s][%s] %s',
        ],
        'clip' => [//ai智剪视频剪辑日志
            'type'           => 'File',
            'path'           => app()->getRootPath() . '/runtime/log/clip/',
            'json'           => false,
            'format'         => '[%s][%s] %s',
        ],
        'shanjian' => [//闪剪合成回调的日志,用于记录闪剪合成回调的参数
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
        'sora' => [
            'type'           => 'File',
            'path'           => app()->getRootPath() . '/runtime/log/sora/',
            'json'           => false,
            'format'         => '[%s][%s] %s',
        ],
        'puzzle' => [
            'type'           => 'File',
            'path'           => app()->getRootPath() . '/runtime/log/puzzle/',
            'json'           => false,
            'format'         => '[%s][%s] %s',
        ],
        'auto' => [
            'type'           => 'File',
            'path'           => app()->getRootPath() . '/runtime/log/auto/',
            'json'           => false,
            'format'         => '[%s][%s] %s',
            'apart_level'   =>  ['publish', 'create','viral_bottom', 'clue', 'active','add_wechat', 'like_reply', 'take_over', 'touch', 'claw', 'clip','img2'],
        ],
        'viral_manual' => [
            'type'           => 'File',
            'path'           => app()->getRootPath() . '/runtime/log/viral_manual/',
            'json'           => false,
            'format'         => '[%s][%s] %s',
        ],
        'ffmpeg' => [//ffmpeg转码日志
            'type'           => 'File',
            'path'           => app()->getRootPath() . '/runtime/log/ffmpeg/',
            'json'           => false,
            'format'         => '[%s][%s] %s',
        ],
        'video_slice' => [//视频切片日志
            'type'           => 'File',
            'path'           => app()->getRootPath() . '/runtime/log/video_slice/',
            'json'           => false,
            'format'         => '[%s][%s] %s',
            'realtime_write' => true,
        ],
        'upload_video' => [//视频上传参数日志
            'type'           => 'File',
            'path'           => app()->getRootPath() . '/runtime/log/upload_video/',
            'json'           => false,
            'format'         => '[%s][%s] %s',
        ],
        'upload_image' => [//图片上传参数日志
            'type'           => 'File',
            'path'           => app()->getRootPath() . '/runtime/log/upload_image/',
            'json'           => false,
            'format'         => '[%s][%s] %s',
        ],
        'notice' => [
            'type'           => 'File',
            'path'           => app()->getRootPath() . '/runtime/log/notice/',
            'json'           => false,
            'format'         => '[%s][%s] %s',
        ],
        'digital' => [
            'type'           => 'File',
            'path'           => app()->getRootPath() . '/runtime/log/digital/',
            'json'           => false,
            'format'         => '[%s][%s] %s',
        ],
        'douyin' => [//抖音js发布日志
            'type'           => 'File',
            'path'           => app()->getRootPath() . '/runtime/log/douyin/',
            'json'           => false,
            'format'         => '[%s][%s] %s',
        ],
        'automedia' => [//自动媒体日志
            'type'           => 'File',
            'path'           => app()->getRootPath() . '/runtime/log/automedia/',
            'json'           => false,
            'format'         => '[%s][%s] %s',
        ],
         'automediaSetting' => [//自动媒体设置日志
            'type'           => 'File',
            'path'           => app()->getRootPath() . '/runtime/log/automediaSetting/',
            'json'           => false,
            'format'         => '[%s][%s] %s',
        ],
        'storyboard' => [
            'type'           => 'File',
            'path'           => app()->getRootPath() . '/runtime/log/storyboard/',
            'json'           => false,
            'format'         => '[%s][%s] %s',
        ],
         'shanjiannotice' => [//闪剪合成回调的日志,用于记录闪剪合成回调的参数
            'type'           => 'File',
            'path'           => app()->getRootPath() . '/runtime/log/shanjiannotice/',
            'json'           => false,
            'format'         => '[%s][%s] %s',
            'realtime_write' => true,
        ],
        'shanjianQueue' => [//闪剪队列日志
           'type'           => 'File',
           'path'           => app()->getRootPath() . '/runtime/log/shanjianQueue/',
           'json'           => false,
           'format'         => '[%s][%s] %s',
        ],
        'shanjianClipTemplate' => [//闪剪视频风格模版删除日志
            'type'           => 'File',
            'path'           => app()->getRootPath() . '/runtime/log/shanjianClipTemplate/',
            'json'           => false,
            'format'         => '[%s][%s] %s',
        ],
         'wechatCircle' => [//微信朋友圈日志
            'type'           => 'File',
            'path'           => app()->getRootPath() . '/runtime/log/wechatCircle/',
            'json'           => false,
            'format'         => '[%s][%s] %s',
        ],
        'acquiringIntent' => [
            'type'           => 'File',
            'path'           => app()->getRootPath() . '/runtime/log/acquiringIntent/',
            'json'           => false,
            'format'         => '[%s][%s] %s',
        ],
         'ipVideoSynthesis' => [//ip人设视频合成日志
            'type'           => 'File',
            'path'           => app()->getRootPath() . '/runtime/log/ipVideoSynthesis/',
            'json'           => false,
            'format'         => '[%s][%s] %s',
        ],
        'ipPersona' => [
            'type'           => 'File',
            'path'           => app()->getRootPath() . '/runtime/log/ipPersona/',
            'json'           => false,
            'format'         => '[%s][%s] %s',
        ],
        'seedance' => [
            'type'           => 'File',
            'path'           => app()->getRootPath() . '/runtime/log/seedance/',
            'json'           => false,
            'format'         => '[%s][%s] %s',
        ],
        'wechatVideoSynthesis' => [//微信朋友圈视频合成日志
            'type'           => 'File',
            'path'           => app()->getRootPath() . '/runtime/log/wechatVideoSynthesis/',
            'json'           => false,
            'format'         => '[%s][%s] %s',
        ],
        'minimax' => [
            'type'           => 'File',
            'path'           => app()->getRootPath() . '/runtime/log/minimax/',
            'json'           => false,
            'format'         => '[%s][%s] %s',
        ],
        'glm' => [
            'type'           => 'File',
            'path'           => app()->getRootPath() . '/runtime/log/glm/',
            'json'           => false,
            'format'         => '[%s][%s] %s',
        ],
        'explosionVideoSynthesis' => [//爆款仿写文案视频合成日志
            'type'           => 'File',
            'path'           => app()->getRootPath() . '/runtime/log/explosionVideoSynthesis/',
            'json'           => false,
            'format'         => '[%s][%s] %s',
        ],
        'personaLog' => [//新增人设ai合成规则配置日志,用于之前新增人设，没有ai合成规则默认的配置
            'type'           => 'File',
            'path'           => app()->getRootPath() . '/runtime/log/personaLog/',
            'json'           => false,
            'format'         => '[%s][%s] %s',
        ],
        'grabMaterial' => [//文案仿写日志
            'type'           => 'File',
            'path'           => app()->getRootPath() . '/runtime/log/grabMaterial/',
            'json'           => false,
            'format'         => '[%s][%s] %s',
        ],
        'member' => [//会员到期检查日志
            'type'           => 'File',
            'path'           => app()->getRootPath() . '/runtime/log/member/',
            'json'           => false,
            'format'         => '[%s][%s] %s',
        ],
        'voiceAsr' => [// Minimax TTS 后闪剪 ASR 提交/回调日志
            'type'           => 'File',
            'path'           => app()->getRootPath() . '/runtime/log/voiceAsr/',
            'json'           => false,
            'format'         => '[%s][%s] %s',
        ],
        'manual_2img' => [//手动-爆款复刻小红书图文图片改写日志
            'type'           => 'File',
            'path'           => app()->getRootPath() . '/runtime/log/manual_2img/',
            'json'           => false,
            'format'         => '[%s][%s] %s',
        ],
        // 其它日志通道配置
    ],

];

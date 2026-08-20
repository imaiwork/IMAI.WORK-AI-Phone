<?php

return [
    // 触发切片的时长阈值，单位：秒
    'max_duration_threshold' => 10,

    // 每片目标时长，单位：秒
    'slice_duration' => 5,

    // 子视频时长小于等于该值时直接标记为分割失败，不执行 ffmpeg 切割
    'min_slice_duration' => 1,

    // 仅这些模块允许执行独立切片任务
    'module_scope' => ['character_ip'],

    // 切片队列名称（旧切片 + 素材单视频切割共用）
    // 消费示例：php think queue:work --queue video_slice --timeout 1900
    'queue_name' => env('QUEUE.VIDEO_SLICE', 'video_slice'),

    // 超过该时长仍未结束的批次由巡检任务整批回滚并退款
    'batch_timeout' => 3600,

    // 临时切片目录：runtime/temp/slices/{video_id}/
    'temp_path' => app()->getRuntimePath() . 'temp/slices',

    // 正式存储路径：uploads/slices/video/{date}/{video_id}/
    'storage_path' => 'uploads/slices/video',

    // 输出文件小于该字节数时视为失败
    'min_output_size' => 100,
];

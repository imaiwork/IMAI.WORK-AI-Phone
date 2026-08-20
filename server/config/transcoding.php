<?php

return [
    // 转码状态最长等待时间，单位：秒。默认 5 天。
    'stale_seconds' => env('TRANSCODING.STALE_SECONDS', 432000),
    // 生成视频下发前等待素材转码的最长时间，单位：秒。默认 30 分钟。
    'submit_stale_seconds' => env('TRANSCODING.SUBMIT_STALE_SECONDS', 1800),
];

<?php

return [
    'cache_ttl' => max(60, (int)env('work_data.cache_ttl', 3600)),
    'negative_cache_ttl' => max(30, (int)env('work_data.negative_cache_ttl', 120)),
    // 账号不存在这类永久性失败的负缓存时长，默认 6 小时；设为 0 则回落到 negative_cache_ttl
    'negative_cache_ttl_permanent' => max(0, (int)env('work_data.negative_cache_ttl_permanent', 21600)),
    // 上游临时故障（超时/限流/返回不完整）的负缓存时长，默认 1 小时；设为 0 则失败不记缓存
    'negative_cache_ttl_transient' => max(0, (int)env('work_data.negative_cache_ttl_transient', 3600)),
    'refresh_min_interval' => max(10, (int)env('work_data.refresh_min_interval', 60)),
    'lock_wait_seconds' => max(1, (int)env('work_data.lock_wait_seconds', 15)),
    'mid_timeout' => max(30, (int)env('work_data.mid_timeout', 180)),
];

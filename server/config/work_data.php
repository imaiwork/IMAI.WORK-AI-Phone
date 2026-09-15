<?php

return [
    'cache_ttl' => max(60, (int)env('work_data.cache_ttl', 3600)),
    'negative_cache_ttl' => max(30, (int)env('work_data.negative_cache_ttl', 120)),
    'refresh_min_interval' => max(10, (int)env('work_data.refresh_min_interval', 60)),
    'lock_wait_seconds' => max(1, (int)env('work_data.lock_wait_seconds', 15)),
    'mid_timeout' => max(30, (int)env('work_data.mid_timeout', 180)),
];

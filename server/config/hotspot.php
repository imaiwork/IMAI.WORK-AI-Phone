<?php

return [
    'ark_search_model' => (string)env('hotspot.ark_search_model', 'doubao-seed-character-260628'),
    'ark_writer_model' => (string)env('hotspot.ark_writer_model', 'doubao-seed-character-260628'),
    'http_timeout' => max(1, (int)env('hotspot.http_timeout', 120)),
    'hot_cache_ttl' => max(1, (int)env('hotspot.hot_cache_ttl', 600)),
    'ua' => 'imaiwork-hotspot/1.0',
    // 素材混剪（clips）补齐后仍不足时是否整单拦截；false 为警告照常出片
    'material_gap_fail_clips' => (bool)env('hotspot.material_gap_fail_clips', false),
    // 数字人口播（digital）补齐后仍不足时是否整单拦截；false 为警告照常出片
    'material_gap_fail_digital' => (bool)env('hotspot.material_gap_fail_digital', false),
    // AI 多轮补齐开关：首轮秒数锚点补完还缺时长时，继续拉 AI 找素材（会产生按次抓取费用）
    'ai_second_round_enabled' => (bool)env('hotspot.ai_second_round_enabled', true),
    // 首轮 AI 找素材按秒数锚点：视频条数 = 本轮目标秒数缺口折算，最低 2 条、最高熔断 50 条
    'ai_round1_duration_enabled' => (bool)env('hotspot.ai_round1_duration_enabled', true),
    // persona（纯人设勾选）模式素材不足时，从同人设可选素材池自动补入
    'persona_pool_autofill_enabled' => (bool)env('hotspot.persona_pool_autofill_enabled', true),
    // 已选素材没有任何视频时长可用时的保守平均条长（秒），用于换算 AI 追加条数
    'avg_video_sec_fallback' => max(1.0, (float)env('hotspot.avg_video_sec_fallback', 5.0)),
];

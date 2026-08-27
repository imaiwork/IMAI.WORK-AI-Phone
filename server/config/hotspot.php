<?php

return [
    'ark_search_model' => (string)env('hotspot.ark_search_model', 'doubao-seed-character-260628'),
    'ark_writer_model' => (string)env('hotspot.ark_writer_model', 'doubao-seed-character-260628'),
    'http_timeout' => max(1, (int)env('hotspot.http_timeout', 120)),
    'hot_cache_ttl' => max(1, (int)env('hotspot.hot_cache_ttl', 600)),
    'ua' => 'imaiwork-hotspot/1.0',
];

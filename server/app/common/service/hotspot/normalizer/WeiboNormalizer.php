<?php

namespace app\common\service\hotspot\normalizer;

use app\common\service\hotspot\HeatFormatter;

class WeiboNormalizer
{
    public static function normalize(mixed $data): array
    {
        $items = is_array($data) ? ($data['realtime'] ?? []) : [];
        $out = [];
        foreach (array_values($items) as $i => $it) {
            if (!is_array($it)) {
                continue;
            }
            $title = trim((string)($it['word'] ?? $it['note'] ?? ''));
            if ($title === '') {
                continue;
            }
            $heat = HeatFormatter::toInt($it['num'] ?? 0);
            $rank = HeatFormatter::toInt($it['realpos'] ?? 0) ?: ($i + 1);
            $out[] = [
                'id' => 'wb-' . $i . '-' . mb_substr($title, 0, 12),
                'platform' => 'weibo',
                'rank' => $rank,
                'title' => $title,
                'heat' => $heat,
                'heat_text' => HeatFormatter::text($heat),
                'category' => (string)($it['label_name'] ?? ''),
                'trend' => (($it['label_name'] ?? '') === '新') ? 'new' : 'flat',
                'url' => 'https://s.weibo.com/weibo?q=' . rawurlencode($title),
                'cover' => '',
                'days_on_board' => 0,
                'best_rank' => 0,
                'rank_diff' => 0,
            ];
        }
        return $out;
    }
}

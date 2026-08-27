<?php

namespace app\common\service\hotspot\normalizer;

use app\common\service\hotspot\HeatFormatter;

class XiaohongshuNormalizer
{
    public static function normalize(mixed $data): array
    {
        $items = is_array($data) ? ($data['items'] ?? []) : [];
        $out = [];
        foreach (array_values($items) as $i => $it) {
            if (!is_array($it)) {
                continue;
            }
            $title = trim((string)($it['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $heat = HeatFormatter::toInt($it['hot_value'] ?? 0);
            $rank = HeatFormatter::toInt($it['rank'] ?? 0) ?: ($i + 1);
            $out[] = [
                'id' => 'xhs-' . ($it['item_id'] ?? $i),
                'platform' => 'xiaohongshu',
                'rank' => $rank,
                'title' => $title,
                'heat' => $heat,
                'heat_text' => (string)($it['hot'] ?? HeatFormatter::text($heat)),
                'category' => '',
                'trend' => (string)($it['trend'] ?? 'flat'),
                'url' => (string)($it['url'] ?? ''),
                'cover' => '',
                'days_on_board' => 0,
                'best_rank' => 0,
                'rank_diff' => 0,
            ];
        }
        return $out;
    }
}

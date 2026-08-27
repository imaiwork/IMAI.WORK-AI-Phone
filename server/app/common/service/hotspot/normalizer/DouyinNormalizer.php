<?php

namespace app\common\service\hotspot\normalizer;

use app\common\service\hotspot\HeatFormatter;

class DouyinNormalizer
{
    public static function normalize(mixed $data): array
    {
        $items = is_array($data) ? ($data['current'] ?? []) : [];
        $out = [];
        foreach (array_values($items) as $i => $it) {
            if (!is_array($it)) {
                continue;
            }
            $title = trim((string)($it['topic_name'] ?? ''));
            if ($title === '') {
                continue;
            }
            $heat = HeatFormatter::toInt($it['topic_index'] ?? 0);
            $rank = HeatFormatter::toInt($it['rank'] ?? 0) ?: ($i + 1);
            $out[] = [
                'id' => 'douyin-' . $rank . '-' . mb_substr($title, 0, 12),
                'platform' => 'douyin',
                'rank' => $rank,
                'title' => $title,
                'heat' => $heat,
                'heat_text' => HeatFormatter::text($heat),
                'category' => (string)($it['category'] ?? ''),
                'trend' => (string)($it['rank_flag'] ?? '') === '1' ? 'new' : 'flat',
                'url' => '',
                'cover' => '',
                'days_on_board' => 0,
                'best_rank' => 0,
                'rank_diff' => 0,
            ];
        }
        return $out;
    }

    public static function normalizeRise(mixed $data): array
    {
        if (is_array($data) && !isset($data['objs']) && isset($data['data']) && is_array($data['data'])) {
            $data = $data['data'];
        }
        $items = is_array($data) ? ($data['objs'] ?? []) : [];
        $out = [];
        foreach (array_values($items) as $i => $it) {
            if (!is_array($it)) {
                continue;
            }
            $title = trim((string)($it['sentence'] ?? ''));
            if ($title === '') {
                continue;
            }
            $heat = HeatFormatter::toInt($it['hot_score'] ?? 0);
            $out[] = [
                'id' => 'douyin-rise-' . ($it['sentence_id'] ?? $i),
                'platform' => 'douyin',
                'rank' => $i + 1,
                'title' => $title,
                'heat' => $heat,
                'heat_text' => HeatFormatter::text($heat),
                'category' => (string)($it['sentence_tag_name'] ?? ''),
                'trend' => 'up',
                'url' => '',
                'cover' => '',
                'days_on_board' => 0,
                'best_rank' => 0,
                'rank_diff' => HeatFormatter::toInt($it['rank_diff'] ?? 0),
            ];
        }
        return $out;
    }
}

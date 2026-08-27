<?php

namespace app\common\service\hotspot\normalizer;

use app\common\service\hotspot\HeatFormatter;

class KuaishouNormalizer
{
    public static function normalize(mixed $data): array
    {
        $items = [];
        if (is_array($data)) {
            $items = (isset($data[0]) || $data === []) ? $data : ($data['list'] ?? []);
        }
        $out = [];
        foreach (array_values($items) as $i => $it) {
            if (!is_array($it)) {
                continue;
            }
            $title = trim((string)($it['name'] ?? $it['id'] ?? ''));
            if ($title === '') {
                continue;
            }
            $heat = HeatFormatter::toInt($it['viewCount'] ?? 0);
            // 上游不带 rank 字段时按数组顺序兜底，否则全部条目 rank=1 会污染周榜聚合
            $upstreamRank = HeatFormatter::toInt($it['rank'] ?? -1);
            $out[] = [
                'id' => 'ks-' . $i . '-' . mb_substr($title, 0, 12),
                'platform' => 'kuaishou',
                'rank' => $upstreamRank >= 0 ? $upstreamRank + 1 : $i + 1,
                'title' => $title,
                'heat' => $heat,
                'heat_text' => (string)($it['hotValue'] ?? ''),
                'category' => (string)($it['tagType'] ?? ''),
                'trend' => (($it['tagType'] ?? '') === '新') ? 'new' : 'flat',
                'url' => '',
                'cover' => (string)($it['poster'] ?? ''),
                'days_on_board' => 0,
                'best_rank' => 0,
                'rank_diff' => 0,
            ];
        }
        return $out;
    }
}

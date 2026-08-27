<?php

namespace app\common\service\hotspot;

use app\common\model\hotspot\HotspotDailySnapshot;

class HistoryService
{
    public const RANK_BASE = 31;

    public static function saveSnapshot(string $platform, array $topics, ?string $day = null): void
    {
        if ($topics === []) {
            HotspotLog::write('保存快照跳过：话题为空 平台=' . $platform);
            return;
        }
        $day = $day ?: date('Y-m-d');
        $now = time();
        $row = HotspotDailySnapshot::where([
            'platform' => $platform,
            'snap_date' => $day,
        ])->findOrEmpty();
        if ($row->isEmpty()) {
            try {
                HotspotDailySnapshot::create([
                    'platform' => $platform,
                    'snap_date' => $day,
                    'topics_json' => $topics,
                    'create_time' => $now,
                    'update_time' => $now,
                ]);
                HotspotLog::write(sprintf('保存快照新增：平台=%s 日期=%s 条数=%d', $platform, $day, count($topics)));
                return;
            } catch (\Throwable $e) {
                // 并发首次落快照撞 uk_plat_date 唯一键：改走更新，不让用户请求失败
                HotspotLog::write(sprintf('保存快照新增冲突转更新：平台=%s 日期=%s 原因=%s', $platform, $day, $e->getMessage()));
                $row = HotspotDailySnapshot::where([
                    'platform' => $platform,
                    'snap_date' => $day,
                ])->findOrEmpty();
                if ($row->isEmpty()) {
                    throw $e;
                }
            }
        }
        $row->topics_json = $topics;
        $row->update_time = $now;
        $row->save();
        HotspotLog::write(sprintf('保存快照更新：平台=%s 日期=%s 条数=%d', $platform, $day, count($topics)));
    }

    public static function availableDates(string $platform): array
    {
        $dates = HotspotDailySnapshot::where('platform', $platform)
            ->order('snap_date', 'desc')
            ->column('snap_date');
        $out = [];
        foreach ($dates as $date) {
            $date = (string)$date;
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $out[] = $date;
            }
        }
        return $out;
    }

    public static function loadDay(string $platform, string $day): array
    {
        $row = HotspotDailySnapshot::where([
            'platform' => $platform,
            'snap_date' => $day,
        ])->findOrEmpty();
        if ($row->isEmpty()) {
            HotspotLog::write(sprintf('读取日快照为空：平台=%s 日期=%s', $platform, $day));
            return [];
        }
        $topics = $row->topics_json;
        if (!is_array($topics) || $topics === []) {
            HotspotLog::write(sprintf('读取日快照话题为空：平台=%s 日期=%s', $platform, $day));
            return [];
        }
        return $topics;
    }

    public static function aggregateWeek(string $platform, ?string $endDay = null, int $limit = 30): array
    {
        $end = $endDay ? strtotime($endDay) : strtotime(date('Y-m-d'));
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $days[] = date('Y-m-d', $end - $i * 86400);
        }

        $used = [];
        $agg = [];
        foreach ($days as $d) {
            $topics = self::loadDay($platform, $d);
            if ($topics === []) {
                continue;
            }
            $used[] = $d;
            foreach ($topics as $t) {
                if (!is_array($t)) {
                    continue;
                }
                $key = trim((string)($t['title'] ?? ''));
                if ($key === '') {
                    continue;
                }
                if (!isset($agg[$key])) {
                    $agg[$key] = [
                        'title' => $key,
                        'platform' => $platform,
                        'category' => (string)($t['category'] ?? ''),
                        'url' => (string)($t['url'] ?? ''),
                        'cover' => (string)($t['cover'] ?? ''),
                        'heat_sum' => 0,
                        'peak_heat' => 0,
                        'best_rank' => 999,
                        'rank_score' => 0,
                        'days' => [],
                    ];
                }
                $agg[$key]['days'][$d] = true;
                $heat = HeatFormatter::toInt($t['heat'] ?? 0);
                $rank = HeatFormatter::toInt($t['rank'] ?? 0);
                $agg[$key]['heat_sum'] += $heat;
                $agg[$key]['peak_heat'] = max($agg[$key]['peak_heat'], $heat);
                if ($rank > 0) {
                    $agg[$key]['best_rank'] = min($agg[$key]['best_rank'], $rank);
                    $agg[$key]['rank_score'] += max(0, self::RANK_BASE - $rank);
                }
                if ($agg[$key]['category'] === '' && !empty($t['category'])) {
                    $agg[$key]['category'] = (string)$t['category'];
                }
                if ($agg[$key]['url'] === '' && !empty($t['url'])) {
                    $agg[$key]['url'] = (string)$t['url'];
                }
            }
        }

        if ($agg === []) {
            HotspotLog::write(sprintf(
                '周榜聚合为空：平台=%s 截止日期=%s 已扫日期=%s',
                $platform,
                $endDay ?: date('Y-m-d'),
                implode(',', $used)
            ));
            return [[], $used];
        }

        $hasHeat = false;
        foreach ($agg as $v) {
            if ($v['heat_sum'] > 0) {
                $hasHeat = true;
                break;
            }
        }

        $ordered = array_values($agg);
        usort($ordered, function ($a, $b) use ($hasHeat) {
            $left = $hasHeat ? $a['heat_sum'] : $a['rank_score'];
            $right = $hasHeat ? $b['heat_sum'] : $b['rank_score'];
            if ($left === $right) {
                return count($b['days']) <=> count($a['days']);
            }
            return $right <=> $left;
        });

        $out = [];
        foreach (array_slice($ordered, 0, $limit) as $i => $v) {
            $rank = $i + 1;
            $out[] = [
                'id' => $platform . '-week-' . $rank . '-' . mb_substr($v['title'], 0, 12),
                'platform' => $platform,
                'rank' => $rank,
                'title' => $v['title'],
                'heat' => $v['peak_heat'],
                'heat_text' => $hasHeat ? HeatFormatter::text($v['peak_heat']) : '',
                'category' => $v['category'],
                'trend' => 'flat',
                'url' => $v['url'],
                'cover' => $v['cover'],
                'days_on_board' => count($v['days']),
                'best_rank' => $v['best_rank'] === 999 ? 0 : $v['best_rank'],
                'rank_diff' => 0,
            ];
        }

        return [$out, $used];
    }

    public static function refreshTodayQuietly(string $platform): void
    {
        try {
            HotspotLog::write('静默刷新当天快照开始：平台=' . $platform);
            HotListService::fetchLiveDay($platform, 100, true);
            HotspotLog::write('静默刷新当天快照完成：平台=' . $platform);
        } catch (\Throwable $e) {
            HotspotLog::exception('刷新当天热榜快照失败', $e);
        }
    }
}

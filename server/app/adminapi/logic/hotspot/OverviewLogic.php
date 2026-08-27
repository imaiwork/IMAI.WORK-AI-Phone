<?php

namespace app\adminapi\logic\hotspot;

use app\api\logic\hotspot\HotspotLogic;
use app\common\logic\BaseLogic;
use app\common\model\hotspot\HotspotAnalysis;
use app\common\model\hotspot\HotspotCreation;
use app\common\model\hotspot\HotspotTask;
use app\common\service\hotspot\HistoryService;
use app\common\service\hotspot\HotListService;
use app\common\service\hotspot\HotspotLog;

class OverviewLogic extends BaseLogic
{
    public static function index(): array
    {
        $today = strtotime(date('Y-m-d'));
        $start = strtotime(date('Y-m-d', strtotime('-13 days')));

        $analysesTotal = HotspotAnalysis::count();
        $creationsTotal = HotspotCreation::count();
        $analysesToday = HotspotAnalysis::where('create_time', '>=', $today)->count();
        $creationsToday = HotspotCreation::where('create_time', '>=', $today)->count();

        $data = [
            'totals' => [
                'analyses' => $analysesTotal,
                'creations' => $creationsTotal,
                'analyses_today' => $analysesToday,
                'creations_today' => $creationsToday,
                'tasks_running' => HotspotTask::where('status', 'running')->count(),
                'tasks_done' => HotspotTask::where('status', 'done')->count(),
            ],
            'trend' => self::trend($start),
            'platform_dist' => self::platformDist(),
            'persona_rank' => self::personaRank(),
            'recent' => self::recent(),
            'snapshots' => self::snapshots(),
            'health' => HotspotLogic::health(),
        ];
        HotspotLog::write(sprintf(
            '后台工作台统计：分析=%d 创作=%d 今日分析=%d 今日创作=%d',
            $analysesTotal,
            $creationsTotal,
            $analysesToday,
            $creationsToday
        ));
        return $data;
    }

    private static function trend(int $start): array
    {
        $days = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime('-' . $i . ' days'));
            $days[$date] = ['date' => $date, 'analyses' => 0, 'creations' => 0];
        }

        foreach (self::countByDay(HotspotAnalysis::class, $start) as $date => $cnt) {
            if (isset($days[$date])) {
                $days[$date]['analyses'] = $cnt;
            }
        }
        foreach (self::countByDay(HotspotCreation::class, $start) as $date => $cnt) {
            if (isset($days[$date])) {
                $days[$date]['creations'] = $cnt;
            }
        }
        return array_values($days);
    }

    private static function countByDay(string $model, int $start): array
    {
        $rows = $model::where('create_time', '>=', $start)
            ->fieldRaw("FROM_UNIXTIME(create_time, '%Y-%m-%d') as day, COUNT(*) as cnt")
            ->group('day')
            ->select();
        $map = [];
        foreach ($rows as $row) {
            $day = is_array($row) ? (string)($row['day'] ?? '') : (string)($row->day ?? '');
            $cnt = is_array($row) ? (int)($row['cnt'] ?? 0) : (int)($row->cnt ?? 0);
            if ($day !== '') {
                $map[$day] = $cnt;
            }
        }
        return $map;
    }

    private static function platformDist(): array
    {
        $rows = HotspotAnalysis::fieldRaw("IF(platform = '' OR platform IS NULL, 'douyin', platform) as platform, COUNT(*) as count")
            ->group("IF(platform = '' OR platform IS NULL, 'douyin', platform)")
            ->order('count', 'desc')
            ->select();
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'platform' => is_array($row) ? (string)($row['platform'] ?? 'douyin') : (string)($row->platform ?? 'douyin'),
                'count' => is_array($row) ? (int)($row['count'] ?? 0) : (int)($row->count ?? 0),
            ];
        }
        return $out;
    }

    private static function personaRank(): array
    {
        $nameExpr = "IFNULL(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(persona_json, '$.name')), ''), '未命名')";
        $rows = HotspotAnalysis::fieldRaw($nameExpr . ' as name, IFNULL(JSON_UNQUOTE(JSON_EXTRACT(persona_json, \'$.avatar\')), \'\') as avatar, COUNT(*) as count, AVG(fit_score) as avg_fit')
            ->group($nameExpr)
            ->order('count', 'desc')
            ->limit(6)
            ->select();
        $out = [];
        foreach ($rows as $row) {
            $count = is_array($row) ? (int)($row['count'] ?? 0) : (int)($row->count ?? 0);
            $avg = is_array($row) ? (float)($row['avg_fit'] ?? 0) : (float)($row->avg_fit ?? 0);
            $out[] = [
                'name' => is_array($row) ? (string)($row['name'] ?? '未命名') : (string)($row->name ?? '未命名'),
                'avatar' => is_array($row) ? (string)($row['avatar'] ?? '') : (string)($row->avatar ?? ''),
                'count' => $count,
                'avg_fit' => $count > 0 ? (int)round($avg) : 0,
            ];
        }
        return $out;
    }

    private static function recent(): array
    {
        $items = [];
        foreach (HotspotAnalysis::order('id', 'desc')->limit(12)->select() as $row) {
            $persona = is_array($row->persona_json) ? $row->persona_json : [];
            $items[] = [
                'kind' => 'analysis',
                'time' => self::unixOf($row),
                'topic' => (string)$row->topic,
                'who' => (string)($persona['name'] ?? ''),
                'extra' => '契合度 ' . (int)$row->fit_score,
            ];
        }
        foreach (HotspotCreation::order('id', 'desc')->limit(12)->select() as $row) {
            $items[] = [
                'kind' => 'creation',
                'time' => self::unixOf($row),
                'topic' => (string)$row->topic,
                'who' => (string)$row->persona_name,
                'extra' => ((int)$row->word_count) . ' 字',
            ];
        }
        usort($items, static function ($a, $b) {
            return $b['time'] <=> $a['time'];
        });
        return array_slice($items, 0, 8);
    }

    private static function snapshots(): array
    {
        $out = [];
        foreach (HotListService::PLATFORMS as $item) {
            $dates = HistoryService::availableDates((string)$item['key']);
            $out[] = [
                'platform' => (string)$item['key'],
                'label' => (string)$item['label'],
                'days' => count($dates),
                'latest' => $dates[0] ?? '',
            ];
        }
        return $out;
    }

    private static function dayOf(mixed $row): string
    {
        $ts = self::unixOf($row);
        return $ts > 0 ? date('Y-m-d', $ts) : '';
    }

    private static function unixOf(mixed $row): int
    {
        $raw = 0;
        if (is_object($row) && method_exists($row, 'getData')) {
            $raw = $row->getData('create_time');
        } elseif (is_object($row)) {
            $raw = $row->create_time ?? 0;
        }
        if (is_numeric($raw)) {
            return (int)$raw;
        }
        $ts = strtotime((string)$raw);
        return $ts > 0 ? $ts : 0;
    }
}

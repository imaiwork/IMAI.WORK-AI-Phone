<?php

namespace app\common\service\workData;

/**
 * 作品数据跨页合并 + 本地窗口聚合。
 *
 * 中台只按单页算 7/30/90 天窗口；翻页回填后必须在本地按合并后的作品重算，
 * 否则窗口数字只反映最后一页。算法与中台 station_hotspot_analytics.go 的
 * stationHotspotAnalyticsWindow 保持一致（含起点 = 今天-(days-1)、逐日升序序列）。
 */
class WorkDataAggregator
{
    public const WINDOW_DAYS = [7, 30, 90];

    /** 合并后本地保留的最大作品数，防长期累积撑大缓存 */
    public const MAX_ITEMS = 500;

    /**
     * 按作品 id 去重合并多页（后到的快照覆盖先到的计数），按发布时间倒序排列。
     *
     * @param list<array<string, mixed>|null> $pages
     * @return array{items: list<array<string, mixed>>, account: array<string, mixed>, playCountAvailable: bool, playCountNote: string}
     */
    public static function mergePages(array $pages): array
    {
        $items = [];
        $account = [];
        $playAvailable = false;
        $playNote = '';

        foreach ($pages as $page) {
            if (!is_array($page)) {
                continue;
            }
            if ($account === [] && is_array($page['account'] ?? null)) {
                $account = $page['account'];
            }
            if (!empty($page['playCountAvailable'])) {
                $playAvailable = true;
            }
            $note = trim((string)($page['playCountNote'] ?? ''));
            if ($note !== '') {
                $playNote = $note;
            }
            foreach ((array)($page['items'] ?? []) as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $key = self::itemKey($item);
                if ($key === '') {
                    $items[] = $item;
                    continue;
                }
                $items[$key] = $item;
            }
        }

        $items = array_values($items);
        self::sortByPublishedAtDesc($items);
        if (count($items) > self::MAX_ITEMS) {
            $items = array_slice($items, 0, self::MAX_ITEMS);
        }

        return [
            'items' => $items,
            'account' => $account,
            'playCountAvailable' => $playAvailable,
            'playCountNote' => $playNote,
        ];
    }

    /**
     * @param list<array<string, mixed>> $items
     * @return array<string, array<string, mixed>>
     */
    public static function windows(array $items, ?int $now = null): array
    {
        $now = $now ?? time();
        $windows = [];
        foreach (self::WINDOW_DAYS as $days) {
            $windows[(string)$days] = self::window($items, $days, $now);
        }
        return $windows;
    }

    /**
     * @param list<array<string, mixed>> $items
     * @return array<string, mixed>
     */
    private static function window(array $items, int $days, int $now): array
    {
        $today = strtotime('today', $now) ?: $now;
        $start = strtotime('-' . ($days - 1) . ' day', $today) ?: $today;

        $daily = [];
        $dailyPlay = [];
        $keys = [];
        for ($i = 0; $i < $days; $i++) {
            $key = date('Y-m-d', (int)strtotime('+' . $i . ' day', $start));
            $keys[] = $key;
            $daily[$key] = 0;
            $dailyPlay[$key] = 0;
        }

        $published = 0;
        $digg = 0;
        $comment = 0;
        $share = 0;
        $collect = 0;
        $play = 0;

        foreach ($items as $item) {
            $publishedAt = trim((string)($item['publishedAt'] ?? ''));
            if ($publishedAt === '') {
                continue;
            }
            $ts = strtotime(str_replace('T', ' ', $publishedAt));
            if ($ts === false) {
                continue;
            }
            $day = (int)strtotime('today', $ts);
            if ($day < $start || $day > $today) {
                continue;
            }
            $published++;
            $itemPlay = isset($item['play']) && is_numeric($item['play']) ? (int)$item['play'] : 0;
            $play += $itemPlay;
            $itemDigg = (int)($item['digg'] ?? 0);
            $itemComment = (int)($item['comment'] ?? 0);
            $itemShare = (int)($item['share'] ?? 0);
            $itemCollect = (int)($item['collect'] ?? 0);
            $digg += $itemDigg;
            $comment += $itemComment;
            $share += $itemShare;
            $collect += $itemCollect;

            $key = date('Y-m-d', $day);
            if (array_key_exists($key, $daily)) {
                $daily[$key] += $itemDigg + $itemComment + $itemShare + $itemCollect;
                $dailyPlay[$key] += $itemPlay;
            }
        }

        $series = [];
        $playSeries = [];
        foreach ($keys as $key) {
            $series[] = ['date' => $key, 'value' => $daily[$key]];
            $playSeries[] = ['date' => $key, 'value' => $dailyPlay[$key]];
        }

        return [
            'days' => $days,
            'published' => $published,
            'digg' => $digg,
            'comment' => $comment,
            'share' => $share,
            'collect' => $collect,
            'interaction' => $digg + $comment + $share + $collect,
            'play' => $play,
            'series' => $series,
            'playSeries' => $playSeries,
        ];
    }

    /**
     * @param array<string, mixed> $item
     */
    private static function itemKey(array $item): string
    {
        $id = $item['id'] ?? '';
        if (!is_scalar($id)) {
            return '';
        }
        $key = trim((string)$id);
        return $key === '0' ? '' : $key;
    }

    /**
     * @param list<array<string, mixed>> $items
     */
    private static function sortByPublishedAtDesc(array &$items): void
    {
        usort($items, static function ($a, $b) {
            return strcmp((string)($b['publishedAt'] ?? ''), (string)($a['publishedAt'] ?? ''));
        });
    }
}

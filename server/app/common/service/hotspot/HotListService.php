<?php

namespace app\common\service\hotspot;

use app\common\service\ToolsService;
use app\common\service\hotspot\normalizer\DouyinNormalizer;
use app\common\service\hotspot\normalizer\KuaishouNormalizer;
use app\common\service\hotspot\normalizer\WeiboNormalizer;
use app\common\service\hotspot\normalizer\XiaohongshuNormalizer;
use think\facade\Cache;

class HotListService
{
    // 目前产品上仅开放抖音；快手/小红书/微博的归一化器保留，放开时在此补回并同步 HotspotValidate 的 platform 白名单
    public const PLATFORMS = [
        ['key' => 'douyin', 'label' => '抖音'],
    ];

    public static function getHot(string $platform, string $period, string $day, int $limit): array
    {
        $today = date('Y-m-d');
        $limit = max(1, min(100, $limit));

        if ($period === 'rise') {
            if ($platform !== 'douyin') {
                HotspotLog::write('热榜分支拒绝：飙升榜仅支持抖音，当前平台=' . $platform);
                throw new HotspotUpstreamException('飙升榜目前仅支持抖音');
            }
            HotspotLog::write('热榜分支：抖音飙升榜 条数=' . $limit);
            [$topics, $cached] = self::fetchRise($limit);
            return self::wrap($platform, $period, $today, $topics, $cached, true, []);
        }

        if ($period === 'week') {
            $endDay = ($day !== '' && $day !== $today) ? $day : $today;
            HotspotLog::write(sprintf('热榜分支：周榜 平台=%s 截止日期=%s 条数=%d', $platform, $endDay, $limit));
            if ($endDay === $today) {
                HistoryService::refreshTodayQuietly($platform);
            }
            [$topics, $used] = HistoryService::aggregateWeek($platform, $endDay, $limit);
            HotspotLog::write(sprintf(
                '周榜聚合完成：平台=%s 覆盖天数=%d 条数=%d',
                $platform,
                count($used),
                count($topics)
            ));
            return self::wrap($platform, $period, $endDay, $topics, false, false, $used);
        }

        if ($day !== '' && $day !== $today) {
            HotspotLog::write(sprintf('热榜分支：历史日榜 平台=%s 日期=%s 条数=%d', $platform, $day, $limit));
            $topics = HistoryService::loadDay($platform, $day);
            if ($topics === []) {
                HotspotLog::write('历史日榜无快照：平台=' . $platform . ' 日期=' . $day);
                throw new HotspotUpstreamException('没有 ' . $day . ' 的快照。历史从服务开始运行后才逐日累积。');
            }
            return self::wrap($platform, $period, $day, array_slice($topics, 0, $limit), false, false, []);
        }

        HotspotLog::write(sprintf('热榜分支：当日实时 平台=%s 条数=%d', $platform, $limit));
        try {
            [$topics, $cached] = self::fetchLiveDay($platform, $limit, true);
            return self::wrap($platform, $period, $today, $topics, $cached, true, []);
        } catch (HotspotUpstreamException $e) {
            $fallback = HistoryService::loadDay($platform, $today);
            if ($fallback !== []) {
                HotspotLog::write(sprintf(
                    '当日实时失败后回落快照：平台=%s 条数=%d 原因=%s',
                    $platform,
                    count($fallback),
                    $e->getMessage()
                ));
                return self::wrap($platform, $period, $today, array_slice($fallback, 0, $limit), false, false, []);
            }
            HotspotLog::write('当日实时失败且无快照：平台=' . $platform . ' 原因=' . $e->getMessage());
            throw $e;
        }
    }

    public static function fetchLiveDay(string $platform, int $limit, bool $writeSnapshot): array
    {
        $ttl = (int)config('hotspot.hot_cache_ttl', 600);
        $cacheKey = self::dayCacheKey($platform);
        $cached = Cache::store('redis')->get($cacheKey);
        if (self::hasTopics($cached)) {
            HotspotLog::write(sprintf(
                '日榜缓存命中：平台=%s 缓存条数=%d 返回=%d',
                $platform,
                count($cached),
                $limit
            ));
            return [array_slice($cached, 0, $limit), true];
        }

        $today = date('Y-m-d');
        $fromDb = HistoryService::loadDay($platform, $today);
        if ($fromDb !== []) {
            HotspotLog::write(sprintf(
                '日榜回源数据表：平台=%s 条数=%d 返回=%d',
                $platform,
                count($fromDb),
                $limit
            ));
            Cache::store('redis')->set($cacheKey, $fromDb, $ttl);
            return [array_slice($fromDb, 0, $limit), false];
        }

        if (self::hitNegativeCache($cacheKey)) {
            HotspotLog::write('日榜负缓存命中（上游近期为空或失败）：平台=' . $platform);
            return [[], false];
        }

        HotspotLog::write('日榜缓存与数据表均未命中，开始拉取上游：平台=' . $platform);
        $topics = self::rebuildWithLock($cacheKey, $ttl, function () use ($platform) {
            $payload = match ($platform) {
                'douyin' => ToolsService::TikHub()->fetchDouyinHotTopic(),
                'kuaishou' => ToolsService::TikHub()->fetchKuaishouHotList(),
                'xiaohongshu' => ToolsService::TikHub()->fetchXiaohongshuHotList(),
                'weibo' => ToolsService::TikHub()->fetchWeiboHotSearch(),
                default => throw new HotspotUpstreamException('不支持的平台'),
            };

            return match ($platform) {
                'douyin' => DouyinNormalizer::normalize($payload['data'] ?? []),
                'kuaishou' => KuaishouNormalizer::normalize($payload['data'] ?? []),
                'xiaohongshu' => XiaohongshuNormalizer::normalize($payload['data'] ?? []),
                default => WeiboNormalizer::normalize($payload['data'] ?? []),
            };
        });

        HotspotLog::write(sprintf(
            '日榜上游归一化完成：平台=%s 条数=%d 写入快照=%s',
            $platform,
            count($topics),
            $writeSnapshot ? '是' : '否'
        ));
        if ($topics === []) {
            HotspotLog::write('日榜上游结果为空，跳过回写：平台=' . $platform);
            return [[], false];
        }

        if ($writeSnapshot) {
            HistoryService::saveSnapshot($platform, $topics);
        }
        return [array_slice($topics, 0, $limit), false];
    }

    public static function fetchRise(int $limit): array
    {
        $ttl = (int)config('hotspot.hot_cache_ttl', 600);
        $cacheKey = 'hotspot:rise:douyin:' . date('Y-m-d');
        $cached = Cache::store('redis')->get($cacheKey);
        if (self::hasTopics($cached)) {
            HotspotLog::write(sprintf('飙升榜缓存命中：缓存条数=%d 返回=%d', count($cached), $limit));
            return [array_slice($cached, 0, $limit), true];
        }

        if (self::hitNegativeCache($cacheKey)) {
            HotspotLog::write('飙升榜负缓存命中（上游近期为空或失败）');
            return [[], false];
        }

        HotspotLog::write('飙升榜缓存未命中，开始拉取上游');
        // 固定按最大条数拉取并整体缓存，避免首个小 limit 请求把缓存截断（详见日榜同款逻辑）
        $topics = self::rebuildWithLock($cacheKey, $ttl, function () {
            $payload = ToolsService::TikHub()->fetchDouyinRiseList(100);
            return DouyinNormalizer::normalizeRise($payload['data'] ?? []);
        });
        HotspotLog::write('飙升榜上游归一化完成：条数=' . count($topics));
        if ($topics === []) {
            return [[], false];
        }
        return [array_slice($topics, 0, $limit), false];
    }

    public static function dayCacheKey(string $platform): string
    {
        // 键含日期：跨零点后不会把昨天的榜单当今天返回，也不推迟当天首个快照
        return 'hotspot:hot:' . $platform . ':' . date('Y-m-d');
    }

    /**
     * 缓存重建互斥：拿到锁的请求拉上游并回填缓存；拿不到锁的短暂等待缓存出现，
     * 超时抛业务异常（getHot 会回落当日快照）。上游异常/空结果写短负缓存，
     * 防止上游故障时并发请求全部同步打上游占满 FPM。
     *
     * @param callable():array $fetcher
     */
    private static function rebuildWithLock(string $cacheKey, int $ttl, callable $fetcher): array
    {
        $handler = Cache::store('redis')->handler();
        $lockKey = 'hotspot:lock:' . md5($cacheKey);
        $locked = (bool)$handler->set($lockKey, 1, ['nx', 'ex' => 60]);
        if (!$locked) {
            for ($i = 0; $i < 8; $i++) {
                usleep(250000);
                $cached = Cache::store('redis')->get($cacheKey);
                if (self::hasTopics($cached)) {
                    HotspotLog::write('等待重建后缓存命中：键=' . $cacheKey);
                    return $cached;
                }
            }
            throw new HotspotUpstreamException('热榜正在更新，请稍后刷新');
        }

        try {
            $topics = $fetcher();
            if (!is_array($topics)) {
                $topics = [];
            }
            if ($topics === []) {
                self::setNegativeCache($cacheKey, 60);
            } else {
                Cache::store('redis')->set($cacheKey, $topics, $ttl);
            }
            return $topics;
        } catch (\Throwable $e) {
            self::setNegativeCache($cacheKey, 30);
            throw $e;
        } finally {
            $handler->del($lockKey);
        }
    }

    private static function hitNegativeCache(string $cacheKey): bool
    {
        return (bool)Cache::store('redis')->get('hotspot:empty:' . md5($cacheKey));
    }

    private static function setNegativeCache(string $cacheKey, int $ttl): void
    {
        Cache::store('redis')->set('hotspot:empty:' . md5($cacheKey), 1, $ttl);
    }

    private static function hasTopics(mixed $topics): bool
    {
        return is_array($topics) && $topics !== [];
    }

    private static function wrap(string $platform, string $period, string $date, array $topics, bool $cached, bool $live, array $covered): array
    {
        return [
            'platform' => $platform,
            'period' => $period,
            'date' => $date,
            'fetched_at' => (int)round(microtime(true) * 1000),
            'cached' => $cached,
            'live' => $live,
            'covered_dates' => $covered,
            'topics' => $topics,
        ];
    }
}

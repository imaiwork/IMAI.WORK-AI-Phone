<?php

namespace app\common\service\workData;

use app\common\service\hotspot\HotspotUpstreamException;
use app\common\service\ToolsService;
use app\common\model\aiPersona\AiPersona;
use think\facade\Db;

class WorkDataFetchService
{
    private const MID_ANALYTICS_PATH = '/api/hotspot/analytics';

    /**
     * @return array<string, mixed>
     */
    public static function fetch(int $userId, int $personaId, string $platform, bool $refresh = false, ?string $account = null, bool $scheduled = false, ?string $cursor = null, bool $skipCharge = false): array
    {
        if (!in_array($platform, [WorkDataSupport::PLATFORM_DOUYIN, WorkDataSupport::PLATFORM_XHS], true)) {
            throw new WorkDataException('不支持的平台');
        }

        // cursor 非空 = 翻页拉历史页：本质是一次付费取数，跳过缓存/节流/负缓存，
        // 且不覆盖主缓存（单页只是片段，合并由 backfill 负责）。
        $cursor = is_string($cursor) ? trim($cursor) : '';
        $isPage = $cursor !== '';
        if ($isPage) {
            $refresh = true;
        }

        $resolved = PersonaBindService::resolve($userId, $personaId, $platform, $account);
        $account = $resolved['account'];
        if ($account === null || $account === '') {
            throw new WorkDataException(
                '该人设还没绑定' . WorkDataSupport::platformName($platform) . '账号',
                0
            );
        }

        // 浏览页面永不触发付费取数；后台调度必须重新校验当前人设开关。
        if (!$refresh && (!$scheduled || !self::canAutoSync($resolved['persona']))) {
            return self::lastPayload($platform, $account);
        }

        $ttl = (int)config('work_data.cache_ttl', 3600);
        $refreshInterval = (int)config('work_data.refresh_min_interval', 60);
        $lockWait = (int)config('work_data.lock_wait_seconds', 15);

        if (!$refresh) {
            $cached = WorkDataCache::getPayload($platform, $account);
            if (is_array($cached)) {
                $cached['cached'] = true;
                WorkDataLog::write(sprintf('作品数据缓存命中：平台=%s 账号=%s 用户=%d', $platform, $account, $userId));
                return $cached;
            }
        } else {
            $existing = WorkDataCache::getPayload($platform, $account);
            if (!$isPage && is_array($existing) && WorkDataCache::shouldThrottleRefresh($platform, $account, $refreshInterval)) {
                $existing['cached'] = true;
                WorkDataLog::write(sprintf('作品数据刷新节流：平台=%s 账号=%s 用户=%d', $platform, $account, $userId));
                return $existing;
            }
        }

        $negative = WorkDataCache::getNegative($platform, $account);
        if (is_string($negative) && !$refresh) {
            throw new WorkDataException($negative);
        }

        WorkDataChargeService::precheck($userId);
        if (!WorkDataCache::acquireLock($platform, $account, $lockWait)) {
            $waited = WorkDataCache::getPayload($platform, $account);
            if (is_array($waited)) {
                $waited['cached'] = true;
                return $waited;
            }
            throw new WorkDataException('作品数据正在拉取，请稍后重试');
        }

        try {
            if ($scheduled && !$refresh) {
                $resolved = PersonaBindService::resolve($userId, $personaId, $platform, $account);
                if (!self::canAutoSync($resolved['persona'])) {
                    return self::lastPayload($platform, $account);
                }
            }
            $again = WorkDataCache::getPayload($platform, $account);
            if (is_array($again) && !$refresh) {
                $again['cached'] = true;
                return $again;
            }
            if ($refresh && !$isPage && is_array($again)
                && WorkDataCache::shouldThrottleRefresh($platform, $account, $refreshInterval)) {
                $again['cached'] = true;
                return $again;
            }

            WorkDataLog::write(sprintf(
                '作品数据开始取数：平台=%s 账号=%s 用户=%d 刷新=%s',
                $platform,
                $account,
                $userId,
                $refresh ? '是' : '否'
            ));
            WorkDataLog::write(sprintf(
                '中台请求：路径=%s 参数=%s',
                self::MID_ANALYTICS_PATH,
                WorkDataLog::encode(array_filter([
                    'platform' => $platform,
                    'account' => $account,
                    'cursor' => $cursor,
                ], static fn($v) => $v !== ''))
            ));

            try {
                $resp = ToolsService::TikHub()->fetchWorkDataAnalytics($platform, $account, $cursor);
            } catch (HotspotUpstreamException $e) {
                $raw = $e->getMessage();
                WorkDataLog::write(sprintf(
                    '中台响应失败：路径=%s 原因=%s',
                    self::MID_ANALYTICS_PATH,
                    $raw
                ));
                throw new WorkDataException(WorkDataException::translateUpstream($raw));
            }

            if (!array_key_exists('platform', $resp)
                || !is_array($resp['account'] ?? null)
                || !is_array($resp['items'] ?? null)
                || !is_array($resp['windows'] ?? null)
            ) {
                throw new WorkDataException(WorkDataException::translateUpstream('上游返回字段不完整'));
            }

            $fetchedAt = trim((string)($resp['fetchedAt'] ?? ''));
            $payload = [
                'platform' => $resp['platform'],
                'account' => $resp['account'],
                'items' => $resp['items'],
                'windows' => $resp['windows'],
                'truncated' => (bool)($resp['truncated'] ?? false),
                'pageSize' => (int)($resp['pageSize'] ?? 0),
                'playCountAvailable' => (bool)($resp['playCountAvailable'] ?? false),
                'playCountNote' => (string)($resp['playCountNote'] ?? ''),
                'fetchedAt' => $fetchedAt !== '' ? $fetchedAt : WorkDataSupport::fetchedAt(),
                'nextCursor' => (string)($resp['nextCursor'] ?? ''),
                'cached' => false,
            ];
            WorkDataLog::write(sprintf(
                '中台响应：路径=%s 返回=%s',
                self::MID_ANALYTICS_PATH,
                WorkDataLog::encode($payload)
            ));

            // 每次实际取数独立计费，不能按小时去重吞掉手动刷新的费用。
            // 回填时逐页取数由 backfill 统一结算一次，这里跳过逐页扣费。
            if (!$skipCharge) {
                $ref = 'work_data-' . bin2hex(random_bytes(16));
                $settle = static function () use ($userId, $ref, $platform, $account, $refresh): void {
                    WorkDataChargeService::settle($userId, $ref, [
                        '平台' => WorkDataSupport::platformName($platform),
                        '账号' => $account,
                        '通道' => '中台',
                        '触发方式' => $refresh ? '手动刷新' : '定时同步',
                    ]);
                };
                if ($scheduled && !$refresh) {
                    // 只在结算时持有行锁：关闭成功后，在途同步也不能继续扣费。
                    $charged = Db::transaction(static function () use ($userId, $personaId, $settle): bool {
                        $persona = AiPersona::where('id', $personaId)->where('user_id', $userId)
                            ->whereNull('delete_time')->lock(true)->find();
                        if (!$persona || !self::canAutoSync($persona)) {
                            return false;
                        }
                        $settle();
                        return true;
                    });
                    if (!$charged) {
                        return self::lastPayload($platform, $account);
                    }
                } else {
                    $settle();
                }
            }

            // 翻页页只是片段，不能覆盖主缓存；合并写入由 backfill 统一处理。
            if (!$isPage) {
                WorkDataCache::setPayload($platform, $account, $payload, $ttl);
                if ($refresh) {
                    WorkDataCache::markRefreshed($platform, $account, $refreshInterval);
                }
            }
            WorkDataLog::write(sprintf(
                '作品数据取数完成：平台=%s 账号=%s 用户=%d 作品数=%d 播放可用=%s',
                $platform,
                $account,
                $userId,
                count($payload['items']),
                $payload['playCountAvailable'] ? '是' : '否'
            ));
            return $payload;
        } catch (WorkDataException $e) {
            $failureTtl = self::negativeTtl($e->getMessage());
            if ($failureTtl > 0) {
                WorkDataCache::setNegative($platform, $account, $e->getMessage(), $failureTtl);
            }
            throw $e;
        } finally {
            WorkDataCache::releaseLock($platform, $account);
        }
    }

    /**
     * 历史回填 / 定时同步：让中台一次请求内循环翻页合并，站长端只需调一次。
     * 中台按 work_data_fetch fixed 扣一次费，站长端自己也结算一次（独立的 work_data_fetch 场景码）。
     * 中台已兜底 max_pages ∈ [1,5]、去重合并、窗口重算、500 条上限，站长端不再做翻页循环。
     *
     * @param bool $scheduled true = 定时同步：重新校验人设开关、遵守失败退避、在途关闭不结算
     * @return array<string, mixed>
     */
    public static function backfill(int $userId, int $personaId, string $platform, ?string $account = null, int $maxPages = 0, bool $scheduled = false): array
    {
        if (!in_array($platform, [WorkDataSupport::PLATFORM_DOUYIN, WorkDataSupport::PLATFORM_XHS], true)) {
            throw new WorkDataException('不支持的平台');
        }
        $limit = $maxPages > 0 ? $maxPages : (int)config('work_data.backfill_max_pages', 5);
        $limit = max(1, min($limit, 5));

        $resolved = PersonaBindService::resolve($userId, $personaId, $platform, $account);
        $account = $resolved['account'];
        if ($account === null || $account === '') {
            throw new WorkDataException('该人设还没绑定' . WorkDataSupport::platformName($platform) . '账号');
        }

        // 后台调度必须重新校验当前人设开关，关闭/冻结后不再付费取数。
        if ($scheduled && !self::canAutoSync($resolved['persona'])) {
            return self::lastPayload($platform, $account);
        }

        // 定时同步是每分钟一次的 cron：失败退避窗口内直接跳过，避免反复请求中台。
        if ($scheduled) {
            $negative = WorkDataCache::getNegative($platform, $account);
            if (is_string($negative) && $negative !== '') {
                throw new WorkDataException($negative);
            }
        }

        WorkDataChargeService::precheck($userId);

        // 与单页取数共用同一把账号锁：定时同步与手动刷新不会同时付费取数。
        $lockWait = (int)config('work_data.lock_wait_seconds', 15);
        if (!WorkDataCache::acquireLock($platform, $account, $lockWait)) {
            if ($scheduled) {
                return self::lastPayload($platform, $account);
            }
            $waited = WorkDataCache::getPayload($platform, $account);
            if (is_array($waited)) {
                $waited['cached'] = true;
                return $waited;
            }
            throw new WorkDataException('作品数据正在拉取，请稍后重试');
        }

        try {
            WorkDataLog::write(sprintf(
                '作品数据回填开始：平台=%s 账号=%s 用户=%d max_pages=%d 定时=%s',
                $platform,
                $account,
                $userId,
                $limit,
                $scheduled ? '是' : '否'
            ));

            try {
                $resp = ToolsService::TikHub()->fetchWorkDataBackfill($platform, $account, $limit);
            } catch (HotspotUpstreamException $e) {
                throw new WorkDataException(WorkDataException::translateUpstream($e->getMessage()));
            }

            if (!array_key_exists('platform', $resp)
                || !is_array($resp['account'] ?? null)
                || !is_array($resp['items'] ?? null)
                || !is_array($resp['windows'] ?? null)
            ) {
                throw new WorkDataException(WorkDataException::translateUpstream('上游返回字段不完整'));
            }

            // 中台已一次性完成翻页、去重、合并、窗口重算，站长端整次只结算 1 次。
            $ref = 'wd-backfill-' . bin2hex(random_bytes(16));
            $settle = static function () use ($userId, $ref, $platform, $account, $scheduled): void {
                WorkDataChargeService::settle($userId, $ref, [
                    '平台' => WorkDataSupport::platformName($platform),
                    '账号' => $account,
                    '通道' => '中台',
                    '触发方式' => $scheduled ? '定时同步' : '历史回填',
                ]);
            };
            if ($scheduled) {
                // 只在结算时持有行锁：关闭成功后，在途同步也不能继续扣费。
                $charged = Db::transaction(static function () use ($userId, $personaId, $settle): bool {
                    $persona = AiPersona::where('id', $personaId)->where('user_id', $userId)
                        ->whereNull('delete_time')->lock(true)->find();
                    if (!$persona || !self::canAutoSync($persona)) {
                        return false;
                    }
                    $settle();
                    return true;
                });
                if (!$charged) {
                    return self::lastPayload($platform, $account);
                }
            } else {
                $settle();
            }

            $fetchedAt = trim((string)($resp['fetchedAt'] ?? ''));
            $payload = [
                'platform' => $resp['platform'],
                'account' => $resp['account'],
                'items' => $resp['items'],
                'windows' => $resp['windows'],
                'truncated' => (bool)($resp['truncated'] ?? false),
                'pageSize' => (int)($resp['pageSize'] ?? 0),
                'playCountAvailable' => (bool)($resp['playCountAvailable'] ?? false),
                'playCountNote' => (string)($resp['playCountNote'] ?? ''),
                'fetchedAt' => $fetchedAt !== '' ? $fetchedAt : WorkDataSupport::fetchedAt(),
                'nextCursor' => (string)($resp['nextCursor'] ?? ''),
                'cached' => false,
            ];
            $meta = is_array($resp['backfill'] ?? null) ? $resp['backfill'] : [];
            $payload['backfill'] = [
                'pages' => (int)($meta['pages'] ?? 0),
                'added' => (int)($meta['added'] ?? 0),
                'total' => count($payload['items']),
                'exhausted' => (bool)($meta['exhausted'] ?? true),
                'nextCursor' => (string)($resp['nextCursor'] ?? ''),
                'warning' => (string)($meta['warning'] ?? ''),
            ];

            WorkDataCache::setPayload($platform, $account, $payload, (int)config('work_data.cache_ttl', 3600));
            WorkDataLog::write(sprintf(
                '作品数据回填完成：平台=%s 账号=%s 用户=%d 页数=%d 作品数=%d 新增=%d 结束=%s',
                $platform,
                $account,
                $userId,
                $payload['backfill']['pages'],
                count($payload['items']),
                $payload['backfill']['added'],
                $payload['backfill']['exhausted'] ? '是' : '否'
            ));
            return $payload;
        } catch (WorkDataException $e) {
            // 定时同步失败同样要退避，否则 cron 每分钟重查一次，费用全由站长垫。
            $failureTtl = self::negativeTtl($e->getMessage());
            if ($failureTtl > 0) {
                WorkDataCache::setNegative($platform, $account, $e->getMessage(), $failureTtl);
            }
            throw $e;
        } finally {
            WorkDataCache::releaseLock($platform, $account);
        }
    }

    private static function isNegative(string $msg): bool
    {
        return str_contains($msg, '没找到抖音号')
            || str_contains($msg, '没找到小红书号')
            || str_contains($msg, '没有 sec_uid');
    }

    /**
     * 这条失败该记多久负缓存（秒），0 表示不记。
     *
     * 「账号不存在 / 资料缺 sec_uid」是永久性失败：账号压根不存在，两分钟后重查毫无意义。
     * 而定时同步会不停重来，中台每次都要向 TikHub 付一次搜索的钱——上游对返 200 的
     * 「查无结果」照样计费，站长这边还一分钱收不到。生产上单个绑错的账号一天能重查 450 次，
     * 所以这类失败要记长 TTL；手动刷新（$refresh=true）本来就绕过负缓存，改绑后能立刻生效。
     *
     * 其余失败是上游临时故障（超时/限流/返回不完整）：定时同步是每分钟一次的 cron，
     * 失败不记缓存就会每分钟重查一次，中台每次照样向站长计费，而取数失败时用户一分算力没扣，
     * 这笔钱全由站长垫。这类失败也记 negative_cache_ttl_transient（默认 1 小时），
     * 把重查间隔压到与成功取数一致——「失败就等一小时再取」。
     */
    private static function negativeTtl(string $msg): int
    {
        if (self::isNegative($msg)) {
            $permanent = (int)config('work_data.negative_cache_ttl_permanent', 21600);
            return $permanent > 0 ? $permanent : (int)config('work_data.negative_cache_ttl', 120);
        }
        return (int)config('work_data.negative_cache_ttl_transient', 3600);
    }

    private static function canAutoSync(mixed $persona): bool
    {
        return (int)($persona['work_data_auto_sync'] ?? 0) === 1
            && (int)($persona['status'] ?? 0) === AiPersona::STATUS_ENABLED;
    }

    private static function lastPayload(string $platform, string $account): array
    {
        $payload = WorkDataCache::getLastPayload($platform, $account);
        if ($payload !== null) {
            $payload['cached'] = true;
            return $payload;
        }
        return ['platform' => $platform, 'bound' => true, 'hasData' => false];
    }
}

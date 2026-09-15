<?php

declare(strict_types=1);

namespace app\common\service\sv;

use app\common\model\sv\SvAddWechatRecord;
use app\common\model\sv\SvDySearchRecord;
use think\facade\Cache;
use think\facade\Log;

/**
 * 抖音获客跨设备去重：user_id + 联系方式 / hash
 */
class SvDySearchDedupService
{
    private const ADD_WECHAT_LOCK_PREFIX = 'dysearch:add_wechat:dedup:';
    private const CRAWL_HASH_LOCK_PREFIX = 'dysearch:crawling:dedup:';
    private const LOCK_TTL = 30;

    /**
     * 按 user_id + reg_wechat 跨渠道去重（含抖音获客及其他渠道同号）
     */
    public static function existsAddWechatContact(int $userId, string $regWechat): bool
    {
        $regWechat = trim($regWechat);
        if ($userId <= 0 || $regWechat === '') {
            return false;
        }

        return !SvAddWechatRecord::where('user_id', $userId)
            ->where('reg_wechat', $regWechat)
            ->where('status', 'in', [1, 2, 4, 5, 6])
            ->limit(1)
            ->findOrEmpty()
            ->isEmpty();
    }

    /**
     * 尝试占用加微写入权。成功返回 lockValue，失败或 Redis 不可用返回 null。
     */
    public static function claimAddWechatContact(int $userId, string $regWechat): ?string
    {
        $regWechat = trim($regWechat);
        if ($userId <= 0 || $regWechat === '') {
            return null;
        }

        if (self::existsAddWechatContact($userId, $regWechat)) {
            return null;
        }

        $lockValue = bin2hex(random_bytes(8));
        $lockResult = self::tryLock(self::addWechatLockKey($userId, $regWechat), $lockValue);
        if (!self::isLockAcquired($lockResult)) {
            return null;
        }

        if (self::existsAddWechatContact($userId, $regWechat)) {
            self::unlock(self::addWechatLockKey($userId, $regWechat), $lockValue);
            return null;
        }

        return $lockValue;
    }

    public static function releaseAddWechatContact(int $userId, string $regWechat, ?string $lockValue): void
    {
        if ($lockValue === null || $lockValue === '') {
            return;
        }
        self::unlock(self::addWechatLockKey($userId, trim($regWechat)), $lockValue);
    }

    /**
     * 同一用户同一 hash 且已入库（result=1）才算存在
     */
    public static function existsCrawlingHash(int $userId, string $hash): bool
    {
        $hash = trim($hash);
        if ($userId <= 0 || $hash === '') {
            return false;
        }

        return !SvDySearchRecord::where('user_id', $userId)
            ->where('hash', $hash)
            ->where('result', 1)
            ->limit(1)
            ->findOrEmpty()
            ->isEmpty();
    }

    /**
     * 尝试占用获客记录写入权。成功返回 lockValue，失败返回 null。
     */
    public static function claimCrawlingHash(int $userId, string $hash): ?string
    {
        $hash = trim($hash);
        if ($userId <= 0 || $hash === '') {
            return null;
        }

        if (self::existsCrawlingHash($userId, $hash)) {
            return null;
        }

        $lockValue = bin2hex(random_bytes(8));
        $lockResult = self::tryLock(self::crawlHashLockKey($userId, $hash), $lockValue);
        if (!self::isLockAcquired($lockResult)) {
            return null;
        }

        if (self::existsCrawlingHash($userId, $hash)) {
            self::unlock(self::crawlHashLockKey($userId, $hash), $lockValue);
            return null;
        }

        return $lockValue;
    }

    /**
     * Redis 锁结果：1 才算拿到。0 被占用、-1 异常都视为未拿到。
     */
    public static function isLockAcquired(int $lockResult): bool
    {
        return $lockResult === 1;
    }

    public static function releaseCrawlingHash(int $userId, string $hash, ?string $lockValue): void
    {
        if ($lockValue === null || $lockValue === '') {
            return;
        }
        self::unlock(self::crawlHashLockKey($userId, trim($hash)), $lockValue);
    }

    private static function addWechatLockKey(int $userId, string $regWechat): string
    {
        return self::ADD_WECHAT_LOCK_PREFIX . $userId . ':' . md5($regWechat);
    }

    private static function crawlHashLockKey(int $userId, string $hash): string
    {
        return self::CRAWL_HASH_LOCK_PREFIX . $userId . ':' . $hash;
    }

    /**
     * @return int 1=拿到锁, 0=锁被占用, -1=Redis 异常（调用方应拒写）
     */
    private static function tryLock(string $lockKey, string $lockValue): int
    {
        try {
            $redis = Cache::store('redis')->handler();
            if (method_exists($redis, 'set')) {
                $ok = $redis->set($lockKey, $lockValue, ['nx', 'ex' => self::LOCK_TTL]);
                return $ok ? 1 : 0;
            }
            if (!$redis->setnx($lockKey, $lockValue)) {
                return 0;
            }
            $redis->expire($lockKey, self::LOCK_TTL);
            return 1;
        } catch (\Throwable $th) {
            Log::channel('socket')->write('抖音获客去重加锁失败: ' . $th->getMessage(), 'task_record');
            return -1;
        }
    }

    private static function unlock(string $lockKey, string $lockValue): void
    {
        try {
            $redis = Cache::store('redis')->handler();
            if ((string)$redis->get($lockKey) === $lockValue) {
                $redis->del($lockKey);
            }
        } catch (\Throwable $th) {
            Log::channel('socket')->write('抖音获客去重解锁失败: ' . $th->getMessage(), 'task_record');
        }
    }
}

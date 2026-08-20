<?php

declare(strict_types=1);

namespace app\common\service\sv;

use app\common\model\sv\SvAddWechatRecord;
use app\common\model\sv\SvCrawlingRecord;
use think\facade\Cache;
use think\facade\Log;

/**
 * 视频号获客跨设备去重：user_id + 联系方式 / hash
 */
class SvSphClueDedupService
{
    private const ADD_WECHAT_LOCK_PREFIX = 'sph:add_wechat:dedup:';
    private const CRAWL_HASH_LOCK_PREFIX = 'sph:crawling:dedup:';
    private const LOCK_TTL = 30;

    public static function existsAddWechatContact(int $userId, string $regWechat): bool
    {
        $regWechat = trim($regWechat);
        if ($userId <= 0 || $regWechat === '') {
            return false;
        }

        return !SvAddWechatRecord::where('user_id', $userId)
            ->where('reg_wechat', $regWechat)
            ->limit(1)
            ->findOrEmpty()
            ->isEmpty();
    }

    /**
     * 尝试占用加微写入权。成功返回 lockValue（空字符串表示无 Redis 锁但可写），失败返回 null（应跳过）。
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
        if ($lockResult === 0) {
            return null;
        }

        if (self::existsAddWechatContact($userId, $regWechat)) {
            if ($lockResult === 1) {
                self::unlock(self::addWechatLockKey($userId, $regWechat), $lockValue);
            }
            return null;
        }

        return $lockResult === 1 ? $lockValue : '';
    }

    public static function releaseAddWechatContact(int $userId, string $regWechat, ?string $lockValue): void
    {
        if ($lockValue === null || $lockValue === '') {
            return;
        }
        self::unlock(self::addWechatLockKey($userId, trim($regWechat)), $lockValue);
    }

    public static function existsCrawlingHash(int $userId, string $hash): bool
    {
        $hash = trim($hash);
        if ($userId <= 0 || $hash === '') {
            return false;
        }

        return !SvCrawlingRecord::where('user_id', $userId)
            ->where('hash', $hash)
            ->limit(1)
            ->findOrEmpty()
            ->isEmpty();
    }

    /**
     * 尝试占用爬取记录写入权。成功返回 lockValue，失败返回 null。
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
        if ($lockResult === 0) {
            return null;
        }

        if (self::existsCrawlingHash($userId, $hash)) {
            if ($lockResult === 1) {
                self::unlock(self::crawlHashLockKey($userId, $hash), $lockValue);
            }
            return null;
        }

        return $lockResult === 1 ? $lockValue : '';
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
     * @return int 1=拿到锁, 0=锁被占用, -1=Redis 异常（降级为无锁继续）
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
            Log::channel('socket')->write('视频号获客去重加锁失败: ' . $th->getMessage(), 'task_record');
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
            Log::channel('socket')->write('视频号获客去重解锁失败: ' . $th->getMessage(), 'task_record');
        }
    }
}

<?php

namespace app\common\service\workData;

use think\facade\Cache;

class WorkDataCache
{
    /** @var array<string, mixed>|null */
    private static ?array $memory = null;

    public static function useMemoryForTest(bool $on = true): void
    {
        self::$memory = $on ? [] : null;
    }

    public static function payloadKey(string $platform, string $account): string
    {
        return 'work_data:v1:' . $platform . ':' . strtolower($account);
    }

    public static function lockKey(string $platform, string $account): string
    {
        return 'work_data:lock:v1:' . $platform . ':' . strtolower($account);
    }

    public static function refreshKey(string $platform, string $account): string
    {
        return 'work_data:refresh:' . $platform . ':' . strtolower($account);
    }

    public static function negativeKey(string $platform, string $account): string
    {
        return 'work_data:neg:v1:' . $platform . ':' . strtolower($account);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function getPayload(string $platform, string $account): ?array
    {
        $raw = self::storeGet(self::payloadKey($platform, $account));
        return is_array($raw) ? $raw : null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function setPayload(string $platform, string $account, array $payload, int $ttl): void
    {
        self::storeSet(self::payloadKey($platform, $account), $payload, $ttl);
        // 展示快照不随取数缓存过期，停用自动同步后仍可查看上次结果。
        self::storeSet('work_data:last:v1:' . $platform . ':' . strtolower($account), $payload, 0);
    }

    public static function getLastPayload(string $platform, string $account): ?array
    {
        $raw = self::getPayload($platform, $account)
            ?? self::storeGet('work_data:last:v1:' . $platform . ':' . strtolower($account));
        return is_array($raw) ? $raw : null;
    }

    public static function getNegative(string $platform, string $account): ?string
    {
        $raw = self::storeGet(self::negativeKey($platform, $account));
        return is_string($raw) && $raw !== '' ? $raw : null;
    }

    public static function setNegative(string $platform, string $account, string $message, int $ttl): void
    {
        self::storeSet(self::negativeKey($platform, $account), $message, $ttl);
    }

    public static function shouldThrottleRefresh(string $platform, string $account, int $interval): bool
    {
        return (bool)self::storeGet(self::refreshKey($platform, $account));
    }

    public static function markRefreshed(string $platform, string $account, int $interval): void
    {
        self::storeSet(self::refreshKey($platform, $account), 1, $interval);
    }

    public static function acquireLock(string $platform, string $account, int $waitSeconds): bool
    {
        $key = self::lockKey($platform, $account);
        if (self::$memory !== null) {
            if (!empty(self::$memory[$key])) {
                return false;
            }
            self::$memory[$key] = 1;
            return true;
        }
        try {
            $handler = Cache::store('redis')->handler();
            $deadline = microtime(true) + max(1, $waitSeconds);
            $lockTtl = max($waitSeconds + 5, (int)config('work_data.mid_timeout', 180) + 60);
            while (!(bool)$handler->set($key, 1, ['nx', 'ex' => $lockTtl])) {
                if (microtime(true) >= $deadline) {
                    return false;
                }
                usleep(100000);
            }
            return true;
        } catch (\Throwable $e) {
            WorkDataLog::write('作品数据取数锁异常：' . $e->getMessage());
            return false;
        }
    }

    public static function releaseLock(string $platform, string $account): void
    {
        $key = self::lockKey($platform, $account);
        if (self::$memory !== null) {
            unset(self::$memory[$key]);
            return;
        }
        try {
            Cache::store('redis')->handler()->del($key);
        } catch (\Throwable $e) {
            WorkDataLog::write('作品数据释放锁异常：' . $e->getMessage());
        }
    }

    /**
     * @return mixed
     */
    private static function storeGet(string $key)
    {
        if (self::$memory !== null) {
            return self::$memory[$key] ?? null;
        }
        try {
            return Cache::store('redis')->get($key);
        } catch (\Throwable $e) {
            WorkDataLog::write('作品数据读缓存异常：' . $e->getMessage());
            return null;
        }
    }

    /**
     * @param mixed $value
     */
    private static function storeSet(string $key, $value, int $ttl): void
    {
        if (self::$memory !== null) {
            self::$memory[$key] = $value;
            return;
        }
        try {
            Cache::store('redis')->set($key, $value, $ttl);
        } catch (\Throwable $e) {
            WorkDataLog::write('作品数据写缓存异常：' . $e->getMessage());
        }
    }
}

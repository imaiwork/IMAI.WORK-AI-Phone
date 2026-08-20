<?php

declare(strict_types=1);

namespace app\common\workerman\rpa\Support;

use think\cache\driver\Redis;

class ConnectionRepository
{
    public function __construct(private Redis $redis)
    {
    }

    public function bindConnection(string $uid, int $workerId, string $clientType = '', string $content = ''): void
    {
        $this->redis->set("xhs:connection:{$uid}:worker", $workerId);
        if ($clientType !== '') {
            $this->redis->set("xhs:connection:{$uid}:type", $clientType);
        }
        $this->redis->set("xhs:connection:{$uid}:content", $content);
    }

    public function forgetConnection(string $uid): void
    {
        $this->redis->del("xhs:connection:{$uid}:worker");
        $this->redis->del("xhs:connection:{$uid}:type");
        $this->redis->del("xhs:connection:{$uid}:content");
    }

    public function cleanupInvalidConnectionKeys(array $activeUids = [], int $limit = 1000): array
    {
        $activeUidMap = array_fill_keys($activeUids, true);
        $boundUidMap = $this->getDeviceAndWebUserBoundUidMap($limit);
        $checked = 0;
        $deleted = [];
        $reserved = [];

        foreach ($this->scanKeys('xhs:connection:*:worker', $limit) as $key) {
            $uid = $this->parseConnectionUid($key, ':worker');
            if ($uid === null) {
                continue;
            }

            $checked++;
            if (isset($activeUidMap[$uid]) || isset($boundUidMap[$uid])) {
                $reserved[] = $uid;
                continue;
            }

            $this->forgetConnection($uid);
            $deleted[] = $uid;
        }

        foreach ($this->scanKeys('xhs:connection:*:type', $limit) as $key) {
            $uid = $this->parseConnectionUid($key, ':type');
            if ($uid === null || isset($activeUidMap[$uid]) || in_array($uid, $deleted, true)) {
                continue;
            }

            if ($this->getConnectionWorkerId($uid) === null && !isset($boundUidMap[$uid])) {
                $this->forgetConnection($uid);
                $deleted[] = $uid;
            }
        }

        return [
            'checked' => $checked,
            'deleted' => array_values(array_unique($deleted)),
            'reserved' => array_values(array_unique($reserved)),
        ];
    }

    public function getConnectionWorkerId(string $uid): ?int
    {
        $workerId = $this->redis->get("xhs:connection:{$uid}:worker");
        return $workerId === false || $workerId === null || $workerId === '' ? null : (int)$workerId;
    }

    public function bindDevice(string $deviceId, string $uid, int $workerId, string $version = ''): void
    {
        $deviceId = $this->normalizeDeviceId($deviceId);
        $this->redis->set("xhs:device:{$deviceId}", $uid);
        $this->redis->set("xhs:device:{$deviceId}:worker", $workerId);
        $this->redis->set("xhs:device:{$deviceId}:status", 'online');
        $this->redis->set("xhs:device:{$deviceId}:onlinetime", date('Y-m-d H:i:s'));
        if ($version !== '') {
            $this->redis->set("xhs:device:{$deviceId}:version", $version);
        }
        $this->bindConnection($uid, $workerId, 'device', $deviceId);
    }

    public function markDeviceOnline(string $deviceId, string $uid, int $workerId, string $version = ''): void
    {
        $this->bindDevice($deviceId, $uid, $workerId, $version);
    }

    public function markDeviceHeartbeat(string $deviceId, string $uid, int $workerId, string $version = ''): void
    {
        $deviceId = $this->normalizeDeviceId($deviceId);
        $this->redis->set("xhs:device:{$deviceId}:heart", date('Y-m-d H:i:s'));
        $this->bindDevice($deviceId, $uid, $workerId, $version);
    }

    public function markDeviceOffline(string $deviceId, ?string $uid = null): bool
    {
        $deviceId = $this->normalizeDeviceId($deviceId);
        if ($uid !== null && $this->getDeviceUid($deviceId) !== $uid) {
            return false;
        }

        $this->redis->del("xhs:device:{$deviceId}");
        $this->redis->del("xhs:device:{$deviceId}:worker");
        $this->redis->del("xhs:init:{$deviceId}");
        $this->redis->del("xhs:getUser:{$deviceId}");
        $this->redis->set("xhs:device:{$deviceId}:status", 'offline');

        return true;
    }

    public function getDeviceUid(string $deviceId): ?string
    {
        $deviceId = $this->normalizeDeviceId($deviceId);
        $uid = $this->redis->get("xhs:device:{$deviceId}");
        return $uid === false || $uid === null || $uid === '' ? null : (string)$uid;
    }

    public function isDeviceOnline(string $deviceId): bool
    {
        $deviceId = $this->normalizeDeviceId($deviceId);
        return $this->redis->get("xhs:device:{$deviceId}:status") === 'online'
            && $this->getDeviceUid($deviceId) !== null;
    }

    public function bindWebUser(string $source, int $userId, string $uid, int $workerId): void
    {
        $this->redis->set("xhs:user:{$source}:{$userId}", $uid);
        $this->redis->set("xhs:user:{$source}:{$userId}:worker", $workerId);
        $this->bindConnection($uid, $workerId, 'webUser', $source . ':' . $userId);
    }

    public function getWebUserUid(string $source, int $userId): ?string
    {
        $uid = $this->redis->get("xhs:user:{$source}:{$userId}");
        return $uid === false || $uid === null || $uid === '' ? null : (string)$uid;
    }

    public function getWebUserWorkerId(string $source, int $userId): ?int
    {
        $workerId = $this->redis->get("xhs:user:{$source}:{$userId}:worker");
        return $workerId === false || $workerId === null || $workerId === '' ? null : (int)$workerId;
    }

    public function getWebUserConnectionUids(string $source, int $userId, int $limit = 1000): array
    {
        $content = $source . ':' . $userId;
        $uids = [];

        foreach ($this->scanKeys('xhs:connection:*:content', $limit) as $key) {
            if ((string)$this->redis->get($key) !== $content) {
                continue;
            }

            $uid = $this->parseConnectionUid($key, ':content');
            if ($uid !== null) {
                $uids[] = $uid;
            }
        }

        return array_values(array_unique($uids));
    }

    public function forgetWebUser(string $source, int $userId): void
    {
        $this->redis->del("xhs:user:{$source}:{$userId}");
        $this->redis->del("xhs:user:{$source}:{$userId}:worker");
    }

    public function forgetWebUserIfUid(string $source, int $userId, string $uid): bool
    {
        if ($this->getWebUserUid($source, $userId) !== $uid) {
            return false;
        }

        $this->forgetWebUser($source, $userId);
        return true;
    }

    private function getDeviceAndWebUserBoundUidMap(int $limit): array
    {
        $boundUidMap = [];

        foreach ($this->scanKeys('xhs:device:*', $limit) as $key) {
            if (!$this->isDeviceBindingKey($key)) {
                continue;
            }

            $uid = (string)$this->redis->get($key);
            if ($uid !== '') {
                $boundUidMap[$uid] = true;
            }
        }

        foreach ($this->scanKeys('xhs:user:*:*', $limit) as $key) {
            if (!$this->isWebUserBindingKey($key)) {
                continue;
            }

            $uid = (string)$this->redis->get($key);
            if ($uid !== '') {
                $boundUidMap[$uid] = true;
            }
        }

        return $boundUidMap;
    }

    private function scanKeys(string $pattern, int $limit): array
    {
        $handler = $this->redis->handler();
        $keys = [];

        if (method_exists($handler, 'scan')) {
            $iterator = null;
            do {
                $batch = $handler->scan($iterator, $pattern, min($limit, 500));
                if ($batch === false) {
                    break;
                }
                foreach ($batch as $key) {
                    $keys[] = (string)$key;
                    if (count($keys) >= $limit) {
                        return $keys;
                    }
                }
            } while ($iterator > 0);

            return $keys;
        }

        return array_slice(array_map('strval', $handler->keys($pattern) ?: []), 0, $limit);
    }

    private function parseConnectionUid(string $key, string $suffix): ?string
    {
        $prefix = 'xhs:connection:';
        if (!str_starts_with($key, $prefix) || !str_ends_with($key, $suffix)) {
            return null;
        }

        $uid = substr($key, strlen($prefix), -strlen($suffix));
        return $uid === '' ? null : $uid;
    }

    private function isDeviceBindingKey(string $key): bool
    {
        return str_starts_with($key, 'xhs:device:') && substr_count($key, ':') === 2;
    }

    private function isWebUserBindingKey(string $key): bool
    {
        return str_starts_with($key, 'xhs:user:') && substr_count($key, ':') === 3;
    }

    private function normalizeDeviceId(string $deviceId): string
    {
        return trim($deviceId);
    }
}

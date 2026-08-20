<?php

declare(strict_types=1);

namespace app\common\workerman\rpa\Support;

use ArrayAccess;

class DeviceIndex implements ArrayAccess
{
    private array $local = [];

    public function __construct(private ConnectionRepository $repository, private int $workerId)
    {
    }

    public function offsetExists(mixed $offset): bool
    {
        $deviceId = trim((string)$offset);
        return isset($this->local[$deviceId]) || $this->repository->isDeviceOnline($deviceId);
    }

    public function offsetGet(mixed $offset): mixed
    {
        $deviceId = trim((string)$offset);
        return $this->local[$deviceId] ?? $this->repository->getDeviceUid($deviceId);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            return;
        }

        $deviceId = trim((string)$offset);
        $uid = (string)$value;
        $this->local[$deviceId] = $uid;
        $this->repository->bindDevice($deviceId, $uid, $this->workerId);
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->local[trim((string)$offset)]);
    }
}

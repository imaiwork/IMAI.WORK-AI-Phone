<?php

namespace app\common\service\deviceauth;

use app\common\enum\deviceauth\DeviceAuthCodeEnum;
use app\common\model\deviceauth\DeviceCdkCode;
use app\common\model\sv\SvDevice;
use think\facade\Cache;

class DeviceAuthActivateWatchService
{
    private const CACHE_KEY_PREFIX = 'device_auth_activate_snapshot:';

    private const CACHE_TTL = 1800;

    public static function cacheKey(int $userId): string
    {
        return self::CACHE_KEY_PREFIX . $userId;
    }

    /**
     * 快照用户名下设备CDK状态并写入缓存。
     *
     * @return array<string, int> code => status
     */
    public static function snapshot(int $userId): array
    {
        $statusMap = self::fetchUserCodeStatusMap($userId);
        Cache::set(self::cacheKey($userId), $statusMap, self::CACHE_TTL);
        return $statusMap;
    }

    public static function hasSnapshot(int $userId): bool
    {
        return Cache::has(self::cacheKey($userId));
    }

    /**
     * @return array<string, int>
     */
    public static function getSnapshot(int $userId): array
    {
        $snapshot = Cache::get(self::cacheKey($userId));
        return is_array($snapshot) ? $snapshot : [];
    }

    public static function clear(int $userId): void
    {
        Cache::delete(self::cacheKey($userId));
    }

    public static function unusedCountFromMap(array $statusMap): int
    {
        $count = 0;
        foreach ($statusMap as $status) {
            if ((int)$status === DeviceAuthCodeEnum::STATUS_UNUSED) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * 检测快照中是否有码从未使用变为已使用。
     */
    public static function detectActivated(int $userId): ?array
    {
        $snapshot = self::getSnapshot($userId);
        if ($snapshot === []) {
            return null;
        }

        $codes = array_keys($snapshot);
        $currentList = DeviceCdkCode::where('owner_user_id', $userId)
            ->whereIn('code', $codes)
            ->field('code,status,device_code,type')
            ->select()
            ->toArray();

        $matched = self::matchActivatedTransition($snapshot, $currentList);
        if ($matched !== null) {
            return $matched;
        }

        return self::matchSvDeviceBindStatus($userId, $snapshot, $currentList);
    }

    /**
     * @param array<string, int> $snapshot
     * @param array<int, array<string, mixed>> $currentList
     */
    public static function matchActivatedTransition(array $snapshot, array $currentList): ?array
    {
        $currentByCode = [];
        foreach ($currentList as $row) {
            $currentByCode[$row['code']] = $row;
        }

        foreach ($snapshot as $code => $oldStatus) {
            if ((int)$oldStatus !== DeviceAuthCodeEnum::STATUS_UNUSED) {
                continue;
            }
            $current = $currentByCode[$code] ?? null;
            if (!$current) {
                continue;
            }
            if ((int)$current['status'] === DeviceAuthCodeEnum::STATUS_USED) {
                $type = (int)($current['type'] ?? 0);
                return [
                    'status'         => 1,
                    'message'        => '激活成功',
                    'code'           => (string)$code,
                    'device_code'    => (string)($current['device_code'] ?? ''),
                    'type'           => $type,
                    'auth_type_desc' => DeviceAuthCodeEnum::getTypeDesc($type),
                ];
            }
        }

        return null;
    }

    /**
     * 当设备CDK表里没有快照 code 时，按 sv_device.device_code 兜底判断设备绑定状态。
     *
     * @param array<string, int> $snapshot
     * @param array<int, array<string, mixed>> $currentList
     */
    private static function matchSvDeviceBindStatus(int $userId, array $snapshot, array $currentList): ?array
    {

        $devices = SvDevice::where('user_id', $userId)
            ->where('auth_start_time', '>', time() - 10)
            ->field('device_code,auth_status,auth_start_time')
            ->select();
        if ($devices->isEmpty()) {
            return null;
        }
        $devices = $devices->toArray();
        return [
            'status'         => 1,
            'message'        => '激活成功',
            'code'           => '',
            'device_code'    => $devices[0]['device_code'],
            'type'           => 0,
            'auth_type_desc' => '',
        ];
    }

    /**
     * @return array<string, int>
     */
    private static function fetchUserCodeStatusMap(int $userId): array
    {
        $list = DeviceCdkCode::where('owner_user_id', $userId)
            ->where('status', '<>', DeviceAuthCodeEnum::STATUS_DISABLED)
            ->column('status', 'code');

        return is_array($list) ? $list : [];
    }
}

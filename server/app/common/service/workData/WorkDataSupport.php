<?php

namespace app\common\service\workData;

use app\common\enum\DeviceEnum;

class WorkDataSupport
{
    public const PLATFORM_DOUYIN = 'douyin';
    public const PLATFORM_XHS = 'xhs';

    public static function platformType(string $platform): int
    {
        return $platform === self::PLATFORM_XHS
            ? DeviceEnum::ACCOUNT_TYPE_XHS
            : DeviceEnum::ACCOUNT_TYPE_DY;
    }

    public static function platformName(string $platform): string
    {
        return $platform === self::PLATFORM_XHS ? '小红书' : '抖音';
    }

    public static function normalizeRefresh(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function fetchedAt(): string
    {
        return date('Y-m-d\TH:i:s');
    }
}

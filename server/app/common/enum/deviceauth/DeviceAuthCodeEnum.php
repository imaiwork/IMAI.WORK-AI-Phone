<?php

namespace app\common\enum\deviceauth;

/**
 * 设备CDK枚举（与中台 type_code 1-7 一致）
 */
class DeviceAuthCodeEnum
{
    const TYPE_FOREVER   = 1;
    const TYPE_WEEK      = 2;
    const TYPE_MONTH     = 3;
    const TYPE_QUARTER   = 4;
    const TYPE_HALF_YEAR = 5;
    const TYPE_YEAR      = 6;
    const TYPE_CUSTOM    = 7;

    const STATUS_UNUSED   = 0;
    const STATUS_USED     = 1;
    const STATUS_DISABLED = 2;

    const SOURCE_PLATFORM = 1;
    const SOURCE_IMPORT   = 2;

    const DEVICE_AUTH_INACTIVE = 0;
    const DEVICE_AUTH_ACTIVE   = 1;
    const DEVICE_AUTH_EXPIRED  = 2;

    /** @deprecated 兼容旧代码引用，等同 TYPE_FOREVER */
    const TYPE_DAY = 7;

    public static function getTypeDesc($type = true)
    {
        $desc = [
            self::TYPE_FOREVER   => '永久卡',
            self::TYPE_WEEK      => '周卡',
            self::TYPE_MONTH     => '月卡',
            self::TYPE_QUARTER   => '季卡',
            self::TYPE_HALF_YEAR => '半年卡',
            self::TYPE_YEAR      => '年卡',
            self::TYPE_CUSTOM    => '自定义天数',
        ];
        if ($type === true) {
            return $desc;
        }
        return $desc[$type] ?? '';
    }

    public static function getStatusDesc($status = true)
    {
        $desc = [
            self::STATUS_UNUSED   => '未使用',
            self::STATUS_USED     => '已使用',
            self::STATUS_DISABLED => '已作废',
        ];
        if ($status === true) {
            return $desc;
        }
        return $desc[$status] ?? '';
    }

    public static function getSourceDesc($source = true)
    {
        $desc = [
            self::SOURCE_PLATFORM => '中台生成',
            self::SOURCE_IMPORT   => '文件导入',
        ];
        if ($source === true) {
            return $desc;
        }
        return $desc[$source] ?? '';
    }

    public static function getDeviceAuthStatusDesc($status = true)
    {
        $desc = [
            self::DEVICE_AUTH_INACTIVE => '未激活',
            self::DEVICE_AUTH_ACTIVE   => '运行中',
            self::DEVICE_AUTH_EXPIRED  => '已过期',
        ];
        if ($status === true) {
            return $desc;
        }
        return $desc[$status] ?? '';
    }

    public static function getDurationDays(int $type, int $customDays = 0): int
    {
        return match ($type) {
            self::TYPE_FOREVER   => 0,
            self::TYPE_WEEK      => 7,
            self::TYPE_MONTH     => 30,
            self::TYPE_QUARTER   => 90,
            self::TYPE_HALF_YEAR => 180,
            self::TYPE_YEAR      => 365,
            self::TYPE_CUSTOM    => max($customDays, 0),
            default              => 0,
        };
    }

    public static function resolveDurationDays(int $type, int $customDays = 0): int
    {
        if ($type === self::TYPE_CUSTOM) {
            return max($customDays, 0);
        }
        return self::getDurationDays($type);
    }
}

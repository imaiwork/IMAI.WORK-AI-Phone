<?php

namespace app\common\enum\deviceauth;

/**
 * 设备授权订单枚举
 */
class DeviceAuthOrderEnum
{
    const BIZ_TYPE_PURCHASE = 1;
    const BIZ_TYPE_RENEW    = 2;

    const PAY_TYPE_ONLINE = 1;
    const PAY_TYPE_TOKENS = 2;

    const PAY_STATUS_UNPAID   = 0;
    const PAY_STATUS_PAID     = 1;
    const PAY_STATUS_CANCEL   = 2;
    const PAY_STATUS_REFUNDED = 3;

    public static function getBizTypeDesc($type = true)
    {
        $desc = [
            self::BIZ_TYPE_PURCHASE => '购买设备CDK',
            self::BIZ_TYPE_RENEW    => '设备续费',
        ];
        if ($type === true) {
            return $desc;
        }
        return $desc[$type] ?? '';
    }

    public static function getPayTypeDesc($type = true)
    {
        $desc = [
            self::PAY_TYPE_ONLINE => '在线支付',
            self::PAY_TYPE_TOKENS => '算力支付',
        ];
        if ($type === true) {
            return $desc;
        }
        return $desc[$type] ?? '';
    }

    public static function getPayStatusDesc($status = true)
    {
        $desc = [
            self::PAY_STATUS_UNPAID   => '待支付',
            self::PAY_STATUS_PAID     => '已支付',
            self::PAY_STATUS_CANCEL   => '已取消',
            self::PAY_STATUS_REFUNDED => '已退款',
        ];
        if ($status === true) {
            return $desc;
        }
        return $desc[$status] ?? '';
    }
}

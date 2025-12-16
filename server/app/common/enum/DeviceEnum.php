<?php


namespace app\common\enum;

/**
 * 设备枚举
 * Class DeviceEnum
 * @package app\common\enum
 */
class DeviceEnum
{
    const TASK_TYPE_PUBLISH = 1; // 发布
    const TASK_TYPE_TAKEOVER = 2; // 接管
    const TASK_TYPE_ACTIVE = 3; // 养号
    const TASK_TYPE_CLUES = 4; // 获客
    const TASK_TYPE_FRIENDS = 5; // 加好友

    const ACCOUNT_TYPE_SPH = 1; // 视频号
    const ACCOUNT_TYPE_XHS = 3; // 小红书
    const ACCOUNT_TYPE_DY = 4; // 抖音
    const ACCOUNT_TYPE_KS = 5; // 快手

    const TASK_STATUS_WAIT = 0; // 待执行
    const TASK_STATUS_RUNNING = 1; // 执行中
    const TASK_STATUS_FINISHED = 2; // 已完成
    const TASK_STATUS_FAILED = 3; // 失败
    const TASK_STATUS_INTERRUPTED = 4; //中断

    const DEVICE_STATUS_ONLINE = 1; // 在线
    const DEVICE_STATUS_OFFLINE = 0; // 离线
    const DEVICE_STATUS_WORKING = 2; // 运行中

    const TASK_SOURCE_PUBLISH = 1; // 发布
    const TASK_SOURCE_TAKEOVER = 2; // 接管
    const TASK_SOURCE_ACTIVE = 3; // 养号
    const TASK_SOURCE_CLUES = 4; // 获客
    const TASK_SOURCE_FRIENDS = 5; // 加好友

    const XHS_TAKE_OVER = 30; // 小红书接管
    const DY_TAKE_OVER = 31; // 抖音接管
    const KS_TAKE_OVER = 32; // 快手接管

    const XHS_MAINTENANCE_ACCOUNT = 40; // 小红书维护账号
    const DY_MAINTENANCE_ACCOUNT = 41; // 抖音维护账号
    const KS_MAINTENANCE_ACCOUNT = 42; // 快手维护账号



    public static function getTakeOverType($type)
    {
        $desc = [
            self::ACCOUNT_TYPE_XHS => self::XHS_TAKE_OVER,
            self::ACCOUNT_TYPE_DY => self::DY_TAKE_OVER,
            self::ACCOUNT_TYPE_KS => self::KS_TAKE_OVER,
        ];
        return $desc[$type] ?? self::XHS_TAKE_OVER;
    }

    public static function getMaintainAccountType($type)
    {
        $desc = [
            self::ACCOUNT_TYPE_XHS => self::XHS_MAINTENANCE_ACCOUNT,
            self::ACCOUNT_TYPE_DY => self::DY_MAINTENANCE_ACCOUNT,
            self::ACCOUNT_TYPE_KS => self::KS_MAINTENANCE_ACCOUNT,
        ];
        return $desc[$type] ?? self::XHS_MAINTENANCE_ACCOUNT;
    }


    public static function getTaskTypeDesc($type, $flag = false)
    {
        $desc = [
            0 => '未知',
            1 => '发布',
            2 => '接管',
            3 => '养号',
            4 => '获客',
            5 => '加好友',
        ];
        if ($flag) {
            return $desc;
        }
        return $desc[$type] ?? '';
    }

    public static function getAccountTypeDesc($type, $flag = false)
    {
        $desc = [
            0 => '未知',
            1 => '视频号',
            3 => '小红书',
            4 => '抖音',
            5 => '快手',
        ];
        if ($flag) {
            return $desc;
        }
        return $desc[$type] ?? '';
    }
}

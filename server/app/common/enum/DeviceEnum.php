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
    const TASK_TYPE_TOUCH = 6; // 截流获客
    const TASK_TYPE_WECHAT_CIRCLE = 7; // 客户互动


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
    const TASK_SOURCE_TOUCH = 6; // 截流获客

    const XHS_TAKE_OVER = 30; // 小红书接管
    const DY_TAKE_OVER = 31; // 抖音接管
    const KS_TAKE_OVER = 32; // 快手接管

    const XHS_MAINTENANCE_ACCOUNT = 40; // 小红书维护账号
    const DY_MAINTENANCE_ACCOUNT = 41; // 抖音维护账号
    const KS_MAINTENANCE_ACCOUNT = 42; // 快手维护账号

    const TASK_COMMENT_TO_COMMENT = 101; // 评论区评论
    const TASK_COMMENT_TO_MSG = 102; // 评论区私信
    const TASK_COMMENT_TO_MARK_CLUE = 103; // 评论区留痕获客
    

    //自动模式
    const AUTO_CONFIG_STATUS_WAIT = 0; // 待执行
    const AUTO_CONFIG_STATUS_RUNNING = 1; // 执行中
    const AUTO_CONFIG_STATUS_FINISHED = 2; // 已完成
    const AUTO_CONFIG_STATUS_FAILED = 3; // 失败



    # 自动化任务类型
    const AUTO_TYPE_CLUES = 21; // 获客
    const AUTO_TYPE_PUBLISH = 22; // 发布
    const AUTO_TYPE_WECHAT_CIRCLE = 23; // 3客户互动
    const AUTO_TYPE_COMMENT_CLUE = 24; // 评论区获客
    const AUTO_TYPE_WECHAT_FRIEND = 25; // 微信加v
    const AUTO_TYPE_ACTIVE = 26; // 养号
    const AUTO_TYPE_CLEAN_PHONE = 27; // 清理手机
    const AUTO_TYPE_TAKE_OVER = 28; // 私信接管



    # 自动化任务场景
    const AUTO_TASK_SCENE_COMMENT_COMMENT = 1; // 评论区评论
    const AUTO_TASK_SCENE_COMMENT_MSG = 2; // 评论区私信
    const AUTO_TASK_SCENE_MARK_CLUE= 3; // 留痕获客
    const AUTO_TASK_SCENE_SPH_CLUE = 4; // 视频号获客
    const AUTO_TASK_SCENE_CONTENT_PUBLISH = 5; // 内容发布
    const AUTO_TASK_SCENE_TAKE_OVER = 6; // 私信接管

    
    



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
            6 => '截流获客',

            21 => '获客',
            22 => '发布',
            23 => '客户互动',
            24 => '评论区获客',
            25 => '微信加v',
            26 => '养号',
            27 => '清理手机',
            28 => '私信接管',


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

    public static function getTaskSceneDesc($type, $flag = false)
    {
        $desc = [
            1 => '评论区评论',
            2 => '评论区私信',
            3 => '留痕获客',
            4 => '视频号获客',
            5 => '内容发布',
        ];
        if ($flag) {
            return $desc;
        }
        return $desc[$type] ?? '';
    }

    public static function getTaskTypeByAuto(int $type){
        $maps = [
            self::AUTO_TYPE_CLUES => self::TASK_SOURCE_CLUES, //获客
            self::AUTO_TYPE_PUBLISH => self::TASK_TYPE_PUBLISH, //发布
            self::AUTO_TYPE_WECHAT_CIRCLE => self::TASK_TYPE_WECHAT_CIRCLE, //客户互动
            self::AUTO_TYPE_COMMENT_CLUE => self::TASK_TYPE_TOUCH, //评论区获客
            self::AUTO_TYPE_WECHAT_FRIEND => self::TASK_TYPE_FRIENDS, //微信加v
            self::AUTO_TYPE_ACTIVE => self::TASK_TYPE_ACTIVE, //养号
            self::AUTO_TYPE_TAKE_OVER => self::TASK_TYPE_TAKEOVER, //私信接管
        ];
        return $maps[$type] ?? $type;
    }
}

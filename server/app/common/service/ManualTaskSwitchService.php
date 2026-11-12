<?php

namespace app\common\service;

/**
 * 手动任务开关
 * 后台「AI获客-基本配置」按任务粒度控制前台手动创建入口是否可用
 */
class ManualTaskSwitchService
{
    // 微信任务
    public const WECHAT_FRIEND_ADD = 'wechat_friend_add'; // 自动加好友
    public const WECHAT_PRIVATE_TAKE = 'wechat_private_take'; // 个微接管
    public const WECHAT_CIRCLE_PUBLISH = 'wechat_circle_publish'; // 发布朋友圈
    public const WECHAT_CIRCLE_INTERACT = 'wechat_circle_interact'; // 朋友圈互动
    // 视频号任务
    public const SPH_CLUE = 'sph_clue'; // 自动获线索
    public const SPH_PRIVATE_TAKE = 'sph_private_take'; // 视频号私信接管
    // 抖音任务
    public const DY_SEARCH_FRIEND_ADD = 'dy_search_friend_add'; // 抖音账号搜寻获客-自动加好友

    /** 全部开关 key（未配置时默认关闭，需管理员在后台 AI获客-基本配置 手动开启） */
    public const TASK_KEYS = [
        self::WECHAT_FRIEND_ADD,
        self::WECHAT_PRIVATE_TAKE,
        self::WECHAT_CIRCLE_PUBLISH,
        self::WECHAT_CIRCLE_INTERACT,
        self::SPH_CLUE,
        self::SPH_PRIVATE_TAKE,
        self::DY_SEARCH_FRIEND_ADD,
    ];

    /**
     * 获取全部开关，1 开启 / 0 关闭
     */
    public static function all(): array
    {
        $config = ConfigService::get('rpa', 'manual_task_switch', []);
        if (!is_array($config)) {
            $config = [];
        }

        $result = [];
        foreach (self::TASK_KEYS as $key) {
            $result[$key] = array_key_exists($key, $config) ? ((int)$config[$key] === 1 ? 1 : 0) : 0;
        }
        return $result;
    }

    /**
     * 判断某个手动任务是否开启
     */
    public static function isEnabled(string $key): bool
    {
        $all = self::all();
        return (int)($all[$key] ?? 0) === 1;
    }

    /**
     * 任务已停用时的提示文案
     */
    public static function disabledMessage(): string
    {
        return '该任务已停用，请联系管理员开启';
    }
}

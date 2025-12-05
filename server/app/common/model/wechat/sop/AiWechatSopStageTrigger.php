<?php
declare (strict_types = 1);

namespace app\common\model\wechat\sop;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class AiWechatSopStageTrigger extends BaseModel
{
    // 匹配类型
    const MATCH_TYPE_ACTION = 1;    // 动作匹配
    const MATCH_TYPE_CHAT = 2;      // 聊天内容匹配

    // 动作类型
    const ACTION_TYPE_NEW_FRIEND = 1;  // 刚加好友

    // 聊天匹配模式
    const CHAT_MODE_FUZZY = 1;     // 模糊匹配
    const CHAT_MODE_EXACT = 2;     // 精确匹配

    // 聊天匹配对象
    const CHAT_OBJECT_AI = 1;      // AI回复
    const CHAT_OBJECT_CUSTOMER = 2; // 客户回复

    // 状态
    const STATUS_DISABLE = 0;      // 禁用
    const STATUS_ENABLE = 1;       // 启用


    use SoftDelete;

    protected $deleteTime = 'delete_time';

    /**
     * 获取匹配类型文本
     */
    public static function getMatchTypeText($type): string
    {
        $types = [
            self::MATCH_TYPE_ACTION => '动作匹配',
            self::MATCH_TYPE_CHAT => '聊天内容匹配'
        ];
        return $types[$type] ?? '未知类型';
    }

    /**
     * 获取动作类型文本
     */
    public static function getActionTypeText($type): string
    {
        $types = [
            self::ACTION_TYPE_NEW_FRIEND => '刚加好友'
        ];
        return $types[$type] ?? '未知动作';
    }

    /**
     * 获取聊天匹配模式文本
     */
    public static function getChatModeText($mode): string
    {
        $modes = [
            self::CHAT_MODE_FUZZY => '模糊匹配',
            self::CHAT_MODE_EXACT => '精确匹配'
        ];
        return $modes[$mode] ?? '未知模式';
    }

    /**
     * 获取聊天匹配对象文本
     */
    public static function getChatObjectText($object): string
    {
        $objects = [
            self::CHAT_OBJECT_AI => 'AI回复',
            self::CHAT_OBJECT_CUSTOMER => '客户回复'
        ];
        return $objects[$object] ?? '未知对象';
    }

    /**
     * 获取状态文本
     */
    public static function getStatusText($status): string
    {
        $statusMap = [
            self::STATUS_DISABLE => '禁用',
            self::STATUS_ENABLE => '启用'
        ];
        return $statusMap[$status] ?? '未知状态';
    }
} 
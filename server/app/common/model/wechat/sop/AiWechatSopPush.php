<?php
declare (strict_types = 1);

namespace app\common\model\wechat\sop;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class AiWechatSopPush extends BaseModel
{
    use SoftDelete;

    protected $deleteTime = 'delete_time';

    // 定义状态常量
    const STATUS_UNCONFIGURED = 0; // 未配置
    const STATUS_PAUSED = 1; // 暂停
    const STATUS_ACTIVE = 2; // 开启

    // 定义类型常量
    const TYPE_CONTENT_SEQUENCE = 0; // 群发任务
    const TYPE_FLOW_PUSH = 1; // 流程推送
    const TYPE_STAGE_PUSH = 2; // 阶段推送
    const TYPE_BIRTHDAY_PUSH = 3; // 生日推送
    const TYPE_HOLIDAY_PUSH = 4; // 节日推送

    // 定义推送类型常量
    const PUSH_TYPE_CONTENT_SEQUENCE = 0; // 群发任务
    const PUSH_TYPE_SOP = 1; // SOP推送

    // 定义默认值
    protected $insert = [
        'status' => self::STATUS_UNCONFIGURED
    ];

    /**
     * 获取状态文字说明
     * @param int $status
     * @return string
     */
    public static function getStatusText($status): string
    {
        $statusMap = [
            self::STATUS_UNCONFIGURED => '未配置',
            self::STATUS_PAUSED => '暂停',
            self::STATUS_ACTIVE => '开启'
        ];
        return $statusMap[$status] ?? '未知状态';
    }

    /**
     * 获取类型文字说明
     * @param int $type
     * @return string
     */
    public static function getTypeText($type): string
    {
        $typeMap = [
            self::TYPE_CONTENT_SEQUENCE => '群发任务',
            self::TYPE_FLOW_PUSH => '流程推送',
            self::TYPE_STAGE_PUSH => '阶段推送',
            self::TYPE_BIRTHDAY_PUSH => '生日推送',
            self::TYPE_HOLIDAY_PUSH => '节日推送'
        ];
        return $typeMap[$type] ?? '未知类型';
    }

    /**
     * 获取推送类型文字说明
     * @param int $pushType
     * @return string
     */
    public static function getPushTypeText($pushType): string
    {
        $pushTypeMap = [
            self::PUSH_TYPE_CONTENT_SEQUENCE => '群发任务',
            self::PUSH_TYPE_SOP => 'SOP推送'
        ];
        return $pushTypeMap[$pushType] ?? '未知推送类型';
    }
} 
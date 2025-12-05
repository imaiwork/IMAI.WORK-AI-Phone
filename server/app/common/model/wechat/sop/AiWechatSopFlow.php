<?php

namespace app\common\model\wechat\sop;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;


class AiWechatSopFlow extends BaseModel
{
    // 定义流程状态常量
    const STATUS_CLOSE = 0;    // 关闭
    const STATUS_OPEN = 1;     // 开启

    // 定义默认值
    protected $insert = [
        'status' => self::STATUS_OPEN
    ];

    use SoftDelete;

    protected $deleteTime = 'delete_time';
    /**
     * 获取状态文字说明
     * @param int $status
     * @return string
     */
    public static function getStatusText($status): string
    {
        $statusMap = [
            self::STATUS_CLOSE => '关闭',
            self::STATUS_OPEN => '开启'
        ];
        return $statusMap[$status] ?? '未知状态';
    }
} 
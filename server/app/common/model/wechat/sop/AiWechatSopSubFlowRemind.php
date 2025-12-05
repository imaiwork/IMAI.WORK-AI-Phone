<?php
declare (strict_types = 1);

namespace app\common\model\wechat\sop;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class AiWechatSopSubFlowRemind extends BaseModel
{
    // 定义状态常量
    const STATUS_STOP = 0; // 停留
    const STATUS_UNCONTACTED = 1; // 未联系

    // 定义默认值
    protected $insert = [
        'status' => self::STATUS_STOP
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
            self::STATUS_STOP => '停留',
            self::STATUS_UNCONTACTED => '未联系'
        ];
        return $statusMap[$status] ?? '未知状态';
    }
} 
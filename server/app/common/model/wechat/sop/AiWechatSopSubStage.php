<?php

namespace app\common\model\wechat\sop;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class AiWechatSopSubStage extends BaseModel
{
    // 定义阶段状态常量
    const STAGE_STATUS_KEY = 0;    // 关键阶段
    const STAGE_STATUS_WARN = 1;   // 警示阶段
    const STAGE_STATUS_OTHER = 2;  // 其他阶段

    // 默认子阶段配置
    const DEFAULT_SUB_STAGES = [
        [
            'sub_stage_name' => '新增用户',
            'status' => self::STAGE_STATUS_KEY,  // 关键阶段
            'sort' => 999
        ],
        [
            'sub_stage_name' => '流失用户',
            'status' => self::STAGE_STATUS_WARN,  // 警示阶段
            'sort' => 0
        ]
    ];
    use SoftDelete;

    protected $deleteTime = 'delete_time';

    /**
     * 获取阶段状态文字说明
     * @param int $status
     * @return string
     */
    public static function getStatusText($status): string
    {
        $statusMap = [
            self::STAGE_STATUS_KEY => '关键阶段',
            self::STAGE_STATUS_WARN => '警示阶段',
            self::STAGE_STATUS_OTHER => '其他阶段'
        ];
        return $statusMap[$status] ?? '未知状态';
    }

    /**
     * 判断是否为保护阶段（关键阶段或警示阶段）
     * @return bool
     */
    public function isProtectedStage(): bool
    {
        return in_array($this->status, [self::STAGE_STATUS_KEY, self::STAGE_STATUS_WARN]);
    }
}

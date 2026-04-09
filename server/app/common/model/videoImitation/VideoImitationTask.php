<?php

namespace app\common\model\videoImitation;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 视频仿写任务模型
 */
class VideoImitationTask extends BaseModel
{
    use SoftDelete;

    // 自定义表名
    protected $name = 'video_imitation_task';

    // 定义软删除时间戳字段
    protected $deleteTime = 'delete_time';

    public function searchStartTimeAttr($query, $value)
    {
        if ($value) {
            $query->where('t.create_time', '>=', strtotime($value));
        }
    }

    public function searchEndTimeAttr($query, $value)
    {
        if ($value) {
            $query->where('t.create_time', '<=', strtotime($value));
        }
    }

}

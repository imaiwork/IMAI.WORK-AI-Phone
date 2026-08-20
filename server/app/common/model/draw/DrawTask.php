<?php

declare(strict_types=1);

namespace app\common\model\draw;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * draw 生成任务
 */
class DrawTask extends BaseModel
{
    use SoftDelete;

    protected $name = 'draw_task';
    protected $deleteTime = 'delete_time';

    protected $json = ['params', 'mid_raw', 'bill_snapshot'];
    protected $jsonAssoc = true;

    public function assets()
    {
        return $this->hasMany(DrawAsset::class, 'task_id', 'id');
    }
}

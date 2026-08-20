<?php

declare(strict_types=1);

namespace app\common\model\draw;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * draw 生成产物
 */
class DrawAsset extends BaseModel
{
    use SoftDelete;

    protected $name = 'draw_asset';
    protected $deleteTime = 'delete_time';

    protected $json = ['extra'];
    protected $jsonAssoc = true;

    public function task()
    {
        return $this->belongsTo(DrawTask::class, 'task_id', 'id');
    }
}

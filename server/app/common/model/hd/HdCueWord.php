<?php

namespace app\common\model\hd;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class HdCueWord extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    public function category()
    {
        return $this->hasOne(HdCueWordCategory::class, 'id', 'cid');
    }
}

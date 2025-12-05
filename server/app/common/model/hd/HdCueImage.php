<?php

namespace app\common\model\hd;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class HdCueImage extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    public function category()
    {
        return $this->hasOne(HdCueImageCategory::class, 'id', 'cid');
    }
}
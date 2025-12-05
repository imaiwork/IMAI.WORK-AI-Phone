<?php

namespace app\common\model\hd;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class HdCueImageCategory extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    public function subs()
    {
        return $this->hasMany(HdCueImage::class, 'cid', 'id');
    }
}

<?php

namespace app\common\model\hd;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class HdCueWordCategory extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    public function subs()
    {
        return $this->hasMany(HdCueWord::class, 'cid', 'id');
    }
}

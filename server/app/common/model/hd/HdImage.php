<?php

namespace app\common\model\hd;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class HdImage extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
}

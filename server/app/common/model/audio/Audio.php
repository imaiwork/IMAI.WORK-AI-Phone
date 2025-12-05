<?php

namespace app\common\model\audio;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class Audio extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
}

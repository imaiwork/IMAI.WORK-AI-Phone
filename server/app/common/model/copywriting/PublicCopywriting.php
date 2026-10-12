<?php

namespace app\common\model\copywriting;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class PublicCopywriting extends BaseModel
{
    use SoftDelete;

    protected $name = 'public_copywriting';
    protected $deleteTime = 'delete_time';
    protected $autoWriteTimestamp = true;
}

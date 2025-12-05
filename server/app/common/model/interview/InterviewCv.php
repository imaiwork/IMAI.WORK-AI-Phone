<?php

namespace app\common\model\interview;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class InterviewCv extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
}

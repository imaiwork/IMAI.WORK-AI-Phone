<?php

namespace app\common\model\interview;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class InterviewFeedback extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
}

<?php
declare (strict_types = 1);

namespace app\common\model\wechat\sop;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class AiWechatSopPushContent extends BaseModel
{
    use SoftDelete;

    protected $deleteTime = 'delete_time';
} 
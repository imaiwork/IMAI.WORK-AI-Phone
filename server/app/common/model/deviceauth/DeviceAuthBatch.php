<?php

namespace app\common\model\deviceauth;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class DeviceAuthBatch extends BaseModel
{
    use SoftDelete;
    protected string $deleteTime = 'delete_time';
}

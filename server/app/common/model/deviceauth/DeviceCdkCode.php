<?php

namespace app\common\model\deviceauth;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class DeviceCdkCode extends BaseModel
{
    use SoftDelete;

    protected $name = 'device_cdk_code';
    protected string $deleteTime = 'delete_time';
}

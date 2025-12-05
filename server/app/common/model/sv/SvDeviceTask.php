<?php

namespace app\common\model\sv;

use app\common\model\BaseModel;

use think\model\concern\SoftDelete;
class SvDeviceTask extends BaseModel {

    protected $deleteTime = 'delete_time';
}

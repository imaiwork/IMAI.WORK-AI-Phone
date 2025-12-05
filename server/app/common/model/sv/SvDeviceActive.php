<?php

namespace app\common\model\sv;

use app\common\model\BaseModel;

use think\model\concern\SoftDelete;
class SvDeviceActive extends BaseModel {

    protected $deleteTime = 'delete_time';
}

<?php

namespace app\common\model\sv;

use app\common\model\BaseModel;

use think\model\concern\SoftDelete;
class SvDeviceTakeOverTaskAccount extends BaseModel {

    protected $deleteTime = 'delete_time';
}

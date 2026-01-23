<?php

namespace app\common\model\auto;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class AutoNeedsAnalysis extends BaseModel {

    use SoftDelete;
    protected $deleteTime = 'delete_time';

}
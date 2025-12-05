<?php

namespace app\common\model\sv;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class SvCrawlingRecord extends BaseModel {

    use SoftDelete;

    protected $deleteTime = 'delete_time';
}
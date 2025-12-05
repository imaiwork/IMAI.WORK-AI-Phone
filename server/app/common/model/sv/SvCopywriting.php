<?php

namespace app\common\model\sv;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class SvCopywriting extends BaseModel {
    // 这里可以添加模型特有的方法
    use SoftDelete;
    protected $deleteTime = 'delete_time';
}
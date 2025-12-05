<?php

namespace app\common\model\sv;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class SvCopywritingContent extends BaseModel {
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    // 这里可以添加模型特有的方法
}
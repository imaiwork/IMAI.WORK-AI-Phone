<?php

namespace app\common\model\kb;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 知识库共享模型
 */
class KbKnowShared extends BaseModel
{
    use SoftDelete;

    protected string $deleteTime = 'delete_time';
}
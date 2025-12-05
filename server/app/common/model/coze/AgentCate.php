<?php

namespace app\common\model\coze;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 模型
 * Class Role
 */
class AgentCate extends BaseModel
{
    use SoftDelete;

    protected $deleteTime = 'delete_time';
}

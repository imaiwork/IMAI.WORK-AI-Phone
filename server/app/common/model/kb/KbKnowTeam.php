<?php

namespace app\common\model\kb;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 知识库团队模型
 */
class KbKnowTeam extends BaseModel
{
    use SoftDelete;

    protected string $deleteTime = 'delete_time';
}
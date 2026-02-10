<?php


namespace app\common\model\kb;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 智能体分组
 */
class KbRobotGroup extends BaseModel
{
    use SoftDelete;

    protected string $deleteTime = 'delete_time';
}
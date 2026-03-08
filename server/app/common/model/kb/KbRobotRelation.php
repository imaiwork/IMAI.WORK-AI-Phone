<?php


namespace app\common\model\kb;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 后台智能体绑定用户
 */
class KbRobotRelation extends BaseModel
{
    use SoftDelete;

    protected string $deleteTime = 'delete_time';
}
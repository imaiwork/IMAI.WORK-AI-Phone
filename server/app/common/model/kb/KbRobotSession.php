<?php


namespace app\common\model\kb;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 机器人会话分类表
 */
class KbRobotSession extends BaseModel
{
    use SoftDelete;

    protected string $deleteTime = 'delete_time';
}
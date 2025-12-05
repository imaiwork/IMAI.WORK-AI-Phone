<?php


namespace app\common\model\kb;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 机器人发布模型
 */
class KbRobotPublish extends BaseModel
{
    use SoftDelete;

    protected string $deleteTime = 'delete_time';
}
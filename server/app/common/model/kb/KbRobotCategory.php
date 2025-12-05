<?php


namespace app\common\model\kb;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 机器人分类模型
 */
class KbRobotCategory extends BaseModel
{
    use SoftDelete;

    protected string $deleteTime = 'delete_time';
}
<?php


namespace app\common\model\kb;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 机器人广场模型
 */
class KbRobotSquare extends BaseModel
{
    use SoftDelete;

    protected string $deleteTime = 'delete_time';
}
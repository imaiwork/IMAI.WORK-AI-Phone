<?php


namespace app\common\model\kb;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 机器人模型
 */
class KbRobot extends BaseModel
{
    use SoftDelete;

    protected string $deleteTime = 'delete_time';

    protected $json = ['flow_config'];

    protected $jsonAssoc = true;
}
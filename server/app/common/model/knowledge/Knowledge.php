<?php


namespace app\common\model\knowledge;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * HumanAnchor
 * @desc 数字人形象
 * @author dagouzi
 */
class Knowledge extends BaseModel
{
    use SoftDelete;

    protected $deleteTime = 'delete_time';
}
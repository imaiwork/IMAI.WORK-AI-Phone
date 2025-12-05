<?php


namespace app\common\model\human;

use app\common\model\BaseModel;
use app\common\service\FileService;
use think\model\concern\SoftDelete;

/**
 * HumanVideo
 * @desc 数字人定时任务
 * @author dagouzi
 */
class HumanTask extends BaseModel
{
    use SoftDelete;

    protected $deleteTime = 'delete_time';
}

<?php


namespace app\common\model\human;

use app\common\model\BaseModel;
use app\common\service\FileService;
use think\model\concern\SoftDelete;

/**
 * HumanVideo
 * @desc 数字人生成视频任务
 * @author dagouzi
 */
class HumanVideoTask extends BaseModel
{
    use SoftDelete;

    protected $deleteTime = 'delete_time';
}

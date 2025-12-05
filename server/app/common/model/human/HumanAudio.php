<?php


namespace app\common\model\human;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * HumanAudio
 * @desc 数字人音频
 * @author dagouzi
 */
class HumanAudio extends BaseModel
{
    use SoftDelete;

    protected $deleteTime = 'delete_time';
}

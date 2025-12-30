<?php


namespace app\common\model\digitalHuman;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * digitalHuman
 * @desc 数字人公共形象
 */
class DigitalHumanAnchor extends BaseModel
{
    use SoftDelete;
    protected string $deleteTime = 'delete_time';
}

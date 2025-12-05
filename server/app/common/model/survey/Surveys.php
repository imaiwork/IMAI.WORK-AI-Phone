<?php


namespace app\common\model\survey;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;


/**
 * 调查问卷模型
 * Class Survey
 * @package app\common\model
 */
class Surveys extends BaseModel
{
    use SoftDelete;

    protected $deleteTime = 'delete_time';
}

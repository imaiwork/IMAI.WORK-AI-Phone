<?php


namespace app\common\model\user;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 用户活跃记录模型
 * Class UserActiveLog
 * @package app\common\model\user
 */
class UserActiveLog extends BaseModel
{
    use SoftDelete;

    protected $deleteTime = 'delete_time';
}

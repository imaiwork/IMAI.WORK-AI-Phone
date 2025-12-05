<?php


namespace app\common\model\user;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 账户流水记录模型
 * Class AccountLog
 * @package app\common\model\user
 */
class UserAccountLog extends BaseModel
{
    use SoftDelete;

    protected $deleteTime = 'delete_time';
}

<?php



namespace app\common\model\user;


use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 用户模型
 * Class User
 * @package app\common\model\user
 */
class Group extends BaseModel
{
    use SoftDelete;

    protected $deleteTime = 'delete_time';
}

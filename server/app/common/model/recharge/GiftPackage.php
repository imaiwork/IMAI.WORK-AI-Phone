<?php
namespace app\common\model\recharge;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 模型
 * Class Role
 * @package app\common\model\
 */
class GiftPackage extends BaseModel
{
    use SoftDelete;

    protected $deleteTime = 'delete_time';

    /**
    public function setResultStatusAttr($value)
    {
        $status = [-1=>'失败',0=>'待转化',1=>'成功',2=>'已提交'];
        return $status[$value];
    }
    **/
}
            
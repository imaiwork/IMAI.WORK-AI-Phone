<?php


namespace app\common\model\recharge;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 充值套餐模型
 */
class RechargePackage extends BaseModel
{
    use SoftDelete;

    protected string $deleteTime = 'delete_time';
}
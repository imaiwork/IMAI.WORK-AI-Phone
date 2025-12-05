<?php


namespace app\common\model\recharge;

use app\common\enum\PayEnum;
use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 充值订单模型
 * Class RechargeOrder
 * @package app\common\model
 */
class RechargeOrder extends BaseModel
{
    use SoftDelete;

    protected $deleteTime = 'delete_time';


    /**
     * @notes 支付方式
     * @param $value
     * @return string|string[]
     * @author 段誉
     * @date 2023/2/23 18:32
     */
    public function getPayWayTextAttr($value, $data)
    {
        return PayEnum::getPayDesc($data['pay_way']);
    }


    /**
     * @notes 支付状态
     * @param $value
     * @return string|string[]
     * @author 段誉
     * @date 2023/2/23 18:32
     */
    public function getPayStatusTextAttr($value, $data)
    {
        return PayEnum::getPayStatusDesc($data['pay_status']);
    }
}

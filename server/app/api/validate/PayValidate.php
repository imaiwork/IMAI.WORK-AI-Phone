<?php



namespace app\api\validate;

use app\common\enum\PayEnum;
use app\common\validate\BaseValidate;

/**
 * 支付验证
 * Class PayValidate
 * @package app\api\validate
 */
class PayValidate extends BaseValidate
{
    protected $rule = [
        'from'      => 'require',
        'pay_way'   => 'require|in:' . PayEnum::BALANCE_PAY . ',' . PayEnum::WECHAT_PAY . ',' . PayEnum::ALI_PAY,
        'order_id'  => 'require'
    ];


    protected $message = [
        'from.require'      => '参数缺失',
        'pay_way.require'   => '支付方式参数缺失',
        'pay_way.in'        => '支付方式参数错误',
        'order_id.require'  => '订单参数缺失'
    ];


    /**
     * @notes 支付方式场景
     * @return PayValidate
     * @author 段誉
     * @date 2023/2/24 17:43
     */
    public function scenePayway()
    {
        //        return $this->only(['from', 'order_id']);
        return $this->only(['from']);
    }


    /**
     * @notes 支付状态
     * @return PayValidate
     * @author 段誉
     * @date 2023/3/1 16:17
     */
    public function sceneStatus()
    {
        return $this->only(['from', 'order_id']);
    }
}

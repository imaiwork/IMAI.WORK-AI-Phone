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
        'order_id'  => 'require',
        'package_id' => 'integer|gt:0',
        'plan_id' => 'integer|gt:0',
        'quantity' => 'integer|gt:0',
        'biz_type' => 'in:1,2',
        'device_id' => 'integer|gt:0',
        'device_code' => 'max:128',
        // code 仅虚拟支付预下单必填，不可放全局 require（否则普通 prePay 会误报「登录code缺失」）
        'code' => 'max:128',
        'platform' => 'in:android,ios',
        'buy_quantity' => 'integer|gt:0',
        'product_id' => 'max:64',
        'zone_id' => 'max:64',
    ];


    protected $message = [
        'from.require'      => '参数缺失',
        'pay_way.require'   => '支付方式参数缺失',
        'pay_way.in'        => '支付方式参数错误',
        'order_id.require'  => '订单参数缺失',
        'code.require' => '小程序登录code缺失',
        'platform.in' => '客户端平台参数错误',
        'buy_quantity.integer' => '虚拟商品数量必须为整数',
        'buy_quantity.gt' => '虚拟商品数量必须大于0',
        'product_id.max' => '虚拟商品ID不能超过64个字符',
        'zone_id.max' => '虚拟支付分区不能超过64个字符',
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
     * @notes 普通预支付（微信/支付宝），不要求小程序 login code
     */
    public function scenePrepay()
    {
        return $this->only(['from', 'pay_way', 'order_id']);
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


    /**
     * @notes 小程序虚拟支付预下单
     * @return PayValidate
     */
    public function sceneMnpVirtualPrepay()
    {
        // from/package_id/plan_id 按业务在服务层校验，避免互相强制必填
        return $this->only([
            'from',
            'package_id',
            'plan_id',
            'quantity',
            'biz_type',
            'device_id',
            'device_code',
            'code',
            'platform',
            'buy_quantity',
            'product_id',
            'zone_id',
        ])
            ->append('code', 'require')
            ->remove('from', 'require');
    }


    /**
     * @notes 小程序虚拟支付确认
     * @return PayValidate
     */
    public function sceneMnpVirtualConfirm()
    {
        return $this->only(['order_id', 'from'])->remove('from', 'require');
    }
}

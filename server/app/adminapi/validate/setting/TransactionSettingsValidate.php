<?php


namespace app\adminapi\validate\setting;

use app\common\validate\BaseValidate;

/**
 * 交易设置验证
 * Class TransactionSettingsValidate
 * @package app\adminapi\validate\setting
 */
class TransactionSettingsValidate extends BaseValidate
{
    protected $rule = [
        'cancel_unpaid_orders' => 'require|in:0,1',
        'cancel_unpaid_orders_times' => 'requireIf:cancel_unpaid_orders,1|integer|gt:0',
        'verification_orders' => 'require|in:0,1',
        'verification_orders_times' => 'requireIf:verification_orders,1|integer|gt:0',
    ];

    protected $message = [
        'cancel_unpaid_orders.require' => '请选择系统取消待付款订单方式',
        'cancel_unpaid_orders.in' => '系统取消待付款订单状态值有误',
        'cancel_unpaid_orders_times.requireIf' => '系统取消待付款订单时间未填写',
        'cancel_unpaid_orders_times.integer' => '系统取消待付款订单时间须为整型',
        'cancel_unpaid_orders_times.gt' => '系统取消待付款订单时间须大于0',

        'verification_orders.require' => '请选择系统自动核销订单方式',
        'verification_orders.in' => '系统自动核销订单状态值有误',
        'verification_orders_times.requireIf' => '系统自动核销订单时间未填写',
        'verification_orders_times.integer' => '系统自动核销订单时间须为整型',
        'verification_orders_times.gt' => '系统自动核销订单时间须大于0',
    ];

    public function sceneSetConfig()
    {
        return $this->only(['cancel_unpaid_orders', 'cancel_unpaid_orders_times', 'verification_orders', 'verification_orders_times']);
    }
}

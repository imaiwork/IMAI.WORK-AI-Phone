<?php

namespace app\api\validate;

use app\common\validate\BaseValidate;

class DeviceAuthValidate extends BaseValidate
{
    protected $rule = [
        'plan_id'     => 'require|integer|gt:0',
        'quantity'    => 'require|integer|gt:0|elt:50',
        'pay_type'    => 'require|in:1,2',
        'device_id'   => 'require|integer|gt:0',
        'device_code' => 'require',
        'code'        => 'require',
        'user_id'     => 'require|integer|gt:0',
        'tab'         => 'in:all,active,inactive',
    ];

    protected $message = [
        'plan_id.require'     => '请选择套餐',
        'quantity.require'    => '请输入购买数量',
        'quantity.elt'        => '单次最多购买50个',
        'pay_type.require'    => '请选择支付方式',
        'device_id.require'   => '请选择设备',
        'device_code.require' => '设备号不能为空',
        'code.require'        => '请输入设备CDK',
        'user_id.require'     => '用户ID不能为空',
        'user_id.gt'          => '用户ID无效',
    ];

    public function scenePurchase()
    {
        return $this->only(['plan_id', 'quantity', 'pay_type']);
    }

    public function sceneRenew()
    {
        return $this->only(['plan_id', 'pay_type', 'device_id', 'device_code']);
    }

    public function sceneActivate()
    {
        return $this->only(['code', 'device_code']);
    }

    public function sceneActivateDevice()
    {
        return $this->only(['code', 'device_code', 'user_id']);
    }

    public function sceneAddPhone()
    {
        return $this->only(['device_code']);
    }

    public function scenePhoneList()
    {
        return $this->only(['tab']);
    }
}

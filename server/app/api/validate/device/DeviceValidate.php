<?php

namespace app\api\validate\device;

use app\common\validate\BaseValidate;

class DeviceValidate extends BaseValidate
{
    protected $rule = [
        'device_code'         => 'require',
        'activation_code'     => 'require',
        'activation_code_url' => 'max:512',
        'cdk_code'            => 'require',
    ];

    protected $message = [
        'device_code.require'       => '设备号不能为空',
        'activation_code.require'   => '激活码不能为空',
        'activation_code_url.max'   => '激活码图片地址过长',
        'cdk_code.require'          => '设备CDK不能为空',
    ];

    public function sceneScanOldQrcode()
    {
        return $this->only(['device_code']);
    }

    public function sceneRedeem()
    {
        return $this->only(['cdk_code', 'device_code']);
    }
}

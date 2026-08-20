<?php

namespace app\api\validate\device;

use app\common\validate\BaseValidate;

/**
 * 设备账号校验
 */
class AccountValidate extends BaseValidate
{
    protected $rule = [
        'device_code' => 'require',
        'type'        => 'require|in:1,3,4,5',
    ];

    protected $message = [
        'device_code.require' => '请输入设备码',
        'type.require'        => '请输入账号类型',
        'type.in'             => '账号类型仅支持1/3/4/5',
    ];

    /**
     * @notes 触发拉取平台账号
     */
    public function sceneFetch()
    {
        return $this->only(['device_code', 'type']);
    }
}

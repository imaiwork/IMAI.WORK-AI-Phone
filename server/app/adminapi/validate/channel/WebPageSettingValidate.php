<?php

namespace app\adminapi\validate\channel;

use app\common\validate\BaseValidate;

/**
 * H5设置验证器
 * Class HFiveSettingValidate
 * @package app\adminapi\validate\setting\h5
 */
class WebPageSettingValidate extends BaseValidate
{
    protected $rule = [
        'status' => 'require|in:0,1'
    ];

    protected $message = [
        'status.require' => '请选择启用状态',
        'status.in' => '启用状态值有误',
    ];
}

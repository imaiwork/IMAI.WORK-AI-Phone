<?php

namespace app\adminapi\validate\channel;

use app\common\validate\BaseValidate;

/**
 * 开放平台验证
 * Class OpenSettingValidate
 * @package app\adminapi\validate\channel
 */
class OpenSettingValidate extends BaseValidate
{
    protected $rule = [
        'app_id' => 'require',
        'app_secret' => 'require',
    ];

    protected $message = [
        'app_id.require' => '请输入appId',
        'app_secret.require' => '请输入appSecret',
    ];
}

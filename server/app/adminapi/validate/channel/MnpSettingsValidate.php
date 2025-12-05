<?php


namespace app\adminapi\validate\channel;


use app\common\validate\BaseValidate;

/**
 * 小程序设置
 * Class MnpSettingsValidate
 * @package app\adminapi\validate\channel
 */
class MnpSettingsValidate extends BaseValidate
{
    protected $rule = [
        'app_id' => 'require',
        'app_secret' => 'require',
    ];

    protected $message = [
        'app_id.require' => '请填写AppID',
        'app_secret.require' => '请填写AppSecret',
    ];
}

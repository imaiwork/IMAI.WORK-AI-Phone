<?php


namespace app\api\validate;


use app\common\validate\BaseValidate;


/**
 * 短信验证
 * Class SmsValidate
 * @package app\api\validate
 */
class SendSmsValidate extends BaseValidate
{

    protected $rule = [
        'mobile' => 'require|mobile',
        'scene' => 'require',
    ];

    protected $message = [
        'mobile.require' => '请输入手机号',
        'mobile.mobile' => '请输入正确手机号',
        'scene.require' => '请输入场景值',
    ];
}

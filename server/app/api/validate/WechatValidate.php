<?php


namespace app\api\validate;

use app\common\validate\BaseValidate;

/**
 * 微信验证器
 * Class WechatValidate
 * @package app\api\validate
 */
class WechatValidate extends BaseValidate
{
    public $rule = [
        'url' => 'require'
    ];

    public $message = [
        'url.require' => '请提供url'
    ];

    public function sceneJsConfig()
    {
        return $this->only(['url']);
    }
}

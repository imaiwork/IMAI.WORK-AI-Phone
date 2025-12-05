<?php


namespace app\api\validate;

use app\common\validate\BaseValidate;

/**
 * 微信验证器
 * Class WechatValidate
 * @package app\api\validate
 */
class GptThreadValidate extends BaseValidate
{
    public $rule = [
        'assistant_id' => 'require',
        'name' => 'require',
    ];

    public $message = [
        'assistant_id.require' => '请提供助手id',
        'name.require' => '请提供会话名称',
    ];

    protected $scene = [
        'add' => [
            'assistant_id',
            'name',
        ]
    ];
}

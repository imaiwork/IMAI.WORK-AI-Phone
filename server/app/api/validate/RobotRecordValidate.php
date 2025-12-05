<?php


namespace app\api\validate;

use app\common\validate\BaseValidate;

/**
 * 微信验证器
 * Class WechatValidate
 * @package app\api\validate
 */
class RobotRecordValidate extends BaseValidate
{
    public $rule = [
        'message'       => 'require',
        'assistants_id' => 'require',
        'thread_id'     => 'requireIf:is_debug,1',
    ];

    public $message = [
        'message.require'       => '信息丢失',
        'assistants_id.require' => '助手参数丢失',
        'thread_id.requireIf'     => '线程参数丢失',
    ];

    protected $scene = [
        'chat' => [
            'message',
            'assistants_id',
            'thread_id',
        ]
    ];
}

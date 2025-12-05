<?php


namespace app\adminapi\validate\notice;

use app\common\validate\BaseValidate;

/**
 * 短信配置验证
 * Class SmsConfigValidate
 * @package app\adminapi\validate\notice
 */
class SmsConfigValidate extends BaseValidate
{
    protected $rule = [
        'type' => 'require',
        'sign' => 'require',
        'app_id' => 'requireIf:type,tencent',
        'app_key' => 'requireIf:type,ali',
        'secret_id' => 'requireIf:type,tencent',
        'secret_key' => 'require',
        'status' => 'require',
    ];

    protected $message = [
        'type.require' => '请选择类型',
        'sign.require' => '请输入签名',
        'app_id.requireIf' => '请输入app_id',
        'app_key.requireIf' => '请输入app_key',
        'secret_id.requireIf' => '请输入secret_id',
        'secret_key.require' => '请输入secret_key',
        'status.require' => '请选择状态',
    ];


    protected function sceneDetail()
    {
        return $this->only(['type']);
    }
}

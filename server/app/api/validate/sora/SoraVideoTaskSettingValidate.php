<?php

namespace app\api\validate\sora;

use think\Validate;

class SoraVideoTaskSettingValidate extends Validate
{
    protected $rule = [
        'id' => 'require|integer',
    ];

    protected $message = [
        'id.require' => 'id 是必须的',
        'id.integer' => 'id 必须是整数',
    ];

    protected $scene = [
        'retry' => ['id'],
    ];
}
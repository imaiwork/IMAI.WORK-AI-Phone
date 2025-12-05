<?php

namespace app\api\validate\coze;

use app\common\validate\BaseValidate;

class CozeConfigValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|number',
        'source' => 'in:0,1',
        'source_id' => 'number',
        'secret_token' => 'require|max:255',
    ];

    protected $message = [
        'id.require' => 'ID是必填项',
        'id.number' => 'ID必须为数字',
        'source.in' => '来源类型只能是0或1',
        'source_id.number' => '创建者id必须为数字',
        'secret_token.require' => 'secret_token是必填项',
        'secret_token.max' => 'secret_token不能超过255个字符',
    ];

    public function sceneAdd()
    {
        return $this->only(['secret_token'])
            ->append('secret_token', 'require');
    }

    public function sceneUpdate()
    {
        return $this->only(['id','secret_token']);
    }

    public function sceneDelete()
    {
        return $this->only(['id']);
    }
}



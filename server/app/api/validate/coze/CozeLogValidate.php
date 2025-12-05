<?php

namespace app\api\validate\coze;

use app\common\validate\BaseValidate;

class CozeLogValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|number',
        'bot_id' => 'require|max:255',
    ];

    protected $message = [
        'id.require' => 'ID是必填项',
        'id.number' => 'ID必须为数字',
        'bot_id.require' => 'bot_id是必填项',
        'bot_id.max' => 'bot_id不能超过255个字符',
    ];



    public function sceneDelete()
    {
        return $this->only(['bot_id']);
    }
}



<?php

namespace app\adminapi\validate\coze;

use app\common\validate\BaseValidate;

class CozeLogValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|number',
        'message_id' => 'require',
        'conversation_id' => 'require|max:255',

    ];

    protected $message = [
        'id.require' => 'ID是必填项',
        'id.number' => 'ID必须为数字',
        'conversation_id.require' => 'conversation_id是必填项',
        'conversation_id.max' => 'conversation_id不能超过255个字符',
        'message_id.require' => 'message_id是必填项',
    ];



    public function sceneDelete()
    {
        return $this->only(['conversation_id']);
    }
    public function sceneReply()
    {
        return $this->only(['message_id']);
    }
}



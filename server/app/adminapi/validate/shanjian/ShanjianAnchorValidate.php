<?php

namespace app\adminapi\validate\shanjian;

use app\common\validate\BaseValidate;

class ShanjianAnchorValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'number',
        'task_id' => 'max:50',
        'status' => 'in:1,2,3,4,5,6',
        'pic' => 'max:255',
        'anchor_id' => 'max:50',
        'voice_id' => 'max:50',
        'voice_model' => 'max:50',
        'name' => 'require|max:50',
        'voice_url' => 'max:255',
        'remark' => 'max:500',
        'token' => 'max:10',
        'anchor_url' => 'max:255',
        'authorized_pic' => 'max:255',
        'authorized_url' => 'max:255',
    ];

    protected $message = [
        'name.require' => '名称是必填项',
    ];

    public function sceneDelete()
    {
        return $this->only(['id']);
    }
}



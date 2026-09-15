<?php

namespace app\api\validate\shanjian;

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
        'anchor_url' => 'require|max:255',
        'authorized_pic' => 'max:255',
        'authorized_url' => 'require|max:255',
    ];

    protected $message = [
        'name.require' => '名称是必填项',
        'anchor_url.require' => '形象视频是必填项',
        'authorized_url.require' => '授权视频是必填项',
    ];

    public function sceneAdd()
    {
        return $this->only(['task_id','status','pic','anchor_id','voice_id','voice_model','voice_url','remark','token','anchor_url','authorized_pic','authorized_url']);
    }

    public function sceneUpdate()
    {
        return $this->only(['id','name','task_id','status','pic','anchor_id','voice_id','voice_model','voice_url','remark','token','anchor_url','authorized_pic','authorized_url']);
    }

    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    public function sceneDelete()
    {
        return $this->only(['id']);
    }
}



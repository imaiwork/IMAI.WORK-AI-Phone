<?php

namespace app\adminapi\validate\geo;

use app\common\validate\BaseValidate;

class GeoMediaValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer',
        'name' => 'require|max:100',
        'type' => 'require|max:30',
        'status' => 'require|in:0,1',
    ];

    protected $message = [
        'id.require' => '媒体ID是必填项',
        'name.require' => '媒体名称是必填项',
        'name.max' => '媒体名称不能超过100个字符',
        'type.require' => '渠道类型是必填项',
        'status.require' => '状态是必填项',
        'status.in' => '状态取值非法',
    ];

    public function sceneAdd()
    {
        return $this->only(['name', 'type']);
    }

    public function sceneEdit()
    {
        return $this->only(['id']);
    }

    public function sceneStatus()
    {
        return $this->only(['id', 'status']);
    }

    public function sceneDelete()
    {
        return $this->only(['id']);
    }
}

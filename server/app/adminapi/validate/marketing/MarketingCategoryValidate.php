<?php

namespace app\adminapi\validate\marketing;

use app\common\validate\BaseValidate;

class MarketingCategoryValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require',
        'name' => 'require|max:50'
    ];

    protected $message = [
        'id.require' => 'ID是必填项',
        'name.require' => '分类名称是必填项',
        'name.max' => '分类名称不能超过50个字符',
    ];

    public function sceneAdd()
    {
        return $this->only(['name']);
    }

    public function sceneEdit()
    {
        return $this->only(['id']);
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

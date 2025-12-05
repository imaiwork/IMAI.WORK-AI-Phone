<?php

namespace app\api\validate\shanjian;

use app\common\validate\BaseValidate;

class ShanjianCharacterDesignValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'number',
        'name' => 'require|max:255',
        'introduced' => 'require',
    ];

    protected $message = [
        'name.require' => '名称是必填项',
        'name.max' => '名称长度不能超过255',
        'introduced.require' => '人物介绍是必填项',
    ];

    public function sceneAdd()
    {
        return $this->only(['name', 'introduced']);
    }

    public function sceneUpdate()
    {
        return $this->only(['id', 'name', 'introduced']);
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



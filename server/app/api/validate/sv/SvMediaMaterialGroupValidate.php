<?php

namespace app\api\validate\sv;

use app\common\validate\BaseValidate;

class SvMediaMaterialGroupValidate extends BaseValidate
{
    protected $rule = [
        'sort' => 'number',
        'name' => 'require',
    ];

    protected $message = [
        'name.require' => '素材分组名称是必填项',
        'sort.number' => '排序值必须是整数',
    ];

    public function sceneAdd()
    {
        return $this->only(['name', 'sort']);
    }

    public function sceneUpdate()
    {
        return $this->only(['id', 'name', 'sort']);
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
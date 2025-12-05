<?php

namespace app\api\validate\sv;

use app\common\validate\BaseValidate;

class SvMediaMaterialValidate extends BaseValidate
{
    protected $rule = [
        'sort' => 'number',
        'type' => 'in:0,1,3',
        'content' => 'require',
        'm_type' => 'in:1,2,6',
    ];

    protected $message = [
        'content.require' => '素材内容是必填项',
        'sort.number' => '排序值必须是整数',
        'type.in' => '类型值只能是0,1,3',
        'm_type.in' => '素材类型值错误',
    ];

    public function sceneAdd()
    {
        return $this->only([ 'content', 'sort', 'type', 'm_type']);
    }

    public function sceneUpdate()
    {
        return $this->only(['id', 'sort', 'type', 'm_type']);
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
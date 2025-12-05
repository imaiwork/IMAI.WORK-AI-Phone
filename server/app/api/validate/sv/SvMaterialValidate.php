<?php

namespace app\api\validate\sv;

use app\common\validate\BaseValidate;

class SvMaterialValidate extends BaseValidate
{
    protected $rule = [
        'account' => 'require',
        'sort' => 'number',
        'type' => 'in:1,3',
        'content' => 'require',
        'm_type' => 'in:0,1,2,3,4,5',
    ];

    protected $message = [
        'account.require' => '平台账号是必填项',
        'content.require' => '素材内容是必填项',
        'sort.number' => '排序值必须是整数',
        'type.in' => '类型值只能是1或3',
        'm_type.in' => '素材类型值只能是0到5',
    ];

    public function sceneAdd()
    {
        return $this->only([ 'account', 'content', 'sort', 'type', 'm_type']);
    }

    public function sceneUpdate()
    {
        return $this->only(['id', 'account', 'content', 'sort', 'type', 'm_type']);
    }


    public function sceneDetail()
    {
        return $this->only(['id', 'account']);
    }

    public function sceneDelete()
    {
        return $this->only(['id']);
    }
}
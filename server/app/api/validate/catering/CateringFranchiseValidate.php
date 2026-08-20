<?php

namespace app\api\validate\catering;

use app\common\validate\BaseValidate;

class CateringFranchiseValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|number',
        'category_type' => 'in:1,2,3',
        'title' => 'max:128',
        'status' => 'in:0,1',
    ];

    protected $message = [
        'id.require' => 'ID是必填项',
        'id.number' => 'ID必须为数字',
        'category_type.in' => '分类类型只能是1、2或3',
        'title.max' => '标题不能超过128个字符',
        'status.in' => '状态值不正确',
    ];

    public function sceneDetail()
    {
        return $this->only(['id']);
    }
}

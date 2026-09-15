<?php

namespace app\api\validate\tutorial;

use app\common\validate\BaseValidate;

class TutorialValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|number',
        'tutorial_category_id' => 'number',
        'main_type' => 'in:1,2',
        'title' => 'max:128',
    ];

    protected $message = [
        'id.require' => 'ID是必填项',
        'id.number' => 'ID必须为数字',
        'tutorial_category_id.number' => '分类ID必须为数字',
        'main_type.in' => '主内容类型只能是1或2',
        'title.max' => '标题不能超过128个字符',
    ];

    public function sceneDetail()
    {
        return $this->only(['id']);
    }
}

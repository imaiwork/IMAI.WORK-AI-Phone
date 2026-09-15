<?php

namespace app\api\validate\tutorial;

use app\common\validate\BaseValidate;

class TutorialCategoryValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|number',
        'name' => 'max:64',
        'sort' => 'number|egt:0',
    ];

    protected $message = [
        'id.require' => 'ID是必填项',
        'id.number' => 'ID必须为数字',
        'name.max' => '分类名称不能超过64个字符',
        'sort.number' => '排序值必须为数字',
        'sort.egt' => '排序值不能小于0',
    ];

    public function sceneDetail()
    {
        return $this->only(['id']);
    }
}

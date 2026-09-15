<?php


namespace app\adminapi\validate\tutorial;

use app\common\validate\BaseValidate;
use app\common\model\tutorial\TutorialCategory;

/**
 * 教程分类管理验证
 * Class TutorialCategoryValidate
 * @package app\adminapi\validate\tutorial
 */
class TutorialCategoryValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|checkTutorialCategory',
        'name' => 'require|length:1,64',
        'sort' => 'egt:0',
    ];

    protected $message = [
        'id.require' => '教程分类id不能为空',
        'name.require' => '教程分类名称不能为空',
        'name.length' => '教程分类名称长度须在1-64位字符',
        'sort.egt' => '排序值不正确',
    ];

    public function sceneAdd()
    {
        return $this->remove(['id'])
            ->remove('id', 'require|checkTutorialCategory');
    }

    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    public function sceneStatus()
    {
        return $this->only(['id']);
    }

    public function sceneEdit() {
         return $this->only(['id']);
    }

    public function sceneDelete()
    {
        return $this->only(['id']);
    }

    public function checkTutorialCategory($value)
    {
        $tutorial_category = TutorialCategory::findOrEmpty($value);
        if ($tutorial_category->isEmpty()) {
            return '教程分类不存在';
        }
        return true;
    }
}

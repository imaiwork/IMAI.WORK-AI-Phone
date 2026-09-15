<?php


namespace app\adminapi\validate\tutorial;

use app\common\validate\BaseValidate;
use app\common\model\tutorial\Tutorial;

/**
 * 教程卡片管理验证
 * Class TutorialValidate
 * @package app\adminapi\validate\tutorial
 */
class TutorialValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|checkTutorial',
        'tutorial_category_id' => 'require',
        'title' => 'require|length:1,128',
        'main_type' => 'require|in:1,2',
        'main_url' => 'require',
    ];

    protected $message = [
        'id.require' => '教程id不能为空',
        'tutorial_category_id.require' => '所属分类必须存在',
        'title.require' => '标题不能为空',
        'title.length' => '标题长度须在1-128位字符',
        'main_type.require' => '主内容类型必须存在',
        'main_type.in' => '主内容类型值不正确',
        'main_url.require' => '主内容地址不能为空',
    ];

    public function sceneAdd()
    {
        return $this->remove(['id'])
            ->remove('id', 'require|checkTutorial');
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

    public function checkTutorial($value)
    {
        $tutorial = Tutorial::findOrEmpty($value);
        if ($tutorial->isEmpty()) {
            return '教程不存在';
        }
        return true;
    }
}

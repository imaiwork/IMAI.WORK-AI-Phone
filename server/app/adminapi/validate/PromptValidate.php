<?php

namespace app\adminapi\validate;


use app\common\validate\BaseValidate;

/**
 * 提示词验证
 * Class PromptValidate
 * @package app\adminapi\validate\user
 */
class PromptValidate extends BaseValidate
{

    protected $rule = [
        'id'            => 'require',
        'prompt_name'   => 'require',
        'prompt_text'   => 'require',
    ];

    protected $message = [
        'id.require'          => '请输入提示词ID',
        'prompt_name.require' => '请输入提示词名称',
        'prompt_text.require' => '请输入提示词内容',
    ];


    /**
     * @notes 更新场景
     * @return PromptValidate
     * @author 段誉
     * @date 2022/9/22 16:35
     */
    public function sceneUpdate()
    {
        return $this->only(['id', 'prompt_name', 'prompt_text']);
    }

    /**
     * @notes 获取场景
     * @return PromptValidate
     * @author 段誉
     * @date 2022/9/22 16:35
     */
    public function sceneGet()
    {
        return $this->only(['id']);
    }
}

<?php

namespace app\adminapi\validate\aiPersona;

use app\common\validate\BaseValidate;

/**
 * 客户触达自动任务校验
 * Class ClueTouchValidate
 * @package app\adminapi\validate\aiPersona
 * @author Qasim
 */
class ClueTouchValidate extends BaseValidate
{

    protected $rule = [
        'persona_id' => 'require',
        'acquire_keywords' => 'require|array',
        'intercept_keywords' => 'require|array',
        'comment_scripts' => 'require|array',
        'dm_scripts' => 'require|array',
    ];



    protected $message = [
        'id.require' => '请输入主键ID',
        'persona_id.require' => '请输入客户ID',
        'acquire_keywords.require' => '请输入获客线索词',
        'acquire_keywords.array' => '获客线索词必须是数组',
        'intercept_keywords.require' => '请输入截流线索词',
        'intercept_keywords.array' => '截流线索词必须是数组',
        'comment_scripts.require' => '请输入评论区引流话术',
        'comment_scripts.array' => '评论区引流话术必须是数组',
        'dm_scripts.require' => '请输入私信固定话术',
        'dm_scripts.array' => '私信固定话术必须是数组',
    ];

    /**
     * @notes 更新
     * @return Validate
     */
    public function sceneUpdate()
    {
        return $this->only(['persona_id', 'acquire_keywords', 'intercept_keywords', 'comment_scripts', 'dm_scripts']);
    }
    

    /**
     * @notes 详情
     * @return Validate
     */
    public function sceneDetail()
    {
        return $this->only(['id']);
    }
}


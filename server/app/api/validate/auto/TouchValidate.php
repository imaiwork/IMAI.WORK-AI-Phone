<?php

namespace app\api\validate\auto;

use app\common\validate\BaseValidate;

/**
 * 触摸词自动任务校验
 * Class TouchValidate
 * @package app\api\validate\auto
 * @author Qasim
 */
class TouchValidate extends BaseValidate
{

    protected $rule = [
        'id' => 'require',
        'device_code' => 'require',
        'exec_type' => 'require|in:1,2,3',
        'ai_direction' => 'max:255',
        'clue_theme' => 'max:255',
        'keywords' => 'require|array',
        'comment_screening' => 'array',
        'touch_speech_type' => 'require|in:1,2,3',
        'touch_speech' => 'require|array',
        'actions' => 'array',
    ];



    protected $message = [
        'id.require' => '请输入主键ID',
        'device_code.require' => '请输入设备编码',
        'exec_type.require' => '请选择执行月份',
        'exec_type.in' => '执行月份必须是1,2,3',
        'ai_direction.max' => '智能方向最多255个字符',
        'clue_theme.max' => '文本主题最多255个字符',
        'keywords.require' => '请输入关键词',
        'keywords.array' => '关键词必须是数组',
        'comment_screening.array' => '评论筛选必须是数组',
        'touch_speech_type.require' => '请选择自动触达方式',
        'touch_speech_type.in' => '自动触达方式必须是1,2,3',
        'touch_speech.require' => '请输入自动触达话术',
        'touch_speech.array' => '自动触达话术必须是数组',
        'actions.array' => '操作必须是数组',
    ];


    /**
     * @notes 添加
     * @return Validate
     */
    public function sceneAdd()
    {
        return $this->only([ 'device_code', 'exec_type', 'ai_direction', 'text_theme', 'keywords', 'comment_screening', 'touch_speech_type', 'touch_speech', 'actions']);
    }

    /**
     * @notes 更新
     * @return Validate
     */
    public function sceneUpdate()
    {
        return $this->only([ 'device_code',  'exec_type', 'ai_direction', 'text_theme', 'keywords', 'comment_screening', 'touch_speech_type', 'touch_speech', 'actions']);
    }
    /**
     * @notes 删除
     * @return Validate
     */
    public function sceneDelete()
    {
        return $this->only(['device_code']);
    }

    /**
     * @notes 详情
     * @return Validate
     */
    public function sceneDetail()
    {
        return $this->only(['device_code', 'account_type', 'scene']);
    }
}


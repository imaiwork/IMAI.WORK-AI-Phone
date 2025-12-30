<?php

namespace app\api\validate\auto;

use app\common\validate\BaseValidate;

/**
 * 线索词自动任务校验
 * Class ClueValidate
 * @package app\api\validate\auto
 * @author Qasim
 */
class ClueValidate extends BaseValidate
{

    protected $rule = [
        'id' => 'require',
        'device_code' => 'require',
        'exec_type' => 'require|in:1,2,3',
        'clue_theme' => 'require|string|max:255',
        'keywords' => 'require|array',
    ];



    protected $message = [
        'id.require' => '请输入主键ID',
        'device_code.require' => '请输入设备编码',
        'exec_type.require' => '请选择执行月份',
        'exec_type.in' => '执行月份必须是1,2,3',
        'clue_theme.require' => '请输入线索主题',
        'clue_theme.max' => '线索主题最多255个字符',
        'keywords.require' => '请输入线索词',
        'keywords.array' => '线索词必须是数组',
    ];


    /**
     * @notes 添加
     * @return Validate
     */
    public function sceneAdd()
    {
        return $this->only([ 'device_code', 'exec_type', 'clue_theme', 'keywords']);
    }

    /**
     * @notes 更新
     * @return Validate
     */
    public function sceneUpdate()
    {
        return $this->only(['device_code', 'exec_type', 'clue_theme', 'keywords']);
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
        return $this->only(['device_code']);
    }
}


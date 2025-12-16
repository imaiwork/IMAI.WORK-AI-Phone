<?php

namespace app\api\validate\hd;

use app\common\validate\BaseValidate;

/**
 * 拼图设置校验
 */
class HdPuzzleSettingValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require',
        'user_id' => 'require',
        'name' => 'require|max:50',
        'task_id' => 'max:50',
        'status' => 'require|in:0,1,2,3,4,5',
        'result_count' => 'number|egt:0',
        'copywriting' => 'require',
        'material' => 'require',
    ];

    protected $message = [
        'id.require' => '请输入主键ID',
        'user_id.require' => '请输入用户ID',
        'name.require' => '请输入名称',
        'name.max' => '名称长度不能超过50个字符',
        'task_id.max' => '唯一任务ID长度不能超过50个字符',
        'status.require' => '请输入状态',
        'status.in' => '状态值不正确',
        'result_count.number' => '生成数量需为数字',
        'result_count.egt' => '生成数量不能为负数',
        'copywriting.require' => '请输入文案格式',
        'material.require' => '请输入素材格式',
    ];

    public function sceneAdd()
    {
        return $this->only(['name', 'copywriting', 'material', 'result_count']);
    }

    public function sceneUpdate()
    {
        return $this->only(['id', 'name', 'status', 'result_count']);
    }

    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    public function sceneDelete()
    {
        return $this->only(['id']);
    }
}


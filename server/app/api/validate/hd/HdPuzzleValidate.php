<?php

namespace app\api\validate\hd;

use app\common\validate\BaseValidate;

/**
 * 拼图任务校验
 */
class HdPuzzleValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require',
        'user_id' => 'require',
        'name' => 'require|max:200',
        'task_id' => 'max:50',
        'status' => 'require|in:0,1,2',
        'type' => 'require|in:1,2,3,4,5',
        'puzzle_setting_id' => 'require',
        'title' => 'json',
        'material' => 'json',
        'extra' => 'json',
        'remark' => 'max:255',
    ];

    protected $message = [
        'id.require' => '请输入主键ID',
        'user_id.require' => '请输入用户ID',
        'name.require' => '请输入名称',
        'name.max' => '名称长度不能超过200个字符',
        'task_id.max' => '唯一任务ID长度不能超过50个字符',
        'status.require' => '请输入状态',
        'status.in' => '状态值不正确',
        'type.require' => '请输入拼图类型',
        'type.in' => '拼图类型值不正确',
        'puzzle_setting_id.require' => '请输入拼图设置ID',
        'title.json' => '标题需为JSON格式',
        'material.json' => '素材需为JSON格式',
        'extra.json' => '附加字段需为JSON格式',
        'remark.max' => '失败原因长度不能超过255个字符',
    ];

    public function sceneAdd()
    {
        return $this->only(['name', 'type', 'status', 'puzzle_setting_id']);
    }

    public function sceneUpdate()
    {
        return $this->only(['id']);
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


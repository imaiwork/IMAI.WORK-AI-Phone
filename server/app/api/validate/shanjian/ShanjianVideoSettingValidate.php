<?php

namespace app\api\validate\shanjian;

use app\common\validate\BaseValidate;

/**
 * 闪剪视频设置校验
 * Class ShanjianVideoSettingValidate
 * @package app\api\validate\shanjian
 */
class ShanjianVideoSettingValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require',
        'user_id' => 'require',
        'name' => 'require|max:50',
        'pic' => 'max:255',
        'task_id' => 'max:50',
        'status' => 'in:0,1,2,3,4,5',
        'video_count' => 'number|between:1,100',
    ];

    protected $message = [
        'id.require' => '请输入主键ID',
        'user_id.require' => '请输入用户ID',
        'name.require' => '请输入名称',
        'name.max' => '名称长度不能超过50个字符',
        'pic.max' => '封面长度不能超过255个字符',
        'task_id.max' => '唯一任务ID长度不能超过50个字符',
        'status.in' => '状态值不正确',
        'video_count.number' => '视频数量必须是数字',
        'video_count.between' => '视频数量必须在1-100之间',
    ];

    // 添加场景
    public function sceneAdd()
    {
        return $this->only(['anchor', 'voice', 'title', 'character_design', 'material']);
    }

    // 更新场景
    public function sceneUpdate()
    {
        return $this->only(['id', 'name', 'status', 'video_count', 'anchor', 'voice', 'title', 'character_design', 'material', 'clip', 'music']);
    }

    // 更新名称场景
    public function sceneUpdateName()
    {
        return $this->only(['id', 'name']);
    }

    // 详情场景
    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    // 删除场景
    public function sceneDelete()
    {
        return $this->only(['id']);
    }
}

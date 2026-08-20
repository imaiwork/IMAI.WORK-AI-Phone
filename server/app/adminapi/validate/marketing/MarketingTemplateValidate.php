<?php

namespace app\adminapi\validate\marketing;

use app\common\validate\BaseValidate;

class MarketingTemplateValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require',
        'name' => 'require|max:100',
        'category_id' => 'require|number',
        'operation_preference' => 'require|number',
        'status' => 'require|number',
        //'description' => 'max:255',
        'detail_content' => 'max:500',
        // 'detail_task_types' => 'max:500',
        // 'detail_users' => 'max:500',
        // 'detail_images' => 'array',
        // 'detail_videos' => 'array',
        'schedule' => 'require|array',
    ];

    protected $message = [
        'id.require' => 'ID是必填项',
        'name.require' => '模板名称是必填项',
        'name.max' => '模板名称不能超过100个字符',
        'category_id.require' => '分类是必填项',
        'category_id.number' => '分类必须是数字',
        'operation_preference.require' => '操作偏好是必填项',
        'operation_preference.number' => '操作偏好必须是数字',
        'status.require' => '状态是必填项',
        'status.number' => '状态必须是数字',
        //'description.max' => '描述不能超过255个字符',
        'detail_content.max' => '详细内容不能超过500个字符',
        // 'detail_task_types.max' => '详细任务类型不能超过500个字符',
        // 'detail_users.max' => '详细用户不能超过500个字符',
        // 'detail_images.array' => '详细图片必须是数组',
        // 'detail_videos.array' => '详细视频必须是数组',
        // 'schedule.require' => '任务计划是必填项',
        // 'schedule.array' => '任务计划必须是数组',
    ];

    public function sceneAdd()
    {
        return $this->only(['name', 'category_id', 'operation_preference', 'status',  'schedule', 'detail_content']);
    }

    public function sceneEdit()
    {
        return $this->only(['id', 'name', 'category_id', 'operation_preference', 'status',  'schedule', 'detail_content']);
    }

    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    public function sceneDelete()
    {
        return $this->only(['id']);
    }
    public function sceneUpdateStatus()
    {
        return $this->only(['id', 'status']);
    }
}

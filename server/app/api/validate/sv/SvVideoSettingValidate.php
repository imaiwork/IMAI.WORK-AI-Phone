<?php

namespace app\api\validate\sv;

use app\common\validate\BaseValidate;

/**
 * 视频设置校验
 * Class SvVideoSettingValidate
 * @package app\api\validate\sv
 */
class SvVideoSettingValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require',
        'user_id' => 'require',
        'name' => 'require|max:50',
        'pic' => 'require|max:255',
        'task_id' => 'require|max:50',
        'status' => 'require|in:0,1,2,3,4,5',
        'setting_type' => 'require|in:1,2',
        'poi' => 'require|max:100',
        'type' => 'require|in:3',
        'speed' => 'require|in:0,1,2',
        'anchor' => 'json',
        'voice' => 'json',
        'title' => 'json',
        'subtitle' => 'json',
        'copywriting' => 'json', 
        'topic' => 'json',
        'extra' => 'json',
      
    ];

    protected $message = [
        'id.require' => '请输入主键ID',
        'user_id.require' => '请输入用户ID',
        'name.require' => '请输入名称',
        'name.max' => '名称长度不能超过50个字符',
        'pic.require' => '请输入封面',
        'pic.max' => '封面长度不能超过255个字符',
        'task_id.require' => '请输入唯一任务ID',
        'task_id.max' => '唯一任务ID长度不能超过50个字符',
        'status.require' => '请输入状态',
        'status.in' => '状态值不正确',
        'poi.require' => '请输入位置信息',
        'poi.max' => '位置信息长度不能超过100个字符',
        'type.require' => '请输入视频类型',
        'type.in' => '视频类型值不正确',
        'speed.require' => '请输入视频合成速度类型',
        'speed.in' => '视频合成速度类型值不正确',
        'extra.require' => '请输入附加字段内容',
    ];

    // 添加场景
    public function sceneAdd()
    {
        return $this->only([ 'name', 'status', 'type', 'speed']);
    }

    // 更新场景
    public function sceneUpdate()
    {
        return $this->only(['id','name', 'status', 'type', 'speed']);
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

    // 重试场景
    public function sceneRetry()
    {
        return $this->only(['id']);
    }
}
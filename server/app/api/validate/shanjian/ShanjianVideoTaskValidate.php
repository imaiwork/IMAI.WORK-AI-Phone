<?php

namespace app\api\validate\shanjian;

use app\common\validate\BaseValidate;

/**
 * 闪剪视频任务校验
 * Class ShanjianVideoTaskValidate
 * @package app\api\validate\shanjian
 */
class ShanjianVideoTaskValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require',
        'name' => 'require|max:200',
        'pic' => 'max:255',
        'task_id' => 'max:50',
        'status' => 'in:0,1,2,3',
        'audio_type' => 'in:1,2',
        'video_setting_id' => 'require',
        'anchor_id' => 'max:50',
        'voice_id' => 'max:50',
        'card_name' => 'max:255',
        'card_introduced' => 'max:5000',
        'title' => 'max:200',
        'msg' => 'max:2000',
        'material' => 'array',
        'music_url' => 'max:255',
        'video_token' => 'max:10',
        'extra' => 'array',
        'tries' => 'integer|between:0,10',
        'remark' => 'max:255',
        'clip_id' => 'max:40',
        'result_id' => 'max:255',
        'video_result_url' => 'max:1000',
    ];

    protected $message = [
        'id.require' => '请输入主键ID',
        'name.require' => '请输入名称',
        'name.max' => '名称长度不能超过200个字符',
        'pic.max' => '封面长度不能超过255个字符',
        'task_id.max' => '唯一任务ID长度不能超过50个字符',
        'status.in' => '状态值不正确',
        'audio_type.in' => '驱动类型值不正确',
        'video_setting_id.require' => '请输入视频设置ID',
        'anchor_id.max' => '形象ID长度不能超过50个字符',
        'voice_id.max' => '音色ID长度不能超过50个字符',
        'card_name.max' => '人设名字长度不能超过255个字符',
        'card_introduced.max' => '人设介绍长度不能超过5000个字符',
        'title.max' => '标题长度不能超过200个字符',
        'msg.max' => '文字长度不能超过2000个字符',
        'material.array' => '素材必须是数组格式',
        'music_url.max' => '音乐地址长度不能超过255个字符',
        'video_token.max' => '视频扣费长度不能超过10个字符',
        'extra.array' => '附加字段必须是数组格式',
        'tries.integer' => '尝试次数必须是整数',
        'tries.between' => '尝试次数必须在0-10之间',
        'remark.max' => '失败原因长度不能超过255个字符',
        'clip_id.max' => '剪辑风格长度不能超过40个字符',
        'result_id.max' => '生成的视频ID长度不能超过255个字符',
        'video_result_url.max' => '生成的视频地址长度不能超过1000个字符',
    ];

    // 新增场景
    public function sceneAdd()
    {
        return $this->only([
            'name', 
            'pic', 
            'audio_type', 
            'video_setting_id', 
            'anchor_id', 
            'voice_id', 
            'card_name', 
            'card_introduced', 
            'title', 
            'msg', 
            'material', 
            'music_url', 
            'clip_id', 
            'extra'
        ]);
    }

    // 更新场景
    public function sceneUpdate()
    {
        return $this->only([
            'id',
            'name', 
            'title', 
            'msg', 
            'card_name', 
            'card_introduced', 
            'material', 
            'music_url', 
            'clip_id', 
            'extra'
        ]);
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

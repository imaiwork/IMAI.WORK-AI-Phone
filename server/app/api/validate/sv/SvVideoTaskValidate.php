<?php

namespace app\api\validate\sv;

use app\common\validate\BaseValidate;

/**
 * 视频任务校验
 * Class SvVideoTaskValidate
 * @package app\api\validate\sv
 */
class SvVideoTaskValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require',
        'user_id' => 'require',
        'name' => 'require|max:200',
        'title' => 'require|max:200',
        'subtitle' => 'require|max:500',
        'pic' => 'require|max:255',
        'task_id' => 'require|max:50',
        'status' => 'require|in:0,1,2,3,4,5,6',
        'speed' => 'require|in:0,1,2',
        'type' => 'require|in:3',
        'gender' => 'require|in:male,female',
        'model_version' => 'require|in:1,2',
        'audio_type' => 'require|in:1,2',
        'video_setting_id' => 'require',
        'poi' => 'max:100',
        'topic' => 'max:200',
        'anchor_id' => 'max:50',
        'anchor_name' => 'max:200',
        'voice_id' => 'max:50',
        'voice_name' => 'max:200',
        'msg' => 'max:2000',
        'audio_url' => 'max:255',
        'audio_result_url' => 'max:255',
        'audio_id' => 'max:50',
        'upload_audio_url' => 'max:255',
        'result_id' => 'max:255',
        'video_result_url' => 'max:1000',
        'extra' => 'json',
        'remark' => 'max:255'
    ];

    protected $message = [
        'id.require' => '请输入主键ID',
        'user_id.require' => '请输入用户ID',
        'name.require' => '请输入名称',
        'name.max' => '名称长度不能超过200个字符',
        'title.require' => '请输入标题',
        'title.max' => '标题长度不能超过200个字符',
        'subtitle.require' => '请输入副标题',
        'subtitle.max' => '副标题长度不能超过500个字符',
        'pic.require' => '请输入封面',
        'pic.max' => '封面长度不能超过255个字符',
        'task_id.require' => '请输入唯一任务ID',
        'task_id.max' => '唯一任务ID长度不能超过50个字符',
        'status.require' => '请输入状态',
        'status.in' => '状态值不正确',
        'speed.require' => '请输入视频合成速度类型',
        'speed.in' => '视频合成速度类型值不正确',
        'type.require' => '请输入视频类型',
        'type.in' => '视频类型值不正确',
        'gender.require' => '请输入性别',
        'gender.in' => '性别值不正确',
        'model_version.require' => '请输入模型类型',
        'model_version.in' => '模型类型值不正确',
        'audio_type.require' => '请输入驱动类型',
        'audio_type.in' => '驱动类型值不正确',
        'video_setting_id.require' => '请输入视频设置ID',
        'poi.max' => '位置信息长度不能超过100个字符',
        'topic.max' => '话题长度不能超过200个字符',
        'anchor_id.max' => '形象ID长度不能超过50个字符',
        'anchor_name.max' => '形象名称长度不能超过200个字符',
        'voice_id.max' => '音色ID长度不能超过50个字符',
        'voice_name.max' => '音色名称长度不能超过200个字符',
        'msg.max' => '文字长度不能超过2000个字符',
        'audio_url.max' => '音频URL长度不能超过255个字符',
        'audio_result_url.max' => '音频生成URL长度不能超过255个字符',
        'audio_id.max' => '音频ID长度不能超过50个字符',
        'upload_audio_url.max' => '上传的语音链接长度不能超过255个字符',
        'result_id.max' => '生成的视频ID长度不能超过255个字符',
        'video_result_url.max' => '生成的视频地址长度不能超过1000个字符',
        'remark.max' => '失败原因长度不能超过255个字符'
    ];

    // 添加场景
    public function sceneAdd()
    {
        return $this->only(['name', 'title', 'subtitle', 'pic', 'speed', 'type', 'gender', 'model_version', 'audio_type', 'video_setting_id']);
    }

    // 更新场景
    public function sceneUpdate()
    {
        return $this->only(['id']);
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
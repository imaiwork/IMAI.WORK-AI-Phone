<?php

namespace app\api\validate\sv;

use app\common\validate\BaseValidate;

/**
 * 媒体手动任务校验
 * Class SvMediaManualTaskValidate
 * @package app\api\validate\sv
 */
class SvMediaManualTaskValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require',
        'user_id' => 'require',
        'manual_setting_id' => 'require',
        'name' => 'require|max:50',
        'media_type' => 'require|in:1,2',
        'media_url' => 'require',
        'title' => 'require',
        'subtitle' => '',
        'topic' => 'json',
        'status' => 'require|in:0,1,2,3',
        'poi' => '',
        'extra' => 'json',
    ];

    protected $message = [
        'id.require' => '请输入主键ID',
        'user_id.require' => '请输入用户ID',
        'manual_setting_id.require' => '请输入媒体手动设置ID',
        'name.require' => '请输入名称',
        'name.max' => '名称长度不能超过50个字符',
        'media_type.require' => '请输入媒体类型',
        'media_type.in' => '媒体类型值不正确（1视频2图片）',
        'media_url.require' => '请输入媒体URL',
        'title.require' => '请输入标题',
        'status.require' => '请输入状态',
        'status.in' => '状态值不正确（0未发布1已发布2发布失败3发布中）',
    ];

    // 添加场景
    public function sceneAdd()
    {
        return $this->only(['manual_setting_id', 'name', 'media_type', 'media_url', 'title', 'subtitle', 'topic', 'poi', 'extra']);
    }

    // 更新场景
    public function sceneUpdate()
    {
        return $this->only(['id', 'manual_setting_id', 'name', 'media_type', 'media_url', 'title', 'subtitle', 'topic', 'poi', 'extra', 'status']);
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

    // 根据设置ID查询场景
    public function sceneBySettingId()
    {
        return $this->only(['manual_setting_id']);
    }
}
<?php

namespace app\api\validate\auto;

use app\common\validate\BaseValidate;

/**
 * 自动设备设置校验
 * Class AutoDeviceSettingValidate
 * @package app\api\validate\auto
 */
class AutoDeviceSettingValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require',
        'user_id' => 'require',
        'type' => 'require|in:1,3,4,5',
        'device_config_id' => 'require',
        'device_code' => 'max:255',
        'video_theme' => 'max:1000',
        'text_theme' => 'max:1000',
        'status' => 'in:0,1,2,3',
        'execution_day' => 'date',
        'remark' => 'max:1000'
    ];

    protected $message = [
        'id.require' => '请输入主键ID',
        'user_id.require' => '请输入用户ID',
        'type.require' => '请输入平台类型',
        'type.in' => '平台类型值不正确',
        'device_config_id.require' => '请输入自动化配置ID',
        'device_code.max' => '设备号长度不能超过255个字符',
        'video_theme.max' => '视频营销主题长度不能超过1000个字符',
        'text_theme.max' => '图文营销主题长度不能超过1000个字符',
        'status.in' => '配置状态值不正确',
        'execution_day.date' => '执行日期必须为有效日期格式',
        'remark.max' => '失败原因长度不能超过1000个字符'
    ];

    // 添加场景
    public function sceneAdd()
    {
        return $this->only([ 'device_config_id', 'device_code', 'human_image', 'clip_material', 'image_material', 'video_theme', 'text_theme', 'execution_day']);
    }

    // 更新场景
    public function sceneUpdate()
    {
        return $this->only(['id', 'device_code', 'human_image', 'clip_material', 'image_material', 'video_theme', 'text_theme', 'status', 'execution_day', 'remark']);
    }

    // 详情场景
    public function sceneDetail()
    {
        return $this->only(['device_code']);
    }

    // 删除场景
    public function sceneDelete()
    {
        return $this->only(['id']);
    }
}
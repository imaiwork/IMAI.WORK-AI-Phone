<?php

namespace app\api\validate\auto;

use app\common\validate\BaseValidate;

/**
 * 微信朋友圈自动化配置校验
 * Class AutoDeviceWechatCircleConfigValidate
 * @package app\api\validate\auto
 */
class AutoDeviceWechatCircleConfigValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require',
        'user_id' => 'require',
        'device_config_id' => 'require',
        'device_code' => 'max:255',
        'video_material' => 'array',
        'image_material' => 'array',
        'industry_type' => 'max:1000',
        'is_ai' => 'in:0,1',
        'exec_time' => 'max:255',
        'status' => 'in:0,1,2,3',
        'remark' => 'max:1000',
        'exec_date' => 'date',
    ];

    protected $message = [
        'id.require' => '请输入主键ID',
        'user_id.require' => '请输入用户ID',
        'device_config_id.require' => '请输入自动化配置ID',
        'device_code.max' => '设备号长度不能超过255个字符',
        'video_material.array' => '视频素材格式不正确',
        'image_material.array' => '图片素材格式不正确',
        'industry_type.max' => '行业类型长度不能超过1000个字符',
        'is_ai.in' => 'Ai类型值不正确',
        'exec_time.max' => '执行时间长度不能超过255个字符',
        'status.in' => '配置状态值不正确',
        'remark.max' => '失败原因长度不能超过1000个字符',
        'exec_date.date' => '执行日期必须为有效日期格式',
    ];

    public function sceneAdd()
    {
        return $this->only(['device_config_id', 'device_code', 'video_material', 'image_material', 'industry_type', 'is_ai', 'exec_time', 'status', 'exec_date']);
    }

    public function sceneUpdate()
    {
        return $this->only(['id', 'device_config_id', 'device_code', 'video_material', 'image_material', 'industry_type', 'is_ai', 'exec_time', 'status', 'remark', 'exec_date']);
    }

    public function sceneDetail()
    {
        return $this->only(['device_code']);
    }

    public function sceneDelete()
    {
        return $this->only(['id']);
    }
}

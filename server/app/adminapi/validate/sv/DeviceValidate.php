<?php

namespace app\adminapi\validate\sv;

use app\common\validate\BaseValidate;

/**
 * 设备校验
 * Class DeviceValidate
 * @package app\adminapi\validate\sv
 */
class DeviceValidate extends BaseValidate
{


    protected $rule = [
        'id' => 'require',
        'device_id' => 'require|integer|gt:0',
        'to_user_id' => 'require|integer|gt:0',
        'cdk_id' => 'require|integer|gt:0',
        'device_code' => 'require',
        'status' => 'require|in:0,1',
        'device_model' => 'require',
        'sdk_version' => 'require',
    ];


    protected $message = [
        'id.require' => '请输入主键ID',
        'device_id.require' => '请选择设备',
        'device_id.integer' => '设备ID必须为整数',
        'device_id.gt' => '设备ID必须大于0',
        'to_user_id.require' => '请选择目标用户',
        'to_user_id.integer' => '目标用户ID必须为整数',
        'to_user_id.gt' => '目标用户ID必须大于0',
        'cdk_id.require' => '请选择兑换码',
        'cdk_id.integer' => '兑换码ID必须为整数',
        'cdk_id.gt' => '兑换码ID必须大于0',
        'device_code.require' => '请输入设备码',
        'status.require' => '请输入设备状态',
        "type.in" => '设备状态值只能是0,1',
        'device_model.require' => '请输入设备型号',
        'sdk_version.require' => '请输入SDK版本',
    ];
    /**
     * @notes 删除设备
     * @return DeviceValidate
     */
    public function sceneRemove()
    {
        return $this->only(['id', 'device_code']);
    }

    /**
     * @notes 可选兑换码列表
     */
    public function sceneAvailableCDKLists()
    {
        return $this->only(['device_id']);
    }

    /**
     * @notes 站长代兑换
     */
    public function sceneRedeem()
    {
        return $this->only(['device_id', 'cdk_id']);
    }

    /**
     * @notes 设备转移用户
     */
    public function sceneDeviceTransfer()
    {
        return $this->only(['device_id', 'to_user_id']);
    }
}

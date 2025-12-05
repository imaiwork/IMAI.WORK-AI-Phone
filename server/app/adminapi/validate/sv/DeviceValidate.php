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
        'device_code' => 'require',
        'status' => 'require|in:0,1',
        'device_model' => 'require',
        'sdk_version' => 'require',
    ];


    protected $message = [
        'id.require' => '请输入主键ID',
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
}

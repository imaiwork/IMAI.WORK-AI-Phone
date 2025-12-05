<?php

namespace app\api\validate\sv;

use app\common\validate\BaseValidate;

/**
 * 设备rpa配置校验
 * Class DeviceRpaValidate
 * @package app\api\validate\wechat
 * @author Qasim
 */
class DeviceRpaValidate extends BaseValidate
{

    protected $rule = [
        'device_code' => 'require',
    ];


    protected $message = [
        'device_code.require' => '设备码不能为空',
    ];


    /** 
     * @notes 更新设备rpa配置
     * @return DeviceRpaValidate
     */
    public function sceneUpdate()
    {
        return $this->remove('device_code', 'require');
    }

    
    /**
     * @notes 获取设备rpa配置
     * @return DeviceRpaValidate
     */
    public function sceneLists()
    {
        return $this->only(['device_code']);
    }
}

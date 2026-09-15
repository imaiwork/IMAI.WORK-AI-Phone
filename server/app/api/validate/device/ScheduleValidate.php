<?php

namespace app\api\validate\device;

use app\common\validate\BaseValidate;

/**
 * 设备计划任务校验
 * Class ScheduleValidate
 * @package app\api\validate\device
 * @author Qasim
 */
class ScheduleValidate extends BaseValidate
{

    protected $rule = [
        'id' => 'require',
        'device_code' => 'require',
        'persona_type' =>  'require|in:1,2,3',
        'status' => 'require|in:0,1'
    ];



    protected $message = [
        'id.require' => '任务计划id不能为空',
        'device_code.require' => '设备编码不能为空',
        'persona_type.require' => 'IP人设类型不能为空',
        'status.require' => '状态不能为空',
        'persona_type.in' => 'IP人设类型必须是1、2、3中的一个',
        'status.in' => '状态必须是0、1中的一个',
    ];

    /**
     * @notes 更新
     * @return Validate
     */
    public function sceneLists()
    {
        return $this->only(['persona_type', 'device_code']);
    }


    /**
     * @notes 更新
     * @return Validate
     */
    public function sceneUpdate()
    {
        return $this->only(['id', 'device_code', 'persona_type', 'status']);
    }

}


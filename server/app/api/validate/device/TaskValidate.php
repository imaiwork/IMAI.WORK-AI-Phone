<?php

namespace app\api\validate\device;

use app\common\validate\BaseValidate;

/**
 * 设备任务校验
 * Class TaskValidate
 * @package app\api\validate\device
 * @author Qasim
 */
class TaskValidate extends BaseValidate
{

    protected $rule = [
        'accounts' =>  'array',
        'device_codes' => 'array',
        'wechat_ids' => 'array',
        'minutes' => 'require|number'
    ];



    protected $message = [
        'accounts.array' => '账号必须是数组',
        'device_codes.array' => '设备码必须是数组',
        'wechat_ids.array' => '微信ID必须是数组',
        'minutes.require' => '请输入任务时长',
        'minutes.number' => '任务时长必须是数字',
    ];


    /**
     * @notes 校验
     * @return Validate
     */
    public function sceneCheck()
    {
        return $this->only(['accounts', 'device_codes', 'wechat_ids', 'minutes']);
    }
}


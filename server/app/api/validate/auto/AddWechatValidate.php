<?php

namespace app\api\validate\auto;

use app\common\validate\BaseValidate;

/**
 * 自动任务校验
 * Class ActiveValidate
 * @package app\api\validate\auto
 * @author Qasim
 */
class AddWechatValidate extends BaseValidate
{

    protected $rule = [
        'id' => 'require',
        'device_code' => 'require',
        'speech_type' => 'require|in:1,2,3',
        'remarks' => 'require|array',
    ];



    protected $message = [
        'id.require' => '请输入主键ID',
        'device_code.require' => '请输入设备编码',
        'speech_type.require' => '请选择话术类型',
        'speech_type.in' => '话术类型错误',
        'remarks.require' => '请输入备注',
        'remarks.array' => '备注格式错误',
    ];


    /**
     * @notes 添加
     * @return Validate
     */
    public function sceneAdd()
    {
        return $this->only([ 'device_code']);
    }

    /**
     * @notes 更新
     * @return Validate
     */
    public function sceneUpdate()
    {
        return $this->only(['device_code', 'remarks']);
    }
    /**
     * @notes 删除
     * @return Validate
     */
    public function sceneDelete()
    {
        return $this->only(['device_code']);
    }

    /**
     * @notes 详情
     * @return Validate
     */
    public function sceneDetail()
    {
        return $this->only(['device_code']);
    }
}


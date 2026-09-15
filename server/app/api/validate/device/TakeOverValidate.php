<?php

namespace app\api\validate\device;

use app\common\validate\BaseValidate;

/**
 * 设备接管任务校验
 * Class TakeOverValidate
 * @package app\api\validate\device
 * @author Qasim
 */
class TakeOverValidate extends BaseValidate
{

    protected $rule = [
        'id' => 'require',
        'task_name' => 'require',
        'accounts' =>  'require',
        'task_frep' => 'require|number',
        'time_config' => 'require|array'
    ];



    protected $message = [
        'id.require' => '请输入主键ID',
        'task_name.require' => '请输入任务名称',
        'accounts.require' => '请选择账号',
        'task_frep.require' => '请选择任务频率',
        'task_frep.number' => '任务频率必须是数字',
        'time_config.require' => '请输入时间配置',
        'time_config.array' => '时间配置必须是数组',
    ];


    /**
     * @notes 添加
     * @return Validate
     */
    public function sceneAdd()
    {
        return $this->only([ 'task_name', 'accounts', 'task_frep', 'time_config']);
    }

    /**
     * @notes 更新
     * @return Validate
     */
    public function sceneUpdate()
    {
        return $this->only(['id', 'task_name', 'accounts', 'task_frep', 'time_config']);
    }

    /**
     * @notes 状态修改
     * @return Validate
     */
    public function sceneChange()
    {
        return $this->only(['id', 'status']);
    }

    /**
     * @notes 删除
     * @return Validate
     */
    public function sceneDelete()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 详情
     * @return Validate
     */
    public function sceneDetail()
    {
        return $this->only(['id']);
    }
}


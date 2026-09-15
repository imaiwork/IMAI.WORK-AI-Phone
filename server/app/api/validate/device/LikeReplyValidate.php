<?php

namespace app\api\validate\device;

use app\common\validate\BaseValidate;

/**
 * 设备点赞回复任务校验
 * Class LikeReplyValidate
 * @package app\api\validate\device
 * @author Qasim
 */
class LikeReplyValidate extends BaseValidate
{

    protected $rule = [
        'id' => 'require',
        'task_name' => 'require',
        'accounts' =>  'require',
        'task_frep' => 'require|number',
        'time_config' => 'require|array',
        'action' => 'require|in:1,2,3',
        'number' => 'require|number',
        'interval' => 'require|number',
        'range' => 'require|in:1,2,3',
        'robot_id' => 'require|number',
        'comment_type' => 'require|in:0,1,2',
    ];



    protected $message = [
        'id.require' => '请输入主键ID',
        'task_name.require' => '请输入任务名称',
        'accounts.require' => '请选择账号',
        'task_frep.require' => '请选择任务频率',
        'task_frep.number' => '任务频率必须是数字',
        'time_config.require' => '请输入时间配置',
        'time_config.array' => '时间配置必须是数组',
        'action.require' => '请选择操作',
        'action.in' => '操作必须是1、2、3',
        'number.require' => '请输入操作次数',
        'number.number' => '操作次数必须是数字',
        'interval.require' => '请输入操作间隔',
        'interval.number' => '操作间隔必须是数字',
        'range.require' => '请选择操作范围',
        'range.in' => '操作范围必须是0、1、2',
        'robot_id.require' => '请选择机器人',
        'robot_id.number' => '机器人必须是数字',
        'comment_type.require' => '请选择评论类型',
        'comment_type.in' => '评论类型必须是0、1、2',
    ];


    /**
     * @notes 添加
     * @return Validate
     */
    public function sceneAdd()
    {
        return $this->only([ 'task_name', 'accounts', 'task_frep', 'time_config', 'action', 'number', 'interval', 'range', 'robot_id', 'comment_type', 'comment']);
    }

    /**
     * @notes 更新
     * @return Validate
     */
    public function sceneUpdate()
    {
        return $this->only(['id', 'task_name', 'accounts', 'task_frep', 'time_config', 'action', 'number', 'interval', 'range', 'robot_id', 'comment_type', 'comment']);
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


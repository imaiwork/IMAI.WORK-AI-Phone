<?php

namespace app\api\validate\device;

use app\common\validate\BaseValidate;

/**
 * 微信机器人校验
 * Class WechatValidate
 * @package app\api\validate\device
 * @author Qasim
 */
class WechatValidate extends BaseValidate
{

    protected $rule = [
        'id' => 'require',
        'task_name' => 'require',
        'accounts' =>  'require',
        'task_frep' => 'require|number',
        'time_config' => 'array',
        'is_free_time' => 'require|in:0,1',
        'custom_date' => 'array',
        'device_code' => 'require',
        'is_auto_group' => 'require|in:0,1',
        'sales_wechat' => 'require|array',
        'group_name_template' => 'require',
        'is_greeting' => 'require|in:0,1',
        'greeting_text' => 'require',
    ];



    protected $message = [
        'id.require' => '请输入主键ID',
        'task_name.require' => '请输入任务名称',
        'accounts.require' => '请选择账号',
        'task_frep.require' => '请选择任务频率',
        'task_frep.number' => '任务频率必须是数字',
        'time_config.array' => '时间配置必须是数组',
        'is_free_time.require' => '请选择是否空闲时间',
        'is_free_time.in' => '是否空闲时间必须是0或1',
        'custom_date.array' => '自定义时间必须是数组',
        'device_code.require' => '请输入设备编码',
        'is_auto_group.require' => '请选择是否自动加群',
        'is_auto_group.in' => '自动加群的有效值只能是0或1',
        'sales_wechat.require' => '请输入销售微信号',
        'sales_wechat.array' => '销售微信号必须是数组',
        'group_name_template.require' => '请输入群名称模板',
        'is_greeting.require' => '请选择是否自动发送欢迎语',
        'is_greeting.in' => '自动发送欢迎语的有效值只能是0或1',
        'greeting_text.require' => '请输入欢迎语',
    ];


    /**
     * @notes 添加
     * @return Validate
     */
    public function sceneAdd()
    {
        return $this->only([ 'task_name', 'accounts', 'task_frep', 'time_config', 'is_free_time']);
    }

    /**
     * @notes 更新
     * @return Validate
     */
    public function sceneUpdate()
    {
        return $this->only(['task_name', 'task_frep', 'time_config', 'is_free_time',  'device_code']);
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


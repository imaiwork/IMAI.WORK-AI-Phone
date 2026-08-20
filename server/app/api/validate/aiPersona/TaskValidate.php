<?php

namespace app\api\validate\aiPersona;

use app\common\validate\BaseValidate;

class TaskValidate extends BaseValidate
{
    protected $rule = [
        'persona_id' => 'require|number',
        'date' => 'dateFormat:Y-m-d',
        'device_code' => 'max:255',
        'status' => 'checkStatus',
        'account_type' => 'in:all,1,3,4,5',
        'platform_type' => 'in:all,1,3,4,5',
        'task_type' => 'in:1,2,3',
        'type' => 'in:1,2,3',
        'message_task_type' => 'in:1,2',
        'time_config' => 'max:50',
        'time_range' => 'max:50',
        'slot_key' => 'max:100',
        'time_start' => 'max:20',
        'time_end' => 'max:20',
        'keyword' => 'max:255',
        'exec_keyword' => 'max:255',
        'start_time' => 'date',
        'end_time' => 'date',
    ];

    protected $message = [
        'type.in' => '互动类型值不正确',
        'persona_id.require' => '人设ID是必填项',
        'persona_id.number' => '人设ID必须是数字',
        'date.dateFormat' => '日期格式必须是Y-m-d',
        'device_code.max' => '设备编号长度不能超过255个字符',
        'status.in' => '任务状态值不正确',
        'account_type.in' => '账号平台类型值不正确',
        'time_config.max' => '时间段长度不能超过50个字符',
        'time_range.max' => '时间段长度不能超过50个字符',
        'slot_key.max' => '时间段标识长度不能超过100个字符',
        'time_start.max' => '开始时间长度不能超过20个字符',
        'time_end.max' => '结束时间长度不能超过20个字符',
        'keyword.max' => '关键词长度不能超过255个字符',
    ];

    public function scenePublish()
    {
        return $this->only(['persona_id', 'date', 'device_code', 'status', 'account_type', 'time_config', 'time_range', 'slot_key', 'time_start', 'time_end', 'keyword']);
    }

    public function sceneMessage()
    {
        return $this->only(['persona_id', 'date', 'platform_type', 'account_type', 'message_task_type', 'keyword', 'start_time', 'end_time']);
    }

    public function sceneMessageStatistics()
    {
        return $this->only(['persona_id', 'date', 'platform_type', 'account_type', 'message_task_type', 'keyword', 'start_time', 'end_time']);
    }

    public function sceneLeadScrapingReport()
    {
        return $this->only(['persona_id', 'date', 'platform_type', 'account_type', 'task_type', 'status', 'keyword', 'start_time', 'end_time']);
    }

    public function sceneSameCityTouch()
    {
        return $this->only(['persona_id', 'date', 'device_code', 'status', 'platform_type', 'account_type', 'time_config', 'time_range', 'slot_key', 'time_start', 'time_end', 'keyword']);
    }

    public function sceneGroupBuyReport()
    {
        return $this->only(['persona_id', 'date', 'platform_type', 'account_type', 'task_type', 'status', 'keyword', 'start_time', 'end_time']);
    }

    public function sceneClueCustomer()
    {
        return $this->only(['persona_id', 'date', 'device_code', 'status', 'exec_keyword', 'keyword']);
    }

    public function sceneWechatCustomer()
    {
        return $this->only(['persona_id', 'date', 'device_code', 'status', 'keyword']);
    }

    public function sceneWechatCreateGroup()
    {
        return $this->only(['persona_id', 'date', 'device_code', 'status', 'keyword', 'start_time', 'end_time']);
    }

    public function sceneWechatCircleInteraction()
    {
        return $this->only(['persona_id', 'device_code', 'type', 'keyword', 'date', 'start_time', 'end_time']);
    }

    public function sceneWechatStatistics()
    {
        return $this->only(['persona_id', 'date', 'device_code', 'keyword', 'start_time', 'end_time']);
    }

    public function sceneWechatMessageReply()
    {
        return $this->only(['persona_id', 'date', 'keyword', 'start_time', 'end_time']);
    }

    protected function checkStatus($value): bool|string
    {
        $allowed = $this->currentScene === 'wechatCreateGroup'
            ? ['-1', '0', '1']
            : ['0', '1', '2', '3', '4'];

        return in_array((string)$value, $allowed, true) ? true : ($this->message['status.in'] ?? 'status invalid');
    }
}

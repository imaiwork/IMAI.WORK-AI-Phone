<?php

namespace app\api\validate\phoneAgent;

use app\common\validate\BaseValidate;

class PhoneAgentValidate extends BaseValidate
{
    protected $rule = [
        'task_id' => 'require',
        'conversation_id' => 'max:64',
        'last_id' => 'number',
        'limit' => 'number',
        'message' => 'require|max:2000',
        'device_code' => 'require',
        'model' => 'max:100',
        'analyze_model' => 'max:100',
        'execution_message' => 'max:10000',
        'skip_analyze' => 'in:0,1',
        'lang' => 'in:cn,en',
    ];

    protected $message = [
        'task_id.require' => '任务ID不能为空',
        'conversation_id.require' => '会话ID不能为空',
        'conversation_id.max' => '会话ID格式错误',
        'last_id.number' => 'last_id格式错误',
        'limit.number' => 'limit格式错误',
        'message.require' => '请输入任务内容',
        'message.max' => '任务内容最多2000字符',
        'device_code.require' => '请选择设备',
        'model.max' => '模型名称最多100字符',
        'analyze_model.max' => '规划模型名称最多100字符',
        'execution_message.max' => '执行文案最多10000字符',
        'skip_analyze.in' => 'skip_analyze参数错误',
        'lang.in' => 'lang参数错误',
    ];

    public function sceneAnalyze(): self
    {
        return $this->only(['message', 'analyze_model', 'lang'])
            ->remove('analyze_model', 'require')
            ->remove('lang', 'require');
    }

    public function sceneDispatch(): self
    {
        return $this->only(['message', 'device_code', 'model', 'conversation_id', 'execution_message', 'skip_analyze', 'analyze_model', 'lang'])
            ->remove('model', 'require')
            ->remove('execution_message', 'require')
            ->remove('skip_analyze', 'require')
            ->remove('analyze_model', 'require')
            ->remove('lang', 'require');
    }

    public function sceneDetail(): self
    {
        return $this->only(['task_id']);
    }

    public function sceneEvents(): self
    {
        return $this->only(['task_id', 'last_id', 'limit'])
            ->remove('last_id', 'require')
            ->remove('limit', 'require');
    }

    public function sceneConversationDetail(): self
    {
        return $this->only(['conversation_id'])
            ->append('conversation_id', 'require');
    }

    public function sceneCancel(): self
    {
        return $this->only(['task_id']);
    }

    public function sceneDeleteConversation(): self
    {
        return $this->only(['conversation_id'])
            ->append('conversation_id', 'require');
    }
}

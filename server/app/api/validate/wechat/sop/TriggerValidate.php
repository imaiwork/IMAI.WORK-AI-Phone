<?php
declare (strict_types = 1);

namespace app\api\validate\wechat\sop;

use app\common\model\wechat\sop\AiWechatSopStageTrigger;
use app\common\model\wechat\sop\AiWechatSopSubStage;
use app\common\validate\BaseValidate;
use think\facade\Db;

class TriggerValidate extends BaseValidate
{
    protected $rule = [
        'trigger_id' => 'require|number',
        'stage_id' => 'require|number|checkStage',
        'match_type' => 'require|in:0,1,2|checkDuplicate',
        'action_type' => 'requireIf:match_type,1|checkActionType',
        'chat_match_mode' => 'requireIf:match_type,2|in:1,2',
        'chat_match_object' => 'requireIf:match_type,2|in:1,2',
        'chat_keywords' => 'requireIf:match_type,2|max:255|checkKeywords',
        'status' => 'in:0,1'
    ];

    protected $message = [
        'id.require' => '请输入触发条件ID',
        'trigger_id.require' => '请输入触发条件ID',
        'stage_id.require' => '请输入阶段ID',
        'stage_id.number' => '阶段ID必须为数字',
        'match_type.require' => '请选择匹配类型',
        'match_type.in' => '匹配类型错误',
        'action_type.requireIf' => '请选择动作类型',
        'chat_match_mode.requireIf' => '请选择聊天匹配模式',
        'chat_match_mode.in' => '聊天匹配模式错误',
        'chat_match_object.requireIf' => '请选择聊天匹配对象',
        'chat_match_object.in' => '聊天匹配对象错误',
        'chat_keywords.requireIf' => '请输入聊天关键词',
        'chat_keywords.max' => '聊天关键词最大长度为255个字符',
        'status.in' => '状态值错误'
    ];

    /**
     * @notes 创建推送
     */
    public function sceneUpdateTrigger()
    {
        return $this->only(['trigger_id', 'match_type', 'action_type','chat_match_mode', 'chat_match_object', 'chat_keywords', 'status']);
    }

    /**
     * 验证阶段是否存在且未删除
     */
    protected function checkStage($value, $rule, array $data): bool|string
    {
        $stage = AiWechatSopSubStage::where([
            ['id', '=', $value],
            ['delete_time', 'null', null]
        ])->find();

        return $stage ? true : '阶段不存在或已删除';
    }

    /**
     * 验证动作类型是否合法
     */
    protected function checkActionType($value, $rule, array $data): bool|string
    {
        // 如果是动作匹配类型
        if ($data['match_type'] == AiWechatSopStageTrigger::MATCH_TYPE_ACTION) {
            // 检查动作类型是否在预定义范围内
            if (!in_array($value, [AiWechatSopStageTrigger::ACTION_TYPE_NEW_FRIEND])) {
                return '不支持的动作类型';
            }
        }
        return true;
    }

    /**
     * 验证聊天关键词
     */
    protected function checkKeywords($value, $rule, array $data): bool|string
    {
        if ($data['match_type'] == AiWechatSopStageTrigger::MATCH_TYPE_CHAT) {
            if (empty(trim($value))) {
                return '聊天关键词不能为空';
            }
        }
        return true;
    }

    /**
     * 检查同一阶段下是否存在相同的触发条件
     */
    protected function checkDuplicate($value, $rule, array $data): bool|string
    {

        $where = [
            ['match_type', '=', $value],
            ['delete_time', 'null', null]
        ];

        // 如果是更新操作，排除自身
        if (!empty($data['trigger_id'])) {
            $where[] = ['id', '<>', $data['trigger_id']];
        }

        if ($value == AiWechatSopStageTrigger::MATCH_TYPE_CHAT) {
            // 聊天匹配：检查相同的匹配模式、对象和关键词
            $where[] = ['chat_match_mode', '=', $data['chat_match_mode']];
            $where[] = ['chat_match_object', '=', $data['chat_match_object']];
            $where[] = ['chat_keywords', '=', trim($data['chat_keywords'])];
            $where[] = ['user_id', '=', $this->request->userId];
            
            $exists = Db::name('ai_wechat_sop_stage_trigger')->where($where)->find();
            if ($exists) {
                return '该触发条件已存在于其它阶段！';
            }
        }

        return true;
    }

    /**
     * 验证场景参数
     */
    protected function checkSceneParams($value, $rule, array $data): bool|string
    {
        if ($data['match_type'] == AiWechatSopStageTrigger::MATCH_TYPE_ACTION) {
            // 动作匹配时，不允许出现聊天相关参数
            $chatParams = ['chat_match_mode', 'chat_match_object', 'chat_keywords'];
            foreach ($chatParams as $param) {
                if (isset($data[$param]) && !empty($data[$param])) {
                    return '动作匹配时不需要设置聊天相关参数';
                }
            }
        } else if ($data['match_type'] == AiWechatSopStageTrigger::MATCH_TYPE_CHAT) {
            // 聊天匹配时，不允许出现动作相关参数
            if (isset($data['action_type']) && !empty($data['action_type'])) {
                return '聊天匹配时不需要设置动作类型';
            }
        }
        return true;
    }

    /**
     * @notes 创建触发条件
     */
    public function sceneCreate()
    {
        return $this->only(['stage_id', 'match_type', 'action_type', 
            'chat_match_mode', 'chat_match_object', 'chat_keywords'])
            ->append('match_type', 'checkSceneParams');
    }

    /**
     * @notes 更新触发条件
     */
    public function sceneUpdate()
    {
        return $this->only(['trigger_id', 'match_type', 'action_type', 
            'chat_match_mode', 'chat_match_object', 'chat_keywords', 'status'])
            ->remove('match_type', 'require')
            ->append('match_type', 'checkSceneParams');
    }

    /**
     * @notes 删除触发条件
     */
    public function sceneDelete()
    {
        return $this->only(['id']);
    }
} 
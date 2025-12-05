<?php

namespace app\api\validate\sv;

use app\common\validate\BaseValidate;

/**
 * 微信回复策略校验
 * Class StrategyValidate
 * @package app\api\validate\wechat
 * @author Qasim
 */
class StrategyValidate extends BaseValidate
{

    protected $rule = [
        'multiple_type' => 'in:0,1,2',
        'voice_enable' => 'in:0,1',
        'image_enable' => 'in:0,1',
        'stop_enable' => 'in:0,1',
        'is_enable' => 'in:0,1',
        'friend_greet_is_reply' => 'in:0,1',
        'greet_after_ai_enable' => 'in:0,1',
        'id' => 'require',
        'robot_id' => 'require',
    ];



    protected $message = [
        'multiple_type.require' => '请输入多条消息回复类型',
        'id.require' => '请输入id',
        'robot_id.require' => '请输入机器人',
        'number_chat_rounds.require' => '请输入聊天轮数',
        'voice_enable.require' => '请输入是否开启语音消息回复',
        'image_enable.require' => '请输入是否开启图片消息回复',
        'image_reply.require' => '请输入图片消息回复的内容',
        'stop_enable.require' => '请输入是否开启停止回复',
        'stop_keywords.require' => '请输入触发停止回复的关键词',
        'is_enable.require' => '请输入是否开启自动打招呼策略',
        'interval_time.require' => '请输入打招呼间隔时间',
        'friend_greet_is_reply.require' => '请输入好友打招呼回复类型',
        'greet_after_ai_enable.require' => '请输入打招呼后，是否开启AI接管',
        'greet_content.require' => '请输入打招呼内容',
        'multiple_type.in' => '多条消息回复类型值只能是0,1,2',
        'voice_enable.in' => '是否开启语音消息回复值只能是0,1',
        'image_enable.in' => '是否开启图片消息回复值只能是0,1',
        'stop_enable.in' => '是否开启停止回复值只能是0,1',
        'is_enable.in' => '是否开启自动打招呼策略值只能是0,1',
        'friend_greet_is_reply.in' => '好友打招呼回复类型值只能是0,1',
        'greet_after_ai_enable.in' => '打招呼后，是否开启AI接管值只能是0,1',
    ];


    /**
     * @notes 回复策略
     * @return Validate
     */
    public function sceneReply()
    {
        return $this->only(['robot_id','multiple_type', 'number_chat_rounds', 'voice_enable', 'image_enable', 'image_reply', 'stop_enable', 'stop_keywords']);
    }

    /**
     * @notes 回复策略
     * @return Validate
     */
    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 打招呼策略
     * @return Validate
     */
    public function sceneGreet()
    {
        return $this->only(['is_enable', 'interval_time', 'firend_greet_is_reply', 'greet_after_ai_enable', 'greet_content']);
    }
}

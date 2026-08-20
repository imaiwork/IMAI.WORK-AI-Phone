<?php

namespace app\api\validate\aiPersona;

use app\common\validate\BaseValidate;

/**
 * 自动互动管家任务校验
 * Class InteractiveValidate
 * @package app\api\validate\aiPersona
 * @author Qasim
 */
class InteractiveValidate extends BaseValidate
{

    protected $rule = [
        'id' => 'require',
        'persona_id' => 'require',
        'add_friend_script' => 'require',
        'is_like' => 'require|in:0,1',
        'is_comment' => 'require|in:0,1',
        'sales_wechat' => 'array',
        'is_greeting' => 'require|in:0,1',
        'is_share_chats' => 'in:0,1',
        'group_trigger_mode' => 'in:1,2',
        'group_trigger_keywords' => 'array',
    ];



    protected $message = [
        'id.require' => '请输入主键ID',
        'persona_id.require' => '请输入人设ID',
        'add_friend_script.require' => '请输入添加好友话术',
        'is_like.require' => '请输入是否点赞',
        'is_comment.require' => '请输入是否评论',
        'sales_wechat.array' => '销售微信号必须是数组',
        'is_greeting.require' => '请选择是否自动发送欢迎语',
        'is_greeting.in' => '是否自动发送欢迎语的有效值只能是0或1',
        'is_share_chats.in' => '是否发送聊天消息的有效值只能是0或1',
        'group_trigger_mode.in' => '加群触发模式的有效值只能是1或2',
        'group_trigger_keywords.array' => '自定义触发关键词必须是数组',
    ];


    /**
     * @notes 更新
     * @return Validate
     */
    public function sceneUpdate()
    {
        return $this->only(['persona_id', 'add_friend_script', 'is_like', 'is_comment', 'sales_wechat', 'group_name_template', 'is_greeting', 'greeting_text', 'is_share_chats', 'group_trigger_mode', 'group_trigger_keywords']);
    }

    /**
     * @notes 详情
     * @return Validate
     */
    public function sceneDetail()
    {
        return $this->only(['persona_id']);
    }
}

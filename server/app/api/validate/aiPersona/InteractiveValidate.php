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
        'comment_robot_prompt' => 'require',
    ];



    protected $message = [
        'id.require' => '请输入主键ID',
        'persona_id.require' => '请输入人设ID',
        'add_friend_script.require' => '请输入添加好友话术',
        'is_like.require' => '请输入是否点赞',
        'is_comment.require' => '请输入是否评论',
        'comment_robot_prompt.require' => '请输入评论机器人提示',
    ];


    /**
     * @notes 更新
     * @return Validate
     */
    public function sceneUpdate()
    {
        return $this->only(['persona_id', 'add_friend_script', 'is_like', 'is_comment', 'comment_robot_prompt']);
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


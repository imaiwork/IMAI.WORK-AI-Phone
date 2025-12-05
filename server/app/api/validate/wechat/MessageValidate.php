<?php

namespace app\api\validate\wechat;

use app\common\validate\BaseValidate;

/**
 * 微信消息校验
 * Class MessageValidate
 * @package app\api\validate\wechat
 * @author Qasim
 */
class MessageValidate extends BaseValidate
{

    protected $rule = [
        'wechat_id' => 'require',
        'friend_id' => 'require',
        'message' => 'require',
        'message_type' => 'require|in:0,1',
    ];



    protected $message = [
        'wechat_id.require' => '请输入微信ID',
        'friend_id.require' => '请输入好友ID',
        'message.require' => '请输入消息内容',
        'message_type.require' => '请输入消息类型',
        'message_type.in' => '消息类型格式错误',
    ];


    /**
     * @notes 打招呼
     * @return WechatValidate
     */
    public function sceneGreet()
    {
        return $this->only(['wechat_id', 'friend_id']);
    }

    /**
     * @notes 发送消息
     * @return WechatValidate
     */
    public function sceneSend()
    {
        return $this->only(['wechat_id', 'friend_id', 'message', 'message_type']);
    }
}

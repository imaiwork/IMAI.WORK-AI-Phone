<?php

namespace app\api\validate\sv;

use app\common\validate\BaseValidate;

/**
 * 消息校验
 * Class MessageValidate
 * @package app\api\validate\wechat
 * @author Qasim
 */
class MessageValidate extends BaseValidate
{

    protected $rule = [
        'account' => 'require',
        'friend_id' => 'require',
        'message' => 'require',
        'message_type' => 'require|in:0,1',
    ];



    protected $message = [
        'account.require' => '请输入ID',
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
        return $this->only(['account', 'friend_id']);
    }

    /**
     * @notes 发送消息
     * @return WechatValidate
     */
    public function sceneSend()
    {
        return $this->only(['account', 'friend_id', 'message', 'message_type']);
    }
}

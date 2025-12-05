<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use Jubo\JuLiao\IM\Wx\Proto\SendFriendVerifyTaskMessage;
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;

/**
 * 给好友发送消息任务处理器
 * 
 * @method static array handle(array $data) 业务处理
 * @author Qasim
 * @package app\handlers\client
 */
class SendFriendVerifyTaskHandler extends BaseHandler
{

    /**
     * 处理给好友发送消息任务
     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {
        // 构造推送任务请求
        $content = $this->buildRequestContent($data);

        return $this->buildProtobufResponse(EnumMsgType::SendFriendVerifyTask, $content);
    }

    /**
     * 构建推送任务请求内容
     *
     * @param array $data 请求数据
     * @return SendFriendVerifyTaskMessage
     */
    protected function buildRequestContent(array $data): SendFriendVerifyTaskMessage
    {

        $request = new SendFriendVerifyTaskMessage();

        $request->setWeChatId($data['WeChatId']);

        if (isset($data['FriendId'])) {
            $request->setFriendId($data['FriendId']);
        }

        if (isset($data['Message'])) {
            $request->setMessage($data['Message']);
        }

        if (isset($data['TaskId'])) {
            $request->setTaskId($data['TaskId']);
        }
        
        return $request;
    }
}

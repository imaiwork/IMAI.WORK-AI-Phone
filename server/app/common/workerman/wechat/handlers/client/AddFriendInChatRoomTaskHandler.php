<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use Jubo\JuLiao\IM\Wx\Proto\AddFriendInChatRoomTaskMessage;
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;

/**
 * 群内加好友任务
 * 
 * @method static array handle(array $data) 业务处理
 * @author Qasim
 * @package app\handlers\client
 */
class AddFriendInChatRoomTaskHandler extends BaseHandler
{

    /**
     * 群内加好友任务
     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {
        // 构造推送任务请求
        $content = $this->buildRequestContent($data);

        return $this->buildProtobufResponse(EnumMsgType::AddFriendInChatRoomTask, $content);
    }

    /**
     * 构建推送任务请求内容
     *
     * @param array $data 请求数据
     * @return AddFriendInChatRoomTaskMessage
     */
    protected function buildRequestContent(array $data): AddFriendInChatRoomTaskMessage
    {
        
        
        $request = new AddFriendInChatRoomTaskMessage();

        $request->setWeChatId($data['WeChatId']);
        
        if (isset($data['ChatroomId'])) {
            $request->setChatroomId($data['ChatroomId']);
        }

        if (isset($data['Message'])) {
            $request->setMessage($data['Message']);
        }

        if (isset($data['FriendId'])) {
            $request->setFriendId($data['FriendId']);
        }

        if (isset($data['Remark'])) {
            $request->setRemark($data['Remark']);
        }
        
        if (isset($data['TaskId'])) {
            $request->setTaskId($data['TaskId']);
        }

        return $request;
    }
}

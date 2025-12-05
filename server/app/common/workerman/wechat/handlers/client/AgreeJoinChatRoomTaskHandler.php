<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use Jubo\JuLiao\IM\Wx\Proto\{
    AgreeJoinChatRoomTaskMessage

};
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;

/**
 * 同意加入群聊命令
 * 
 * @method static array handle(array $data) 业务处理
 * @author Qasim
 * @package app\handlers\client
 */
class AgreeJoinChatRoomTaskHandler extends BaseHandler
{

    /**
     * 同意加入群聊命令
     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {
        // 构造推送任务请求
        $content = $this->buildRequestContent($data);

        return $this->buildProtobufResponse(EnumMsgType::AgreeJoinChatRoomTask, $content);
    }

    /**
     * 构建推送任务请求内容
     *
     * @param array $data 请求数据
     * @return AgreeJoinChatRoomTaskMessage
     */
    protected function buildRequestContent(array $data): AgreeJoinChatRoomTaskMessage
    {
        
        
        $request = new AgreeJoinChatRoomTaskMessage();

        $request->setWeChatId($data['WeChatId']);
        
        if (isset($data['Talker'])) {
            $request->setTalker($data['Talker']);
        }

        if (isset($data['MsgSvrId'])) {
            $request->setMsgSvrId($data['MsgSvrId']);
        }

        if (isset($data['MsgContent'])) {
            $request->setMsgContent($data['MsgContent']);
        }
        
        if (isset($data['TaskId'])) {
            $request->setTaskId($data['TaskId']);
        }

        return $request;
    }
}

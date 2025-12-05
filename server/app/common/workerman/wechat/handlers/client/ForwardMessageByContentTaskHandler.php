<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use Jubo\JuLiao\IM\Wx\Proto\ForwardMessageByContentTaskMessage;
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;

/**
 * 转发消息内容任务
 * 
 * @method static array handle(array $data) 业务处理
 * @author Qasim
 * @package app\handlers\client
 */
class ForwardMessageByContentTaskHandler extends BaseHandler
{

    /**
     * 转发消息内容任务
     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {
        // 构造推送任务请求
        $content = $this->buildRequestContent($data);

        return $this->buildProtobufResponse(EnumMsgType::ForwardMessageByContentTask, $content);
    }

    /**
     * 构建推送任务请求内容
     *
     * @param array $data 请求数据
     * @return ForwardMessageByContentTaskMessage
     */
    protected function buildRequestContent(array $data): ForwardMessageByContentTaskMessage
    {
        

        $request = new ForwardMessageByContentTaskMessage();

        $request->setWeChatId($data['WeChatId']);
        
        if (isset($data['MsgSvrId'])) {
            $request->setMsgSvrId($data['MsgSvrId']);
        }
        
        if (isset($data['FriendIds'])) {
            $request->setFriendIds($data['FriendIds']);
        }

        if (isset($data['MsgType'])) {
            $request->setMsgType($data['MsgType']);
        }

        if (isset($data['Content'])) {
            $request->setContent($data['Content']);
        }

        if (isset($data['Thumb'])) {
            $request->setThumb($data['Thumb']);
        }
        
        if (isset($data['TaskId'])) {
            $request->setTaskId($data['TaskId']);
        }
        

        return $request;
    }
}

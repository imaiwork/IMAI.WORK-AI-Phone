<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use Jubo\JuLiao\IM\Wx\Proto\ForwardMessageTaskMessage;
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;

/**
 * 转发消息任务
 * 
 * @method static array handle(array $data) 业务处理
 * @author Qasim
 * @package app\handlers\client
 */
class ForwardMessageTaskHandler extends BaseHandler
{

    /**
     * 消息撤回任务
     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {
        // 构造推送任务请求
        $content = $this->buildRequestContent($data);

        return $this->buildProtobufResponse(EnumMsgType::ForwardMessageTask, $content);
    }

    /**
     * 构建推送任务请求内容
     *
     * @param array $data 请求数据
     * @return ForwardMessageTaskMessage
     */
    protected function buildRequestContent(array $data): ForwardMessageTaskMessage
    {
        

        $request = new ForwardMessageTaskMessage();

        $request->setWeChatId($data['WeChatId']);
        
        if (isset($data['MsgSrvId'])) {
            $request->setMsgSrvId($data['MsgSrvId']);
        }
        
        if (isset($data['FriendIds'])) {
            $request->setFriendIds($data['FriendIds']);
        }

        if (isset($data['ExtMsg'])) {
            $request->setExtMsg($data['ExtMsg']);
        }

        if (isset($data['Talker'])) {
            $request->setTalker($data['Talker']);
        }
        
        if (isset($data['TaskId'])) {
            $request->setTaskId($data['TaskId']);
        }
        

        return $request;
    }
}

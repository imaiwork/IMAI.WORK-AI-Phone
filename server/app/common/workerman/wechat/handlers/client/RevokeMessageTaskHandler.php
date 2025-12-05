<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use Jubo\JuLiao\IM\Wx\Proto\RevokeMessageTaskMessage;
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;

/**
 * 消息撤回任务
 * 
 * @method static array handle(array $data) 业务处理
 * @author Qasim
 * @package app\handlers\client
 */
class RevokeMessageTaskHandler extends BaseHandler
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

        return $this->buildProtobufResponse(EnumMsgType::RevokeMessageTask, $content);
    }

    /**
     * 构建推送任务请求内容
     *
     * @param array $data 请求数据
     * @return RevokeMessageTaskMessage
     */
    protected function buildRequestContent(array $data): RevokeMessageTaskMessage
    {
        

        $request = new RevokeMessageTaskMessage();

        $request->setWeChatId($data['WeChatId']);
        
        if (isset($data['MsgId'])) {
            $request->setMsgId($data['MsgId']);
        }
        
        if (isset($data['FriendId'])) {
            $request->setFriendId($data['FriendId']);
        }
        
        if (isset($data['TaskId'])) {
            $request->setTaskId($data['TaskId']);
        }
        

        return $request;
    }
}

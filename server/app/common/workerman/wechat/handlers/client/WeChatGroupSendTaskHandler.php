<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use Jubo\JuLiao\IM\Wx\Proto\{
    WeChatGroupSendTaskMessage

};
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;

/**
 * 6、群发消息任务
 * 
 * @method static array handle(array $data) 业务处理
 * @author Qasim
 * @package app\handlers\client
 */
class WeChatGroupSendTaskHandler extends BaseHandler
{

    /**
     * 6、群发消息任务
     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {
        // 构造推送任务请求
        $content = $this->buildRequestContent($data);

        return $this->buildProtobufResponse(EnumMsgType::WeChatGroupSendTask, $content);
    }

    /**
     * 构建推送任务请求内容
     *
     * @param array $data 请求数据
     * @return WeChatGroupSendTaskMessage
     */
    protected function buildRequestContent(array $data): WeChatGroupSendTaskMessage
    {
        
        
        $request = new WeChatGroupSendTaskMessage();

        $request->setWeChatId($data['WeChatId']);
        
        if (isset($data['FriendIds'])) {
            $request->setFriendIds($data['FriendIds']);
        }

        if (isset($data['ContentType'])) {
            $request->setContentType($data['ContentType']);
        }

        if (isset($data['Content'])) {
            $request->setContent($data['Content']);
        }

        if (isset($data['Duration'])) {
            $request->setDuration($data['Duration']);
        }

        if (isset($data['Original'])) {
            $request->setOriginal($data['Original']);
        }
        
        
        if (isset($data['TaskId'])) {
            $request->setTaskId($data['TaskId']);
        }

        return $request;
    }
}

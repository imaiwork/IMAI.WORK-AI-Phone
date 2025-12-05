<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use Jubo\JuLiao\IM\Wx\Proto\RequestTalkContentTaskMessage;
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;

/**
 * 获取聊天消息的原始内容（主要是xml内容）任务及返回
 * 
 * @method static array handle(array $data) 业务处理
 * @author Qasim
 * @package app\handlers\client
 */
class RequestTalkContentTaskHandler extends BaseHandler
{

    /**
     * 获取聊天消息的原始内容（主要是xml内容）任务及返回
     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {
        // 构造推送任务请求
        $content = $this->buildRequestContent($data);

        return $this->buildProtobufResponse(EnumMsgType::RequestTalkContentTask, $content);
    }

    /**
     * 构建推送任务请求内容
     *
     * @param array $data 请求数据
     * @return RequestTalkContentTaskMessage
     */
    protected function buildRequestContent(array $data): RequestTalkContentTaskMessage
    {
        

        $request = new RequestTalkContentTaskMessage();

        $request->setWeChatId($data['WeChatId']);
        if (isset($data['MsgSvrId'])) {
            $request->setMsgSvrId($data['MsgSvrId']);
        }

        return $request;
    }
}

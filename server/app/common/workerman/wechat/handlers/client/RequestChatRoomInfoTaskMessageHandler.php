<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use Jubo\JuLiao\IM\Wx\Proto\RequestChatRoomInfoTaskMessage;
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;

/**
 * 请求群聊信息任务处理器
 * 
 * @method static array handle(array $data) 业务处理
 * @author Qasim
 * @package app\handlers\client
 */
class RequestChatRoomInfoTaskMessageHandler extends BaseHandler
{

    /**
     * 处理请求群聊信息任务
     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {
        // 构造推送任务请求
        $content = $this->buildRequestContent($data);

        return $this->buildProtobufResponse(EnumMsgType::RequestChatRoomInfoTask, $content);
    }

    /**
     * 构建推送任务请求内容
     *
     * @param array $data 请求数据
     * @return RequestChatRoomInfoTaskMessage
     */
    protected function buildRequestContent(array $data): RequestChatRoomInfoTaskMessage
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *           商家所属微信号
         *     @type string $ChatRoomId
         *     @type int $Flag
         *           Flag=1:推送成员信息（ChatRoomMembersNotice)
         */

        $request = new RequestChatRoomInfoTaskMessage();

        $request->setWeChatId($data['WeChatId']);

        if (isset($data['ChatRoomId'])) {
            $request->setChatRoomId($data['ChatRoomId']);
        }

        if (isset($data['Flag'])) {
            $request->setFlag($data['Flag']);
        }

        return $request;
    }
}

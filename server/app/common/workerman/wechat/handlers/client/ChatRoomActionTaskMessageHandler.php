<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use Jubo\JuLiao\IM\Wx\Proto\ChatRoomActionTaskMessage;
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;

/**
 * 群聊操作任务处理器
 * 
 * @method static array handle(array $data) 业务处理
 * @author Qasim
 * @package app\handlers\client
 */
class ChatRoomActionTaskMessageHandler extends BaseHandler
{

    /**
     * 处理群聊操作任务
     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {
        // 构造推送任务请求
        $content = $this->buildRequestContent($data);

        return $this->buildProtobufResponse(EnumMsgType::ChatRoomActionTask, $content);
    }

    /**
     * 构建推送任务请求内容
     *
     * @param array $data 请求数据
     * @return ChatRoomActionTaskMessage
     */
    protected function buildRequestContent(array $data): ChatRoomActionTaskMessage
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *           商家所属微信号
         *     @type string $ChatRoomId
         *           群聊id
         *     @type int $Action
         *           指令
         *     @type string $Content
         *           指令内容
         *     @type int $IntValue
         *          
         *     @type int|string $taskId
         */

        $request = new ChatRoomActionTaskMessage();

        $request->setWeChatId($data['WeChatId']);

        if (isset($data['ChatRoomId'])) {
            $request->setChatRoomId($data['ChatRoomId']);
        }

        if (isset($data['Action'])) {
            $request->setAction($data['Action']);
        }

        if (isset($data['Content'])) {
            $request->setContent($data['Content']);
        }

        if (isset($data['IntValue'])) {
            $request->setIntValue($data['IntValue']);
        }

        if (isset($data['TaskId'])) {
            $request->setTaskId($data['TaskId']);
        }

        return $request;
    }
}

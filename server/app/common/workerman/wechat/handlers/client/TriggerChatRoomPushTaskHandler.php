<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use Jubo\JuLiao\IM\Wx\Proto\TriggerChatRoomPushTaskMessage;
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;

/**
 * 触发群聊推送任务处理器
 * 
 * @method static array handle(array $data) 业务处理
 * @author Qasim
 * @package app\handlers\client
 */
class TriggerChatRoomPushTaskHandler extends BaseHandler
{

    /**
     * 处理触发群聊推送任务
     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {
        // 构造推送任务请求
        $content = $this->buildRequestContent($data);

        return $this->buildProtobufResponse(EnumMsgType::TriggerChatroomPushTask, $content);
    }

    /**
     * 构建推送任务请求内容
     *
     * @param array $data 请求数据
     * @return TriggerChatRoomPushTaskMessage
     */
    protected function buildRequestContent(array $data): TriggerChatRoomPushTaskMessage
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *           商家所属微信号
         *     @type int $Flag
         *           Flag = 1:不推送成员信息（ChatRoomMembersNotice)
         *     @type int|string $TaskId
         */

        $request = new TriggerChatRoomPushTaskMessage();

        $request->setWeChatId($data['WeChatId']);

        if (isset($data['Flag'])) {
            $request->setFlag($data['Flag']);
        }

        if (isset($data['TaskId'])) {
            $request->setTaskId($data['TaskId']);
        }

        return $request;
    }
}

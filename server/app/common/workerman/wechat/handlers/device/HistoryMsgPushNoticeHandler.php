<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\device;

use Jubo\JuLiao\IM\Wx\Proto\{EnumMsgType, HistoryMsgPushNoticeMessage, ChatMessage};

/**
 * 历史消息推送通知处理器
 * 
 * @method static array handle(string $data, array $context) 业务处理
 * @author Qasim
 * @package app\handlers\device
 */
class HistoryMsgPushNoticeHandler extends BaseHandler
{

    /**
     * 处理历史消息推送通知
     * 
     * @param string $data 二进制数据
     * @param array $context 上下文数据
     * @return array
     */
    protected function handle(string $data, array $context): array
    {
        $request = new HistoryMsgPushNoticeMessage();
        $request->mergeFromString($data);

        $deviceId = $context['DeviceId'];

        // 构建响应内容
        $content = $this->buildResponseContent($request, $deviceId);

        // 返回响应体
        return $this->buildJsonResponse(EnumMsgType::HistoryMsgPushNotice, $content);
    }

    /**
     * 构建响应内容
     * 
     * @param HistoryMsgPushNoticeMessage $request 历史消息推送通知消息
     * @param string $deviceId 设备ID
     * @return array
     */
    private function buildResponseContent(HistoryMsgPushNoticeMessage $request, string $deviceId): array
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *           商家个人微信内部全局唯一识别码
         *     @type \Jubo\JuLiao\IM\Wx\Proto\ChatMessage[]|\Google\Protobuf\Internal\RepeatedField $Messages
         *     @type int $Size
         *           页大小，固定50
         *     @type int $Count
         *           总数，只在获取单个会话时有用，获取全部会话的时候不准确
         *     @type int $Page
         *           页码，从0开始
         *     @type int|string $TaskId
         */

        // 获取历史消息
        $messages = [];
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $FriendId
         *           好友微信内部全局唯一识别码
         *     @type int $ContentType
         *           发送的消息内容类型
         *     @type string $Content
         *           内容 二进制流
         *     @type int|string $MsgId
         *          服务端的主键id
         *     @type int|string $MsgSvrId
         *           消息唯一id
         *     @type bool $IsSend
         *           发送或接收
         *     @type int|string $CreateTime
         *           发送时间
         *     @type int $Status
         * @var ChatMessage $message
         */
        foreach ($request->getMessages() as $message) {

            $messages[] = [
                'FriendId' => $message->getFriendId(),
                'ContentType' => $message->getContentType(),
                'Content' => base64_decode(base64_encode($message->getContent())),
                'MsgId' => $message->getMsgId(),
                'MsgSvrId' => (string)$message->getMsgSvrId(),
                'IsSend' => $message->getIsSend(),
                'CreateTime' => $message->getCreateTime(),
                'Status' => $message->getStatus(),
            ];
        }

        $content = [
            'DeviceId' => $deviceId,
            'WeChatId' => $request->getWeChatId(),
            'Messages' => $messages,
            'Size' => $request->getSize(),
            'Count' => $request->getCount(),
            'Page' => $request->getPage(),
            'TaskId' => $request->getTaskId(),
        ];

        $this->logInfo('History msg push notice', array_merge($content, [
            'Messages' => count($messages),
        ]));

        return $content;
    }
}

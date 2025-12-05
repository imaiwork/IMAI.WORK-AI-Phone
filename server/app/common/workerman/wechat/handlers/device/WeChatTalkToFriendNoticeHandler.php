<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\device;

use Jubo\JuLiao\IM\Wx\Proto\{EnumMsgType, WeChatTalkToFriendNoticeMessage};

/**
 * 微信发送消息结果通知处理器
 * 
 * @method static array handle(string $data, array $context) 业务处理
 * @author Qasim
 * @package app\handlers\device
 */
class WeChatTalkToFriendNoticeHandler extends BaseHandler
{
    /**
     * 处理发送消息结果通知
     * 
     * @param string $data 二进制数据
     * @param array $context 上下文数据
     * @return array
     */
    protected function handle(string $data, array $context): array
    {
        $request = new WeChatTalkToFriendNoticeMessage();
        $request->mergeFromString($data);

        $deviceId = $context['DeviceId'];

        // 构建响应内容
        $content = $this->buildResponseContent($request, $deviceId);

        // 返回响应体
        return $this->buildJsonResponse(EnumMsgType::WeChatTalkToFriendNotice, $content);
    }

    /**
     * 构建响应内容
     * 
     * @param WeChatTalkToFriendNoticeMessage $request 微信发送消息结果通知消息
     * @param string $deviceId 设备ID
     * @return array
     */
    private function buildResponseContent(WeChatTalkToFriendNoticeMessage $request, string $deviceId): array
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *           登录的人的微信号
         *     @type string $FriendId
         *           对方的微信号
         *     @type int $ContentType
         *           发送的消息内容类型
         *     @type string $Content
         *           内容 二进制流
         *     @type int|string $MsgId
         *           本地Id，没啥用
         *     @type int|string $msgSvrId
         *           消息唯一id
         *     @type int|string $CreateTime
         *           消息时间
         *     @type string $Ext
         *     @type int|string $TaskId
         *           发消息的任务id，=TalkToFriendTaskMessage.MsgId
         */

        $content = [
            'DeviceId' => $deviceId,
            'WeChatId' => $request->getWeChatId(),
            'FriendId' => $request->getFriendId(),
            'ContentType' => $request->getContentType(),
            'Content' => base64_decode(base64_encode($request->getContent())),
            'MsgId' => $request->getMsgId(),
            'MsgSvrId' => (string)$request->getMsgSvrId(),
            'CreateTime' => $request->getCreateTime(),
            'Ext' => $request->getExt(),
            'TaskId' => $request->getTaskId(),
        ];

        $this->logInfo('WeChat talk to friend notice', $content);

        return $content;
    }
}

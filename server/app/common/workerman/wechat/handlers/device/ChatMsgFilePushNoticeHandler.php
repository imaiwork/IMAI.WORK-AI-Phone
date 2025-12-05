<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\device;

use Jubo\JuLiao\IM\Wx\Proto\{EnumMsgType, ChatMsgFilePushNoticeMessage};

/**
 * 聊天文件推送通知处理器
 * 
 * @method static array handle(string $data, array $context) 业务处理
 * @author Qasim
 * @package app\handlers\device
 */
class ChatMsgFilePushNoticeHandler extends BaseHandler
{
    /**
     * 处理聊天文件推送通知
     * 
     * @param string $data 二进制数据
     * @param array $context 上下文数据
     * @return array
     */
    protected function handle(string $data, array $context): array
    {
        $request = new ChatMsgFilePushNoticeMessage();
        $request->mergeFromString($data);

        $deviceId = $context['DeviceId'];

        // 构建响应内容
        $content = $this->buildResponseContent($request, $deviceId);

        // 返回响应体
        return $this->buildJsonResponse(EnumMsgType::ChatMsgFilePushNotice, $content);
    }

    /**
     * 构建响应内容
     * 
     * @param ChatMsgFilePushNoticeMessage $request 聊天文件推送通知消息
     * @param string $deviceId 设备ID
     * @return array
     */
    private function buildResponseContent(ChatMsgFilePushNoticeMessage $request, string $deviceId): array
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *           商家个人微信内部全局唯一识别码
         *     @type int|string $MsgSvrId
         *           消息唯一id
         *     @type int $MsgType
         *           消息类型
         *     @type string $Url
         *          
         *     @type int|string $FileSize
         *          
         *     @type int $SubType
         *           文件类型（图片消息：0 大图，1 原图 ）
         */

        $content = [
            'DeviceId' => $deviceId,
            'WeChatId' => $request->getWeChatId(),
            'MsgSvrId' => (string)$request->getMsgSvrId(),
            'MsgType' => $request->getMsgType(),
            'Url' => $request->getUrl(),
            'FileSize' => $request->getFileSize(),
            'SubType' => $request->getSubType(),
        ];

        $this->logInfo('Chat msg file push notice', $content);

        return $content;
    }
}

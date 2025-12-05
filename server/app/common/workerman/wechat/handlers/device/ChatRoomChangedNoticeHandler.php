<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\device;

use Jubo\JuLiao\IM\Wx\Proto\{EnumMsgType, ChatRoomChangedNoticeMessage};

/**
 * 群聊信息变更通知处理器
 * 
 * @method static array handle(string $data, array $context) 业务处理
 * @author Qasim
 * @package app\handlers\device
 */
class ChatRoomChangedNoticeHandler extends BaseHandler
{

    /**
     * 处理群聊信息变更通知
     * 
     * @param string $data 二进制数据
     * @param array $context 上下文数据
     * @return array
     */
    protected function handle(string $data, array $context): array
    {
        $request = new ChatRoomChangedNoticeMessage();
        $request->mergeFromString($data);

        $deviceId = $context['DeviceId'];

        // 构建响应内容
        $content = $this->buildResponseContent($request, $deviceId);

        // 返回响应体
        return $this->buildJsonResponse(EnumMsgType::ChatRoomChangedNotice, $content);
    }

    /**
     * 构建响应内容
     * 
     * @param ChatRoomChangedNoticeMessage $request 群聊信息变更通知消息
     * @param string $deviceId 设备ID
     * @return array
     */
    private function buildResponseContent(ChatRoomChangedNoticeMessage $request, string $deviceId): array
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *           商家所属微信号
         *     @type string $UserName
         *     @type int $What
         *     @type string $Content
         */

        $content = [
            'DeviceId' => $deviceId,
            'WeChatId' => $request->getWeChatId(),
            'UserName' => $request->getUserName(),
            'What'  => $request->getWhat(),
            'Content'  => $request->getContent(),
        ];

        $this->logInfo('Chat room changed notice', $content);

        return $content;
    }
}

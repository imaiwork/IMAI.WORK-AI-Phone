<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\device;

use Jubo\JuLiao\IM\Wx\Proto\{EnumMsgType, ConvDelNoticeMessage};

/**
 * 会话删除通知处理器
 * 
 * @method static array handle(string $data, array $context) 业务处理
 * @author Qasim
 * @package app\handlers\device
 */
class ConvDelNoticeHandler extends BaseHandler
{

    /**
     * 处理会话推送通知
     * 
     * @param string $data 二进制数据
     * @param array $context 上下文数据
     * @return array
     */
    protected function handle(string $data, array $context): array
    {
        $request = new ConvDelNoticeMessage();
        $request->mergeFromString($data);

        $deviceId = $context['DeviceId'];

        // 构建响应内容
        $content = $this->buildResponseContent($request, $deviceId);

        // 返回响应体
        return $this->buildJsonResponse(EnumMsgType::ConvDelNotice, $content);
    }

    /**
     * 构建响应内容
     * 
     * @param ConvDelNoticeMessage $request 会话删除通知消息
     * @param string $deviceId 设备ID
     * @return array
     */
    private function buildResponseContent(ConvDelNoticeMessage $request, string $deviceId): array
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *          
         *     @type string $FriendId
         *           会话Id
         *     @type string $ConvName
         *           会话名称
         *     @type string $Avatar
         *           头像
         */

        $content = [
            'DeviceId' => $deviceId,
            'WeChatId' => $request->getWeChatId(),
            'FriendId' => $request->getFriendId(),
            'ConvName' => $request->getConvName(),
            'Avatar' => $request->getAvatar(),
        ];

        $this->logInfo('Conversation del notice', $content);

        return $content;
    }
}

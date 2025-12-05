<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\device;

use Jubo\JuLiao\IM\Wx\Proto\{EnumMsgType, PostMessageReadNoticeMessage};

/**
 * 消息已读通知处理器
 * 
 * @method static array handle(string $data, array $context) 业务处理
 * @author Qasim
 * @package app\handlers\device
 */
class PostMessageReadNoticeHandler extends BaseHandler
{

    /**
     * 处理消息已读通知
     * 
     * @param string $data 二进制数据
     * @param array $context 上下文数据
     * @return array
     */
    protected function handle(string $data, array $context): array
    {
        $request = new PostMessageReadNoticeMessage();
        $request->mergeFromString($data);

        $deviceId = $context['DeviceId'];

        // 构建响应内容
        $content = $this->buildResponseContent($request, $deviceId);

        // 返回响应体
        return $this->buildJsonResponse(EnumMsgType::PostMessageReadNotice, $content);
    }

    /**
     * 构建内容
     * 
     * @param PostMessageReadNoticeMessage $request 消息已读通知消息
     * @param string $deviceId 设备ID
     * @return array
     */
    private function buildResponseContent(PostMessageReadNoticeMessage $request, string $deviceId): array
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *           所属微信
         *     @type string $FriendId
         *           好友id
         */

        $content = [
            'DeviceId' => $deviceId,
            'WeChatId' => $request->getWeChatId(),
            'FriendId' => $request->getFriendId(),
        ];


        $this->logInfo('Post message read notice', $content);

        return $content;
    }
}

<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\device;

use Jubo\JuLiao\IM\Wx\Proto\{EnumMsgType, FriendDelNoticeMessage};

/**
 * 好友删除通知处理器
 * 
 * @method static array handle(string $data, array $context) 业务处理
 * @author Qasim
 * @package app\handlers\device
 */
class FriendDelNoticeHandler extends BaseHandler
{

    /**
     * 处理好友删除通知
     * 
     * @param string $data 二进制数据
     * @param array $context 上下文数据
     * @return array
     */
    protected function handle(string $data, array $context): array
    {
        $request = new FriendDelNoticeMessage();
        $request->mergeFromString($data);

        $deviceId = $context['DeviceId'];

        // 构建响应内容
        $content = $this->buildResponseContent($request, $deviceId);

        // 返回响应体
        return $this->buildJsonResponse(EnumMsgType::FriendDelNotice, $content);
    }

    /**
     * 构建响应内容
     * 
     * @param FriendDelNoticeMessage $request 好友删除通知消息
     * @param string $deviceId 设备ID
     * @return array
     */
    private function buildResponseContent(FriendDelNoticeMessage $request, string $deviceId): array
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *           商家个人微信内部全局唯一识别码
         *     @type string $FriendId
         *           删除的好友微信内部全局唯一识别码
         *     @type int $Reason
         *           0 删除，1 拉黑
         */

        $content = [
            'DeviceId' => $deviceId,
            'WeChatId' => $request->getWeChatId(),
            'FriendId' => $request->getFriendId(),
            'Reason' => $request->getReason(),
        ];

        $this->logInfo('Friend del notice', $content);

        return $content;
    }
}

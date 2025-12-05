<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\device;

use Jubo\JuLiao\IM\Wx\Proto\{EnumMsgType, CallLogPushNoticeMessage};

/**
 * 通话记录推送通知处理器
 * 
 * @method static array handle(string $data, array $context) 业务处理
 * @author Qasim
 * @package app\handlers\device
 */
class CallLogPushNoticeHandler extends BaseHandler
{
    /**
     * 处理通话记录推送通知
     * 
     * @param string $data 二进制数据
     * @param array $context 上下文数据
     * @return array
     */
    protected function handle(string $data, array $context): array
    {
        $request = new CallLogPushNoticeMessage();
        $request->mergeFromString($data);

        $deviceId = $context['DeviceId'];

        // 构建响应内容
        $content = $this->buildResponseContent($request, $deviceId);

        // 返回响应体
        return $this->buildJsonResponse(EnumMsgType::CallLogPushNotice, $content);
    }

    /**
     * 构建响应内容
     * 
     * @param CallLogPushNoticeMessage $request 聊天文件推送通知消息
     * @param string $deviceId 设备ID
     * @return array
     */
    private function buildResponseContent(CallLogPushNoticeMessage $request, string $deviceId): array
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *           商家个人微信内部全局唯一识别码
         *     @type int|string $IMEI
         *           手机设备号
         *     @type array $Messages
         *           通话记录
         */

        $content = [
            'DeviceId' => $deviceId,
            'WeChatId' => $request->getWeChatId(),
            'IMEI' => $request->getIMEI(),
            'Messages' => $request->getMessages()
        ];

        $this->logInfo('Call log push notice', $content);

        return $content;
    }
}

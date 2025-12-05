<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\device;

use Jubo\JuLiao\IM\Wx\Proto\{EnumMsgType, CircleDelNoticeMessage};

/**
 * 手机上删除朋友圈返回通知
 * 
 * @method static array handle(string $data, array $context) 业务处理
 * @author Qasim
 * @package app\handlers\device
 */
class CircleDelNoticeHandler extends BaseHandler
{
    /**
     * 处理获取朋友圈推送务通知
     * 
     * @param string $data 二进制数据
     * @param array $context 上下文数据
     * @return array
     */
    protected function handle(string $data, array $context): array
    {
        $request = new CircleDelNoticeMessage();
        $request->mergeFromString($data);

        $deviceId = $context['DeviceId'];

        // 构建响应内容
        $content = $this->buildResponseContent($request, $deviceId);

        // 返回响应体
        return $this->buildJsonResponse(EnumMsgType::CircleDelNotice, $content);
    }

    /**
     * 构建响应内容
     * 
     * @param CircleDelNoticeMessage $request 处理获取朋友圈任务通知消息
     * @param string $deviceId 设备ID
     * @return array
     */
    private function buildResponseContent(CircleDelNoticeMessage $request, string $deviceId): array
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *           所属微信号
         *     @type int64 $CircleId
         *           朋友圈id
         */
        

        $content = [
            'DeviceId' => $deviceId,
            'WeChatId' => $request->getWeChatId(),
            'CircleId' => (string)$request->getCircleId(),
        ];

        $this->logInfo('Circle del notice', $content);

        return $content;
    }
}

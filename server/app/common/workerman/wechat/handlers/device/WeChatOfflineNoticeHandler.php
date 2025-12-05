<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\device;

use Jubo\JuLiao\IM\Wx\Proto\{EnumMsgType, WeChatOfflineNoticeMessage};

/**
 * 微信下线通知处理器
 * 
 * - 缓存微信信息
 * 
 * @method static array handle(string $data, array $context) 业务处理
 * @author Qasim
 * @package app\handlers\device
 */
class WeChatOfflineNoticeHandler extends BaseHandler
{
    /**
     * 处理微信下线通知
     * 
     * @param string $data 二进制数据
     * @param array $context 上下文数据
     * @return array
     */
    protected function handle(string $data, array $context): array
    {
        $request = new WeChatOfflineNoticeMessage();
        $request->mergeFromString($data);

        $deviceId = $context['DeviceId'];

        // 构建响应内容
        $content = $this->buildResponseContent($request, $deviceId);

        // 返回响应体
        return $this->buildJsonResponse(EnumMsgType::WeChatOfflineNotice, $content);
    }

    /**
     * 构建响应内容
     * 
     * @param WeChatOfflineNoticeMessage $request 微信下线通知消息
     * @param string $deviceId 设备ID
     * @return array
     */
    private function buildResponseContent(WeChatOfflineNoticeMessage $request, string $deviceId): array
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *           微信内部全局唯一识别码
         *     @type string $IMEI
         *           设备唯一号
         *     @type int $Reason
         *           下线原因
         */

        // 获取原缓存数据
        $key = $this->getWechatKey($deviceId, $request->getWeChatId());
        $content = $this->redis()->hGetAll($key);

        // 构建更新信息
        $content = array_merge($content, [
            'Status' => 'offline',
            'Reason' => $request->getReason(),
            'UpdateTime' => time()
        ]);

        $this->logInfo('WeChat offline notice', $content);

        // 缓存微信信息
        $this->cacheContent($deviceId, $content);

        return $content;
    }

    /**
     * 缓存内容
     * 
     * @param string $deviceId 设备ID
     * @param array $content 信息
     * @return void
     */
    private function cacheContent(string $deviceId, array $content): void
    {
        $wechatId = $content['WeChatId'];

        // 缓存微信ID
        $key = $this->getDeviceKey($deviceId, 'wechat');
        $this->redis()->set($key, $wechatId);

        // 缓存微信信息
        $key = $this->getWechatKey($deviceId, $wechatId);
        $this->redis()->hMSet($key, $content);
    }
}

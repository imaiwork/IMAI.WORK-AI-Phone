<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\device;

use Jubo\JuLiao\IM\Wx\Proto\{EnumMsgType, WeChatOnlineNoticeMessage};

/**
 * 微信上线通知处理器
 * 
 * - 缓存微信信息
 * 
 * @method static array handle(string $data, array $context) 业务处理
 * @author Qasim
 * @package app\handlers\device
 */
class WeChatOnlineNoticeHandler extends BaseHandler
{
    /**
     * 处理微信上线通知
     * 
     * @param string $data 二进制数据
     * @param array $context 上下文数据
     * @return array
     */
    protected function handle(string $data, array $context): array
    {
        $request = new WeChatOnlineNoticeMessage();
        $request->mergeFromString($data);

        $deviceId = $context['DeviceId'];

        // 构建响应内容
        $content = $this->buildResponseContent($request, $deviceId);

        // 返回响应体
        return $this->buildJsonResponse(EnumMsgType::WeChatOnlineNotice, $content);
    }

    /**
     * 构建内容
     * 
     * @param WeChatOnlineNoticeMessage $request 微信上线通知消息
     * @param string $deviceId 设备ID
     * @return array
     */
    private function buildResponseContent(WeChatOnlineNoticeMessage $request, string $deviceId): array
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *          微信内部全局唯一识别码
         *     @type string $WeChatNo
         *          微信号（如果用户设置了微信号）
         *     @type string $WeChatNick
         *          微信昵称
         *     @type int $Gender
         *          性别
         *     @type string $Country
         *          国家
         *     @type string $Province
         *          省份
         *     @type string $City
         *          城市
         *     @type string $Avatar
         *          微信头像
         *     @type string $IMEI
         *           imei号
         *     @type string $Phone
         */

        $content = [
            'DeviceId' => $deviceId,
            'WeChatId' => $request->getWeChatId(),
            'WeChatNo' => $request->getWeChatNo(),
            'WeChatNick' => $request->getWeChatNick(),
            'Gender' => $request->getGender(),
            'Country' => $request->getCountry(),
            'Province' => $request->getProvince(),
            'City' => $request->getCity(),
            'Avatar' => $request->getAvatar(),
            'IMEI' => $request->getIMEI(),
            'Phone' => $request->getPhone(),
            'Status' => 'online',
            'UpdateTime' => time()
        ];

        $this->logInfo('WeChat online notice', $content);

        // 缓存内容
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

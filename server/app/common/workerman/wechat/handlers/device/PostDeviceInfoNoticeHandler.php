<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\device;

use Jubo\JuLiao\IM\Wx\Proto\{EnumMsgType, PostDeviceInfoNoticeMessage, PostDeviceInfoNoticeMessage\DeviceAppInfoMessage};

/**
 * 设备信息通知处理器
 * 
 * - 缓存设备信息
 * 
 * @method static array handle(string $data, array $context) 业务处理
 * @author Qasim
 * @package app\handlers\device
 */
class PostDeviceInfoNoticeHandler extends BaseHandler
{

    /**
     * 处理设备信息通知
     * 
     * @param string $data 二进制数据
     * @param array $context 上下文数据
     * @return array
     */
    protected function handle(string $data, array $context): array
    {
        $request = new PostDeviceInfoNoticeMessage();
        $request->mergeFromString($data);

        $deviceId = $context['DeviceId'];

        // 构建响应内容
        $content = $this->buildResponseContent($request, $deviceId);

        // 返回响应体
        return $this->buildJsonResponse(EnumMsgType::PostDeviceInfoNotice, $content);
    }

    /**
     * 构建内容
     * 
     * @param PostDeviceInfoNoticeMessage $request 设备信息通知消息
     * @param string $deviceId 设备ID
     * @return array
     */
    private function buildResponseContent(PostDeviceInfoNoticeMessage $request, string $deviceId): array
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $PhoneBrand
         *           手机品牌
         *     @type string $PhoneModel
         *           手机型号
         *     @type int $OSVerNumber
         *     @type \Jubo\JuLiao\IM\Wx\Proto\PostDeviceInfoNoticeMessage\DeviceAppInfoMessage[]|\Google\Protobuf\Internal\RepeatedField $AppInfos
         *           App信息
         *     @type string $NetType
         *     @type string $WeChatId
         *           微信id
         *     @type string $IMEI
         *     @type string $IMSI1
         *           SIM卡1的IMSI
         *     @type string $IMSI2
         *           SIM卡2的IMSI,
         *     @type string $Number1
         *           SIM卡1的手机号，有可能读不到
         *     @type string $Number2
         *           SIM卡2的手机好，有可能读不到
         *     @type bool $IsHook
         *     @type bool $WxSupport          
         */
        $appInfos = [];
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $PackageName
         *     @type string $AppName
         *     @type int $VerNumber
         *     @type string $Version
         * @var DeviceAppInfoMessage $appInfo
         */
        foreach ($request->getAppInfos() as $appInfo) {
            $appInfos[] = [
                'PackageName' => $appInfo->getPackageName(),
                'AppName' => $appInfo->getAppName(),
                'VerNumber' => $appInfo->getVerNumber(),
                'Version' => $appInfo->getVersion(),
            ];
        }

        $content = [
            'DeviceId' => $deviceId,
            'PhoneBrand' => $request->getPhoneBrand(),
            'PhoneModel' => $request->getPhoneModel(),
            'OSVerNumber' => $request->getOSVerNumber(),
            'AppInfos' => json_encode($appInfos, JSON_UNESCAPED_UNICODE),
            'NetType' => $request->getNetType(),
            'WeChatId' => $request->getWeChatId(),
            'IMEI' => $request->getIMEI(),
            'IMSI1' => $request->getIMSI1(),
            'IMSI2' => $request->getIMSI2(),
            'Number1' => $request->getNumber1(),
            'Number2' => $request->getNumber2(),
            'IsHook' => $request->getIsHook(),
            'WxSupport' => $request->getWxSupport(),
        ];

        $this->logInfo('Post device info notice', $content);

        // 缓存设备信息
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
        // 缓存设备信息
        $key = $this->getDeviceKey($deviceId, 'info');
        $this->redis()->hMSet($key, $content);
    }
}

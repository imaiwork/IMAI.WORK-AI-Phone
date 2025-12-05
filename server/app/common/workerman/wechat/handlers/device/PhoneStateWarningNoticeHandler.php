<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\device;

use Jubo\JuLiao\IM\Wx\Proto\{EnumMsgType, PhoneStateWarningNoticeMessage};


/**
 * 手机状态通知处理器
 * 
 * @method static array handle(string $data, array $context) 业务处理
 * @author Qasim
 * @package app\handlers\device
 */
class PhoneStateWarningNoticeHandler extends BaseHandler
{

    /**
     * 处理手机状态通知
     * 
     * @param string $data 二进制数据
     * @param array $context 上下文数据
     * @return array
     */
    protected function handle(string $data, array $context): array
    {
        $request = new PhoneStateWarningNoticeMessage();
        $request->mergeFromString($data);

        $deviceId = $context['DeviceId'];

        // 构建响应内容
        $content = $this->buildResponseContent($request, $deviceId);

        // 返回响应体
        return $this->buildJsonResponse(EnumMsgType::FriendAddReqeustNotice, $content);
    }

    /**
     * 构建响应内容
     * 
     * @param PhoneStateWarningNoticeMessage $request 手机状态通知消息
     * @param string $deviceId 设备ID
     * @return array
     */
    private function buildResponseContent(PhoneStateWarningNoticeMessage $request, string $deviceId): array
    {
        $content = [
            'DeviceId' => $deviceId,
            'WeChatId' => $request->getWeChatId(),
            'getImei' => $request->getImei(),
            'BatteryLevel' => $request->getBatteryLevel(),
            'ChargingState' => $request->getChargingState(),
            'NetType' => $request->getNetType(),
            'SdcardFree' => $request->getSdcardFree(),
            'SdcardTotal' => $request->getSdcardTotal(),
        ];

        $this->logInfo('Phone state warning notice', $content);

        return $content;
    }
}

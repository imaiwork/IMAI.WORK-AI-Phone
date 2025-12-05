<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\device;

use Jubo\JuLiao\IM\Wx\Proto\{EnumMsgType, HeartBeatMessage};

/**
 * 设备心跳处理器
 * 
 * - 更新心跳时间
 * - 构建心跳响应
 * 
 * @method static array handle(string $data, array $context) 业务处理
 * @author Qasim
 * @package app\handlers\device
 */
class HeartBeatReqHandler extends BaseHandler
{
    /**
    /**
     * 处理设备心跳
     * 
     * @param string $data 心跳数据
     * @param array $context 上下文数据
     * @return array
     */
    protected function handle(string $data, array $context): array
    {
        $request = new HeartBeatMessage();
        $request->mergeFromString($data);

        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $Imei
         *           设备号
         *     @type string $WeChatId
         *           商家个人微信内部全局唯一识别码
         */

        $deviceId = $context['DeviceId'];

        // 更新心跳时间
        $this->updateDeviceHeartbeat($deviceId);
        // 更新设备状态
        $this->updateDeviceStatus($deviceId, true);
        // 发送响应
        $response = new HeartBeatMessage();

        // 返回响应体
        return $this->buildProtobufResponse(EnumMsgType::MsgReceivedAck, $response);
    }
}

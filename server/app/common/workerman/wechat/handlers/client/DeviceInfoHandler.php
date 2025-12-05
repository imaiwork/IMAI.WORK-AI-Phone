<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use app\common\workerman\wechat\constants\ClientRequestMsgType;
use app\common\workerman\wechat\exceptions\ResponseException;
use app\common\workerman\wechat\constants\ResponseCode;

/**
 * 设备信息请求处理器
 * 
 * @method static array handle(array $data) 业务处理
 * @author Qasim
 * @package app\handlers\client
 */
class DeviceInfoHandler extends BaseHandler
{

    /**
     * 处理设备信息
     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {
        $deviceId = $data['DeviceId'];

        // 获取设备信息
        $deviceInfo = $this->getDeviceInfo($deviceId);

        // 更新设备在线状态
        $deviceInfo['IsOnline'] = $this->isDeviceOnline($deviceId);
        $this->updateDevices($deviceInfo);

        // 构建内容
        $content = $deviceInfo;

        return $this->buildJsonResponse(ClientRequestMsgType::DEVICE_INFO, $content);
    }
}

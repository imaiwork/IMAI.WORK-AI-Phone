<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use app\common\workerman\wechat\constants\ClientRequestMsgType;
use app\common\workerman\wechat\exceptions\ResponseException;
use app\common\workerman\wechat\constants\ResponseCode;

/**
 * 添加设备请求处理器
 * 
 * @method static array handle(array $data) 业务处理
 * @author Qasim
 * @package app\handlers\client
 */
class AddDeviceHandler extends BaseHandler
{

    /**
     * 处理添加设备
     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {
        $deviceId = $data['DeviceId'];

        // 获取设备信息
        $deviceInfo = $this->getDeviceInfo($deviceId);

        // 检查设备是否被使用
        if ($deviceInfo['IsUsed']) {

            throw new ResponseException(ResponseCode::DEVICE_ALREADY_USED);
        }

        // 更新设备信息
        $deviceInfo['IsUsed'] = true;
        $deviceInfo['IsOnline'] = $this->isDeviceOnline($deviceId);
        //$this->updateDevices($deviceInfo);

        // 构建内容
        $content = $deviceInfo;

        return $this->buildJsonResponse(ClientRequestMsgType::ADD_DEVICE, $content);
    }
}

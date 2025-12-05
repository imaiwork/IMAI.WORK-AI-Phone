<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\validators;

use app\common\workerman\wechat\traits\{LoggerTrait, DeviceTrait};
use app\common\workerman\wechat\constants\ResponseCode;
use app\common\workerman\wechat\exceptions\ResponseException;
use app\common\workerman\wechat\constants\ClientRequestMsgType;
use app\common\workerman\wechat\services\Service;
use Workerman\Connection\TcpConnection;

/**
 * 消息验证器
 * 
 * @author Qasim
 * @package app\validator
 */
class MessageValidator
{
    use LoggerTrait, DeviceTrait;

    protected Service $service;

    public function __construct()
    {

        $this->service = Service::getInstance();
    }

    /**
     * 验证Socket消息
     * 
     * @param TcpConnection $connection 连接实例
     * @return string
     */
    public function validateDeviceMessage(TcpConnection $connection): string
    {
        $deviceId = $this->service->connectionService->getDeviceId($connection);

        // 获取获取到设备ID
        if (!isset($deviceId)) {

            throw new ResponseException(ResponseCode::UNAUTHORIZED);
        }


        // 设备不合法
        if (!$this->checkDevice($deviceId)) {
            $this->withChannel('wechat_socket')->withLevel('error')->withTitle('Device not found')->withContext([
                'deviceId' => $deviceId,
                'msg' => 'Device not found',
            ])->log();
            throw new ResponseException(ResponseCode::DEVICE_NOT_FOUND);
        }



        return $deviceId;
    }

    /**
     * 验证WebSocket消息
     * 
     * @param TcpConnection $connection 连接实例
     * @param array $message 消息
     * @return array
     */
    public function validateClientMessage(TcpConnection $connection, array $message): array
    {
        // 1. 验证消息格式
        if (!isset($message['MsgType']) || !isset($message['Content'])) {

            throw new ResponseException(ResponseCode::INVALID_PARAMS);
        }

        $type = $message['MsgType'];
        $data = $message['Content'];

        // 2. 根据消息类型验证
        switch ($type) {
            case ClientRequestMsgType::HEARTBEAT:
                break;

            case ClientRequestMsgType::AUTH:
            case ClientRequestMsgType::ADD_DEVICE:
                $this->validateDevice($connection, $data);
                break;

            default:
                $this->validateDevice($connection, $data);
                $this->validateClientRequest($data);
        }

        return [$type, $data];
    }

    /**
     * 验证认证参数
     * 
     * @param TcpConnection $connection 连接实例
     * @param array $data 数据
     * @return void
     */
    private function validateDevice(TcpConnection $connection, array $data): void
    {
        // 1. 验证设备ID
        if (!isset($data['DeviceId']) || empty($data['DeviceId'])) {

            throw new ResponseException(ResponseCode::INVALID_PARAMS);
        }

        // 2. 验证设备是否存在
        if (!$this->checkDevice($data['DeviceId'])) {

            throw new ResponseException(ResponseCode::DEVICE_NOT_FOUND);
        }

        // 3. 验证设备在线状态
        if (!$this->isDeviceOnline($data['DeviceId'])) {

            throw new ResponseException(ResponseCode::DEVICE_OFFLINE);
        }
    }


    /**
     * 验证客户端请求
     * 
     * @param array $data 数据
     * @return void
     */
    private function validateClientRequest(array $data): void
    {
        return;
        // 验证Token
        if (!isset($data['AccessToken']) || !$this->verifyToken($data['DeviceId'], $data['AccessToken'], 'client')) {

            throw new ResponseException(ResponseCode::UNAUTHORIZED);
        }
    }
}

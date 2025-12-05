<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\device;

use Jubo\JuLiao\IM\Wx\Proto\{EnumMsgType, DeviceAuthReqMessage, DeviceAuthRspMessage};
use app\common\workerman\wechat\exceptions\ResponseException;
use app\common\workerman\wechat\constants\ResponseCode;

/**
 * 设备认证请求处理器
 * 
 * - 生成Token
 * - 绑定设备连接
 * - 构建认证响应
 * 
 * @method static array handle(string $data, array $context) 业务处理
 * @author Qasim
 * @package app\handlers\device
 */
class DeviceAuthReqHandler extends BaseHandler
{

    /**
     * 处理设备认证
     *
     * @param string $data 原始消息数据
     * @param array $context 上下文数据
     * @return array
     */
    protected function handle(string $data, array $context): array
    {
        // 解析认证请求
        $request = new DeviceAuthReqMessage();
        $request->mergeFromString($data);

        $deviceId = $request->getCredential();
        $connection = $context['Connection'];

        if (empty($deviceId)) {

            throw new ResponseException(ResponseCode::DEVICE_NOT_FOUND);
        }

        $connection->deviceId = $deviceId;

        $this->logInfo('Processing device auth', [
            'device_id' => $deviceId
        ]);

        // 设置Token
        $token = $this->setToken($deviceId, 'device');

        // 构建认证响应
        $response = new DeviceAuthRspMessage();
        $response->setAccessToken($token);

        return $this->buildProtobufResponse(EnumMsgType::DeviceAuthRsp, $response);
    }
}

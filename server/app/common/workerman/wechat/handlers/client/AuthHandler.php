<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use app\common\workerman\wechat\constants\ClientRequestMsgType;

/**
 * 客户端认证请求处理器
 * 
 * - 生成Token
 * - 绑定设备连接
 * - 构建响应
 * 
 * @method static array handle(array $data) 业务处理
 * @author Qasim
 * @package app\handlers\client
 */
class AuthHandler extends BaseHandler
{

    /**
     * 处理客户端认证
     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {

        $deviceId = $data['DeviceId'];

        // 设置Token
        $token = $this->setToken($deviceId, 'client');

        // 获取微信ID
        $key = $this->getDeviceKey($deviceId, 'wechat');
        $wechatId = $this->redis()->get($key);

        // 构建内容
        $content = [
            'AccessToken' => $token,
            'DeviceId' => $deviceId,
            'WeChatId' => $wechatId,
        ];

        return $this->buildJsonResponse(ClientRequestMsgType::AUTH, $content);
    }
}

<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use app\common\workerman\wechat\constants\ClientRequestMsgType;
use app\common\workerman\wechat\exceptions\ResponseException;
use app\common\workerman\wechat\constants\ResponseCode;

/**
 * 微信信息请求处理器
 * 
 * @method static array handle(array $data) 业务处理
 * @author Qasim
 * @package app\handlers\client
 */
class WxInfoHandler extends BaseHandler
{

    /**
     * 处理微信信息
     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {
        $deviceId = $data['DeviceId'];

        // 获取微信ID
        $wechatId = $this->redis()->get($this->getDeviceKey($deviceId, 'wechat'));

        if (empty($wechatId)) {

            throw new ResponseException(ResponseCode::DEVICE_WECHAT_NOT_FOUND);
        }

        // 获取微信信息
        $key = $this->getWechatKey($deviceId, $wechatId);
        $content = $this->redis()->hGetAll($key) ?: [];

        return $this->buildJsonResponse(ClientRequestMsgType::WX_INFO, $content);
    }
}

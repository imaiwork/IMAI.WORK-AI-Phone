<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use app\common\workerman\wechat\constants\ClientRequestMsgType;

/**
 * 心跳请求处理器
 * 
 * @method static array handle(array $data) 业务处理
 * @author Qasim
 * @package app\handlers\client
 */
class HeartbeatHandler extends BaseHandler
{

    /**
     * 处理心跳
     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {
        return $this->buildJsonResponse(ClientRequestMsgType::HEARTBEAT, []);
    }
}

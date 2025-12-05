<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use app\common\workerman\wechat\constants\ClientRequestMsgType;
use app\common\workerman\wechat\exceptions\ResponseException;
use app\common\workerman\wechat\constants\ResponseCode;

/**
 * 清理缓存
 */
class CleanCacheHandler extends BaseHandler
{

    /**
     * 处理清理缓存请求

     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {
        $deviceId = $data['DeviceId'];

        // 构建内容
        $content = [
            'IsClean' => true,
            'Msg' => '清理缓存成功',
            'DeviceId' => $deviceId,
        ];

        return $this->buildJsonResponse(ClientRequestMsgType::CLEAN_CACHE, $content);
    }
}

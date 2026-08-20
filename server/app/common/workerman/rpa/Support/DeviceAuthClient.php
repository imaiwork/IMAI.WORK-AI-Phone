<?php

declare(strict_types=1);

namespace app\common\workerman\rpa\Support;

/**
 * RPA 设备鉴权调用（带有限超时，避免阻塞 Workerman 事件循环）
 */
class DeviceAuthClient
{
    public const CONNECT_TIMEOUT = 3;
    public const REQUEST_TIMEOUT = 5;

    /**
     * @param array $payload device_code/platform/code 等
     * @return array{code:int|string,message?:string,data?:mixed}
     */
    public static function checkSvDevice(array $payload): array
    {
        $response = \app\common\service\ToolsService::Auth()->checkSvDevice(
            $payload,
            self::CONNECT_TIMEOUT,
            self::REQUEST_TIMEOUT
        );

        return is_array($response) ? $response : ['code' => 10001, 'message' => '设备鉴权响应无效', 'data' => []];
    }

    public static function isTimeoutResponse(array $response): bool
    {
        if ((int)($response['code'] ?? 0) !== 10001) {
            return false;
        }
        $message = (string)($response['message'] ?? '');
        $errno = (int)($response['data']['curl_errno'] ?? 0);

        return $errno === CURLE_OPERATION_TIMEDOUT || str_contains($message, '超时');
    }
}

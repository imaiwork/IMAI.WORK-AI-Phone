<?php

namespace app\common\service\station;

use Exception;

/**
 * 中台 Push 验签（HMAC-SHA256 原始请求体）
 */
class StationAuthService
{
    public static function verify(string $rawBody, ?string $key = null, ?string $sign = null): void
    {
        $key = trim($key ?? (string)request()->header('Key', request()->header('key', '')));
        $sign = trim($sign ?? (string)request()->header('Sign', request()->header('sign', '')));

        $apiKey = env('PROJECT_KEY.API_KEY', '');
        $signKey = env('PROJECT_KEY.SIGN_KEY', '');

        if ($apiKey === '' || $key === '' || $key !== $apiKey) {
            throw new Exception('站长授权Key无效', 401);
        }
        if ($signKey === '') {
            return;
        }
        if ($sign === '') {
            throw new Exception('签名缺失', 401);
        }

        $expected = hash_hmac('sha256', $rawBody, $signKey);
        if (!hash_equals(strtolower($expected), strtolower($sign))) {
            throw new Exception('签名无效', 401);
        }
    }
}

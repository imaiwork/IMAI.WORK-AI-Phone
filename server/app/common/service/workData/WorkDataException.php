<?php

namespace app\common\service\workData;

use RuntimeException;

class WorkDataException extends RuntimeException
{
    public const BUSY_RETRY = '上游接口繁忙，请点击重试';

    public static function translateUpstream(string $msg): string
    {
        $msg = trim($msg);
        if ($msg !== '' && (stripos($msg, 'Invalid URL') !== false || str_contains($msg, 'invalid url'))) {
            return '中台未开放该取数路径，请联系站长加白名单';
        }
        if ($msg !== '' && (str_contains($msg, '密钥') || stripos($msg, 'API Key') !== false)) {
            return '缺少中台密钥，请联系站长配置';
        }
        if ($msg === '' || self::isRetryableUpstream($msg)) {
            return self::BUSY_RETRY;
        }
        return mb_substr($msg, 0, 300);
    }

    private static function isRetryableUpstream(string $msg): bool
    {
        if (stripos($msg, 'TikHub') !== false
            || str_contains($msg, '上游错误')
            || str_contains($msg, '上游接口返回')
            || str_contains($msg, '上游接口限流')
            || str_contains($msg, '上游返回')
            || str_contains($msg, '服务异常')
            || str_contains($msg, '超时')
            || stripos($msg, 'timeout') !== false
            || stripos($msg, 'timed out') !== false
        ) {
            return true;
        }
        if (preg_match('/[（(]\s*[45]\d{2}\s*[）)]/u', $msg)) {
            return true;
        }
        return (bool)preg_match('/HTTP\s*[45]\d{2}/i', $msg);
    }
}

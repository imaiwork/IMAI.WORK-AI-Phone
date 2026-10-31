<?php

namespace app\common\service\workData;

use RuntimeException;

class WorkDataException extends RuntimeException
{
    public const BUSY_RETRY = '上游接口繁忙，请点击重试';

    public static function translateUpstream(string $msg): string
    {
        $msg = trim($msg);
        if ($msg === '') {
            return self::BUSY_RETRY;
        }
        if (stripos($msg, 'Invalid URL') !== false || str_contains($msg, 'invalid url')) {
            return '中台未开放该取数路径，请联系站长加白名单';
        }
        if (str_contains($msg, '密钥') || stripos($msg, 'API Key') !== false) {
            return '缺少中台密钥，请联系站长配置';
        }
        return mb_substr($msg, 0, 300);
    }
}

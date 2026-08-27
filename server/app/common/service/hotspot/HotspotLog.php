<?php

namespace app\common\service\hotspot;

use think\facade\Log;

class HotspotLog
{
    /** @var list<string> */
    private static array $testLogs = [];

    private static bool $testCapture = false;

    public static function setTestCapture(bool $on): void
    {
        self::$testCapture = $on;
        self::$testLogs = [];
    }

    /**
     * @return list<string>
     */
    public static function testLogs(): array
    {
        return self::$testLogs;
    }

    public static function write(string $msg): void
    {
        if (self::$testCapture) {
            self::$testLogs[] = $msg;
            return;
        }
        Log::channel('hotspot')->write($msg);
    }

    public static function json(string $prefix, mixed $data, int $max = 1500): void
    {
        self::write($prefix . self::clip($data, $max));
    }

    public static function exception(string $prefix, \Throwable $e): void
    {
        self::write(sprintf(
            '%s：%s | 类型=%s | 位置=%s:%d',
            $prefix,
            $e->getMessage(),
            $e::class,
            $e->getFile(),
            $e->getLine()
        ));
    }

    public static function clip(mixed $data, int $max = 800): string
    {
        if ($data === null) {
            return '';
        }
        $text = is_string($data) ? $data : (string)json_encode($data, JSON_UNESCAPED_UNICODE);
        if ($text === '') {
            return '';
        }
        if (mb_strlen($text) > $max) {
            return mb_substr($text, 0, $max) . '…(已截断)';
        }
        return $text;
    }

    public static function safe(mixed $data): mixed
    {
        if (!is_array($data)) {
            return $data;
        }
        $hide = ['api_key', 'authorization', 'token', 'key', 'sign', 'secret', 'password'];
        $out = [];
        foreach ($data as $name => $value) {
            if (in_array(strtolower((string)$name), $hide, true)) {
                $out[$name] = '***';
                continue;
            }
            $out[$name] = is_array($value) ? self::safe($value) : $value;
        }
        return $out;
    }
}

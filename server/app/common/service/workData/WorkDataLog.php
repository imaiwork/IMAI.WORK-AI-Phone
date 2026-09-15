<?php

namespace app\common\service\workData;

use think\facade\Log;

class WorkDataLog
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
        Log::channel('work_data')->write($msg);
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

    /**
     * 完整 JSON，不截断；密钥字段脱敏。
     */
    public static function encode(mixed $data): string
    {
        $json = json_encode(self::safe($data), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return is_string($json) ? $json : '';
    }

    /**
     * @param array<string, mixed>|list<mixed>|string|null $data
     */
    public static function json(string $prefix, mixed $data): void
    {
        self::write($prefix . self::encode($data));
    }

    /**
     * @return mixed
     */
    public static function safe(mixed $data)
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

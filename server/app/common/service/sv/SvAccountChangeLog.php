<?php

declare(strict_types=1);

namespace app\common\service\sv;

use think\facade\Log;

/**
 * 账号行级变更文件日志。
 * 一行 JSON，固定字段缺省写空/0，不省略 key。
 */
class SvAccountChangeLog
{
    public const EVENT = 'account_change';

    public const SOURCE_SET_ACCOUNT = 'set_account';
    public const SOURCE_RPA = 'rpa';
    public const SOURCE_ADD_SV_ACCOUNT = 'add_sv_account';

    public const ACTION_INSERT = 'insert';
    public const ACTION_UPDATE = 'update';
    public const ACTION_DELETE = 'delete';
    public const ACTION_CONFLICT = 'conflict';

    /** @var callable(string, array):void|null */
    private static $writer = null;

    /**
     * @return list<string>
     */
    public static function requiredKeys(): array
    {
        return [
            'event',
            'source',
            'action',
            'result',
            'msg',
            'user_id',
            'device_code',
            'type',
            'account',
            'account_id',
            'changed_fields',
            'before_json',
            'after_json',
            'trace_id',
        ];
    }

    public static function setWriter(?callable $writer): void
    {
        self::$writer = $writer;
    }

    /**
     * @param array<string, mixed> $fields
     * @return array<string, int|string>
     */
    public static function record(string $source, string $action, array $fields = []): array
    {
        $fields['event'] = self::EVENT;
        $fields['source'] = $source;
        $fields['action'] = $action;
        if (!isset($fields['result']) || $fields['result'] === '') {
            $fields['result'] = $action === self::ACTION_CONFLICT ? 'fail' : 'ok';
        }
        return self::write($fields);
    }

    /**
     * @param array<string, mixed> $fields
     * @return array<string, int|string>
     */
    public static function write(array $fields): array
    {
        $payload = self::normalize($fields);
        $line = '账号变更 ' . json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
        );

        try {
            if (self::$writer !== null) {
                (self::$writer)($line, $payload);
                return $payload;
            }
            Log::channel('auto')->write($line, 'account');
        } catch (\Throwable) {
            // 日志失败不影响业务
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $fields
     * @return array<string, int|string>
     */
    public static function normalize(array $fields): array
    {
        $defaults = [
            'event' => self::EVENT,
            'source' => '',
            'action' => '',
            'result' => '',
            'msg' => '',
            'user_id' => 0,
            'device_code' => '',
            'type' => 0,
            'account' => '',
            'account_id' => 0,
            'changed_fields' => '[]',
            'before_json' => '{}',
            'after_json' => '{}',
            'trace_id' => '',
        ];

        $payload = [];
        foreach ($defaults as $key => $default) {
            if (!array_key_exists($key, $fields)) {
                $payload[$key] = $default;
                continue;
            }
            if ($key === 'changed_fields') {
                $payload[$key] = self::encodeList($fields[$key]);
                continue;
            }
            if ($key === 'before_json' || $key === 'after_json') {
                $payload[$key] = self::encodeObject($fields[$key]);
                continue;
            }
            $payload[$key] = is_int($default) ? (int)$fields[$key] : (string)$fields[$key];
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public static function snapshot(array $row): array
    {
        $extra = $row['extra'] ?? [];
        if (is_array($extra)) {
            $extra = json_encode($extra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
        } elseif (!is_string($extra) || $extra === '') {
            $extra = '{}';
        }

        return [
            'id' => (int)($row['id'] ?? 0),
            'user_id' => (int)($row['user_id'] ?? 0),
            'device_code' => (string)($row['device_code'] ?? ''),
            'type' => (int)($row['type'] ?? 0),
            'account' => (string)($row['account'] ?? ''),
            'account_no' => (string)($row['account_no'] ?? ''),
            'nickname' => (string)($row['nickname'] ?? ''),
            'avatar' => (string)($row['avatar'] ?? ''),
            'status' => (int)($row['status'] ?? 0),
            'is_verified' => (int)($row['is_verified'] ?? 0),
            'extra' => $extra,
        ];
    }

    /**
     * @param list<string>|string $value
     */
    public static function encodeList(mixed $value): string
    {
        if (is_string($value)) {
            return $value !== '' ? $value : '[]';
        }
        if (!is_array($value)) {
            return '[]';
        }

        return json_encode(array_values($value), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]';
    }

    public static function encodeObject(mixed $value): string
    {
        if (is_string($value)) {
            return $value !== '' ? $value : '{}';
        }
        if (!is_array($value)) {
            return '{}';
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    }
}

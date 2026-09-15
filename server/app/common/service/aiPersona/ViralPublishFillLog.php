<?php

declare(strict_types=1);

namespace app\common\service\aiPersona;

use think\facade\Log;

/**
 * 图文填坑 / 视频兜底结构化日志。
 * 一行 JSON，固定字段缺省写空/0，不省略 key。
 */
class ViralPublishFillLog
{
    public const EVENT_FILL_SUCCESS = 'fill_success';
    public const EVENT_FILL_FAIL = 'fill_fail';
    public const EVENT_FILL_SKIP = 'fill_skip';
    public const EVENT_FALLBACK_START = 'fallback_start';
    public const EVENT_FALLBACK_SKIP = 'fallback_skip';
    public const EVENT_FALLBACK_VIDEO = 'fallback_video';
    public const EVENT_FALLBACK_STATUS7 = 'fallback_status7';
    public const EVENT_FALLBACK_SETTLE = 'fallback_settle';
    public const EVENT_LOCK_SKIP = 'lock_skip';

    public const SOURCE_IMAGE_TEXT = 'image_text';
    public const SOURCE_VIDEO = 'video';

    public const SKIP_NOT_DUE = '未到点';
    public const SKIP_PAST_DUE = '已过点';
    public const SKIP_SETTLED = '已结案';
    public const SKIP_NO_SLOT = '无空坑';
    public const SKIP_NO_STOCK = '无库存';
    public const SKIP_PROCESSING = '处理中';
    public const SKIP_NO_PERSONA = '人设不存在';
    public const SKIP_NO_DEVICE = '设备不存在';
    public const SKIP_INVALID_PARAM = '参数无效';

    /** @var callable(string, array):void|null */
    private static $writer = null;

    /**
     * @return list<string>
     */
    public static function requiredKeys(): array
    {
        return [
            'event',
            'device_code',
            'task_id',
            'day',
            'persona_id',
            'publish_slots',
            'success_count',
            'shortage',
            'created',
            'failed',
            'skipped',
            'skip_reason',
            'fallback_source',
            'status7',
            'remark',
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
    public static function write(string $prefix, array $fields, string $level = 'create'): array
    {
        $payload = self::normalize($fields);
        $line = trim($prefix) . ' ' . json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
        );
        if (self::$writer !== null) {
            (self::$writer)($line, $payload);
            return $payload;
        }

        Log::channel('auto')->write($line, $level);

        return $payload;
    }

    /**
     * @param array<string, mixed> $fields
     * @return array<string, int|string>
     */
    public static function normalize(array $fields): array
    {
        $defaults = [
            'event' => '',
            'device_code' => '',
            'task_id' => 0,
            'day' => '',
            'persona_id' => 0,
            'publish_slots' => 0,
            'success_count' => 0,
            'shortage' => 0,
            'created' => 0,
            'failed' => 0,
            'skipped' => 0,
            'skip_reason' => '',
            'fallback_source' => '',
            'status7' => 0,
            'remark' => '',
        ];

        $payload = [];
        foreach ($defaults as $key => $default) {
            if (!array_key_exists($key, $fields)) {
                $payload[$key] = $default;
                continue;
            }
            $payload[$key] = is_int($default) ? (int)$fields[$key] : (string)$fields[$key];
        }

        return $payload;
    }
}

<?php

namespace app\common\service;

/**
 * 闪剪中台队列响应归一化。
 *
 * 中台提交接口和批量查询接口存在一层或两层 data 包装，这里统一兼容，
 * 避免业务逻辑把 waiting 响应误判为缺少 taskId 的提交失败。
 */
class ShanjianQueueService
{
    public const STATUS_WAITING = 'waiting';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_FAILED = 'failed';

    public static function normalizeItem(array $item): array
    {
        $data = self::unwrapSingleItem($item);
        $status = strtolower(trim((string)self::firstValue($data, [
            'queue_status', 'queueStatus', 'status',
        ], '')));
        if (!in_array($status, [self::STATUS_WAITING, self::STATUS_SUBMITTED, self::STATUS_FAILED], true)) {
            $status = '';
        }

        return [
            'source' => (string)self::firstValue($data, ['source', 'task_source', 'taskSource'], ''),
            'task_id' => (string)self::firstValue($data, [
                'task_id', 'taskId', 'station_task_id', 'stationTaskId',
                'local_task_id', 'localTaskId', 'client_task_id', 'clientTaskId',
            ], ''),
            'upstream_task_id' => (string)self::firstValue($data, [
                'shanjian_task_id', 'shanjianTaskId', 'upstream_task_id', 'upstreamTaskId', 'result_id', 'resultId',
            ], ''),
            'queue_status' => $status,
            'queue_position' => max(0, (int)self::firstValue($data, [
                'queue_position', 'queuePosition', 'position',
            ], 0)),
            'message' => (string)self::firstValue($data, [
                'message', 'error_message', 'errorMessage', 'reason',
            ], ''),
            'duration' => max(0, (float)self::firstValue($data, ['duration'], 0)),
        ];
    }

    public static function normalizeSubmission(array $response): array
    {
        $normalized = self::normalizeItem($response);
        $data = self::unwrapSingleItem($response);

        // 提交成功时 taskId 表示上游任务ID；本地 task_id 由请求侧持有。
        if ($normalized['upstream_task_id'] === '') {
            $normalized['upstream_task_id'] = (string)self::firstValue(
                $data,
                ['taskId', 'resultId', 'shanjianTaskId'],
                ''
            );
        }
        if ($normalized['queue_status'] === '' && $normalized['upstream_task_id'] !== '') {
            $normalized['queue_status'] = self::STATUS_SUBMITTED;
        }

        return $normalized;
    }

    public static function normalizeBatchResponse(array $response): array
    {
        $payload = $response;
        for ($i = 0; $i < 3; $i++) {
            if (isset($payload['items']) && is_array($payload['items'])) {
                $payload = $payload['items'];
                break;
            }
            if (isset($payload['list']) && is_array($payload['list'])) {
                $payload = $payload['list'];
                break;
            }
            if (isset($payload['data']) && is_array($payload['data'])) {
                $payload = $payload['data'];
                continue;
            }
            break;
        }

        if (!self::isList($payload)) {
            $mapped = [];
            foreach ($payload as $taskId => $item) {
                if (!is_array($item)) {
                    continue;
                }
                if (!isset($item['task_id']) && !isset($item['localTaskId'])) {
                    $item['task_id'] = (string)$taskId;
                }
                $mapped[] = $item;
            }
            $payload = $mapped;
        }

        return array_values(array_filter(array_map(
            static fn($item) => is_array($item) ? self::normalizeItem($item) : [],
            $payload
        ), static fn(array $item) => !empty($item['task_id']) && !empty($item['queue_status'])));
    }

    public static function statusText(string $status, int $position = 0): string
    {
        return match ($status) {
            self::STATUS_WAITING => $position > 0 ? "排队中，第{$position}位" : '排队中',
            self::STATUS_SUBMITTED => '已提交',
            self::STATUS_FAILED => '排队失败',
            default => '',
        };
    }

    private static function unwrapSingleItem(array $item): array
    {
        for ($i = 0; $i < 3; $i++) {
            if (!isset($item['data']) || !is_array($item['data']) || self::isList($item['data'])) {
                break;
            }
            $nested = $item['data'];
            $hasQueueFields = self::firstValue($item, [
                'queue_status', 'queueStatus', 'taskId', 'resultId', 'upstream_task_id',
            ], null) !== null;
            if ($hasQueueFields) {
                break;
            }
            $item = array_replace($item, $nested);
        }

        return $item;
    }

    private static function isList(array $value): bool
    {
        return $value === [] || array_keys($value) === range(0, count($value) - 1);
    }

    private static function firstValue(array $data, array $keys, $default = null)
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data) && $data[$key] !== null && $data[$key] !== '') {
                return $data[$key];
            }
        }
        return $default;
    }
}

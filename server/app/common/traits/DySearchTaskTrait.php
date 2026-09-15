<?php

declare(strict_types=1);

namespace app\common\traits;

use app\common\enum\DeviceEnum;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvDeviceTask;
use app\common\model\sv\SvDySearchTask;
use app\common\model\sv\SvDySearchTaskDevice;
use app\common\service\CallbackTokenService;
use app\common\service\sv\DySearchAccountNormalizer;
use app\common\workerman\rpa\WorkerEnum;
use Channel\Client as ChannelClient;
use think\cache\driver\Redis;
use think\facade\Log;

/**
 * 抖音获客：下发 / 暂停 / 恢复 / 取消 / 结束。
 * Logic 与调度器共用；不连库的组包走 buildSendContent。
 */
trait DySearchTaskTrait
{
    public static function dySearchSend(int $taskId): void
    {
        $task = SvDySearchTask::where('id', $taskId)->findOrEmpty();
        if ($task->isEmpty()) {
            throw new \Exception('获客任务不存在');
        }

        $binds = SvDySearchTaskDevice::where('task_id', $taskId)
            ->where('status', '<>', 3)
            ->select();
        if ($binds->isEmpty()) {
            self::dySearchLog('抖音获客任务' . $taskId . '没有未完成的设备绑定，跳过下发');
            return;
        }

        self::dySearchConnect();
        foreach ($binds as $bind) {
            $deviceTask = self::dySearchFindDeviceTask($taskId, (string)$bind->device_code);
            self::pushTaskContent($task, $bind, $deviceTask);
        }
    }

    public static function dySearchPause(int $taskId): void
    {
        $task = SvDySearchTask::where('id', $taskId)->findOrEmpty();
        if ($task->isEmpty()) {
            throw new \Exception('获客任务不存在');
        }

        $binds = SvDySearchTaskDevice::where('task_id', $taskId)
            ->where('status', 'in', [1, 2])
            ->select();
        if ($binds->isEmpty()) {
            return;
        }

        self::dySearchConnect();
        foreach ($binds as $bind) {
            self::dySearchPublishControl(
                (string)$bind->device_code,
                WorkerEnum::RPA_DY_SEARCH_TASK_PAUSE,
                [
                    'task_id' => $taskId,
                    'bind_id' => (int)$bind->id,
                    'deviceId' => (string)$bind->device_code,
                    'msg' => '任务暂停',
                ]
            );
        }
    }

    public static function dySearchRecovery(int $taskId): void
    {
        $task = SvDySearchTask::where('id', $taskId)->findOrEmpty();
        if ($task->isEmpty()) {
            throw new \Exception('获客任务不存在');
        }

        $binds = SvDySearchTaskDevice::where('task_id', $taskId)
            ->where('status', '<>', 3)
            ->select();
        if ($binds->isEmpty()) {
            return;
        }

        self::dySearchConnect();
        foreach ($binds as $bind) {
            self::dySearchPublishControl(
                (string)$bind->device_code,
                WorkerEnum::RPA_DY_SEARCH_TASK_RESUME,
                [
                    'task_id' => $taskId,
                    'bind_id' => (int)$bind->id,
                    'deviceId' => (string)$bind->device_code,
                    'msg' => '任务恢复',
                ]
            );
        }
    }

    public static function dySearchCancel(int $taskId, string $msg = '任务取消', string $deviceCode = ''): void
    {
        $task = SvDySearchTask::where('id', $taskId)->findOrEmpty();
        if ($task->isEmpty()) {
            throw new \Exception('获客任务不存在');
        }

        $query = SvDySearchTaskDevice::where('task_id', $taskId)
            ->where('status', '<>', 3);
        if ($deviceCode !== '') {
            $query->where('device_code', $deviceCode);
        }
        $binds = $query->select();
        if ($binds->isEmpty()) {
            self::dySearchLog('抖音获客任务' . $taskId . '没有未完成的设备绑定，跳过取消');
            return;
        }

        self::dySearchConnect();
        foreach ($binds as $bind) {
            self::dySearchPublishControl(
                (string)$bind->device_code,
                WorkerEnum::RPA_DY_SEARCH_TASK_CANCEL,
                [
                    'task_id' => $taskId,
                    'bind_id' => (int)$bind->id,
                    'deviceId' => (string)$bind->device_code,
                    'msg' => $msg,
                ]
            );
        }
    }

    public static function pushTaskContent(SvDySearchTask $task, SvDySearchTaskDevice $bind, SvDeviceTask $deviceTask): void
    {
        $account = SvAccount::where('device_code', $bind->device_code)
            ->where('type', DeviceEnum::ACCOUNT_TYPE_DY)
            ->value('account');

        $content = self::buildSendContent([
            'id' => (int)$bind->id,
            'task_id' => (int)$task->id,
            'device_task_id' => (int)$deviceTask->id,
            'device_code' => (string)$bind->device_code,
            'account' => (string)($account ?? ''),
            'search_mode' => (string)$task->search_mode,
            'keywords' => is_array($bind->keywords) ? $bind->keywords : [],
            'per_kw' => (int)$task->per_kw,
            'scan_config' => is_array($task->scan_config) ? $task->scan_config : [],
            'add_type' => (int)$task->add_type,
            'add_rule' => (string)$task->add_rule,
            'ocr_type' => (int)$task->ocr_type,
            'day_cap' => (int)$task->day_cap,
            'start_time' => (int)$deviceTask->start_time,
            'end_time' => (int)$deviceTask->end_time,
        ]);

        self::dySearchConnect();
        $payload = [
            'type' => WorkerEnum::RPA_DY_SEARCH_TASK_SEND,
            'appType' => DeviceEnum::ACCOUNT_TYPE_DY,
            'content' => json_encode($content, JSON_UNESCAPED_UNICODE),
            'deviceId' => (string)$bind->device_code,
            'appVersion' => DeviceEnum::APP_VERSION,
            'messageId' => 0,
        ];
        self::dySearchPublish((string)$bind->device_code, $payload);
        self::dySearchLog(
            'task_id=' . (int)$task->id . ' bind_id=' . (int)$bind->id . ' device=' . (string)$bind->device_code . ' device_task_id=' . (int)$deviceTask->id,
            '下发获客配置'
        );
        self::dySearchLog(self::dySearchMaskPayload($payload), '下发获客配置明细');
        self::dySearchSetRedis((string)$bind->device_code, '执行抖音获客');
    }

    /**
     * 组 1007 content。只读入参数组，不访问数据库。
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public static function buildSendContent(array $payload): array
    {
        $searchMode = (string)($payload['search_mode'] ?? 'keyword');
        $scan = $payload['scan_config'] ?? [];
        if (is_string($scan)) {
            $decoded = json_decode($scan, true);
            $scan = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($scan)) {
            $scan = [];
        }

        $keywords = $payload['keywords'] ?? [];
        if (is_string($keywords)) {
            $decoded = json_decode($keywords, true);
            $keywords = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($keywords)) {
            $keywords = [];
        }

        $start = (int)($payload['start_time'] ?? 0);
        $end = (int)($payload['end_time'] ?? 0);

        $scan['pre_filter'] = self::dySearchToBool($scan['pre_filter'] ?? false);
        $scan['fans_min'] = (int)($scan['fans_min'] ?? 0);
        $scan['works_min'] = (int)($scan['works_min'] ?? 0);
        $scan['active_days'] = (int)($scan['active_days'] ?? 0);
        $scan['only_biz'] = self::dySearchToBool($scan['only_biz'] ?? false);
        $scan['contact_phone'] = self::dySearchToBool($scan['contact_phone'] ?? false);
        $scan['phone_as_wechat'] = self::dySearchToBool($scan['phone_as_wechat'] ?? false);
        $scan['cover_count'] = (int)($scan['cover_count'] ?? 0);
        $scan['stitch'] = strtolower(trim((string)($scan['stitch'] ?? '')));
        $scan = DySearchAccountNormalizer::applyScanHardRules($scan, $searchMode);

        $content = [
            'id' => (int)($payload['id'] ?? $payload['bind_id'] ?? 0),
            'task_id' => (int)($payload['task_id'] ?? 0),
            'device_task_id' => (int)($payload['device_task_id'] ?? 0),
            'device_code' => (string)($payload['device_code'] ?? ''),
            'account' => (string)($payload['account'] ?? ''),
            'account_type' => DeviceEnum::ACCOUNT_TYPE_DY,
            'search_mode' => $searchMode,
            'keywords' => array_values($keywords),
            'per_kw' => (int)($payload['per_kw'] ?? 0),
            'pre_filter' => (bool)$scan['pre_filter'],
            'fans_min' => (int)$scan['fans_min'],
            'works_min' => (int)$scan['works_min'],
            'active_days' => (int)$scan['active_days'],
            'only_biz' => (bool)$scan['only_biz'],
            'scan_bio' => self::dySearchToBool($scan['scan_bio'] ?? false),
            'scan_dyid' => self::dySearchToBool($scan['scan_dyid'] ?? false),
            'scan_cover' => self::dySearchToBool($scan['scan_cover'] ?? false),
            'cover_count' => (int)$scan['cover_count'],
            'stitch' => (string)$scan['stitch'],
            'contact_phone' => (bool)$scan['contact_phone'],
            'contact_wechat' => self::dySearchToBool($scan['contact_wechat'] ?? false),
            'contact_qq' => self::dySearchToBool($scan['contact_qq'] ?? false),
            'phone_as_wechat' => (bool)($scan['phone_as_wechat'] ?? false),
            'exclude_default_id' => 1,
            'dedupe' => true,
            'skip_existing' => true,
            'miss_action' => (string)($scan['miss_action'] ?? ''),
            'miss_scripts' => is_array($scan['miss_scripts'] ?? null) ? array_values($scan['miss_scripts']) : [],
            'miss_once' => self::dySearchToBool($scan['miss_once'] ?? false),
            'add_type' => (int)($payload['add_type'] ?? 0),
            'add_rule' => (string)($payload['add_rule'] ?? ''),
            'ocr_type' => (int)($payload['ocr_type'] ?? 1),
            'day_cap' => (int)($payload['day_cap'] ?? 0),
            'start_time' => $start,
            'end_time' => $end,
            'time_interval' => (int)ceil(($end - $start) / 60),
            'status' => 0,
            'msg' => '抖音获客任务运行',
        ];

        return $content;
    }

    /**
     * 收口仍存活的搜寻任务：发 1011，父任务结束，绑定完成，日历任务中断。不软删。
     */
    public static function dySearchCloseAlive(int $taskId, string $msg = '任务已收口'): void
    {
        $task = SvDySearchTask::where('id', $taskId)->findOrEmpty();
        if ($task->isEmpty()) {
            throw new \Exception('获客任务不存在');
        }

        self::dySearchCancel($taskId, $msg);

        $now = time();
        SvDySearchTask::where('id', $taskId)->update([
            'status' => 4,
            'update_time' => $now,
        ]);
        SvDySearchTaskDevice::where('task_id', $taskId)
            ->where('status', '<>', 3)
            ->update(['status' => 3, 'update_time' => $now]);
        SvDeviceTask::where('sub_task_id', $taskId)
            ->where('task_type', 'in', [
                DeviceEnum::TASK_TYPE_DY_SEARCH,
                DeviceEnum::TASK_TYPE_DY_SEARCH_WECHAT,
            ])
            ->where('status', 'in', [
                DeviceEnum::TASK_STATUS_WAIT,
                DeviceEnum::TASK_STATUS_RUNNING,
            ])
            ->update([
                'status' => DeviceEnum::TASK_STATUS_INTERRUPTED,
                'remark' => $msg,
                'update_time' => $now,
            ]);
    }

    public static function dySearchMaybeFinishMainTask(int $taskId): void
    {
        $left = SvDySearchTaskDevice::where('task_id', $taskId)
            ->where('status', '<>', 3)
            ->count();
        if ($left > 0) {
            return;
        }
        $exists = SvDySearchTaskDevice::where('task_id', $taskId)->count();
        if ($exists < 1) {
            return;
        }
        SvDySearchTask::where('id', $taskId)->update([
            'status' => 3,
            'update_time' => time(),
        ]);
    }

    private static function dySearchFindDeviceTask(int $taskId, string $deviceCode): SvDeviceTask
    {
        $deviceTask = SvDeviceTask::where('sub_task_id', $taskId)
            ->where('device_code', $deviceCode)
            ->where('task_type', DeviceEnum::TASK_TYPE_DY_SEARCH)
            ->findOrEmpty();
        if (!$deviceTask->isEmpty()) {
            return $deviceTask;
        }
        $fallback = new SvDeviceTask();
        $fallback->id = 0;
        $fallback->device_code = $deviceCode;
        $fallback->start_time = 0;
        $fallback->end_time = 0;
        return $fallback;
    }

    /**
     * @param array<string, mixed> $content
     */
    private static function dySearchPublishControl(string $deviceCode, int $type, array $content): void
    {
        $payload = [
            'type' => $type,
            'appType' => DeviceEnum::ACCOUNT_TYPE_DY,
            'content' => json_encode($content, JSON_UNESCAPED_UNICODE),
            'deviceId' => $deviceCode,
            'appVersion' => DeviceEnum::APP_VERSION,
            'messageId' => 0,
        ];
        self::dySearchPublish($deviceCode, $payload);
        self::dySearchLog(self::dySearchMaskPayload($payload), '控制指令');
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function dySearchPublish(string $deviceCode, array $payload): void
    {
        $channel = 'device.' . $deviceCode . '.message';
        ChannelClient::publish($channel, [
            'data' => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ]);
    }

    private static function dySearchConnect(): void
    {
        ChannelClient::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
    }

    private static function dySearchSetRedis(string $deviceCode, string $msg): void
    {
        self::dySearchRedis()->set('xhs:device:' . $deviceCode . ':taskStatus', json_encode([
            'taskStatus' => 'standby',
            'taskType' => 'setDySearch',
            'msg' => $msg,
            'duration' => 0,
            'time' => date('Y-m-d H:i:s'),
            'scene' => 'dy_search',
        ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private static function dySearchMaskPayload(array $payload): array
    {
        $payload = CallbackTokenService::maskForLog($payload);
        if (!empty($payload['content']) && is_string($payload['content'])) {
            $decoded = json_decode($payload['content'], true);
            if (is_array($decoded)) {
                $payload['content'] = CallbackTokenService::maskForLog($decoded);
            }
        }
        return $payload;
    }

    /**
     * @param mixed $value
     */
    private static function dySearchToBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return ((int)$value) === 1;
        }
        $s = strtolower(trim((string)$value));
        return in_array($s, ['1', 'true', 'yes', 'on'], true);
    }

    private static function dySearchRedis(): Redis
    {
        return new Redis([
            'host' => env('redis.HOST', '127.0.0.1'),
            'port' => env('redis.PORT', 6379),
            'password' => env('redis.PASSWORD', '123456'),
            'select' => env('redis.WS_SELECT', 8),
            'timeout' => 0,
            'persistent' => true,
        ]);
    }

    /**
     * @param array<string, mixed>|string $content
     */
    private static function dySearchLog($content, string $scene = '抖音获客'): void
    {
        if (is_array($content)) {
            $content = (string)json_encode($content, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        Log::channel('device')->write($scene . "\n" . $content);
    }
}

<?php

namespace app\common\service\phoneAgent;

use app\common\model\phoneAgent\PhoneAgentEvent;
use app\common\model\sv\SvDevice;
use app\common\workerman\rpa\Support\ConnectionRepository;
use app\common\workerman\rpa\WorkerEnum;
use Channel\Client as ChannelClient;
use think\facade\Cache;
use think\facade\Log;

class PhoneAgentDispatchService
{
    public const APP_TYPE = 0;

    public static function isDeviceOnline(string $deviceCode): bool
    {
        if ($deviceCode === '') {
            return false;
        }

        try {
            $redis = Cache::store('redis');
            $redis->select((int)env('redis.WS_SELECT', 8));
            $repository = new ConnectionRepository($redis);
            if ($repository->isDeviceOnline($deviceCode)) {
                return true;
            }

            $status = $redis->get("xhs:device:{$deviceCode}:status");
            if ($status !== false && $status !== null && $status !== '') {
                return false;
            }
        } catch (\Throwable $e) {
            Log::channel('socket')->write('phone agent check redis online fail:' . $e->getMessage(), 'error');
        }

        return (bool)SvDevice::where('device_code', $deviceCode)->where('status', 1)->count();
    }

    public static function observe(string $deviceCode, int $taskDbId, int $turnNo): bool
    {
        return self::send($deviceCode, WorkerEnum::RPA_PHONE_AGENT_OBSERVE, [
            'task_id' => $taskDbId,
            'turn_no' => $turnNo,
            'need_screenshot' => true,
            'need_ocr' => true,
            'need_accessibility_tree' => true,
            'is_desktop'=> $turnNo === 1 ? 1 : 0, //回到桌面
        ], (string)$taskDbId);
    }

    public static function execute(
        string $deviceCode,
        int $taskDbId,
        int $turnNo,
        int $actionNo,
        string $actionType,
        array $params,
        int $timeout = 60,
        bool $publish = true
    ): array {
        $params = self::fillParamsWhenEmpty($actionType, $params);
        $content = [
            'task_id' => $taskDbId,
            'turn_no' => $turnNo,
            'action_no' => $actionNo,
            'action_type' => $actionType,
            'params' => $params,
            'coordinate_system' => 'normalized_1000',
            'timeout' => $timeout,
        ];

        $payload = self::buildPayload($deviceCode, WorkerEnum::RPA_PHONE_AGENT_EXEC, $content, (string)$taskDbId);
        $sent = $publish ? self::publish($deviceCode, $payload) : false;
        return [$sent, $payload];
    }

    private static function fillParamsWhenEmpty(string $actionType, array $params): array
    {
        if ($params !== []) {
            return $params;
        }

        $actionType = trim($actionType);
        if ($actionType === '') {
            return [];
        }

        return ['action' => $actionType];
    }

    public static function cancel(string $deviceCode, int $taskDbId, string $reason = 'user_cancel'): bool
    {
        return self::send($deviceCode, WorkerEnum::RPA_PHONE_AGENT_CANCEL, [
            'task_id' => $taskDbId,
            'reason' => $reason,
        ], (string)$taskDbId);
    }

    public static function send(string $deviceCode, int $type, array $content, ?string $messageId = null): bool
    {
        return self::publish($deviceCode, self::buildPayload($deviceCode, $type, $content, $messageId));
    }

    public static function buildPayload(string $deviceCode, int $type, array $content, ?string $messageId = null): array
    {
        return [
            'messageId' => $messageId ?: generate_unique_task_id(),
            'type' => $type,
            'appType' => self::APP_TYPE,
            'content' => json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'deviceId' => $deviceCode,
            'appVersion' => WorkerEnum::APP_VERSION,
        ];
    }

    private static function publish(string $deviceCode, array $payload): bool
    {
        try {
            ChannelClient::connect(
                env('WORKERMAN.CHANNEL_HOST', '127.0.0.1'),
                (int)env('WORKERMAN.CHANNEL_PROT', 2206)
            );
            ChannelClient::publish("device.{$deviceCode}.message", [
                'data' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
            return true;
        } catch (\Throwable $e) {
            Log::channel('socket')->write('phone agent publish fail:' . $e->getMessage(), 'error');
            return false;
        }
    }

    public static function pushWebEvent(int $userId, string $deviceCode, array $event): void
    {
        if ($userId <= 0) {
            return;
        }

        try {
            $redis = Cache::store('redis');
            $redis->select((int)env('redis.WS_SELECT', 8));
            $repository = new ConnectionRepository($redis);

            foreach (WorkerEnum::WS_SOURCES as $source) {
                $uid = $repository->getWebUserUid($source, $userId);
                if (!$uid) {
                    continue;
                }

                $workerId = $repository->getConnectionWorkerId($uid);
                if ($workerId === null) {
                    continue;
                }

                ChannelClient::connect(
                    env('WORKERMAN.CHANNEL_HOST', '127.0.0.1'),
                    (int)env('WORKERMAN.CHANNEL_PROT', 2206)
                );
                ChannelClient::publish("rpa.worker.{$workerId}.send", [
                    'uid' => $uid,
                    'content' => [
                        'appType' => self::APP_TYPE,
                        'messageId' => generate_unique_task_id(),
                        'type' => WorkerEnum::RPA_PHONE_AGENT_REPORT,
                        'content' => json_encode($event, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'deviceId' => $deviceCode,
                        'appVersion' => WorkerEnum::APP_VERSION,
                        'code' => WorkerEnum::SUCCESS_CODE,
                        'action' => 'send',
                    ],
                ]);
            }
        } catch (\Throwable $e) {
            Log::channel('socket')->write('phone agent push web fail:' . $e->getMessage(), 'error');
        }
    }

    public static function createEvent(
        string $taskId,
        string $deviceCode,
        string $eventType,
        array $eventData = []
    ): PhoneAgentEvent {
        return PhoneAgentEvent::create([
            'task_id' => $taskId,
            'device_code' => $deviceCode,
            'event_type' => $eventType,
            'event_data' => $eventData,
            'create_time' => time(),
            'update_time' => time(),
        ]);
    }
}

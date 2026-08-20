<?php

declare(strict_types=1);

namespace app\common\service\device;

use app\common\model\sv\SvDevice;
use app\common\model\sv\SvDeviceUsed;
use app\common\workerman\rpa\Support\ConnectionRepository;
use app\common\workerman\rpa\WorkerEnum;
use Channel\Client as ChannelClient;
use think\facade\Cache;
use think\facade\Log;

/**
 * RPA 设备 Channel 下发与业务解绑状态
 */
class RpaDeviceDispatchService
{
    public const APP_TYPE = 0;

    public const PENDING_TTL = 604800; // 7 天

    /** 下发 1212 后延迟踢线秒数（不依赖手机 ACK / API 上报） */
    public const FORCE_CLOSE_DELAY_SEC = 10;

    /**
     * 暂时关闭 RPA 解绑通知（1212 / ACK / 踢线 / 重连强制）。
     * 恢复时改为 true，并重启 Workerman。
     */
    public const ENABLE_RPA_DEVICE_UNBIND_NOTIFY = false;

    private static function wsRedis()
    {
        $redis = Cache::store('redis');
        $redis->select((int)env('redis.WS_SELECT', 8));
        return $redis;
    }

    private static function repository(): ConnectionRepository
    {
        return new ConnectionRepository(self::wsRedis());
    }

    private static function pendingKey(string $deviceCode): string
    {
        return 'xhs:device:' . trim($deviceCode) . ':pending_unbind';
    }

    private static function forceKey(string $deviceCode): string
    {
        return 'xhs:device:' . trim($deviceCode) . ':force_unbind';
    }

    private static function closeTokenKey(string $deviceCode): string
    {
        return 'xhs:device:' . trim($deviceCode) . ':unbind_close_token';
    }

    private static function traceKey(string $deviceCode): string
    {
        return 'xhs:device:' . trim($deviceCode) . ':unbind_trace';
    }

    private static function connectChannel(): void
    {
        ChannelClient::connect(
            env('WORKERMAN.CHANNEL_HOST', '127.0.0.1'),
            (int)env('WORKERMAN.CHANNEL_PROT', 2206)
        );
    }

    /**
     * 解绑链路统一结构化日志（channel=device, level=unbind）
     */
    public static function logUnbind(string $event, array $context = []): void
    {
        $payload = array_merge([
            'event' => $event,
            'msg' => (string)($context['msg'] ?? ''),
            'result' => (string)($context['result'] ?? ''),
        ], $context);

        try {
            Log::channel('device')->write(
                json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'unbind'
            );
        } catch (\Throwable $e) {
            // 日志失败不影响业务
        }
    }

    public static function newTraceId(): string
    {
        return str_replace('.', '', uniqid('ub_', true));
    }

    public static function rememberTraceId(string $deviceCode, string $traceId, int $ttl = 0): void
    {
        $deviceCode = trim($deviceCode);
        if ($deviceCode === '' || $traceId === '') {
            return;
        }
        try {
            $ttl = $ttl > 0 ? $ttl : (self::FORCE_CLOSE_DELAY_SEC + 120);
            $redis = self::wsRedis();
            $redis->set(self::traceKey($deviceCode), $traceId);
            $redis->expire(self::traceKey($deviceCode), $ttl);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public static function getTraceId(string $deviceCode): string
    {
        $deviceCode = trim($deviceCode);
        if ($deviceCode === '') {
            return '';
        }
        try {
            $v = self::wsRedis()->get(self::traceKey($deviceCode));
            return ($v === false || $v === null) ? '' : (string)$v;
        } catch (\Throwable $e) {
            return '';
        }
    }

    public static function isOnline(string $deviceCode): bool
    {
        $deviceCode = trim($deviceCode);
        if ($deviceCode === '') {
            return false;
        }

        try {
            return self::repository()->isDeviceOnline($deviceCode);
        } catch (\Throwable $e) {
            self::logUnbind('unbind_error', [
                'device_code' => $deviceCode,
                'msg' => '设备在线状态检查失败',
                'result' => 'fail',
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public static function markPendingUnbind(string $deviceCode, array $meta = []): void
    {
        $deviceCode = trim($deviceCode);
        if ($deviceCode === '') {
            return;
        }

        $traceId = (string)($meta['trace_id'] ?? '');
        try {
            $payload = json_encode([
                'reason' => (string)($meta['reason'] ?? ''),
                'user_id' => (int)($meta['user_id'] ?? 0),
                'ts' => (int)($meta['ts'] ?? time()),
                'trace_id' => $traceId,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $redis = self::wsRedis();
            $redis->set(self::pendingKey($deviceCode), $payload);
            $redis->expire(self::pendingKey($deviceCode), self::PENDING_TTL);
            if ($traceId !== '') {
                self::rememberTraceId($deviceCode, $traceId, self::PENDING_TTL);
            }
            self::markForceUnbind($deviceCode, self::PENDING_TTL);
            self::logUnbind('unbind_pending_set', [
                'trace_id' => $traceId,
                'device_code' => $deviceCode,
                'reason' => (string)($meta['reason'] ?? ''),
                'user_id' => (int)($meta['user_id'] ?? 0),
                'msg' => '已写入待解绑标记',
                'result' => 'ok',
            ]);
        } catch (\Throwable $e) {
            self::logUnbind('unbind_error', [
                'trace_id' => $traceId,
                'device_code' => $deviceCode,
                'msg' => '写入待解绑标记失败',
                'result' => 'fail',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 强制解绑标记（API 清 pending 后仍保留，用于停用重连补偿；scanOld 不会写入）
     */
    public static function markForceUnbind(string $deviceCode, int $ttl = 0): void
    {
        $deviceCode = trim($deviceCode);
        if ($deviceCode === '') {
            return;
        }
        try {
            $ttl = $ttl > 0 ? $ttl : self::PENDING_TTL;
            $redis = self::wsRedis();
            $redis->set(self::forceKey($deviceCode), '1');
            $redis->expire(self::forceKey($deviceCode), $ttl);
        } catch (\Throwable $e) {
            self::logUnbind('unbind_error', [
                'device_code' => $deviceCode,
                'msg' => '写入强制解绑标记失败',
                'result' => 'fail',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public static function hasForceUnbind(string $deviceCode): bool
    {
        $deviceCode = trim($deviceCode);
        if ($deviceCode === '') {
            return false;
        }
        try {
            $value = self::wsRedis()->get(self::forceKey($deviceCode));
            return $value !== false && $value !== null && $value !== '';
        } catch (\Throwable $e) {
            self::logUnbind('unbind_error', [
                'device_code' => $deviceCode,
                'msg' => '读取强制解绑标记失败',
                'result' => 'fail',
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public static function clearForceUnbind(string $deviceCode): void
    {
        $deviceCode = trim($deviceCode);
        if ($deviceCode === '') {
            return;
        }
        try {
            self::wsRedis()->del(self::forceKey($deviceCode));
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /**
     * 当前是否处于解绑流程中（pending / force / 延迟踢线 token）
     */
    public static function isInUnbindFlow(string $deviceCode): bool
    {
        return self::hasPendingUnbind($deviceCode)
            || self::hasForceUnbind($deviceCode)
            || self::getUnbindCloseToken($deviceCode) !== null;
    }

    public static function hasPendingUnbind(string $deviceCode): bool
    {
        $deviceCode = trim($deviceCode);
        if ($deviceCode === '') {
            return false;
        }

        try {
            $value = self::wsRedis()->get(self::pendingKey($deviceCode));
            return $value !== false && $value !== null && $value !== '';
        } catch (\Throwable $e) {
            self::logUnbind('unbind_error', [
                'device_code' => $deviceCode,
                'msg' => '读取待解绑标记失败',
                'result' => 'fail',
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * 仅清除 pending（Claw 解绑 API 上报可用；不取消延迟踢线）
     */
    public static function clearPendingUnbind(string $deviceCode): void
    {
        $deviceCode = trim($deviceCode);
        if ($deviceCode === '') {
            return;
        }

        try {
            self::wsRedis()->del(self::pendingKey($deviceCode));
        } catch (\Throwable $e) {
            self::logUnbind('unbind_error', [
                'device_code' => $deviceCode,
                'msg' => '清除待解绑标记失败',
                'result' => 'fail',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 取消延迟踢线（重绑成功时调用）
     */
    public static function cancelDelayedForceClose(string $deviceCode, array $context = []): void
    {
        $deviceCode = trim($deviceCode);
        if ($deviceCode === '') {
            return;
        }

        $traceId = (string)($context['trace_id'] ?? self::getTraceId($deviceCode));
        try {
            self::wsRedis()->del(self::closeTokenKey($deviceCode));
            if (empty($context['silent'])) {
                self::logUnbind('unbind_close_cancel', [
                    'trace_id' => $traceId,
                    'device_code' => $deviceCode,
                    'msg' => (string)($context['msg'] ?? '已取消延迟踢线标记'),
                    'result' => 'ok',
                    'reason' => (string)($context['reason'] ?? ''),
                ]);
            }
        } catch (\Throwable $e) {
            self::logUnbind('unbind_error', [
                'trace_id' => $traceId,
                'device_code' => $deviceCode,
                'msg' => '取消延迟踢线标记失败',
                'result' => 'fail',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 重绑成功后清理 pending + force + 延迟踢线 token
     */
    public static function clearUnbindState(string $deviceCode): void
    {
        $deviceCode = trim($deviceCode);
        if ($deviceCode === '') {
            return;
        }

        if (!self::ENABLE_RPA_DEVICE_UNBIND_NOTIFY) {
            self::logUnbind('unbind_disabled', [
                'device_code' => $deviceCode,
                'msg' => 'RPA 解绑通知已暂时关闭，跳过清理解绑状态',
                'result' => 'skip',
            ]);
            return;
        }

        $traceId = self::getTraceId($deviceCode);
        self::clearPendingUnbind($deviceCode);
        self::clearForceUnbind($deviceCode);
        self::cancelDelayedForceClose($deviceCode, [
            'trace_id' => $traceId,
            'msg' => '重绑成功，已清理解绑状态并取消延迟踢线',
            'reason' => 'rebind',
        ]);
        try {
            self::wsRedis()->del(self::traceKey($deviceCode));
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public static function getUnbindCloseToken(string $deviceCode): ?string
    {
        $deviceCode = trim($deviceCode);
        if ($deviceCode === '') {
            return null;
        }

        try {
            $token = self::wsRedis()->get(self::closeTokenKey($deviceCode));
            if ($token === false || $token === null || $token === '') {
                return null;
            }
            return (string)$token;
        } catch (\Throwable $e) {
            self::logUnbind('unbind_error', [
                'device_code' => $deviceCode,
                'msg' => '读取延迟踢线 token 失败',
                'result' => 'fail',
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public static function buildPayload(string $deviceCode, int $type, array $content, ?int $messageId = null): array
    {
        return [
            'messageId' => $messageId ?? 0,
            'type' => $type,
            'appType' => self::APP_TYPE,
            'content' => json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'deviceId' => trim($deviceCode),
            'appVersion' => WorkerEnum::APP_VERSION,
            'code' => WorkerEnum::SUCCESS_CODE,
        ];
    }

    public static function publish(string $deviceCode, array $payload): bool
    {
        $deviceCode = trim($deviceCode);
        if ($deviceCode === '') {
            return false;
        }

        try {
            self::connectChannel();
            ChannelClient::publish("device.{$deviceCode}.message", [
                'data' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
            return true;
        } catch (\Throwable $e) {
            self::logUnbind('unbind_error', [
                'device_code' => $deviceCode,
                'msg' => '设备消息下发失败',
                'result' => 'fail',
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * 下发 602 获取平台账号（reply 形态，与 UserHandler 一致）
     */
    public static function notifyGetUserInfo(string $deviceCode, int $appType): bool
    {
        $deviceCode = trim($deviceCode);
        if ($deviceCode === '') {
            return false;
        }

        $payload = [
            'messageId' => 0,
            'deviceId' => $deviceCode,
            'type' => WorkerEnum::TO_RPA_USER_INFO,
            'appVersion' => WorkerEnum::APP_VERSION,
            'appType' => $appType,
            'code' => WorkerEnum::SUCCESS_CODE,
            'reply' => [
                'type' => WorkerEnum::TO_RPA_USER_INFO,
                'appType' => $appType,
                'msg' => sprintf('获取设备%s用户信息', WorkerEnum::getAccountTypeDesc($appType)),
                'deviceId' => $deviceCode,
            ],
        ];

        $sent = self::publish($deviceCode, $payload);
        try {
            Log::channel('device')->write(
                json_encode([
                    'event' => 'notify_get_user_info',
                    'device_code' => $deviceCode,
                    'app_type' => $appType,
                    'msg' => $sent ? '已下发获取账号指令602' : '下发获取账号指令602失败',
                    'result' => $sent ? 'ok' : 'fail',
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'account'
            );
        } catch (\Throwable $e) {
            // 日志失败不影响业务
        }

        return $sent;
    }

    public static function notifyUnbind(string $deviceCode, string $reason, array $extra = []): bool
    {
        if (!self::ENABLE_RPA_DEVICE_UNBIND_NOTIFY) {
            self::logUnbind('unbind_disabled', [
                'device_code' => trim($deviceCode),
                'reason' => $reason,
                'msg' => 'RPA 解绑通知已暂时关闭，跳过 1212 下发',
                'result' => 'skip',
            ]);
            return false;
        }

        $deviceCode = trim($deviceCode);
        if ($deviceCode === '') {
            return false;
        }

        $traceId = (string)($extra['trace_id'] ?? '');
        $content = [
            'reason' => $reason,
            'user_id' => (int)($extra['user_id'] ?? 0),
            'ts' => (int)($extra['ts'] ?? time()),
        ];

        $payload = self::buildPayload($deviceCode, WorkerEnum::TO_RPA_DEVICE_UNBIND, $content);
        $sent = self::publish($deviceCode, $payload);
        self::logUnbind('unbind_notify_1212', [
            'trace_id' => $traceId,
            'device_code' => $deviceCode,
            'reason' => $reason,
            'user_id' => (int)($extra['user_id'] ?? 0),
            'msg' => $sent ? '已下发设备解绑指令 1212' : '下发设备解绑指令 1212 失败',
            'result' => $sent ? 'ok' : 'fail',
            'payload' => [
                'type' => $payload['type'] ?? null,
                'messageId' => $payload['messageId'] ?? null,
                'deviceId' => $payload['deviceId'] ?? null,
                'content' => $payload['content'] ?? null,
            ],
        ]);

        return $sent;
    }

    public static function getLiveDeviceUid(string $deviceCode): ?string
    {
        $deviceCode = trim($deviceCode);
        if ($deviceCode === '') {
            return null;
        }
        try {
            return self::repository()->getDeviceUid($deviceCode);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function forceClose(string $deviceCode, string $reason = '设备已解绑', ?string $fallbackUid = null): bool
    {
        $deviceCode = trim($deviceCode);
        if ($deviceCode === '') {
            return false;
        }

        $traceId = self::getTraceId($deviceCode);
        try {
            $repository = self::repository();
            $uid = $repository->getDeviceUid($deviceCode);
            if ($uid === null || $uid === '') {
                $uid = ($fallbackUid !== null && $fallbackUid !== '') ? $fallbackUid : null;
            }
            if ($uid === null || $uid === '') {
                self::logUnbind('unbind_close_skipped', [
                    'trace_id' => $traceId,
                    'device_code' => $deviceCode,
                    'msg' => '踢线跳过：当前无设备连接',
                    'result' => 'skip',
                    'reason' => $reason,
                ]);
                return false;
            }

            $workerId = $repository->getConnectionWorkerId($uid);
            if ($workerId === null) {
                self::logUnbind('unbind_error', [
                    'trace_id' => $traceId,
                    'device_code' => $deviceCode,
                    'uid' => $uid,
                    'msg' => '踢线失败：未找到设备连接 worker',
                    'result' => 'fail',
                ]);
                return false;
            }

            self::connectChannel();
            ChannelClient::publish("rpa.worker.{$workerId}.send", [
                'action' => 'closeConnection',
                'uid' => $uid,
                'reason' => $reason,
            ]);

            self::logUnbind('unbind_close_executed', [
                'trace_id' => $traceId,
                'device_code' => $deviceCode,
                'uid' => $uid,
                'worker_id' => $workerId,
                'msg' => '已请求踢掉设备当前连接',
                'result' => 'ok',
                'reason' => $reason,
            ]);
            return true;
        } catch (\Throwable $e) {
            self::logUnbind('unbind_error', [
                'trace_id' => $traceId,
                'device_code' => $deviceCode,
                'msg' => '踢掉设备连接失败',
                'result' => 'fail',
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * 调度延迟踢线（默认 10 秒，不依赖手机 ACK / API 上报）
     */
    public static function scheduleForceClose(
        string $deviceCode,
        int $delaySec = self::FORCE_CLOSE_DELAY_SEC,
        string $reason = '设备已解绑超时未断开',
        ?string $uid = null,
        ?int $workerId = null,
        array $extra = []
    ): bool {
        if (!self::ENABLE_RPA_DEVICE_UNBIND_NOTIFY) {
            self::logUnbind('unbind_disabled', [
                'device_code' => trim($deviceCode),
                'msg' => 'RPA 解绑通知已暂时关闭，跳过延迟踢线调度',
                'result' => 'skip',
                'reason' => $reason,
            ]);
            return false;
        }

        $deviceCode = trim($deviceCode);
        if ($deviceCode === '') {
            return false;
        }

        if ($delaySec <= 0) {
            $delaySec = self::FORCE_CLOSE_DELAY_SEC;
        }

        $traceId = (string)($extra['trace_id'] ?? self::getTraceId($deviceCode));
        $online = null;

        try {
            $repository = self::repository();
            if ($uid === null || $uid === '') {
                $online = self::isOnline($deviceCode);
                if (!$online) {
                    self::logUnbind('unbind_schedule_close', [
                        'trace_id' => $traceId,
                        'device_code' => $deviceCode,
                        'online' => false,
                        'delay_sec' => $delaySec,
                        'msg' => '设备离线，跳过延迟踢线调度',
                        'result' => 'skip',
                        'reason' => $reason,
                    ]);
                    return false;
                }
                $uid = $repository->getDeviceUid($deviceCode);
            } else {
                $online = true;
            }
            if ($uid === null || $uid === '') {
                self::logUnbind('unbind_schedule_close', [
                    'trace_id' => $traceId,
                    'device_code' => $deviceCode,
                    'online' => $online,
                    'delay_sec' => $delaySec,
                    'msg' => '未找到设备 uid，跳过延迟踢线调度',
                    'result' => 'skip',
                    'reason' => $reason,
                ]);
                return false;
            }

            if ($workerId === null) {
                $workerId = $repository->getConnectionWorkerId($uid);
            }
            if ($workerId === null) {
                self::logUnbind('unbind_schedule_close', [
                    'trace_id' => $traceId,
                    'device_code' => $deviceCode,
                    'uid' => $uid,
                    'online' => $online,
                    'delay_sec' => $delaySec,
                    'msg' => '延迟踢线调度失败：未找到 worker',
                    'result' => 'fail',
                    'reason' => $reason,
                ]);
                return false;
            }

            $token = bin2hex(random_bytes(8));
            $redis = self::wsRedis();
            $redis->set(self::closeTokenKey($deviceCode), $token);
            $redis->expire(self::closeTokenKey($deviceCode), $delaySec + 30);
            if ($traceId !== '') {
                self::rememberTraceId($deviceCode, $traceId, $delaySec + 120);
            }

            self::connectChannel();
            ChannelClient::publish("rpa.worker.{$workerId}.send", [
                'action' => 'scheduleForceClose',
                'uid' => $uid,
                'deviceId' => $deviceCode,
                'delaySec' => $delaySec,
                'token' => $token,
                'reason' => $reason,
                'trace_id' => $traceId,
            ]);

            self::logUnbind('unbind_schedule_close', [
                'trace_id' => $traceId,
                'device_code' => $deviceCode,
                'uid' => $uid,
                'worker_id' => $workerId,
                'online' => $online,
                'delay_sec' => $delaySec,
                'token' => substr($token, 0, 8),
                'msg' => '已调度延迟踢线',
                'result' => 'ok',
                'reason' => $reason,
            ]);

            return true;
        } catch (\Throwable $e) {
            self::logUnbind('unbind_schedule_close', [
                'trace_id' => $traceId,
                'device_code' => $deviceCode,
                'online' => $online,
                'delay_sec' => $delaySec,
                'msg' => '调度延迟踢线失败',
                'result' => 'fail',
                'error' => $e->getMessage(),
                'reason' => $reason,
            ]);
            return false;
        }
    }

    /**
     * 服务端业务解绑完成后的通知（事务外调用，失败不影响 API）
     */
    public static function afterServerUnbind(string $deviceCode, string $reason, array $extra = []): void
    {
        $deviceCode = trim($deviceCode);
        if ($deviceCode === '') {
            return;
        }

        if (!self::ENABLE_RPA_DEVICE_UNBIND_NOTIFY) {
            self::logUnbind('unbind_disabled', [
                'device_code' => $deviceCode,
                'reason' => $reason,
                'user_id' => (int)($extra['user_id'] ?? 0),
                'msg' => 'RPA 解绑通知已暂时关闭，跳过 1212/踢线/标记写入',
                'result' => 'skip',
            ]);
            return;
        }

        $traceId = self::newTraceId();
        $userId = (int)($extra['user_id'] ?? 0);
        $online = self::isOnline($deviceCode);

        try {
            self::logUnbind('unbind_start', [
                'trace_id' => $traceId,
                'device_code' => $deviceCode,
                'reason' => $reason,
                'user_id' => $userId,
                'online' => $online,
                'msg' => '业务解绑后通知开始',
                'result' => 'ok',
            ]);

            $meta = [
                'reason' => $reason,
                'user_id' => $userId,
                'ts' => time(),
                'trace_id' => $traceId,
            ];
            self::rememberTraceId($deviceCode, $traceId);
            self::markPendingUnbind($deviceCode, $meta);
            self::notifyUnbind($deviceCode, $reason, $meta);
            self::scheduleForceClose(
                $deviceCode,
                self::FORCE_CLOSE_DELAY_SEC,
                '设备已解绑超时未断开',
                null,
                null,
                ['trace_id' => $traceId]
            );
        } catch (\Throwable $e) {
            self::logUnbind('unbind_error', [
                'trace_id' => $traceId,
                'device_code' => $deviceCode,
                'reason' => $reason,
                'user_id' => $userId,
                'msg' => '业务解绑后通知失败',
                'result' => 'fail',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 设备重连上报时是否应强制解绑清码
     *
     * 规则：
     * - 设备库无记录：强制（remove 后）
     * - 当前归属用户 is_used=0 且仍有 force/pending：强制（停用补偿；API 清 pending 后靠 force）
     * - 仅有 pending 但已重绑(is_used=1)：清脏标记并放行
     * - scanOld 等「绑定未启用」(is_used=0 且无 force/pending)：不强制，避免换绑误发 1212
     */
    public static function shouldForceUnbind(string $deviceCode): bool
    {
        if (!self::ENABLE_RPA_DEVICE_UNBIND_NOTIFY) {
            return false;
        }

        $deviceCode = trim($deviceCode);
        if ($deviceCode === '') {
            return false;
        }

        try {
            $device = SvDevice::where('device_code', $deviceCode)->findOrEmpty();
            if ($device->isEmpty()) {
                return true;
            }

            $used = SvDeviceUsed::where('device_code', $deviceCode)
                ->where('user_id', (int)$device->user_id)
                ->order('id', 'desc')
                ->findOrEmpty();

            if (!$used->isEmpty() && (int)$used->is_used === 0) {
                // 停用写入 force；scanOld 不会写 force
                return self::hasForceUnbind($deviceCode) || self::hasPendingUnbind($deviceCode);
            }

            if (self::hasPendingUnbind($deviceCode) || self::hasForceUnbind($deviceCode)) {
                if ($used->isEmpty() || (int)$used->is_used === 1) {
                    self::clearUnbindState($deviceCode);
                    return false;
                }
                return true;
            }

            return false;
        } catch (\Throwable $e) {
            self::logUnbind('unbind_error', [
                'device_code' => $deviceCode,
                'msg' => '判断强制解绑失败',
                'result' => 'fail',
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}

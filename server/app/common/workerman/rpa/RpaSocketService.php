<?php

declare(strict_types=1);

namespace app\common\workerman\rpa;

use app\common\model\sv\SvAccount;
use app\common\model\sv\SvDevice;
use app\common\workerman\rpa\Routing\HandlerRegistry;
use app\common\workerman\rpa\Support\ChannelBus;
use app\common\workerman\rpa\Support\ConnectionRepository;
use app\common\workerman\rpa\Support\DeviceIndex;
use app\common\workerman\rpa\Validator\MessageValidator;
use app\common\workerman\rpa\Tool\ToolUtil;
use app\common\workerman\rpa\Service\DeviceUnbindService;
use think\cache\driver\Redis;
use think\facade\Log;
use Workerman\Connection\TcpConnection;
use Workerman\Timer;
use Workerman\Worker;


class RpaSocketService
{
    protected Worker $worker;
    protected int $HEARTBEAT_TIME = 30;
    protected ?Redis $redis = null;
    protected bool $isWriteLog = true;
    public array $uidConnections = [];

    private HandlerRegistry $registry;
    private ConnectionRepository $repository;
    private ChannelBus $channelBus;
    private MessageValidator $messageValidator;
    private ToolUtil $toolUtil;
    private DeviceUnbindService $deviceUnbindService;

    public function __construct(Worker $object)
    {
        $this->worker = $object;

        date_default_timezone_set('PRC');
        ini_set('memory_limit', '1024M');
        set_time_limit(0);

        $this->initServices();
        $this->registry = new HandlerRegistry();
    }

    /**
     * 初始化服务组件
     * @param bool $force 是否强制重新连接Redis
     */
    private function initServices(bool $force = false): void
    {
        $this->_connRedis($force);
        $this->repository = new ConnectionRepository($this->redis);
        $this->channelBus = new ChannelBus();
        $this->messageValidator = new MessageValidator($this, $this->repository);
        $this->toolUtil = new ToolUtil();
        $this->deviceUnbindService = new DeviceUnbindService(
            $this->repository,
            function (array $content): void {
                $this->sendWeb($content);
            },
        );
    }

    public function onConnect(TcpConnection $connection): void
    {
        try {
            $connection->maxSendBufferSize = 1024 * 1024 * 10;
            $connection->maxPackageSize = 1024 * 1024 * 10;
            $connection->lastHeartbeat = time();
            $connection->connectedAt = time();
            $this->setLog([
                '阶段' => 'tcp_accept',
                'ip' => $connection->getRemoteIp(),
                'port' => $connection->getRemotePort(),
                'workerId' => (int)($this->worker->id ?? 0),
                'time' => date('Y-m-d H:i:s'),
            ], 'info');

            $connection->onWebSocketConnect = function (TcpConnection $connection, $http_header): void {
                $this->setLog('header:' . $http_header);
                if (isset($connection->uid)) {
                    return;
                }

                $connection->uid = 'xhs_' . generate_unique_task_id();
                $connection->deviceid = '';
                $connection->apptype = '';
                $connection->appversion = WorkerEnum::APP_VERSION;
                $connection->messageid = 0;
                $connection->userid = 0;
                $connection->messageCount = 0;
                $connection->clientType = '';
                $connection->initial = 0;
                $connection->name = '';
                $connection->timerId = '';
                $connection->crontabId = '';
                $connection->testCrontabId = '';
                $connection->isMsgRunning = 0;
                $connection->lastHeartbeat = time();
                $connection->handshakeAt = time();
                $connection->workerId = (int)($this->worker->id ?? 0);

                $this->worker->uidConnections[$connection->uid] = $connection;
                try {
                    $this->repository->bindConnection($connection->uid, (int)$this->worker->id);
                } catch (\Throwable $e) {
                    $this->setLog([
                        '阶段' => 'ws_handshake',
                        'msg' => 'Redis不可用，握手绑定失败',
                        'uid' => $connection->uid,
                        'error' => $e->getMessage(),
                        'workerId' => (int)($this->worker->id ?? 0),
                        'time' => date('Y-m-d H:i:s'),
                    ], 'error');
                    unset($this->worker->uidConnections[$connection->uid]);
                    $this->closeWithReason($connection, 'Redis不可用，握手绑定失败');
                    return;
                }

                $this->setLog([
                    '阶段' => 'ws_handshake',
                    'uid' => $connection->uid,
                    'ip' => $connection->getRemoteIp(),
                    'port' => $connection->getRemotePort(),
                    'workerId' => (int)($this->worker->id ?? 0),
                    'time' => date('Y-m-d H:i:s'),
                ], 'info');
                $this->setLog('新连接:' . $connection->uid);
            };
        } catch (\Throwable $e) {
            $this->setLog('onConnect 异常:' . $e, 'error');
        }
    }

    public function onMessage(TcpConnection $connection, string $data): void
    {
        $connection->lastHeartbeat = time();
        if ($this->shouldWriteIncomingInfoLog($data)) {
            $this->setLog('消息:' . $this->toolUtil->replaceImageInfo($data));
        }

        $uid = $connection->uid ?? '';
        $message = null;
        try {
            $message = json_decode($data, true);
            [$type, $payload] = $this->messageValidator->validate($connection, $message);
            if ($type === 'continue') {
                return;
            }
            if ($type === false) {
                return;
            }
            if ($uid === '') {
                // 走框架 close → onClose，避免业务直接调用 onClose 导致重复清理
                $this->setLog([
                    '阶段' => 'missing_uid',
                    'msg' => '校验后缺少uid',
                    'closeReason' => '校验后缺少uid',
                    'ip' => $connection->getRemoteIp(),
                    'port' => $connection->getRemotePort(),
                    'workerId' => (int)($this->worker->id ?? 0),
                    'time' => date('Y-m-d H:i:s'),
                ], 'error');
                $this->closeWithReason($connection, '校验后缺少uid');
                return;
            }

            $this->registry->resolve($type, $this)->handle($connection, $uid, $payload);
            $this->refreshConnectionOwner($connection);
        } catch (\Throwable $e) {
            $this->setLog([
                'msg' => 'onMessage 异常',
                'uid' => $uid,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ], 'error');
            $message = is_array($message) ? $message : [];
            $message['code'] = $e->getCode();
            $message['reply'] = $e->getMessage();
            $this->sendError($uid, $message);
        }
    }

    public function onClose(TcpConnection $connection): bool
    {
        try {
            // 框架回调与业务误调用都可能进入此处，避免重复解绑
            if (!empty($connection->closeHandled)) {
                $this->setLog([
                    'msg' => '跳过重复的onClose',
                    'uid' => $connection->uid ?? '未知',
                    'deviceid' => $connection->deviceid ?? '',
                    'reason' => $connection->closeReason ?? '未知',
                ], 'channel');
                return true;
            }
            $connection->closeHandled = true;

            $uid = $connection->uid ?? '未知';
            $deviceId = trim((string)($connection->deviceid ?? ''));
            $userId = (int)($connection->userid ?? 0);
            $sourceType = (string)($connection->sourceType ?? WorkerEnum::WS_SOURCE_PC_TYPE);
            $activeDeviceUid = $deviceId !== '' ? $this->repository->getDeviceUid($deviceId) : null;
            $closeReason = (string)($connection->closeReason ?? '客户端断开或原因未知');

            if ($deviceId !== '' && $activeDeviceUid !== null && $activeDeviceUid !== $uid) {
                $this->setLog([
                    'msg' => '旧连接关闭，设备已被新连接接管',
                    'deviceId' => $deviceId,
                    'closing_uid' => $uid,
                    'active_uid' => $activeDeviceUid,
                    'reason' => $closeReason,
                ], 'info');
                if (!isset($this->worker->uidConnections[$activeDeviceUid])) {
                    $this->channelBus->unsubscribe("device.{$deviceId}.message");
                } else {
                    $this->setLog([
                        'msg' => 'skip unsubscribe because replacement is in current worker',
                        'deviceId' => $deviceId,
                        'closing_uid' => $uid,
                        'active_uid' => $activeDeviceUid,
                    ], 'channel');
                }
                if ($uid !== '未知') {
                    $this->repository->forgetConnection($uid);
                    unset($this->worker->uidConnections[$uid]);
                }
                unset($connection->uid, $connection->lastHeartbeat, $connection->deviceid, $connection->closeReason);
                return false;
            }

            $this->setLog([
                'info' => '连接关闭',
                'ip' => $connection->getRemoteIp(),
                'uid' => $uid,
                'name' => $connection->name ?? '',
                'reason' => $closeReason,
                'status' => $connection->getStatus(),
                'port' => $connection->getRemotePort(),
                'remote_address' => $connection->getRemoteAddress(),
                'client_type' => $connection->clientType ?? '',
                'deviceId' => $deviceId,
                'time' => date('Y-m-d H:i:s'),
            ], 'info');

            if (($connection->clientType ?? '') === WorkerEnum::WS_DEVICE_TYPE && $deviceId !== '') {
                if ($activeDeviceUid === null || $activeDeviceUid === $uid) {
                    $this->channelBus->unsubscribe("device.{$deviceId}.message");
                    if (isset($this->worker->devices)) {
                        unset($this->worker->devices[$deviceId]);
                    }
                } else {
                    $this->setLog([
                        'msg' => 'skip unsubscribe for replaced device connection',
                        'deviceId' => $deviceId,
                        'closing_uid' => $uid,
                        'active_uid' => $activeDeviceUid,
                    ], 'channel');
                }
            }
            $this->setLog('连接关闭:' . $uid . ', reason:' . $closeReason, 'channel');

            if ($this->canUpdateDeviceOffline($deviceId, $uid === '未知' ? '' : $uid)) {
                $this->updateDeviceOfflineStatus($deviceId);
            }
            if (isset($connection->uid)) {
                $this->worker->uidConnections = $this->deviceUnbindService->execute($uid, $deviceId, $userId, $sourceType, $this->worker->uidConnections ?? []);
            }
            unset($connection->uid, $connection->lastHeartbeat, $connection->deviceid, $connection->closeReason);
        } catch (\Throwable $e) {
            $this->setLog('onClose 异常:' . $e->getMessage(), 'error');
        }

        return true;
    }

    public function onError(TcpConnection $connection, int $code, string $msg): void
    {
        try {
            // onError 在「对端已关 / 连出失败」时会紧接 onClose；缓冲满丢包不会关连接，勿污染 closeReason
            $zhMsg = $this->translateWorkermanErrorMsg($msg);
            $isClosingError = stripos($msg, 'send buffer full') === false;
            if ($isClosingError && empty($connection->closeReason)) {
                $connection->closeReason = "错误[{$code}]:{$zhMsg}";
            }

            $deviceId = trim((string)($connection->deviceid ?? ''));
            $uid = (string)($connection->uid ?? '');
            if ($this->canUpdateDeviceOffline($deviceId, $uid)) {
                $this->updateDeviceOfflineStatus($deviceId);
            }
            $logLevel = $zhMsg === '客户端已关闭' ? 'info' : 'error';
            $this->setLog('连接错误: ' . json_encode([
                'code' => $code,
                'error_message' => $zhMsg,
                'client_ip' => $connection->getRemoteIp(),
                'client_port' => $connection->getRemotePort(),
                'uid' => $uid !== '' ? $uid : '未知',
                'deviceid' => $deviceId,
                'connection_status' => $connection->getStatus(),
                'close_reason' => $connection->closeReason ?? '',
                'time' => date('Y-m-d H:i:s'),
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), $logLevel);
        } catch (\Throwable $e) {
            $this->setLog($e, 'error');
        }
    }

    public function onWorkerStart(Worker $worker): void
    {
        $this->worker = $worker;
        $this->worker->uidConnections = [];
        $this->initServices(true);
        $this->worker->devices = new DeviceIndex($this->repository, (int)$worker->id);
        $this->subscribeWorkerChannel((int)$worker->id);
        $this->setLog("连接workerId [{$worker->id}] 已启动, PID: " . getmypid(), 'info');

        if ((int)$worker->id === 0) {
            $this->cleanupInvalidConnectionKeys();
            Timer::add(300, function (): void {
                $this->cleanupInvalidConnectionKeys();
            });
        }

        Timer::add(10, function () use ($worker): void {
            $this->cleanupStaleHandshakeConnections($worker);
            $this->checkDeviceHeartbeats($worker);
        });
    }

    /**
     * 清理未完成握手/业务绑定的僵尸连接，并写正向阶段日志。
     */
    private function cleanupStaleHandshakeConnections(Worker $worker): void
    {
        $now = time();
        $toClose = [];

        foreach ($worker->connections as $connection) {
            if (!$connection instanceof TcpConnection) {
                continue;
            }
            $connectedAt = (int)($connection->connectedAt ?? 0);
            if ($connectedAt <= 0) {
                continue;
            }
            $uid = (string)($connection->uid ?? '');
            if ($uid === '' && ($now - $connectedAt) > 30) {
                $toClose[] = [$connection, 'ws_handshake_timeout', 'WebSocket握手超时', ''];
            }
        }

        foreach ($worker->uidConnections as $uid => $connection) {
            if (!$connection instanceof TcpConnection) {
                continue;
            }
            $clientType = (string)($connection->clientType ?? '');
            if ($clientType !== '') {
                continue;
            }
            $handshakeAt = (int)($connection->handshakeAt ?? $connection->connectedAt ?? 0);
            if ($handshakeAt > 0 && ($now - $handshakeAt) > 60) {
                $toClose[] = [$connection, 'biz_bind_timeout', '业务绑定超时', (string)$uid];
            }
        }

        foreach ($toClose as [$connection, $stage, $reason, $uid]) {
            try {
                $this->setLog([
                    '阶段' => $stage,
                    'uid' => $uid !== '' ? $uid : ($connection->uid ?? ''),
                    'ip' => $connection->getRemoteIp(),
                    'port' => $connection->getRemotePort(),
                    'closeReason' => $reason,
                    'workerId' => (int)($worker->id ?? 0),
                    'time' => date('Y-m-d H:i:s'),
                ], 'info');
                if ($uid !== '' && isset($worker->uidConnections[$uid])) {
                    unset($worker->uidConnections[$uid]);
                }
                $this->closeWithReason($connection, $reason);
            } catch (\Throwable $e) {
                $this->setLog([
                    'msg' => '清理卡住连接异常',
                    '阶段' => $stage,
                    'error' => $e->getMessage(),
                ], 'error');
            }
        }
    }

    /**
     * 已绑定设备的心跳检查与探测下发（原有逻辑）。
     */
    private function checkDeviceHeartbeats(Worker $worker): void
    {
        foreach ($worker->uidConnections as $uid => $connection) {
            try {
                if (!isset($connection->clientType, $connection->lastHeartbeat) || $connection->clientType != 'device') {
                    continue;
                }

                if ((time() - $connection->lastHeartbeat) > 60) {
                    $deviceId = (string)($connection->deviceid ?? '');
                    if (!$this->repository->markDeviceOffline($deviceId, (string)$uid)) {
                        $this->closeWithReason($connection, '心跳超时（标记离线失败）');
                        unset($worker->uidConnections[$uid]);
                        continue;
                    }

                    $this->setLog("设备心跳超时 uid: {$uid}, deviceid: {$deviceId}, workerId: {$worker->id}", 'heart');

                    try {
                        // 数据库短暂不可用时不能让 Workerman 定时器异常退出。
                        $find = SvDevice::where('device_code', $deviceId)->limit(1)->findOrEmpty();
                        if (!$find->isEmpty()) {
                            $find->status = 0;
                            $find->update_time = time();
                            $find->save();
                            $this->toolUtil->sendNotification((int)$find->user_id, $find->device_name, '离线');
                        }
                    } catch (\Throwable $e) {
                        $this->setLog([
                            'msg' => '设备心跳超时更新数据库失败',
                            'uid' => (string)$uid,
                            'deviceid' => $deviceId,
                            'workerId' => (int)($worker->id ?? 0),
                            'error' => $e->getMessage(),
                        ], 'error');
                    }

                    $this->closeWithReason($connection, '心跳超时');
                    unset($worker->uidConnections[$uid]);
                    continue;
                }

                $connection->send(json_encode([
                    'appType' => 0,
                    'appVersion' => $connection->appversion,
                    'content' => json_encode(array(
                        'uid' => $uid,
                        'workerId' => $worker->id,
                    ), JSON_UNESCAPED_UNICODE),
                    'deviceId' => $connection->deviceid,
                    'messageId' => 0,
                    'type' => 0,
                ], JSON_UNESCAPED_UNICODE));
            } catch (\Throwable $e) {
                $this->setLog([
                    'msg' => '设备心跳检查异常',
                    'uid' => (string)$uid,
                    'deviceid' => (string)($connection->deviceid ?? ''),
                    'workerId' => (int)($worker->id ?? 0),
                    'error' => $e->getMessage(),
                ], 'error');
            }
        }
    }

    public function onWorkerReload(Worker $worker): void
    {
        $this->setLog('onWorkerReload', 'error');
    }

    public function onBufferFull(TcpConnection $connection): void
    {
        $this->setLog('发送缓冲区已满', 'error');
    }

    public function onBufferDrain(TcpConnection $connection): void
    {
        $connection->sendBufferSize = 0;
        $connection->maxSendBufferSize = 1024 * 1024 * 10;
        $this->setLog('发送缓冲区已清空', 'info');
    }

    public function sendSuccess(string $uid, array $payload): void
    {
        $payload['code'] = WorkerEnum::SUCCESS_CODE;
        $this->send($uid, $payload);
    }

    public function sendError(string $uid, array $payload): void
    {
        $code = $payload['code'] ?? WorkerEnum::ERROR_CODE;
        $payload = [
            'code' => WorkerEnum::ERROR_CODE,
            'reply' => [
                'code' => $code,
                'msg' => $payload['reply'] ?? (WorkerEnum::getMessage((int)$code) ?? '命令错误'),
                'deviceId' => $payload['deviceId'] ?? '',
            ],
            'appType' => $payload['appType'] ?? '',
            'type' => $payload['type'] ?? 'error',
            'messageId' => 0,
            'deviceId' => $payload['deviceId'] ?? '',
            'appVersion' => $payload['appVersion'] ?? WorkerEnum::APP_VERSION,
        ];
        $this->send($uid, $payload);
    }

    public function send(string $uid, array $payload): bool
    {
        try {
            $content = $this->formatOutgoingPayload($payload);
            $this->setLog('reply content: ' . json_encode($content, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), 'send');
            if (isset($this->worker->uidConnections[$uid])) {
                return $this->sendToLocalConnection($uid, $content);
            }

            $workerId = $this->repository->getConnectionWorkerId($uid);
            if ($workerId === null) {
                $this->setLog('uid 未找到: ' . $uid, 'error');
                return false;
            }
            $this->channelBus->publish($this->channelBus->workerChannel($workerId), [
                'uid' => $uid,
                'content' => $content,
            ]);
            $this->setLog("将消息发送到workerId {$workerId}, uid {$uid}", 'channel');
            return true;
        } catch (\Throwable $e) {
            $this->setLog([
                'msg' => '发送消息失败',
                'uid' => $uid,
                'deviceId' => $payload['deviceId'] ?? '',
                'type' => $payload['type'] ?? '',
                'error' => $e->getMessage(),
            ], 'error');
            return false;
        }
    }

    public function closeConnection(string $uid, string $reason = '连接被替换'): bool
    {
        if ($uid === '') {
            return false;
        }

        if (isset($this->worker->uidConnections[$uid])) {
            return $this->closeLocalConnection($uid, $reason);
        }

        $workerId = $this->repository->getConnectionWorkerId($uid);
        if ($workerId === null) {
            $this->repository->forgetConnection($uid);
            $this->setLog('closeConnection uid未找到: ' . $uid, 'channel');
            return false;
        }

        $this->channelBus->publish($this->channelBus->workerChannel($workerId), [
            'action' => 'closeConnection',
            'uid' => $uid,
            'reason' => $reason,
        ]);
        $this->setLog("跨进程关闭旧连接 workerId: {$workerId}, uid: {$uid}, reason: {$reason}", 'channel');
        return true;
    }

    public function closeWebUserConnections(int $userId, string $source, string $excludeUid = '', string $reason = 'WebSocket连接被替换'): void
    {
        if ($userId <= 0) {
            return;
        }

        $this->closeLocalWebUserConnections($userId, $source, $excludeUid, $reason);

        foreach ($this->repository->getWebUserConnectionUids($source, $userId) as $uid) {
            if ($uid !== $excludeUid) {
                $this->closeConnection($uid, $reason);
            }
        }

        $workerCount = max(1, (int)($this->worker->count ?? 1));
        $currentWorkerId = (int)($this->worker->id ?? 0);
        for ($workerId = 0; $workerId < $workerCount; $workerId++) {
            if ($workerId === $currentWorkerId) {
                continue;
            }

            $this->channelBus->publish($this->channelBus->workerChannel($workerId), [
                'action' => 'closeWebUserConnections',
                'userId' => $userId,
                'source' => $source,
                'excludeUid' => $excludeUid,
                'reason' => $reason,
            ]);
        }

        $this->setLog([
            'msg' => '广播关闭同账号同来源旧WebSocket连接',
            'userId' => $userId,
            'source' => $source,
            'excludeUid' => $excludeUid,
            'workerCount' => $workerCount,
        ], 'channel');
    }

    public function sendChannelMessage(string $deviceId, array $data, string $targetProcess = 'device'): void
    {
        $uid = $targetProcess === 'device'
            ? $this->repository->getDeviceUid($deviceId)
            : $this->redis->get("xhs:user:{$targetProcess}:{$deviceId}");
        if ($uid) {
            $this->send((string)$uid, $data);
            return;
        }

        $this->channelBus->publish("{$targetProcess}.{$deviceId}.message", [
            'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function registerChannelListener(TcpConnection $connection, string $id, string $type = 'device'): void
    {
        if (($connection->uid ?? '') === '') {
            $this->setLog('无效连接 uid: ' . ($connection->uid ?? '')  . ' workerId: ' . ($connection->workerId ?? '') . 'deviceId: ' . ($connection->deviceid ?? ''), 'channel');
            $this->closeWithReason($connection, '无效连接uid');
            return;
        }
        $channel = "{$type}.{$id}.message";
        $this->channelBus->subscribe($channel, function ($data) use ($connection, $id, $type, $channel): void {
            $message = $data['data'] ?? '';
            if ($type === 'device') {
                $connectionUid = (string)($connection->uid ?? '');
                $connectionDeviceId = trim((string)($connection->deviceid ?? ''));
                $activeUid = $this->repository->getDeviceUid($id);

                if (
                    $connectionUid === ''
                    || $connectionDeviceId !== $id
                    || ($connection->clientType ?? '') !== WorkerEnum::WS_DEVICE_TYPE
                    || $activeUid !== $connectionUid
                ) {
                    $this->setLog([
                        'msg' => '跳过旧设备频道消息',
                        'channel' => $channel,
                        'deviceId' => $id,
                        'connection_uid' => $connectionUid,
                        'active_uid' => $activeUid,
                        'connection_deviceid' => $connectionDeviceId,
                        'client_type' => $connection->clientType ?? '',
                        'workerId' => $connection->workerId ?? '',
                    ], 'channel');
                    return;
                }
            }

            $connection->send((string)$message);

            if (!is_array($message)) {
                $message = json_decode($message, true);
                $message = is_array($message) ? $message : [];
                $message['uid'] = $connection->uid ?? '';
                $message['workerId'] = $connection->workerId ?? '';
                $this->setLog($message, 'channel');
            }
        });
        $this->setLog('Channel listener registered: ' . $channel . ' uid: ' . ($connection->uid ?? '')  . ' workerId: ' . ($connection->workerId ?? ''), 'channel');
    }

    public function refreshConnectionOwner(TcpConnection $connection): void
    {
        if (!isset($connection->uid)) {
            return;
        }

        $this->repository->bindConnection((string)$connection->uid, (int)$this->worker->id, (string)($connection->clientType ?? ''));
        if (($connection->clientType ?? '') === WorkerEnum::WS_DEVICE_TYPE && ($connection->deviceid ?? '') !== '') {
            $this->repository->bindDevice(
                (string)$connection->deviceid,
                (string)$connection->uid,
                (int)$this->worker->id,
                (string)($connection->appversion ?? WorkerEnum::APP_VERSION)
            );
        }
        if (($connection->clientType ?? '') === WorkerEnum::WS_CLIENT_TYPE && (int)($connection->userid ?? 0) > 0) {
            $this->repository->bindWebUser(
                (string)($connection->sourceType ?? WorkerEnum::WS_SOURCE_PC_TYPE),
                (int)$connection->userid,
                (string)$connection->uid,
                (int)$this->worker->id
            );
        }
    }

    public function getWorker(): Worker
    {
        return $this->worker;
    }

    public function setWorker(Worker $worker): void
    {
        $this->worker = $worker;
    }

    public function getConnections(): array
    {
        return $this->worker->uidConnections ?? [];
    }

    public function setConnections(array $connections): void
    {
        $this->worker->uidConnections = $connections;
    }

    public function isWriteLog(): bool
    {
        return $this->isWriteLog;
    }

    public function getRedis(): Redis
    {
        return $this->redis;
    }

    public function getRepository(): ConnectionRepository
    {
        return $this->repository;
    }

    public function cleanupInvalidConnectionKeys(): array
    {
        $result = $this->repository->cleanupInvalidConnectionKeys(array_keys($this->worker->uidConnections ?? []));
        if (!empty($result['deleted'])) {
            $this->setLog('清理无效connection: ' . json_encode($result, JSON_UNESCAPED_UNICODE), 'info');
        }

        return $result;
    }

    private function shouldWriteIncomingInfoLog(string $data): bool
    {
        $message = json_decode($data, true);
        if (!is_array($message) || !array_key_exists('type', $message)) {
            return true;
        }

        $type = is_string($message['type'])
            ? trim($message['type'])
            : (string)$message['type'];

        return !in_array($type, ['ping', '0', '2000', '2001', (string)WorkerEnum::RPA_DEVICE_RUNNING_LOG], true);
    }

    public function setLog(string|array|\Throwable $content, $level = 'info'): void
    {
        try {
            if (!$this->isWriteLog) {
                return;
            }
            if ($content instanceof \Throwable) {
                $content = $content->getTraceAsString();
            }
            if (is_array($content)) {
                $content = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            }
            Log::channel('socket')->write($content, $level);
        } catch (\Throwable $th) {
            Log::channel('socket')->write($th, 'error');
        }
    }

    private function subscribeWorkerChannel(int $workerId): void
    {
        $this->channelBus->subscribe($this->channelBus->workerChannel($workerId), function ($message): void {
            $uid = (string)($message['uid'] ?? '');
            $action = (string)($message['action'] ?? 'send');
            if ($action === 'closeConnection') {
                $this->closeLocalConnection($uid, (string)($message['reason'] ?? '连接被替换'));
                return;
            }
            if ($action === 'scheduleForceClose') {
                $delaySec = (int)($message['delaySec'] ?? 10);
                if ($delaySec <= 0) {
                    $delaySec = 10;
                }
                $deviceId = trim((string)($message['deviceId'] ?? ''));
                $token = (string)($message['token'] ?? '');
                $reason = (string)($message['reason'] ?? '设备已解绑超时未断开');
                $traceId = (string)($message['trace_id'] ?? '');
                if ($traceId === '' && $deviceId !== '') {
                    $traceId = \app\common\service\device\RpaDeviceDispatchService::getTraceId($deviceId);
                }
                Timer::add($delaySec, function () use ($uid, $deviceId, $token, $reason, $traceId): void {
                    try {
                        if ($deviceId !== '' && $token !== '') {
                            $current = \app\common\service\device\RpaDeviceDispatchService::getUnbindCloseToken($deviceId);
                            if ($current === null || $current !== $token) {
                                \app\common\service\device\RpaDeviceDispatchService::logUnbind('unbind_close_skipped', [
                                    'trace_id' => $traceId,
                                    'device_code' => $deviceId,
                                    'uid' => $uid,
                                    'msg' => '延迟踢线已取消或 token 不匹配，跳过',
                                    'result' => 'skip',
                                    'reason' => $reason,
                                ]);
                                return;
                            }
                        }

                        $targetUid = $uid;
                        if ($deviceId !== '') {
                            $liveUid = \app\common\service\device\RpaDeviceDispatchService::getLiveDeviceUid($deviceId);
                            if ($liveUid !== null && $liveUid !== '') {
                                $targetUid = $liveUid;
                            }
                        }

                        $closed = false;
                        $logged = false;
                        // 优先关本进程连接，避免依赖 Channel 回环
                        if ($targetUid !== '' && isset($this->worker->uidConnections[$targetUid])) {
                            $closed = $this->closeLocalConnection($targetUid, $reason);
                        }
                        if (!$closed && $uid !== '' && $uid !== $targetUid && isset($this->worker->uidConnections[$uid])) {
                            $closed = $this->closeLocalConnection($uid, $reason);
                            $targetUid = $uid;
                        }
                        // 连接在其他 worker 时走 Channel（forceClose 内部已记 unbind_close_executed）
                        if (!$closed && $deviceId !== '') {
                            $closed = \app\common\service\device\RpaDeviceDispatchService::forceClose($deviceId, $reason, $uid);
                            $logged = $closed;
                        } elseif (!$closed && $uid !== '') {
                            $closed = $this->closeLocalConnection($uid, $reason);
                        }

                        if ($deviceId !== '') {
                            \app\common\service\device\RpaDeviceDispatchService::cancelDelayedForceClose($deviceId, [
                                'silent' => true,
                                'trace_id' => $traceId,
                            ]);
                        }

                        if ($closed && !$logged) {
                            \app\common\service\device\RpaDeviceDispatchService::logUnbind('unbind_close_executed', [
                                'trace_id' => $traceId,
                                'device_code' => $deviceId,
                                'uid' => $targetUid,
                                'msg' => '延迟踢线已执行',
                                'result' => 'ok',
                                'reason' => $reason,
                            ]);
                        }
                    } catch (\Throwable $e) {
                        \app\common\service\device\RpaDeviceDispatchService::logUnbind('unbind_error', [
                            'trace_id' => $traceId,
                            'device_code' => $deviceId,
                            'uid' => $uid,
                            'msg' => '延迟踢线执行失败',
                            'result' => 'fail',
                            'error' => $e->getMessage(),
                        ]);
                    }
                }, [], false);
                return;
            }
            if ($action === 'closeWebUserConnections') {
                $this->closeLocalWebUserConnections(
                    (int)($message['userId'] ?? 0),
                    (string)($message['source'] ?? WorkerEnum::WS_SOURCE_PC_TYPE),
                    (string)($message['excludeUid'] ?? ''),
                    (string)($message['reason'] ?? 'WebSocket连接被替换')
                );
                return;
            }

            $content = $message['content'] ?? null;
            if ($uid !== '' && is_array($content)) {
                $this->sendToLocalConnection($uid, $content);
            }
        });
    }

    /**
     * 主动关闭连接并写入 closeReason，供框架 onClose 日志使用。
     */
    private function closeWithReason(TcpConnection $connection, string $reason): void
    {
        $connection->closeReason = $reason;
        $connection->close();
    }

    /**
     * 将 Workerman 常见英文错误信息转为中文。
     */
    private function translateWorkermanErrorMsg(string $msg): string
    {
        $normalized = strtolower(trim($msg));
        $map = [
            'client closed' => '客户端已关闭',
            'send buffer full and drop package' => '发送缓冲区已满，丢弃数据包',
        ];
        if (isset($map[$normalized])) {
            return $map[$normalized];
        }
        // AsyncTcpConnection 失败时 msg 多为 stream_socket_client 原文，原样返回并加前缀
        if ($normalized !== '' && preg_match('/^[\\x00-\\x7F]+$/', $msg) === 1) {
            return '连接失败: ' . $msg;
        }
        return $msg !== '' ? $msg : '未知错误';
    }

    private function closeLocalConnection(string $uid, string $reason = '连接被替换'): bool
    {
        if (!isset($this->worker->uidConnections[$uid])) {
            $this->repository->forgetConnection($uid);
            $this->setLog('本地关闭旧连接失败，uid不存在: ' . $uid, 'channel');
            return false;
        }

        $connection = $this->worker->uidConnections[$uid];
        $connection->closeReason = $reason;
        $connection->destroy();
        $this->setLog('本地关闭旧连接: uid=' . $uid . ', reason=' . $reason, 'channel');
        return true;
    }

    private function closeLocalWebUserConnections(int $userId, string $source, string $excludeUid = '', string $reason = 'WebSocket连接被替换'): int
    {
        if ($userId <= 0) {
            return 0;
        }

        $closedCount = 0;
        foreach ($this->worker->uidConnections ?? [] as $uid => $connection) {
            if ((string)$uid === $excludeUid) {
                continue;
            }
            if (($connection->clientType ?? '') !== WorkerEnum::WS_CLIENT_TYPE) {
                continue;
            }
            if ((int)($connection->userid ?? 0) !== $userId) {
                continue;
            }

            $connectionSource = (string)($connection->sourceType ?? '');
            if ($connectionSource !== '' && $connectionSource !== $source) {
                continue;
            }

            if ($this->closeLocalConnection((string)$uid, $reason)) {
                $closedCount++;
            }
        }

        if ($closedCount > 0) {
            $this->setLog([
                'msg' => '本进程关闭同账号同来源旧WebSocket连接',
                'userId' => $userId,
                'source' => $source,
                'excludeUid' => $excludeUid,
                'closedCount' => $closedCount,
            ], 'channel');
        }

        return $closedCount;
    }

    private function sendToLocalConnection(string $uid, array $content): bool
    {
        if (!isset($this->worker->uidConnections[$uid])) {
            $this->setLog('本地uid未找到: ' . $uid, 'error');
            return false;
        }

        $connection = $this->worker->uidConnections[$uid];
        $clientType = (string)($connection->clientType ?? '');
        $deviceId = trim((string)($connection->deviceid ?? ''));
        $established = defined(TcpConnection::class . '::STATUS_ESTABLISHED')
            ? TcpConnection::STATUS_ESTABLISHED
            : 2;
        if ((int)$connection->getStatus() !== (int)$established) {
            $this->setLog([
                'msg' => '连接已关闭，跳过发送',
                'uid' => $uid,
                'deviceId' => $deviceId,
                'status' => $connection->getStatus(),
            ], 'info');
            return false;
        }

        $connection->messageCount = (int)($connection->messageCount ?? 0) + 1;
        $connection->send(json_encode($content, JSON_UNESCAPED_UNICODE));
        if ((int)$connection->getStatus() !== (int)$established) {
            $this->setLog([
                'msg' => '连接已关闭，跳过发送',
                'uid' => $uid,
                'deviceId' => $deviceId,
                'status' => $connection->getStatus(),
                '阶段' => 'send_after',
            ], 'info');
            return false;
        }
        if ($clientType === WorkerEnum::WS_DEVICE_TYPE && $deviceId !== '') {
            $this->redis->set("xhs:{$clientType}:{$deviceId}:sendtime", time());
        }
        return true;
    }

    private function formatOutgoingPayload(array $payload): array
    {
        $content = [
            'appType' => $payload['appType'] ?? 3,
            'messageId' => 0,
            'type' => $payload['type'] ?? 'message',
            'content' => !is_array($payload['reply'] ?? null)
                ? ($payload['reply'] ?? null)
                : json_encode($payload['reply'], JSON_UNESCAPED_UNICODE),
            'deviceId' => $payload['deviceId'] ?? '',
            'appVersion' => $payload['appVersion'] ?? WorkerEnum::APP_VERSION,
            'code' => $payload['code'] ?? WorkerEnum::SUCCESS_CODE,
            'action' => 'send',
        ];
        if ($content['content'] === null && array_key_exists('content', $payload)) {
            $content['content'] = json_encode($payload['content'], JSON_UNESCAPED_UNICODE);
        }
        return $content;
    }

    private function sendWeb(array $content): void
    {
        try {
            $find = SvDevice::where('device_code', $content['deviceId'])->limit(1)->find();
            if (empty($find)) {
                $this->setLog('设备未找到: ' . $content['deviceId'], 'user');
                return;
            }

            foreach (WorkerEnum::WS_SOURCES as $source) {
                $uid = $this->redis->get("xhs:user:{$source}:{$find['user_id']}");
                if (!$uid) {
                    continue;
                }

                $this->send((string)$uid, [
                    'messageId' => $uid,
                    'type' => $content['type'],
                    'appType' => $content['appType'] ?? 3,
                    'deviceId' => $content['deviceId'],
                    'appVersion' => $content['appVersion'] ?? WorkerEnum::APP_VERSION,
                    'code' => $content['code'],
                    'reply' => json_encode($content, JSON_UNESCAPED_UNICODE),
                ]);
            }
        } catch (\Throwable $e) {
            $this->setLog('sendWeb:' . $e, 'error');
        }
    }

    /**
     * 仅当本连接仍是 Redis 当前绑定时，才允许把设备打成离线。
     */
    private function canUpdateDeviceOffline(string $deviceId, string $uid): bool
    {
        if ($deviceId === '') {
            return false;
        }
        $activeUid = $this->repository->getDeviceUid($deviceId);
        if ($activeUid === null || $activeUid === '') {
            return true;
        }
        return $uid !== '' && $activeUid === $uid;
    }

    /**
     * 更新设备离线状态
     * @param string $deviceId 设备ID
     */
    private function updateDeviceOfflineStatus(string $deviceId): void
    {
        try {
            SvDevice::where('device_code', $deviceId)->update(['status' => 0, 'update_time' => time()]);
            SvAccount::where('device_code', $deviceId)->update(['status' => 0, 'update_time' => time()]);
        } catch (\Throwable $e) {
            $this->setLog([
                'msg' => '设备离线状态同步失败',
                'deviceId' => $deviceId,
                'error' => $e->getMessage(),
            ], 'error');
        }
    }

    private function _connRedis(bool $force = false): void
    {
        if ($this->redis !== null && !$force) {
            return;
        }

        $timeout = (float)env('WORKERMAN.REDIS_TIMEOUT', 2);
        if ($timeout <= 0) {
            $timeout = 2;
        }

        $this->redis = new Redis([
            'host' => env('redis.HOST', '127.0.0.1'),
            'port' => env('redis.PORT', 6379),
            'password' => env('redis.PASSWORD', '123456'),
            'select' => env('redis.WS_SELECT', 8),
            'timeout' => $timeout,
            'persistent' => true,
        ]);
    }
}

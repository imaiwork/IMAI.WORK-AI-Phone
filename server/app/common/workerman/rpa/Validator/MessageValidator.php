<?php

declare(strict_types=1);

namespace app\common\workerman\rpa\Validator;

use app\common\workerman\rpa\Support\ConnectionRepository;
use app\common\workerman\rpa\Support\DeviceAuthClient;
use Workerman\Connection\TcpConnection;
use app\common\workerman\rpa\RpaSocketService;
use app\common\workerman\rpa\WorkerEnum;



class MessageValidator
{
    private array $whitelist = [1, 11, 'bindSocket', 'ping', 21, 25, 26, 91];
    private array $runLogTypes = [2000, 2001, WorkerEnum::RPA_DEVICE_RUNNING_LOG];
    private array $logTypeWhite = [2000, 2001];
    private string $errorMsg = '';

    public function __construct(
        private RpaSocketService $service,
        private ConnectionRepository $repository
    ) {
    }

    public function validate(TcpConnection $connection, array $message): array
    {
        try {
            if (!isset($message['type'])) {
                return $this->reject($connection, $message, '无效请求', WorkerEnum::INVALID_REQUEST);
            }

            $type = ctype_digit((string)$message['type']) ? intval($message['type']) : $message['type'];
            $payload = $message;

            if (isset($payload['deviceId']) && trim((string)$payload['deviceId']) !== '') {
                $payload['deviceId'] = trim((string)$payload['deviceId']);
            }

            if ($type === 'ping' || $type === 0) {
                return [$type, $payload];
            }

            if (in_array($type, $this->runLogTypes, true)) {
                $this->service->setLog($payload, 'run');
            }

            if (in_array($type, $this->logTypeWhite, true)) {
                return ['continue', $payload];
            }

            if (!isset($message['content'])) {
                return $this->reject($connection, $payload, '无效请求，内容缺失', WorkerEnum::INVALID_REQUEST);
            }

            if (!isset($payload['deviceId'])) {
                return $this->reject($connection, $payload, '无效请求，deviceId缺失', WorkerEnum::INVALID_REQUEST_NOFUND_DEVICE);
            }

            if (in_array($type, [1, 'addDevice'], true) && !$this->checkDevice($payload)) {
                return $this->reject($connection, $payload, $this->errorMsg, WorkerEnum::DEVICE_NOT_FOUND);
            }

            if (!in_array($type, $this->whitelist, true)) {
                if (trim((string)$payload['deviceId']) === '') {
                    return $this->reject($connection, $payload, '无效deviceId，deviceId缺失', WorkerEnum::DEVICE_INVALID_REQUEST);
                }

                if (!$this->ensureDeviceOnline($connection, (string)$payload['deviceId'])) {
                    return $this->reject(
                        $connection,
                        $payload,
                        (string)$payload['deviceId'] . '设备离线，需要重新连接设备',
                        WorkerEnum::DEVICE_OFFLINE
                    );
                }
            }

            if (isset($payload['content'])) {
                $content = is_array($payload['content']) ? $payload['content'] : json_decode((string)$payload['content'], true);
                if (is_array($content)) {
                    if (isset($content['deviceId'])) {
                        $content['deviceId'] = trim((string)$content['deviceId']);
                    }
                    $payload['content'] = $content;
                }
            }

            return [$type, $payload];
        } catch (\Throwable $e) {
            $this->service->setLog([
                'msg' => '消息校验异常',
                'uid' => $connection->uid ?? '',
                'deviceId' => $message['deviceId'] ?? '',
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ], 'error');
            throw $e;
        }
    }

    /**
     * Redis 已在线，或当前连接就是该设备时修复在线标记。
     */
    private function ensureDeviceOnline(TcpConnection $connection, string $deviceId): bool
    {
        if ($this->repository->isDeviceOnline($deviceId)) {
            return true;
        }

        $boundDeviceId = trim((string)($connection->deviceid ?? ''));
        $clientType = (string)($connection->clientType ?? '');
        $uid = (string)($connection->uid ?? '');
        if ($clientType !== WorkerEnum::WS_DEVICE_TYPE || $boundDeviceId !== $deviceId || $deviceId === '' || $uid === '') {
            return false;
        }

        $this->repository->markDeviceOnline(
            $deviceId,
            $uid,
            (int)($connection->workerId ?? 0),
            (string)($connection->appversion ?? WorkerEnum::APP_VERSION)
        );
        $this->service->setLog([
            'msg' => '设备连接仍在，已修复离线标记',
            'uid' => $uid,
            'deviceId' => $deviceId,
            'workerId' => (int)($connection->workerId ?? 0),
        ], 'info');

        return true;
    }

    /**
     * 可预期的校验失败：无栈日志 + 业务错误回包。
     */
    private function reject(TcpConnection $connection, array $message, string $errorMsg, int $code): array
    {
        $this->service->setLog([
            'msg' => '消息校验未通过',
            'uid' => $connection->uid ?? '',
            'deviceId' => $message['deviceId'] ?? '',
            'type' => $message['type'] ?? '',
            'code' => $code,
            'reason' => $errorMsg,
        ], 'info');

        $this->service->sendError((string)($connection->uid ?? ''), [
            'code' => $code,
            'reply' => $errorMsg,
            'deviceId' => $message['deviceId'] ?? '',
            'type' => $message['type'] ?? 'error',
            'appType' => $message['appType'] ?? '',
            'appVersion' => $message['appVersion'] ?? WorkerEnum::APP_VERSION,
        ]);

        return [false, []];
    }

    private function checkDevice(array $payload): bool
    {
        try {
            if (($payload['deviceId'] ?? '') === '') {
                return true;
            }

            $content = is_array($payload['content']) ? $payload['content'] : json_decode((string)$payload['content'], true);
            \think\facade\Log::channel('socket')->write('checkDevice:' . json_encode([
                'device_code' => $payload['deviceId'],
                'platform' => 3,
                'code' => $content['code'] ?? '',
            ], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT), 'device');

            $response = DeviceAuthClient::checkSvDevice([
                'device_code' => $payload['deviceId'],
                'platform' => 3,
                'code' => $content['code'] ?? '',
            ]);

            $this->service->setLog($response, 'device');
            if ((int)$response['code'] === 10000) {
                return true;
            }

            if (DeviceAuthClient::isTimeoutResponse($response)) {
                $this->service->setLog([
                    'msg' => '设备鉴权超时',
                    'deviceId' => $payload['deviceId'] ?? '',
                    'response' => $response,
                ], 'error');
                $this->errorMsg = '设备鉴权超时，请稍后重试';
                return false;
            }

            $this->errorMsg = $response['message'] ?? '设备未找到';
            return false;
        } catch (\Throwable $e) {
            $this->service->setLog([
                'msg' => '设备鉴权失败',
                'deviceId' => $payload['deviceId'] ?? '',
                'error' => $e->getMessage(),
            ], 'error');
            $this->errorMsg = '设备鉴权失败，请稍后重试';
            return false;
        }
    }
}

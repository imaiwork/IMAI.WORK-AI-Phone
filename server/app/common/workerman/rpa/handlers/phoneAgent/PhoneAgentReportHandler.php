<?php

namespace app\common\workerman\rpa\handlers\phoneAgent;

use app\common\service\phoneAgent\PhoneAgentStateService;
use app\common\workerman\rpa\BaseMessageHandler;
use app\common\workerman\rpa\WorkerEnum;
use Workerman\Connection\TcpConnection;

class PhoneAgentReportHandler extends BaseMessageHandler
{
    public function handle(TcpConnection $connection, string $uid, array $payload): void
    {
        $content = $payload['content'] ?? [];
        if (!is_array($content)) {
            $decoded = json_decode((string)$content, true);
            $content = is_array($decoded) ? $decoded : [];
        }
        $content['_connection_device_id'] = (string)($connection->deviceid ?? ($payload['deviceId'] ?? ''));
        $content['_connection_client_type'] = (string)($connection->clientType ?? '');

        try {
            $result = PhoneAgentStateService::handleReport($content, $payload);
            $this->service->send($uid, [
                'type' => WorkerEnum::RPA_PHONE_AGENT_REPORT,
                'deviceId' => $payload['deviceId'] ?? ($content['device_code'] ?? ''),
                'appType' => $payload['appType'] ?? 0,
                'appVersion' => $payload['appVersion'] ?? WorkerEnum::APP_VERSION,
                'code' => WorkerEnum::SUCCESS_CODE,
                'reply' => [
                    'task_id' => $content['task_id'] ?? '',
                    'event_type' => $result['event_type'] ?? '',
                    'message' => $result['message'] ?? '',
                ],
            ]);
        } catch (\Throwable $e) {
            $this->setLog('PhoneAgentReportHandler:' . $e->getMessage(), 'error');
            $this->service->send($uid, [
                'type' => WorkerEnum::RPA_PHONE_AGENT_REPORT,
                'deviceId' => $payload['deviceId'] ?? ($content['device_code'] ?? ''),
                'appType' => $payload['appType'] ?? 0,
                'appVersion' => $payload['appVersion'] ?? WorkerEnum::APP_VERSION,
                'code' => WorkerEnum::DEVICE_ERROR_CODE,
                'reply' => [
                    'task_id' => $content['task_id'] ?? '',
                    'message' => $e->getMessage(),
                ],
            ]);
        }
    }
}

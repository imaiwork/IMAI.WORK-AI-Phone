<?php

namespace app\common\workerman\rpa\handlers\device;

use app\common\workerman\rpa\BaseMessageHandler;
use Workerman\Connection\TcpConnection;
use app\common\workerman\rpa\WorkerEnum;

class AppActionHandler extends BaseMessageHandler
{
    public function handle(TcpConnection $connection, string $uid, array $payload): void {}

    public function sendActionToWeb($content, $action)
    {

        try {

            $userId = $this->service->getRedis()->get("xhs:getUser:" . $this->payload['deviceId']);
            $sources = WorkerEnum::WS_SOURCES;
            foreach ($sources as $source) {
                $uid = $this->service->getRedis()->get("xhs:user:{$source}:{$userId}");
                if ($uid) {
                    $message = array(
                        'messageId' => $uid,
                        'type' => $action,
                        'appType' => $this->payload['appType'] ?? 3,
                        'deviceId' => $this->payload['deviceId'],
                        'appVersion' => $this->payload['appVersion'] ?? WorkerEnum::APP_VERSION,
                        'code' => $this->payload['code'],
                        'reply' => json_encode($content, JSON_UNESCAPED_UNICODE)
                    );
                    $this->sendResponse($uid,  $message,  $message['reply']);
                }
            }
        } catch (\Exception $e) {
            $this->setLog('_sendWeb' . $e, 'error');
        }
    }
}

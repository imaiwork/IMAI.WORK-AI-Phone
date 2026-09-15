<?php

namespace app\common\workerman\rpa\handlers;

use app\common\workerman\rpa\BaseMessageHandler;
use Workerman\Connection\TcpConnection;
use app\common\model\sv\SvDevice;

class DeviceHeartBeatHandler extends BaseMessageHandler
{
    protected $HEARTBEAT_TIME = 60;
    protected $heartbeatCounter = [];
    public function handle(TcpConnection $connection, string $uid, array $payload): void
    {
        try {
            $deviceId = trim((string)$payload['deviceId']);
            $worker = $this->service->getWorker();
            if (isset($worker->uidConnections[$uid])) {
                $worker->uidConnections[$uid]->lastHeartbeat = time();
                $this->service->setWorker($worker);
                if (!isset($connection->heartbeatCounter)) {
                    $connection->heartbeatCounter = 0;
                }
                $connection->heartbeatCounter++;
                if ($connection->heartbeatCounter % 5 === 0) {

                    $payload['uid'] = $uid;
                    $payload['workerId'] = $worker->id;
                    $this->setLog('设备心跳记录:' . json_encode($payload, JSON_UNESCAPED_UNICODE), 'heart');
                    $connection->heartbeatCounter = 0;
                }
                $this->service->getRepository()->markDeviceHeartbeat(
                    $deviceId,
                    $uid,
                    (int)$worker->id,
                    (string)($connection->appversion ?? '')
                );
            }
        } catch (\Exception $e) {
            $this->setLog('handle' . $e, 'heart');
        }
    }
}

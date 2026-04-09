<?php

namespace app\common\workerman\rpa\handlers;

use app\common\workerman\rpa\BaseMessageHandler;
use Workerman\Connection\TcpConnection;
use app\common\model\sv\SvDevice;

class DeviceHeartBeatHandler extends BaseMessageHandler
{
    protected $HEARTBEAT_TIME = 60;
    public function handle(TcpConnection $connection, string $uid, array $payload): void
    {
        try {
            $connection->lastHeartbeat = time();
            //$this->setLog('设备上线:' . json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT), 'heart');
            $this->service->getRedis()->set("xhs:device:" . $payload['deviceId'] . ":heart", date('Y-m-d H:i:s', time()));
            // if ($status == 'offline') {
            //     $this->service->getRedis()->set("xhs:device:" . $payload['deviceId'] . ":status", 'online');
            //     $this->setLog('设备上线:' . $payload['deviceId'], 'info');
            //     // 可以在这里触发设备上线事件
            //     SvDevice::where('device_code', $payload['deviceId'])->update(['status' => 1, 'update_time' => time()]);
            // }
        } catch (\Exception $e) {
            $this->setLog('handle' . $e, 'heart');
        }
    }
}

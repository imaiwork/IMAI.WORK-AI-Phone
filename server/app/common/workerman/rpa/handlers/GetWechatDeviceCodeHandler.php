<?php

namespace app\common\workerman\rpa\handlers;

use app\common\workerman\rpa\BaseMessageHandler;
use Workerman\Connection\TcpConnection;
use app\common\model\sv\SvDevice;
use app\common\workerman\rpa\WorkerEnum;

class GetWechatDeviceCodeHandler extends BaseMessageHandler
{
    public function handle(TcpConnection $connection, string $uid, array $payload): void
    {
        $content = !is_array($payload['content']) ? json_decode($payload['content'], true) : $payload['content'];
        try {
            $this->msgType = WorkerEnum::DESC[$payload['type']] ?? $payload['type'];
            $this->uid = $uid;
            $this->payload = $payload;
            $this->userId = $content['userId'] ?? 0;
            $this->connection = $connection;

            $this->service->getRedis()->set("xhs:device:" . $payload['deviceId'] . ":wechat_code", $content['wechatDeviceCode']);

            $deviceId = $content['deviceId'] ?? '';
            $find = SvDevice::where('device_code', $deviceId)->findOrEmpty();
            if ($find->isEmpty()) {
                $this->setLog('设备不存在:' . $deviceId, 'cron');
                throw new \Exception('设备不存在:' . $deviceId);
            }

            $find->wechat_device_code = $content['wechatDeviceCode'] ?? '';
            $find->update_time = time();
            $find->save();

            $this->payload['reply'] = '设备微信绑定码已更新';
            $this->sendResponse($this->uid, $this->payload, $this->payload['reply']);
        } catch (\Exception $e) {
            $this->setLog('异常信息' . $e, 'cron');

            $this->payload['reply'] = $e->getMessage();
            $this->payload['code'] =  WorkerEnum::DEVICE_ERROR_CODE;
            $this->payload['type'] = 'error';
            $this->sendError($this->connection,  $this->payload);
        }
    }
}

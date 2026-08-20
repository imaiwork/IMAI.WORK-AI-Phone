<?php

namespace app\common\workerman\rpa\handlers\device;

use app\common\model\sv\SvDevice;
use app\common\model\sv\SvDeviceLog;
use app\common\workerman\rpa\BaseMessageHandler;
use app\common\workerman\rpa\WorkerEnum;
use Workerman\Connection\TcpConnection;


class RunningLogHandler extends BaseMessageHandler
{
    protected $appType = 0;

    // 入口保持不变
    public function handle(TcpConnection $connection, string $uid, array $payload): void
    {
        $content = !is_array($payload['content']) ? json_decode($payload['content'], true) : $payload['content'];
        try {
            $this->msgType = WorkerEnum::DESC[$payload['type']] ?? $payload['type'];
            $this->appType = $payload['appType'] ?? 0;
            $this->uid = $uid;
            $this->payload = $payload;
            $this->userId = $content['userId'] ?? 0;
            $this->connection = $connection;
            $this->payload['reply'] = $this->saveLog($content);
            $this->payload['type'] = WorkerEnum::RPA_DEVICE_RUNNING_LOG;
            //$this->sendResponse($uid, $this->payload, $this->payload['reply']);
        } catch (\Exception $e) {
            $this->setLog('RunningLogHandler异常信息' . $e->getTraceAsString(), 'error');
            $this->payload['reply'] = $e->getMessage();
            $this->payload['code'] = WorkerEnum::RPA_DEVICE_RUNNING_LOG_FAIL;
            $this->payload['type'] = WorkerEnum::RPA_DEVICE_RUNNING_LOG;
            $this->payload['content'] = [
                'code' => WorkerEnum::RPA_DEVICE_RUNNING_LOG_FAIL,
                'msg' => '异常信息:' . $e->getMessage(),
                'deviceId' => $this->payload['deviceId']
            ];
            $this->sendError($this->connection, $this->payload);
        } finally {
            unset($content);
        }
    }

    private function saveLog(array $content){
        try {
            $device = SvDevice::where('device_code', $this->payload['deviceId'])->findOrEmpty();
            if ($device->isEmpty()) {
                throw new \Exception('设备不存在');
            }
            $content['msg'] = $content['msg'] ?? '未知信息';
            $log = SvDeviceLog::create([
                'user_id' => $device->user_id,
                'device_code' => $this->payload['deviceId'],
                'app_type' => $this->payload['appType'],
                'content' => $content,
                'app_version' => $this->payload['appVersion'],
                'day' => date('Y-m-d'),
                'create_time' => time(),
            ]);
            return '日志保存成功.' . $log->id;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
<?php

namespace app\common\workerman\rpa\handlers\sph;

use Workerman\Connection\TcpConnection;
use app\common\workerman\rpa\BaseMessageHandler;
use app\common\workerman\rpa\WorkerEnum;
use app\common\model\sv\SvCrawlingTask;
use app\common\model\sv\SvCrawlingTaskDeviceBind;

class TaskReceivedHandler extends BaseMessageHandler
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
            $this->payload['reply'] = $this->updateTaskDeviceStatus($content);

            $this->sendResponse($uid, $this->payload, $this->payload['reply']);
        } catch (\Exception $e) {
            $this->setLog('异常信息' . $e, 'task_received');
            $this->payload['reply'] = $e->getMessage();
            $this->payload['code'] =  WorkerEnum::SPH_RECEIVED_ERROR_CODE;
            $this->payload['type'] = 26;
            $this->payload['content'] = [
                'code' =>  WorkerEnum::SPH_RECEIVED_ERROR_CODE,
                'msg' => '异常信息:' . $e->getMessage(),
                'deviceId' => $this->payload['deviceId']
            ];
            $this->sendError($this->connection,  $this->payload);
        }
    }

    private function updateTaskDeviceStatus(array $content)
    {
        try {
            //更新任务设备绑定表
            SvCrawlingTaskDeviceBind::where('task_id',  $content['task_id'])->where('device_code', $content['deviceId'])->update([
                'status' => 1,
                'update_time' => time(),
            ]);
            SvCrawlingTask::where('id', $content['task_id'])->update(['status' => 1, 'update_time' => time()]);

            return [
                'deviceId' => $this->payload['deviceId'],
                'task_id' =>  $content['task_id'],
                'status' => 1,
                'time' => date('Y-m-d H:i:s'),
                'msg' => '任务接收,设备状态已设置为待执行',
            ];
        } catch (\Exception $e) {
            $this->setLog('异常信息' . $e, 'task_received');
            $this->payload['reply'] = $e->getMessage();
            $this->payload['code'] =  WorkerEnum::SPH_RECEIVED_ERROR_CODE;
            $this->payload['type'] = 26;
            $this->payload['content'] = [
                'code' =>  WorkerEnum::SPH_RECEIVED_ERROR_CODE,
                'msg' => '异常信息:' . $e->getMessage(),
                'deviceId' => $this->payload['deviceId']
            ];
            $this->sendError($this->connection,  $this->payload);
        }
    }
}

<?php

namespace app\common\workerman\rpa\handlers\sph;

use Workerman\Connection\TcpConnection;
use app\common\workerman\rpa\BaseMessageHandler;
use app\common\workerman\rpa\WorkerEnum;
use app\common\model\sv\SvCrawlingTask;
use app\common\model\sv\SvCrawlingTaskDeviceBind;

class TaskCompletedHandler extends BaseMessageHandler
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
            $this->payload['reply'] = $this->completeTask($content);

            $this->sendResponse($uid, $this->payload, $this->payload['reply']);
        } catch (\Exception $e) {
            $this->setLog('异常信息' . $e, 'task_complete');
            $this->payload['reply'] = $e->getMessage();
            $this->payload['code'] =  WorkerEnum::SPH_COMPLETE_ERROR_CODE;
            $this->payload['type'] = 25;
            $this->payload['content'] = [
                'code' =>  WorkerEnum::SPH_COMPLETE_ERROR_CODE,
                'msg' => '异常信息:' . $e->getMessage(),
                'deviceId' => $this->payload['deviceId']
            ];
            $this->sendError($this->connection,  $this->payload);
        }
    }

    private function completeTask(array $content)
    {
        try {
            $find = SvCrawlingTask::where('id', $content['task_id'])->findOrEmpty();

            if ($find->isEmpty()) {
                throw new \Exception('任务不存在');
            }
            //$find->status = 3;

            //更新任务设备绑定表
            SvCrawlingTaskDeviceBind::where('task_id',  $content['task_id'])->where('device_code', $content['deviceId'])->update([
                'status' => 3,
                'update_time' => time(),
            ]);

            $devices_count = count(json_decode($find->device_codes, true));
            $count = SvCrawlingTaskDeviceBind::where('task_id',  $content['task_id'])->where('status', 3)->group('device_code')->count();
            if ($count == $devices_count) {
                $find->status = 3;
            }
            $find->update_time = time();
            $find->save();

            return [
                'deviceId' => $this->payload['deviceId'],
                'task_id' =>  $content['task_id'],
                'status' => 3,
                'time' => date('Y-m-d H:i:s'),
                'msg' => '任务完成设置成功',
            ];
        } catch (\Exception $e) {
            $this->setLog('异常信息' . $e, 'task_complete');
            $this->payload['reply'] = $e->getMessage();
            $this->payload['code'] =  WorkerEnum::SPH_COMPLETE_ERROR_CODE;
            $this->payload['type'] = 25;
            $this->payload['content'] = [
                'code' =>  WorkerEnum::SPH_COMPLETE_ERROR_CODE,
                'msg' => '异常信息:' . $e->getMessage(),
                'deviceId' => $this->payload['deviceId']
            ];
            $this->sendError($this->connection,  $this->payload);
        }
    }
}

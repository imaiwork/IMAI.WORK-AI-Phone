<?php

namespace app\common\workerman\rpa\handlers;

use app\common\workerman\rpa\BaseMessageHandler;
use Workerman\Connection\TcpConnection;
use app\common\model\sv\SvDeviceTask;
use app\common\model\sv\SvDeviceTakeOverTaskAccount;
use app\common\workerman\rpa\WorkerEnum;

class TakeOverTaskResultSaveHandler extends BaseMessageHandler
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


            $taskId = $content['taskId'] ?? 0;
            $task = SvDeviceTask::where('sub_task_id', $taskId)->where('device_code', $this->payload['deviceId'])->where('source', '3')->findOrEmpty();
            if ($task->isEmpty()) {
                $this->setLog('设备任务接管子任务不存在:' . $taskId . ' sql: ' . \think\facade\Db::getLastSql(), 'cron');
                return;
            }
            $task->status = $content['status'] ?? $task->status;
            $task->remark = $content['msg'] ?? $task->remark;
            $task->update_time = time();
            $task->save();


            $account = SvDeviceTakeOverTaskAccount::where('id', $taskId)->findOrEmpty();
            if ($account->isEmpty()) {
                $this->setLog('接管任务不存在:' . $taskId, 'cron');
                return;
            }

            $account->status = $content['status'] ?? $account->status;
            //$account->remark = $content['remark'] ?? $account->remark;
            $account->update_time = time();
            $account->save();



            $this->payload['reply'] = '接管任务结果已更新';
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

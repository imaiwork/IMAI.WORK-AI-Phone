<?php

namespace app\common\workerman\rpa\handlers;

use app\common\workerman\rpa\BaseMessageHandler;
use Workerman\Connection\TcpConnection;
use app\common\model\sv\SvDeviceActiveAccount;
use app\common\workerman\rpa\WorkerEnum;

class ActiveTaskResultSaveHandler extends BaseMessageHandler
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

            $task = SvDeviceActiveAccount::where('id', $taskId)->findOrEmpty();
            if ($task->isEmpty()) {
                $this->setLog('活动任务不存在:' . $taskId, 'cron');
                return;
            }

            $task->duration = $content['duration'] ?? 0;
            $task->action_times = $content['actionTimes'] ?? 0;
            $task->interactive_times = $content['interactiveTimes'] ?? 0;
            $task->update_time = time();
            $task->save();

            $this->payload['reply'] = '活动任务结果已更新';
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

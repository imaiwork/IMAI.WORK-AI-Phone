<?php

namespace app\common\workerman\rpa\handlers\sph;

use Workerman\Connection\TcpConnection;
use app\common\workerman\rpa\BaseMessageHandler;
use app\common\workerman\rpa\WorkerEnum;

class TaskSendHandler extends BaseMessageHandler
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


            $task = $this->getTaskInfo();
            $this->payload['reply'] = $task;
            $this->sendResponse($uid, $this->payload, $this->payload['reply']);
        } catch (\Exception $e) {
            $this->setLog('异常信息' . $e, 'task_send');
            $this->payload['reply'] = $e->getMessage();
            $this->payload['code'] =  WorkerEnum::SPH_SEND_ERROR_CODE;
            $this->payload['type'] = 'error';
            $this->sendError($this->connection,  $this->payload);
        }
    }

    private function getTaskInfo()
    {

        return [
            'id' => 1,
            'task_id' => generate_unique_task_id(),
            'platform' => '视频号',
            'device' => $this->payload['deviceId'],
            'keywords' => ['人文', '地理', '历史'],
            'exec_number' => 99,
            'is_chat' => 1,
            'chat_frep' => 10,
            'greet_content' => '您好,我是AI',
            'status' => 0,
            'tokens' => 0,
            'create_time' => time(),
        ];
    }
}

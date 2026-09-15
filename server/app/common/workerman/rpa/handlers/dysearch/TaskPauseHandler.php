<?php

declare(strict_types=1);

namespace app\common\workerman\rpa\handlers\dysearch;

use Workerman\Connection\TcpConnection;
use app\common\workerman\rpa\BaseMessageHandler;
use app\common\workerman\rpa\WorkerEnum;

class TaskPauseHandler extends BaseMessageHandler
{
    public function handle(TcpConnection $connection, string $uid, array $payload): void
    {
        $content = !is_array($payload['content'] ?? null) ? json_decode((string)($payload['content'] ?? ''), true) : $payload['content'];
        if (!is_array($content)) {
            $content = [];
        }
        try {
            $this->msgType = WorkerEnum::DESC[$payload['type']] ?? $payload['type'];
            $this->uid = $uid;
            $this->payload = $payload;
            $this->userId = (int)($content['userId'] ?? 0);
            $this->connection = $connection;

            $this->setLog('抖音获客任务暂停回执已收到 task_id=' . ($content['task_id'] ?? 0) . ' device=' . ($this->payload['deviceId'] ?? ''), 'dy_search');
            $this->payload['reply'] = [
                'deviceId' => $this->payload['deviceId'] ?? '',
                'task_id' => $content['task_id'] ?? 0,
                'status' => 2,
                'time' => date('Y-m-d H:i:s'),
                'msg' => '暂停成功',
            ];
            $this->sendResponse($uid, $this->payload, $this->payload['reply']);
        } catch (\Exception $e) {
            $this->setLog('抖音获客任务暂停回执异常：' . $e->getMessage(), 'dy_search');
            $this->payload['reply'] = $e->getMessage();
            $this->payload['code'] = WorkerEnum::RPA_DEVICE_DY_SEARCH_TASK_FAIL;
            $this->payload['type'] = 'error';
            $this->sendError($this->connection, $this->payload);
        } finally {
            unset($content);
        }
    }
}

<?php

declare(strict_types=1);

namespace app\common\workerman\rpa\handlers\dysearch;

use Workerman\Connection\TcpConnection;
use app\common\model\sv\SvDySearchTask;
use app\common\model\sv\SvDySearchTaskDevice;
use app\common\traits\DySearchTaskTrait;
use app\common\workerman\rpa\BaseMessageHandler;
use app\common\workerman\rpa\WorkerEnum;

class TaskCompletedHandler extends BaseMessageHandler
{
    use DySearchTaskTrait;

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
            $this->payload['reply'] = $this->completeTask($content);
            $this->sendResponse($uid, $this->payload, $this->payload['reply']);
        } catch (\Exception $e) {
            $this->setLog('抖音获客任务完成处理异常：' . $e->getMessage(), 'dy_search');
            $this->payload['reply'] = $e->getMessage();
            $this->payload['code'] = WorkerEnum::RPA_DEVICE_DY_SEARCH_TASK_FAIL;
            $this->payload['type'] = WorkerEnum::RPA_DY_SEARCH_TASK_COMPLETED;
            $this->payload['content'] = [
                'code' => WorkerEnum::RPA_DEVICE_DY_SEARCH_TASK_FAIL,
                'msg' => '任务完成处理异常：' . $e->getMessage(),
                'deviceId' => $this->payload['deviceId'] ?? '',
            ];
            $this->sendError($this->connection, $this->payload);
        } finally {
            unset($content);
        }
    }

    /**
     * @param array<string, mixed> $content
     * @return array<string, mixed>
     */
    private function completeTask(array $content): array
    {
        $taskId = (int)($content['task_id'] ?? 0);
        $deviceCode = (string)($content['deviceId'] ?? $this->payload['deviceId'] ?? '');
        $bindId = (int)($content['bind_id'] ?? 0);

        if ($taskId === 0) {
            $this->setLog('抖音获客完成上报缺少 task_id，已拒绝 device=' . $deviceCode, 'dy_search');
            throw new \Exception('获客任务不存在');
        }

        $task = SvDySearchTask::where('id', $taskId)->findOrEmpty();
        if ($task->isEmpty()) {
            throw new \Exception('获客任务不存在');
        }

        $bind = SvDySearchTaskDevice::where('task_id', $taskId);
        if ($bindId > 0) {
            $bind = $bind->where('id', $bindId);
        } else {
            $bind = $bind->where('device_code', $deviceCode);
        }
        $bind = $bind->findOrEmpty();
        if ($bind->isEmpty()) {
            throw new \Exception('设备绑定不存在');
        }

        $bind->status = 3;
        $bind->update_time = time();
        $bind->save();

        self::dySearchMaybeFinishMainTask($taskId);
        $this->setLog('抖音获客设备绑定已完成 task_id=' . $taskId . ' device=' . $deviceCode . ' bind_id=' . (int)$bind->id, 'dy_search');

        return [
            'deviceId' => $deviceCode,
            'task_id' => $taskId,
            'bind_id' => (int)$bind->id,
            'status' => 3,
            'time' => date('Y-m-d H:i:s'),
            'msg' => '任务完成设置成功',
        ];
    }
}

<?php

namespace app\common\Jobs;

use app\common\service\hotspot\HotspotLog;
use app\common\service\hotspot\TaskService;
use app\common\service\hotspot\VideoService;
use think\queue\Job;

class HotspotVideoDispatchJob
{
    public const MAX_ERROR_ATTEMPTS = 2;
    public const ERROR_DELAY = 30;

    public function fire(Job $job, array $data): void
    {
        $taskNo = (string)($data['task_no'] ?? '');
        $userId = (int)($data['user_id'] ?? 0);
        if ($taskNo === '') {
            HotspotLog::write('热点下发队列参数缺失，已丢弃');
            $job->delete();
            return;
        }

        try {
            VideoService::dispatchOne($taskNo, $userId);
            $job->delete();
        } catch (\Throwable $e) {
            HotspotLog::exception('热点下发队列异常：任务号=' . $taskNo, $e);
            if ($job->attempts() >= self::MAX_ERROR_ATTEMPTS) {
                $this->failIfUnbound($taskNo, $userId);
                $job->delete();
                return;
            }
            $job->release(self::ERROR_DELAY);
        }
    }

    private function failIfUnbound(string $taskNo, int $userId): void
    {
        $task = TaskService::detail($taskNo, $userId);
        if ($task === null) {
            HotspotLog::write('热点下发队列最终失败，任务已不存在：任务号=' . $taskNo);
            return;
        }
        if (TaskService::hasBoundShanjian($task)) {
            HotspotLog::write('热点下发队列最终失败，但已绑定闪剪，不标记失败：任务号=' . $taskNo);
            return;
        }
        $options = is_array($task['options'] ?? null) ? $task['options'] : [];
        if (TaskService::isExpiredDispatchLock($options, VideoService::DISPATCH_LOCK_TTL) === false
            && (string)($options['dispatch_status'] ?? '') === 'dispatching') {
            HotspotLog::write('热点下发队列最终失败，但任务正在下发，不标记失败：任务号=' . $taskNo);
            return;
        }
        if (VideoService::recoverExistingIfAny($taskNo, $userId) !== null) {
            HotspotLog::write('热点下发队列最终失败，已复用闪剪订单，不标记失败：任务号=' . $taskNo);
            return;
        }
        TaskService::markFailed($taskNo, '视频下发失败，请稍后重试');
        TaskService::patchOptions($taskNo, ['dispatch_status' => 'fail']);
        HotspotLog::write('热点下发队列最终失败，已标记失败：任务号=' . $taskNo);
    }
}

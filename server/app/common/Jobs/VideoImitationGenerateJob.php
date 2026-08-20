<?php

namespace app\common\Jobs;

use app\api\logic\videoImitation\TaskLogic;
use app\common\model\videoImitation\VideoImitationTask;
use think\facade\Log;
use think\queue\Job;

/**
 * 视频复刻生成任务的队列消费
 * - 调用 TaskLogic::generate() 走原同步流程下发到第三方
 * - TaskLogic 会在构建素材池时跳过未完成/失败的转码素材,避免阻塞提交
 */
class VideoImitationGenerateJob
{
    // 业务异常时的有限重试
    const MAX_ERROR_ATTEMPTS = 3;
    const ERROR_DELAY = 30;

    public function fire(Job $job, array $data): void
    {
        $taskId = (int)($data['task_id'] ?? 0);
        $userId = (int)($data['user_id'] ?? 0);
        $rewrittenText = (string)($data['rewritten_text'] ?? '');

        if ($taskId <= 0 || $userId <= 0) {
            Log::channel('shanjian')->write("[生成Job] 参数缺失,丢弃 data=" . json_encode($data, JSON_UNESCAPED_UNICODE));
            $job->delete();
            return;
        }

        try {
            $task = VideoImitationTask::where('id', $taskId)->where('user_id', $userId)->find();
            if (!$task) {
                Log::channel('shanjian')->write("[生成Job] 任务不存在 task_id={$taskId}");
                $job->delete();
                return;
            }

            // 已下发/已完结的不再处理(generate() 内部也有这层防护,这里早返回省一次 IO)
            if ($task->status >= 3 || !empty($task->shanjian_task_id)) {
                $job->delete();
                return;
            }

            $ok = TaskLogic::generate($taskId, $userId, $rewrittenText);
            if ($ok === false) {
                $err = TaskLogic::getError() ?: '生成失败';
                Log::channel('shanjian')->write("[生成Job] generate 返回失败 task_id={$taskId} err={$err}");
                // generate() 自身已处理退款/状态回写,这里不再重试避免重复扣费
            }
            $job->delete();
        } catch (\Throwable $e) {
            Log::channel('shanjian')->write("[生成Job] 异常 task_id={$taskId} err=" . $e->getMessage());
            if ($job->attempts() >= self::MAX_ERROR_ATTEMPTS) {
                $job->delete();
            } else {
                $job->release(self::ERROR_DELAY);
            }
        }
    }
}

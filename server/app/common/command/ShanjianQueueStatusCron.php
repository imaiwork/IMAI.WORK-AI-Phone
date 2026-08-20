<?php

namespace app\common\command;

use app\api\logic\shanjian\ShanjianVideoTaskLogic;
use app\api\logic\videoImitation\TaskLogic as VideoImitationTaskLogic;
use app\common\model\shanjian\ShanjianVideoTask;
use app\common\model\videoImitation\VideoImitationTask;
use app\common\service\ShanjianQueueService;
use app\common\service\ToolsService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

/**
 * 每分钟批量同步中台闪剪单队列状态。
 */
class ShanjianQueueStatusCron extends Command
{
    private const BATCH_SIZE = 100;

    protected function configure()
    {
        $this->setName('shanjian_queue_status')
            ->setDescription('批量同步闪剪队列状态');
    }

    protected function execute(Input $input, Output $output)
    {
        $requests = [];

        $shanjianTasks = ShanjianVideoTask::where('queue_status', ShanjianQueueService::STATUS_WAITING)
            ->whereIn('status', [ShanjianVideoTask::STATUS_PENDING, ShanjianVideoTask::STATUS_PROCESSING])
            ->limit(self::BATCH_SIZE)
            ->select();
        foreach ($shanjianTasks as $task) {
            $requests[] = [
                'task_id' => (string)$task->task_id,
                'user_id' => (int)$task->user_id,
                'source' => 'shanjian_video_task',
            ];
        }

        $imitationTasks = VideoImitationTask::where('queue_status', ShanjianQueueService::STATUS_WAITING)
            ->where('status', 2)
            ->limit(self::BATCH_SIZE)
            ->select();
        foreach ($imitationTasks as $task) {
            $requests[] = [
                'task_id' => (string)$task->id,
                'user_id' => (int)$task->user_id,
                'source' => 'video_imitation_task',
            ];
        }

        if (empty($requests)) {
            return 0;
        }

        try {
            $synced = 0;
            foreach (array_chunk($requests, self::BATCH_SIZE) as $batch) {
                $response = ToolsService::Shanjian()->queueStatus([
                    'task_ids' => array_values(array_column($batch, 'task_id')),
                ]);
                Log::channel('shanjianQueue')->write('[闪剪队列轮询结果] ' . json_encode($response));
                $items = ShanjianQueueService::normalizeBatchResponse($response);
                foreach ($items as $item) {
                    $source = $item['source'] ?? '';
                    foreach ($batch as $request) {
                        if ($source === '' && $request['task_id'] === $item['task_id']) {
                            $source = $request['source'];
                            break;
                        }
                    }
                    if ($source === 'shanjian_video_task') {
                        ShanjianVideoTaskLogic::handleQueueStatus($item['task_id'], $item);
                    } elseif ($source === 'video_imitation_task') {
                        VideoImitationTaskLogic::handleQueueStatus($item['task_id'], $item);
                    }
                    $synced++;
                }
            }
            $output->writeln('同步闪剪队列状态：' . $synced);
            return 0;
        } catch (\Throwable $e) {
            Log::channel('shanjian')->write('[闪剪队列轮询] ' . $e->getMessage());
            $output->writeln('同步闪剪队列状态失败：' . $e->getMessage());
            return 1;
        }
    }
}

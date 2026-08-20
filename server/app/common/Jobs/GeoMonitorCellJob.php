<?php

namespace app\common\Jobs;

use app\common\service\geo\GeoMonitorCellService;
use think\facade\Log;
use think\queue\Job;

/**
 * 残留队列兼容:新路径已改为 geo_monitor_cron 落库执行。
 * 上线瞬间 Redis 里未消费的 Job 仍走同一套 runOne,避免丢任务或双扣费。
 */
class GeoMonitorCellJob
{
    public function fire(Job $job, $data)
    {
        $data = is_array($data) ? $data : [];
        try {
            GeoMonitorCellService::runOne($data);
            $job->delete();
        } catch (\Throwable $e) {
            Log::error('GeoMonitorCellJob 失败: ' . $e->getMessage(), ['data' => $data, 'attempts' => $job->attempts()]);
            if ($job->attempts() < 2) {
                $job->release(300);
            } else {
                GeoMonitorCellService::writebackBatchProgress($data, 'failed');
                $job->delete();
            }
        }
    }

    public function failed($data)
    {
        Log::error('GeoMonitorCellJob 最终失败', $data ?: []);
    }
}

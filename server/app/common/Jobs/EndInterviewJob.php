<?php

namespace app\common\Jobs;

use app\api\logic\InterviewLogic;
use think\facade\Log;
use think\queue\Job;

/**
 * 兼容 Redis 残留队列任务；新流程由 InterviewLogic::analysisCron 定时执行。
 */
class EndInterviewJob
{
    public function handle(Job $job, $data)
    {
        $interviewId = (int)$data;
        try {
            InterviewLogic::runAnalysis($interviewId);
        } catch (\Throwable $e) {
            Log::error("面试结束任务处理失败，面试ID: {$interviewId}，错误信息: {$e->getMessage()}");
            InterviewLogic::markAnalysisFailed($interviewId, $e->getMessage());
        }
        // 成功/失败都删除，避免与定时任务双通道重复执行
        $job->delete();
    }
}

<?php

namespace app\common\Jobs;

use app\common\service\ffmpeg\MaterialSliceBatchService;
use think\facade\Log;
use think\queue\Job;

class MaterialSliceBatchJob
{
    public function fire(Job $job, array $data): void
    {
        $batchNo = (string)($data['batch_no'] ?? '');
        if ($batchNo === '') {
            $job->delete();
            return;
        }
        try {
            (new MaterialSliceBatchService())->process($batchNo);
        } catch (\Throwable $e) {
            Log::channel('video_slice')->error("[素材切割] batch_no={$batchNo} error=" . $e->getMessage());
        } finally {
            $job->delete();
        }
    }
}

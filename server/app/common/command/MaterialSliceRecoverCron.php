<?php

namespace app\common\command;

use app\common\service\ffmpeg\MaterialSliceBatchService;
use app\model\VideoSlice;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class MaterialSliceRecoverCron extends Command
{
    protected function configure()
    {
        $this->setName('material:split-recover')
            ->setDescription('回收超时的素材批量分割任务并退款');
    }

    protected function execute(Input $input, Output $output)
    {
        $timeout = max(600, (int)config('video_slice.batch_timeout', 3600));
        $deadline = time() - $timeout;
        // 仅回收带 batch_no 的素材切割任务，避免误伤旧 VideoSliceJob 记录
        $batches = VideoSlice::whereIn('status', [
                VideoSlice::STATUS_PENDING,
                VideoSlice::STATUS_PROCESSING,
            ])
            ->whereNotNull('batch_no')
            ->where('batch_no', '<>', '')
            ->where('update_time', '>', 0)
            ->where('update_time', '<', $deadline)
            ->limit(50)
            ->select();

        $service = new MaterialSliceBatchService();
        foreach ($batches as $batch) {
            $service->failBatch((string)$batch->batch_no, '素材分割任务中断或处理超时');
        }
        $output->writeln('recovered=' . count($batches));
        return 0;
    }
}

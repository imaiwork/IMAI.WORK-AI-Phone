<?php

namespace app\common\command;

use app\common\service\hotspot\HotspotLog;
use app\common\service\hotspot\VideoService;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class HotspotVideoDispatchCron extends Command
{
    protected function configure()
    {
        $this->setName('hotspot_video_dispatch')
            ->setDescription('热点追踪视频后台下发');
    }

    protected function execute(Input $input, Output $output)
    {
        set_time_limit(VideoService::DISPATCH_LOCK_TTL);
        $dispatched = 0;
        $synced = 0;
        try {
            $dispatched = VideoService::dispatchPending(1);
        } catch (\Throwable $e) {
            $msg = '热点视频后台下发异常：' . $e->getMessage();
            $output->writeln($msg);
            HotspotLog::exception('热点视频后台下发异常', $e);
        }
        try {
            $synced = VideoService::syncCompleted(20);
        } catch (\Throwable $e) {
            $msg = '热点视频成片回写异常：' . $e->getMessage();
            $output->writeln($msg);
            HotspotLog::exception('热点视频成片回写异常', $e);
        }
        $msg = '热点视频后台下发完成：下发数=' . $dispatched . ' 回写数=' . $synced;
        $output->writeln($msg);
        HotspotLog::write($msg);
        return true;
    }
}

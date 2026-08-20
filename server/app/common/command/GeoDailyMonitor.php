<?php

namespace app\common\command;

use app\common\service\geo\GeoDailyMonitorService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

/**
 * GEO 每日自动监测(二期):对开启自动监测的品牌每天建一批 monitor_batch。
 * 由后台「定时任务」调度(建议 expression: 0 3 * * *):php think geo_daily_monitor
 * 只负责建批次,cell 由 geo_monitor_cron 执行;last_auto_date 幂等闸防重复建批。
 */
class GeoDailyMonitor extends Command
{
    protected function configure()
    {
        $this->setName('geo_daily_monitor')
            ->setDescription('GEO 每日自动监测');
    }

    protected function execute(Input $input, Output $output)
    {
        try {
            $s = GeoDailyMonitorService::runDue();
            $msg = sprintf('GEO每日监测: 项目%d 建批次cell %d 跳过%d', $s['projects'], $s['queued'], $s['skipped']);
            $output->writeln($msg);
            Log::info($msg);
        } catch (\Throwable $e) {
            $output->writeln('GEO每日监测异常: ' . $e->getMessage());
            Log::error('geo_daily_monitor: ' . $e->getMessage());
        }
        return 0;
    }
}

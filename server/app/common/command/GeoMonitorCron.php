<?php

namespace app\common\command;

use app\common\model\geo\GeoTask;
use app\common\service\geo\GeoMonitorCronService;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Log;

/**
 * GEO 监测 cell 定时执行:处理一键诊断/每日监测落库的 pending cell。
 * 由后台「定时任务」每分钟调度(expression: * * * * *):php think geo_monitor_cron
 *
 * 吞吐:单 cell 中台联网监测常态 40~60s,单进程一轮 50s 预算只消费得动 1 条,
 * 且 think crontab 调度器是单进程串行、会拖慢其他定时任务。
 * 故调度入口自派生 GEO.MONITOR_WORKERS-1 个「短命」后台 worker 并行消费:
 * 与主进程一样受 TIME_BUDGET(50s)约束,到点自行退出,下一分钟由调度重新拉起。
 * 无常驻进程,无需宝塔守护进程/额外计划任务;空闲时(无在跑批次)不派生。
 * cell 领取是批次行锁 CAS,多进程并发安全;worker 子进程带 mode=worker
 * 不再二次派生,防进程裂变。吞吐 ≈ workers × 1 cell/分钟。
 */
class GeoMonitorCron extends Command
{
    protected function configure()
    {
        $this->setName('geo_monitor_cron')
            ->setDescription('GEO 监测 cell 定时执行(每分钟)')
            ->addArgument('mode', Argument::OPTIONAL, 'worker=并行消费子进程(内部使用)', '');
    }

    protected function execute(Input $input, Output $output)
    {
        try {
            $isWorker = (string)$input->getArgument('mode') === 'worker';
            if (!$isWorker) {
                $this->spawnWorkers($output);
            }
            $s = GeoMonitorCronService::runDue();
            $msg = sprintf(
                'GEO监测cell%s: 处理%d 成功%d 失败%d 跳过%d%s',
                $isWorker ? '(worker)' : '',
                $s['handled'],
                $s['success'],
                $s['failed'],
                $s['skipped'],
                !empty($s['capped']) ? ' (已达单轮上限,剩余下轮继续)' : ''
            );
            $output->writeln($msg);
            if ($s['handled'] > 0) {
                Log::info($msg);
            }
        } catch (\Throwable $e) {
            $output->writeln('GEO监测cell异常: ' . $e->getMessage());
            Log::error('geo_monitor_cron: ' . $e->getMessage());
        }
        return 0;
    }

    /** 有活可干时,派生 workers-1 个短命后台子进程并行消费(本进程自己也算一个 worker) */
    protected function spawnWorkers(Output $output): void
    {
        $workers = max(1, (int)env('geo.monitor_workers', 3));
        if ($workers <= 1 || PHP_OS_FAMILY === 'Windows') {
            return;
        }
        // 空闲时不起子进程,避免每分钟白拉 PHP 进程
        $hasWork = GeoTask::where('task_type', 'monitor_batch')->where('status', 'running')->count() > 0;
        if (!$hasWork) {
            return;
        }
        $php = escapeshellarg(PHP_BINARY);
        $think = escapeshellarg(root_path() . 'think');
        for ($i = 1; $i < $workers; $i++) {
            // nohup + & 后台分离:调度器不等待子进程,不阻塞其他定时任务
            exec("nohup {$php} {$think} geo_monitor_cron worker > /dev/null 2>&1 &");
        }
        $output->writeln("GEO监测cell: 已派生 " . ($workers - 1) . " 个并行 worker");
    }
}

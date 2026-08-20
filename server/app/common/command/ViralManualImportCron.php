<?php

namespace app\common\command;

use app\common\service\aiPersona\ViralManualImportService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

/**
 * 爆款手动导入解析（仅 00:00-03:00）
 */
class ViralManualImportCron extends Command
{
    protected function configure()
    {
        $this->setName('viral_manual_import_cron')
            ->setDescription('爆款手动导入解析：每日00:00-03:00消费待执行队列');
    }

    protected function execute(Input $input, Output $output)
    {
        if (!ViralManualImportService::isInExecuteWindow()) {
            $output->writeln('非执行窗口（仅00:00-03:00），跳过');
            return 0;
        }

        try {
            $stats = ViralManualImportService::processPending(10);
            $msg = self::formatStatsMessage($stats);
            $output->writeln($msg);
            Log::channel('viral_manual')->write('【定时】' . $msg);
            return 0;
        } catch (\Throwable $th) {
            $output->writeln('手动导入解析异常：' . $th->getMessage());
            Log::channel('viral_manual')->write('【定时】手动导入解析异常：' . $th->getMessage() . "\n" . $th->getTraceAsString());
            return 1;
        }
    }

    private static function formatStatsMessage(array $stats): string
    {
        $reason = (string)($stats['skip_reason'] ?? '');
        if ($reason === 'quota_satisfied') {
            return '当天与次日解析配额已满足，无需再解析';
        }
        if ($reason === 'empty') {
            return '无待执行记录，跳过';
        }
        if ($reason === 'out_of_window') {
            return '非执行窗口（仅00:00-03:00），跳过';
        }

        return sprintf(
            '手动导入解析完成：processed=%d success=%d failed=%d skipped=%d',
            (int)($stats['processed'] ?? 0),
            (int)($stats['success'] ?? 0),
            (int)($stats['failed'] ?? 0),
            (int)($stats['skipped'] ?? 0)
        );
    }
}

<?php

namespace app\common\command;

use app\common\model\sv\SvDeviceLog;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\facade\Log;

/**
 * 清理过期设备日志（sv_device_log）
 */
class SvDeviceLogCleanCron extends Command
{
    private const DEFAULT_DAYS = 30;
    private const DEFAULT_BATCH = 1000;
    private const DEFAULT_MAX_BATCHES = 200;

    protected function configure()
    {
        $this->setName('device_log_clean')
            ->setDescription('清理过期设备日志，默认保留最近30天')
            ->addOption('days', null, Option::VALUE_OPTIONAL, '保留天数', self::DEFAULT_DAYS)
            ->addOption('batch', null, Option::VALUE_OPTIONAL, '每批删除条数', self::DEFAULT_BATCH)
            ->addOption('max-batches', null, Option::VALUE_OPTIONAL, '单次最多批次数', self::DEFAULT_MAX_BATCHES)
            ->addOption('dry-run', null, Option::VALUE_NONE, '只统计不删除');
    }

    protected function execute(Input $input, Output $output)
    {
        $start = microtime(true);
        $days = (int)($input->getOption('days') ?: self::DEFAULT_DAYS);
        $batch = (int)($input->getOption('batch') ?: self::DEFAULT_BATCH);
        $maxBatches = (int)($input->getOption('max-batches') ?: self::DEFAULT_MAX_BATCHES);
        $dryRun = (bool)$input->getOption('dry-run');

        if ($days < 1) {
            $days = self::DEFAULT_DAYS;
        }
        if ($batch < 1 || $batch > 5000) {
            $batch = self::DEFAULT_BATCH;
        }
        if ($maxBatches < 1 || $maxBatches > 2000) {
            $maxBatches = self::DEFAULT_MAX_BATCHES;
        }

        $cutoffDay = date('Y-m-d', strtotime("-{$days} days"));
        $output->writeln("设备日志清理开始：保留{$days}天，截止日={$cutoffDay}，batch={$batch}，maxBatches={$maxBatches}" . ($dryRun ? '，dry-run' : ''));

        try {
            $pending = (int)SvDeviceLog::where('day', '<', $cutoffDay)->count();
            if ($dryRun) {
                $msg = "设备日志清理预览：截止日={$cutoffDay}，待删除约{$pending}条";
                $output->writeln($msg);
                $this->writeLog($msg);
                return 0;
            }

            if ($pending <= 0) {
                $msg = "设备日志清理完成：无过期数据，截止日={$cutoffDay}";
                $output->writeln($msg);
                $this->writeLog($msg);
                return 0;
            }

            $deleted = 0;
            $batches = 0;
            while ($batches < $maxBatches) {
                $ids = SvDeviceLog::where('day', '<', $cutoffDay)
                    ->order('id', 'asc')
                    ->limit($batch)
                    ->column('id');

                if (empty($ids)) {
                    break;
                }

                $affected = (int)SvDeviceLog::whereIn('id', $ids)->delete();
                $deleted += $affected;
                $batches++;

                if ($affected < $batch) {
                    break;
                }
            }

            $elapsed = round(microtime(true) - $start, 2);
            $remainHint = ($batches >= $maxBatches) ? '，已达单次批次数上限，剩余下次继续' : '';
            $msg = "设备日志清理完成：截止日={$cutoffDay}，删除{$deleted}条，批次{$batches}，耗时{$elapsed}秒{$remainHint}";
            $output->writeln($msg);
            $this->writeLog($msg);
            return 0;
        } catch (\Throwable $e) {
            $err = '设备日志清理失败：' . $e->getMessage();
            $output->writeln($err);
            $this->writeLog($err);
            throw $e;
        }
    }

    private function writeLog(string $message): void
    {
        try {
            Log::channel('crontab')->write($message);
        } catch (\Throwable $e) {
            Log::write($message);
        }
    }
}

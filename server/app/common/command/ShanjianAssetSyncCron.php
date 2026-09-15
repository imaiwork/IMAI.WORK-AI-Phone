<?php

namespace app\common\command;

use app\common\service\ShanjianAssetReportService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Cache;
use think\facade\Log;

/**
 * 闪剪多 key 池：存量形象/音色批量上报中台（资产级 key 绑定）。
 * Redis 真游标增量推进（详见 ShanjianAssetReportService），实时上报漏报的行也由本任务兜底。
 */
class ShanjianAssetSyncCron extends Command
{
    private const RUNNING_LOCK_KEY = 'shanjian_asset_report:running';
    private const RUNNING_LOCK_TTL = 600;

    protected function configure()
    {
        $this->setName('shanjian_asset_sync')->setDescription('上报存量闪剪形象/音色到中台资产级key绑定');
    }

    protected function execute(Input $input, Output $output)
    {
        $lockValue = (getmypid() ?: 0) . ':' . time();
        $lockAcquired = false;

        try {
            $lockAcquired = $this->acquireRunningLock($lockValue);
            if (!$lockAcquired) {
                $message = '闪剪资产上报正在执行，跳过本轮';
                $output->writeln($message);
                Log::channel('crontab')->info($message);
                return true;
            }

            $result = ShanjianAssetReportService::syncBatch();
            $message = '闪剪资产上报完成: ' . json_encode($result, JSON_UNESCAPED_UNICODE);
            $output->writeln($message);
            Log::channel('crontab')->info($message);
            return true;
        } catch (\Throwable $e) {
            $message = '闪剪资产上报失败: ' . $e->getMessage();
            Log::channel('crontab')->error($message);
            $output->writeln($message);
            return false;
        } finally {
            if ($lockAcquired) {
                $this->releaseRunningLock($lockValue);
            }
        }
    }

    private function acquireRunningLock(string $lockValue): bool
    {
        try {
            $redis = Cache::store('redis')->handler();
            if (!$redis->setnx(self::RUNNING_LOCK_KEY, $lockValue)) {
                return false;
            }
            $redis->expire(self::RUNNING_LOCK_KEY, self::RUNNING_LOCK_TTL);
            return true;
        } catch (\Throwable $e) {
            Log::channel('crontab')->error('闪剪资产上报获取运行锁失败: ' . $e->getMessage());
            return false;
        }
    }

    private function releaseRunningLock(string $lockValue): void
    {
        try {
            $redis = Cache::store('redis')->handler();
            if ($redis->get(self::RUNNING_LOCK_KEY) === $lockValue) {
                $redis->del(self::RUNNING_LOCK_KEY);
            }
        } catch (\Throwable $e) {
            Log::channel('crontab')->error('闪剪资产上报释放运行锁失败: ' . $e->getMessage());
        }
    }
}

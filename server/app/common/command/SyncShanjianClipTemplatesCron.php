<?php

namespace app\common\command;

use app\common\service\ShanjianClipTemplateSyncService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Cache;
use think\facade\Log;

class SyncShanjianClipTemplatesCron extends Command
{
    private const RUNNING_LOCK_KEY = 'sync_shanjian_clip_templates:running';
    private const RUNNING_LOCK_TTL = 3300;

    protected function configure()
    {
        $this->setName('sync_shanjian_clip_templates')->setDescription('同步剪辑模板');
    }

    protected function execute(Input $input, Output $output)
    {
        $lockValue = (getmypid() ?: 0) . ':' . time();
        $lockAcquired = false;

        try {
            $lockAcquired = $this->acquireRunningLock($lockValue);
            if (!$lockAcquired) {
                $message = '同步剪辑模板正在执行，跳过本轮';
                $output->writeln($message);
                Log::channel('crontab')->info($message);
                return true;
            }

            $ok = true;
            try {
                $result = ShanjianClipTemplateSyncService::syncFromPlatform();
                $partialFailed = ($result['failed'] ?? 0) > 0;
                $message = ($partialFailed ? '同步剪辑模板部分失败(已跳过失败条目): ' : '同步剪辑模板成功: ')
                    . json_encode($result, JSON_UNESCAPED_UNICODE);
                $output->writeln($message);
                if ($partialFailed) {
                    Log::channel('crontab')->error($message);
                } else {
                    Log::channel('crontab')->info($message);
                }
            } catch (\Throwable $e) {
                $ok = false;
                $message = '同步剪辑模板失败: ' . $e->getMessage();
                $output->writeln($message);
                Log::channel('crontab')->error($message);
            }

            // 爆款玩法模板走同一个定时任务（2026-09-02）；两域互不阻塞
            try {
                $playResult = \app\common\service\PlayVideoTemplateSyncService::syncFromTemplateCenter();
                $partialFailed = ($playResult['failed'] ?? 0) > 0;
                $message = ($partialFailed ? '同步玩法模板部分失败(已跳过失败条目): ' : '同步玩法模板成功: ')
                    . json_encode($playResult, JSON_UNESCAPED_UNICODE);
                $output->writeln($message);
                if ($partialFailed) {
                    Log::channel('crontab')->error($message);
                } else {
                    Log::channel('crontab')->info($message);
                }
            } catch (\Throwable $e) {
                $ok = false;
                $message = '同步玩法模板失败: ' . $e->getMessage();
                $output->writeln($message);
                Log::channel('crontab')->error($message);
            }

            return $ok;
        } catch (\Throwable $e) {
            $message = '同步剪辑模板失败: ' . $e->getMessage();
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
            Log::channel('crontab')->error('同步剪辑模板获取运行锁失败: ' . $e->getMessage());
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
            Log::channel('crontab')->error('同步剪辑模板释放运行锁失败: ' . $e->getMessage());
        }
    }
}

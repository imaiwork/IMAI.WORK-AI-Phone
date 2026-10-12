<?php

namespace app\common\command;

use app\common\service\SystemMusicSyncService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Cache;
use think\facade\Log;

class SyncSystemMusicCron extends Command
{
    private const RUNNING_LOCK_KEY = 'sync_system_music:running';
    private const RUNNING_LOCK_TTL = 600;

    protected function configure()
    {
        $this->setName('sync_system_music')->setDescription('同步系统曲库');
    }

    protected function execute(Input $input, Output $output)
    {
        $lockValue = (getmypid() ?: 0) . ':' . time();
        $lockAcquired = false;

        try {
            $lockAcquired = $this->acquireRunningLock($lockValue);
            if (!$lockAcquired) {
                $message = '同步系统曲库正在执行，跳过本轮';
                $output->writeln($message);
                Log::channel('crontab')->info($message);
                return true;
            }

            $result = SystemMusicSyncService::syncFromTemplateCenter();
            $message = '同步系统曲库成功: ' . json_encode($result, JSON_UNESCAPED_UNICODE);
            $output->writeln($message);
            Log::channel('crontab')->info($message);
            return true;
        } catch (\Throwable $e) {
            $message = '同步系统曲库失败: ' . $e->getMessage();
            $output->writeln($message);
            Log::channel('crontab')->error($message);
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
            Log::channel('crontab')->error('同步系统曲库获取运行锁失败: ' . $e->getMessage());
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
            Log::channel('crontab')->error('同步系统曲库释放运行锁失败: ' . $e->getMessage());
        }
    }
}

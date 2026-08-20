<?php

namespace app\common\command;

use app\api\logic\videoImitation\VideoImitationLogic;
use app\common\model\videoImitation\VideoImitationTask;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Cache;
use think\facade\Log;

/**
 * 爆款复刻图文提取超时回收：PARSING 超 30 分钟标 FAIL，供重试
 */
class VideoImitationParseRecoverCron extends Command
{
    private const RUNNING_LOCK_KEY = 'video_imitation_parse_recover_cron:running';
    private const RUNNING_LOCK_TTL = 120;
    private const BATCH_SIZE = 20;

    protected function configure()
    {
        $this->setName('video_imitation_parse_recover_cron')
            ->setDescription('手动-爆款复刻图文提取超时标失败');
    }

    protected function execute(Input $input, Output $output)
    {
        return self::runOnce($output) ? 0 : 1;
    }

    public static function runOnce(?Output $output = null): bool
    {
        $lockValue = (getmypid() ?: 0) . ':' . microtime(true);
        if (!self::acquireRunningLock($lockValue)) {
            self::log('跳过：运行锁占用中');
            return true;
        }

        $failed = 0;
        $skipped = 0;
        $locked = 0;

        try {
            $staleBefore = time() - VideoImitationLogic::PARSE_STALE_SECONDS;
            $tasks = VideoImitationTask::where('media_type', VideoImitationTask::MEDIA_TYPE_IMAGE_TEXT)
                ->where('status', VideoImitationTask::STATUS_PARSING)
                ->where('update_time', '<=', $staleBefore)
                ->order('id', 'asc')
                ->limit(self::BATCH_SIZE)
                ->select();
            if ($tasks->isEmpty()) {
                return true;
            }
            self::log(sprintf(
                '开始扫描 stale_before=%d stale_seconds=%d count=%d',
                $staleBefore,
                VideoImitationLogic::PARSE_STALE_SECONDS,
                count($tasks)
            ));

            foreach ($tasks as $task) {
                $taskId = (int)$task->id;
                $result = VideoImitationLogic::recoverStaleImageTextParse($task);
                $action = (string)($result['action'] ?? 'skip');
                self::log(sprintf('回收结果 task_id=%d action=%s', $taskId, $action));
                if ($action === 'fail') {
                    $failed++;
                } elseif ($action === 'locked') {
                    $locked++;
                } else {
                    $skipped++;
                }
            }

            $summary = sprintf('本轮完成 fail=%d locked=%d skipped=%d', $failed, $locked, $skipped);
            self::log($summary);
            if ($output) {
                $output->writeln('图文提取超时回收: ' . $summary);
            }
            return true;
        } finally {
            self::releaseRunningLock($lockValue);
        }
    }

    private static function acquireRunningLock(string $lockValue): bool
    {
        try {
            $redis = Cache::store('redis')->handler();
            return (bool)$redis->set(
                self::RUNNING_LOCK_KEY,
                $lockValue,
                ['nx', 'ex' => self::RUNNING_LOCK_TTL]
            );
        } catch (\Throwable $th) {
            self::log('获取运行锁失败：' . $th->getMessage());
            return false;
        }
    }

    private static function releaseRunningLock(string $lockValue): void
    {
        try {
            $redis = Cache::store('redis')->handler();
            $script = <<<'LUA'
if redis.call('get', KEYS[1]) == ARGV[1] then
    return redis.call('del', KEYS[1])
end
return 0
LUA;
            $redis->eval($script, [self::RUNNING_LOCK_KEY, $lockValue], 1);
        } catch (\Throwable $th) {
            self::log('释放运行锁失败：' . $th->getMessage());
        }
    }

    private static function log(string $message): void
    {
        Log::channel('manual_2img')->write('[parse_recover_cron] ' . $message);
    }
}

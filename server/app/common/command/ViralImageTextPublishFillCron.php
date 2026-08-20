<?php

namespace app\common\command;

use app\common\service\aiPersona\ViralImageTextPublishFillService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Cache;
use think\facade\Log;

/**
 * 按当天空闲时段，从跨天未使用图文仿写库存（id ASC）生成发布记录。
 */
class ViralImageTextPublishFillCron extends Command
{
    private const RUNNING_LOCK_KEY = 'viral_image_text_publish_fill_cron:running';
    private const RUNNING_LOCK_TTL = 300;

    protected function configure()
    {
        $this->setName('viral_image_text_publish_fill_cron')
            ->setDescription('图文发布填坑：按未使用仿写库存 id 升序填充当天发布时段');
    }

    protected function execute(Input $input, Output $output)
    {
        return self::runOnce($output) ? 0 : 1;
    }

    public static function runOnce(?Output $output = null): bool
    {
        $lockValue = (getmypid() ?: 0) . ':' . microtime(true);
        if (!self::acquireRunningLock($lockValue)) {
            if ($output) {
                $output->writeln('图文发布填坑任务正在执行，跳过');
            }
            return true;
        }

        try {
            $stats = ViralImageTextPublishFillService::runOnce();
            $msg = sprintf(
                '图文发布填坑完成：groups=%d created=%d failed=%d skipped=%d',
                (int)($stats['groups'] ?? 0),
                (int)($stats['created'] ?? 0),
                (int)($stats['failed'] ?? 0),
                (int)($stats['skipped'] ?? 0)
            );
            if ($output) {
                $output->writeln($msg);
            }
            Log::channel('auto')->write($msg, 'create');
            foreach (($stats['errors'] ?? []) as $error) {
                if ($error !== '') {
                    Log::channel('auto')->write('图文发布填坑分组提示：' . $error, 'create');
                }
            }
            return true;
        } catch (\Throwable $th) {
            $msg = '图文发布填坑异常：' . $th->getMessage();
            if ($output) {
                $output->writeln($msg);
            }
            Log::channel('auto')->write($msg . "\n" . $th->getTraceAsString(), 'create');
            return false;
        } finally {
            self::releaseRunningLock($lockValue);
        }
    }

    private static function acquireRunningLock(string $lockValue): bool
    {
        try {
            $redis = Cache::store('redis')->handler();
            return (bool)$redis->set(self::RUNNING_LOCK_KEY, $lockValue, ['nx', 'ex' => self::RUNNING_LOCK_TTL]);
        } catch (\Throwable $th) {
            Log::channel('auto')->write('图文发布填坑获取运行锁失败：' . $th->getMessage(), 'create');
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
            Log::channel('auto')->write('图文发布填坑释放运行锁失败：' . $th->getMessage(), 'create');
        }
    }
}

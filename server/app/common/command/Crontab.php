<?php


namespace app\common\command;

use app\common\enum\CrontabEnum;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use Cron\CronExpression;
use think\facade\Console;
use app\common\model\Crontab as CrontabModel;
use think\facade\Log;

/**
 * 定时任务
 * Class Crontab
 * @package app\command
 */
class Crontab extends Command
{
    protected function configure()
    {
        $this->setName('crontab')
            ->setDescription('定时任务');
    }

    protected function execute(Input $input, Output $output)
    {

        $lists = CrontabModel::where('status', CrontabEnum::START)->select()->toArray();
        if (empty($lists)) {
            return false;
        }
        $time =  time();
        foreach ($lists as $item) {
            if (empty($item['last_time'])) {
                $lastTime = (new CronExpression($item['expression']))
                    ->getNextRunDate()
                    ->getTimestamp();
                CrontabModel::where('id', $item['id'])->update([
                    'last_time' => $lastTime,
                ]);
                continue;
            }

            $nextTime = (new CronExpression($item['expression']))
                ->getNextRunDate($item['last_time'])
                ->getTimestamp();

            if ($nextTime > $time) {
                // 未到时间，不执行
                continue;
            }
            // 开始执行
            self::start($item);
        }
    }

    public static function start($item)
    {
        // 单任务互斥锁：last_time 要等任务跑完才写回，一个跑 5 分钟的任务会被后面 4 次 crontab 调度
        // 各再拉起一份并行跑（重复扣费 / 重复发起合成）。按任务加锁而不是整个 crontab 一把锁，
        // 一个慢任务只挡住自己，不拖住其它任务。抢不到锁不动 last_time，下一分钟再试。
        $lockKey = 'crontab:running:' . $item['id'];
        $lock = self::acquireLock($lockKey, self::LOCK_TTL);
        if ($lock === false) {
            Log::channel('crontab')->info('定时任务' . $item['name'] . ' 上一轮仍在执行，本轮跳过');
            return;
        }

        // 开始执行
        $startTime = microtime(true);
        try {
            $params = explode(' ', $item['params']);
            if (is_array($params) && !empty($item['params'])) {
                Console::call($item['command'], $params);
            } else {
                Console::call($item['command']);
            }
            // 清除错误信息
            CrontabModel::where('id', $item['id'])->update(['error' => '']);
        } catch (\Throwable $e) {
            // 必须是 Throwable 不能只catch Exception：任务里抛 \Error（如上游返回异常结构后的 TypeError）
            // 会穿透出去打断 execute() 的整个 foreach，排在它后面的定时任务当天一次都跑不了。
            // 2026-09-08 19:04~09-09 09:42 就是这么炸的：#22 视频号获客队列加微信(每分钟)
            // 因 Coze 模型下线报错，把 #68 自动合成视频等后续任务全拖停，整晚零出片。
            Log::channel('crontab')->error("定时任务".$item['name']."执行失败".$e->getMessage()."\n");

            // 只记录错误信息，不改 status：execute() 只捞 START，一旦置成 ERROR 这条任务就再也不会被调度，
            // 等于「报一次错永久停跑」，上游偶发超时也会把整条业务线停掉。
            // 是否需要人工介入由 error 字段和日志告警判断，不靠停跑表达。
            CrontabModel::where('id', $item['id'])->update([
                'error' => $e->getMessage(),
            ]);
        } finally {
            try {
                $endTime = microtime(true);
                // 本次执行时间
                $useTime = round(($endTime - $startTime), 2);
                // 最大执行时间
                $maxTime = max($useTime, $item['max_time']);
                // 更新最后执行时间
                CrontabModel::where('id', $item['id'])->update([
                    'last_time' => time(),
                    'time' => $useTime,
                    'max_time' => $maxTime
                ]);
            } finally {
                // 写库抛异常也要放锁，否则要等 LOCK_TTL 才过期，期间该任务每分钟都被跳过。
                // 放锁必须在 last_time 写回之后，否则下一轮 crontab 会读到旧 last_time 立刻再跑一遍。
                self::releaseLock($lock);
            }
        }
    }

    /**
     * 锁的兜底过期时间：进程被 kill -9 时锁不会被 finally 释放，靴子落地要靠 TTL。
     * 整张表在同一个 /crontab 请求里跑，nginx fastcgi_read_timeout / php-fpm request_terminate_timeout
     * 都是 600s，超时整个进程被杀、finally 不执行。TTL 只需略大于 600：请求活不过 600s，不会并行；
     * 设大了每次被掐都会让该任务空停一整个 TTL。2026-09-15 凌晨 TTL=3600 时自动合成视频被锁成
     * 一小时只跑一次（整晚 10 次，前一晚 38 次）。站点调大上述超时时这里要同步调。
     */
    private const LOCK_TTL = 620;

    /**
     * 拿 redis 互斥锁。返回锁句柄（redis 实例 + 完整 key）；被占返回 false；
     * redis 不可用时返回 null，调用方按无锁执行——不能因为 redis 挂了把所有定时任务停掉。
     */
    private static function acquireLock(string $key, int $ttl)
    {
        try {
            $redis = \think\facade\Cache::store('concurrent_redis')->handler();
            $fullKey = (string)config('cache.stores.concurrent_redis.prefix', '') . $key;
            // SET NX EX 一条命令原子拿锁，不会留下没有过期时间的锁
            if (!$redis->set($fullKey, time(), ['nx', 'ex' => $ttl])) {
                return false;
            }
            return [$redis, $fullKey];
        } catch (\Throwable $e) {
            Log::channel('crontab')->warning('定时任务互斥锁不可用，按无锁执行: ' . $e->getMessage());
            return null;
        }
    }

    private static function releaseLock($lock): void
    {
        if (!is_array($lock)) {
            return;
        }
        try {
            $lock[0]->del($lock[1]);
        } catch (\Throwable $e) {
        }
    }
}

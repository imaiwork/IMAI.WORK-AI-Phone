<?php

namespace app\common\command;

use app\api\logic\videoImitation\TaskLogic;
use app\common\model\videoImitation\VideoImitationTask;
use app\common\service\videoImitation\VideoImitationImageLifecycle;
use app\common\service\videoImitation\VideoImitationImageRewriteService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Cache;
use think\facade\Log;

/**
 * 爆款复刻小红书图文图片改写
 */
class VideoImitationImageRewriteCron extends Command
{
    private const RUNNING_LOCK_KEY = 'video_imitation_image_rewrite_cron:running';
    private const BATCH_SIZE = 3;
    private const RECOVER_BATCH_SIZE = 5;
    private const AUTO_CONFIRM_BATCH_SIZE = 20;

    protected function configure()
    {
        $this->setName('video_imitation_image_rewrite_cron')
            ->setDescription('手动-爆款复刻图文图片改写状态轮询');
    }

    protected function execute(Input $input, Output $output)
    {
        return self::runOnce($output) ? 0 : 1;
    }

    public static function runOnce(?Output $output = null): bool
    {
        $startedMicrotime = microtime(true);
        $lockValue = (getmypid() ?: 0) . ':' . microtime(true);
        if (!self::acquireRunningLock($lockValue)) {
            $holder = self::readRawLock();

            if (self::isDeadLock($holder)) {
                self::log('检测到死锁，尝试清除 holder=' . $holder['value'] . ' ttl=' . $holder['ttl']);
                self::forceClearDeadLock($holder['value']);
                usleep(100000);

                if (!self::acquireRunningLock($lockValue)) {
                    self::log('死锁清除后重新获取锁失败');
                    return true;
                }
                self::log('死锁清除成功，已获取新锁 lock=' . $lockValue);
            } else {
                self::log(sprintf(
                    '跳过：运行锁占用中 holder=%s ttl=%s lock_key=%s',
                    $holder['value'] === '' ? '(空)' : $holder['value'],
                    $holder['ttl'] < 0 ? '无' : (string)$holder['ttl'],
                    self::RUNNING_LOCK_KEY
                ));
                return true;
            }
        }
        self::log(PHP_EOL);
        self::log(sprintf(
            '开始执行 lock=%s auto_confirm_batch=%d batch=%d recover_batch=%d',
            $lockValue,
            self::AUTO_CONFIRM_BATCH_SIZE,
            self::BATCH_SIZE,
            self::RECOVER_BATCH_SIZE
        ));

        try {
            $recovered = 0;
            $handled = 0;
            $recoveredIds = [];
            $handledIds = [];
            $successIds = [];
            $failIds = [];

            // 阶段 0：超时未确认选图 → 自动确认进入 WAIT，便于同轮提交
            $onHeartbeat = static function () use ($lockValue): void {
                self::renewRunningLock($lockValue);
            };
            $autoConfirm = TaskLogic::autoConfirmExpiredImageSelections(
                self::AUTO_CONFIRM_BATCH_SIZE,
                $onHeartbeat
            );
            self::renewRunningLock($lockValue);
            self::log(sprintf(
                '自动确认选图 confirmed=%d failed=%d skipped=%d confirmed_ids=[%s] failed_ids=[%s] skipped_ids=[%s]',
                (int)($autoConfirm['confirmed'] ?? 0),
                (int)($autoConfirm['failed'] ?? 0),
                (int)($autoConfirm['skipped'] ?? 0),
                implode(',', $autoConfirm['confirmed_ids'] ?? []),
                implode(',', $autoConfirm['failed_ids'] ?? []),
                implode(',', $autoConfirm['skipped_ids'] ?? [])
            ));

            $processingTasks = VideoImitationTask::where('media_type', VideoImitationTask::MEDIA_TYPE_IMAGE_TEXT)
                ->where('image_rewrite_status', VideoImitationTask::IMAGE_REWRITE_STATUS_PROCESSING)
                ->where('task_delete', 0)
                ->order('id', 'asc')
                ->limit(self::RECOVER_BATCH_SIZE)
                ->select();

            $processingIds = [];
            foreach ($processingTasks as $task) {
                $processingIds[] = (int)$task->id;
            }
            self::log(sprintf(
                '回收扫描 processing_count=%d ids=[%s]',
                count($processingIds),
                implode(',', $processingIds)
            ));

            foreach ($processingTasks as $task) {
                $taskId = (int)$task->id;
                if (!VideoImitationImageLifecycle::canScanRewrite($task)) {
                    self::log(sprintf(
                        '回收跳过：任务已删除或不属于图文 task_id=%d task_delete=%d media_type=%d',
                        $taskId,
                        (int)$task->task_delete,
                        (int)$task->media_type
                    ));
                    continue;
                }
                $beforeStatus = (int)$task->image_rewrite_status;
                $startedAt = (int)$task->image_rewrite_started_at;
                $age = $startedAt > 0 ? max(0, time() - $startedAt) : -1;
                $expired = VideoImitationImageRewriteService::isProcessingExpired($startedAt);
                $orphanStale = VideoImitationImageRewriteService::isOrphanStale($startedAt);
                $expected = VideoImitationImageRewriteService::resolveExpectedImageCount($task);
                $doneCount = is_array($task->rewritten_images) ? count($task->rewritten_images) : 0;
                $remain = max(0, $expected - $doneCount);
                $lockHolder = self::readRawLock();
                $lockIsStale = $lockHolder['ttl'] >= 0 && $lockHolder['ttl'] < 60;

                // 硬超时：recover（未满图只标记可续跑，不再部分 SUCCESS）
                if ($expired) {
                    $ok = VideoImitationImageRewriteService::recoverExpired($task);
                    if ($ok) {
                        $recovered++;
                        $recoveredIds[] = $taskId;
                    }
                    self::log(sprintf(
                        '回收结果 task_id=%d before_status=%d started_at=%d age=%ds attempt_id=%s retry=%d expected=%d done=%d remain=%d ok=%s',
                        $taskId,
                        $beforeStatus,
                        $startedAt,
                        $age,
                        (string)$task->image_rewrite_task_id,
                        (int)$task->image_rewrite_retry_count,
                        $expected,
                        $doneCount,
                        $remain,
                        $ok ? '1' : '0'
                    ));

                    // 仍为 PROCESSING 则同轮立即续跑剩余图
                    $latest = VideoImitationTask::where('id', $taskId)->findOrEmpty();
                    if (!$latest->isEmpty()
                        && (int)$latest->image_rewrite_status === VideoImitationTask::IMAGE_REWRITE_STATUS_PROCESSING
                    ) {
                        self::renewRunningLock($lockValue);
                        self::log(sprintf(
                            '孤儿续跑开始 task_id=%d reason=hard_timeout expected=%d done=%d remain=%d attempt_id=%s',
                            $taskId,
                            VideoImitationImageRewriteService::resolveExpectedImageCount($latest),
                            is_array($latest->rewritten_images) ? count($latest->rewritten_images) : 0,
                            max(0, VideoImitationImageRewriteService::resolveExpectedImageCount($latest) - (is_array($latest->rewritten_images) ? count($latest->rewritten_images) : 0)),
                            (string)$latest->image_rewrite_task_id
                        ));
                        $resumeOk = VideoImitationImageRewriteService::sync($latest, $onHeartbeat);
                        self::renewRunningLock($lockValue);
                        $handled++;
                        $handledIds[] = $taskId;
                        if ($resumeOk) {
                            $successIds[] = $taskId;
                        } else {
                            $failIds[] = $taskId;
                        }
                        self::log(sprintf(
                            '孤儿续跑结束 task_id=%d reason=hard_timeout ok=%s',
                            $taskId,
                            $resumeOk ? '1' : '0'
                        ));
                    }
                    continue;
                }

                // 软孤儿：本进程已持锁且无心跳过久 → 直接续跑，不再空等 1800s
                if ($orphanStale) {
                    self::log(sprintf(
                        '孤儿续跑开始 task_id=%d reason=soft_orphan age=%ds expected=%d done=%d remain=%d attempt_id=%s lock_ttl=%s',
                        $taskId,
                        $age,
                        $expected,
                        $doneCount,
                        $remain,
                        (string)$task->image_rewrite_task_id,
                        $lockHolder['ttl'] < 0 ? '无' : (string)$lockHolder['ttl']
                    ));
                    self::renewRunningLock($lockValue);
                    $resumeOk = VideoImitationImageRewriteService::sync($task, $onHeartbeat);
                    self::renewRunningLock($lockValue);
                    $recovered++;
                    $recoveredIds[] = $taskId;
                    $handled++;
                    $handledIds[] = $taskId;
                    if ($resumeOk) {
                        $successIds[] = $taskId;
                    } else {
                        $failIds[] = $taskId;
                    }
                    self::log(sprintf(
                        '孤儿续跑结束 task_id=%d reason=soft_orphan ok=%s',
                        $taskId,
                        $resumeOk ? '1' : '0'
                    ));
                    continue;
                }

                if ($lockIsStale) {
                    self::log(sprintf(
                        '回收观察：运行锁即将过期但任务未达软孤儿阈值 task_id=%d lock_ttl=%d age=%ds',
                        $taskId,
                        $lockHolder['ttl'],
                        $age
                    ));
                }

                self::log(sprintf(
                    '回收跳过：未超时且未达软孤儿 task_id=%d started_at=%d age=%ds orphan_stale_after=%ds lock_ttl=%s expected=%d done=%d remain=%d',
                    $taskId,
                    $startedAt,
                    $age,
                    VideoImitationImageRewriteService::ORPHAN_STALE_SECONDS,
                    $lockHolder['ttl'] < 0 ? '无' : (string)$lockHolder['ttl'],
                    $expected,
                    $doneCount,
                    $remain
                ));
            }

            // status 放宽含 FAIL(4)：承接手动续跑脏数据 / 写库异常，避免 WAIT 饿死
            $waitingTasks = VideoImitationTask::where('media_type', VideoImitationTask::MEDIA_TYPE_IMAGE_TEXT)
                ->where('image_rewrite_status', VideoImitationTask::IMAGE_REWRITE_STATUS_WAIT)
                ->where('status', 'in', [
                    VideoImitationTask::STATUS_WAIT_CONFIRM,
                    VideoImitationTask::STATUS_GENERATING,
                    VideoImitationTask::STATUS_FAIL,
                ])
                ->where('task_delete', 0)
                ->order('id', 'asc')
                ->limit(self::BATCH_SIZE)
                ->select();

            $waitingIds = [];
            foreach ($waitingTasks as $task) {
                $waitingIds[] = (int)$task->id;
            }
            self::log(sprintf(
                '待提交扫描 wait_count=%d ids=[%s]',
                count($waitingIds),
                implode(',', $waitingIds)
            ));

            foreach ($waitingTasks as $task) {
                $taskId = (int)$task->id;
                if (!VideoImitationImageLifecycle::canScanRewrite($task)) {
                    self::log(sprintf(
                        '提交跳过：任务已删除或不属于图文 task_id=%d task_delete=%d media_type=%d',
                        $taskId,
                        (int)$task->task_delete,
                        (int)$task->media_type
                    ));
                    continue;
                }
                $userId = (int)$task->user_id;
                $selectedCount = is_array($task->selected_images) ? count($task->selected_images) : 0;
                $chargedCount = (int)$task->image_rewrite_charged_count;
                $status = (int)$task->status;
                $remarks = (string)$task->remarks;
                $isResume = $status === VideoImitationTask::STATUS_FAIL
                    || str_contains($remarks, '续跑')
                    || str_contains($remarks, '重试');

                // 脏状态：rewrite=WAIT 但主状态仍为 FAIL → 校正为待确认后再提交
                if ($status === VideoImitationTask::STATUS_FAIL) {
                    $affected = VideoImitationTask::where('id', $taskId)
                        ->where('image_rewrite_status', VideoImitationTask::IMAGE_REWRITE_STATUS_WAIT)
                        ->where('status', VideoImitationTask::STATUS_FAIL)
                        ->update([
                            'status' => VideoImitationTask::STATUS_WAIT_CONFIRM,
                            'update_time' => time(),
                        ]);
                    self::log(sprintf(
                        '手动续跑脏状态校正 task_id=%d status:4→1 affected=%d',
                        $taskId,
                        (int)$affected
                    ));
                    if ($affected > 0) {
                        $task->status = VideoImitationTask::STATUS_WAIT_CONFIRM;
                        $status = VideoImitationTask::STATUS_WAIT_CONFIRM;
                    }
                }

                // charged_count>0 且无成功图：自清零后继续 submit，避免与手动门禁形成死锁
                $rewrittenCount = is_array($task->rewritten_images) ? count($task->rewritten_images) : 0;
                if ($chargedCount > 0 && $rewrittenCount <= 0) {
                    $cleared = VideoImitationTask::where('id', $taskId)
                        ->where('image_rewrite_status', VideoImitationTask::IMAGE_REWRITE_STATUS_WAIT)
                        ->update([
                            'image_rewrite_charged_count' => 0,
                            'status' => VideoImitationTask::STATUS_WAIT_CONFIRM,
                            'update_time' => time(),
                        ]);
                    self::log(sprintf(
                        '脏WAIT自清零 charged→0 后继续提交 task_id=%d charged_before=%d affected=%d',
                        $taskId,
                        $chargedCount,
                        (int)$cleared
                    ));
                    $task->image_rewrite_charged_count = 0;
                    $task->status = VideoImitationTask::STATUS_WAIT_CONFIRM;
                    $chargedCount = 0;
                    $status = VideoImitationTask::STATUS_WAIT_CONFIRM;
                }

                $taskStarted = microtime(true);
                self::log(sprintf(
                    '提交开始 task_id=%d user_id=%d status=%d rewrite_status=%d selected=%d has_selected=%d charged=%d retry=%d billing_round=%d resume=%d remarks=%s',
                    $taskId,
                    $userId,
                    $status,
                    (int)$task->image_rewrite_status,
                    $selectedCount,
                    $selectedCount > 0 ? 1 : 0,
                    $chargedCount,
                    (int)$task->image_rewrite_retry_count,
                    max(1, (int)($task->billing_round ?? 1)),
                    $isResume ? 1 : 0,
                    mb_substr($remarks, 0, 80)
                ));

                // 新增：提交前显式续期运行锁，防止长时间 API 调用期间锁过期
                self::renewRunningLock($lockValue);

                $ok = VideoImitationImageRewriteService::sync($task, $onHeartbeat);
                $elapsedMs = (int)round((microtime(true) - $taskStarted) * 1000);
                self::renewRunningLock($lockValue);
                $handled++;
                $handledIds[] = $taskId;
                if ($ok) {
                    $successIds[] = $taskId;
                } else {
                    $failIds[] = $taskId;
                }

                $latest = VideoImitationTask::where('id', $taskId)
                    ->field('status,image_rewrite_status,image_rewrite_success_count,image_rewrite_fail_count,remarks')
                    ->findOrEmpty();
                self::log(sprintf(
                    '提交结束 task_id=%d ok=%s elapsed_ms=%d status=%s rewrite_status=%s success=%s fail=%s remarks=%s',
                    $taskId,
                    $ok ? '1' : '0',
                    $elapsedMs,
                    $latest->isEmpty() ? '-' : (string)(int)$latest->status,
                    $latest->isEmpty() ? '-' : (string)(int)$latest->image_rewrite_status,
                    $latest->isEmpty() ? '-' : (string)(int)$latest->image_rewrite_success_count,
                    $latest->isEmpty() ? '-' : (string)(int)$latest->image_rewrite_fail_count,
                    $latest->isEmpty() ? '-' : mb_substr((string)$latest->remarks, 0, 120)
                ));
            }

            $totalElapsedMs = (int)round((microtime(true) - $startedMicrotime) * 1000);
            $summary = sprintf(
                '本轮完成 自动确认=%d 回收=%d 提交=%d 成功=%d 失败=%d elapsed_ms=%d auto_confirmed_ids=[%s] recovered_ids=[%s] handled_ids=[%s] success_ids=[%s] fail_ids=[%s]',
                (int)($autoConfirm['confirmed'] ?? 0),
                $recovered,
                $handled,
                count($successIds),
                count($failIds),
                $totalElapsedMs,
                implode(',', $autoConfirm['confirmed_ids'] ?? []),
                implode(',', $recoveredIds),
                implode(',', $handledIds),
                implode(',', $successIds),
                implode(',', $failIds)
            );
            if ($output) {
                $output->writeln(sprintf(
                    '爆款复刻图文改写已自动确认: %d，已回收: %d，已提交: %d',
                    (int)($autoConfirm['confirmed'] ?? 0),
                    $recovered,
                    $handled
                ));
            }
            self::log($summary);
            return true;
        } finally {
            self::releaseRunningLock($lockValue);
            $holder = self::readRawLock();
            self::log(sprintf(
                '释放运行锁后 holder=%s ttl=%s',
                $holder['value'] === '' ? '(空)' : $holder['value'],
                $holder['ttl'] < 0 ? '无' : (string)$holder['ttl']
            ));
        }
    }

    private static function acquireRunningLock(string $lockValue): bool
    {
        try {
            $redis = Cache::store('redis')->handler();
            return (bool)$redis->set(
                self::RUNNING_LOCK_KEY,
                $lockValue,
                ['nx', 'ex' => VideoImitationImageLifecycle::REWRITE_CRON_LOCK_TTL]
            );
        } catch (\Throwable $th) {
            self::log('获取运行锁失败：' . $th->getMessage());
            return false;
        }
    }

    private static function renewRunningLock(string $lockValue): void
    {
        try {
            $redis = Cache::store('redis')->handler();
            $script = <<<'LUA'
if redis.call('get', KEYS[1]) == ARGV[1] then
    return redis.call('expire', KEYS[1], ARGV[2])
end
return 0
LUA;
            $redis->eval($script, [self::RUNNING_LOCK_KEY, $lockValue, VideoImitationImageLifecycle::REWRITE_CRON_LOCK_TTL], 1);
        } catch (\Throwable $th) {
            self::log('续期运行锁失败：' . $th->getMessage());
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

    /**
     * @return array{value: string, ttl: int}
     */
    private static function readRawLock(): array
    {
        try {
            $redis = Cache::store('redis')->handler();
            $value = $redis->get(self::RUNNING_LOCK_KEY);
            $ttl = (int)$redis->ttl(self::RUNNING_LOCK_KEY);
            return [
                'value' => is_string($value) ? $value : '',
                'ttl' => $ttl,
            ];
        } catch (\Throwable $th) {
            return ['value' => '读取失败:' . $th->getMessage(), 'ttl' => -1];
        }
    }

    /**
     * 判断是否为死锁：锁 TTL 即将过期且持有者时间戳已超过锁周期
     */
    private static function isDeadLock(array $holder): bool
    {
        if ($holder['ttl'] < 0) {
            return false;
        }

        if ($holder['ttl'] > 60) {
            return false;
        }

        $value = $holder['value'];
        if ($value === '' || strpos($value, ':') === false) {
            return false;
        }

        $parts = explode(':', $value, 2);
        if (count($parts) < 2) {
            return false;
        }

        $holderTimestamp = (float)$parts[1];
        $ageSeconds = microtime(true) - $holderTimestamp;

        return $ageSeconds > VideoImitationImageLifecycle::REWRITE_CRON_LOCK_TTL;
    }

    /**
     * 使用 Lua 脚本安全清除死锁
     */
    private static function forceClearDeadLock(string $expectedValue): void
    {
        if ($expectedValue === '') {
            return;
        }

        try {
            $redis = Cache::store('redis')->handler();
            $script = <<<'LUA'
if redis.call('get', KEYS[1]) == ARGV[1] then
    return redis.call('del', KEYS[1])
end
return 0
LUA;
            $result = $redis->eval($script, [self::RUNNING_LOCK_KEY, $expectedValue], 1);
            self::log('死锁清除结果 result=' . $result);
        } catch (\Throwable $th) {
            self::log('死锁清除异常：' . $th->getMessage());
        }
    }

    private static function log(string $message): void
    {
        Log::channel('manual_2img')->write('[cron] ' . $message);
    }
}

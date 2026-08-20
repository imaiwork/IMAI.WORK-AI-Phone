<?php

namespace app\common\Jobs;

use app\common\service\ffmpeg\MaterialService;
use app\common\service\ffmpeg\VideoSliceService;
use app\common\service\transcoding\OssMediaProcessService;
use think\facade\Cache;
use think\facade\Log;
use think\facade\Queue;
use think\queue\Job;

class VideoSliceJob
{
    const MAX_CONCURRENT = 4;
    const MAX_EXECUTE_SECONDS = 1800;
    const CIRCUIT_BREAK_THRESHOLD = 5;
    const CIRCUIT_BREAK_PAUSE = 60;
    const MAX_CPU_USAGE = 70;
    const CPU_HIGH_RELEASE_DELAY = 120;
    const CPU_CACHE_SECONDS = 3;
    /** 队列 Job 自身 attempts 上限（仅对 release 重试有效） */
    const MAX_ATTEMPTS = 10;
    /** 按 video_id 累计的真实执行失败次数上限，超过后永久放弃（不依赖 job attempts） */
    const MAX_TASK_ATTEMPTS = 10;
    const RETRY_DELAY = 60;
    const STALE_CLEAN_INTERVAL = 300;
    const CACHE_STORE = 'concurrent_redis';
    const LOCK_KEY = 'video_slice_running_';
    const CIRCUIT_KEY = 'video_slice_circuit_';
    const FAIL_COUNT_KEY = 'video_slice_fail_count_';
    const TASK_ATTEMPT_KEY = 'video_slice_task_attempt_';
    const RUNNING_COUNT_KEY = 'video_slice_running_count';
    const RUNNING_IDS_KEY = 'video_slice_running_ids';
    const RUNNING_IDS_SET_KEY = 'video_slice_running_ids_set';
    const STALE_CLEAN_KEY = 'video_slice_stale_clean_at';
    const CPU_CACHE_KEY = 'video_slice_cpu_usage_cache';

    private static float $cpuUsageCache = 0.0;
    private static int $cpuUsageCachedAt = 0;

    public function fire(Job $job, array $data): void
    {
        $videoId = (int)($data['video_id'] ?? 0);

        $taskAttempts = $videoId > 0 ? $this->getTaskAttemptCount($videoId) : 0;
        Log::channel('video_slice')->write(
            "[切片队列] 收到任务 | video_id={$videoId} job_attempts={$job->attempts()} task_attempts={$taskAttempts}/" . self::MAX_TASK_ATTEMPTS
        );
        $this->markStaleProcessingMaterialsFailed();

        try {
            if ($videoId <= 0 || empty($data['module']) || empty($data['file_path']) || empty($data['original_name'])) {
                throw new \InvalidArgumentException(
                    '切片任务参数缺失: ' . json_encode($data, JSON_UNESCAPED_UNICODE)
                );
            }

            if ($this->isTaskAttemptExhausted($videoId)) {
                $this->markFailedSafely($data);
                $job->delete();
                Log::channel('video_slice')->write(
                    "[切片队列] 单任务已达最大重试次数(" . self::MAX_TASK_ATTEMPTS . ")，不再执行 video_id={$videoId}"
                );
                return;
            }

            if ($this->isCircuitOpen($videoId)) {
                $remaining = $this->getCircuitRemainingSeconds($videoId);
                $this->delayWithoutConsumingAttempts(
                    $job,
                    $data,
                    $remaining,
                    "单任务熔断中 remaining={$remaining}s"
                );
                return;
            }

            // OSS MPS 不吃本机 CPU，跳过 CPU 节流
            if (!OssMediaProcessService::isEnabled()) {
                $cpuUsage = $this->getCpuUsage();
                if ($cpuUsage > self::MAX_CPU_USAGE) {
                    Log::channel('video_slice')->write(
                        "[切片队列] CPU使用率过高 {$cpuUsage}%，延迟 " . self::CPU_HIGH_RELEASE_DELAY . "s 执行 video_id={$videoId}"
                    );
                    $this->delayWithoutConsumingAttempts(
                        $job,
                        $data,
                        self::CPU_HIGH_RELEASE_DELAY,
                        "CPU使用率过高 {$cpuUsage}%"
                    );
                    return;
                }
            }

            if (!$this->acquireLock($videoId)) {
                Log::channel('video_slice')->write("[切片队列] 并发已满或同视频处理中，延迟30s执行 video_id={$videoId}");
                $this->delayWithoutConsumingAttempts($job, $data, 30, '并发已满或同视频处理中');
                return;
            }

            try {
                $this->handleWithTimeout($data);
            } finally {
                $this->releaseLock($videoId);
            }

            $this->resetFailCount($videoId);
            $this->resetTaskAttemptCount($videoId);
            $job->delete();
            Log::channel('video_slice')->write("[切片队列] 任务完成 video_id={$videoId}");
        } catch (VideoSliceTimeoutException $e) {
            $taskAttempts = $this->incrementTaskAttemptCount($videoId);
            $this->incrementFailCount($videoId);
            $this->markFailedSafely($data);
            $job->delete();
            Log::channel('video_slice')->write(
                "[切片队列] 任务超时，已放弃 video_id={$videoId} task_attempts={$taskAttempts}/" . self::MAX_TASK_ATTEMPTS
                    . " | " . $e->getMessage()
            );
        } catch (\Throwable $e) {
            $taskAttempts = $this->incrementTaskAttemptCount($videoId);
            $this->incrementFailCount($videoId);
            Log::channel('video_slice')->write(
                "[切片队列] 任务失败 video_id={$videoId} task_attempts={$taskAttempts}/" . self::MAX_TASK_ATTEMPTS
                    . " | " . $e->getMessage()
            );

            if ($taskAttempts >= self::MAX_TASK_ATTEMPTS || $job->attempts() >= self::MAX_ATTEMPTS) {
                $this->markFailedSafely($data);
                $job->delete();
                Log::channel('video_slice')->write(
                    "[切片队列] 重试超限，放弃任务 video_id={$videoId} task_attempts={$taskAttempts} job_attempts={$job->attempts()}"
                );
            } else {
                $delay = self::RETRY_DELAY * max(1, $job->attempts());
                $job->release($delay);
                Log::channel('video_slice')->write(
                    "[切片队列] 第{$taskAttempts}次失败后重试，{$delay}s后执行 video_id={$videoId}"
                );
            }
        }
    }

    private function handleWithTimeout(array $data): void
    {
        $deadline = time() + self::MAX_EXECUTE_SECONDS;
        VideoSliceService::setExecuteDeadline($deadline);

        if (function_exists('pcntl_async_signals')) {
            pcntl_async_signals(true);
        }

        if (function_exists('pcntl_signal') && function_exists('pcntl_alarm')) {
            pcntl_signal(SIGALRM, static function () {
                VideoSliceService::setExecuteDeadline(time());
            });
            pcntl_alarm(self::MAX_EXECUTE_SECONDS);
        }

        try {
            $this->handle($data);
        } finally {
            if (function_exists('pcntl_alarm')) {
                pcntl_alarm(0);
            }
            VideoSliceService::clearExecuteDeadline();
        }
    }

    private function handle(array $data): void
    {
        if (empty($data['video_id']) || empty($data['module']) || empty($data['file_path']) || empty($data['original_name'])) {
            throw new \InvalidArgumentException('切片任务参数缺失: ' . json_encode($data, JSON_UNESCAPED_UNICODE));
        }

        (new VideoSliceService())->handle($data);
    }

    private function delayWithoutConsumingAttempts(Job $job, array $data, int $delay, string $reason): void
    {
        $delay = max(1, $delay);
        $queueName = (string)config('video_slice.queue_name', 'video_slice');
        $jobId = Queue::later($delay, self::class, $data, $queueName);
        if ($jobId === false) {
            throw new \RuntimeException("切片任务延迟重投递失败：{$reason}");
        }

        $job->delete();
        Log::channel('video_slice')->write(
            "[切片队列] 已延迟重投递 video_id=" . (int)($data['video_id'] ?? 0) . " delay={$delay}s reason={$reason} job_id={$jobId}"
        );
    }

    private function markFailedSafely(array $data): void
    {
        try {
            (new VideoSliceService())->markFailed($data);
        } catch (\Throwable $e) {
            Log::channel('video_slice')->warning(
                "[切片队列] 标记失败状态异常 video_id=" . (int)($data['video_id'] ?? 0) . " | " . $e->getMessage()
            );
        }
    }

    private function markStaleProcessingMaterialsFailed(): void
    {
        try {
            if ($this->cache()->get(self::STALE_CLEAN_KEY)) {
                return;
            }
            $this->cache()->set(self::STALE_CLEAN_KEY, time(), self::STALE_CLEAN_INTERVAL);
            MaterialService::markStaleProcessingSlicesFailed(self::MAX_EXECUTE_SECONDS + 300);
        } catch (\Throwable $e) {
            Log::channel('video_slice')->warning("[切片队列] 清理超时分割中素材异常 | " . $e->getMessage());
        }
    }

    private function acquireLock(int $videoId): bool
    {
        if ($videoId <= 0) {
            return false;
        }

        $this->refreshRunningState();

        $ttl = self::MAX_EXECUTE_SECONDS + 60;
        $script = <<<'LUA'
if redis.call('exists', KEYS[2]) == 1 then
    return -1
end
local count = tonumber(redis.call('get', KEYS[1]) or '0')
local max = tonumber(ARGV[1])
if count >= max then
    return 0
end
local ok = redis.call('set', KEYS[2], ARGV[4], 'EX', tonumber(ARGV[3]), 'NX')
if not ok then
    return -1
end
count = redis.call('incr', KEYS[1])
redis.call('expire', KEYS[1], tonumber(ARGV[3]))
redis.call('sadd', KEYS[3], ARGV[2])
redis.call('expire', KEYS[3], tonumber(ARGV[3]))
return count
LUA;

        try {
            $result = (int)$this->redisEval($script, [
                $this->redisKey(self::RUNNING_COUNT_KEY),
                $this->redisKey(self::LOCK_KEY . $videoId),
                $this->redisKey(self::RUNNING_IDS_SET_KEY),
            ], [
                self::MAX_CONCURRENT,
                $videoId,
                $ttl,
                time(),
            ]);

            return $result > 0;
        } catch (\Throwable $e) {
            Log::channel('video_slice')->warning(
                "[切片队列] Lua获取并发锁失败，降级普通锁 video_id={$videoId} | " . $e->getMessage()
            );

            if ($this->cache()->get(self::LOCK_KEY . $videoId)) {
                return false;
            }
            if ($this->getRunningCount() >= self::MAX_CONCURRENT) {
                return false;
            }

            $this->cache()->set(self::LOCK_KEY . $videoId, time(), $ttl);
            $this->cache()->inc(self::RUNNING_COUNT_KEY);
            $this->rememberRunningVideo($videoId);
            return true;
        }
    }

    private function releaseLock(int $videoId): void
    {
        if ($videoId <= 0) {
            return;
        }

        $script = <<<'LUA'
local deleted = redis.call('del', KEYS[2])
redis.call('srem', KEYS[3], ARGV[1])
local count = tonumber(redis.call('get', KEYS[1]) or '0')
if deleted > 0 and count > 0 then
    count = redis.call('decr', KEYS[1])
end
if count < 0 then
    redis.call('set', KEYS[1], 0, 'EX', tonumber(ARGV[2]))
    count = 0
end
return count
LUA;

        try {
            $this->redisEval($script, [
                $this->redisKey(self::RUNNING_COUNT_KEY),
                $this->redisKey(self::LOCK_KEY . $videoId),
                $this->redisKey(self::RUNNING_IDS_SET_KEY),
            ], [
                $videoId,
                self::MAX_EXECUTE_SECONDS + 60,
            ]);
        } catch (\Throwable $e) {
            Log::channel('video_slice')->warning(
                "[切片队列] Lua释放并发锁失败，降级普通释放 video_id={$videoId} | " . $e->getMessage()
            );
            $this->cache()->delete(self::LOCK_KEY . $videoId);
            $this->forgetRunningVideo($videoId);
            $this->decrementRunningCount();
        }

        $this->refreshRunningState();
    }

    private function getRunningCount(): int
    {
        return (int)$this->cache()->get(self::RUNNING_COUNT_KEY, 0);
    }

    private function decrementRunningCount(): void
    {
        $count = $this->getRunningCount();
        if ($count > 0) {
            $this->cache()->dec(self::RUNNING_COUNT_KEY);
            return;
        }

        $this->cache()->set(self::RUNNING_COUNT_KEY, 0, self::MAX_EXECUTE_SECONDS + 60);
    }

    private function rememberRunningVideo(int $videoId): void
    {
        if ($videoId <= 0) {
            return;
        }

        $ids = $this->getRunningVideoIds();
        $ids[] = $videoId;
        $this->saveRunningVideoIds($ids);
    }

    private function forgetRunningVideo(int $videoId): void
    {
        if ($videoId <= 0) {
            return;
        }

        $ids = array_values(array_filter(
            $this->getRunningVideoIds(),
            static fn($id) => (int)$id !== $videoId
        ));
        $this->saveRunningVideoIds($ids);
    }

    private function refreshRunningState(): void
    {
        $ids = $this->getRunningVideoIds();
        $activeIds = [];
        foreach ($ids as $id) {
            $id = (int)$id;
            if ($id > 0 && $this->cache()->get(self::LOCK_KEY . $id)) {
                $activeIds[] = $id;
            }
        }

        $activeIds = array_values(array_unique($activeIds));
        $actualCount = count($activeIds);
        $cachedCount = $this->getRunningCount();
        if ($actualCount !== count($ids)) {
            $this->saveRunningVideoIds($activeIds);
        }
        if ($cachedCount !== $actualCount) {
            $this->cache()->set(self::RUNNING_COUNT_KEY, $actualCount, self::MAX_EXECUTE_SECONDS + 60);
            Log::channel('video_slice')->write("[切片队列] 并发计数修正 cached={$cachedCount} actual={$actualCount}");
        }
    }

    private function getRunningVideoIds(): array
    {
        try {
            $ids = $this->redisHandler()->sMembers($this->redisKey(self::RUNNING_IDS_SET_KEY));
            if (is_array($ids) && !empty($ids)) {
                return array_values(array_unique(array_filter(array_map('intval', $ids))));
            }
        } catch (\Throwable $e) {
            Log::channel('video_slice')->warning("[切片队列] 读取运行中ID集合失败 | " . $e->getMessage());
        }

        $value = $this->cache()->get(self::RUNNING_IDS_KEY, '[]');
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('intval', $value))));
    }

    private function saveRunningVideoIds(array $ids): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        try {
            $key = $this->redisKey(self::RUNNING_IDS_SET_KEY);
            $handler = $this->redisHandler();
            $handler->del($key);
            if (!empty($ids)) {
                foreach ($ids as $id) {
                    $handler->sAdd($key, (string)$id);
                }
                $handler->expire($key, self::MAX_EXECUTE_SECONDS + 60);
            }
        } catch (\Throwable $e) {
            Log::channel('video_slice')->warning("[切片队列] 保存运行中ID集合失败 | " . $e->getMessage());
        }

        if (empty($ids)) {
            $this->cache()->delete(self::RUNNING_IDS_KEY);
            return;
        }

        $this->cache()->set(
            self::RUNNING_IDS_KEY,
            json_encode($ids, JSON_UNESCAPED_UNICODE),
            self::MAX_EXECUTE_SECONDS + 60
        );
    }

    private function isTaskAttemptExhausted(int $videoId): bool
    {
        return $videoId > 0 && $this->getTaskAttemptCount($videoId) >= self::MAX_TASK_ATTEMPTS;
    }

    private function getTaskAttemptCount(int $videoId): int
    {
        if ($videoId <= 0) {
            return 0;
        }

        return (int)$this->cache()->get($this->taskAttemptKey($videoId), 0);
    }

    private function incrementTaskAttemptCount(int $videoId): int
    {
        if ($videoId <= 0) {
            return 0;
        }

        $key = $this->taskAttemptKey($videoId);
        $count = (int)$this->cache()->get($key, 0) + 1;
        // 保留足够长，避免坏任务被再次投递时计数丢失
        $this->cache()->set($key, $count, 7 * 86400);

        return $count;
    }

    private function resetTaskAttemptCount(int $videoId): void
    {
        if ($videoId <= 0) {
            return;
        }

        $this->cache()->delete($this->taskAttemptKey($videoId));
    }

    private function taskAttemptKey(int $videoId): string
    {
        return self::TASK_ATTEMPT_KEY . $videoId;
    }

    private function isCircuitOpen(int $videoId): bool
    {
        if ($videoId <= 0) {
            return false;
        }

        return (bool)$this->cache()->get($this->circuitKey($videoId), false);
    }

    private function getCircuitRemainingSeconds(int $videoId): int
    {
        $openedAt = (int)$this->cache()->get($this->circuitOpenedAtKey($videoId), 0);
        if ($openedAt <= 0) {
            return self::CIRCUIT_BREAK_PAUSE;
        }

        return max(1, self::CIRCUIT_BREAK_PAUSE - (time() - $openedAt));
    }

    private function incrementFailCount(int $videoId): void
    {
        if ($videoId <= 0) {
            return;
        }

        $failKey = $this->failCountKey($videoId);
        $count = (int)$this->cache()->get($failKey, 0) + 1;
        $this->cache()->set($failKey, $count, 3600);

        Log::channel('video_slice')->write(
            "[切片队列] 单任务失败计数 video_id={$videoId} count={$count}/" . self::CIRCUIT_BREAK_THRESHOLD
        );

        if ($count >= self::CIRCUIT_BREAK_THRESHOLD) {
            $this->cache()->set($this->circuitKey($videoId), true, self::CIRCUIT_BREAK_PAUSE);
            $this->cache()->set($this->circuitOpenedAtKey($videoId), time(), self::CIRCUIT_BREAK_PAUSE);
            $this->cache()->set($failKey, 0, 3600);
            Log::channel('video_slice')->write(
                "[切片队列] 单任务熔断触发 video_id={$videoId}，暂停 " . self::CIRCUIT_BREAK_PAUSE . "s"
            );
        }
    }

    private function resetFailCount(int $videoId): void
    {
        if ($videoId <= 0) {
            return;
        }

        $this->cache()->delete($this->failCountKey($videoId));
        $this->cache()->delete($this->circuitKey($videoId));
        $this->cache()->delete($this->circuitOpenedAtKey($videoId));
    }

    private function failCountKey(int $videoId): string
    {
        return self::FAIL_COUNT_KEY . $videoId;
    }

    private function circuitKey(int $videoId): string
    {
        return self::CIRCUIT_KEY . $videoId;
    }

    private function circuitOpenedAtKey(int $videoId): string
    {
        return self::CIRCUIT_KEY . $videoId . '_opened_at';
    }

    private function cache()
    {
        return Cache::store(self::CACHE_STORE);
    }

    private function redisHandler()
    {
        return $this->cache()->handler();
    }

    private function redisKey(string $key): string
    {
        return (string)config('cache.stores.' . self::CACHE_STORE . '.prefix', '') . $key;
    }

    private function redisEval(string $script, array $keys, array $args)
    {
        return $this->redisHandler()->eval($script, array_merge($keys, array_map('strval', $args)), count($keys));
    }

    private function getCpuUsage(): float
    {
        $now = time();
        if ($now - self::$cpuUsageCachedAt < self::CPU_CACHE_SECONDS) {
            return self::$cpuUsageCache;
        }

        try {
            $cached = $this->cache()->get(self::CPU_CACHE_KEY);
            if (
                is_array($cached) && isset($cached['usage'], $cached['at'])
                && ($now - (int)$cached['at']) < self::CPU_CACHE_SECONDS
            ) {
                self::$cpuUsageCache = (float)$cached['usage'];
                self::$cpuUsageCachedAt = (int)$cached['at'];
                return self::$cpuUsageCache;
            }
        } catch (\Throwable $e) {
            // ignore cache read failure
        }

        if (!is_file('/proc/stat')) {
            self::$cpuUsageCache = 0.0;
            self::$cpuUsageCachedAt = $now;
            return 0.0;
        }

        try {
            $stat1 = $this->readCpuStat();
            usleep(200000);
            $stat2 = $this->readCpuStat();

            $idle1 = (float)($stat1['idle'] ?? 0);
            $total1 = array_sum($stat1);
            $idle2 = (float)($stat2['idle'] ?? 0);
            $total2 = array_sum($stat2);

            $totalDiff = $total2 - $total1;
            $idleDiff = $idle2 - $idle1;
            $usage = ($totalDiff <= 0) ? 0.0 : round((1 - $idleDiff / $totalDiff) * 100, 1);

            self::$cpuUsageCache = $usage;
            self::$cpuUsageCachedAt = $now;
            try {
                $this->cache()->set(self::CPU_CACHE_KEY, ['usage' => $usage, 'at' => $now], self::CPU_CACHE_SECONDS);
            } catch (\Throwable $e) {
                // ignore cache write failure
            }

            return $usage;
        } catch (\Throwable $e) {
            return 0.0;
        }
    }

    private function readCpuStat(): array
    {
        $line = explode(' ', trim(file('/proc/stat')[0]));
        array_shift($line);
        $line = array_filter($line, fn($v) => $v !== '');
        $keys = ['user', 'nice', 'system', 'idle', 'iowait', 'irq', 'softirq', 'steal'];
        $values = array_map('floatval', array_values($line));
        return array_combine(array_slice($keys, 0, count($values)), $values) ?: [];
    }
}

class VideoSliceTimeoutException extends \RuntimeException {}

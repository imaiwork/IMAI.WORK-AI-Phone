<?php

namespace app\common\Jobs;

use app\common\model\file\File;
use app\common\service\ConfigService;
use app\common\service\UploadService;
use think\facade\Cache;
use think\facade\Log;
use think\queue\Job;

class MediaTranscodeJob
{
    // ========== 配置常量 ==========

    // 最大并发转码数（建议 = CPU核心数 - 1，最少为1）
    const MAX_CONCURRENT = 1;

    // 单任务最大执行时间（秒），超过则强制终止
    const MAX_EXECUTE_SECONDS = 600;

    // 熔断：连续失败多少次后暂停队列
    const CIRCUIT_BREAK_THRESHOLD = 5;

    // 熔断后暂停时间（秒）
    const CIRCUIT_BREAK_PAUSE = 300;

    // CPU 使用率上限（%），超过则延迟执行
    const MAX_CPU_USAGE = 70;

    // CPU 过高时延迟重试（秒）
    const CPU_HIGH_RELEASE_DELAY = 120;

    // 最大重试次数
    const MAX_ATTEMPTS = 3;

    // 失败重试间隔（秒）
    const RETRY_DELAY = 60;

    // 并发锁缓存 key 前缀
    const LOCK_KEY = 'transcode_running_';

    // 熔断器缓存 key
    const CIRCUIT_KEY = 'transcode_circuit_breaker';

    // 失败计数缓存 key
    const FAIL_COUNT_KEY = 'transcode_fail_count';

    /**
     * 队列消费入口
     */
    public function fire(Job $job, array $data): void
    {
        $fileId = $data['file_id'] ?? 0;

        Log::channel('ffmpeg')->write(
            "[转码队列] 收到任务 | file_id={$fileId} attempts={$job->attempts()} | "
        );

        try {
            // 1. 熔断器检查
            if ($this->isCircuitOpen()) {
                $remaining = Cache::get(self::CIRCUIT_KEY . '_remaining', self::CIRCUIT_BREAK_PAUSE);
                $job->release($remaining > 0 ? $remaining : self::CIRCUIT_BREAK_PAUSE);
                return;
            }

            // 2. CPU 检查
            $cpuUsage = $this->getCpuUsage();
            if ($cpuUsage > self::MAX_CPU_USAGE) {
                // Log::channel('ffmpeg')->write("[转码队列] CPU使用率过高 {$cpuUsage}%，延迟 " . self::CPU_HIGH_RELEASE_DELAY . "s 执行 file_id={$fileId}");
                $job->release(self::CPU_HIGH_RELEASE_DELAY);
                return;
            }

            // 3. 并发数检查
            if (!$this->acquireLock($fileId)) {
                // Log::channel('ffmpeg')->write("[转码队列] 并发已满（最大" . self::MAX_CONCURRENT . "），延迟30s执行 file_id={$fileId}");
                $job->release(30);
                return;
            }

            // 4. 执行转码（带超时保护）
            try {
                $this->handleWithTimeout($data);
            } finally {
                // 无论成功失败都释放并发锁
                $this->releaseLock($fileId);
            }

            // 5. 成功：重置失败计数，删除任务
            $this->resetFailCount();
            $job->delete();
            // Log::channel('ffmpeg')->write("[转码队列] ✅ 任务完成 file_id={$fileId}");
        } catch (TranscodeTimeoutException $e) {
            // 超时异常：直接放弃，不重试
            $this->incrementFailCount();
            $job->delete();
            // Log::channel('ffmpeg')->write("[转码队列] ⏰ 任务超时被终止，已放弃 file_id={$fileId} | " . $e->getMessage());
        } catch (\Throwable $e) {
            $this->incrementFailCount();
            // Log::channel('ffmpeg')->write("[转码队列] ❌ 任务失败 file_id={$fileId} | " . $e->getMessage());

            if ($job->attempts() < self::MAX_ATTEMPTS) {
                $delay = self::RETRY_DELAY * $job->attempts(); // 递增延迟：60s, 120s
                $job->release($delay);
                // Log::channel('ffmpeg')->write("[转码队列] 第{$job->attempts()}次重试，{$delay}s后执行 file_id={$fileId}");
            } else {
                $job->delete();
                // Log::channel('ffmpeg')->write("[转码队列] 🚫 重试超限，放弃任务 file_id={$fileId}");
            }
        }
    }

    /**
     * 带超时保护的任务执行
     */
    private function handleWithTimeout(array $data): void
    {
        // 设置最大执行时间
        $startTime = time();

        // 注册超时信号（Linux 环境）
        if (function_exists('pcntl_signal') && function_exists('pcntl_alarm')) {
            pcntl_signal(SIGALRM, function () use ($data) {
                throw new TranscodeTimeoutException(
                    "任务执行超时（超过 " . self::MAX_EXECUTE_SECONDS . "s）file_id=" . ($data['file_id'] ?? '')
                );
            });
            pcntl_alarm(self::MAX_EXECUTE_SECONDS);
        }

        try {
            $this->handle($data);
        } finally {
            // 清除超时信号
            if (function_exists('pcntl_alarm')) {
                pcntl_alarm(0);
            }
        }

        // 软超时兜底（不支持 pcntl 时）
        if ((time() - $startTime) > self::MAX_EXECUTE_SECONDS) {
            throw new TranscodeTimeoutException("任务软超时 file_id=" . ($data['file_id'] ?? ''));
        }
    }

    /**
     * 核心处理逻辑
     */
    private function handle(array $data): void
    {
        $fileId      = $data['file_id']      ?? 0;
        $localPath   = $data['local_path']   ?? '';
        $ossUri      = $data['oss_uri']      ?? '';
        $saveDir     = $data['save_dir']     ?? '';
        $customSpecs = !empty($data['custom_specs']) && is_array($data['custom_specs'])
            ? $data['custom_specs']
            : null;

        // 基础参数校验
        if (!$fileId || !$localPath || !$saveDir) {
            throw new \Exception('任务参数缺失: ' . json_encode($data, JSON_UNESCAPED_UNICODE));
        }

        $default = ConfigService::get('storage', 'default', 'local');

        // ✅ 修复2：本地文件不存在时，尝试从 OSS 重新下载
        if (!file_exists($localPath)) {
            // Log::channel('ffmpeg')->write(
            //     "[转码队列] ⚠️ 本地文件不存在，尝试从OSS重新下载 | file_id={$fileId} local_path={$localPath}"
            // );

            // 非本地存储模式且有 OSS 路径时，才能尝试重新下载
            if ($default === 'local') {
                throw new \Exception("本地存储模式下本地文件不存在，无法恢复: {$localPath}");
            }

            if (empty($ossUri)) {
                throw new \Exception("本地文件不存在且 oss_uri 为空，无法重新下载: {$localPath}");
            }

            // 确保本地目录存在
            $localDir  = dirname($localPath);
            $fileName  = basename($localPath);

            if (!is_dir($localDir) && !mkdir($localDir, 0755, true)) {
                throw new \Exception("无法创建本地临时目录: {$localDir}");
            }

            // 从 OSS 下载文件到本地（复用 UploadService 的下载方法）
            // ⚠️ 注意：UploadService::downloadForTranscode 需改为 public static
            $localPath = UploadService::downloadForTranscode($ossUri, $localDir, $fileName);

            // Log::channel('ffmpeg')->write(
            //     "[转码队列] ✅ OSS重新下载完成 | file_id={$fileId} local_path={$localPath}"
            // );
        }

        // 二次校验：下载后文件仍不存在
        if (!file_exists($localPath)) {
            throw new \Exception("本地文件下载后仍不存在: {$localPath}");
        }

        // 文件大小校验
        if (filesize($localPath) === 0) {
            throw new \Exception("本地文件为空（0字节）: {$localPath}");
        }

        // Log::channel('ffmpeg')->write(
        //     "[转码队列] 开始转码 | file_id={$fileId} local_path={$localPath} size="
        //         . round(filesize($localPath) / 1024, 1) . "KB"
        // );

        // 确定目标文件名（OSS 模式下与 oss_uri 保持一致）
        $targetFileName = null;
        if ($default !== 'local' && !empty($ossUri)) {
            $targetFileName = basename($ossUri);
            // Log::channel('ffmpeg')->write(
            //     "[转码队列] 目标文件名: {$targetFileName} | oss_uri={$ossUri}"
            // );
        }

        // 执行转码
        $url = UploadService::standardizeMedia($localPath, $customSpecs, $targetFileName);

        // 更新数据库 uri
        if (!empty($url)) {
            File::where('id', $fileId)->update(['uri' => $url]);
            // Log::channel('ffmpeg')->write(
            //     "[转码队列] ✅ 已更新uri | file_id={$fileId} url={$url}"
            // );
        }

        // OSS 模式下清理本地临时文件
        if ($default !== 'local') {
            $this->cleanLocalFiles($localPath, $targetFileName);
        }
    }

    /**
     * 清理本地文件
     *
     * @param string $standardizedPath 原始上传文件路径
     * @param string|null $targetFileName 转码输出文件名（与原始文件同目录）
     */
    private function cleanLocalFiles(string $standardizedPath, ?string $targetFileName = null): void
    {
        $dir = dirname($standardizedPath);

        // 1. 删除原始上传文件
        $this->deleteFile($standardizedPath, '原始上传文件');

        // 2. 删除转码输出文件
        if ($targetFileName) {
            $outputPath = $dir . '/' . $targetFileName;
            if ($outputPath !== $standardizedPath) {
                $this->deleteFile($outputPath, '转码输出文件');
            }
        }

        // 3. 兜底清理残留 tmp_ 临时文件
        $tmpFiles = glob($dir . '/tmp_*');
        if ($tmpFiles) {
            foreach ($tmpFiles as $tmpFile) {
                $this->deleteFile($tmpFile, '残留临时文件');
            }
        }

        $this->removeEmptyDir($dir);
    }

    /**
     * 删除单个文件并记录日志
     */
    private function deleteFile(string $path, string $label = '文件'): void
    {
        if (!file_exists($path)) {
            // Log::channel('ffmpeg')->write("[清理] {$label}不存在，跳过: {$path}");
            return;
        }

        if (@unlink($path)) {
            // Log::channel('ffmpeg')->write("[清理] ✅ 已删除{$label}: {$path}");
        } else {
            // Log::channel('ffmpeg')->write("[清理] ⚠️ 删除{$label}失败: {$path}");
        }
    }

    /**
     * 删除空目录（仅删除日期目录，避免误删父目录）
     */
    private function removeEmptyDir(string $dir): void
    {
        // 只处理日期格式目录，避免误删
        if (!preg_match('/\d{8}$/', $dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        if (empty($files)) {
            if (@rmdir($dir)) {
                // Log::channel('ffmpeg')->write("[清理] ✅ 已删除空目录: {$dir}");
            }
        }
    }

    // ========== 并发锁 ==========

    /**
     * 获取并发锁
     */
    private function acquireLock(int $fileId): bool
    {
        $running = $this->getRunningCount();
        if ($running >= self::MAX_CONCURRENT) {
            return false;
        }
        // 写入当前任务锁，MAX_EXECUTE_SECONDS 后自动过期（防止死锁）
        Cache::set(self::LOCK_KEY . $fileId, time(), self::MAX_EXECUTE_SECONDS + 60);
        return true;
    }

    /**
     * 释放并发锁
     */
    private function releaseLock(int $fileId): void
    {
        Cache::delete(self::LOCK_KEY . $fileId);
    }

    /**
     * 获取当前运行中的任务数
     */
    private function getRunningCount(): int
    {
        $count = Cache::get('transcode_running_count', 0);
        return (int)$count;
    }

    /**
     * 重写 acquireLock：使用 Redis 原子计数器（更可靠）
     */
    private function acquireLockAtomic(int $fileId): bool
    {
        $count = (int)Cache::get('transcode_running_count', 0);
        if ($count >= self::MAX_CONCURRENT) {
            return false;
        }
        Cache::inc('transcode_running_count');
        Cache::set(self::LOCK_KEY . $fileId, time(), self::MAX_EXECUTE_SECONDS + 60);
        return true;
    }

    private function releaseLockAtomic(int $fileId): void
    {
        Cache::delete(self::LOCK_KEY . $fileId);
        $count = (int)Cache::get('transcode_running_count', 0);
        if ($count > 0) {
            Cache::dec('transcode_running_count');
        }
    }

    // ========== 熔断器 ==========

    /**
     * 检查熔断器是否触发
     */
    private function isCircuitOpen(): bool
    {
        return (bool)Cache::get(self::CIRCUIT_KEY, false);
    }

    /**
     * 增加失败计数，超过阈值触发熔断
     */
    private function incrementFailCount(): void
    {
        $count = (int)Cache::get(self::FAIL_COUNT_KEY, 0) + 1;
        Cache::set(self::FAIL_COUNT_KEY, $count, 3600);

        // Log::channel('ffmpeg')->write("[转码队列] 失败计数: {$count}/" . self::CIRCUIT_BREAK_THRESHOLD);

        if ($count >= self::CIRCUIT_BREAK_THRESHOLD) {
            // 触发熔断
            Cache::set(self::CIRCUIT_KEY, true, self::CIRCUIT_BREAK_PAUSE);
            Cache::set(self::CIRCUIT_KEY . '_remaining', self::CIRCUIT_BREAK_PAUSE, self::CIRCUIT_BREAK_PAUSE);
            Cache::set(self::FAIL_COUNT_KEY, 0, 3600); // 重置计数
            // Log::channel('ffmpeg')->write(
            //     "[转码队列] 🔴 熔断器触发！连续失败 {$count} 次，暂停 " . self::CIRCUIT_BREAK_PAUSE . "s"
            // );
        }
    }

    /**
     * 重置失败计数
     */
    private function resetFailCount(): void
    {
        Cache::set(self::FAIL_COUNT_KEY, 0, 3600);
    }

    // ========== CPU 检测 ==========

    /**
     * 获取当前 CPU 使用率（%）
     * 仅支持 Linux
     */
    private function getCpuUsage(): float
    {
        try {
            // 读取 /proc/stat 两次取差值，更准确
            $stat1 = $this->readCpuStat();
            usleep(200000); // 等待 200ms
            $stat2 = $this->readCpuStat();

            $idle1  = $stat1['idle']  + $stat1['iowait'];
            $total1 = array_sum($stat1);
            $idle2  = $stat2['idle']  + $stat2['iowait'];
            $total2 = array_sum($stat2);

            $totalDiff = $total2 - $total1;
            $idleDiff  = $idle2  - $idle1;

            if ($totalDiff <= 0) return 0.0;

            return round((1 - $idleDiff / $totalDiff) * 100, 1);
        } catch (\Throwable $e) {
            // 无法获取 CPU 信息时返回 0，不阻断任务
            return 0.0;
        }
    }

    private function readCpuStat(): array
    {
        $line = explode(' ', trim(file('/proc/stat')[0]));
        // 去掉 "cpu" 前缀
        array_shift($line);
        $line = array_filter($line, fn($v) => $v !== '');
        $keys = ['user', 'nice', 'system', 'idle', 'iowait', 'irq', 'softirq', 'steal'];
        return array_combine(array_slice($keys, 0, count($line)), array_values($line));
    }
}

/**
 * 转码超时异常
 */
class TranscodeTimeoutException extends \RuntimeException {}

<?php

namespace app\common\Jobs;

use app\common\enum\FileEnum;
use app\common\model\file\File;
use app\common\service\ConfigService;
use app\common\service\FileService;
use app\common\service\UploadService;
use app\common\service\transcoding\OssMediaProcessService;
use think\facade\Cache;
use think\facade\Log;
use think\facade\Queue;
use think\queue\Job;

class MediaTranscodeJob
{
    // ========== 配置常量 ==========

    // 最大并发转码数（建议 = CPU核心数 - 1，最少为1）
    const MAX_CONCURRENT = 4;

    // 单任务最大执行时间（秒），超过则强制终止
    const MAX_EXECUTE_SECONDS = 600;

    // 熔断：单任务连续失败多少次后暂停该任务
    const CIRCUIT_BREAK_THRESHOLD = 5;

    // 单任务熔断后暂停时间（秒）
    const CIRCUIT_BREAK_PAUSE = 60;

    // CPU 使用率上限（%），超过则延迟执行
    const MAX_CPU_USAGE = 70;

    // CPU 过高时延迟重试（秒）
    const CPU_HIGH_RELEASE_DELAY = 120;

    // 队列 Job 自身 attempts 上限（仅对 release 重试有效）
    const MAX_ATTEMPTS = 10;

    // 按 file_id 累计的真实执行失败次数上限，超过后永久放弃
    const MAX_TASK_ATTEMPTS = 10;

    // 失败重试间隔（秒）
    const RETRY_DELAY = 60;

    // 并发锁缓存 key 前缀
    const LOCK_KEY = 'transcode_running_';

    // 单任务熔断器缓存 key 前缀
    const CIRCUIT_KEY = 'transcode_circuit_';

    // 单任务失败计数缓存 key 前缀
    const FAIL_COUNT_KEY = 'transcode_fail_count_';

    // 单任务真实失败次数缓存 key 前缀
    const TASK_ATTEMPT_KEY = 'transcode_task_attempt_';

    // 并发/熔断状态使用的缓存仓库（Redis，保证 inc/dec 跨进程原子）
    const CACHE_STORE = 'concurrent_redis';

    // 运行中任务计数 key
    const RUNNING_COUNT_KEY = 'transcode_running_count';

    // 运行中文件 ID 列表 key，用于修正异常退出导致的计数残留
    const RUNNING_IDS_KEY = 'transcode_running_ids';

    // 运行中文件 ID 集合 key，Lua 并发锁使用
    const RUNNING_IDS_SET_KEY = 'transcode_running_ids_set';

    // 前一天 tmp 残留文件清理缓存 key 前缀
    const PREVIOUS_DAY_TMP_CLEAN_KEY = 'transcode_previous_day_tmp_cleaned_';

    // 前一天 tmp 残留文件清理间隔（秒）
    const PREVIOUS_DAY_TMP_CLEAN_TTL = 43200;

    /**
     * 队列消费入口
     */
    public function fire(Job $job, array $data): void
    {
        $fileId = (int)($data['file_id'] ?? 0);
        $taskAttempts = $fileId > 0 ? $this->getTaskAttemptCount($fileId) : 0;

        Log::channel('ffmpeg')->write(
            "[转码队列] 收到任务 | file_id={$fileId} job_attempts={$job->attempts()} task_attempts={$taskAttempts}/" . self::MAX_TASK_ATTEMPTS
        );

        try {
            if ($fileId <= 0) {
                throw new \InvalidArgumentException(
                    '转码任务参数缺失: ' . json_encode($data, JSON_UNESCAPED_UNICODE)
                );
            }

            if ($this->isTaskAttemptExhausted($fileId)) {
                $this->markTranscodeFailed($fileId);
                $job->delete();
                Log::channel('ffmpeg')->write(
                    "[转码队列] 单任务已达最大重试次数(" . self::MAX_TASK_ATTEMPTS . ")，不再执行 file_id={$fileId}"
                );
                return;
            }

            if ($this->isCircuitOpen($fileId)) {
                $remaining = $this->getCircuitRemainingSeconds($fileId);
                $this->delayWithoutConsumingAttempts(
                    $job,
                    $data,
                    $remaining,
                    "单任务熔断中 remaining={$remaining}s"
                );
                return;
            }

            // 仅「视频 + OSS-MPS」不吃本机 CPU；图片仍走本机 ffmpeg，需 CPU 节流
            if (!$this->shouldUseOssMps($data)) {
                $cpuUsage = $this->getCpuUsage();
                if ($cpuUsage > self::MAX_CPU_USAGE) {
                    Log::channel('ffmpeg')->write(
                        "[转码队列] CPU使用率过高 {$cpuUsage}%，延迟 " . self::CPU_HIGH_RELEASE_DELAY . "s 执行 file_id={$fileId}"
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

            if (!$this->acquireLock($fileId)) {
                $running = $this->getRunningCount();
                $runningIds = implode(',', $this->getRunningFileIds());
                Log::channel('ffmpeg')->write(
                    "[转码队列] 并发已满或同文件处理中（最大" . self::MAX_CONCURRENT . "），延迟30s执行 "
                        . "file_id={$fileId} running={$running} running_ids=[{$runningIds}]"
                );
                $this->delayWithoutConsumingAttempts($job, $data, 30, '并发已满或同文件处理中');
                return;
            }

            try {
                Log::channel('ffmpeg')->write("[转码队列] 准备执行转码 | file_id={$fileId}");
                $this->handleWithTimeout($data);
            } finally {
                $this->releaseLock($fileId);
            }

            $this->resetFailCount($fileId);
            $this->resetTaskAttemptCount($fileId);
            $job->delete();
            Log::channel('ffmpeg')->write("[转码队列] ✅ 任务完成 file_id={$fileId}");
        } catch (TranscodeTimeoutException $e) {
            $taskAttempts = $this->incrementTaskAttemptCount($fileId);
            $this->incrementFailCount($fileId);
            $this->markTranscodeFailed($fileId);
            $job->delete();
            Log::channel('ffmpeg')->write(
                "[转码队列] ⏰ 任务超时被终止，已放弃 file_id={$fileId} task_attempts={$taskAttempts}/" . self::MAX_TASK_ATTEMPTS
                    . " | " . $e->getMessage()
            );
        } catch (\Throwable $e) {
            $taskAttempts = $this->incrementTaskAttemptCount($fileId);
            $this->incrementFailCount($fileId);
            Log::channel('ffmpeg')->write(
                "[转码队列] ❌ 任务失败 file_id={$fileId} task_attempts={$taskAttempts}/" . self::MAX_TASK_ATTEMPTS
                    . " | " . $e->getMessage()
            );

            if ($taskAttempts >= self::MAX_TASK_ATTEMPTS || $job->attempts() >= self::MAX_ATTEMPTS) {
                $this->markTranscodeFailed($fileId);
                $job->delete();
                Log::channel('ffmpeg')->write(
                    "[转码队列] 🚫 重试超限，放弃任务 file_id={$fileId} task_attempts={$taskAttempts} job_attempts={$job->attempts()}"
                );
            } else {
                $delay = self::RETRY_DELAY * max(1, $job->attempts());
                $job->release($delay);
                Log::channel('ffmpeg')->write(
                    "[转码队列] 第{$taskAttempts}次失败后重试，{$delay}s后执行 file_id={$fileId}"
                );
            }
        }
    }

    private function delayWithoutConsumingAttempts(Job $job, array $data, int $delay, string $reason): void
    {
        $delay = max(1, $delay);
        $queueName = (string)env('QUEUE.TRANSCODING', '');
        $jobId = Queue::later($delay, self::class, $data, $queueName !== '' ? $queueName : null);
        if ($jobId === false) {
            throw new \RuntimeException("转码任务延迟重投递失败：{$reason}");
        }

        $job->delete();
        Log::channel('ffmpeg')->write(
            "[转码队列] 已延迟重投递 file_id=" . (int)($data['file_id'] ?? 0)
                . " delay={$delay}s reason={$reason} job_id={$jobId}"
        );
    }

    private function markTranscodeFailed(int $fileId): void
    {
        if ($fileId <= 0) {
            return;
        }

        File::where('id', $fileId)->update([
            'transcode_status' => 4,
            'update_time' => time(),
        ]);
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
        // 视频走 OSS-MPS；图片始终本机 ffmpeg（对齐现网图合成规范）
        if ($this->shouldUseOssMps($data)) {
            $this->handleOssMps($data);
            return;
        }

        if (OssMediaProcessService::isEnabled()) {
            Log::channel('ffmpeg')->write(
                "[转码队列] OSS 媒体处理已开启，但当前为图片，回退本机 ffmpeg file_id="
                    . (int)($data['file_id'] ?? 0)
            );
        }

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
        $localPath = rtrim($localPath, '/\\');

        // 标记转码中,供生成视频侧门禁查询
        File::where('id', $fileId)->update([
            'transcode_status' => 2,
            'update_time' => time(),
        ]);

        $default = ConfigService::get('storage', 'default', 'local');

        if (!file_exists($localPath)) {
            if ($default === 'local') {
                $legacyPath = dirname($localPath) . basename($localPath);
                if (is_file($legacyPath)) {
                    if (!is_dir(dirname($localPath))) {
                        mkdir(dirname($localPath), 0755, true);
                    }
                    rename($legacyPath, $localPath);
                    Log::channel('ffmpeg')->write(
                        "[转码队列] ✅ 已修正历史本地路径 | file_id={$fileId} legacy_path={$legacyPath} local_path={$localPath}"
                    );
                }
            }

            if (!file_exists($localPath)) {
                if ($default === 'local') {
                    Log::channel('ffmpeg')->write(
                        "[转码队列] ⚠️ 本地存储文件不存在，无法直接转码 | file_id={$fileId} local_path={$localPath}"
                    );
                    throw new \Exception("本地存储模式下本地文件不存在，无法恢复: {$localPath}");
                }

                Log::channel('ffmpeg')->write(
                    "[转码队列] ⚠️ 本地文件不存在，尝试从OSS重新下载 | file_id={$fileId} local_path={$localPath}"
                );

                if (empty($ossUri)) {
                    throw new \Exception("本地文件不存在且 oss_uri 为空，无法重新下载: {$localPath}");
                }

                $ossSaveDir = ltrim(dirname($ossUri), '/\\');
                $fileName = basename($ossUri);
                $localPath = UploadService::downloadForTranscode($ossUri, $ossSaveDir, $fileName);

                Log::channel('ffmpeg')->write(
                    "[转码队列] ✅ OSS重新下载完成 | file_id={$fileId} local_path={$localPath}"
                );
            }
        }

        // 二次校验：下载后文件仍不存在
        if (!file_exists($localPath)) {
            throw new \Exception("本地文件下载后仍不存在: {$localPath}");
        }

        // 文件大小校验
        if (filesize($localPath) === 0) {
            throw new \Exception("本地文件为空（0字节）: {$localPath}");
        }

        Log::channel('ffmpeg')->write(
            "[转码队列] 开始转码 | file_id={$fileId} local_path={$localPath} size="
                . round(filesize($localPath) / 1024, 1) . "KB"
        );

        // 确定目标文件名（OSS 模式下与 oss_uri 保持一致）
        $targetFileName = null;
        if ($default !== 'local' && !empty($ossUri)) {
            $targetFileName = basename($ossUri);
            Log::channel('ffmpeg')->write(
                "[转码队列] 目标文件名: {$targetFileName} | oss_uri={$ossUri}"
            );
        }

        // 执行转码
        $result = UploadService::standardizeMedia($localPath, $customSpecs, $targetFileName);
        $url = $result['url'] ?? '';
        // 更新数据库 uri,同步标记转码完成
        if (!empty($url)) {
            $this->markTranscodeSuccess($fileId, $url, [
                'thumbnail_url' => (string)($result['thumbnail_url'] ?? ''),
                'duration' => (float)($result['duration'] ?? 0),
                'width' => (int)($result['width'] ?? 0),
                'height' => (int)($result['height'] ?? 0),
            ]);
            Log::channel('ffmpeg')->write(
                "[转码队列] ✅ 已更新uri | file_id={$fileId} url={$url}"
                    . " thumb=" . ((string)($result['thumbnail_url'] ?? '') !== '' ? 'yes' : 'no')
            );
        } else {
            throw new \Exception("转码完成但未返回有效url file_id={$fileId}");
        }

        // OSS 模式下清理本地临时文件
        if ($default !== 'local') {
            $this->cleanLocalFiles($localPath, $targetFileName);
        }
    }

    /**
     * 是否走 OSS-MPS：仅启用云媒体处理且确认为视频时
     */
    private function shouldUseOssMps(array $data): bool
    {
        if (!OssMediaProcessService::isEnabled()) {
            return false;
        }

        return $this->isVideoMedia($data);
    }

    /**
     * 判断转码任务是否为视频（图片回退本机）
     */
    private function isVideoMedia(array $data): bool
    {
        $fileId = (int)($data['file_id'] ?? 0);
        $uri = trim((string)($data['oss_uri'] ?? ''));
        if ($uri === '') {
            $uri = trim((string)($data['local_path'] ?? ''));
        }

        if ($fileId > 0) {
            $file = File::where('id', $fileId)->field('id,type,uri')->findOrEmpty();
            if (!$file->isEmpty()) {
                $type = (int)($file->type ?? 0);
                if ($type === FileEnum::IMAGE_TYPE) {
                    return false;
                }
                if ($type === FileEnum::VIDEO_TYPE) {
                    return true;
                }
                if ($uri === '') {
                    $uri = trim((string)($file->uri ?? ''));
                }
            }
        }

        $ext = strtolower(pathinfo($uri, PATHINFO_EXTENSION));
        $imageExts = array_map('strtolower', (array)config('project.file_image', []));
        if ($ext !== '' && in_array($ext, $imageExts, true)) {
            return false;
        }

        $videoExts = ['mp4', 'mov', 'avi', 'mkv', 'webm', 'm4v', 'flv', 'wmv', '3gp', 'mpeg', 'mpg'];
        return $ext !== '' && in_array($ext, $videoExts, true);
    }

    /**
     * 阿里云 MPS 整段转码（media_process=oss，仅视频）
     * 封面在转码前用本地原片截取，避免转码后再从 OSS 整包下载。
     */
    private function handleOssMps(array $data): void
    {
        $fileId = (int)($data['file_id'] ?? 0);
        if ($fileId <= 0) {
            throw new \Exception('OSS转码任务缺少 file_id: ' . json_encode($data, JSON_UNESCAPED_UNICODE));
        }

        File::where('id', $fileId)->update([
            'transcode_status' => 2,
            'update_time' => time(),
        ]);

        $ossUri = trim((string)($data['oss_uri'] ?? ''));
        if ($ossUri === '') {
            $file = File::where('id', $fileId)->field('id,uri')->findOrEmpty();
            $ossUri = trim((string)($file->uri ?? ''));
        }
        if ($ossUri === '') {
            throw new \Exception("OSS转码缺少对象路径 file_id={$fileId}");
        }

        // 1) 转码前：用本地原片截封面（上传时已下载到 local_path）
        $localPath = rtrim(trim((string)($data['local_path'] ?? '')), '/\\');
        if ($localPath === '' || !is_file($localPath)) {
            try {
                $ossSaveDir = ltrim(dirname($ossUri), '/\\');
                $fileName = basename($ossUri);
                $localPath = rtrim(UploadService::downloadForTranscode($ossUri, $ossSaveDir, $fileName), '/\\');
                Log::channel('ffmpeg')->write(
                    "[转码队列] OSS-MPS 截封面前补下载原片 file_id={$fileId} local_path={$localPath}"
                );
            } catch (\Throwable $e) {
                Log::channel('ffmpeg')->write(
                    "[转码队列] OSS-MPS 原片本地不可用，转码后回退远程截封面 file_id={$fileId} err="
                        . $e->getMessage()
                );
                $localPath = '';
            }
        }

        $meta = $localPath !== '' && is_file($localPath)
            ? $this->buildMetaFromLocalVideo($localPath)
            : [
                'thumbnail_url' => '',
                'duration' => 0.0,
                'width' => 0,
                'height' => 0,
            ];
        if (!empty($meta['thumbnail_url'])) {
            Log::channel('ffmpeg')->write(
                "[素材封面] 转码前已截取 file_id={$fileId} duration=" . ($meta['duration'] ?? 0)
                    . " thumb=" . $meta['thumbnail_url']
            );
        }

        $inputObject = OssMediaProcessService::toObjectKey($ossUri);
        // 输出到独立对象，避免与并行切片抢同一 OSS 源文件
        $dir = str_replace('\\', '/', dirname($inputObject));
        $base = pathinfo($inputObject, PATHINFO_FILENAME) ?: 'video';
        $ext = pathinfo($inputObject, PATHINFO_EXTENSION) ?: 'mp4';
        $outputObject = ($dir !== '.' && $dir !== '' ? $dir . '/' : '') . $base . '.' . $ext;

        Log::channel('ffmpeg')->write(
            "[转码队列] OSS-MPS 开始 | file_id={$fileId} input={$inputObject} output={$outputObject}"
        );

        $service = OssMediaProcessService::make();
        $outputUri = $service->submitAndWait(
            $inputObject,
            $outputObject,
            null,
            max(60, self::MAX_EXECUTE_SECONDS - 30)
        );

        // 2) 转码后：仅当转码前没截到封面时，才回退远程下载截帧
        if (empty($meta['thumbnail_url'])) {
            $remoteMeta = $this->buildMetaFromRemoteVideo($outputUri);
            $meta = [
                'thumbnail_url' => (string)($remoteMeta['thumbnail_url'] ?? ''),
                'duration' => (float)($meta['duration'] ?? 0) > 0
                    ? (float)$meta['duration']
                    : (float)($remoteMeta['duration'] ?? 0),
                'width' => (int)($meta['width'] ?? 0) > 0
                    ? (int)$meta['width']
                    : (int)($remoteMeta['width'] ?? 0),
                'height' => (int)($meta['height'] ?? 0) > 0
                    ? (int)$meta['height']
                    : (int)($remoteMeta['height'] ?? 0),
            ];
        }

        $this->markTranscodeSuccess($fileId, $outputUri, $meta);
        Log::channel('ffmpeg')->write(
            "[转码队列] ✅ OSS-MPS 完成 | file_id={$fileId} uri={$outputUri}"
                . " thumb=" . (!empty($meta['thumbnail_url']) ? 'yes' : 'no')
                . " duration=" . ($meta['duration'] ?? 0)
        );

        if ($localPath !== '' && is_file($localPath)) {
            $this->cleanLocalFiles($localPath, basename($outputUri));
        }
    }

    /**
     * 本地原片截封面 + 探测时长宽高（转码前调用，最快）。
     *
     * @return array{thumbnail_url:string,duration:float,width:int,height:int}
     */
    private function buildMetaFromLocalVideo(string $localPath): array
    {
        $meta = [
            'thumbnail_url' => '',
            'duration' => 0.0,
            'width' => 0,
            'height' => 0,
        ];
        if ($localPath === '' || !is_file($localPath)) {
            return $meta;
        }

        try {
            $info = UploadService::getMediaInfo($localPath);
            $meta['duration'] = round((float)($info['format']['duration'] ?? 0), 2);
            foreach ($info['streams'] ?? [] as $stream) {
                if (($stream['codec_type'] ?? '') === 'video') {
                    $meta['width'] = (int)($stream['width'] ?? 0);
                    $meta['height'] = (int)($stream['height'] ?? 0);
                    break;
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        $seek = $meta['duration'] > 0
            ? min(0.3, max(0.01, $meta['duration'] * 0.15))
            : 0.1;
        $meta['thumbnail_url'] = \app\common\service\ffmpeg\MaterialService::makeVideoThumbnail($localPath, $seek);

        return $meta;
    }

    /**
     * OSS 上的视频：拼完整 URL 后截封面，并尽量探测时长/宽高。
     *
     * @return array{thumbnail_url:string,duration:float,width:int,height:int}
     */
    private function buildMetaFromRemoteVideo(string $uri): array
    {
        $meta = [
            'thumbnail_url' => '',
            'duration' => 0.0,
            'width' => 0,
            'height' => 0,
        ];
        $uri = ltrim(trim($uri), '/');
        if ($uri === '') {
            return $meta;
        }

        // 强制走云存储域名（OSS-MPS 场景本地一定没有该文件）
        $fullUrl = FileService::getFileUrl($uri, '', true);
        if (!preg_match('/^https?:\/\//i', $fullUrl)) {
            Log::channel('ffmpeg')->write(
                "[素材封面] OSS地址解析失败 uri={$uri} full=" . $fullUrl
            );
            return $meta;
        }

        try {
            $tmp = $this->downloadRemoteVideoHead($fullUrl);
            if ($tmp !== '' && is_file($tmp)) {
                try {
                    $info = UploadService::getMediaInfo($tmp);
                    $meta['duration'] = round((float)($info['format']['duration'] ?? 0), 2);
                    foreach ($info['streams'] ?? [] as $stream) {
                        if (($stream['codec_type'] ?? '') === 'video') {
                            $meta['width'] = (int)($stream['width'] ?? 0);
                            $meta['height'] = (int)($stream['height'] ?? 0);
                            break;
                        }
                    }
                } catch (\Throwable $e) {
                    // ignore probe
                }
                $seek = $meta['duration'] > 0
                    ? min(0.3, max(0.01, $meta['duration'] * 0.15))
                    : 0.1;
                $meta['thumbnail_url'] = \app\common\service\ffmpeg\MaterialService::makeVideoThumbnail($tmp, $seek);
                @unlink($tmp);
            }
        } catch (\Throwable $e) {
            Log::channel('ffmpeg')->write(
                "[素材封面] OSS截帧失败 uri={$uri} err=" . $e->getMessage()
            );
        }

        // 本地临时下载失败时，再试直接用完整 URL 截帧
        if ($meta['thumbnail_url'] === '') {
            $seek = $meta['duration'] > 0
                ? min(0.3, max(0.01, $meta['duration'] * 0.15))
                : 0.1;
            $meta['thumbnail_url'] = \app\common\service\ffmpeg\MaterialService::makeVideoThumbnail($fullUrl, $seek);
        }

        return $meta;
    }

    /**
     * 下载远程视频到临时文件（整文件，避免缺 moov 导致截帧失败）。
     */
    private function downloadRemoteVideoHead(string $remoteUrl): string
    {
        $tempDir = root_path() . 'runtime/temp/';
        if (!is_dir($tempDir) && !mkdir($tempDir, 0755, true) && !is_dir($tempDir)) {
            throw new \RuntimeException('无法创建临时目录: ' . $tempDir);
        }
        $ext = pathinfo(parse_url($remoteUrl, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION) ?: 'mp4';
        $tempFile = $tempDir . 'oss_thumb_' . uniqid('', true) . '.' . $ext;
        $fp = fopen($tempFile, 'w+');
        if (!$fp) {
            throw new \RuntimeException('无法创建临时文件: ' . $tempFile);
        }
        $ch = curl_init($remoteUrl);
        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; OssThumb/1.0)',
        ]);
        $ok = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        fclose($fp);
        if (!$ok || ($code !== 200 && $code !== 206) || !is_file($tempFile) || filesize($tempFile) < 100) {
            @unlink($tempFile);
            throw new \RuntimeException("下载OSS视频失败 HTTP {$code} {$err}");
        }
        return $tempFile;
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

        $this->cleanPreviousDayTmpFiles($dir);

        // 不扫描清理当天 tmp_*:同目录可能有其他并发转码任务正在写临时文件。
        // 当前任务自己的临时文件由 UploadService::standardizeMedia() 成功/异常路径清理。

        $this->removeEmptyDir($dir);
    }

    /**
     * 上传侧「已符合规范跳过转码」时调用：标记转码成功，并继续 ffmpeg=1 入库 / ffmpeg=2 自动切割。
     * 与真实转码成功共用后续钩子，避免跳过转码后链路中断。
     *
     * @param array{thumbnail_url?:string,duration?:float,width?:int,height?:int} $meta
     */
    public static function finalizeSkippedTranscode(int $fileId, string $url, array $meta = []): void
    {
        (new self())->markTranscodeSuccess($fileId, $url, $meta);
    }

    /**
     * 更新转码结果后立即回读校验，避免 OSS 已上传但数据库未真正落库。
     *
     * @param array{thumbnail_url?:string,duration?:float,width?:int,height?:int} $meta
     */
    private function markTranscodeSuccess(int $fileId, string $url, array $meta = []): void
    {
        $update = [
            'uri' => $url,
            'transcode_status' => 3,
            'update_time' => time(),
        ];
        $duration = round((float)($meta['duration'] ?? 0), 2);
        if ($duration > 0) {
            $update['duration'] = $duration;
        }
        File::where('id', $fileId)->update($update);

        $file = File::where('id', $fileId)
            ->field('id,uri,transcode_status')
            ->findOrEmpty();

        if ($file->isEmpty()) {
            throw new \Exception("转码结果写库校验失败：文件记录不存在 file_id={$fileId}");
        }

        $savedUri = (string)($file->uri ?? '');
        $savedStatus = (int)($file->transcode_status ?? 0);
        if ($savedUri !== $url || $savedStatus !== 3) {
            throw new \Exception(
                "转码结果写库校验失败 file_id={$fileId} expected_uri={$url} "
                    . "actual_uri={$savedUri} actual_status={$savedStatus}"
            );
        }

        $this->publishOriginalMaterialAfterTranscode($fileId, $url, $meta);
        $this->autoStartSliceAfterTranscode($fileId, $url, $meta);
    }

    /**
     * ffmpeg=1：若上传时带了 persona_id，转码成功后把完整视频入库（不切割、不扣费）；
     * 未传 persona_id 则跳过入库，由前端调用素材新增接口。
     * ffmpeg=2：不在此处入库，统一走 autoStartSliceAfterTranscode（<5秒直接入库，≥5秒进切割队列）。
     */
    private function publishOriginalMaterialAfterTranscode(int $fileId, string $url, array $meta = []): void
    {
        try {
            $file = File::where('id', $fileId)
                ->field('id,type,persona_id,scene,slice_mode,source_id,name,duration')
                ->findOrEmpty();
            if ($file->isEmpty()) {
                return;
            }
            if ((int)($file->slice_mode ?? 0) !== 1) {
                return;
            }
            if ((int)($file->type ?? 0) !== FileEnum::VIDEO_TYPE) {
                return;
            }
            $personaId = (int)($file->persona_id ?? 0);
            // 未传人设：只转码，不自动入库
            if ($personaId <= 0) {
                return;
            }

            $duration = round((float)($meta['duration'] ?? $file->duration ?? 0), 2);
            $width = (int)($meta['width'] ?? 0);
            $height = (int)($meta['height'] ?? 0);
            $thumbnailUrl = trim((string)($meta['thumbnail_url'] ?? ''));
            if ($duration <= 0 || $width <= 0 || $height <= 0) {
                try {
                    $local = rtrim(public_path(), '/\\') . '/' . ltrim($url, '/');
                    if (is_file($local)) {
                        $info = UploadService::getMediaInfo($local);
                        if ($duration <= 0) {
                            $duration = round((float)($info['format']['duration'] ?? 0), 2);
                        }
                        foreach ($info['streams'] ?? [] as $stream) {
                            if (($stream['codec_type'] ?? '') === 'video') {
                                if ($width <= 0) {
                                    $width = (int)($stream['width'] ?? 0);
                                }
                                if ($height <= 0) {
                                    $height = (int)($stream['height'] ?? 0);
                                }
                                break;
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    // 探测失败不阻断入库
                }
            }

            $materialId = \app\common\service\ffmpeg\MaterialService::publishUploadedVideo(
                $fileId,
                $personaId,
                (int)($file->source_id ?? 0),
                [
                    'file_url' => $url,
                    'name' => pathinfo((string)($file->name ?? ''), PATHINFO_FILENAME),
                    'duration' => $duration,
                    'width' => $width,
                    'height' => $height,
                    'thumbnail_url' => $thumbnailUrl,
                    'ensure_thumbnail' => $thumbnailUrl === '',
                ]
            );

            Log::channel('ffmpeg')->write(
                "[转码入库] ffmpeg=1 原视频入库 file_id={$fileId} material_id={$materialId}"
                    . " persona_id={$personaId} duration={$duration} thumb=" . ($thumbnailUrl !== '' ? 'yes' : 'no')
            );
        } catch (\Throwable $e) {
            Log::channel('ffmpeg')->write(
                "[转码入库] 原视频入库失败 file_id={$fileId} err=" . $e->getMessage()
            );
        }
    }

    /**
     * ffmpeg=2：转码成功后统一走 startFromFile。
     * 时长 <5 秒：直接原片入库，不进切割队列、不扣费；
     * 时长 ≥5 秒：预扣并投递切割队列。
     *
     * @param array{thumbnail_url?:string,duration?:float,width?:int,height?:int} $meta
     */
    private function autoStartSliceAfterTranscode(int $fileId, string $url, array $meta = []): void
    {
        try {
            $file = File::where('id', $fileId)
                ->field('id,type,persona_id,scene,slice_mode,source_id,uri,duration')
                ->findOrEmpty();
            if ($file->isEmpty()) {
                return;
            }
            if ((int)($file->slice_mode ?? 0) !== 2) {
                return;
            }
            if ((int)($file->type ?? 0) !== FileEnum::VIDEO_TYPE) {
                return;
            }
            $userId = (int)($file->source_id ?? 0);
            $personaId = (int)($file->persona_id ?? 0);
            $scene = (string)($file->scene ?? 'persona');
            if ($userId <= 0 || $personaId <= 0) {
                Log::channel('ffmpeg')->write(
                    "[自动切割] 跳过：缺少 user/persona file_id={$fileId} user_id={$userId} persona_id={$personaId}"
                );
                return;
            }
            if (!in_array($scene, ['ai_creation', 'persona'], true)) {
                $scene = 'persona';
            }

            // 确保 quote/切割使用转码后的 uri
            if ($url !== '' && (string)$file->uri !== $url) {
                File::where('id', $fileId)->update(['uri' => $url, 'update_time' => time()]);
            }

            $result = (new \app\common\service\ffmpeg\MaterialSliceBatchService())->startFromFile(
                $userId,
                $fileId,
                $personaId,
                $scene,
                [
                    'thumbnail_url' => (string)($meta['thumbnail_url'] ?? ''),
                    'width' => (int)($meta['width'] ?? 0),
                    'height' => (int)($meta['height'] ?? 0),
                ]
            );
            if (!empty($result['skipped_slice'])) {
                Log::channel('ffmpeg')->write(
                    "[自动切割] 无需切割，已跳过队列直接入库（不扣费） file_id={$fileId}"
                        . " material_id=" . ($result['material_id'] ?? 0)
                        . " duration=" . ($result['total_duration'] ?? 0)
                        . " reason=" . ($result['skip_reason'] ?? '')
                        . " thumb=" . (!empty($result['has_thumbnail']) ? 'yes' : 'no')
                );
                return;
            }
            Log::channel('ffmpeg')->write(
                "[自动切割] 已发起 file_id={$fileId} batch_no=" . ($result['batch_no'] ?? '')
                    . " tokens_cost=" . ($result['tokens_cost'] ?? 0)
            );
        } catch (\Throwable $e) {
            Log::channel('ffmpeg')->write(
                "[自动切割] 发起失败 file_id={$fileId} err=" . $e->getMessage()
            );
        }
    }

    /**
     * 清理前一天日期目录里的历史 tmp_* 残留文件,避免误删当天并发转码临时文件。
     */
    private function cleanPreviousDayTmpFiles(string $currentDir): void
    {
        $currentDate = basename($currentDir);
        if (!preg_match('/^\d{8}$/', $currentDate)) {
            return;
        }

        $date = \DateTime::createFromFormat('Ymd', $currentDate);
        if (!$date) {
            return;
        }

        $date->modify('-1 day');
        $previousDate = $date->format('Ymd');
        $cacheKey = self::PREVIOUS_DAY_TMP_CLEAN_KEY . $previousDate;
        if ($this->cache()->get($cacheKey)) {
            return;
        }

        $previousDir = dirname($currentDir) . '/' . $previousDate;
        if (!is_dir($previousDir)) {
            $this->cache()->set($cacheKey, time(), self::PREVIOUS_DAY_TMP_CLEAN_TTL);
            return;
        }

        $tmpFiles = glob($previousDir . '/tmp_*');
        if (!$tmpFiles) {
            $this->cache()->set($cacheKey, time(), self::PREVIOUS_DAY_TMP_CLEAN_TTL);
            return;
        }

        foreach ($tmpFiles as $tmpFile) {
            $this->deleteFile($tmpFile, '前一天残留临时文件');
        }

        $this->removeEmptyDir($previousDir);
        $this->cache()->set($cacheKey, time(), self::PREVIOUS_DAY_TMP_CLEAN_TTL);
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
            Log::channel('ffmpeg')->write("[清理] ✅ 已删除{$label}: {$path}");
        } else {
            Log::channel('ffmpeg')->write("[清理] ⚠️ 删除{$label}失败: {$path}");
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
                Log::channel('ffmpeg')->write("[清理] ✅ 已删除空目录: {$dir}");
            }
        }
    }

    // ========== 并发锁 ==========

    /**
     * 获取并发锁（基于 Redis 原子计数器，跨进程可靠）
     */
    private function acquireLock(int $fileId): bool
    {
        $this->refreshRunningState();

        $ttl = self::MAX_EXECUTE_SECONDS + 60;
        $script = <<<'LUA'
local count = tonumber(redis.call('get', KEYS[1]) or '0')
local max = tonumber(ARGV[1])
if count >= max then
    return 0
end
count = redis.call('incr', KEYS[1])
redis.call('expire', KEYS[1], tonumber(ARGV[3]))
redis.call('set', KEYS[2], ARGV[4], 'EX', tonumber(ARGV[3]))
redis.call('sadd', KEYS[3], ARGV[2])
redis.call('expire', KEYS[3], tonumber(ARGV[3]))
return count
LUA;

        try {
            $result = $this->redisEval($script, [
                $this->redisKey(self::RUNNING_COUNT_KEY),
                $this->redisKey(self::LOCK_KEY . $fileId),
                $this->redisKey(self::RUNNING_IDS_SET_KEY),
            ], [
                self::MAX_CONCURRENT,
                $fileId,
                $ttl,
                time(),
            ]);

            return (int)$result > 0;
        } catch (\Throwable $e) {
            Log::channel('ffmpeg')->write("[转码队列] ⚠️ Lua获取并发锁失败，降级普通锁 file_id={$fileId} err=" . $e->getMessage());
            $running = $this->getRunningCount();
            if ($running >= self::MAX_CONCURRENT) {
                return false;
            }
            $this->cache()->inc(self::RUNNING_COUNT_KEY);
            $this->cache()->set(self::LOCK_KEY . $fileId, time(), $ttl);
            $this->rememberRunningFile($fileId);
            return true;
        }
    }

    /**
     * 释放并发锁
     */
    private function releaseLock(int $fileId): void
    {
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
                $this->redisKey(self::LOCK_KEY . $fileId),
                $this->redisKey(self::RUNNING_IDS_SET_KEY),
            ], [
                $fileId,
                self::MAX_EXECUTE_SECONDS + 60,
            ]);
        } catch (\Throwable $e) {
            Log::channel('ffmpeg')->write("[转码队列] ⚠️ Lua释放并发锁失败，降级普通释放 file_id={$fileId} err=" . $e->getMessage());
            $this->cache()->delete(self::LOCK_KEY . $fileId);
            $this->forgetRunningFile($fileId);
            $count = (int)$this->cache()->get(self::RUNNING_COUNT_KEY, 0);
            if ($count > 0) {
                $this->cache()->dec(self::RUNNING_COUNT_KEY);
            }
        }
        $this->refreshRunningState();
    }

    /**
     * 获取当前运行中的任务数
     */
    private function getRunningCount(): int
    {
        return (int)$this->cache()->get(self::RUNNING_COUNT_KEY, 0);
    }

    private function rememberRunningFile(int $fileId): void
    {
        if ($fileId <= 0) {
            return;
        }

        $ids = $this->getRunningFileIds();
        $ids[] = $fileId;
        $this->saveRunningFileIds($ids);
    }

    private function forgetRunningFile(int $fileId): void
    {
        if ($fileId <= 0) {
            return;
        }

        $ids = array_values(array_filter(
            $this->getRunningFileIds(),
            static fn($id) => (int)$id !== $fileId
        ));
        $this->saveRunningFileIds($ids);
    }

    private function refreshRunningState(): void
    {
        $ids = $this->getRunningFileIds();
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
            $this->saveRunningFileIds($activeIds);
        }
        if ($cachedCount !== $actualCount) {
            $this->cache()->set(self::RUNNING_COUNT_KEY, $actualCount, self::MAX_EXECUTE_SECONDS + 60);
            Log::channel('ffmpeg')->write("[转码队列] 并发计数修正 cached={$cachedCount} actual={$actualCount}");
        }
    }

    private function getRunningFileIds(): array
    {
        try {
            $ids = $this->redisHandler()->sMembers($this->redisKey(self::RUNNING_IDS_SET_KEY));
            if (is_array($ids) && !empty($ids)) {
                return array_values(array_unique(array_filter(array_map('intval', $ids))));
            }
        } catch (\Throwable $e) {
            Log::channel('ffmpeg')->write("[转码队列] ⚠️ 读取运行中ID集合失败: " . $e->getMessage());
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

    private function saveRunningFileIds(array $ids): void
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
            Log::channel('ffmpeg')->write("[转码队列] ⚠️ 保存运行中ID集合失败: " . $e->getMessage());
        }

        if (empty($ids)) {
            $this->cache()->delete(self::RUNNING_IDS_KEY);
            return;
        }

        $this->cache()->set(self::RUNNING_IDS_KEY, json_encode($ids, JSON_UNESCAPED_UNICODE), self::MAX_EXECUTE_SECONDS + 60);
    }

    // ========== 单任务熔断 / 硬上限 ==========

    private function isTaskAttemptExhausted(int $fileId): bool
    {
        return $fileId > 0 && $this->getTaskAttemptCount($fileId) >= self::MAX_TASK_ATTEMPTS;
    }

    private function getTaskAttemptCount(int $fileId): int
    {
        if ($fileId <= 0) {
            return 0;
        }

        return (int)$this->cache()->get($this->taskAttemptKey($fileId), 0);
    }

    private function incrementTaskAttemptCount(int $fileId): int
    {
        if ($fileId <= 0) {
            return 0;
        }

        $key = $this->taskAttemptKey($fileId);
        $count = (int)$this->cache()->get($key, 0) + 1;
        $this->cache()->set($key, $count, 7 * 86400);

        return $count;
    }

    private function resetTaskAttemptCount(int $fileId): void
    {
        if ($fileId <= 0) {
            return;
        }

        $this->cache()->delete($this->taskAttemptKey($fileId));
    }

    private function taskAttemptKey(int $fileId): string
    {
        return self::TASK_ATTEMPT_KEY . $fileId;
    }

    private function isCircuitOpen(int $fileId): bool
    {
        if ($fileId <= 0) {
            return false;
        }

        return (bool)$this->cache()->get($this->circuitKey($fileId), false);
    }

    private function incrementFailCount(int $fileId): void
    {
        if ($fileId <= 0) {
            return;
        }

        $failKey = $this->failCountKey($fileId);
        $count = (int)$this->cache()->get($failKey, 0) + 1;
        $this->cache()->set($failKey, $count, 3600);

        Log::channel('ffmpeg')->write(
            "[转码队列] 单任务失败计数 file_id={$fileId} count={$count}/" . self::CIRCUIT_BREAK_THRESHOLD
        );

        if ($count >= self::CIRCUIT_BREAK_THRESHOLD) {
            $this->cache()->set($this->circuitKey($fileId), true, self::CIRCUIT_BREAK_PAUSE);
            $this->cache()->set($this->circuitOpenedAtKey($fileId), time(), self::CIRCUIT_BREAK_PAUSE);
            $this->cache()->set($failKey, 0, 3600);
            Log::channel('ffmpeg')->write(
                "[转码队列] 单任务熔断触发 file_id={$fileId}，暂停 " . self::CIRCUIT_BREAK_PAUSE . "s"
            );
        }
    }

    private function getCircuitRemainingSeconds(int $fileId): int
    {
        $openedAt = (int)$this->cache()->get($this->circuitOpenedAtKey($fileId), 0);
        if ($openedAt <= 0) {
            return self::CIRCUIT_BREAK_PAUSE;
        }

        return max(1, self::CIRCUIT_BREAK_PAUSE - (time() - $openedAt));
    }

    private function resetFailCount(int $fileId): void
    {
        if ($fileId <= 0) {
            return;
        }

        $this->cache()->delete($this->failCountKey($fileId));
        $this->cache()->delete($this->circuitKey($fileId));
        $this->cache()->delete($this->circuitOpenedAtKey($fileId));
    }

    private function failCountKey(int $fileId): string
    {
        return self::FAIL_COUNT_KEY . $fileId;
    }

    private function circuitKey(int $fileId): string
    {
        return self::CIRCUIT_KEY . $fileId;
    }

    private function circuitOpenedAtKey(int $fileId): string
    {
        return self::CIRCUIT_KEY . $fileId . '_opened_at';
    }

    /**
     * 并发/熔断状态统一使用 Redis 仓库，保证 inc/dec 跨进程原子
     */
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

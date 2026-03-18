<?php
declare(strict_types=1);

namespace app\common\service;

use Exception;
use think\facade\Cache;
use think\facade\Log;
use think\facade\Queue;

class VideoInfoService
{
    private const CACHE_PREFIX = 'video_info_';
    private const DEFAULT_TIMEOUT = 60;
    private const CACHE_DURATION = 3600;          // 1小时
    private const RATE_LIMIT_KEY = 'video_rate_limit_';
    private const BATCH_LIMIT_KEY = 'video_batch_limit_';

    // 性能控制配置
    private const MAX_CONCURRENT_JOBS = 3;        // 最大并发处理数
    private const MAX_BATCH_SIZE = 10;            // 单次批量处理最大数量
    private const RATE_LIMIT_PER_MINUTE = 30;     // 每分钟最大请求数
    private const RATE_LIMIT_PER_HOUR = 500;      // 每小时最大请求数
    private const MEMORY_LIMIT_MB = 256;          // 内存限制

    private array $supportedFormats = [
        'mp4',
        'avi',
        'mov',
        'wmv',
        'flv',
        'webm',
        'mkv',
        '3gp',
        'ogv',
        'ts',
        'm4v',
        'mpg',
        'mpeg',
        'f4v',
        'm3u8'
    ];

    // FFmpeg可能的命令名称和路径
    private static $ffmpegCommands = [
        'ffmpeg6',
        'ffmpeg',
        '/usr/bin/ffmpeg6',
        '/usr/local/bin/ffmpeg6',
        '/usr/bin/ffmpeg',
        '/usr/local/bin/ffmpeg',
        '/opt/ffmpeg/bin/ffmpeg',
        '/snap/bin/ffmpeg'
    ];

    private static $ffprobeCommands = [
        'ffprobe6',
        'ffprobe',
        '/usr/bin/ffprobe6',
        '/usr/local/bin/ffprobe6',
        '/usr/bin/ffprobe',
        '/usr/local/bin/ffprobe',
        '/opt/ffmpeg/bin/ffprobe',
        '/snap/bin/ffprobe'
    ];

    /**
     * 获取视频信息（主入口）- 添加限流
     */
    public function getInfo(string $videoUrl, int $timeout = self::DEFAULT_TIMEOUT): ?array
    {
        try {
            // 1. 限流检查
            $this->checkRateLimit();

            // 2. 内存检查
            $this->checkMemoryUsage();

            // 3. 输入验证
            $this->validateInput($videoUrl, $timeout);

            // 4. 检查缓存
            $cachedInfo = $this->getCachedVideoInfo($videoUrl);
            if ($cachedInfo) {
                return $cachedInfo;
            }
            // 5. 检查是否正在处理中
            if ($this->isProcessing($videoUrl)) {
                throw new Exception('视频正在处理中，请稍后再试');
            }

            // 6. 标记为处理中
            $this->markAsProcessing($videoUrl);
            try {
                // 7. 预检查URL可访问性
                if (!$this->isUrlAccessible($videoUrl)) {
                    throw new Exception('视频URL无法访问或不存在');
                }

                // 8. 提取视频信息
                $videoInfo = $this->extractVideoInfo($videoUrl, $timeout);

                // 9. 缓存结果
                if ($videoInfo) {
                    $this->cacheVideoInfo($videoUrl, $videoInfo);
                }
                return $videoInfo;
            } finally {
                // 10. 清除处理标记
                $this->clearProcessingMark($videoUrl);
            }

        } catch (Exception $e) {
            Log::error('获取视频信息失败', [
                'url'          => $videoUrl,
                'error'        => $e->getMessage(),
                'memory_usage' => memory_get_usage(true)
            ]);
            throw $e;
        }
    }

    /**
     * 批量获取视频信息（优化版）- 支持队列和分页
     */
    public function batchGetInfo(array $videoUrls, int $timeout = self::DEFAULT_TIMEOUT, bool $useQueue = true): array
    {
        try {
            // 1. 批量限制检查
            $this->checkBatchLimit(count($videoUrls));

            // 2. 验证输入
            $validUrls = array_filter($videoUrls, 'filter_var', FILTER_VALIDATE_URL);

            if (count($validUrls) !== count($videoUrls)) {
                Log::warning('批量处理中发现无效URL', [
                    'total' => count($videoUrls),
                    'valid' => count($validUrls)
                ]);
            }

            // 3. 分批处理
            $batches    = array_chunk($validUrls, self::MAX_BATCH_SIZE);
            $allResults = [];

            foreach ($batches as $batchIndex => $batch) {
                Log::info("处理批次 " . ($batchIndex + 1) . "/" . count($batches), [
                    'batch_size'   => count($batch),
                    'memory_usage' => memory_get_usage(true)
                ]);

                if ($useQueue && count($batch) > 3) {
                    // 使用队列处理大批量
                    $batchResults = $this->processBatchWithQueue($batch, $timeout);
                } else {
                    // 直接处理小批量
                    $batchResults = $this->processBatchDirect($batch, $timeout);
                }

                $allResults = array_merge($allResults, $batchResults);

                // 批次间休息，避免服务器过载
                if ($batchIndex < count($batches) - 1) {
                    usleep(500000); // 0.5秒
                }
            }

            return $allResults;

        } catch (Exception $e) {
            Log::error('批量获取视频信息失败', [
                'total_urls' => count($videoUrls),
                'error'      => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 使用队列处理批量任务
     */
    private function processBatchWithQueue(array $videoUrls, int $timeout): array
    {
        $batchId = uniqid('batch_');
        $results = [];

        // 初始化批次结果缓存
        $batchCacheKey = 'batch_result_' . $batchId;
        Cache::set($batchCacheKey, [], 300);

        // 5分钟过期
        // 将任务推送到队列
        foreach ($videoUrls as $index => $url) {
            $jobData = [
                'batch_id' => $batchId,
                'index' => $index,
                'url' => $url,
                'timeout' => $timeout,
                'created_at' => time()
            ];

            // 推送到队列（需要配置队列驱动）
            try {
                Queue::push('app\common\Jobs\VideoInfoJob', $jobData, 'video_processing');
            } catch (Exception $e) {
                Log::warning('队列推送失败，改为直接处理', [
                    'url' => $url,
                    'error' => $e->getMessage()
                ]);
                // 队列失败时直接处理
                return $this->processBatchDirect($videoUrls, $timeout);
            }
        }

        // 等待处理完成或超时
        $maxWaitTime   = min($timeout + 30, 120);                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            // 最多等待2分钟
        $waitTime      = 0;
        $checkInterval = 2; // 每2秒检查一次

        while ($waitTime < $maxWaitTime) {
            $batchResults = Cache::get($batchCacheKey, []);

            if (count($batchResults) >= count($videoUrls)) {
                // 所有任务完成
                break;
            }

            sleep($checkInterval);
            $waitTime += $checkInterval;
        }

        // 获取最终结果
        $batchResults = Cache::get($batchCacheKey, []);

        // 对于未完成的任务，标记为超时
        foreach ($videoUrls as $index => $url) {
            if (!isset($batchResults[$index])) {
                $batchResults[$index] = [
                    'index'        => $index,
                    'url'          => $url,
                    'success'      => false,
                    'error'        => '队列处理超时',
                    'processed_at' => date('Y-m-d H:i:s')
                ];
            }
        }

        // 清理缓存
        Cache::delete($batchCacheKey);

        return array_values($batchResults);
    }

    /**
     * 直接处理批量任务（小批量）
     */
    private function processBatchDirect(array $videoUrls, int $timeout): array
    {
        $results           = [];
        $currentConcurrent = 0;

        foreach ($videoUrls as $index => $url) {
            // 控制并发数
            if ($currentConcurrent >= self::MAX_CONCURRENT_JOBS) {
                usleep(100000); // 等待100ms
                $currentConcurrent = max(0, $currentConcurrent - 1);
            }

            $result = [
                'index'        => $index,
                'url'          => $url,
                'success'      => false,
                'data'         => null,
                'error'        => null,
                'processed_at' => date('Y-m-d H:i:s')
            ];

            try {
                $currentConcurrent++;

                // 跳过限流检查，直接调用核心方法
                $info = $this->extractVideoInfoDirect($url, min($timeout, 30));

                if ($info) {
                    $result['success'] = true;
                    $result['data']    = $info;

                    // 缓存结果
                    $this->cacheVideoInfo($url, $info);
                } else {
                    $result['error'] = '无法解析视频信息';
                }
            } catch (Exception $e) {
                $result['error'] = $e->getMessage();
            } finally {
                $currentConcurrent--;
            }

            $results[] = $result;

            // 检查内存使用情况
            if (memory_get_usage(true) > self::MEMORY_LIMIT_MB * 1024 * 1024) {
                Log::warning('内存使用过高，强制垃圾回收', [
                    'memory_usage'    => memory_get_usage(true),
                    'processed_count' => count($results)
                ]);
                gc_collect_cycles();
            }
        }

        return $results;
    }

    /**
     * 直接提取视频信息（跳过限流等检查）
     */
    private function extractVideoInfoDirect(string $videoUrl, int $timeout): ?array
    {
        // 检查缓存
        $cachedInfo = $this->getCachedVideoInfo($videoUrl);
        if ($cachedInfo) {
            return $cachedInfo;
        }

        // 直接提取
        return $this->extractVideoInfo($videoUrl, $timeout);
    }

    /**
     * 异步获取视频信息（返回任务ID）
     */
    public function getVideoInfoAsync(string $videoUrl, int $timeout = self::DEFAULT_TIMEOUT): string
    {
        $taskId = uniqid('task_');

        $jobData = [
            'task_id'    => $taskId,
            'url'        => $videoUrl,
            'timeout'    => $timeout,
            'created_at' => time()
        ];

        // 推送到队列
        try {
            Queue::push('app\common\Jobs\VideoInfoJob', $jobData, 'video_processing');
        } catch (Exception $e) {
            Log::error('异步任务创建失败', [
                'task_id' => $taskId,
                'url'     => $videoUrl,
                'error'   => $e->getMessage()
            ]);
            throw new Exception('异步任务创建失败: ' . $e->getMessage());
        }

        // 缓存任务状态
        Cache::set('task_' . $taskId, [
            'status'     => 'pending',
            'url'        => $videoUrl,
            'created_at' => date('Y-m-d H:i:s')
        ],         1800); // 30分钟过期

        return $taskId;
    }

    /**
     * 获取异步任务结果
     */
    public function getAsyncResult(string $taskId): ?array
    {
        return Cache::get('task_' . $taskId);
    }

    /**
     * 限流检查
     */
    private function checkRateLimit(): void
    {
        $ip        = request()->ip();
        $minuteKey = self::RATE_LIMIT_KEY . 'minute_' . $ip . '_' . date('YmdHi');
        $hourKey   = self::RATE_LIMIT_KEY . 'hour_' . $ip . '_' . date('YmdH');

        $minuteCount = (int)Cache::get($minuteKey, 0);
        $hourCount   = (int)Cache::get($hourKey, 0);

        if ($minuteCount >= self::RATE_LIMIT_PER_MINUTE) {
            throw new Exception('请求过于频繁，请稍后再试（每分钟限制' . self::RATE_LIMIT_PER_MINUTE . '次）');
        }

        if ($hourCount >= self::RATE_LIMIT_PER_HOUR) {
            throw new Exception('请求过于频繁，请稍后再试（每小时限制' . self::RATE_LIMIT_PER_HOUR . '次）');
        }

        // 增加计数
        Cache::set($minuteKey, $minuteCount + 1, 60);
        Cache::set($hourKey, $hourCount + 1, 3600);
    }

    /**
     * 批量限制检查
     */
    private function checkBatchLimit(int $count): void
    {
        if ($count > self::MAX_BATCH_SIZE * 2) { // 允许最多5个批次
            throw new Exception('单次批量处理数量过多，最大允许' . (self::MAX_BATCH_SIZE * 2) . '个');
        }

        // $ip         = request()->ip();
        // $batchKey   = self::BATCH_LIMIT_KEY . $ip . '_' . date('YmdH');
        // $batchCount = (int)Cache::get($batchKey, 0);

        // if ($batchCount >= 10) { // 每小时最多10次批量请求
        //     throw new Exception('批量请求过于频繁，请稍后再试');
        // }

        // Cache::set($batchKey, $batchCount + 1, 3600);
    }

    /**
     * 内存使用检查
     */
    private function checkMemoryUsage(): void
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = self::MEMORY_LIMIT_MB * 1024 * 1024;

        if ($memoryUsage > $memoryLimit) {
            gc_collect_cycles(); // 强制垃圾回收

            $memoryUsage = memory_get_usage(true);
            if ($memoryUsage > $memoryLimit) {
                throw new Exception('系统内存不足，请稍后再试');
            }
        }
    }

    /**
     * 检查是否正在处理中
     */
    private function isProcessing(string $videoUrl): bool
    {
        $processingKey = 'processing_' . md5($videoUrl);
        return Cache::has($processingKey);
    }

    /**
     * 标记为处理中
     */
    private function markAsProcessing(string $videoUrl): void
    {
        $processingKey = 'processing_' . md5($videoUrl);
        Cache::set($processingKey, time(), 300); // 5分钟过期
    }

    /**
     * 清除处理标记
     */
    private function clearProcessingMark(string $videoUrl): void
    {
        $processingKey = 'processing_' . md5($videoUrl);
        Cache::delete($processingKey);
    }

    /**
     * 输入验证
     */
    private function validateInput(string $videoUrl, int $timeout): void
    {
        if (empty($videoUrl) || !filter_var($videoUrl, FILTER_VALIDATE_URL)) {
            throw new Exception('无效的视频URL');
        }

        if ($timeout < 10 || $timeout > 300) {
            throw new Exception('超时时间必须在10-300秒之间');
        }

        if (!$this->isVideoUrl($videoUrl)) {
            throw new Exception('URL不是支持的视频格式');
        }
    }

    /**
     * 获取缓存的视频信息
     */
    private function getCachedVideoInfo(string $videoUrl): ?array
    {
        try {
            $cacheKey = $this->getCacheKey($videoUrl);
            $cached   = Cache::get($cacheKey);

            if ($cached && is_array($cached)) {
                $cached['from_cache'] = true;
                return $cached;
            }
        } catch (Exception $e) {
            Log::warning('缓存读取失败', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * 缓存视频信息
     */
    public function cacheVideoInfo(string $videoUrl, array $videoInfo): void
    {
        try {
            $cacheKey               = $this->getCacheKey($videoUrl);
            $videoInfo['cached_at'] = date('Y-m-d H:i:s');
            Cache::set($cacheKey, $videoInfo, self::CACHE_DURATION);
        } catch (Exception $e) {
            Log::warning('缓存写入失败', ['error' => $e->getMessage()]);
        }
    }

    /**
     * 生成缓存键
     */
    private function getCacheKey(string $videoUrl): string
    {
        return self::CACHE_PREFIX . md5($videoUrl);
    }

    /**
     * 使用FFmpeg提取视频信息（核心逻辑）- 修复文件路径问题
     */
    public function extractVideoInfo(string $videoUrl, int $timeout): ?array
    {
        if (!$this->isFFmpegAvailable()) {
            $debugInfo = $this->getFFmpegDebugInfo();
            throw new Exception('FFmpeg 不可用，请确保已正确安装。调试信息: ' . json_encode($debugInfo));
        }

        $tempFilePath = null;
        $inputPath    = null;

        try {
            // 处理文件路径
            $host         = config('app.app_host', '');
            $processedUrl = $this->getFileUrl($videoUrl);
            $is_local     = !empty($host) && strpos($processedUrl, $host) === 0;

            Log::info('文件路径处理', [
                'original_url'  => $videoUrl,
                'processed_url' => $processedUrl,
                'is_local'      => $is_local,
                'host'          => $host
            ]);

            if (!$is_local) {
                // 下载远程文件到本地临时目录
                $tempFilePath = $this->downloadRemoteFile($processedUrl);
                $inputPath    = $tempFilePath;

                Log::info('下载远程文件', [
                    'temp_path'   => $tempFilePath,
                    'file_exists' => file_exists($tempFilePath),
                    'file_size'   => file_exists($tempFilePath) ? filesize($tempFilePath) : 0
                ]);
            } else {
                // 本地文件，需要转换为实际文件路径
                $inputPath = $this->getLocalFilePath($videoUrl);

                Log::info('本地文件路径', [
                    'original_url' => $videoUrl,
                    'input_path'   => $inputPath,
                    'file_exists'  => file_exists($inputPath),
                    'is_readable'  => is_readable($inputPath)
                ]);
            }

            // 验证文件是否存在
            if (!file_exists($inputPath)) {
                // 如果文件不存在，尝试几种可能的路径
                $alternativePaths = $this->getAlternativePaths($videoUrl);
                $foundPath        = null;

                foreach ($alternativePaths as $altPath) {
                    if (file_exists($altPath)) {
                        $foundPath = $altPath;
                        break;
                    }
                }

                if ($foundPath) {
                    $inputPath = $foundPath;
                    Log::info('使用替代路径', [
                        'original_path' => $inputPath,
                        'found_path'    => $foundPath
                    ]);
                } else {
                    Log::error('文件不存在，尝试的路径', [
                        'primary_path'      => $inputPath,
                        'alternative_paths' => $alternativePaths,
                        'all_failed'        => true
                    ]);
                    throw new Exception('文件不存在: ' . $inputPath);
                }
            }

            // 检查文件大小
            $fileSize = filesize($inputPath);
            if ($fileSize === 0) {
                throw new Exception('文件大小为0: ' . $inputPath);
            }

            Log::info('文件验证通过', [
                'path'     => $inputPath,
                'size'     => $fileSize,
                'readable' => is_readable($inputPath)
            ]);

            // 构建FFprobe命令
            $command = $this->buildFFprobeCommand($inputPath, $timeout);

            Log::info('执行FFprobe命令', ['command' => $command]);

            // 执行命令
            $output = $this->executeCommand($command);

            if (empty($output)) {
                throw new Exception('FFprobe 没有返回数据');
            }

            Log::info('FFprobe原始输出', ['output' => substr($output, 0, 1000) . '...']);

            // 解析JSON输出
            $data = json_decode($output, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('FFprobe 输出解析失败: ' . json_last_error_msg() . ', 原始输出: ' . substr($output, 0, 500));
            }
            // 检查是否有错误
            if (isset($data['error'])) {
                throw new Exception('FFprobe错误: ' . ($data['error']['string'] ?? '未知错误'));
            }
            if (!isset($data['streams']) || empty($data['streams'])) {
                // 尝试备用命令
                $fallbackCommand = $this->buildFallbackFFprobeCommand($inputPath, $timeout);
                Log::info('尝试备用FFprobe命令', ['command' => $fallbackCommand]);

                $fallbackOutput = $this->executeCommand($fallbackCommand);
                if ($fallbackOutput) {
                    $fallbackData = json_decode($fallbackOutput, true);
                    if ($fallbackData && isset($fallbackData['streams']) && !empty($fallbackData['streams'])) {
                        $data = $fallbackData;
                    } else {
                        throw new Exception('未找到有效的媒体流。原始输出: ' . substr($output, 0, 200) . ', 备用输出: ' . substr($fallbackOutput, 0, 200));
                    }
                } else {
                    throw new Exception('未找到有效的媒体流。原始输出: ' . substr($output, 0, 200));
                }
            }
            // 解析视频数据
            $videoInfo                   = $this->parseFFprobeData($data);
            $videoInfo['source_url']     = $videoUrl;
            $videoInfo['processed_at']   = date('Y-m-d H:i:s');
            $videoInfo['ffmpeg_version'] = $this->getFFmpegVersion();
            $videoInfo['input_path']     = $inputPath;
            $videoInfo['is_local']       = $is_local;
            return $videoInfo;

        } catch (Exception $e) {
            Log::error('提取视频信息失败', [
                'url'        => $videoUrl,
                'input_path' => $inputPath,
                'is_local'   => $is_local ?? false,
                'temp_file'  => $tempFilePath,
                'error'      => $e->getMessage()
            ]);
            throw $e;
        } finally {
            // 清理临时文件
            if ($tempFilePath && file_exists($tempFilePath)) {
                unlink($tempFilePath);
                Log::info('清理临时文件', ['path' => $tempFilePath]);
            }
        }
    }

    /**
     * 获取可能的替代路径
     */
    private function getAlternativePaths(string $videoUrl): array
    {
        $paths      = [];
        $publicPath = public_path();

        // 方式1: 直接拼接
        if (strpos($videoUrl, 'http') === 0) {
            $parsedUrl = parse_url($videoUrl);
            $path      = ltrim($parsedUrl['path'] ?? '', '/');
        } else {
            $path = ltrim($videoUrl, '/');
        }

        // 尝试不同的路径组合
        $paths[] = $publicPath . '/' . $path;
        $paths[] = $publicPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
        $paths[] = rtrim($publicPath, '/') . '/' . $path;
        $paths[] = rtrim($publicPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);

        // 如果路径包含uploads，尝试不同的组合
        if (strpos($path, 'uploads') !== false) {
            $paths[] = $publicPath . '/' . $path;
            $paths[] = dirname($publicPath) . '/' . $path; // 上级目录
        }

        // 去重
        return array_unique($paths);
    }

    /**
     * 获取文件URL（简化版FileService::getFileUrl）
     */
    private function getFileUrl(string $url): string
    {
        // 如果已经是完整URL，直接返回
        if (strpos($url, 'http') === 0) {
            return $url;
        }

        // 如果是相对路径，拼接域名
        $host = config('app.app_host', '');
        if (!empty($host)) {
            return rtrim($host, '/') . '/' . ltrim($url, '/');
        }

        return $url;
    }

    /**
     * 下载远程文件（简化版UploadService::downloadRemoteFile）
     */
    private function downloadRemoteFile(string $url): string
    {
        $tempDir  = sys_get_temp_dir();
        $tempFile = $tempDir . '/video_' . uniqid() . '.tmp';

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; VideoInfoBot/1.0)',
        ]);

        $data     = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode >= 400 || $data === false) {
            throw new Exception('下载文件失败: ' . ($error ?: 'HTTP ' . $httpCode));
        }

        if (file_put_contents($tempFile, $data) === false) {
            throw new Exception('写入临时文件失败');
        }

        return $tempFile;
    }

    /**
     * 获取本地文件的实际路径
     */
    private function getLocalFilePath(string $videoUrl): string
    {
        $path = '';

        // 如果是完整URL，提取路径部分
        if (strpos($videoUrl, 'http') === 0) {
            $parsedUrl = parse_url($videoUrl);
            $path      = $parsedUrl['path'] ?? '';
            // 去掉开头的斜杠
            $path = ltrim($path, '/');
        } else {
            // 直接使用相对路径，去掉开头的斜杠
            $path = ltrim($videoUrl, '/');
        }

        // 构建完整路径，注意不要在末尾添加斜杠
        $fullPath = public_path() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);

        // 规范化路径，移除多余的斜杠和点
        $fullPath = realpath($fullPath) ?: $fullPath;

        Log::info('路径处理详情', [
            'original_url'   => $videoUrl,
            'extracted_path' => $path,
            'public_path'    => public_path(),
            'full_path'      => $fullPath,
            'file_exists'    => file_exists($fullPath)
        ]);

        return $fullPath;
    }


    /**
     * 构建FFprobe命令 - 修改版，更好的路径处理
     */
    private function buildFFprobeCommand(string $inputPath, int $timeout): string
    {
        $ffprobeCmd = $this->getFFprobeCommand();

        // 确保路径正确转义
        $escapedPath = escapeshellarg($inputPath);

        return sprintf(
            'timeout %d %s -v quiet -print_format json -show_format -show_streams -show_error %s 2>&1',
            $timeout,
            $ffprobeCmd,
            $escapedPath
        );
    }

    /**
     * 构建备用FFprobe命令
     */
    private function buildFallbackFFprobeCommand(string $inputPath, int $timeout): string
    {
        $ffprobeCmd = $this->getFFprobeCommand();

        $escapedPath = escapeshellarg($inputPath);

        return sprintf(
            'timeout %d %s -v error -select_streams v:0 -show_entries stream=width,height,duration,codec_name,bit_rate -of json %s 2>&1',
            $timeout,
            $ffprobeCmd,
            $escapedPath
        );
    }

    /**
     * 执行系统命令
     */
    private function executeCommand(string $command): ?string
    {
        $output = shell_exec($command);

        if ($output === null || $output === false) {
            throw new Exception('命令执行失败');
        }

        return trim($output);
    }

    /**
     * 解析FFprobe数据（重构优化）
     */
    private function parseFFprobeData(array $data): array
    {
        $videoInfo = $this->initializeVideoInfo();
        // 解析格式信息
        if (isset($data['format'])) {
            $this->parseFormatInfo($data['format'], $videoInfo);
        }
        // 解析流信息
        $this->parseStreamInfo($data['streams'], $videoInfo);
        // 计算额外信息
        $this->calculateAdditionalInfo($videoInfo);

        return $videoInfo;
    }

    /**
     * 初始化视频信息结构
     */
    private function initializeVideoInfo(): array
    {
        return [
            'duration'            => null,
            'duration_formatted'  => null,
            'width'               => null,
            'height'              => null,
            'resolution'          => null,
            'fps'                 => null,
            'bitrate'             => null,
            'video_codec'         => null,
            'audio_codec'         => null,
            'format'              => null,
            'file_size'           => null,
            'file_size_formatted' => null,
            'aspect_ratio'        => null,
            'has_video'           => false,
            'has_audio'           => false,
            'stream_count'        => 0,
        ];
    }

    /**
     * 解析格式信息
     */
    private function parseFormatInfo(array $format, array &$videoInfo): void
    {
        if (isset($format['duration'])) {
            $videoInfo['duration']           = (float)$format['duration'];
            $videoInfo['duration_formatted'] = $this->formatDuration($videoInfo['duration']);
        }

        if (isset($format['bit_rate'])) {
            $videoInfo['bitrate'] = (int)$format['bit_rate'];
        }

        if (isset($format['format_name'])) {
            $videoInfo['format'] = $format['format_name'];
        }

        if (isset($format['size'])) {
            $videoInfo['file_size']           = (int)$format['size'];
            $videoInfo['file_size_formatted'] = $this->formatFileSize($videoInfo['file_size']);
        }
    }

    /**
     * 解析流信息
     */
    private function parseStreamInfo(array $streams, array &$videoInfo): void
    {
        $videoInfo['stream_count'] = count($streams);

        foreach ($streams as $stream) {
            $codecType = $stream['codec_type'] ?? '';

            switch ($codecType) {
                case 'video':
                    $this->parseVideoStream($stream, $videoInfo);
                    $videoInfo['has_video'] = true;
                    break;

                case 'audio':
                    $this->parseAudioStream($stream, $videoInfo);
                    $videoInfo['has_audio'] = true;
                    break;
            }
        }
    }

    /**
     * 解析视频流
     */
    private function parseVideoStream(array $stream, array &$videoInfo): void
    {
        if (isset($stream['width'], $stream['height'])) {
            $videoInfo['width']      = (int)$stream['width'];
            $videoInfo['height']     = (int)$stream['height'];
            $videoInfo['resolution'] = $videoInfo['width'] . 'x' . $videoInfo['height'];
        }

        if (isset($stream['r_frame_rate'])) {
            $videoInfo['fps'] = $this->parseFrameRate($stream['r_frame_rate']);
        }

        if (isset($stream['codec_name'])) {
            $videoInfo['video_codec'] = $stream['codec_name'];
        }
    }

    /**
     * 解析音频流
     */
    private function parseAudioStream(array $stream, array &$videoInfo): void
    {
        if (isset($stream['codec_name'])) {
            $videoInfo['audio_codec'] = $stream['codec_name'];
        }
    }

    /**
     * 计算额外信息
     */
    private function calculateAdditionalInfo(array &$videoInfo): void
    {
        // 计算宽高比
        if ($videoInfo['width'] && $videoInfo['height']) {
            $gcd                       = $this->gcd($videoInfo['width'], $videoInfo['height']);
            $videoInfo['aspect_ratio'] = ($videoInfo['width'] / $gcd) . ':' . ($videoInfo['height'] / $gcd);
        }
    }

    /**
     * 解析帧率
     */
    private function parseFrameRate(string $frameRate): float
    {
        if (strpos($frameRate, '/') !== false) {
            [$numerator, $denominator] = explode('/', $frameRate);
            return $denominator > 0 ? round((float)$numerator / (float)$denominator, 2) : 0;
        }

        return (float)$frameRate;
    }

    /**
     * 检查是否为视频URL
     */
    private function isVideoUrl(string $url): bool
    {
        $parsedUrl = parse_url($url);
        $path      = $parsedUrl['path'] ?? '';
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($extension, $this->supportedFormats) ||
               strpos($url, '.m3u8') !== false || // HLS流
               strpos($url, 'rtmp://') === 0;     // RTMP流
    }

    /**
     * 检查URL可访问性（优化版）
     */
    private function isUrlAccessible(string $url): bool
    {
        // 对于流媒体URL，跳过可访问性检查
        if (strpos($url, 'rtmp://') === 0 || strpos($url, '.m3u8') !== false) {
            return true;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_NOBODY         => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; VideoInfoBot/1.0)',
            CURLOPT_RETURNTRANSFER => true,
        ]);

        $result   = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            Log::debug('URL访问检查失败', ['url' => $url, 'error' => $error]);
            return false;
        }

        return $result !== false && $httpCode >= 200 && $httpCode < 400;
    }

    /**
     * 格式化时长
     */
    private function formatDuration($seconds): string
    {
        $roundedSeconds = round($seconds, 2);
        $totalSeconds = (int) $roundedSeconds; // 先整体转整数，避免后续浮点运算

        $hours = intdiv($totalSeconds, 3600);
        $minutes = intdiv($totalSeconds % 3600, 60);
        $secs = $totalSeconds % 60;

        // 处理小数部分（保留两位小数）
        $micro = (int) round(($roundedSeconds - $totalSeconds) * 100);

        if ($hours > 0) {
            return $micro > 0
                ? sprintf('%02d:%02d:%02d.%02d', $hours, $minutes, $secs, $micro)
                : sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
        }

        return $micro > 0
            ? sprintf('%02d:%02d.%02d', $minutes, $secs, $micro)
            : sprintf('%02d:%02d', $minutes, $secs);
    }

    /**
     * 格式化文件大小
     */
    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow   = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow   = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * 检查FFmpeg可用性 - 修改版，支持多种命令
     */
    private function isFFmpegAvailable(): bool
    {
        static $available = null;

        if ($available === null) {
            $available = false;

            foreach (self::$ffmpegCommands as $cmd) {
                $output = shell_exec($cmd . ' -version 2>&1');
                if ($output && (strpos($output, 'ffmpeg version') !== false || strpos($output, 'ffmpeg6 version') !== false)) {
                    $available = true;
                    break;
                }
            }
        }

        return $available;
    }

    /**
     * 获取可用的FFmpeg命令
     */
    private function getFFmpegCommand(): ?string
    {
        static $command = null;

        if ($command === null) {
            foreach (self::$ffmpegCommands as $cmd) {
                $output = shell_exec($cmd . ' -version 2>&1');
                if ($output && (strpos($output, 'ffmpeg version') !== false || strpos($output, 'ffmpeg6 version') !== false)) {
                    $command = $cmd;
                    break;
                }
            }
        }

        return $command;
    }

    /**
     * 获取可用的FFprobe命令
     */
    private function getFFprobeCommand(): ?string
    {
        static $command = null;

        if ($command === null) {
            foreach (self::$ffprobeCommands as $cmd) {
                $output = shell_exec($cmd . ' -version 2>&1');
                if ($output && (strpos($output, 'ffprobe version') !== false || strpos($output, 'ffprobe6 version') !== false)) {
                    $command = $cmd;
                    break;
                }
            }
        }

        return $command;
    }

    /**
     * 获取FFmpeg版本 - 修改版
     */
    private function getFFmpegVersion(): ?string
    {
        $cmd = $this->getFFmpegCommand();
        if (!$cmd) {
            return null;
        }

        $output = shell_exec($cmd . ' -version 2>&1');
        if ($output && preg_match('/ffmpeg\d* version ([^\s]+)/', $output, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * 获取FFmpeg调试信息 - 新增方法
     */
    public function getFFmpegDebugInfo(): array
    {
        $debugInfo = [
            'shell_exec_enabled'     => function_exists('shell_exec'),
            'php_version'            => PHP_VERSION,
            'system_info'            => php_uname(),
            'path_env'               => $_ENV['PATH'] ?? getenv('PATH') ?: 'Not set',
            'ffmpeg_commands_check'  => [],
            'ffprobe_commands_check' => []
        ];

        // 检查所有可能的FFmpeg命令
        foreach (self::$ffmpegCommands as $cmd) {
            $output                                   = shell_exec($cmd . ' -version 2>&1');
            $debugInfo['ffmpeg_commands_check'][$cmd] = [
                'command_output' => $output ?: 'No output',
                'available'      => ($output && (strpos($output, 'ffmpeg version') !== false || strpos($output, 'ffmpeg6 version') !== false))
            ];
        }

        // 检查所有可能的FFprobe命令
        foreach (self::$ffprobeCommands as $cmd) {
            $output                                    = shell_exec($cmd . ' -version 2>&1');
            $debugInfo['ffprobe_commands_check'][$cmd] = [
                'command_output' => $output ?: 'No output',
                'available'      => ($output && (strpos($output, 'ffprobe version') !== false || strpos($output, 'ffprobe6 version') !== false))
            ];
        }

        return $debugInfo;
    }

    /**
     * 计算最大公约数
     */
    private function gcd(int $a, int $b): int
    {
        return $b === 0 ? $a : $this->gcd($b, $a % $b);
    }

    /**
     * 生成视频缩略图（优化版）- 修改版
     */
    public function generateThumbnail(string $videoUrl, float $time = 1.0, array $options = []): ?string
    {
        $tempFilePath = null;

        try {
            if (!$this->isFFmpegAvailable()) {
                throw new Exception('FFmpeg 不可用');
            }

            $thumbnailDir = root_path() . 'public/uploads/thumbnails/' . date('Ymd') . '/';
            if (!is_dir($thumbnailDir)) {
                mkdir($thumbnailDir, 0755, true);
            }

            // 生成唯一文件名
            $thumbnailName = 'thumb_' . md5($videoUrl . $time . serialize($options)) . '.jpg';
            $thumbnailPath = $thumbnailDir . $thumbnailName;

            // 如果缩略图已存在，直接返回
            if (file_exists($thumbnailPath)) {
                return 'uploads/thumbnails/' . date('Ymd') . '/' . $thumbnailName;
            }

            // 处理输入文件路径（与extractVideoInfo相同的逻辑）
            $host         = config('app.app_host', '');
            $processedUrl = $this->getFileUrl($videoUrl);
            $is_local     = !empty($host) && strpos($processedUrl, $host) === 0;

            if (!$is_local) {
                $tempFilePath = $this->downloadRemoteFile($processedUrl);
                $inputPath    = $tempFilePath;
            } else {
                $inputPath = $this->getLocalFilePath($videoUrl);
            }

            // 验证文件存在
            if (!file_exists($inputPath)) {
                throw new Exception('输入文件不存在: ' . $inputPath);
            }

            // 构建FFmpeg命令
            $width   = $options['width'] ?? 320;
            $height  = $options['height'] ?? 240;
            $quality = $options['quality'] ?? 2; // 1-31, 越小质量越好

            $ffmpegCmd = $this->getFFmpegCommand();
            $command   = sprintf(
                '%s -i %s -ss %.2f -vframes 1 -vf "scale=%d:%d" -q:v %d -y %s 2>&1',
                $ffmpegCmd,
                escapeshellarg($inputPath),
                $time,
                $width,
                $height,
                $quality,
                escapeshellarg($thumbnailPath)
            );

            $output = shell_exec($command);

            if (!file_exists($thumbnailPath)) {
                Log::error('缩略图生成失败', [
                    'url'        => $videoUrl,
                    'input_path' => $inputPath,
                    'command'    => $command,
                    'output'     => $output
                ]);
                return null;
            }

            return 'uploads/thumbnails/' . date('Ymd') . '/' . $thumbnailName;

        } catch (Exception $e) {
            Log::error('生成缩略图异常', [
                'url'   => $videoUrl,
                'error' => $e->getMessage()
            ]);
            return null;
        } finally {
            // 清理临时文件
            if ($tempFilePath && file_exists($tempFilePath)) {
                unlink($tempFilePath);
            }
        }
    }

    /**
     * 获取支持的格式列表
     */
    public function getSupportedFormats(): array
    {
        return $this->supportedFormats;
    }

    /**
     * 清理缓存
     */
    public function clearCache(string $videoUrl = null): bool
    {
        try {
            if ($videoUrl) {
                $cacheKey = $this->getCacheKey($videoUrl);
                return Cache::delete($cacheKey);
            }

            // 清理所有视频信息缓存
            return Cache::clear();
        } catch (Exception $e) {
            Log::error('清理缓存失败', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * 获取系统状态 - 修改版
     */
    public function getSystemStatus(): array
    {
        return [
            'ffmpeg_available'        => $this->isFFmpegAvailable(),
            'ffmpeg_command'          => $this->getFFmpegCommand(),
            'ffprobe_command'         => $this->getFFprobeCommand(),
            'ffmpeg_version'          => $this->getFFmpegVersion(),
            'supported_formats_count' => count($this->supportedFormats),
            'php_version'             => PHP_VERSION,
            'system_time'             => date('Y-m-d H:i:s'),
            'debug_info'              => $this->getFFmpegDebugInfo()
        ];
    }

    /**
     * 获取系统负载状态
     */
    public function getSystemLoad(): array
    {
        $load = [
            'memory_usage'     => memory_get_usage(true),
            'memory_peak'      => memory_get_peak_usage(true),
            'memory_limit'     => ini_get('memory_limit'),
            'processing_count' => $this->getProcessingCount(),
            'cache_stats'      => $this->getCacheStats()
        ];

        if (function_exists('sys_getloadavg')) {
            $load['system_load'] = sys_getloadavg();
        }

        return $load;
    }

    /**
     * 获取正在处理的任务数量
     */
    private function getProcessingCount(): int
    {
        // 这里需要根据您的缓存实现来统计processing_*键的数量
        // 简化实现，实际可能需要更复杂的逻辑
        return 0;
    }

    /**
     * 获取缓存统计
     */
    private function getCacheStats(): array
    {
        return [
            'video_info_cache_count' => 0, // 需要实现具体统计逻辑
            'cache_hit_rate'         => 0.0
        ];
    }

    /**
     * 测试媒体文件 - 增强版调试方法
     */
    public function testMediaFile(string $videoUrl): array
    {
        $debugInfo    = [];
        $tempFilePath = null;

        try {
            $debugInfo['original_url']     = $videoUrl;
            $debugInfo['is_valid_url']     = filter_var($videoUrl, FILTER_VALIDATE_URL) !== false;
            $debugInfo['is_video_url']     = $this->isVideoUrl($videoUrl);
            $debugInfo['is_accessible']    = $this->isUrlAccessible($videoUrl);
            $debugInfo['ffmpeg_available'] = $this->isFFmpegAvailable();
            $debugInfo['ffmpeg_command']   = $this->getFFmpegCommand();
            $debugInfo['ffprobe_command']  = $this->getFFprobeCommand();

            // 文件路径处理测试
            $host         = config('app.app_host', '');
            $processedUrl = $this->getFileUrl($videoUrl);
            $is_local     = !empty($host) && strpos($processedUrl, $host) === 0;

            $debugInfo['file_processing'] = [
                'host'          => $host,
                'processed_url' => $processedUrl,
                'is_local'      => $is_local,
                'public_path'   => public_path()
            ];

            if (!$is_local) {
                try {
                    $tempFilePath = $this->downloadRemoteFile($processedUrl);
                    $inputPath    = $tempFilePath;

                    $debugInfo['file_processing']['temp_file']        = $tempFilePath;
                    $debugInfo['file_processing']['temp_file_exists'] = file_exists($tempFilePath);
                    $debugInfo['file_processing']['temp_file_size']   = file_exists($tempFilePath) ? filesize($tempFilePath) : 0;
                } catch (Exception $e) {
                    $debugInfo['file_processing']['download_error'] = $e->getMessage();
                    return $debugInfo;
                }
            } else {
                $inputPath        = $this->getLocalFilePath($videoUrl);
                $alternativePaths = $this->getAlternativePaths($videoUrl);

                $debugInfo['file_processing']['local_path']        = $inputPath;
                $debugInfo['file_processing']['local_file_exists'] = file_exists($inputPath);
                $debugInfo['file_processing']['local_file_size']   = file_exists($inputPath) ? filesize($inputPath) : 0;
                $debugInfo['file_processing']['alternative_paths'] = $alternativePaths;

                // 检查所有可能的路径
                $debugInfo['file_processing']['path_check'] = [];
                foreach ($alternativePaths as $altPath) {
                    $debugInfo['file_processing']['path_check'][$altPath] = [
                        'exists'   => file_exists($altPath),
                        'readable' => is_readable($altPath),
                        'size'     => file_exists($altPath) ? filesize($altPath) : 0
                    ];
                }

                // 找到存在的文件路径
                foreach ($alternativePaths as $altPath) {
                    if (file_exists($altPath)) {
                        $inputPath                                  = $altPath;
                        $debugInfo['file_processing']['found_path'] = $altPath;
                        break;
                    }
                }
            }

            if ($this->isFFmpegAvailable() && file_exists($inputPath)) {
                // 测试基础命令
                $basicCommand               = $this->buildFFprobeCommand($inputPath, 30);
                $debugInfo['basic_command'] = $basicCommand;

                $output                        = shell_exec($basicCommand);
                $debugInfo['basic_output']     = substr($output ?: '', 0, 1000);
                $debugInfo['basic_json_valid'] = json_decode($output ?: '', true) !== null;

                // 测试备用命令
                $fallbackCommand               = $this->buildFallbackFFprobeCommand($inputPath, 30);
                $debugInfo['fallback_command'] = $fallbackCommand;

                $fallbackOutput                   = shell_exec($fallbackCommand);
                $debugInfo['fallback_output']     = substr($fallbackOutput ?: '', 0, 1000);
                $debugInfo['fallback_json_valid'] = json_decode($fallbackOutput ?: '', true) !== null;
            }

            return $debugInfo;

        } catch (Exception $e) {
            $debugInfo['error'] = $e->getMessage();
            return $debugInfo;
        } finally {
            // 清理临时文件
            if ($tempFilePath && file_exists($tempFilePath)) {
                unlink($tempFilePath);
            }
        }
    }
}
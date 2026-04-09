<?php

declare(strict_types=1);

namespace app\common\service;

use Exception;
use think\facade\Cache;
use think\facade\Log;
use think\facade\Queue;

/**
 * 视频信息服务类
 * 提供视频信息获取、批量处理、缩略图生成等功能
 */
class VideoInfoService
{
    // ==================== 常量定义 ====================

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

    // ==================== 属性定义 ====================

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

    // ==================== 核心方法 ====================

    /**
     * 获取视频信息（主入口）- 添加限流
     *
     * @param string $videoUrl 视频URL
     * @param int $timeout 超时时间
     * @return array|null
     * @throws Exception
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
                'url' => $videoUrl,
                'error' => $e->getMessage(),
                'memory_usage' => memory_get_usage(true)
            ]);
            throw $e;
        }
    }

    /**
     * 批量获取视频信息（优化版）- 支持队列和分页
     *
     * @param array $videoUrls 视频URL数组
     * @param int $timeout 超时时间
     * @param bool $useQueue 是否使用队列
     * @return array
     * @throws Exception
     */
    public function batchGetInfo(array $videoUrls, int $timeout = self::DEFAULT_TIMEOUT, bool $useQueue = true): array
    {
        try {
            // 1. 批量限制检查
            $this->checkBatchLimit(count($videoUrls));

            // 2. 验证输入
            $validUrls = array_filter($videoUrls, function ($url) {
                return filter_var($url, FILTER_VALIDATE_URL) !== false;
            });

            if (count($validUrls) !== count($videoUrls)) {
                Log::warning('批量处理中发现无效URL', [
                    'total' => count($videoUrls),
                    'valid' => count($validUrls)
                ]);
            }

            // 3. 分批处理
            $batches = array_chunk($validUrls, self::MAX_BATCH_SIZE);
            $allResults = [];

            foreach ($batches as $batchIndex => $batch) {
                Log::info("处理批次 " . ($batchIndex + 1) . "/" . count($batches), [
                    'batch_size' => count($batch),
                    'memory_usage' => memory_get_usage(true)
                ]);

                $batchResults = $this->processBatchDirect($batch, $timeout);

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
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 生成视频缩略图（增强版 - 支持OSS和本地存储）
     *
     * @param string $videoUrl 视频URL（支持本地路径或远程URL）
     * @param float $time 截取时间点（秒）
     * @param array $options 选项配置
     *   - width: 宽度（默认：null，保持原始比例）
     *   - height: 高度（默认：null，保持原始比例）
     *   - quality: 质量（1-31，数字越小质量越高，默认：2）
     *   - format: 输出格式（jpg/png，默认：jpg）
     *   - force: 是否强制重新生成（默认：false）
     * @return array|null 返回缩略图信息
     * @throws Exception
     */
    public function generateThumbnail(string $videoUrl, float $time = 1.0, array $options = []): ?array
    {
        $tempFilePath = null;
        $thumbnailPath = null;

        try {
            // 1. 检查 FFmpeg
            if (!$this->isFFmpegAvailable()) {
                throw new Exception('FFmpeg 不可用，无法生成缩略图');
            }

            // 2. 获取存储配置
            $storageDefault = ConfigService::get('storage', 'default', 'local');
            $isOSS = ($storageDefault !== 'local');

            // 3. 解析和验证参数
            $params = $this->parseThumbnailOptions($options);

            // 4. 生成缩略图路径
            $thumbnailInfo = $this->prepareThumbnailPath($videoUrl, $time, $params);

            // 5. 检查缓存（仅本地存储时检查本地缓存）
            if (!$params['force'] && !$isOSS && file_exists($thumbnailInfo['path'])) {
                Log::info('使用缓存的缩略图', [
                    'video_url' => $videoUrl,
                    'thumbnail' => $thumbnailInfo['url']
                ]);

                return [
                    'url' => $thumbnailInfo['url'],
                    'full_url' => FileService::getFileUrl($thumbnailInfo['url']),
                    'path' => $thumbnailInfo['path'],
                    'size' => filesize($thumbnailInfo['path']),
                    'cached' => true,
                    'storage' => 'local'
                ];
            }

            // 6. 解析视频路径
            $inputPath = $this->resolveVideoPath($videoUrl);

            // 标记临时文件（如果是远程下载的）
            if (strpos($inputPath, 'runtime/temp/') !== false) {
                $tempFilePath = $inputPath;
            }

            // 7. 验证视频文件
            $this->validateVideoFile($inputPath);

            // 8. 获取视频元数据并调整时间点
            $metadata = $this->getVideoMetadata($inputPath);
            $time = $this->adjustTimePoint($time, $metadata);

            // 9. 生成缩略图到本地临时目录
            $this->executeThumbnailGeneration(
                $inputPath,
                $thumbnailInfo['path'],
                $time,
                $params
            );

            // 10. 验证输出
            $this->validateThumbnailOutput($thumbnailInfo['path']);

            // 11. 如果是OSS存储，上传到OSS
            $finalUrl = $thumbnailInfo['url'];
            $finalFullUrl = FileService::getFileUrl($thumbnailInfo['url']);
            $storageType = 'local';

            if ($isOSS) {
                try {
                    // 上传到OSS
                    $ossResult = $this->uploadThumbnailToOSS(
                        $thumbnailInfo['path'],
                        $thumbnailInfo['url']
                    );

                    if ($ossResult['success']) {
                        $finalUrl = $ossResult['url'];
                        $finalFullUrl = $ossResult['full_url'];
                        $storageType = $storageDefault;


                        // 删除本地临时文件
                        if (file_exists($thumbnailInfo['path'])) {
                            @unlink($thumbnailInfo['path']);
                        }
                    }
                } catch (Exception $e) {

                    // OSS上传失败，继续使用本地存储
                }
            }

            // 12. 清理视频临时文件
            if ($tempFilePath && file_exists($tempFilePath)) {
                @unlink($tempFilePath);
            }



            return [
                'url' => $finalUrl,
                'full_url' => $finalFullUrl,
                'path' => $thumbnailInfo['path'],
                'size' => file_exists($thumbnailInfo['path']) ? filesize($thumbnailInfo['path']) : 0,
                'cached' => false,
                'storage' => $storageType
            ];
        } catch (Exception $e) {
            // 清理临时文件
            if ($tempFilePath && file_exists($tempFilePath)) {
                @unlink($tempFilePath);
            }
            if (isset($thumbnailInfo['path']) && file_exists($thumbnailInfo['path'])) {
                @unlink($thumbnailInfo['path']);
            }
            throw $e;
        }
    }
    /**
     * 本地存储：直接返回本地路径
     */
    private function handleLocalThumbnail(array $thumbnailInfo): array
    {
        return [
            'url'      => $thumbnailInfo['url'],
            'full_url' => FileService::getFileUrl($thumbnailInfo['url']),
            'path'     => $thumbnailInfo['path'],
            'size'     => filesize($thumbnailInfo['path']),
            'cached'   => false,
            'storage'  => 'local'
        ];
    }

    /**
     * OSS存储：上传到OSS后删除本地，返回OSS路径
     */
    private function handleOSSThumbnail(array $thumbnailInfo, string $storageDefault): array
    {
        $localPath = $thumbnailInfo['path'];
        $ossDir    = dirname($thumbnailInfo['url']); // uploads/thumbnails/20260407

        try {
            // 获取存储配置
            $config = [
                'default' => $storageDefault,
                'engine'  => ConfigService::get('storage') ?? [],
            ];

            $storageDriver = new \app\common\service\storage\Driver($config);
            $storageDriver->setUploadFileByReal($localPath);

            if (!$storageDriver->upload($ossDir)) {
                throw new Exception('OSS上传失败: ' . $storageDriver->getError());
            }

            // ✅ 拼接真实 OSS 路径（保持原文件名，不用 getFileName()）
            $ossRelativePath = $ossDir . '/' . basename($localPath);
            $ossFullUrl      = FileService::getFileUrl($ossRelativePath);

            Log::info('缩略图已上传OSS', [
                'local_path' => $localPath,
                'oss_path'   => $ossRelativePath,
                'full_url'   => $ossFullUrl,
            ]);

            // ✅ 上传成功后删除本地临时文件
            @unlink($localPath);

            return [
                'url'      => $ossRelativePath,
                'full_url' => $ossFullUrl,
                'path'     => $ossRelativePath, // OSS 场景下 path 存相对路径
                'size'     => 0,                // OSS 文件不在本地，size 无意义
                'cached'   => false,
                'storage'  => $storageDefault
            ];
        } catch (Exception $e) {
            Log::error('缩略图上传OSS失败，降级使用本地', [
                'local_path' => $localPath,
                'error'      => $e->getMessage()
            ]);

            // ✅ OSS 失败降级：保留本地文件，返回本地路径
            return [
                'url'      => $thumbnailInfo['url'],
                'full_url' => FileService::getFileUrl($thumbnailInfo['url']),
                'path'     => $localPath,
                'size'     => file_exists($localPath) ? filesize($localPath) : 0,
                'cached'   => false,
                'storage'  => 'local'
            ];
        }
    }

    /**
     * 上传缩略图到OSS
     *
     * @param string $localPath 本地文件路径
     * @param string $ossPath OSS路径
     * @return array
     */
    private function uploadThumbnailToOSS(string $localPath, string $ossPath): array
    {
        try {
            // 获取存储配置
            $config = [
                'default' => ConfigService::get('storage', 'default', 'local'),
                'engine'  => ConfigService::get('storage') ?? ['local' => []],
            ];

            // 使用 StorageDriver 上传
            $storageDriver = new \app\common\service\storage\Driver($config);

            // 设置要上传的本地文件
            $storageDriver->setUploadFileByReal($localPath);

            // 获取文件信息
            $fileInfo = $storageDriver->getFileInfo();

            // 解析OSS路径（去除文件名，只保留目录）
            $pathParts = pathinfo($ossPath);
            $saveDir = $pathParts['dirname'];

            // 上传到OSS
            if (!$storageDriver->upload($saveDir)) {
                return [
                    'success' => false,
                    'error' => $storageDriver->getError()
                ];
            }

            // 获取上传后的文件名
            $fileName = $storageDriver->getFileName();
            $ossFullPath = $saveDir . '/' . str_replace("\\", "/", $fileName);

            return [
                'success' => true,
                'url' => $ossFullPath,
                'full_url' => FileService::getFileUrl($ossFullPath)
            ];
        } catch (Exception $e) {
            Log::error('上传缩略图到OSS失败', [
                'local_path' => $localPath,
                'oss_path' => $ossPath,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }


    // ==================== 缩略图相关私有方法 ====================

    /**
     * 解析缩略图选项参数
     *
     * @param array $options
     * @return array
     */
    private function parseThumbnailOptions(array $options): array
    {
        return [
            'width' => isset($options['width']) ? max(1, intval($options['width'])) : null,
            'height' => isset($options['height']) ? max(1, intval($options['height'])) : null,
            'quality' => max(1, min(31, intval($options['quality'] ?? 2))),
            'format' => in_array($options['format'] ?? 'jpg', ['jpg', 'png']) ? ($options['format'] ?? 'jpg') : 'jpg',
            'force' => (bool)($options['force'] ?? false),
        ];
    }

    /**
     * 准备缩略图保存路径
     *
     * @param string $videoUrl
     * @param float $time
     * @param array $params
     * @return array
     * @throws Exception
     */
    private function prepareThumbnailPath(string $videoUrl, float $time, array $params): array
    {
        $date = date('Ymd');
        $thumbnailDir = root_path() . 'public/uploads/thumbnails/' . $date . '/';

        if (!is_dir($thumbnailDir) && !mkdir($thumbnailDir, 0755, true)) {
            throw new Exception('无法创建缩略图目录: ' . $thumbnailDir);
        }

        $thumbnailName = 'thumb_' . md5($videoUrl . $time . serialize($params)) . '.' . $params['format'];
        $thumbnailPath = $thumbnailDir . $thumbnailName;
        $thumbnailUrl = 'uploads/thumbnails/' . $date . '/' . $thumbnailName;

        return [
            'path' => $thumbnailPath,
            'url' => $thumbnailUrl,
            'name' => $thumbnailName
        ];
    }

    /**
     * 解析视频路径（处理本地路径和远程URL）
     *
     * @param string $videoUrl
     * @return string 本地文件路径
     * @throws Exception
     */
    private function resolveVideoPath(string $videoUrl): string
    {
        // 1. 远程 URL → 下载到临时文件
        if (preg_match('/^https?:\/\//i', $videoUrl)) {
            return $this->downloadVideoForThumbnail($videoUrl);
        }

        // 2. ✅ 绝对路径 → 直接验证返回，不做任何拼接
        if (strpos($videoUrl, '/') === 0) {
            clearstatcache(true, $videoUrl);
            if (file_exists($videoUrl) && is_file($videoUrl)) {
                Log::info('视频路径解析成功（绝对路径）', [
                    'resolved_path' => $videoUrl,
                    'file_size'     => filesize($videoUrl)
                ]);
                return $videoUrl;
            }
            throw new Exception('视频文件不存在: ' . $videoUrl);
        }

        // 3. 相对路径 → 尝试多种拼接方式
        $cleanUrl = ltrim($videoUrl, '/\\');

        $possiblePaths = [
            root_path() . 'public/' . $cleanUrl,
            root_path() . 'public/' . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $cleanUrl),
            public_path() . $cleanUrl,
            public_path() . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $cleanUrl),
            $cleanUrl,
        ];

        foreach ($possiblePaths as $path) {
            clearstatcache(true, $path);
            if (file_exists($path) && is_file($path)) {
                Log::info('视频路径解析成功（相对路径）', [
                    'original_url'  => $videoUrl,
                    'resolved_path' => $path,
                    'file_size'     => filesize($path)
                ]);
                return $path;
            }
        }

        Log::error('视频文件未找到', [
            'original_url' => $videoUrl,
            'tried_paths'  => $possiblePaths
        ]);

        throw new Exception('视频文件不存在: ' . $videoUrl);
    }


    /**
     * 验证视频文件
     *
     * @param string $filePath
     * @throws Exception
     */
    private function validateVideoFile(string $filePath): void
    {
        if (!file_exists($filePath)) {
            throw new Exception('视频文件不存在: ' . $filePath);
        }

        if (!is_readable($filePath)) {
            throw new Exception('视频文件不可读: ' . $filePath);
        }

        $fileSize = filesize($filePath);
        if ($fileSize === 0 || $fileSize === false) {
            throw new Exception('视频文件大小为0或无法读取');
        }
    }

    /**
     * 调整时间点（不超过视频时长）
     *
     * @param float $time
     * @param array $metadata
     * @return float
     */
    private function adjustTimePoint(float $time, array $metadata): float
    {
        $duration = $metadata['duration'] ?? 0;

        if ($duration > 0 && $time > $duration) {
            Log::warning('截取时间超过视频时长，调整为中点', [
                'requested_time' => $time,
                'duration' => $duration
            ]);
            return $duration / 2;
        }

        return max(0, $time);
    }

    /**
     * 执行缩略图生成
     *
     * @param string $inputPath
     * @param string $outputPath
     * @param float $time
     * @param array $params
     * @throws Exception
     */
    private function executeThumbnailGeneration(
        string $inputPath,
        string $outputPath,
        float $time,
        array $params
    ): void {
        $ffmpegCmd = $this->buildThumbnailCommand(
            $inputPath,
            $outputPath,
            $time,
            $params['width'],
            $params['height'],
            $params['quality'],
            $params['format']
        );

        Log::info('执行FFmpeg命令', [
            'command' => $ffmpegCmd,
            'input' => $inputPath,
            'output' => $outputPath
        ]);

        $output = [];
        $returnCode = 0;
        exec($ffmpegCmd . ' 2>&1', $output, $returnCode);

        if ($returnCode !== 0) {
            $errorMsg = implode("\n", $output);
            Log::error('FFmpeg执行失败', [
                'command' => $ffmpegCmd,
                'return_code' => $returnCode,
                'output' => $errorMsg
            ]);
            throw new Exception('生成缩略图失败: ' . $errorMsg);
        }
    }

    /**
     * 验证缩略图输出
     *
     * @param string $thumbnailPath
     * @throws Exception
     */
    private function validateThumbnailOutput(string $thumbnailPath): void
    {
        if (!file_exists($thumbnailPath)) {
            throw new Exception('缩略图文件未生成');
        }

        $size = filesize($thumbnailPath);
        if ($size < 100) {
            @unlink($thumbnailPath);
            throw new Exception('生成的缩略图文件过小（' . $size . ' bytes），可能损坏');
        }
    }

    /**
     * 下载远程视频用于生成缩略图（仅下载部分内容）
     *
     * @param string $remoteUrl
     * @return string 本地临时文件路径
     * @throws Exception
     */
    private function downloadVideoForThumbnail(string $remoteUrl): string
    {
        try {
            // 1. 生成临时文件路径
            $tempDir = root_path() . 'runtime/temp/';
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $extension = pathinfo(parse_url($remoteUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'mp4';
            $tempFile = $tempDir . 'video_' . uniqid() . '.' . $extension;

            // 2. 下载文件（仅下载前10MB，足够生成缩略图）
            $ch = curl_init($remoteUrl);
            $fp = fopen($tempFile, 'w+');

            if (!$fp) {
                throw new Exception('无法创建临时文件: ' . $tempFile);
            }

            curl_setopt_array($ch, [
                CURLOPT_FILE => $fp,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; ThumbnailGenerator/1.0)',
                CURLOPT_RANGE => '0-10485760', // 仅下载前10MB
                CURLOPT_SSL_VERIFYPEER => false,
            ]);

            $success = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);

            curl_close($ch);
            fclose($fp);

            // 3. 检查下载结果
            if (!$success || ($httpCode !== 200 && $httpCode !== 206)) {
                @unlink($tempFile);
                throw new Exception("下载视频失败: HTTP {$httpCode} - {$error}");
            }

            if (!file_exists($tempFile) || filesize($tempFile) === 0) {
                @unlink($tempFile);
                throw new Exception('下载的视频文件无效');
            }

            Log::info('远程视频下载成功（部分）', [
                'remote_url' => $remoteUrl,
                'temp_file' => $tempFile,
                'size' => filesize($tempFile)
            ]);

            return $tempFile;
        } catch (Exception $e) {
            if (isset($tempFile) && file_exists($tempFile)) {
                @unlink($tempFile);
            }
            throw $e;
        }
    }

    /**
     * 构建生成缩略图的 FFmpeg 命令
     *
     * @param string $inputPath
     * @param string $outputPath
     * @param float $time
     * @param int|null $width
     * @param int|null $height
     * @param int $quality
     * @param string $format
     * @return string
     */
    private function buildThumbnailCommand(
        string $inputPath,
        string $outputPath,
        float $time,
        ?int $width,
        ?int $height,
        int $quality,
        string $format
    ): string {
        $ffmpegBin = $this->findFFmpegBinary();

        $parts = [
            $ffmpegBin,
            '-ss ' . $time,      // 跳转到指定时间
            '-i ' . escapeshellarg($inputPath),
            '-vframes 1',        // 只提取一帧
        ];

        // 构建缩放参数
        if ($width && $height) {
            // 指定了宽高
            $parts[] = "-vf scale={$width}:{$height}";
        } elseif ($width) {
            // 只指定宽度，高度自动计算（-2确保是偶数）
            $parts[] = "-vf scale={$width}:-2";
        } elseif ($height) {
            // 只指定高度，宽度自动计算
            $parts[] = "-vf scale=-2:{$height}";
        }

        // 设置质量
        if ($format === 'jpg') {
            $parts[] = '-q:v ' . $quality;
        } else {
            $parts[] = '-compression_level ' . min(9, $quality);
        }

        $parts[] = '-y'; // 覆盖已存在的文件
        $parts[] = escapeshellarg($outputPath);

        return implode(' ', $parts);
    }

    /**
     * 获取视频元数据（时长等）
     *
     * @param string $filePath
     * @return array
     */
    private function getVideoMetadata(string $filePath): array
    {
        try {
            $ffprobeBin = $this->findFFprobeBinary();
            $command = sprintf(
                '%s -v quiet -print_format json -show_format -show_streams %s',
                $ffprobeBin,
                escapeshellarg($filePath)
            );

            $output = shell_exec($command);
            if (!$output) {
                return [];
            }

            $data = json_decode($output, true);
            if (!$data) {
                return [];
            }

            return [
                'duration' => floatval($data['format']['duration'] ?? 0),
                'size' => intval($data['format']['size'] ?? 0),
                'bit_rate' => intval($data['format']['bit_rate'] ?? 0),
                'format_name' => $data['format']['format_name'] ?? '',
            ];
        } catch (Exception $e) {
            Log::warning('获取视频元数据失败', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    // ==================== FFmpeg 工具方法 ====================

    /**
     * 检查 FFmpeg 是否可用
     *
     * @return bool
     */
    private function isFFmpegAvailable(): bool
    {
        try {
            $this->findFFmpegBinary();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 查找 FFmpeg 可执行文件
     *
     * @return string
     * @throws Exception
     */
    private function findFFmpegBinary(): string
    {
        foreach (self::$ffmpegCommands as $cmd) {
            if ($this->commandExists($cmd)) {
                return $cmd;
            }
        }
        throw new Exception('未找到 FFmpeg 可执行文件');
    }

    /**
     * 查找 FFprobe 可执行文件
     *
     * @return string
     * @throws Exception
     */
    private function findFFprobeBinary(): string
    {
        foreach (self::$ffprobeCommands as $cmd) {
            if ($this->commandExists($cmd)) {
                return $cmd;
            }
        }
        throw new Exception('未找到 FFprobe 可执行文件');
    }

    /**
     * 检查命令是否存在
     *
     * @param string $command
     * @return bool
     */
    private function commandExists(string $command): bool
    {
        $checkCmd = stripos(PHP_OS, 'WIN') === 0
            ? "where {$command} 2>nul"
            : "command -v {$command} 2>/dev/null";

        $output = shell_exec($checkCmd);
        return !empty($output);
    }

    // ==================== 原有方法（保持不变）====================

    /**
     * 限流检查
     *
     * @throws Exception
     */
    private function checkRateLimit(): void
    {
        $minuteKey = self::RATE_LIMIT_KEY . 'minute_' . date('YmdHi');
        $hourKey = self::RATE_LIMIT_KEY . 'hour_' . date('YmdH');

        $minuteCount = Cache::get($minuteKey, 0);
        $hourCount = Cache::get($hourKey, 0);

        if ($minuteCount >= self::RATE_LIMIT_PER_MINUTE) {
            throw new Exception('请求过于频繁，请稍后再试（每分钟限制' . self::RATE_LIMIT_PER_MINUTE . '次）');
        }

        if ($hourCount >= self::RATE_LIMIT_PER_HOUR) {
            throw new Exception('请求过于频繁，请稍后再试（每小时限制' . self::RATE_LIMIT_PER_HOUR . '次）');
        }

        Cache::inc($minuteKey);
        Cache::inc($hourKey);
        Cache::expire($minuteKey, 60);
        Cache::expire($hourKey, 3600);
    }

    /**
     * 检查内存使用
     *
     * @throws Exception
     */
    private function checkMemoryUsage(): void
    {
        $memoryUsage = memory_get_usage(true) / 1024 / 1024;

        if ($memoryUsage > self::MEMORY_LIMIT_MB) {
            throw new Exception('系统内存不足，请稍后再试');
        }
    }

    /**
     * 验证输入
     *
     * @param string $videoUrl
     * @param int $timeout
     * @throws Exception
     */
    private function validateInput(string $videoUrl, int $timeout): void
    {
        if (empty($videoUrl)) {
            throw new Exception('视频URL不能为空');
        }

        if ($timeout < 1 || $timeout > 300) {
            throw new Exception('超时时间必须在1-300秒之间');
        }
    }

    /**
     * 获取缓存的视频信息
     *
     * @param string $videoUrl
     * @return array|null
     */
    private function getCachedVideoInfo(string $videoUrl): ?array
    {
        $cacheKey = self::CACHE_PREFIX . md5($videoUrl);
        $cached = Cache::get($cacheKey);

        if ($cached) {
            Log::info('使用缓存的视频信息', ['url' => $videoUrl]);
            return $cached;
        }

        return null;
    }

    /**
     * 缓存视频信息
     *
     * @param string $videoUrl
     * @param array $videoInfo
     */
    public function cacheVideoInfo(string $videoUrl, array $videoInfo): void
    {
        $cacheKey = self::CACHE_PREFIX . md5($videoUrl);
        Cache::set($cacheKey, $videoInfo, self::CACHE_DURATION);
    }

    /**
     * 检查是否正在处理
     *
     * @param string $videoUrl
     * @return bool
     */
    private function isProcessing(string $videoUrl): bool
    {
        $lockKey = 'video_processing_' . md5($videoUrl);
        return Cache::has($lockKey);
    }

    /**
     * 标记为处理中
     *
     * @param string $videoUrl
     */
    private function markAsProcessing(string $videoUrl): void
    {
        $lockKey = 'video_processing_' . md5($videoUrl);
        Cache::set($lockKey, true, 300); // 5分钟锁
    }

    /**
     * 清除处理标记
     *
     * @param string $videoUrl
     */
    private function clearProcessingMark(string $videoUrl): void
    {
        $lockKey = 'video_processing_' . md5($videoUrl);
        Cache::delete($lockKey);
    }

    /**
     * 检查URL是否可访问
     *
     * @param string $url
     * @return bool
     */
    private function isUrlAccessible(string $url): bool
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_NOBODY => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode >= 200 && $httpCode < 400;
    }

    /**
     * 提取视频信息
     *
     * @param string $videoUrl
     * @param int $timeout
     * @return array|null
     * @throws Exception
     */
    public function extractVideoInfo(string $videoUrl, int $timeout): ?array
    {
        $ffprobeBin = $this->findFFprobeBinary();

        $command = sprintf(
            '%s -v quiet -print_format json -show_format -show_streams -timeout %d %s',
            $ffprobeBin,
            $timeout * 1000000, // 转换为微秒
            escapeshellarg($videoUrl)
        );

        $output = shell_exec($command);

        if (!$output) {
            throw new Exception('无法获取视频信息');
        }

        $data = json_decode($output, true);

        if (!$data || !isset($data['format'])) {
            throw new Exception('视频信息解析失败');
        }

        return $this->formatVideoInfo($data);
    }

    /**
     * 格式化视频信息
     *
     * @param array $data
     * @return array
     */
    private function formatVideoInfo(array $data): array
    {
        $format = $data['format'] ?? [];
        $streams = $data['streams'] ?? [];

        $videoStream = null;
        $audioStream = null;

        foreach ($streams as $stream) {
            if ($stream['codec_type'] === 'video' && !$videoStream) {
                $videoStream = $stream;
            }
            if ($stream['codec_type'] === 'audio' && !$audioStream) {
                $audioStream = $stream;
            }
        }

        return [
            'duration' => floatval($format['duration'] ?? 0),
            'size' => intval($format['size'] ?? 0),
            'bit_rate' => intval($format['bit_rate'] ?? 0),
            'format_name' => $format['format_name'] ?? '',
            'video' => $videoStream ? [
                'codec' => $videoStream['codec_name'] ?? '',
                'width' => intval($videoStream['width'] ?? 0),
                'height' => intval($videoStream['height'] ?? 0),
                'fps' => $this->calculateFps($videoStream),
            ] : null,
            'audio' => $audioStream ? [
                'codec' => $audioStream['codec_name'] ?? '',
                'sample_rate' => intval($audioStream['sample_rate'] ?? 0),
                'channels' => intval($audioStream['channels'] ?? 0),
            ] : null,
        ];
    }

    /**
     * 计算帧率
     *
     * @param array $stream
     * @return float
     */
    private function calculateFps(array $stream): float
    {
        if (isset($stream['r_frame_rate'])) {
            $parts = explode('/', $stream['r_frame_rate']);
            if (count($parts) === 2 && $parts[1] > 0) {
                return round($parts[0] / $parts[1], 2);
            }
        }
        return 0.0;
    }

    /**
     * 检查批量限制
     *
     * @param int $count
     * @throws Exception
     */
    private function checkBatchLimit(int $count): void
    {
        if ($count > self::MAX_BATCH_SIZE * 2) { // 允许最多5个批次
            throw new Exception('单次批量处理数量过多，最大允许' . (self::MAX_BATCH_SIZE * 2) . '个');
        }
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
     * 使用队列处理批量
     *
     * @param array $urls
     * @param int $timeout
     * @return array
     */
    private function processBatchWithQueue(array $urls, int $timeout): array
    {
        $batchId = uniqid('batch_');
        $results = [];

        // 初始化批次结果缓存
        $batchCacheKey = 'batch_result_' . $batchId;
        Cache::set($batchCacheKey, [], 300);

        // 5分钟过期
        // 将任务推送到队列
        foreach ($urls as $index => $url) {
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
                return $this->processBatchDirect($urls, $timeout);
            }
        }

        // 等待处理完成或超时
        $maxWaitTime   = min($timeout + 30, 120);                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            // 最多等待2分钟
        $waitTime      = 0;
        $checkInterval = 2; // 每2秒检查一次

        while ($waitTime < $maxWaitTime) {
            $batchResults = Cache::get($batchCacheKey, []);

            if (count($batchResults) >= count($urls)) {
                // 所有任务完成
                break;
            }

            sleep($checkInterval);
            $waitTime += $checkInterval;
        }

        // 获取最终结果
        $batchResults = Cache::get($batchCacheKey, []);

        // 对于未完成的任务，标记为超时
        foreach ($urls as $index => $url) {
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
     * 直接处理批量
     *
     * @param array $urls
     * @param int $timeout
     * @return array
     */
    private function processBatchDirect(array $urls, int $timeout): array
    {
        $results           = [];
        $currentConcurrent = 0;

        foreach ($urls as $index => $url) {
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
     * 生成视频缩略图（请勿修改）
     * 参数: video_url, time(可选), options(可选)
     */
    public function commonVideoThumbnail($params): array
    {
        try {
            $videoUrl = $params['video_url'];
            $time     = $params['time'] ? (float)$params['time'] : 1.0;
            $options  = $params['options'] ?? [];

            if (empty($videoUrl)) {
                return ['result' => false, 'url' => '', 'msg' => '视频URL不能为空'];
            }

            // 验证时间参数
            if ($time < 0) {
                return ['result' => false, 'url' => '', 'msg' => '时间参数不能为负数'];
            }

            // 验证选项参数
            if (!empty($options) && !is_array($options)) {
                return ['result' => false, 'url' => '', 'msg' => '选项参数必须是数组'];
            }

            $targetWidth = $options['width'] ?? 960;
            $targetHeight = $options['height'] ?? 0;

            // ========== 方式1：用FFmpeg命令行解析（无需扩展，兼容性好） ==========
            $ffmpegCmd = "ffprobe -v error -select_streams v:0 -show_entries stream=width,height -of csv=s=x:p=0 " . escapeshellarg($videoUrl);
            exec($ffmpegCmd, $output, $returnVar);
            if ($returnVar !== 0 || empty($output[0])) {
                $finalWidth = 320;
                $finalHeight = 240;
            } else {
                list($originWidth, $originHeight) = explode('x', $output[0]);
                $originWidth = (int)$originWidth;
                $originHeight = (int)$originHeight;

                // 3. 关键计算：按原比例缩放，适配目标宽/高（只传宽/只传高/都传的情况）
                $scaleRatio = min(
                    $targetWidth > 0 ? $targetWidth / $originWidth : 1,
                    $targetHeight > 0 ? $targetHeight / $originHeight : 1
                );
                // 最终等比例尺寸（取整，避免小数）
                $finalWidth = (int)round($originWidth * $scaleRatio);
                $finalHeight = (int)round($originHeight * $scaleRatio);
            }

            // 设置默认选项
            $defaultOptions = [
                'width'   => $finalWidth,
                'height'  => $finalHeight,
                'quality' => 2
            ];
            $options        = array_merge($defaultOptions, $options);

            // 验证尺寸限制
            if ($options['width'] > 2000 || $options['height'] > 2000) {
                return ['result' => false, 'url' => '', 'msg' => '缩略图宽高不能超过1920'];
            }

            $thumbnailUrl = $this->commonGenerateThumbnail($videoUrl, $time, $options);
            if (!$thumbnailUrl) {
                return ['result' => false, 'url' => '', 'msg' => '缩略图生成失败'];
            }

            $localPath    = public_path() . $thumbnailUrl;
            $thumbnailUrl = FileService::getFileUrl($thumbnailUrl);
            $default      = ConfigService::get('storage', 'default', 'local');
            if ($default != 'local') {
                if (preg_match('/uploads\/(.+?)\/\d{8}/', $thumbnailUrl, $matches)) {
                    $ossPath = $matches[0];
                    $url     = UploadService::uploadToOSS($localPath, $ossPath);
                }
            }

            return [
                'result'  => true,
                'msg'     => '缩略图生成成功',
                'url'     => isset($url) ? FileService::getFileUrl($url) : $thumbnailUrl,
                'time'    => $time,
                'options' => $options
            ];
        } catch (Exception $e) {
            return ['result' => false, 'url' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 生成视频缩略图(请勿修改)
     */
    public function commonGenerateThumbnail(string $videoUrl, float $time = 1.0, array $options = []): ?string
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
            $processedUrl = $this->commonGetFileUrl($videoUrl);
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
     * 获取文件URL(请勿修改)
     */
    private function commonGetFileUrl(string $url): string
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
     * 下载远程文件（请勿修改）
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
     * 获取本地文件的实际路径（请勿修改）
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
}

<?php

namespace app\common\service\ffmpeg;

use app\common\Jobs\VideoSliceJob;
use app\common\Jobs\VideoSliceTimeoutException;
use app\common\service\ConfigService;
use app\common\service\FileService;
use app\common\service\UploadService;
use app\common\service\storage\Driver as StorageDriver;
use app\model\VideoSlice;
use app\model\VideoSliceItem;
use app\validate\VideoSliceValidate;
use think\facade\Db;
use think\facade\Log;
use think\facade\Queue;

class VideoSliceService
{
    private static ?int $executeDeadline = null;

    private const FFMPEG_COMMANDS = [
        'ffmpeg6',
        'ffmpeg',
        '/usr/bin/ffmpeg6',
        '/usr/local/bin/ffmpeg6',
        '/usr/bin/ffmpeg',
        '/usr/local/bin/ffmpeg',
        '/opt/ffmpeg/bin/ffmpeg',
        '/snap/bin/ffmpeg',
    ];

    private const FFPROBE_COMMANDS = [
        'ffprobe6',
        'ffprobe',
        '/usr/bin/ffprobe6',
        '/usr/local/bin/ffprobe6',
        '/usr/bin/ffprobe',
        '/usr/local/bin/ffprobe',
        '/opt/ffmpeg/bin/ffprobe',
        '/snap/bin/ffprobe',
    ];

    public static function setExecuteDeadline(?int $deadline): void
    {
        self::$executeDeadline = $deadline;
    }

    public static function clearExecuteDeadline(): void
    {
        self::$executeDeadline = null;
    }

    public static function assertWithinDeadline(): void
    {
        if (self::$executeDeadline !== null && time() >= self::$executeDeadline) {
            throw new VideoSliceTimeoutException(
                '切片任务执行超时 video_id deadline=' . self::$executeDeadline
            );
        }
    }

    public static function dispatch(array $payload, ?string $queueName = null)
    {
        $validate = new VideoSliceValidate();
        if (!$validate->scene('uploaded')->check($payload)) {
            throw new \InvalidArgumentException((string)$validate->getError());
        }

        $service = new self();
        $existingSlice = $service->findSliceRecord((int)$payload['video_id']);
        if (!$existingSlice->isEmpty() && (int)$existingSlice->status === VideoSlice::STATUS_SUCCESS) {
            Log::channel('video_slice')->write(
                "[切片队列] 已存在成功记录，跳过投递 video_id=" . (int)$payload['video_id']
            );
            return 0;
        }

        if ($service->canSlice($payload)) {
            $service->createOrUpdateSliceRecord($payload, [
                'status' => VideoSlice::STATUS_PENDING,
            ]);
        }

        $queueName = $queueName ?: (string)config('video_slice.queue_name', 'video_slice');
        try {
            $jobId = Queue::push(VideoSliceJob::class, $payload, $queueName);
        } catch (\Throwable $e) {
            if ($service->canSlice($payload)) {
                $service->createFailedRecord($payload);
            }
            throw $e;
        }

        if ($jobId === false) {
            if ($service->canSlice($payload)) {
                $service->createFailedRecord($payload);
            }
            throw new \RuntimeException('视频切片任务投递失败');
        }

        Log::channel('video_slice')->write(
            "[切片队列] 已投递任务 video_id=" . (int)$payload['video_id'] . " queue={$queueName} job_id={$jobId}"
        );

        return $jobId;
    }

    public function preparePendingMaterials(array $payload): array
    {
        $validate = new VideoSliceValidate();
        if (!$validate->scene('uploaded')->check($payload)) {
            throw new \InvalidArgumentException((string)$validate->getError());
        }

        $module = (string)$payload['module'];
        $moduleScope = (array)config('video_slice.module_scope', ['character_ip']);
        if (!in_array($module, $moduleScope, true)) {
            Log::channel('video_slice')->write("[视频切片] 占位素材跳过，模块不在生效范围内 module={$module}");
            return [];
        }

        if ((int)($payload['persona_id'] ?? 0) <= 0) {
            Log::channel('video_slice')->write("[视频切片] 占位素材跳过，persona_id为空");
            return [];
        }

        $videoId = (int)$payload['video_id'];
        $probeTempDir = '';
        try {
            $sourcePath = $this->prepareProbeSourceForPayload($payload, $probeTempDir);
            $duration = $this->probeDuration($sourcePath);

            $threshold = (float)config('video_slice.max_duration_threshold', 10);
            $sliceDuration = (float)config('video_slice.slice_duration', 5);
            if ($sliceDuration <= 0) {
                throw new \RuntimeException('video_slice.slice_duration 配置必须大于0');
            }

            $videoSize = $this->probeVideoSize($sourcePath);
            if ($duration <= $threshold) {
                $slice = $this->storeOriginalAsMaterial($payload, $duration, $videoSize);
                $materialIds = $this->getSliceMaterialIds($slice);

                Log::channel('video_slice')->write(
                    "[视频切片] 时长未超过阈值，原视频已即时入库 video_id={$videoId} duration={$duration}"
                );
                return $materialIds;
            }

            $tempDir = rtrim((string)config('video_slice.temp_path'), DIRECTORY_SEPARATOR)
                . DIRECTORY_SEPARATOR . 'preview_' . $videoId;

            $slicePlans = $this->buildSlicePlans(
                $tempDir,
                (string)$payload['original_name'],
                $duration,
                $sliceDuration,
                $this->getExtension((string)$payload['original_name']),
                $this->formatDimension($videoSize['width'] ?? ''),
                $this->formatDimension($videoSize['height'] ?? '')
            );
            $this->createOrUpdateSliceRecord($payload, [
                'original_duration' => $this->formatSeconds($duration),
                'slice_count' => count($slicePlans),
                'status' => VideoSlice::STATUS_PENDING,
            ]);

            $slicePlans = $this->createPendingSliceMaterials($payload, $slicePlans, $this->resolveUserId($payload));
            $materialIds = array_values(array_map('intval', array_column($slicePlans, 'material_id')));

            Log::channel('video_slice')->write(
                "[视频切片] 占位素材已创建 video_id={$videoId} count=" . count($materialIds)
            );

            return $materialIds;
        } finally {
            $this->cleanupTempDir($probeTempDir);
        }
    }

    public function handle(array $payload): ?VideoSlice
    {
        $validate = new VideoSliceValidate();
        if (!$validate->scene('uploaded')->check($payload)) {
            throw new \InvalidArgumentException((string)$validate->getError());
        }
        self::assertWithinDeadline();
        Log::channel('video_slice')->write('[视频切片] 开始处理 payload=' . json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
        $module = (string)$payload['module'];
        $moduleScope = (array)config('video_slice.module_scope', ['character_ip']);
        if (!in_array($module, $moduleScope, true)) {
            Log::channel('video_slice')->write("[视频切片] 模块不在生效范围内，跳过 module={$module}");
            return null;
        }

        if ((int)($payload['persona_id'] ?? 0) <= 0) {
            Log::channel('video_slice')->write("[视频切片] persona_id为空，跳过切片");
            return null;
        }

        $videoId = (int)$payload['video_id'];
        $existingSlice = $this->findSliceRecord($videoId);
        if (!$existingSlice->isEmpty() && (int)$existingSlice->status === VideoSlice::STATUS_SUCCESS) {
            Log::channel('video_slice')->write("[视频切片] 已存在成功记录，跳过重复任务 video_id={$videoId}");
            return $existingSlice;
        }

        $this->createOrUpdateSliceRecord($payload, [
            'status' => VideoSlice::STATUS_PROCESSING,
        ]);

        $originalPath = (string)$payload['file_path'];
        $originalName = (string)$payload['original_name'];
        $probeTempDir = '';
        try {
            $sourcePath = $this->prepareProbeSourceForPayload($payload, $probeTempDir);
            $duration = $this->probeDuration($sourcePath);
            $videoSize = $this->probeVideoSize($sourcePath);
        } catch (\Throwable $e) {
            $this->cleanupTempDir($probeTempDir);
            $this->createFailedRecord($payload);
            Log::channel('video_slice')->error("[视频切片] 媒体信息检测失败 video_id={$videoId} error=" . $e->getMessage());
            throw $e;
        }

        Log::channel('video_slice')->write(sprintf(
            '[视频切片] 收到视频 video_id=%d module=%s duration=%.2f original_path=%s source_path=%s',
            $videoId,
            $module,
            $duration,
            $originalPath,
            $sourcePath
        ));

        $threshold = (float)config('video_slice.max_duration_threshold', 10);
        $sliceDuration = (float)config('video_slice.slice_duration', 5);
        if ($sliceDuration <= 0) {
            throw new \RuntimeException('video_slice.slice_duration 配置必须大于0');
        }

        try {
            return $duration <= $threshold
                ? $this->storeOriginalAsMaterial($payload, $duration, $videoSize)
                : $this->sliceAndStore($payload, $sourcePath, $duration, $sliceDuration);
        } finally {
            $this->cleanupTempDir($probeTempDir);
        }
    }

    private function storeOriginalAsMaterial(array $payload, float $duration, array $videoSize): VideoSlice
    {
        return Db::transaction(function () use ($payload, $duration, $videoSize) {
            $videoId = (int)$payload['video_id'];
            $userId = $this->resolveUserId($payload);
            $originalPath = (string)$payload['file_path'];
            $originalName = (string)$payload['original_name'];
            $materialName = $this->buildOriginalMaterialName($originalName);
            $width = $this->formatDimension($videoSize['width'] ?? '');
            $height = $this->formatDimension($videoSize['height'] ?? '');

            $slice = $this->createOrUpdateSliceRecord($payload, [
                'original_duration' => $this->formatSeconds($duration),
                'original_path' => $originalPath,
                'slice_count' => 1,
                'status' => VideoSlice::STATUS_SUCCESS,
            ]);

            if (!empty($this->getSliceMaterialIds($slice))) {
                Log::channel('video_slice')->write("[视频切片] 原视频素材已存在，跳过重复入库 video_id={$videoId}");
                return $slice;
            }

            $materialId = MaterialService::addVideo([
                'original_video_id' => $videoId,
                'persona_id' => $payload['persona_id'] ?? null,
                'user_id' => $payload['user_id'] ?? null,
                'name' => $materialName,
                'duration' => $duration,
                'file_path' => $this->normalizeStoragePath($originalPath),
                'source_type' => 'original',
                'width' => $width,
                'height' => $height,
                'thumbnail_url' => (string)($payload['thumbnail_url'] ?? ''),
            ]);

            VideoSliceItem::create([
                'slice_id' => $slice->id,
                'user_id' => $userId,
                'sequence' => 1,
                'name' => $materialName,
                'time_start' => '0.00',
                'time_end' => $this->formatSeconds($duration),
                'duration' => $this->formatSeconds($duration),
                'file_path' => $this->normalizeStoragePath($originalPath),
                'file_size' => $this->getFileSize($originalPath),
                'material_id' => $materialId,
                'width' => $width,
                'height' => $height,
            ]);

            Log::channel('video_slice')->write("[视频切片] 时长未超过阈值，原视频已作为素材入库 video_id={$videoId}");
            return $slice;
        });
    }

    private function getSliceMaterialIds(VideoSlice $slice): array
    {
        if ($slice->isEmpty()) {
            return [];
        }

        $materialIds = VideoSliceItem::where('slice_id', $slice->id)
            ->where('material_id', '>', 0)
            ->column('material_id');

        return array_values(array_unique(array_map('intval', $materialIds)));
    }

    private function createFailedRecord(array $payload): void
    {
        $this->createOrUpdateSliceRecord($payload, [
            'original_path' => (string)($payload['file_path'] ?? ''),
            'status' => VideoSlice::STATUS_FAILED,
        ]);
        $this->markSliceMaterialsFailedByPayload($payload);
    }

    public function markFailed(array $payload): void
    {
        $this->createFailedRecord($payload);
    }

    private function canSlice(array $payload): bool
    {
        $module = (string)($payload['module'] ?? '');
        $moduleScope = (array)config('video_slice.module_scope', ['character_ip']);

        return in_array($module, $moduleScope, true)
            && (int)($payload['persona_id'] ?? 0) > 0;
    }

    private function findSliceRecord(int $videoId): VideoSlice
    {
        if ($videoId <= 0) {
            return VideoSlice::where('id', 0)->findOrEmpty();
        }

        return VideoSlice::where('original_video_id', $videoId)
            // 素材切割任务(batch_no 非空)与旧切片队列分轨，避免互相覆盖
            ->where(function ($query) {
                $query->whereNull('batch_no')->whereOr('batch_no', '');
            })
            ->order(['id' => 'desc'])
            ->findOrEmpty();
    }

    private function createOrUpdateSliceRecord(array $payload, array $attributes = []): VideoSlice
    {
        $videoId = (int)($payload['video_id'] ?? 0);
        if ($videoId <= 0) {
            throw new \InvalidArgumentException('视频ID不能为空');
        }

        $baseData = [
            'user_id' => $this->resolveUserId($payload),
            'original_name' => (string)($payload['original_name'] ?? ''),
            'original_path' => (string)($payload['file_path'] ?? ''),
        ];

        $slice = $this->findSliceRecord($videoId);
        if ($slice->isEmpty()) {
            return VideoSlice::create(array_merge([
                'original_video_id' => $videoId,
                'original_duration' => '0.00',
                'slice_count' => 0,
                'status' => VideoSlice::STATUS_PENDING,
            ], $baseData, $attributes));
        }

        $currentStatus = (int)$slice->status;
        $nextStatus = array_key_exists('status', $attributes) ? (int)$attributes['status'] : null;
        if ($currentStatus === VideoSlice::STATUS_SUCCESS && $nextStatus !== VideoSlice::STATUS_SUCCESS) {
            return $slice;
        }
        if ($currentStatus === VideoSlice::STATUS_PROCESSING && $nextStatus === VideoSlice::STATUS_PENDING) {
            unset($attributes['status']);
        }

        $slice->save(array_merge($baseData, $attributes));
        return $slice->refresh();
    }

    private function sliceAndStore(array $payload, string $sourcePath, float $duration, float $sliceDuration): VideoSlice
    {
        $videoId = (int)$payload['video_id'];
        $userId = $this->resolveUserId($payload);
        $originalName = (string)$payload['original_name'];
        $originalPath = (string)$payload['file_path'];
        $tempDir = $this->prepareTempDir($videoId);
        $finalFiles = [];
        $sliceMaterialIds = [];
        $remainingMaterialIds = [];

        $slice = $this->createOrUpdateSliceRecord($payload, [
            'original_duration' => $this->formatSeconds($duration),
            'original_path' => $originalPath,
            'slice_count' => 0,
            'status' => VideoSlice::STATUS_PROCESSING,
        ]);

        try {
            $standardizedSource = $this->standardizeSourceForSlicing(
                $sourcePath,
                $tempDir,
                $payload['custom_specs'] ?? null
            );
            $standardizedSourcePath = (string)$standardizedSource['path'];
            $standardizedDuration = (float)($standardizedSource['duration'] ?? 0);
            if ($standardizedDuration <= 0) {
                $standardizedDuration = $this->probeDuration($standardizedSourcePath);
            }
            $standardizedWidth = (float)($standardizedSource['width'] ?? 0);
            $standardizedHeight = (float)($standardizedSource['height'] ?? 0);
            if ($standardizedWidth <= 0 || $standardizedHeight <= 0) {
                $standardizedSize = $this->probeVideoSize($standardizedSourcePath);
                $standardizedWidth = (float)$standardizedSize['width'];
                $standardizedHeight = (float)$standardizedSize['height'];
            }
            $standardizedWidthText = $this->formatDimension($standardizedWidth);
            $standardizedHeightText = $this->formatDimension($standardizedHeight);

            $slicePlans = $this->buildSlicePlans(
                $tempDir,
                $originalName,
                $standardizedDuration,
                $sliceDuration,
                $this->getExtension($standardizedSourcePath),
                $standardizedWidthText,
                $standardizedHeightText
            );
            $slice->save(['slice_count' => count($slicePlans)]);
            $slicePlans = $this->createPendingSliceMaterials($payload, $slicePlans, $userId);
            $sliceMaterialIds = array_column($slicePlans, 'material_id');
            $remainingMaterialIds = array_values(array_map('intval', $sliceMaterialIds));
            MaterialService::deleteObsoleteSlicePlaceholders(
                $videoId,
                $sliceMaterialIds,
                $userId,
                (int)($payload['persona_id'] ?? 0)
            );

            foreach ($slicePlans as $slicePlan) {
                self::assertWithinDeadline();
                $tempFiles = $this->makeSlices($standardizedSourcePath, [$slicePlan]);
                if (empty($tempFiles)) {
                    continue;
                }

                $storedFiles = $this->moveSlicesToStorage($tempFiles, $originalName, $videoId);
                foreach ($storedFiles as $item) {
                    $this->markStoredSliceSuccess($payload, $slice, $item, $userId);
                    $finalFiles[] = $item;
                    $materialId = (int)($item['material_id'] ?? 0);
                    $remainingMaterialIds = array_values(array_filter(
                        $remainingMaterialIds,
                        static fn($id) => (int)$id !== $materialId
                    ));
                }
            }

            if (empty($finalFiles)) {
                $this->cleanupTempDir($tempDir);
                throw new \RuntimeException("全部子视频时长过短，切片失败 video_id={$videoId}");
            }

            Db::transaction(function () use ($payload, $slice, $finalFiles, $duration, $userId, $videoId, $originalPath) {
                MaterialService::deleteOriginalUploadMaterial([
                    'original_video_id' => $videoId,
                    'persona_id' => $payload['persona_id'] ?? 0,
                    'user_id' => $userId,
                    'file_path' => $originalPath,
                ]);

                $slice->save([
                    'original_duration' => $this->formatSeconds($duration),
                    'original_path' => '',
                    'slice_count' => count($finalFiles),
                    'status' => VideoSlice::STATUS_SUCCESS,
                ]);
            });

            $this->deleteOriginal($originalPath);
            $this->cleanupTempDir($tempDir);

            Log::channel('video_slice')->write(sprintf(
                '[视频切片] 切片完成 video_id=%d success=%d failed=%d',
                $videoId,
                count($finalFiles),
                count($slicePlans) - count($finalFiles)
            ));
            return $slice->refresh();
        } catch (\Throwable $e) {
            $slice->save(['status' => VideoSlice::STATUS_FAILED]);
            if (!empty($remainingMaterialIds)) {
                MaterialService::markSliceFailed($remainingMaterialIds);
            } else {
                $this->markSliceMaterialsFailedByPayload($payload);
            }
            $this->cleanupTempDir($tempDir);
            Log::channel('video_slice')->error("[视频切片] 切片失败 video_id={$videoId} error=" . $e->getMessage());
            throw $e;
        }
    }

    private function markStoredSliceSuccess(array $payload, VideoSlice $slice, array $item, int $userId): void
    {
        Db::transaction(function () use ($payload, $slice, $item, $userId) {
            $materialId = (int)($item['material_id'] ?? 0);
            $successParams = [
                'duration' => $item['duration'],
                'file_path' => $item['relative_path'],
                'width' => $item['width'],
                'height' => $item['height'],
            ];
            if (!empty($payload['thumbnail_url'])) {
                $successParams['thumbnail_url'] = (string)$payload['thumbnail_url'];
            }

            MaterialService::markSliceSuccess($materialId, $successParams);

            VideoSliceItem::where('slice_id', $slice->id)
                ->where('material_id', $materialId)
                ->delete();

            VideoSliceItem::create([
                'slice_id' => $slice->id,
                'user_id' => $userId,
                'sequence' => $item['sequence'],
                'name' => $item['name'],
                'time_start' => $this->formatSeconds($item['start']),
                'time_end' => $this->formatSeconds($item['end']),
                'duration' => $this->formatSeconds($item['duration']),
                'file_path' => $item['relative_path'],
                'file_size' => $item['file_size'],
                'material_id' => $materialId,
                'width' => $item['width'],
                'height' => $item['height'],
            ]);
        });
    }

    private function standardizeSourceForSlicing(string $sourcePath, string $tempDir, ?array $customSpecs = null): array
    {
        $standardizedPath = $tempDir . DIRECTORY_SEPARATOR . 'standardized_source.mp4';
        $standardizeInput = $this->prepareStandardizeInput($sourcePath, $tempDir);

        Log::channel('video_slice')->write("[视频切片] 开始转码标准化 source={$standardizeInput}");
        $result = UploadService::standardizeVideoToLocal($standardizeInput, $standardizedPath, $customSpecs);
        $this->assertOutputFile($standardizedPath);

        Log::channel('video_slice')->write("[视频切片] 转码标准化完成 source={$standardizedPath}");
        return array_merge($result, ['path' => $standardizedPath]);
    }

    private function prepareStandardizeInput(string $sourcePath, string $tempDir): string
    {
        if (!$this->isRemotePath($sourcePath)) {
            return $sourcePath;
        }

        return $this->downloadRemoteSource($sourcePath, $tempDir);
    }

    private function downloadRemoteSource(string $url, string $tempDir): string
    {
        self::assertWithinDeadline();

        $extension = strtolower(pathinfo((string)parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION)) ?: 'mp4';
        $localPath = $tempDir . DIRECTORY_SEPARATOR . 'remote_source.' . $extension;

        Log::channel('video_slice')->write("[视频切片] 下载远端源文件 source={$url} local={$localPath}");

        $fp = fopen($localPath, 'w+');
        if (!$fp) {
            throw new \RuntimeException("无法创建远端源临时文件：{$localPath}");
        }

        $timeout = 600;
        if (self::$executeDeadline !== null) {
            $timeout = max(1, self::$executeDeadline - time());
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'VideoSlice/1.0',
        ]);

        $success = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        fclose($fp);

        if (self::$executeDeadline !== null && time() >= self::$executeDeadline) {
            @unlink($localPath);
            throw new VideoSliceTimeoutException("远端源文件下载超时 url={$url}");
        }

        if (!$success || $httpCode < 200 || $httpCode >= 300) {
            @unlink($localPath);
            throw new \RuntimeException("远端源文件下载失败：HTTP {$httpCode} {$error} url={$url}");
        }

        $this->assertOutputFile($localPath);
        return $localPath;
    }

    private function probeDuration(string $filePath): float
    {
        $ffprobeBin = $this->resolveFfprobeBin();
        $command = sprintf(
            '%s -v error -show_entries format=duration -of csv=p=0 %s',
            escapeshellcmd($ffprobeBin),
            escapeshellarg($filePath)
        );
        $output = $this->runCommand($command);
        $duration = (float)trim($output);

        if ($duration <= 0) {
            throw new \RuntimeException("FFprobe未能获取有效时长：{$filePath}");
        }

        return $duration;
    }

    private function probeVideoSize(string $filePath): array
    {
        $ffprobeBin = $this->resolveFfprobeBin();
        $command = sprintf(
            '%s -v error -select_streams v:0 -show_entries stream=width,height -of csv=p=0:s=x %s',
            escapeshellcmd($ffprobeBin),
            escapeshellarg($filePath)
        );
        $output = $this->runCommand($command);
        $line = trim(strtok(trim($output), "\r\n") ?: '');

        if (!preg_match('/^(\d+(?:\.\d+)?)x(\d+(?:\.\d+)?)$/', $line, $matches)) {
            throw new \RuntimeException("FFprobe未能获取有效宽高：{$filePath}");
        }

        return [
            'width' => (float)$matches[1],
            'height' => (float)$matches[2],
        ];
    }

    private function buildSlicePlans(
        string $tempDir,
        string $originalName,
        float $duration,
        float $sliceDuration,
        string $extension,
        string $width,
        string $height
    ): array {
        $items = [];
        $minSliceDuration = (float)config('video_slice.min_slice_duration', 1);
        $sliceCount = (int)floor($duration / $sliceDuration);
        if ($sliceCount <= 0) {
            $sliceCount = 1;
        }

        for ($i = 1; $i <= $sliceCount; $i++) {
            $start = ($i - 1) * $sliceDuration;
            $end = $i === $sliceCount ? $duration : min($i * $sliceDuration, $duration);
            $currentDuration = max(0, $end - $start);
            if ($currentDuration <= $minSliceDuration) {
                Log::channel('video_slice')->write(
                    sprintf(
                        '[视频切片] 子视频时长过短，跳过创建切片计划 start=%.2f duration=%.2f',
                        $start,
                        $currentDuration
                    )
                );
                continue;
            }

            $sequence = count($items) + 1;
            $outputPath = $tempDir . DIRECTORY_SEPARATOR . sprintf('slice_%03d.%s', $sequence, $extension);

            $items[] = [
                'sequence' => $sequence,
                'start' => $start,
                'end' => $end,
                'duration' => $currentDuration,
                'temp_path' => $outputPath,
                'extension' => $extension,
                'width' => $width,
                'height' => $height,
                'name' => $this->buildSliceName($originalName, $sequence),
                'should_fail' => false,
            ];
        }

        return $items;
    }

    private function createPendingSliceMaterials(array $payload, array $slicePlans, int $userId): array
    {
        foreach ($slicePlans as &$item) {
            $materialParams = [
                'original_video_id' => (int)$payload['video_id'],
                'persona_id' => $payload['persona_id'] ?? null,
                'user_id' => $userId,
                'name' => $item['name'],
                'duration' => $item['duration'],
                'width' => $item['width'],
                'height' => $item['height'],
            ];
            if (!empty($payload['thumbnail_url'])) {
                $materialParams['thumbnail_url'] = (string)$payload['thumbnail_url'];
            }

            $item['material_id'] = MaterialService::createPendingSlice($materialParams);
        }
        unset($item);

        return $slicePlans;
    }

    private function makeSlices(string $sourcePath, array $slicePlans): array
    {
        $items = [];
        $ffmpegBin = $this->resolveFfmpegBin();

        foreach ($slicePlans as $item) {
            $materialId = (int)($item['material_id'] ?? 0);
            MaterialService::markSliceProcessing($materialId);

            $start = (float)$item['start'];
            $currentDuration = (float)$item['duration'];
            if (!empty($item['should_fail'])) {
                MaterialService::markSliceFailed([$materialId]);
                Log::channel('video_slice')->write(
                    sprintf(
                        '[视频切片] 子视频时长过短，直接标记失败 material_id=%d duration=%.2f',
                        $materialId,
                        $currentDuration
                    )
                );
                continue;
            }

            $outputPath = (string)$item['temp_path'];

            $command = sprintf(
                '%s -hide_banner -loglevel error -y -i %s -ss %s -t %s -c:v libx264 -preset veryfast -crf 28 -c:a aac -b:a 128k -pix_fmt yuv420p -movflags +faststart -avoid_negative_ts make_zero %s',
                escapeshellcmd($ffmpegBin),
                escapeshellarg($sourcePath),
                escapeshellarg($this->formatSeconds($start)),
                escapeshellarg($this->formatSeconds($currentDuration)),
                escapeshellarg($outputPath)
            );

            $this->runCommand($command);
            $this->assertPlayableVideo($outputPath);

            $items[] = [
                'sequence' => (int)$item['sequence'],
                'start' => $start,
                'end' => (float)$item['end'],
                'duration' => $currentDuration,
                'temp_path' => $outputPath,
                'extension' => (string)$item['extension'],
                'width' => (string)$item['width'],
                'height' => (string)$item['height'],
                'material_id' => (int)($item['material_id'] ?? 0),
            ];
        }

        return $items;
    }

    private function resolveFfmpegBin(): string
    {
        return $this->resolveMediaBin(self::FFMPEG_COMMANDS, ['ffmpeg version', 'ffmpeg6 version'], 'ffmpeg/ffmpeg6');
    }

    private function resolveFfprobeBin(): string
    {
        return $this->resolveMediaBin(self::FFPROBE_COMMANDS, ['ffprobe version', 'ffprobe6 version'], 'ffprobe/ffprobe6');
    }

    private function resolveMediaBin(array $commands, array $versionKeywords, string $label): string
    {
        foreach ($commands as $bin) {
            $output = shell_exec(escapeshellcmd($bin) . ' -version 2>/dev/null');
            if ($output === null || trim($output) === '') {
                continue;
            }

            foreach ($versionKeywords as $keyword) {
                if (stripos($output, $keyword) !== false) {
                    return $bin;
                }
            }
        }

        throw new \RuntimeException("{$label} 未安装或不在 PATH 中");
    }

    private function moveSlicesToStorage(array $tempFiles, string $originalName, int $videoId): array
    {
        $date = date('Ymd');
        $storagePath = trim((string)config('video_slice.storage_path', 'uploads/slices/video'), '/');
        $relativeDir = $storagePath . '/' . $date . '/' . $videoId;
        $storageDefault = ConfigService::get('storage', 'default', 'local');
        $storage = $storageDefault === 'local' ? null : $this->makeStorageDriver();
        $absoluteDir = rtrim(public_path(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDir);

        if ($storageDefault === 'local' && !is_dir($absoluteDir) && !mkdir($absoluteDir, 0755, true)) {
            throw new \RuntimeException("无法创建正式切片目录：{$absoluteDir}");
        }

        $finalFiles = [];
        try {
            foreach ($tempFiles as $item) {
                $displayName = $this->buildSliceName($originalName, (int)$item['sequence']);
                $fileName = $this->buildSliceFileName($originalName, (int)$item['sequence'], (string)($item['extension'] ?? 'mp4'));
                $relativePath = $relativeDir . '/' . $fileName;
                $fileSize = filesize($item['temp_path']) ?: 0;

                if ($storageDefault === 'local') {
                    $fileName = $this->avoidNameCollision($absoluteDir, $fileName, $videoId);
                    $relativePath = $relativeDir . '/' . $fileName;
                    $absolutePath = $absoluteDir . DIRECTORY_SEPARATOR . $fileName;

                    if (!@rename($item['temp_path'], $absolutePath)) {
                        if (!@copy($item['temp_path'], $absolutePath)) {
                            throw new \RuntimeException("切片移动失败：{$item['temp_path']} => {$absolutePath}");
                        }
                        @unlink($item['temp_path']);
                    }

                    $playableInfo = $this->assertPlayableVideo($absolutePath);
                    $fileSize = filesize($absolutePath) ?: 0;
                } else {
                    $playableInfo = $this->assertPlayableVideo($item['temp_path']);
                    $storage->setUploadFileByFileName($item['temp_path'], $fileName);
                    if (!$storage->upload($relativeDir)) {
                        throw new \RuntimeException("切片上传OSS失败：{$relativePath} error=" . $storage->getError());
                    }

                    $absolutePath = '';
                    $remoteUrl = FileService::getFileUrl($relativePath, '', true);
                    Log::channel('video_slice')->write(
                        "[视频切片] OSS上传完成 path={$relativePath} remote_url={$remoteUrl} encoded="
                        . $this->encodeRemoteUrl($remoteUrl)
                    );
                    try {
                        $this->waitForRemotePlayable($remoteUrl);
                    } catch (\Throwable $e) {
                        try {
                            $storage->delete($relativePath);
                        } catch (\Throwable $cleanupError) {
                            Log::channel('video_slice')->warning(
                                "[视频切片] 清理不可播放OSS切片异常 path={$relativePath} error=" . $cleanupError->getMessage()
                            );
                        }
                        throw $e;
                    }
                    @unlink($item['temp_path']);
                }

                $finalFiles[] = [
                    'sequence' => (int)$item['sequence'],
                    'name' => $displayName,
                    'start' => (float)$item['start'],
                    'end' => (float)$item['start'] + (float)$playableInfo['duration'],
                    'duration' => (float)$playableInfo['duration'],
                    'relative_path' => $relativePath,
                    'absolute_path' => $absolutePath,
                    'remote_path' => $storageDefault === 'local' ? '' : $relativePath,
                    'file_size' => $fileSize,
                    'width' => $this->formatDimension($playableInfo['width'] ?? $item['width'] ?? ''),
                    'height' => $this->formatDimension($playableInfo['height'] ?? $item['height'] ?? ''),
                    'material_id' => (int)($item['material_id'] ?? 0),
                ];
            }
        } catch (\Throwable $e) {
            $this->cleanupFinalFiles($finalFiles);
            throw $e;
        }

        return $finalFiles;
    }

    private function makeStorageDriver(): StorageDriver
    {
        return new StorageDriver([
            'default' => ConfigService::get('storage', 'default', 'local'),
            'engine' => ConfigService::get('storage') ?? ['local' => []],
        ]);
    }

    private function runCommand(string $command): string
    {
        self::assertWithinDeadline();

        $descriptorSpec = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptorSpec, $pipes);
        if (!is_resource($process)) {
            throw new \RuntimeException("命令启动失败：{$command}");
        }

        foreach ([1, 2] as $idx) {
            stream_set_blocking($pipes[$idx], false);
        }

        $stdout = '';
        $stderr = '';
        $timedOut = false;
        // proc_get_status 在进程结束后只有第一次能拿到真实 exitcode，之后为 -1；
        // 若这里丢弃，后面 proc_close 也会得到 -1，导致 ffmpeg 成功仍被判失败。
        $exitCode = null;

        while (true) {
            $status = proc_get_status($process);
            if (!$status['running']) {
                $exitCode = (int)$status['exitcode'];
                break;
            }

            if (self::$executeDeadline !== null && time() >= self::$executeDeadline) {
                $timedOut = true;
                $this->terminateProcess($process);
                $status = proc_get_status($process);
                if (!$status['running']) {
                    $exitCode = (int)$status['exitcode'];
                }
                break;
            }

            $read = [$pipes[1], $pipes[2]];
            $write = null;
            $except = null;
            if (@stream_select($read, $write, $except, 1) > 0) {
                foreach ($read as $stream) {
                    $chunk = stream_get_contents($stream);
                    if ($chunk === false || $chunk === '') {
                        continue;
                    }
                    if ($stream === $pipes[1]) {
                        $stdout .= $chunk;
                    } else {
                        $stderr .= $chunk;
                    }
                }
            }
        }

        $stdout .= stream_get_contents($pipes[1]) ?: '';
        $stderr .= stream_get_contents($pipes[2]) ?: '';
        fclose($pipes[1]);
        fclose($pipes[2]);

        $closeCode = proc_close($process);
        if ($exitCode === null) {
            $exitCode = (int)$closeCode;
        }

        if ($timedOut) {
            throw new VideoSliceTimeoutException(
                '切片命令执行超时，已终止子进程 command=' . $command
            );
        }
        if ($exitCode !== 0) {
            throw new \RuntimeException("命令执行失败({$exitCode})：{$stderr}");
        }

        return trim($stdout);
    }

    private function terminateProcess($process): void
    {
        if (!is_resource($process)) {
            return;
        }

        $status = proc_get_status($process);
        $pid = (int)($status['pid'] ?? 0);
        @proc_terminate($process, SIGTERM);

        $waitUntil = time() + 3;
        while (time() < $waitUntil) {
            $status = proc_get_status($process);
            if (!$status['running']) {
                return;
            }
            usleep(100000);
        }

        @proc_terminate($process, SIGKILL);
        if ($pid > 0 && function_exists('posix_kill')) {
            @posix_kill($pid, SIGKILL);
        }
    }

    private function prepareTempDir(int $videoId): string
    {
        $basePath = rtrim((string)config('video_slice.temp_path'), DIRECTORY_SEPARATOR);
        $tempDir = $basePath . DIRECTORY_SEPARATOR . $videoId;
        $this->cleanupTempDir($tempDir);

        if (!is_dir($tempDir) && !mkdir($tempDir, 0755, true)) {
            throw new \RuntimeException("无法创建临时切片目录：{$tempDir}");
        }

        return $tempDir;
    }

    private function cleanupTempDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = glob($dir . DIRECTORY_SEPARATOR . '*') ?: [];
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        @rmdir($dir);
    }

    private function cleanupFinalFiles(array $finalFiles): void
    {
        $storage = null;
        foreach ($finalFiles as $file) {
            if (!empty($file['absolute_path']) && is_file($file['absolute_path'])) {
                @unlink($file['absolute_path']);
            }
            if (!empty($file['remote_path'])) {
                try {
                    $storage = $storage ?: $this->makeStorageDriver();
                    if (!$storage->delete($file['remote_path'])) {
                        Log::channel('video_slice')->warning("[视频切片] 清理OSS切片失败 path={$file['remote_path']}");
                    }
                } catch (\Throwable $e) {
                    Log::channel('video_slice')->warning(
                        "[视频切片] 清理OSS切片异常 path={$file['remote_path']} error=" . $e->getMessage()
                    );
                }
            }
        }
    }

    private function deleteOriginal(string $originalPath): void
    {
        $storageDefault = ConfigService::get('storage', 'default', 'local');
        $absolutePath = $this->resolveLocalPath($originalPath);
        if ($absolutePath && is_file($absolutePath)) {
            if (!@unlink($absolutePath)) {
                Log::channel('video_slice')->warning("[视频切片] 原视频本地删除失败 path={$absolutePath}");
            }
        }

        if ($storageDefault !== 'local' || !$absolutePath) {
            $storagePath = $this->normalizeStoragePath($originalPath);
            try {
                $config = [
                    'default' => $storageDefault,
                    'engine' => ConfigService::get('storage') ?? ['local' => []],
                ];
                $storage = new StorageDriver($config);
                if (!$storage->delete($storagePath)) {
                    Log::channel('video_slice')->warning("[视频切片] 原视频删除失败 path={$storagePath}");
                }
            } catch (\Throwable $e) {
                Log::channel('video_slice')->warning("[视频切片] 原视频删除异常 path={$originalPath} error=" . $e->getMessage());
            }
        }
    }

    private function assertOutputFile(string $path): void
    {
        $minSize = (int)config('video_slice.min_output_size', 100);
        if (!is_file($path)) {
            throw new \RuntimeException("切片文件未生成：{$path}");
        }
        if ((filesize($path) ?: 0) < $minSize) {
            throw new \RuntimeException("切片文件异常，大小不足：{$path}");
        }
    }

    private function assertPlayableVideo(string $path): array
    {
        $this->assertOutputFile($path);

        $duration = $this->probeDuration($path);
        $videoSize = $this->probeVideoSize($path);
        if ($duration <= 0 || (float)$videoSize['width'] <= 0 || (float)$videoSize['height'] <= 0) {
            throw new \RuntimeException("切片文件不可播放：{$path}");
        }

        return [
            'duration' => $duration,
            'width' => (float)$videoSize['width'],
            'height' => (float)$videoSize['height'],
        ];
    }

    private function waitForRemotePlayable(string $url, int $maxAttempts = 10, int $sleepSeconds = 2): void
    {
        if (!$this->isRemotePath($url)) {
            return;
        }

        $probeUrl = $this->encodeRemoteUrl($url);
        $lastError = '';
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            self::assertWithinDeadline();

            $httpCode = $this->probeRemoteHttpCode($probeUrl);
            if ($httpCode > 0 && ($httpCode < 200 || $httpCode >= 300)) {
                $lastError = "HTTP {$httpCode}";
                Log::channel('video_slice')->write(
                    "[视频切片] 远端切片暂不可访问 attempt={$attempt}/{$maxAttempts} http={$httpCode} url={$probeUrl}"
                );
                if ($attempt < $maxAttempts) {
                    sleep($sleepSeconds);
                }
                continue;
            }

            try {
                $duration = $this->probeDuration($probeUrl);
                $videoSize = $this->probeVideoSize($probeUrl);
                if ($duration > 0 && (float)$videoSize['width'] > 0 && (float)$videoSize['height'] > 0) {
                    return;
                }
                $lastError = "duration={$duration} size=" . json_encode($videoSize, JSON_UNESCAPED_UNICODE);
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
                Log::channel('video_slice')->write(
                    "[视频切片] 远端切片探测失败 attempt={$attempt}/{$maxAttempts} url={$probeUrl} error={$lastError}"
                );
            }

            if ($attempt < $maxAttempts) {
                sleep($sleepSeconds);
            }
        }

        throw new \RuntimeException("远端切片文件暂不可播放：{$probeUrl} {$lastError}");
    }

    private function encodeRemoteUrl(string $url): string
    {
        $parts = parse_url($url);
        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return $url;
        }

        $path = (string)($parts['path'] ?? '');
        $encodedPath = implode('/', array_map(
            static fn(string $segment) => $segment === '' ? '' : rawurlencode(rawurldecode($segment)),
            explode('/', $path)
        ));

        $encoded = $parts['scheme'] . '://' . $parts['host'];
        if (!empty($parts['port'])) {
            $encoded .= ':' . $parts['port'];
        }
        $encoded .= $encodedPath;
        if (!empty($parts['query'])) {
            $encoded .= '?' . $parts['query'];
        }
        if (!empty($parts['fragment'])) {
            $encoded .= '#' . $parts['fragment'];
        }

        return $encoded;
    }

    private function probeRemoteHttpCode(string $url): int
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return 0;
        }

        curl_setopt_array($ch, [
            CURLOPT_NOBODY => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'VideoSlice/1.0',
        ]);
        curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode;
    }

    private function resolveSourcePathForPayload(string $path, string $originalPath): string
    {
        if ($this->isRemotePath($path)) {
            $storageUrl = $this->buildStorageSourceUrl($originalPath);
            if ($storageUrl !== '' && $storageUrl !== $path) {
                Log::channel('video_slice')->write(
                    "[视频切片] OSS模式纠正远端源地址 source={$path} storage_source={$storageUrl}"
                );
                return $storageUrl;
            }

            return $path;
        }

        try {
            return $this->resolveSourcePath($path);
        } catch (\Throwable $e) {
            $storageUrl = $this->buildStorageSourceUrl($originalPath);
            if ($storageUrl !== '') {
                Log::channel('video_slice')->warning(
                    "[视频切片] 本地源不可用，改用OSS源 source={$path} storage_source={$storageUrl}"
                );
                return $storageUrl;
            }

            throw $e;
        }
    }

    private function prepareProbeSourceForPayload(array $payload, string &$tempDir = ''): string
    {
        $originalPath = (string)($payload['file_path'] ?? '');
        $sourcePath = $this->resolveSourcePathForPayload(
            (string)($payload['source_path'] ?? $originalPath),
            $originalPath
        );

        if (!$this->isRemotePath($sourcePath)) {
            return $sourcePath;
        }

        $tempDir = $this->prepareProbeTempDir((int)($payload['video_id'] ?? 0));
        return $this->downloadRemoteSource($sourcePath, $tempDir);
    }

    private function prepareProbeTempDir(int $videoId): string
    {
        $basePath = rtrim((string)config('video_slice.temp_path'), DIRECTORY_SEPARATOR);
        $tempDir = $basePath . DIRECTORY_SEPARATOR . uniqid('probe_' . max(0, $videoId) . '_', true);

        if (!is_dir($tempDir) && !mkdir($tempDir, 0755, true)) {
            throw new \RuntimeException("无法创建临时探测目录：{$tempDir}");
        }

        return $tempDir;
    }

    private function buildStorageSourceUrl(string $originalPath): string
    {
        if ($originalPath === '' || $this->isRemotePath($originalPath)) {
            return '';
        }

        if (ConfigService::get('storage', 'default', 'local') === 'local') {
            return '';
        }

        $url = FileService::getFileUrl($originalPath, '', true);
        return $this->isRemotePath($url) ? $url : '';
    }

    private function resolveSourcePath(string $path): string
    {
        if ($this->isRemotePath($path)) {
            return $path;
        }

        $localPath = $this->resolveLocalPath($path);
        if (!$localPath) {
            throw new \RuntimeException("视频文件不存在：{$path}");
        }

        return $localPath;
    }

    private function isRemotePath(string $path): bool
    {
        return preg_match('/^https?:\/\//i', $path) === 1;
    }

    private function resolveLocalPath(string $path): ?string
    {
        if ($path === '') {
            return null;
        }

        if (is_file($path)) {
            return $path;
        }

        $relativePath = $this->normalizeStoragePath($path);
        $candidates = [
            public_path() . ltrim($relativePath, '/'),
            root_path() . ltrim($relativePath, '/'),
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function normalizeStoragePath(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        $publicPath = str_replace('\\', '/', public_path());
        $rootPath = str_replace('\\', '/', root_path());

        if ($publicPath !== '' && str_starts_with($path, $publicPath)) {
            return ltrim(substr($path, strlen($publicPath)), '/');
        }

        if ($rootPath !== '' && str_starts_with($path, $rootPath)) {
            return ltrim(substr($path, strlen($rootPath)), '/');
        }

        $path = FileService::setFileUrl($path);
        $path = str_replace('\\', '/', $path);
        return ltrim($path, '/');
    }

    private function getFileSize(string $path): int
    {
        $localPath = $this->resolveLocalPath($path);
        return $localPath && is_file($localPath) ? (int)filesize($localPath) : 0;
    }

    private function buildOriginalMaterialName(string $originalName): string
    {
        return pathinfo($originalName, PATHINFO_FILENAME) ?: $originalName;
    }

    private function buildSliceName(string $originalName, int $sequence): string
    {
        $name = pathinfo($originalName, PATHINFO_FILENAME) ?: $originalName;
        return "{$name}_切片{$sequence}";
    }

    private function buildSliceFileName(string $originalName, int $sequence, ?string $extension = null): string
    {
        // 存储对象名仅用 ASCII，避免 OSS/CDN/ffprobe 对中文路径处理不一致导致 404
        $base = $this->sanitizeFileName(pathinfo($originalName, PATHINFO_FILENAME) ?: 'video');
        $base = preg_replace('/[^A-Za-z0-9._-]+/', '_', $base) ?: 'video_slice';
        $base = trim($base, '._-') ?: 'video_slice';

        return sprintf(
            '%s_slice%d.%s',
            $base,
            $sequence,
            $extension ?: $this->getExtension($originalName)
        );
    }

    private function sanitizeFileName(string $name): string
    {
        $name = preg_replace('/[\/\\\\:*?"<>|]+/u', '_', $name) ?: 'video_slice';
        return trim($name, '. ');
    }

    private function avoidNameCollision(string $dir, string $fileName, int $videoId): string
    {
        $target = $dir . DIRECTORY_SEPARATOR . $fileName;
        if (!is_file($target)) {
            return $fileName;
        }

        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $name = pathinfo($fileName, PATHINFO_FILENAME);
        return sprintf('%s_%d.%s', $name, $videoId, $extension);
    }

    private function getExtension(string $originalName): string
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        return $extension !== '' ? $extension : 'mp4';
    }

    private function formatSeconds(float $seconds): string
    {
        return number_format($seconds, 2, '.', '');
    }

    private function formatDimension(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (!is_numeric($value)) {
            return (string)$value;
        }

        $dimension = (float)$value;
        if ($dimension <= 0) {
            return '';
        }

        return rtrim(rtrim(number_format($dimension, 2, '.', ''), '0'), '.');
    }

    private function resolveUserId(array $payload): int
    {
        $userId = (int)($payload['user_id'] ?? 0);
        if ($userId > 0) {
            return $userId;
        }

        $videoId = (int)($payload['video_id'] ?? 0);
        if ($videoId <= 0) {
            return 0;
        }

        try {
            return (int)Db::name('file')->where('id', $videoId)->value('source_id');
        } catch (\Throwable $e) {
            Log::channel('video_slice')->warning(
                "[视频切片] user_id兜底查询失败 video_id={$videoId} error=" . $e->getMessage()
            );
            return 0;
        }
    }

    private function markSliceMaterialsFailedByPayload(array $payload): void
    {
        MaterialService::markSliceFailedByOriginalVideoId(
            (int)($payload['video_id'] ?? 0),
            $this->resolveUserId($payload),
            (int)($payload['persona_id'] ?? 0)
        );
    }
}

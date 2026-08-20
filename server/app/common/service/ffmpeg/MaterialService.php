<?php

namespace app\common\service\ffmpeg;

use app\common\model\aiPersona\Material;
use app\common\model\file\File;
use app\common\service\ConfigService;
use app\common\service\FileService;
use think\facade\Log;

class MaterialService
{
    public static function addVideo(array $params): int
    {
        $defaultSliceStatus = (string)($params['source_type'] ?? 'slice') === 'slice'
            ? Material::SLICE_STATUS_SUCCESS
            : Material::SLICE_STATUS_NONE;

        $material = self::createVideo($params + [
            'use_status' => Material::USE_STATUS_ENABLED,
            'slice_status' => $defaultSliceStatus,
        ]);

        return (int)$material->id;
    }

    /**
     * 将上传的原始完整视频入库为可用素材（ffmpeg=0 上传成功、ffmpeg=1 转码成功共用）。
     * 以 source_video_id + persona 幂等，避免重复入库。
     * 低于 5 秒或不切割短视频：缺封面时自动截帧补上。
     *
     * @return int 素材ID，0 表示未入库
     */
    public static function publishUploadedVideo(int $fileId, int $personaId, int $userId, array $meta = []): int
    {
        if ($fileId <= 0 || $personaId <= 0) {
            return 0;
        }

        $file = File::where('id', $fileId)->findOrEmpty();
        if ($file->isEmpty()) {
            return 0;
        }
        if ($userId <= 0) {
            $userId = (int)$file->source_id;
        }

        $fileUrl = (string)($meta['file_url'] ?? $file->uri);
        if ($fileUrl === '') {
            return 0;
        }

        $duration = round((float)($meta['duration'] ?? $file->duration ?? 0), 2);
        $thumbnailUrl = trim((string)($meta['thumbnail_url'] ?? ''));
        // 无需切割（≤5秒、6~7秒等）/ 显式要求：入库时必须尽量补封面
        $needThumb = !empty($meta['ensure_thumbnail'])
            || ($duration > 0 && MaterialSliceBatchService::calcSliceCount($duration) <= 0);
        if ($thumbnailUrl === '' && $needThumb) {
            // 极短片取更靠前的帧，避免 seek 失败
            $seek = $duration > 0 ? min(0.3, max(0.01, $duration * 0.15)) : 0.1;
            $thumbnailUrl = self::makeVideoThumbnail($fileUrl, $seek);
        }

        $existing = Material::where('persona_id', $personaId)
            ->where('material_type', Material::MATERIAL_TYPE_VIDEO)
            ->where('source_type', 'original')
            ->where('source_video_id', $fileId)
            ->where('delete_time', null)
            ->when($userId > 0, fn ($query) => $query->where('user_id', $userId))
            ->findOrEmpty();
        if (!$existing->isEmpty()) {
            $rawThumb = trim((string)$existing->getData('thumbnail_url'));
            // 已入库但缺封面：强制再截一帧补上
            if ($rawThumb === '' && $needThumb) {
                if ($thumbnailUrl === '') {
                    $seek = $duration > 0 ? min(0.3, max(0.01, $duration * 0.15)) : 0.1;
                    $thumbnailUrl = self::makeVideoThumbnail($fileUrl, $seek);
                }
                if ($thumbnailUrl !== '') {
                    $existing->save([
                        'thumbnail_url' => $thumbnailUrl,
                        'update_time' => time(),
                    ]);
                }
            }
            return (int)$existing->id;
        }

        $material = self::createVideo([
            'persona_id' => $personaId,
            'user_id' => max(0, $userId),
            'name' => (string)($meta['name'] ?? pathinfo((string)$file->name, PATHINFO_FILENAME)),
            'file_url' => $fileUrl,
            'thumbnail_url' => $thumbnailUrl,
            'duration' => $duration,
            'width' => $meta['width'] ?? '',
            'height' => $meta['height'] ?? '',
            'use_status' => Material::USE_STATUS_ENABLED,
            'source_type' => 'original',
            'source_video_id' => $fileId,
            'slice_status' => Material::SLICE_STATUS_NONE,
        ]);

        return (int)$material->id;
    }

    /**
     * 从视频截取封面，返回相对/可存库路径；失败返回空字符串。
     * 兼容本地相对路径与 OSS：本地不存在时转成可下载的完整 URL 再截帧。
     */
    public static function makeVideoThumbnail(string $videoUri, float $time = 0.5): string
    {
        $videoUri = trim($videoUri);
        if ($videoUri === '') {
            return '';
        }
        try {
            $source = self::resolveVideoSourceForThumb($videoUri);
            // 极短视频：避免 seek 超过片长（即使 adjustTimePoint 会兜底，也先收敛）
            $time = max(0.0, min(0.5, $time));
            $result = (new \app\common\service\VideoInfoService())->generateThumbnail($source, $time, [
                'width' => 480,
                'format' => 'jpg',
                'force' => true,
            ]);
            $url = (string)($result['url'] ?? '');
            if ($url === '') {
                self::safeFfmpegLog('[素材封面] 截帧返回空 url uri=' . $videoUri . ' source=' . $source);
                return '';
            }
            return FileService::setFileUrl($url);
        } catch (\Throwable $e) {
            self::safeFfmpegLog('[素材封面] 截帧失败 uri=' . $videoUri . ' err=' . $e->getMessage());
            return '';
        }
    }

    private static function safeFfmpegLog(string $message): void
    {
        try {
            Log::channel('ffmpeg')->write($message);
        } catch (\Throwable $e) {
            // 日志目录权限异常时不能反向打断入库主流程
        }
    }

    /**
     * 解析截帧输入：本地文件优先，否则强制拼完整可访问 URL（OSS/CDN）。
     */
    private static function resolveVideoSourceForThumb(string $uri): string
    {
        if (preg_match('/^https?:\/\//i', $uri)) {
            return $uri;
        }
        if (str_starts_with($uri, '/') && is_file($uri)) {
            return $uri;
        }

        $relative = ltrim($uri, '/\\');
        $local = rtrim(public_path(), '/\\') . DIRECTORY_SEPARATOR . $relative;
        clearstatcache(true, $local);
        if (is_file($local)) {
            return $local;
        }

        // 本地不存在时必须拿到 http(s) 地址：OSS-MPS 产物只在云端
        $full = FileService::getFileUrl($relative, '', true);
        if (!preg_match('/^https?:\/\//i', $full)) {
            $full = FileService::getFileUrl($relative, '', false);
        }
        if (!preg_match('/^https?:\/\//i', $full)) {
            throw new \RuntimeException('视频文件不存在且无法解析为可访问URL: ' . $uri . ' => ' . $full);
        }
        return $full;
    }

    public static function createPendingSlice(array $params): int
    {
        $originalVideoId = (int)($params['original_video_id'] ?? 0);
        $materialName = (string)($params['name'] ?? $params['material_name'] ?? '');
        if ($originalVideoId > 0 && $materialName !== '') {
            $material = Material::where('source_video_id', $originalVideoId)
                ->where('source_type', 'slice')
                ->where('material_name', $materialName)
                ->where('delete_time', null)
                ->findOrEmpty();

            if (!$material->isEmpty()) {
                $saveData = [
                    'file_url' => '',
                    'duration' => (int)round((float)($params['duration'] ?? 0)),
                    'width' => self::formatDimension($params['width'] ?? ''),
                    'height' => self::formatDimension($params['height'] ?? ''),
                    'use_status' => Material::USE_STATUS_DISABLED,
                    'slice_status' => Material::SLICE_STATUS_PENDING,
                    'update_time' => time(),
                ];
                if (!empty($params['thumbnail_url'])) {
                    $saveData['thumbnail_url'] = (string)$params['thumbnail_url'];
                }

                $material->save($saveData);

                return (int)$material->id;
            }
        }

        $material = self::createVideo($params + [
            'file_path' => '',
            'use_status' => Material::USE_STATUS_DISABLED,
            'source_type' => 'slice',
            'slice_status' => Material::SLICE_STATUS_PENDING,
        ]);

        return (int)$material->id;
    }

    public static function markSliceProcessing(int $materialId): void
    {
        if ($materialId <= 0) {
            return;
        }

        Material::where('id', $materialId)->update([
            'use_status' => Material::USE_STATUS_DISABLED,
            'slice_status' => Material::SLICE_STATUS_PROCESSING,
            'update_time' => time(),
        ]);
    }

    public static function markSliceSuccess(int $materialId, array $params): void
    {
        if ($materialId <= 0) {
            return;
        }

        $saveData = [
            'file_url' => (string)($params['file_path'] ?? $params['file_url'] ?? ''),
            'duration' => (int)round((float)($params['duration'] ?? 0)),
            'width' => self::formatDimension($params['width'] ?? ''),
            'height' => self::formatDimension($params['height'] ?? ''),
            'use_status' => Material::USE_STATUS_ENABLED,
            'slice_status' => Material::SLICE_STATUS_SUCCESS,
            'update_time' => time(),
        ];
        if (!empty($params['thumbnail_url'])) {
            $saveData['thumbnail_url'] = (string)$params['thumbnail_url'];
        }

        Material::where('id', $materialId)->update($saveData);
    }

    public static function markSliceFailed(array $materialIds): void
    {
        $materialIds = array_values(array_filter(array_map('intval', $materialIds)));
        if (empty($materialIds)) {
            return;
        }

        Material::whereIn('id', $materialIds)->update([
            'file_url' => '',
            'use_status' => Material::USE_STATUS_DISABLED,
            'slice_status' => Material::SLICE_STATUS_FAILED,
            'update_time' => time(),
        ]);
    }

    public static function markSliceFailedByOriginalVideoId(int $originalVideoId, int $userId = 0, int $personaId = 0): int
    {
        if ($originalVideoId <= 0) {
            return 0;
        }

        $query = Material::where('source_video_id', $originalVideoId)
            ->where('source_type', 'slice')
            ->where('delete_time', null)
            ->whereIn('slice_status', [
                Material::SLICE_STATUS_PENDING,
                Material::SLICE_STATUS_PROCESSING,
            ]);

        if ($userId > 0) {
            $query->where('user_id', $userId);
        }
        if ($personaId > 0) {
            $query->where('persona_id', $personaId);
        }

        $count = (int)$query->update([
            'file_url' => '',
            'use_status' => Material::USE_STATUS_DISABLED,
            'slice_status' => Material::SLICE_STATUS_FAILED,
            'update_time' => time(),
        ]);

        if ($count > 0) {
            Log::channel('video_slice')->write(
                "[素材切片] 占位素材已标记失败 original_video_id={$originalVideoId} count={$count}"
            );
        }

        return $count;
    }

    public static function deleteObsoleteSlicePlaceholders(
        int $originalVideoId,
        array $keepMaterialIds,
        int $userId = 0,
        int $personaId = 0
    ): int {
        if ($originalVideoId <= 0) {
            return 0;
        }

        $keepMaterialIds = array_values(array_unique(array_filter(array_map('intval', $keepMaterialIds))));

        $query = Material::where('source_video_id', $originalVideoId)
            ->where('source_type', 'slice')
            ->where('delete_time', null)
            ->whereIn('slice_status', [
                Material::SLICE_STATUS_PENDING,
                Material::SLICE_STATUS_PROCESSING,
            ]);

        if (!empty($keepMaterialIds)) {
            $query->whereNotIn('id', $keepMaterialIds);
        }
        if ($userId > 0) {
            $query->where('user_id', $userId);
        }
        if ($personaId > 0) {
            $query->where('persona_id', $personaId);
        }

        $count = (int)$query->update([
            'file_url' => '',
            'use_status' => Material::USE_STATUS_DELETED,
            'slice_status' => Material::SLICE_STATUS_FAILED,
            'delete_time' => time(),
            'update_time' => time(),
        ]);

        if ($count > 0) {
            Log::channel('video_slice')->write(
                "[素材切片] 多余占位素材已删除 original_video_id={$originalVideoId} count={$count}"
            );
        }

        return $count;
    }

    public static function markStaleProcessingSlicesFailed(int $timeoutSeconds): int
    {
        $timeoutSeconds = max(60, $timeoutSeconds);
        $expiredBefore = time() - $timeoutSeconds;

        $count = (int)Material::where('source_type', 'slice')
            ->where('delete_time', null)
            ->where('slice_status', Material::SLICE_STATUS_PROCESSING)
            ->where('update_time', '<', $expiredBefore)
            ->update([
                'file_url' => '',
                'use_status' => Material::USE_STATUS_DISABLED,
                'slice_status' => Material::SLICE_STATUS_FAILED,
                'update_time' => time(),
            ]);

        if ($count > 0) {
            Log::channel('video_slice')->write(
                "[素材切片] 超时分割中素材已标记失败 count={$count} timeout={$timeoutSeconds}s"
            );
        }

        return $count;
    }

    public static function deleteOriginalUploadMaterial(array $params): int
    {
        $originalVideoId = (int)($params['original_video_id'] ?? $params['video_id'] ?? 0);
        $personaId = (int)($params['persona_id'] ?? 0);
        $userId = (int)($params['user_id'] ?? 0);
        $fileUrl = (string)($params['file_path'] ?? $params['original_path'] ?? $params['file_url'] ?? '');
        $fileUrls = self::buildFileUrlCandidates($fileUrl);

        if ($originalVideoId > 0) {
            $file = File::where('id', $originalVideoId)->field('uri,source_id')->findOrEmpty();
            if (!$file->isEmpty()) {
                $fileUrls = array_merge($fileUrls, self::buildFileUrlCandidates((string)$file->uri));
                if ($userId <= 0) {
                    $userId = (int)$file->source_id;
                }
            }
        }

        $fileUrls = array_values(array_unique(array_filter($fileUrls)));
        if ($personaId <= 0 || empty($fileUrls)) {
            return 0;
        }

        $query = Material::where('persona_id', $personaId)
            ->where('material_type', Material::MATERIAL_TYPE_VIDEO)
            ->where(function ($query) {
                $query->where('source_type', '<>', 'slice')->whereOr('source_type', null);
            })
            ->where('delete_time', null)
            ->whereIn('file_url', $fileUrls);

        if ($userId > 0) {
            $query->where('user_id', $userId);
        }

        $count = (int)$query->update([
            'use_status' => Material::USE_STATUS_DELETED,
            'delete_time' => time(),
            'update_time' => time(),
        ]);

        if ($count > 0) {
            Log::channel('video_slice')->write(
                "[素材删除] 原视频素材已软删除 original_video_id={$originalVideoId} count={$count}"
            );
        }

        return $count;
    }

    public static function isOriginalMaterialBeingSliced(array $params): bool
    {
        $personaId = (int)($params['persona_id'] ?? 0);
        $userId = (int)($params['user_id'] ?? 0);
        $materialType = (int)($params['material_type'] ?? Material::MATERIAL_TYPE_VIDEO);
        $sourceType = (string)($params['source_type'] ?? '');
        $fileUrl = (string)($params['file_url'] ?? '');

        if ($personaId <= 0 || $materialType !== Material::MATERIAL_TYPE_VIDEO || $sourceType === 'slice' || $fileUrl === '') {
            return false;
        }

        $fileUrls = self::buildFileUrlCandidates($fileUrl);
        $relativeUrls = array_values(array_filter($fileUrls, static fn ($url) => !preg_match('/^https?:\/\//i', $url)));
        if (empty($relativeUrls)) {
            return false;
        }

        $videoIds = File::whereIn('uri', $relativeUrls)->column('id');
        $videoIds = array_values(array_filter(array_map('intval', $videoIds)));
        if (empty($videoIds)) {
            return false;
        }

        $query = Material::where('persona_id', $personaId)
            ->where('material_type', Material::MATERIAL_TYPE_VIDEO)
            ->where('source_type', 'slice')
            ->where('delete_time', null)
            ->whereIn('source_video_id', $videoIds);

        if ($userId > 0) {
            $query->where('user_id', $userId);
        }

        return !$query->findOrEmpty()->isEmpty();
    }

    private static function createVideo(array $params): Material
    {
        $originalVideoId = (int)($params['original_video_id'] ?? 0);
        $userId = (int)($params['user_id'] ?? 0);
        if ($userId <= 0 && $originalVideoId > 0) {
            $userId = (int)File::where('id', $originalVideoId)->value('source_id');
        }

        $personaId = (int)($params['persona_id'] ?? $params['character_ip_id'] ?? 0);
        if ($personaId <= 0) {
            Log::channel('video_slice')->warning("[素材入库] persona_id为空，使用0兜底 original_video_id={$originalVideoId}");
        }

        return Material::create([
            'persona_id' => $personaId,
            'user_id' => max(0, $userId),
            'material_name' => (string)($params['name'] ?? $params['material_name'] ?? ''),
            'material_type' => Material::MATERIAL_TYPE_VIDEO,
            'file_url' => (string)($params['file_path'] ?? $params['file_url'] ?? ''),
            'thumbnail_url' => (string)($params['thumbnail_url'] ?? ''),
            'duration' => (int)round((float)($params['duration'] ?? 0)),
            'width' => self::formatDimension($params['width'] ?? ''),
            'height' => self::formatDimension($params['height'] ?? ''),
            'use_status' => (int)($params['use_status'] ?? Material::USE_STATUS_ENABLED),
            'publish_mode' => Material::PUBLISH_MODE_MAKE_VIDEO,
            'source_type' => (string)($params['source_type'] ?? 'slice'),
            'source_video_id' => $originalVideoId,
            'slice_status' => (int)($params['slice_status'] ?? Material::SLICE_STATUS_NONE),
            'create_time' => time(),
            'update_time' => time(),
        ]);
    }

    private static function formatDimension(mixed $value): string
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

    private static function buildFileUrlCandidates(string $fileUrl): array
    {
        $fileUrl = trim(str_replace('\\', '/', $fileUrl));
        if ($fileUrl === '') {
            return [];
        }

        $candidates = [$fileUrl];
        $path = $fileUrl;
        if (preg_match('/^https?:\/\//i', $fileUrl) === 1) {
            $urlPath = (string)parse_url($fileUrl, PHP_URL_PATH);
            if ($urlPath !== '') {
                $path = ltrim($urlPath, '/');
            }
        }

        $relative = FileService::setFileUrl($fileUrl);
        foreach ([$path, $relative] as $item) {
            $item = trim(str_replace('\\', '/', (string)$item));
            if ($item === '') {
                continue;
            }
            $candidates[] = ltrim($item, '/');
            $candidates[] = '/' . ltrim($item, '/');
        }

        $relativePath = ltrim($path, '/');
        if ($relativePath !== '' && preg_match('/^https?:\/\//i', $relativePath) !== 1) {
            $candidates[] = FileService::getFileUrl($relativePath);
            $candidates[] = FileService::getFileUrl($relativePath, '', true);
        }

        return array_values(array_unique(array_filter($candidates)));
    }
}

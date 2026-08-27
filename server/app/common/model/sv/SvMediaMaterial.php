<?php

namespace app\common\model\sv;

use app\common\enum\DeviceEnum;
use app\common\model\BaseModel;
use app\common\model\file\File;
use app\common\service\FileService;
use app\common\service\ffmpeg\MaterialService;
use think\facade\Log;
use think\model\concern\SoftDelete;

/**
 * 素材模型
 * Class SvMediaMaterial
 * @package app\common\model\sv
 */
class SvMediaMaterial extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    // 素材类型常量
    const TYPE_IMAGE = 1;     // 图片
    const TYPE_VIDEO = 2;     // 视频

    const SOURCE_HOTSPOT = 'hotspot';
    const SOURCE_VIDEO_IMITATION = 'video_imitation';
    /** 图生图改写入库来源（需求原文拼写） */
    const SOURCE_IMAGE_REWRITE = 'image_rewite';

    public const IMAGE_REWRITE_SCENE_MANUAL = 'manual';
    public const IMAGE_REWRITE_SCENE_AUTO = 'auto';

    public const CONTENT_MAX_LEN = 255;
    public const REMOTE_URL_MAX_LEN = 1024;

    const PLATFORM_UNKNOWN = 0;
    const PLATFORM_WECHAT = 1;
    const PLATFORM_XHS = 3;
    const PLATFORM_DOUYIN = 4;
    const PLATFORM_KUAISHOU = 5;

    /**
     * 测试钩子：仅 tests/ 注入，生产勿用。
     * @var array<string, mixed>
     */
    private static array $testHooks = [];

    public static function setTestHooks(array $hooks): void
    {
        self::$testHooks = $hooks;
    }

    public static function clearTestHooks(): void
    {
        self::$testHooks = [];
    }

    public static function testHookState(): array
    {
        return self::$testHooks;
    }

    public static function mapHotspotPlatform(string $platform): int
    {
        return match ($platform) {
            'xiaohongshu' => self::PLATFORM_XHS,
            'douyin' => self::PLATFORM_DOUYIN,
            'kuaishou' => self::PLATFORM_KUAISHOU,
            'wechat', 'sph', 'weixin' => self::PLATFORM_WECHAT,
            default => self::PLATFORM_UNKNOWN,
        };
    }

    public static function mapDevicePlatform(int $platformType): int
    {
        return match ($platformType) {
            DeviceEnum::ACCOUNT_TYPE_SPH => self::PLATFORM_WECHAT,
            DeviceEnum::ACCOUNT_TYPE_XHS => self::PLATFORM_XHS,
            DeviceEnum::ACCOUNT_TYPE_DY => self::PLATFORM_DOUYIN,
            DeviceEnum::ACCOUNT_TYPE_KS => self::PLATFORM_KUAISHOU,
            default => self::PLATFORM_UNKNOWN,
        };
    }

    public static function normalizeMaterialContent(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        return trim((string)FileService::setFileUrl($url));
    }

    public static function normalizeRemoteUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (strlen($url) <= self::REMOTE_URL_MAX_LEN) {
            return $url;
        }
        try {
            Log::channel('shanjian')->write('素材源链超长已截断：' . mb_substr($url, 0, 180));
        } catch (\Throwable $e) {
        }
        return substr($url, 0, self::REMOTE_URL_MAX_LEN);
    }

    public static function stripRemoteUrlQuery(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['host'])) {
            return $url;
        }
        $scheme = ($parts['scheme'] ?? 'https') . '://';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        return $scheme . $parts['host'] . $port . ($parts['path'] ?? '');
    }

    /**
     * @return array<int, string>
     */
    public static function remoteUrlLookupValues(string $remoteUrl): array
    {
        $raw = trim($remoteUrl);
        if ($raw === '') {
            return [];
        }
        $values = [];
        $stripped = self::stripRemoteUrlQuery($raw);
        foreach ([$raw, self::normalizeRemoteUrl($raw), $stripped, self::normalizeRemoteUrl($stripped)] as $value) {
            $value = trim((string)$value);
            if ($value !== '') {
                $values[$value] = true;
            }
        }
        return array_keys($values);
    }

    public static function clipMaterialContent(string $content): string
    {
        if (strlen($content) <= self::CONTENT_MAX_LEN) {
            return $content;
        }
        try {
            Log::channel('shanjian')->write('素材content超长已截断：' . mb_substr($content, 0, 180));
        } catch (\Throwable $e) {
        }
        return substr($content, 0, self::CONTENT_MAX_LEN);
    }

    /**
     * @return array<int, string>
     */
    public static function contentLookupValues(string $content): array
    {
        $raw = trim($content);
        $norm = self::normalizeMaterialContent($raw);
        $values = [];
        foreach ([$raw, $norm] as $value) {
            if ($value !== '') {
                $values[$value] = true;
            }
        }
        if ($norm !== '') {
            try {
                $full = trim((string)FileService::getFileUrl($norm));
                if ($full !== '') {
                    $values[$full] = true;
                }
            } catch (\Throwable $e) {
            }
        }
        $result = array_keys($values);
        foreach ($result as $value) {
            if (strlen($value) > self::CONTENT_MAX_LEN) {
                $clipped = substr($value, 0, self::CONTENT_MAX_LEN);
                if ($clipped !== '') {
                    $values[$clipped] = true;
                }
            }
        }
        return array_keys($values);
    }

    public static function findExistingId(int $userId, string $source, string $content): int
    {
        if ($userId <= 0 || $source === '' || trim($content) === '') {
            return 0;
        }
        if (array_key_exists('findExistingId', self::$testHooks)) {
            $hook = self::$testHooks['findExistingId'];
            if (is_callable($hook)) {
                return (int)$hook($userId, $source, $content);
            }
            return (int)$hook;
        }
        if ($source === self::SOURCE_IMAGE_REWRITE && array_key_exists('exists', self::$testHooks)) {
            $hook = self::$testHooks['exists'];
            $exists = is_callable($hook) ? (bool)$hook($userId, $content) : (bool)$hook;
            return $exists ? 1 : 0;
        }
        $values = self::contentLookupValues($content);
        if ($values === []) {
            return 0;
        }
        try {
            $id = self::where('user_id', $userId)
                ->where('source', $source)
                ->whereIn('content', $values)
                ->order('id', 'asc')
                ->value('id');
            return (int)$id;
        } catch (\Throwable $e) {
            try {
                Log::channel('shanjian')->write('素材按content查重失败：' . $e->getMessage());
            } catch (\Throwable $ignored) {
            }
            return 0;
        }
    }

    public static function findExistingIdByRemoteUrl(int $userId, string $source, string $remoteUrl): int
    {
        $remoteUrl = trim($remoteUrl);
        if ($userId <= 0 || $source === '' || $remoteUrl === '') {
            return 0;
        }
        if (array_key_exists('findExistingIdByRemoteUrl', self::$testHooks)) {
            $hook = self::$testHooks['findExistingIdByRemoteUrl'];
            if (is_callable($hook)) {
                return (int)$hook($userId, $source, $remoteUrl);
            }
            return (int)$hook;
        }
        $candidates = self::remoteUrlLookupValues($remoteUrl);
        if ($candidates === []) {
            return 0;
        }
        $stripped = self::stripRemoteUrlQuery($remoteUrl);
        try {
            $id = self::where('user_id', $userId)
                ->where('source', $source)
                ->where(function ($query) use ($candidates, $stripped) {
                    $query->whereIn('remote_url', $candidates);
                    if ($stripped !== '') {
                        $likePrefix = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $stripped);
                        $query->whereOr('remote_url', 'like', $likePrefix . '?%');
                    }
                })
                ->order('id', 'asc')
                ->value('id');
            return (int)$id;
        } catch (\Throwable $e) {
            try {
                Log::channel('shanjian')->write('素材按源链查重失败：' . $e->getMessage());
            } catch (\Throwable $ignored) {
            }
            return 0;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public static function findExistingVideoImitationRow(int $userId, string $remoteUrl): array
    {
        $remoteUrl = trim($remoteUrl);
        if ($userId <= 0 || $remoteUrl === '') {
            return [];
        }
        if (array_key_exists('findExistingVideoImitationRow', self::$testHooks)) {
            $hook = self::$testHooks['findExistingVideoImitationRow'];
            if (is_callable($hook)) {
                $row = $hook($userId, $remoteUrl);
                return is_array($row) ? $row : [];
            }
            return is_array($hook) ? $hook : [];
        }
        $id = self::findExistingIdByRemoteUrl($userId, self::SOURCE_VIDEO_IMITATION, $remoteUrl);
        if ($id <= 0) {
            return [];
        }
        try {
            $model = self::where('id', $id)->find();
            if (!$model) {
                return [];
            }
            return [
                'id' => (int)($model->id ?? 0),
                'content' => (string)($model->content ?? ''),
                'pic' => (string)($model->pic ?? ''),
                'size' => (int)($model->size ?? 0),
                'duration' => (int)($model->duration ?? 0),
                'm_type' => (int)($model->m_type ?? 0),
            ];
        } catch (\Throwable $e) {
            try {
                Log::channel('shanjian')->write('素材按源链复用查行失败：' . $e->getMessage());
            } catch (\Throwable $ignored) {
            }
            return [];
        }
    }

    /**
     * @return array<int, string>
     */
    public static function listExistingRemoteUrls(int $userId, string $source): array
    {
        if (array_key_exists('listExistingRemoteUrls', self::$testHooks)) {
            $hook = self::$testHooks['listExistingRemoteUrls'];
            if (is_callable($hook)) {
                $urls = $hook($userId, $source);
                return is_array($urls) ? array_values(array_map('strval', $urls)) : [];
            }
            return is_array($hook) ? array_values(array_map('strval', $hook)) : [];
        }
        if ($userId <= 0 || $source === '') {
            return [];
        }
        try {
            $urls = self::where('user_id', $userId)
                ->where('source', $source)
                ->where('remote_url', '<>', '')
                ->column('remote_url');
            return array_values(array_filter(array_map('strval', is_array($urls) ? $urls : [])));
        } catch (\Throwable $e) {
            try {
                Log::channel('shanjian')->write('素材源链列表读取失败：' . $e->getMessage());
            } catch (\Throwable $ignored) {
            }
            return [];
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function buildHotspotRow(
        int $userId,
        string $name,
        string $fileUrl,
        string $pic,
        int $mType,
        int $platform,
        int $duration,
        int $size,
        string $remoteUrl,
        int $now = 0
    ): ?array {
        $content = self::normalizeMaterialContent($fileUrl);
        if ($content === '') {
            return null;
        }
        $now = $now > 0 ? $now : time();
        return [
            'user_id' => $userId,
            'name' => $name,
            'content' => self::clipMaterialContent($content),
            'pic' => $pic,
            'm_type' => $mType,
            'type' => $platform,
            'duration' => $duration,
            'size' => $size,
            'group_id' => 0,
            'source' => self::SOURCE_HOTSPOT,
            'remote_url' => self::normalizeRemoteUrl($remoteUrl),
            'sort' => 0,
            'create_time' => $now,
            'update_time' => $now,
        ];
    }

    /**
     * 组装手动复刻入库行（不写库，便于无库断言）。
     *
     * @param array<int, array<string, mixed>> $items
     * @return array{rows: array<int, array<string, mixed>>, positions: array<int, int>}
     */
    public static function buildVideoImitationRows(int $userId, int $platformType, array $items, int $now = 0): array
    {
        $now = $now > 0 ? $now : time();
        $platform = self::mapDevicePlatform($platformType);
        $stamp = date('YmdHis', $now);
        $rows = [];
        $positions = [];
        $seq = 0;
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $fileUrl = trim((string)($item['fileUrl'] ?? $item['file_url'] ?? ''));
            $kind = (string)($item['type'] ?? '');
            if ($fileUrl === '' || !in_array($kind, ['video', 'image'], true)) {
                continue;
            }
            $isVideo = $kind === 'video';
            $content = self::clipMaterialContent(self::normalizeMaterialContent($fileUrl));
            if ($content === '') {
                continue;
            }
            $size = (int)($item['size'] ?? $item['file_size'] ?? 0);
            if ($size <= 0) {
                $size = self::resolveFileSizeBytes($fileUrl);
            }
            if ($size <= 0) {
                try {
                    Log::channel('shanjian')->write('手动爆款复刻素材无法计算文件大小：' . mb_substr($fileUrl, 0, 180));
                } catch (\Throwable $e) {
                }
            }
            $seq++;
            $remoteUrl = trim((string)($item['remote_url'] ?? $item['remoteUrl'] ?? $item['link'] ?? ''));
            if ($remoteUrl !== '') {
                $stripped = self::stripRemoteUrlQuery($remoteUrl);
                $remoteUrl = self::normalizeRemoteUrl($stripped !== '' ? $stripped : $remoteUrl);
            }
            $rows[] = [
                'user_id' => $userId,
                'name' => sprintf('手动爆款复刻-%s-%s-%d', $isVideo ? '视频' : '图片', $stamp, $seq),
                'content' => $content,
                'pic' => self::resolveVideoImitationPic($isVideo, $content, $item),
                'm_type' => $isVideo ? self::TYPE_VIDEO : self::TYPE_IMAGE,
                'type' => $platform,
                'duration' => $isVideo ? (int)($item['duration'] ?? 0) : 2,
                'size' => $size,
                'group_id' => 0,
                'source' => self::SOURCE_VIDEO_IMITATION,
                'remote_url' => $remoteUrl,
                'sort' => 0,
                'create_time' => $now,
                'update_time' => $now,
            ];
            $positions[] = (int)$index;
        }
        return ['rows' => $rows, 'positions' => $positions];
    }

    /**
     * 手动爆款复刻 AI 素材写入 SV 库。
     *
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    /**
     * 组装图生图改写入库行（不写库，便于无库断言）。
     *
     * @return array<string, mixed>|null
     */
    public static function buildImageRewriteRow(
        int $userId,
        int $platformType,
        string $scene,
        string $fileUrl,
        int $size,
        int $now = 0,
        int $seq = 1
    ): ?array {
        if ($userId <= 0) {
            return null;
        }
        if (!in_array($scene, [self::IMAGE_REWRITE_SCENE_MANUAL, self::IMAGE_REWRITE_SCENE_AUTO], true)) {
            return null;
        }
        $fileUrl = trim($fileUrl);
        if ($fileUrl === '') {
            return null;
        }
        $content = self::clipMaterialContent(self::normalizeMaterialContent($fileUrl));
        if ($content === '') {
            return null;
        }
        $now = $now > 0 ? $now : time();
        $seq = max(1, $seq);
        $prefix = $scene === self::IMAGE_REWRITE_SCENE_AUTO ? '自动爆款复刻' : '手动爆款复刻';
        $name = $prefix . '-' . $now;
        if ($seq > 1) {
            $name .= '-' . $seq;
        }

        return [
            'user_id' => $userId,
            'name' => $name,
            'content' => $content,
            'pic' => $content,
            'm_type' => self::TYPE_IMAGE,
            'type' => self::mapDevicePlatform($platformType),
            'duration' => 2,
            'size' => $size,
            'group_id' => 0,
            'source' => self::SOURCE_IMAGE_REWRITE,
            'sort' => 0,
            'create_time' => $now,
            'update_time' => $now,
        ];
    }

    /**
     * 图生图改写成功图写入 SV 库。失败只记日志，不抛给调用方。
     */
    public static function persistImageRewriteMaterial(
        int $userId,
        int $platformType,
        string $scene,
        string $fileUrl,
        int $size,
        int $now = 0
    ): bool {
        try {
            $now = $now > 0 ? $now : time();
            $row = self::buildImageRewriteRow($userId, $platformType, $scene, $fileUrl, $size, $now, 1);
            if ($row === null) {
                return false;
            }
            if (self::findExistingId($userId, self::SOURCE_IMAGE_REWRITE, (string)$row['content']) > 0) {
                return true;
            }
            $seq = self::nextImageRewriteNameSeq($userId, $scene, $now);
            if ($seq > 1) {
                $prefix = $scene === self::IMAGE_REWRITE_SCENE_AUTO ? '自动爆款复刻' : '手动爆款复刻';
                $row['name'] = $prefix . '-' . $now . '-' . $seq;
            }
            if ($size <= 0) {
                try {
                    Log::channel('shanjian')->write('图生图改写素材无法计算文件大小：' . mb_substr($fileUrl, 0, 180));
                } catch (\Throwable $e) {
                }
            }
            if (array_key_exists('persistCall', self::$testHooks) && is_callable(self::$testHooks['persistCall'])) {
                (self::$testHooks['persistCall'])($row);
            }
            self::saveVideoImitationRows([$row]);
            return true;
        } catch (\Throwable $e) {
            try {
                Log::channel('shanjian')->write('图生图改写素材入库失败：' . $e->getMessage());
            } catch (\Throwable $ignored) {
            }
            return false;
        }
    }

    public static function persistVideoImitationMaterials(int $userId, int $platformType, array $items): array
    {
        if ($items === []) {
            return [];
        }
        $built = self::buildVideoImitationRows($userId, $platformType, $items);
        $insertPositions = $built['positions'];
        if ($built['rows'] === []) {
            return array_values($items);
        }
        $pendingRows = [];
        $pendingPositions = [];
        foreach ($built['rows'] as $j => $row) {
            $pos = $insertPositions[$j] ?? null;
            $existingId = 0;
            $remoteUrl = trim((string)($row['remote_url'] ?? ''));
            if ($remoteUrl !== '') {
                $existingId = self::findExistingIdByRemoteUrl($userId, self::SOURCE_VIDEO_IMITATION, $remoteUrl);
            }
            if ($existingId <= 0) {
                $existingId = self::findExistingId($userId, self::SOURCE_VIDEO_IMITATION, (string)($row['content'] ?? ''));
            }
            if ($existingId > 0) {
                if ($pos !== null) {
                    $items[$pos]['id'] = $existingId;
                    $items[$pos]['material_store'] = 'sv_media';
                }
                continue;
            }
            $pendingRows[] = $row;
            if ($pos !== null) {
                $pendingPositions[] = $pos;
            }
        }
        if ($pendingRows === []) {
            return array_values($items);
        }
        $pendingRows = self::fillMissingVideoImitationCovers($pendingRows);
        $result = self::saveVideoImitationRows($pendingRows);
        foreach ($result as $j => $model) {
            $pos = $pendingPositions[$j] ?? null;
            if ($pos === null) {
                continue;
            }
            $items[$pos]['id'] = is_object($model) ? (int)($model->id ?? 0) : (int)($model['id'] ?? 0);
            $items[$pos]['material_store'] = 'sv_media';
        }
        return array_values($items);
    }

    public static function resolveFileSizeBytesForTest(string $fileUrl): int
    {
        return self::resolveFileSizeBytes($fileUrl);
    }

    private static function nextImageRewriteNameSeq(int $userId, string $scene, int $now): int
    {
        $prefix = $scene === self::IMAGE_REWRITE_SCENE_AUTO ? '自动爆款复刻' : '手动爆款复刻';
        $baseName = $prefix . '-' . $now;
        if (array_key_exists('sameSecondCount', self::$testHooks)) {
            $hook = self::$testHooks['sameSecondCount'];
            $count = is_callable($hook) ? (int)$hook($userId, $baseName) : (int)$hook;
            return $count + 1;
        }
        $count = (int)self::where('user_id', $userId)
            ->where('source', self::SOURCE_IMAGE_REWRITE)
            ->where('name', 'like', $baseName . '%')
            ->count();
        return $count + 1;
    }

    /**
     * @param array<int, array<string, mixed>> $insertData
     * @return iterable<int, mixed>
     */
    public static function saveMaterialRows(array $insertData): iterable
    {
        return self::saveVideoImitationRows($insertData);
    }

    /**
     * @param array<int, array<string, mixed>> $insertData
     * @return iterable<int, mixed>
     */
    private static function saveVideoImitationRows(array $insertData): iterable
    {
        if (array_key_exists('saveAll', self::$testHooks)) {
            $hook = self::$testHooks['saveAll'];
            if ($hook instanceof \Throwable) {
                throw $hook;
            }
            if (is_callable($hook)) {
                $saved = $hook($insertData);
                return is_iterable($saved) ? $saved : [];
            }
            return is_iterable($hook) ? $hook : [];
        }
        return (new self())->saveAll($insertData);
    }

    /**
     * @param array<string, mixed> $item
     */
    private static function resolveVideoImitationPic(bool $isVideo, string $content, array $item): string
    {
        if (!$isVideo) {
            return $content;
        }
        $raw = trim((string)($item['pic'] ?? $item['thumbnail_url'] ?? $item['image'] ?? ''));
        if ($raw === '') {
            return '';
        }
        return (string)FileService::setFileUrl($raw);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private static function fillMissingVideoImitationCovers(array $rows): array
    {
        foreach ($rows as $i => $row) {
            if (!is_array($row) || (int)($row['m_type'] ?? 0) !== self::TYPE_VIDEO) {
                continue;
            }
            if (trim((string)($row['pic'] ?? '')) !== '') {
                continue;
            }
            $content = (string)($row['content'] ?? '');
            $pic = self::makeVideoImitationCover($content, (int)($row['duration'] ?? 0));
            $rows[$i]['pic'] = $pic;
            if ($pic === '') {
                try {
                    Log::channel('shanjian')->write('手动爆款复刻视频封面抽帧失败：' . mb_substr($content, 0, 180));
                } catch (\Throwable $e) {
                }
            }
        }
        return $rows;
    }

    private static function makeVideoImitationCover(string $videoUri, int $duration): string
    {
        if (array_key_exists('makeThumb', self::$testHooks)) {
            $hook = self::$testHooks['makeThumb'];
            if (is_callable($hook)) {
                return (string)$hook($videoUri, $duration);
            }
            return (string)$hook;
        }
        $videoUri = trim($videoUri);
        if ($videoUri === '') {
            return '';
        }
        $seek = $duration > 0 ? min(0.3, max(0.01, $duration * 0.15)) : 0.1;
        try {
            return (string)MaterialService::makeVideoThumbnail($videoUri, $seek);
        } catch (\Throwable $e) {
            try {
                Log::channel('shanjian')->write('手动爆款复刻视频封面抽帧异常：' . $e->getMessage());
            } catch (\Throwable $ignored) {
            }
            return '';
        }
    }

    private static function resolveFileSizeBytes(string $fileUrl): int
    {
        if (array_key_exists('fileSize', self::$testHooks)) {
            $hook = self::$testHooks['fileSize'];
            if (is_callable($hook)) {
                return (int)$hook($fileUrl);
            }
            return (int)$hook;
        }
        $fileUrl = trim($fileUrl);
        if ($fileUrl === '') {
            return 0;
        }
        $uri = ltrim((string)FileService::setFileUrl($fileUrl), '/\\');
        $candidates = [];
        if (is_file($fileUrl)) {
            $candidates[] = $fileUrl;
        }
        if ($uri !== '') {
            $public = FileService::getFileUrl($uri, 'public_path');
            if ($public !== '' && !str_starts_with($public, 'http://') && !str_starts_with($public, 'https://')) {
                $candidates[] = $public;
            }
            $rel = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $uri);
            $candidates[] = rtrim(public_path(), '/\\') . DIRECTORY_SEPARATOR . $rel;
            if (function_exists('root_path')) {
                $candidates[] = rtrim(root_path(), '/\\') . DIRECTORY_SEPARATOR . $rel;
            }
        }
        foreach ($candidates as $path) {
            if ($path === '' || !is_file($path)) {
                continue;
            }
            $size = filesize($path);
            if ($size !== false && $size > 0) {
                return (int)$size;
            }
        }
        if ($uri !== '') {
            try {
                $file = File::where('uri', $uri)->whereNull('delete_time')->order('id', 'desc')->find();
                if ($file) {
                    foreach (['size', 'file_size'] as $field) {
                        if (isset($file[$field]) && (int)$file[$field] > 0) {
                            return (int)$file[$field];
                        }
                    }
                }
            } catch (\Throwable $e) {
            }
        }
        return self::fetchRemoteContentLength($fileUrl);
    }

    private static function fetchRemoteContentLength(string $fileUrl): int
    {
        if (array_key_exists('remoteContentLength', self::$testHooks)) {
            $hook = self::$testHooks['remoteContentLength'];
            if (is_callable($hook)) {
                return (int)$hook($fileUrl);
            }
            return (int)$hook;
        }
        $url = trim($fileUrl);
        if ($url === '') {
            return 0;
        }
        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            $url = (string)FileService::getFileUrl($url);
        }
        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            return 0;
        }
        $ch = curl_init($url);
        if ($ch === false) {
            return 0;
        }
        curl_setopt_array($ch, [
            CURLOPT_NOBODY => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'IMAICC-SvMediaMaterial/1.0',
        ]);
        $ok = curl_exec($ch);
        $errno = curl_errno($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $length = (int)curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        curl_close($ch);
        if ($ok === false || $errno !== 0 || $httpCode < 200 || $httpCode >= 400 || $length <= 0) {
            return 0;
        }
        return $length;
    }

    /**
     * 人设库 material_type(1视频/2图片) → SV 库 m_type(1图片/2视频)
     */
    public static function fromPersonaMaterialType(int $materialType): int
    {
        return $materialType === 1 ? self::TYPE_VIDEO : self::TYPE_IMAGE;
    }

    /**
     * SV 库 m_type(1图片/2视频) → 人设库 material_type(1视频/2图片)
     */
    public static function toPersonaMaterialType(int $mType): int
    {
        return $mType === self::TYPE_VIDEO ? 1 : 2;
    }

    /**
     * 获取素材类型文本
     * @param int $type
     * @return string
     */
    public static function getTypeText(int $type): string
    {
        $typeTexts = [
            self::TYPE_IMAGE => '图片',
            self::TYPE_VIDEO => '视频',
        ];
        return $typeTexts[$type] ?? '未知类型';
    }

   

    /**
     * 获取创建时间的格式化
     * @return string
     */
    public function getCreateTimeAttr($value)
    {
        return date('Y-m-d H:i:s', $value);
    }

    /**
     * 获取更新时间的格式化
     * @return string
     */
    public function getUpdateTimeAttr($value)
    {
        return date('Y-m-d H:i:s', $value);
    }
}
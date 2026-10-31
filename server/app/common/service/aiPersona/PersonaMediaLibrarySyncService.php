<?php

declare(strict_types=1);

namespace app\common\service\aiPersona;

use app\common\enum\FileEnum;
use app\common\model\sv\SvMediaMaterial;
use app\common\service\FileService;
use think\facade\Log;

/**
 * IP人设素材库上传 → 外部素材库（平台素材库）同步。
 *
 * 相册上传到某个 IP 人设素材库的图片/视频，同时写一份到外部素材库，
 * 其他人设可从「素材库」选到，避免素材只在当前人设下可见。
 * 人设素材仍走原入库链路，两边各自独立，删除人设素材不影响外部素材库。
 */
class PersonaMediaLibrarySyncService
{
    /** 上传接口开关参数：传 1 表示本次上传的素材同步写入外部素材库 */
    public const SYNC_PARAM = 'sync_media_library';

    /** 上传接口的切割标记：ffmpeg=2 表示转码成功后自动切割成多段素材 */
    public const UPLOAD_FFMPEG_CUT = 2;

    /** 只接收图片/视频，音频和未知文件不进外部素材库 */
    private const MEDIA_TYPES = [
        SvMediaMaterial::TYPE_IMAGE,
        SvMediaMaterial::TYPE_VIDEO,
    ];

    public static function requested(mixed $flag): bool
    {
        return (int)$flag === 1;
    }

    /**
     * 切割上传的视频一律不进外部素材库。
     *
     * 切割上传返回的是完整原片，切片完成后原片文件会被删除，外部素材库只会留下一条
     * 打不开的整段视频；其他人设再导入这条素材时又会被「原片已进入切片」的守卫静默跳过，
     * 表现为「添加成功但列表里没有」。切片产物只属于当前 IP 人设，因此整体不同步。
     * 图片不受影响：前端会把图片的 ffmpeg=2 降为 1，这里再按类型兜一层。
     */
    public static function skipForCutUpload(array $uploadResult, int $ffmpeg): bool
    {
        return (int)$ffmpeg === self::UPLOAD_FFMPEG_CUT
            && (int)($uploadResult['type'] ?? 0) === (int)FileEnum::VIDEO_TYPE;
    }

    /**
     * 上传接口返回的 file.type（FileEnum）→ 外部素材库 m_type。
     * 图片、视频以外的类型返回 0，调用方据此跳过同步。
     */
    public static function mediaTypeByUploadType(int $uploadType): int
    {
        return match ($uploadType) {
            (int)FileEnum::IMAGE_TYPE => SvMediaMaterial::TYPE_IMAGE,
            (int)FileEnum::VIDEO_TYPE => SvMediaMaterial::TYPE_VIDEO,
            default => 0,
        };
    }

    /**
     * 把一次上传成功的文件写入外部素材库，同一用户的同一文件只写一条。
     */
    public static function mirrorUpload(int $userId, array $uploadResult, int $mediaType): void
    {
        if ($userId <= 0 || !in_array($mediaType, self::MEDIA_TYPES, true)) {
            return;
        }

        try {
            $content = SvMediaMaterial::clipMaterialContent(
                SvMediaMaterial::normalizeMaterialContent((string)($uploadResult['url'] ?? ''))
            );
            if ($content === '') {
                return;
            }
            if (self::exists($userId, $content)) {
                return;
            }

            $isVideo = $mediaType === SvMediaMaterial::TYPE_VIDEO;
            $now = time();
            SvMediaMaterial::create([
                'user_id'     => $userId,
                'name'        => self::resolveName($uploadResult),
                'content'     => $content,
                'pic'         => self::resolvePic($uploadResult, $content, $isVideo),
                'm_type'      => $mediaType,
                'type'        => SvMediaMaterial::PLATFORM_UNKNOWN,
                'size'        => self::resolveSize($uploadResult),
                'duration'    => $isVideo ? max(0, (int)round((float)($uploadResult['duration'] ?? 0))) : 0,
                'group_id'    => 0,
                'sort'        => 0,
                'create_time' => $now,
                'update_time' => $now,
            ]);
        } catch (\Throwable $e) {
            Log::write('人设素材同步外部素材库失败: ' . json_encode([
                'user_id'    => $userId,
                'media_type' => $mediaType,
                'url'        => (string)($uploadResult['url'] ?? ''),
                'error'      => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
    }

    /**
     * content 存的是相对路径，历史数据里可能混有完整 URL，按三种口径查重。
     */
    private static function exists(int $userId, string $content): bool
    {
        $values = SvMediaMaterial::contentLookupValues($content);
        if ($values === []) {
            return false;
        }
        return SvMediaMaterial::where('user_id', $userId)
            ->whereIn('content', $values)
            ->count() > 0;
    }

    private static function resolveName(array $uploadResult): string
    {
        $name = trim((string)($uploadResult['name'] ?? ''));
        if ($name !== '') {
            $name = (string)pathinfo($name, PATHINFO_FILENAME);
        }
        return $name !== '' ? mb_substr($name, 0, 100) : '素材-' . date('YmdHis');
    }

    private static function resolvePic(array $uploadResult, string $content, bool $isVideo): string
    {
        $pic = trim((string)($uploadResult['thumbnail_url'] ?? $uploadResult['thumbnail_path'] ?? ''));
        // 图片没有缩略图，封面直接用原图
        return $pic !== '' ? $pic : ($isVideo ? '' : $content);
    }

    private static function resolveSize(array $uploadResult): int
    {
        $size = (int)($uploadResult['size'] ?? 0);
        if ($size > 0) {
            return $size;
        }

        $uri = trim((string)($uploadResult['url'] ?? ''));
        if ($uri === '') {
            return 0;
        }
        try {
            $path = FileService::getFileUrl($uri, 'public_path');
            if ($path !== '' && is_file($path)) {
                $bytes = filesize($path);
                return $bytes !== false ? (int)$bytes : 0;
            }
        } catch (\Throwable $e) {
            // 大小只用于展示，取不到就写 0
        }
        return 0;
    }
}

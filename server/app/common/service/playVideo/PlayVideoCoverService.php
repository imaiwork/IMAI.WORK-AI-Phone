<?php

declare(strict_types=1);

namespace app\common\service\playVideo;

use app\common\service\ConfigService;

/**
 * 爆款玩法 · 视频封面（2026-09-02）。
 *
 * 站点存储为阿里云 OSS 时直接用 OSS 视频截帧当封面（x-oss-process=video/snapshot），
 * 零转码、零落盘：封面就是「视频相对路径 + 截帧参数」，经 FileService::getFileUrl 拼上
 * 域名即可给 <image> 用。实测截帧只对 GET 生效，HEAD 返回的是原视频元信息，别拿 HEAD 验。
 * 用途：模板中心没配封面的模板、成片记录封面兜底。本地存储或其它第三方存储返回空串。
 */
class PlayVideoCoverService
{
    /** 截帧参数：0.5 秒处避开首帧黑场，宽 540 等比缩放，快速模式 */
    private const SNAPSHOT_PROCESS = 'x-oss-process=video/snapshot,t_500,f_jpg,w_540,m_fast';

    /**
     * 由站内视频相对路径生成截帧封面 uri；外链、已带参数、非阿里云存储一律返回空串。
     */
    public static function snapshotUri(string $videoUri): string
    {
        $videoUri = trim($videoUri);
        if ($videoUri === '' || preg_match('#^https?://#i', $videoUri) || str_contains($videoUri, '?')) {
            return '';
        }
        if (!self::isAliyunStorage()) {
            return '';
        }
        return $videoUri . '?' . self::SNAPSHOT_PROCESS;
    }

    private static function isAliyunStorage(): bool
    {
        try {
            return (string)ConfigService::get('storage', 'default', 'local') === 'aliyun';
        } catch (\Throwable $e) {
            return false;
        }
    }
}

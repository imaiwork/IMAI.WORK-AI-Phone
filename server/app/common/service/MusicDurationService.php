<?php

namespace app\common\service;

use think\facade\Log;

/**
 * 音乐素材时长探测（闪剪背景音乐硬限制 0-300 秒）
 */
class MusicDurationService
{
    /**
     * 探测音乐文件时长（秒），失败返回 0
     * @param string $fileUrl 存库的相对 uri 或完整 URL
     */
    public static function probe(string $fileUrl): int
    {
        $fileUrl = trim($fileUrl);
        if ($fileUrl === '') {
            return 0;
        }
        try {
            $source = FileService::getFileUrl($fileUrl);
            // 本地文件优先，省一次远程探测/下载
            if (!preg_match('#^https?://#i', $fileUrl)) {
                $local = rtrim(public_path(), '/\\') . DIRECTORY_SEPARATOR . ltrim($fileUrl, '/\\');
                clearstatcache(true, $local);
                if (is_file($local)) {
                    $source = $local;
                }
            }
            $info = (new VideoInfoService())->getInfo($source, 30);
            return (int)ceil((float)($info['duration'] ?? 0));
        } catch (\Throwable $e) {
            Log::channel('ffmpeg')->write('[音乐时长探测] 失败 url=' . $fileUrl . ' err=' . $e->getMessage());
            return 0;
        }
    }
}

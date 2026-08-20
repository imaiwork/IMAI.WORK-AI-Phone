<?php

namespace app\common\service;

/**
 * 媒体分辨率校验
 * 闪剪接受图片/视频最大边不超过 2000;超过会被远端拒绝
 * 在下发前先校验;超标时由预检自动投递转码并等待,避免无谓失败
 */
class ResolutionValidator
{
    /** 闪剪上限 */
    const MAX_DIMENSION = 2000;

    /**
     * 批量校验 URL 列表,返回超限的违规项
     *
     * @param array<int, array{url:string,type?:string}|string> $items
     *        - 简单形式: ['http://.../a.jpg', 'http://.../b.mp4']
     *        - 显式形式: [['url'=>'...', 'type'=>'image|video'], ...]
     * @param int $maxDim 上限,默认 2000
     * @return array<int, array{url:string,type:string,width:int,height:int}>
     */
    public static function findViolations(array $items, int $maxDim = self::MAX_DIMENSION): array
    {
        $violations = [];
        foreach ($items as $item) {
            if (is_string($item)) {
                $url = $item;
                $type = self::guessType($url);
            } else {
                $url = (string)($item['url'] ?? '');
                $type = (string)($item['type'] ?? '') ?: self::guessType($url);
            }
            if ($url === '') {
                continue;
            }
            $dims = $type === 'video'
                ? self::probeVideo($url)
                : self::probeImage($url);
            if ($dims && max($dims[0], $dims[1]) > $maxDim) {
                $violations[] = [
                    'url' => $url,
                    'type' => $type,
                    'width' => $dims[0],
                    'height' => $dims[1],
                ];
            }
        }
        return $violations;
    }

    /**
     * 把违规列表格式化成用户友好的提示语
     */
    public static function formatViolationMessage(array $violations, int $maxDim = self::MAX_DIMENSION): string
    {
        if (empty($violations)) {
            return '';
        }
        $tips = [];
        foreach ($violations as $v) {
            $name = basename(parse_url($v['url'], PHP_URL_PATH) ?: $v['url']);
            $tips[] = "{$v['type']}「{$name}」当前 {$v['width']}x{$v['height']}";
        }
        return "下列素材分辨率超过 {$maxDim}x{$maxDim},请重新上传或选择其他素材:" . implode(';', $tips);
    }

    private static function guessType(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: $url;
        if (preg_match('/\.(mp4|mov|avi|webm|mkv|m4v)$/i', $path)) {
            return 'video';
        }
        return 'image';
    }

    /**
     * 取图片宽高
     * @return array{0:int,1:int}|null
     */
    private static function probeImage(string $url): ?array
    {
        try {
            // getimagesize 支持本地路径和 http(s) URL(需 allow_url_fopen=On)
            $info = @getimagesize($url);
            if (is_array($info) && isset($info[0], $info[1])) {
                return [(int)$info[0], (int)$info[1]];
            }
        } catch (\Throwable $e) {
            // 网络异常等情况:为避免误拦截,返回 null(放行交给闪剪自己判)
        }
        return null;
    }

    /**
     * 取视频宽高(ffprobe)
     * @return array{0:int,1:int}|null
     */
    private static function probeVideo(string $url): ?array
    {
        // 视频 probe 较慢(本地几十 ms,远端可能数秒),且需 ffprobe 在 PATH 中
        $bin = self::findFfprobe();
        if (!$bin) {
            return null;
        }
        $cmd = sprintf(
            '%s -v error -select_streams v:0 -show_entries stream=width,height -of csv=p=0 %s 2>/dev/null',
            escapeshellcmd($bin),
            escapeshellarg($url)
        );
        $out = @shell_exec($cmd);
        if (is_string($out) && preg_match('/^\s*(\d+)\s*,\s*(\d+)/', $out, $m)) {
            return [(int)$m[1], (int)$m[2]];
        }
        return null;
    }

    private static function findFfprobe(): ?string
    {
        foreach (['ffprobe6', 'ffprobe', '/usr/bin/ffprobe', '/usr/local/bin/ffprobe'] as $candidate) {
            $check = @shell_exec(sprintf('command -v %s 2>/dev/null', escapeshellarg($candidate)));
            if (is_string($check) && trim($check) !== '') {
                return trim($check);
            }
        }
        return null;
    }
}

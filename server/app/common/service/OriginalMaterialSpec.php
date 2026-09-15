<?php

namespace app\common\service;

/**
 * 闪剪原图模式硬规格（与自动适配转码规范 DEFAULT_VIDEO_SPECS 分离）
 * 视频：MP4/MOV + H.264 + 25~30fps + 长边≤2000
 * 图片：JPG/PNG + 长边≤2000
 * 分辨率只卡上限 2000：闪剪合成端对图片与视频都按 2000 拒（InvalidFile.Resolution）；
 * 不设下限——小图小视频只是糊，闪剪照收，卡下限只会把能用的素材挡在外面
 * 音乐：MP3/M4A/AAC/WAV（不重编码）
 */
class OriginalMaterialSpec
{
    /** 与图片同一个上限：闪剪合成端超过 2000 一律 InvalidFile.Resolution 拒 */
    public const VIDEO_MAX_LONG_EDGE = 2000;
    public const VIDEO_FPS_MIN = 25.0;
    public const VIDEO_FPS_MAX = 30.0;
    /**
     * 图片最大边，与闪剪合成端的硬限制一致：超过会被远端以
     * InvalidFile.Resolution「图片素材分辨率不能超过2000x2000」拒掉。
     * 这里原先写的是「长边≥2048」的下限，与这个上限交集为空——
     * 合规入库的原图必然在合成时失败，2026-09-05 在 test 上被闪剪实测证实
     * （任务 6810，素材 2176x1952 与 4388x6582 均被拒）。
     */
    public const IMAGE_MAX_LONG_EDGE = 2000;
    /** 入库口径的单条时长上限，与 ShanjianVideoSettingLogic::trimMaterialsByDuration 的 maxSingle 对齐 */
    public const VIDEO_MAX_DURATION = 59.0;

    public const VIDEO_EXTS = ['mp4', 'mov'];
    public const IMAGE_EXTS = ['jpg', 'jpeg', 'png'];
    public const MUSIC_EXTS = ['mp3', 'm4a', 'aac', 'wav'];
    public const VIDEO_CODECS = ['h264', 'avc1'];
    public const VIDEO_FORMAT_TOKENS = ['mp4', 'mov', 'isom'];

    public static function isOriginalKind(mixed $kind): bool
    {
        return strtolower(trim((string)$kind)) === 'original';
    }

    public static function wantsOriginal(array $params): bool
    {
        if (self::isOriginalKind($params['material_kind'] ?? '')) {
            return true;
        }
        return (int)($params['keep_original'] ?? 0) === 1;
    }

    public static function extensionOf(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path));
        if ($path === '') {
            return '';
        }
        $basename = basename((string)(parse_url($path, PHP_URL_PATH) ?: $path));
        $ext = strtolower((string)pathinfo($basename, PATHINFO_EXTENSION));
        return $ext;
    }

    public static function isH264(string $codec): bool
    {
        return in_array(strtolower(trim($codec)), self::VIDEO_CODECS, true);
    }

    public static function isFpsOk(float $fps): bool
    {
        return $fps >= self::VIDEO_FPS_MIN && $fps <= self::VIDEO_FPS_MAX;
    }

    public static function isVideoContainerOk(string $ext, string $formatName): bool
    {
        if (!in_array(strtolower($ext), self::VIDEO_EXTS, true)) {
            return false;
        }
        $formatName = strtolower(trim($formatName));
        if ($formatName === '') {
            return false;
        }
        foreach (self::VIDEO_FORMAT_TOKENS as $token) {
            if (str_contains($formatName, $token)) {
                return true;
            }
        }
        return false;
    }

    public static function isVideoResolutionOk(int $width, int $height): bool
    {
        if ($width <= 0 || $height <= 0) {
            return false;
        }
        return max($width, $height) <= self::VIDEO_MAX_LONG_EDGE;
    }

    public static function isImageExtOk(string $ext): bool
    {
        return in_array(strtolower($ext), self::IMAGE_EXTS, true);
    }

    public static function isImageResolutionOk(int $width, int $height): bool
    {
        if ($width <= 0 || $height <= 0) {
            return false;
        }
        return max($width, $height) <= self::IMAGE_MAX_LONG_EDGE;
    }

    public static function isMusicExtOk(string $ext): bool
    {
        return in_array(strtolower($ext), self::MUSIC_EXTS, true);
    }

    /**
     * 探测视频真实规格，供调用方回填素材表的时长/宽高。
     * 抓取侧拿到的第三方元数据经常缺失或是占位值，不能拿来当判据。
     *
     * @return array{duration:float,width:int,height:int,fps:float,codec:string,format_name:string}|null
     */
    public static function describeVideo(string $url): ?array
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }
        return self::fillVideoInfo(['url' => $url]);
    }

    /**
     * 补全视频规格：调用方传入的值优先，缺项才 ffprobe 探测一次。
     * 探测失败返回 null。
     *
     * @return array{duration:float,width:int,height:int,fps:float,codec:string,format_name:string}|null
     */
    private static function fillVideoInfo(array $info): ?array
    {
        $filled = [
            'duration'    => (float)($info['duration'] ?? 0),
            'width'       => (int)($info['width'] ?? 0),
            'height'      => (int)($info['height'] ?? 0),
            'fps'         => (float)($info['fps'] ?? 0),
            'codec'       => strtolower(trim((string)($info['codec'] ?? ''))),
            'format_name' => strtolower(trim((string)($info['format_name'] ?? ''))),
        ];

        $url = trim((string)($info['url'] ?? ''));
        $needProbe = $url !== '' && (
            $filled['duration'] <= 0 || $filled['width'] <= 0 || $filled['height'] <= 0
            || $filled['fps'] <= 0 || $filled['codec'] === '' || $filled['format_name'] === ''
        );
        if (!$needProbe) {
            return $filled;
        }

        $probed = self::probeVideo($url);
        if ($probed === null) {
            return null;
        }
        if ($filled['duration'] <= 0) {
            $filled['duration'] = (float)$probed['duration'];
        }
        if ($filled['width'] <= 0) {
            $filled['width'] = (int)$probed['width'];
        }
        if ($filled['height'] <= 0) {
            $filled['height'] = (int)$probed['height'];
        }
        if ($filled['fps'] <= 0) {
            $filled['fps'] = (float)$probed['fps'];
        }
        if ($filled['codec'] === '') {
            $filled['codec'] = (string)$probed['codec'];
        }
        if ($filled['format_name'] === '') {
            $filled['format_name'] = (string)$probed['format_name'];
        }

        return $filled;
    }

    /**
     * 入库口径：容器/编码/帧率/分辨率之外，再卡单条时长上限。
     * 合成时 trimMaterialsByDuration 会直接丢掉超过 59 秒的素材，入库就拦住，
     * 免得素材躺在库里能看见、到合成又报"没有可用的原始素材"。
     * 下发前预检不走这个口径：一批里混了超时长素材应当被裁掉，而不是整单失败。
     *
     * @return true|string
     */
    public static function assertVideoForIngest(array $info)
    {
        $filled = self::fillVideoInfo($info);
        if ($filled === null) {
            return '无法识别视频规格，请更换符合原图模式要求的文件';
        }

        $guard = self::assertVideo(array_merge($info, [
            'duration'    => $filled['duration'],
            'width'       => $filled['width'],
            'height'      => $filled['height'],
            'fps'         => $filled['fps'],
            'codec'       => $filled['codec'],
            'format_name' => $filled['format_name'],
        ]));
        if ($guard !== true) {
            return $guard;
        }

        if ($filled['duration'] > self::VIDEO_MAX_DURATION) {
            return '原图模式视频单条时长不能超过' . (int)self::VIDEO_MAX_DURATION . '秒';
        }

        return true;
    }

    /**
     * @param array{url?:string,name?:string,duration?:float|int,width?:int,height?:int,fps?:float|int,codec?:string,format_name?:string} $info
     * @return true|string
     */
    public static function assertVideo(array $info)
    {
        $url = trim((string)($info['url'] ?? ''));
        $name = trim((string)($info['name'] ?? ''));
        $ext = self::extensionOf($url !== '' ? $url : $name);
        if ($ext === '' || !in_array($ext, self::VIDEO_EXTS, true)) {
            return '原图模式视频仅支持 MP4/MOV 容器';
        }

        $filled = self::fillVideoInfo($info);
        if ($filled === null) {
            return '无法识别视频规格，请更换符合原图模式要求的文件';
        }
        $duration = (float)$filled['duration'];
        $width = (int)$filled['width'];
        $height = (int)$filled['height'];
        $fps = (float)$filled['fps'];
        $codec = strtolower(trim((string)$filled['codec']));
        $formatName = strtolower(trim((string)$filled['format_name']));

        if ($duration <= 0) {
            return '无法识别视频时长，请更换文件';
        }
        if (!self::isVideoContainerOk($ext, $formatName)) {
            return '原图模式视频仅支持 MP4/MOV 容器';
        }
        if ($codec === '') {
            return '无法识别视频编码，请更换文件';
        }
        if (!self::isH264($codec)) {
            return '原图模式视频编码必须是 H.264';
        }
        if ($fps <= 0) {
            return '无法识别视频帧率，请更换文件';
        }
        if (!self::isFpsOk($fps)) {
            return '原图模式视频帧率必须在 25~30 fps';
        }
        if ($width <= 0 || $height <= 0) {
            return '无法识别视频分辨率，请更换文件';
        }
        $long = max($width, $height);
        if ($long > self::VIDEO_MAX_LONG_EDGE) {
            return '原图模式视频长边不能超过' . self::VIDEO_MAX_LONG_EDGE
                . '像素（当前 ' . $width . 'x' . $height . '）';
        }
        return true;
    }

    /**
     * @param array{url?:string,name?:string,width?:int,height?:int} $info
     * @return true|string
     */
    public static function assertImage(array $info)
    {
        $url = trim((string)($info['url'] ?? ''));
        $name = trim((string)($info['name'] ?? ''));
        $ext = self::extensionOf($url !== '' ? $url : $name);
        if (!self::isImageExtOk($ext)) {
            return '原图模式图片仅支持 JPG/PNG';
        }

        $width = (int)($info['width'] ?? 0);
        $height = (int)($info['height'] ?? 0);
        if (($width <= 0 || $height <= 0) && $url !== '') {
            $dims = self::probeImage($url);
            if ($dims === null) {
                return '无法识别图片分辨率，请更换符合原图模式要求的文件';
            }
            $width = $dims[0];
            $height = $dims[1];
        }
        if ($width <= 0 || $height <= 0) {
            return '无法识别图片分辨率，请更换文件';
        }
        if (!self::isImageResolutionOk($width, $height)) {
            return '原图模式图片最大边不能超过' . self::IMAGE_MAX_LONG_EDGE
                . '像素（当前 ' . $width . 'x' . $height . '），请压缩后再上传';
        }
        return true;
    }

    /**
     * @param array{url?:string,name?:string} $info
     * @return true|string
     */
    public static function assertMusic(array $info)
    {
        $url = trim((string)($info['url'] ?? ''));
        $name = trim((string)($info['name'] ?? ''));
        $ext = self::extensionOf($url !== '' ? $url : $name);
        if (!self::isMusicExtOk($ext)) {
            return '原图模式音乐仅支持 MP3/M4A/AAC/WAV';
        }
        return true;
    }

    /**
     * @return array{duration:float,width:int,height:int,fps:float,codec:string,format_name:string}|null
     */
    public static function probeVideo(string $url): ?array
    {
        $source = self::resolveProbeSource($url);
        if ($source === '') {
            return null;
        }
        try {
            $info = (new VideoInfoService())->extractVideoInfo($source, 30);
        } catch (\Throwable $e) {
            return null;
        }
        if (!is_array($info) || empty($info)) {
            return null;
        }
        return [
            'duration' => (float)($info['duration'] ?? 0),
            'width' => (int)($info['video']['width'] ?? 0),
            'height' => (int)($info['video']['height'] ?? 0),
            'fps' => (float)($info['video']['fps'] ?? 0),
            'codec' => strtolower((string)($info['video']['codec'] ?? '')),
            'format_name' => strtolower((string)($info['format_name'] ?? '')),
        ];
    }

    /**
     * @return array{0:int,1:int}|null
     */
    public static function probeImage(string $url): ?array
    {
        $source = self::resolveProbeSource($url);
        if ($source === '') {
            return null;
        }
        try {
            $info = @getimagesize($source);
            if (is_array($info) && isset($info[0], $info[1]) && (int)$info[0] > 0 && (int)$info[1] > 0) {
                return [(int)$info[0], (int)$info[1]];
            }
        } catch (\Throwable $e) {
        }
        return null;
    }

    public static function resolveProbeSource(string $uri): string
    {
        $uri = trim($uri);
        if ($uri === '') {
            return '';
        }
        if (!preg_match('#^https?://#i', $uri)) {
            $local = rtrim((string)public_path(), '/\\') . DIRECTORY_SEPARATOR . ltrim($uri, '/\\');
            clearstatcache(true, $local);
            if (is_file($local)) {
                return $local;
            }
        }
        return FileService::getFileUrl($uri);
    }
}

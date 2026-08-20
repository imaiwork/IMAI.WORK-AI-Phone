<?php


namespace app\common\service;

use think\facade\Cache;
use app\common\service\{ConfigService, storage\Driver as StorageDriver};

class FileService
{

    /**
     * @notes 补全路径
     * @param string $uri
     * @param string $type
     * @return string
     * @author 段誉
     * @date 2021/12/28 15:19
     * @remark
     * 场景一:补全域名路径,仅传参$uri;
     *      例: FileService::getFileUrl('uploads/img.png');
     *
     * 场景二:补全获取web根目录路径, 传参$uri 和 $type = public_path;
     *      例: FileService::getFileUrl('uploads/img.png', 'public_path');
     *
     * 场景三:获取当前储存方式的域名
     *      例: FileService::getFileUrl();
     */
    public static function getFileUrl(string $uri = '', string $type = '', bool $isoss = false): string
    {
        $uri = trim($uri);
        if ($uri == '')  return $uri;
        if (strstr($uri, 'http://'))  return $uri;
        if (strstr($uri, 'https://')) return $uri;

        $default = Cache::get('STORAGE_DEFAULT');
        if (!$default) {
            $default = ConfigService::get('storage', 'default', 'local');
            Cache::set('STORAGE_DEFAULT', $default);
        }
        //强制跳过本地判断，直接获取oss连接
        if (!$isoss) {
           // 优先本地文件
            $localPath = public_path() . $uri;
            if (file_exists($localPath)) {
                if ($type == 'public_path') {
                    return $localPath;
                }
                return config('app.app_host') . '/' . $uri;
            }
        }
      

        // 本地存储：始终使用站点域名补全（兼容 $isoss=true 时跳过本地判断的场景，
        // 否则 local 配置无 domain 字段会返回相对路径 /uploads/...）
        if ($default === 'local') {
            $localPath = public_path() . $uri;
            if ($type == 'public_path') {
                return $localPath;
            }
            return self::format(config('app.app_host'), $uri);
        }

        //第三方存储
        $storage = Cache::get('STORAGE_ENGINE');
        //print_r($storage);die;
        if (!$storage) {
            $storage = ConfigService::get('storage', $default);
            Cache::set('STORAGE_ENGINE', $storage);
        }
        $domain = $storage ? ($storage['domain'] ?? '') : '';

        return self::format($domain, $uri);
    }

    /**
     * @notes 转相对路径
     * @param $uri
     * @return mixed
     * @author 张无忌
     * @date 2021/7/28 15:09
     */
    public static function setFileUrl(mixed$uri)
    {
        $uri = trim($uri);

        $default = ConfigService::get('storage', 'default', 'local');
        if ($default === 'local') {
            $domain = config('app.app_host');
            return str_replace($domain . '/', '', $uri);
        } else {
            $storage = ConfigService::get('storage', $default);
            return str_replace($storage['domain'] . '/', '', $uri);
        }
    }


    /**
     * @notes 格式化url
     * @param $domain
     * @param $uri
     * @return string
     * @author 段誉
     * @date 2022/7/11 10:36
     */
    public static function format(mixed $domain, mixed $uri)
    {
        // 处理域名
        $domainLen = strlen($domain);
        $domainRight = substr($domain, $domainLen - 1, 1);
        if ('/' == $domainRight) {
            $domain = substr_replace($domain, '', $domainLen - 1, 1);
        }

        // 处理uri
        $uriLeft = substr($uri, 0, 1);
        if ('/' == $uriLeft) {
            $uri = substr_replace($uri, '', 0, 1);
        }

        return trim($domain) . '/' . trim($uri);
    }


    /**
     * 下载远程文件（流式写盘，失败时返回原 URL，避免大文件 OOM/超时拖垮业务）
     * @param string $url 文件地址
     * @param string $type 文件类型
     * @param int $timeout 整次下载超时秒数，0 表示不限制（仍保留连接超时）
     */
    public static function downloadFileBySource(string $url, string $type, int $timeout = 180): string
    {
        if (!str_contains($url, 'http')) {
            return $url;
        }

        $typePath = match ($type) {
            'avatar'    => 'images',
            'audio'     => 'audio',
            'video'     => 'video',
            'image'     => 'images',
            default     => 'images',
        };

        // TOS 等带 query 的 URL：只取 path 文件名，避免 ? 污染本地文件名
        $pathName = (string)(parse_url($url, PHP_URL_PATH) ?: '');
        $filename = $pathName !== '' ? basename($pathName) : basename(explode('?', $url, 2)[0]);

        //如果文件没有后缀，按类型补充默认后缀
        if (!str_contains(substr($filename, -7), '.')) {
            $filename = date('YmdHis') . md5(rand(100000, 999999) . time());
            $filename .= match ($type) {
                'avatar'    => '.jpg',
                'audio'     => '.mp3',
                'video'     => '.mp4',
                'image'     => '.jpg',
                default     => '.bin',
            };
        }

        $dateDir = date('Ymd');
        $relativeDir = 'uploads/' . $typePath . '/' . $dateDir;
        $filePath = $relativeDir . '/' . $filename;
        $directory = rtrim(public_path($relativeDir), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        $config = [
            'default' => ConfigService::get('storage', 'default', 'local'),
            'engine' => ConfigService::get('storage')
        ];

        try {
            if (($config['default'] ?? 'local') === 'local') {
                if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
                    return $url;
                }
                if (!self::streamDownloadToFile($url, $directory . $filename, $timeout)) {
                    return $url;
                }
                return $filePath;
            }

            // 第三方存储：先流式落到临时文件，再 upload，避免 file_get_contents 整包进内存
            $tmp = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'dl_' . md5($url . microtime(true)) . '_' . $filename;
            if (!self::streamDownloadToFile($url, $tmp, $timeout)) {
                return $url;
            }
            $StorageDriver = new StorageDriver($config);
            $StorageDriver->setUploadFileByReal($tmp);
            $ok = $StorageDriver->upload($relativeDir);
            @unlink($tmp);
            if (!$ok) {
                return $url;
            }
            $savedName = (string)$StorageDriver->getFileName();
            return $savedName !== '' ? ($relativeDir . '/' . $savedName) : $filePath;
        } catch (\Throwable $e) {
            return $url;
        }
    }

    /**
     * 流式下载远程文件到本地路径
     */
    public static function streamDownloadToFile(string $url, string $savePath, int $timeout = 180): bool
    {
        $dir = dirname($savePath);
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            return false;
        }
        if (is_file($savePath)) {
            @unlink($savePath);
        }

        $fp = @fopen($savePath, 'wb');
        if ($fp === false) {
            return false;
        }

        $ch = curl_init($url);
        if ($ch === false) {
            fclose($fp);
            @unlink($savePath);
            return false;
        }

        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_TIMEOUT => max(0, $timeout),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FAILONERROR => true,
            CURLOPT_USERAGENT => 'IMAICC-FileService/1.0',
        ]);

        $ok = curl_exec($ch);
        $errno = curl_errno($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);

        if ($ok === false || $errno !== 0 || ($httpCode > 0 && $httpCode >= 400) || !is_file($savePath) || filesize($savePath) <= 0) {
            @unlink($savePath);
            return false;
        }
        return true;
    }

    /**
     * 是否仍是未转存的远端 http(s) 地址
     */
    public static function isRemoteHttpUrl(string $uri): bool
    {
        $uri = trim($uri);
        return $uri !== '' && (str_starts_with($uri, 'http://') || str_starts_with($uri, 'https://'));
    }

    /**
     * AI 抓素材允许的视频后缀：mp4、mov
     */
    public const GRAB_VIDEO_EXTS = ['mp4', 'mov'];

    /**
     * AI 抓素材允许的图片后缀：jpg、png、webp（静态图）
     */
    public const GRAB_IMAGE_EXTS = ['jpg', 'jpeg', 'png', 'webp'];

    /**
     * 从 URL 提取小写后缀（忽略 query/hash）
     */
    public static function getUrlExtension(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        $path = (string)(parse_url($url, PHP_URL_PATH) ?: '');
        if ($path === '') {
            $path = explode('?', $url, 2)[0];
            $path = explode('#', $path, 2)[0];
        }
        return strtolower(pathinfo($path, PATHINFO_EXTENSION));
    }

    /**
     * AI 抓素材 URL 后缀是否允许
     * @param string $type video|image
     */
    public static function isAllowedGrabMaterialUrl(string $url, string $type): bool
    {
        $ext = self::getUrlExtension($url);
        if ($ext === '') {
            return false;
        }
        if ($type === 'video') {
            return in_array($ext, self::GRAB_VIDEO_EXTS, true);
        }
        if ($type === 'image') {
            return in_array($ext, self::GRAB_IMAGE_EXTS, true);
        }
        return false;
    }

    /**
     * 确保目录对多进程账号可写（规避 root/www 混用时 umask 导致 0755）
     *
     * 禁止从文件系统根逐级 is_dir/chmod：生产 open_basedir 通常只放行项目目录与 /tmp，
     * 探测 /www 这类祖先路径会直接抛警告并中断写文件。
     */
    public static function ensureWritableDir(string $absoluteDir): void
    {
        $absoluteDir = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $absoluteDir), DIRECTORY_SEPARATOR);
        if ($absoluteDir === '') {
            return;
        }

        $base = self::resolveWritableDirBase($absoluteDir);
        $prevUmask = umask(0);
        try {
            if ($base === '') {
                if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0777) && !is_dir($absoluteDir)) {
                    throw new \RuntimeException('目录创建失败：' . $absoluteDir);
                }
                @chmod($absoluteDir, 0777);
                return;
            }

            $relative = ltrim(substr($absoluteDir, strlen($base)), DIRECTORY_SEPARATOR);
            $cursor = $base;
            if ($relative === '') {
                if (is_dir($cursor)) {
                    @chmod($cursor, 0777);
                }
                return;
            }

            foreach (explode(DIRECTORY_SEPARATOR, $relative) as $part) {
                if ($part === '') {
                    continue;
                }
                $cursor .= DIRECTORY_SEPARATOR . $part;
                if (!is_dir($cursor) && !mkdir($cursor, 0777) && !is_dir($cursor)) {
                    throw new \RuntimeException('目录创建失败：' . $cursor);
                }
                @chmod($cursor, 0777);
            }
        } finally {
            umask($prevUmask);
        }
    }

    /**
     * 取目标路径上最长的可探测前缀（open_basedir / 项目根 / public / 临时目录）
     */
    public static function resolveWritableDirBase(string $absoluteDir): string
    {
        $candidates = [];
        $openBasedir = (string)ini_get('open_basedir');
        if ($openBasedir !== '') {
            foreach (explode(PATH_SEPARATOR, $openBasedir) as $item) {
                $item = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($item)), DIRECTORY_SEPARATOR);
                if ($item !== '') {
                    $candidates[] = $item;
                }
            }
        }
        foreach ([root_path(), public_path(), sys_get_temp_dir()] as $item) {
            $item = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string)$item), DIRECTORY_SEPARATOR);
            if ($item !== '') {
                $candidates[] = $item;
            }
        }
        $candidates = array_values(array_unique($candidates));
        usort($candidates, static function (string $left, string $right): int {
            return strlen($right) <=> strlen($left);
        });

        foreach ($candidates as $candidate) {
            if (self::isPathPrefix($absoluteDir, $candidate)) {
                return $candidate;
            }
        }
        return '';
    }

    private static function isPathPrefix(string $path, string $prefix): bool
    {
        if ($prefix === '') {
            return false;
        }
        if (DIRECTORY_SEPARATOR === '\\') {
            $path = strtolower($path);
            $prefix = strtolower($prefix);
        }
        return $path === $prefix || str_starts_with($path, $prefix . DIRECTORY_SEPARATOR);
    }

    /**
     * 写文件后放宽权限，便于其他账号读/删
     */
    public static function ensureWritableFile(string $absoluteFile): void
    {
        $absoluteFile = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $absoluteFile);
        if ($absoluteFile === '' || !is_file($absoluteFile)) {
            return;
        }
        @chmod($absoluteFile, 0666);
    }
}

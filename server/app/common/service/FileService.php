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
    public static function getFileUrl(string $uri = '', string $type = ''): string
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
        // 优先本地文件
        $localPath = public_path() . $uri;
        if (file_exists($localPath)) {
            if ($type == 'public_path') {
                return $localPath;
            }
            return config('app.app_host') . '/' . $uri;
        }

        //第三方存储
        $storage = Cache::get('STORAGE_ENGINE');
        //print_r($storage);die;
        if (!$storage) {
            $storage = ConfigService::get('storage', $default);
            Cache::set('STORAGE_ENGINE', $storage);
        }
        $domain = $storage ?  $storage['domain'] : '';

        return self::format($domain, $uri);
    }

    /**
     * @notes 转相对路径
     * @param $uri
     * @return mixed
     * @author 张无忌
     * @date 2021/7/28 15:09
     */
    public static function setFileUrl($uri)
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
    public static function format($domain, $uri)
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
     * 下载远程文件
     * @param string $url 文件地址
     * @param string $type 文件类型
     */
    public static function downloadFileBySource(string $url, string $type): string
    {

        if (!str_contains($url, 'http')){

            return $url;
        }

        $response = @file_get_contents($url);

        if ($response === false) {

            return $url;
        }

        $typePath = match ($type) {
            'avatar'    => 'images',
            'audio'     => 'audio',
            'video'     => 'video',
            'image'     => 'images',
        };

        // 设置保存路径，包含日期子目录
        $directory = public_path('uploads/' . $typePath . '/' . date('Ymd'));
        $filename = basename($url); // 提取文件名

        //如果文件没有后缀，按类型不补充默认后缀
        if (!str_contains(substr($filename, -7), '.')) {
            $filename = date('YmdHis').md5(rand(100000,999999).time());
            $filename .= match ($type) {
                'avatar'    => '.jpg',
                'audio'     => '.mp3',
                'video'     => '.mp4',
                'image'     => '.jpg',
            };
        }

        $savePath = $directory . $filename;
        $filePath = 'uploads/' . $typePath . '/' . date('Ymd') . '/' . $filename;

        // 存储引擎
        $config = [
            'default' => ConfigService::get('storage', 'default', 'local'),
            'engine' => ConfigService::get('storage')
        ];

        if ($config['default'] == 'local') {

            // 检查目录是否存在，不存在则创建
            if (!is_dir($directory)) {

                mkdir($directory, 0777, true);
            }

            download_file($url, $directory, $filename);
        } else {
            // 第三方存储
            $StorageDriver = new StorageDriver($config);

            if (!$StorageDriver->fetch($url, $filePath)) {

                return $url;
            }
        }

        return $filePath;
    }
}

<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\services;

use OSS\OssClient;
use app\common\workerman\wechat\traits\LoggerTrait;


class OSSService
{
    use LoggerTrait;
    private OssClient $ossClient;
    private string $bucket;
    private string $cdnDomain;

    public function __construct()
    {
        try {
            $config = config('oss');

            // 1. 使用最基础的配置
            $accessKeyId = $config['access_id'];
            $accessKeySecret = $config['access_key'];
            $endpoint = $config['endpoint'];

            $this->ossClient = new OssClient(
                $accessKeyId,
                $accessKeySecret,
                $endpoint,
            );

            $this->bucket = $config['bucket'];
            $this->cdnDomain = $config['url'];
        } catch (\Throwable $e) {
            $this->withChannel('wechat_socket')->withLevel('error')->withTitle('OSS init error')->withContext([
                'error' => $e->getMessage(),
                'endpoint' => $endpoint,
                'bucket' => $this->bucket
            ])->log();
            throw $e;
        }
    }

    /**
     * 上传文件到OSS
     */
    public function uploadFile(string $filePath, string $object): string
    {
        try {

            // 上传
            $a = $this->ossClient->uploadFile(
                $this->bucket,
                $object,
                $filePath
            );
            
            return rtrim($this->cdnDomain, '/') . '/' . $object;
        } catch (\Throwable $e) {
            $this->withChannel('wechat_socket')->withLevel('error')->withTitle('Upload file error')->withContext([
                'error' => $e->getMessage(),
            ])->log();

            throw $e;
        }
    }
}

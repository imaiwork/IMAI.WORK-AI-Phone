<?php

declare(strict_types=1);

namespace app\common\service\playVideo;

use app\common\model\playVideo\PlayVideoFace;
use app\common\service\ConfigService;
use OSS\OssClient;

/**
 * 爆款玩法 · 人脸素材。
 *
 * 2026-09-02 用户定稿：人脸照直接存站点存储桶（play_video/face/ 前缀），
 * 不再要求单独私有桶（原 PLAY_FACE_BUCKET 方案作废）。
 * 归属校验保留：素材登记表按 user_id 记录归属，URL 只发给素材归属用户。
 */
class PlayVideoFaceService
{
    private const ALLOW_EXT = ['jpg', 'jpeg', 'png', 'webp'];
    private const MAX_BYTES = 10 * 1024 * 1024;
    public const SIGN_TTL_SUBMIT = 48 * 3600; // 提交给中台转发百炼拉取：48h
    private const SIGN_TTL_PREVIEW = 600;     // 前端预览：10min

    /**
     * 上传人脸照，返回 ['face_file_id' => int, 'preview_url' => string]。
     *
     * @param \think\file\UploadedFile $file
     * @throws \Exception
     */
    public static function upload(int $userId, $file): array
    {
        if ($userId <= 0 || $file === null) {
            throw new \Exception('参数错误');
        }
        $ext = strtolower($file->extension() ?: pathinfo($file->getOriginalName(), PATHINFO_EXTENSION));
        if (in_array($ext, ['heic', 'heif'], true)) {
            throw new \Exception('iPhone 照片请改用 JPG/PNG 格式（设置→相机→格式→兼容性优先）');
        }
        if (!in_array($ext, self::ALLOW_EXT, true)) {
            throw new \Exception('仅支持 JPG/PNG/WEBP 图片');
        }
        if ($file->getSize() > self::MAX_BYTES) {
            throw new \Exception('图片超过 10MB，请压缩后上传');
        }

        $storage = self::storageConfig();
        $objectPath = sprintf(
            'play_video/face/%d/%s/%s.%s',
            $userId,
            date('Ymd'),
            md5(uniqid((string)$userId, true)),
            $ext
        );

        // 站点存储桶（CNAME 域名做 endpoint，对齐既有 Aliyun 引擎用法）
        $client = new OssClient($storage['access_key'], $storage['secret_key'], $storage['domain'], true);
        $client->uploadFile($storage['bucket'], $objectPath, $file->getRealPath());

        $row = PlayVideoFace::create([
            'user_id'     => $userId,
            'object_path' => $objectPath,
            'bucket'      => '',
            'create_time' => time(),
        ]);

        return [
            'face_file_id' => (int)$row->id,
            'preview_url'  => self::signedUrl($userId, (int)$row->id, self::SIGN_TTL_PREVIEW),
        ];
    }

    /**
     * 生成素材访问 URL（站点存储桶公开地址；bucket 字段兼容历史私有桶行走签名）。
     * 归属校验：素材不属于该用户直接拒绝。
     *
     * @throws \Exception
     */
    public static function signedUrl(int $userId, int $faceId, int $ttl = self::SIGN_TTL_SUBMIT): string
    {
        $row = PlayVideoFace::where('id', $faceId)->where('user_id', $userId)->findOrEmpty();
        if ($row->isEmpty()) {
            throw new \Exception('人脸素材不存在，请重新上传');
        }
        $storage = self::storageConfig();
        $bucket = (string)$row->bucket;
        $objectPath = (string)$row->object_path;
        if ($bucket === '') {
            return rtrim((string)$storage['domain'], '/') . '/' . ltrim($objectPath, '/');
        }
        $client = new OssClient($storage['access_key'], $storage['secret_key'], $storage['endpoint'], false);
        return (string)$client->signUrl($bucket, $objectPath, max(60, $ttl), OssClient::OSS_HTTP_GET);
    }

    /**
     * 站点存储设置（阿里云引擎）+ 推导地域 endpoint。
     *
     * @return array{access_key:string,secret_key:string,bucket:string,domain:string,endpoint:string}
     * @throws \Exception
     */
    private static function storageConfig(): array
    {
        $config = ConfigService::get('storage', 'aliyun', []);
        if (!is_array($config) || empty($config['access_key']) || empty($config['secret_key'])) {
            throw new \Exception('站点未配置阿里云存储，无法上传人脸素材');
        }
        $location = trim((string)($config['Location'] ?? ''));
        if ($location === '') {
            $location = 'oss-cn-beijing';
        }
        $domain = (string)($config['domain'] ?? '');
        if ($domain !== '' && !str_starts_with($domain, 'http')) {
            $domain = 'https://' . $domain;
        }
        return [
            'access_key' => (string)$config['access_key'],
            'secret_key' => (string)$config['secret_key'],
            'bucket'     => (string)($config['bucket'] ?? ''),
            'domain'     => $domain,
            'endpoint'   => 'https://' . $location . '.aliyuncs.com',
        ];
    }
}

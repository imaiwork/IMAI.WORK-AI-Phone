<?php

namespace app\common\service;

use app\common\model\shanjian\ShanjianClipTemplate;
use app\common\service\storage\Driver as StorageDriver;
use Exception;
use think\facade\Db;

class ShanjianClipTemplateSyncService
{
    private const ALLOWED_SCENES = [
        'virtualman',
        'oralMixCutting',
        'newsMixCutting',
        'realMan',
    ];

    public static function syncFromPlatform(): array
    {
        $response = ToolsService::Shanjian()->clipTemplate([]);
        if ((int)($response['code'] ?? 0) !== 10000) {
            throw new Exception($response['message'] ?? '中台闪剪剪辑模板返回异常');
        }

        $templates = $response['data'] ?? [];
        if (!is_array($templates)) {
            throw new Exception('中台闪剪剪辑模板数据格式异常');
        }

        return self::syncFromPayload($templates);
    }

    public static function syncFromPayload(array $templates): array
    {
        $rows = [];
        $stats = [
            'created' => 0,
            'updated' => 0,
            'deleted' => 0,
            'downloaded' => 0,
            'uploaded' => 0,
            'reused' => 0,
            'skipped' => 0,
            'total' => 0,
        ];

        foreach ($templates as $item) {
            if (!is_array($item)) {
                $stats['skipped']++;
                continue;
            }

            $row = self::normalizeTemplate($item, $stats);
            if ($row === null) {
                $stats['skipped']++;
                continue;
            }

            $rows[$row['id']] = $row;
        }

        if (empty($rows)) {
            throw new Exception('剪辑模板为空，已跳过清洗，避免误删本地数据');
        }

        Db::startTrans();
        try {
            $remoteIds = array_keys($rows);
            $existingIds = ShanjianClipTemplate::whereIn('id', $remoteIds)->column('id');
            $existingMap = array_fill_keys(array_map('strval', $existingIds), true);

            foreach ($rows as $id => $row) {
                if (isset($existingMap[$id])) {
                    ShanjianClipTemplate::where('id', $id)->update($row);
                    $stats['updated']++;
                    continue;
                }

                ShanjianClipTemplate::create($row);
                $stats['created']++;
            }

            $stats['deleted'] = ShanjianClipTemplate::whereNotIn('id', $remoteIds)->delete();
            $stats['total'] = count($rows);

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }

        return $stats;
    }

    private static function normalizeTemplate(array $item, array &$stats): ?array
    {
        $id = trim((string)($item['id'] ?? ''));
        $name = trim((string)($item['name'] ?? ''));
        $scene = trim((string)($item['scene'] ?? ''));

        if ($id === '' || $name === '' || $scene === '' || !in_array($scene, self::ALLOWED_SCENES, true)) {
            return null;
        }

        return [
            'id' => $id,
            'name' => $name,
            'cover_url' => self::downloadTemplateAsset(trim((string)($item['cover_url'] ?? '')), 'image', $stats),
            'scene' => $scene,
            'demo_url' => self::downloadTemplateAsset(trim((string)($item['demo_url'] ?? '')), 'video', $stats),
            'auto_type' => (int)($item['auto_type'] ?? 0),
            'create_time' => max(0, (int)($item['create_time'] ?? time())),
            'update_time' => max(0, (int)($item['update_time'] ?? time())),
        ];
    }

    private static function downloadTemplateAsset(string $url, string $type, array &$stats): string
    {
        if ($url === '' || !preg_match('/^https?:\/\//i', $url)) {
            return $url;
        }

        $extension = self::resolveExtension($url, $type);
        $typePath = $type === 'video' ? 'videos' : 'images';
        $relativeDir = 'uploads/clip_template/' . $typePath;
        $filename = sha1($url) . '.' . $extension;
        $relativePath = $relativeDir . '/' . $filename;
        $absoluteDir = rtrim(public_path($relativeDir), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $absolutePath = $absoluteDir . $filename;
        $storageDefault = self::getStorageDefault();

        if ($storageDefault !== 'local' && self::remoteAssetExists($relativePath)) {
            $stats['reused']++;
            return $relativePath;
        }

        if ($storageDefault === 'local' && is_file($absolutePath) && filesize($absolutePath) > 0) {
            $stats['reused']++;
            return $relativePath;
        }

        if (!is_file($absolutePath) || filesize($absolutePath) <= 0) {
            if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
                throw new Exception('无法创建闪剪模板资源目录: ' . $absoluteDir);
            }

            $content = self::fetchRemoteContentWithRetry($url);
            if ($content === '') {
                throw new Exception('闪剪模板资源下载失败: ' . $url);
            }

            if (file_put_contents($absolutePath, $content) === false || !is_file($absolutePath) || filesize($absolutePath) <= 0) {
                @unlink($absolutePath);
                throw new Exception('闪剪模板资源保存失败: ' . $url);
            }

            $stats['downloaded']++;
        }

        if ($storageDefault !== 'local') {
            self::uploadAssetToStorage($absolutePath, $relativeDir, $filename, $relativePath);
            $stats['uploaded']++;
            @unlink($absolutePath);
        }

        return $relativePath;
    }

    private static function getStorageDefault(): string
    {
        return (string)(ConfigService::get('storage', 'default', 'local') ?: 'local');
    }

    private static function makeStorageDriver(): StorageDriver
    {
        return new StorageDriver([
            'default' => self::getStorageDefault(),
            'engine' => ConfigService::get('storage') ?? ['local' => []],
        ]);
    }

    private static function uploadAssetToStorage(string $absolutePath, string $relativeDir, string $filename, string $relativePath): void
    {
        $storage = self::makeStorageDriver();
        $storage->setUploadFileByFileName($absolutePath, $filename);
        if (!$storage->upload($relativeDir)) {
            throw new Exception('闪剪模板资源上传存储失败: ' . $relativePath . ' error=' . $storage->getError());
        }
    }

    private static function remoteAssetExists(string $relativePath): bool
    {
        $url = FileService::getFileUrl($relativePath, '', true);
        if (!preg_match('/^https?:\/\//i', $url)) {
            return false;
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_exec($ch);
        $statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $statusCode >= 200 && $statusCode < 400;
    }

    private static function resolveExtension(string $url, string $type): string
    {
        $path = (string)(parse_url($url, PHP_URL_PATH) ?? '');
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $extension = preg_replace('/[^a-z0-9]/', '', $extension) ?: '';

        $allowed = $type === 'video'
            ? ['mp4', 'mov']
            : ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        if (!in_array($extension, $allowed, true)) {
            return $type === 'video' ? 'mp4' : 'jpg';
        }

        return $extension;
    }

    private static function fetchRemoteContentWithRetry(string $url): string
    {
        $maxAttempts = 3;
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $content = self::fetchRemoteContent($url);
            if ($content !== '') {
                return $content;
            }

            if ($attempt < $maxAttempts) {
                usleep(300000);
            }
        }

        return '';
    }

    private static function fetchRemoteContent(string $url): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $content = curl_exec($ch);
        $statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($content === false || ($statusCode >= 400 && $statusCode !== 0)) {
            return '';
        }

        return (string)$content;
    }
}

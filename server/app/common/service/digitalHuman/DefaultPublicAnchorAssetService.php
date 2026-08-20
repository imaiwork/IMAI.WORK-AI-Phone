<?php

namespace app\common\service\digitalHuman;

use app\common\service\ConfigService;
use app\common\service\storage\Driver as StorageDriver;
use Exception;
use think\facade\Log;

class DefaultPublicAnchorAssetService
{
    public static function getTemplateFiles(): array
    {
        $config = DefaultPublicAnchorConfig::get();
        return $config['files'] ?? [];
    }

    public static function resolveLocalPath(string $relativeUri): string
    {
        $relativeUri = ltrim(str_replace('\\', '/', $relativeUri), '/');
        return public_path() . str_replace('/', DIRECTORY_SEPARATOR, $relativeUri);
    }

    public static function isSynced(): bool
    {
        return (int)ConfigService::get('default_public_anchor', 'assets_synced', 0) === 1;
    }

    /**
     * @return array{synced:int, skipped:int, failed:array<int, array{file:string, error:string}>}
     */
    public static function syncToStorage(bool $force = false): array
    {
        if (!$force && self::isSynced()) {
            return ['synced' => 0, 'skipped' => count(self::getTemplateFiles()), 'failed' => []];
        }

        $files = self::getTemplateFiles();
        $synced = 0;
        $skipped = 0;
        $failed = [];

        $storageDefault = ConfigService::get('storage', 'default', 'local');
        $storageConfig = [
            'default' => $storageDefault,
            'engine'  => ConfigService::get('storage') ?? ['local' => []],
        ];

        foreach ($files as $relativeUri) {
            $relativeUri = ltrim(str_replace('\\', '/', (string)$relativeUri), '/');
            if ($relativeUri === '') {
                continue;
            }

            try {
                $localPath = self::resolveLocalPath($relativeUri);
                if (!is_file($localPath)) {
                    throw new Exception('母版文件不存在: ' . $localPath);
                }

                if ($storageDefault === 'local') {
                    // 母版文件已在 public 目录，本地存储无需重复复制
                    $synced++;
                    continue;
                }

                self::uploadToRemoteStorage($storageConfig, $localPath, $relativeUri);
                $synced++;
            } catch (Exception $e) {
                $failed[] = ['file' => $relativeUri, 'error' => $e->getMessage()];
                Log::channel('digital')->error('默认公共形象母版同步失败: ' . $relativeUri . ' - ' . $e->getMessage());
            }
        }

        if ($failed === []) {
            ConfigService::set('default_public_anchor', 'assets_synced', 1);
            ConfigService::set('default_public_anchor', 'assets_synced_time', time());
        }

        return ['synced' => $synced, 'skipped' => $skipped, 'failed' => $failed];
    }

    private static function uploadToRemoteStorage(array $storageConfig, string $localPath, string $relativeUri): void
    {
        $filename = basename($relativeUri);
        $saveDir = dirname($relativeUri);
        if ($saveDir === '.' || $saveDir === '\\') {
            $saveDir = '';
        }

        $storageDriver = new StorageDriver($storageConfig);
        $storageDriver->setUploadFileByFileName($localPath, $filename);
        if (!$storageDriver->upload($saveDir)) {
            throw new Exception($storageDriver->getError() ?: '上传失败');
        }
    }
}

<?php

declare(strict_types=1);

namespace app\common\service\draw;

use app\common\enum\draw\DrawEnum;
use app\common\model\draw\DrawAsset;
use app\common\model\draw\DrawTask;
use app\common\service\ConfigService;
use app\common\service\FileService;
use app\common\service\storage\Driver as StorageDriver;
use app\common\service\VideoInfoService;
use think\facade\Log;

/**
 * 产物落盘：URL 下载 / base64 落盘；视频同步截封面
 */
class DrawAssetService
{
    /**
     * @param array<int, array{kind:string,value:string,mime?:string}|string> $sources
     */
    public function materialize(DrawTask $task, array $sources): void
    {
        $storage = (string)ConfigService::get('storage', 'default', 'local');
        $sort = 0;

        foreach ($sources as $source) {
            if (is_string($source)) {
                $source = ['kind' => 'url', 'value' => $source];
            }
            if (!is_array($source)) {
                continue;
            }

            $kind = (string)($source['kind'] ?? 'url');
            $value = trim((string)($source['value'] ?? ''));
            if ($value === '') {
                continue;
            }

            $downloadType = $task->media_type === DrawEnum::MEDIA_VIDEO ? 'video' : 'image';
            if ($kind === 'b64') {
                $fileUrl = $this->saveBase64Image($value, (string)($source['mime'] ?? 'image/png'), $storage);
                $sourceUrl = '';
            } else {
                $fileUrl = FileService::downloadFileBySource($value, $downloadType);
                $sourceUrl = mb_substr($value, 0, 2000);
            }

            if ($fileUrl === '') {
                throw new \Exception('产物落盘失败：空文件路径');
            }

            $asset = DrawAsset::create([
                'task_id'    => $task->id,
                'asset_type' => $task->media_type === DrawEnum::MEDIA_VIDEO
                    ? DrawEnum::ASSET_VIDEO
                    : DrawEnum::ASSET_IMAGE,
                'source_url' => $sourceUrl,
                'file_url'   => $fileUrl,
                'storage'    => $storage,
                'sort'       => $sort++,
                'extra'      => [
                    'source_kind' => $kind,
                    'mime'        => $source['mime'] ?? null,
                ],
            ]);

            if ($task->media_type === DrawEnum::MEDIA_VIDEO) {
                $this->tryCreateCover($task, $asset);
            }
        }

        $task->asset_count = DrawAsset::where('task_id', $task->id)->count();
        $task->save();
    }

    /**
     * 将 base64 / data URI 图片落盘，返回相对路径。
     * 站点配了第三方存储时与 URL 产物（downloadFileBySource）保持一致：上传后删掉本地副本，
     * 否则 getFileUrl 本地优先会一直拼主站 app_host 域名，OEM 域名下前端跨域拉图会被 CORS 拦下。
     * 上传失败则保留本地文件，不让任务因此失败。
     */
    private function saveBase64Image(string $raw, string $mime = 'image/png', string $storage = 'local'): string
    {
        if (str_starts_with($raw, 'data:')) {
            if (!preg_match('#^data:([^;]+);base64,(.+)$#s', $raw, $m)) {
                throw new \Exception('非法 data URI');
            }
            $mime = $m[1] !== '' ? $m[1] : $mime;
            $raw = $m[2];
        }

        $binary = base64_decode($raw, true);
        if ($binary === false || $binary === '') {
            throw new \Exception('base64 解码失败');
        }

        $ext = match (strtolower($mime)) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'png',
        };

        $relativeDir = 'uploads/images/' . date('Ymd');
        $filename = date('YmdHis') . md5(uniqid((string)mt_rand(), true)) . '.' . $ext;
        $relativePath = $relativeDir . '/' . $filename;

        $directory = public_path($relativeDir);
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new \Exception('创建图片目录失败');
        }
        $absolute = rtrim((string)$directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
        if (file_put_contents($absolute, $binary) === false) {
            throw new \Exception('写入图片失败');
        }

        if ($storage === '' || $storage === 'local') {
            return $relativePath;
        }

        try {
            $driver = new StorageDriver([
                'default' => $storage,
                'engine'  => ConfigService::get('storage'),
            ]);
            $driver->setUploadFileByReal($absolute);
            $savedName = $driver->upload($relativeDir) ? (string)$driver->getFileName() : '';
            if ($savedName !== '') {
                @unlink($absolute);
                return $relativeDir . '/' . $savedName;
            }
            Log::warning('draw b64 asset upload failed, keep local copy: ' . (string)$driver->getError(), [
                'storage' => $storage,
                'path'    => $relativePath,
            ]);
        } catch (\Throwable $e) {
            Log::warning('draw b64 asset upload failed, keep local copy: ' . $e->getMessage(), [
                'storage' => $storage,
                'path'    => $relativePath,
            ]);
        }

        return $relativePath;
    }

    public function tryCreateCover(DrawTask $task, ?DrawAsset $videoAsset = null): bool
    {
        $exists = DrawAsset::where('task_id', $task->id)
            ->where('asset_type', DrawEnum::ASSET_COVER)
            ->where('file_url', '<>', '')
            ->find();
        if ($exists) {
            return true;
        }

        if (!$videoAsset) {
            $videoAsset = DrawAsset::where('task_id', $task->id)
                ->where('asset_type', DrawEnum::ASSET_VIDEO)
                ->where('file_url', '<>', '')
                ->order('sort', 'asc')
                ->find();
        }
        if (!$videoAsset || $videoAsset->file_url === '') {
            return false;
        }

        try {
            $service = new VideoInfoService();
            $result = $service->generateThumbnail($videoAsset->file_url, 1.0, [
                'format' => 'jpg',
            ]);
            if (empty($result['url'])) {
                return false;
            }

            $storage = (string)($result['storage'] ?? ConfigService::get('storage', 'default', 'local'));
            DrawAsset::create([
                'task_id'    => $task->id,
                'asset_type' => DrawEnum::ASSET_COVER,
                'source_url' => '',
                'file_url'   => $result['url'],
                'storage'    => $storage,
                'sort'       => 0,
                'extra'      => [
                    'from_video_asset_id' => $videoAsset->id,
                    'size'                => $result['size'] ?? 0,
                ],
            ]);

            $task->asset_count = DrawAsset::where('task_id', $task->id)->count();
            $task->save();

            return true;
        } catch (\Throwable $e) {
            Log::warning('draw cover generate failed: ' . $e->getMessage(), [
                'task_no' => $task->task_no,
                'video'   => $videoAsset->file_url,
            ]);
            return false;
        }
    }
}

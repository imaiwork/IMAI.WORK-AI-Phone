<?php

declare(strict_types=1);

namespace app\common\workerman\rpa\Tool;

use app\common\service\ConfigService;
use app\common\service\FileService;
use app\common\service\storage\Driver as StorageDriver;
use think\facade\Config;
use think\facade\Log;

class ToolUtil
{
    public function replaceImageInfo(string $data): string
    {
        $payload = json_decode($data, true);
        if (!is_array($payload) || !isset($payload['content'])) {
            return $data;
        }

        $content = is_array($payload['content']) ? $payload['content'] : json_decode((string)$payload['content'], true);
        if (!is_array($content)) {
            return $data;
        }
        if (isset($content['image'])) {
            $content['image'] = '图片内容';
        }
        if (isset($content['avatar'])) {
            $content['avatar'] = '头像内容';
        }
        $payload['content'] = $content;

        return json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    public function base64ToImage(array $item): string
    {
        if (!trim($item['avatar'])) {
            return '';
        }
        // 分离Base64头和数据
        $data = explode(',', $item['avatar']);
        // 解码Base64数据
        $decoded = base64_decode($data[1] ?? $data[0]);
        $code = $item['xhsId'] ?? $item['authorName'];
        $output = 'uploads/images/xhs/xhs_' . $code . '.png';
        $root_path = public_path();
        // 创建目录（如果不存在）
        if (!is_dir(dirname($root_path . $output))) {
            mkdir(dirname($root_path . $output), 0777, true);
        }

        // 保存文件
        if (file_put_contents($root_path . $output, $decoded)) {
            if ($this->maybeUploadLocalImageToOss($output)) {
                return FileService::getFileUrl($output);
            }
            return Config::get('app.app_host') . '/' . $output;
        }
        return '';
    }

    public function saveBase64ToImage(string $content, string $code, string $type = 'ai'): string
    {
        if (!trim($content)) {
            return '';
        }
        // 分离Base64头和数据
        $data = explode(',', $content);
        // 解码Base64数据
        $decoded = base64_decode($data[1] ?? $data[0]);
        $date = date('Ymd');
        $output = 'uploads/images/' . $type . '/' . $date . '/' . $code . '.png';
        $root_path = public_path();
        // 创建目录（如果不存在）
        if (!is_dir(dirname($root_path . $output))) {
            mkdir(dirname($root_path . $output), 0777, true);
        }

        // 保存文件
        if (file_put_contents($root_path . $output, $decoded)) {
            $this->maybeUploadLocalImageToOss($output);
            return '/' . $output;
        }
        return '';
    }

    public function sendNotification(int $user_id, string $content, string $status): void
    {
        // 发送通知逻辑
        \app\api\logic\ApiLogic::sendNotice([
            'userId' => $user_id,
            'content' => $content,
            'time' => date('Y-m-d H:i:s', time()),
            'status' => $status,
        ], 403);
    }

    /**
     * 若开启非本地存储，将本地图片上传到 OSS；成功后删除本地文件。
     *
     * @param string $relativePath 相对 public 的路径，无前导斜杠
     * @return bool true=已上传 OSS 并删除本地；false=未开启 OSS 或上传失败（降级保留本地）
     */
    private function maybeUploadLocalImageToOss(string $relativePath): bool
    {
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
        if ($relativePath === '') {
            return false;
        }

        $storageDefault = (string)ConfigService::get('storage', 'default', 'local');
        if ($storageDefault === 'local') {
            return false;
        }

        $localAbs = $this->buildLocalAbsolutePath($relativePath);
        if (!is_file($localAbs)) {
            Log::write('RPA图片上传OSS失败：本地文件不存在 path=' . $relativePath, 'error');
            return false;
        }

        try {
            $storageConfig = [
                'default' => $storageDefault,
                'engine' => ConfigService::get('storage') ?? ['local' => []],
            ];
            $filename = basename($relativePath);
            $saveDir = dirname($relativePath);
            if ($saveDir === '.' || $saveDir === '\\') {
                $saveDir = '';
            }

            $storageDriver = new StorageDriver($storageConfig);
            $storageDriver->setUploadFileByFileName($localAbs, $filename);
            if (!$storageDriver->upload($saveDir)) {
                Log::write(
                    'RPA图片上传OSS失败：' . ($storageDriver->getError() ?: '上传失败') . ' path=' . $relativePath,
                    'error'
                );
                return false;
            }

            @unlink($localAbs);
            return true;
        } catch (\Throwable $th) {
            Log::write(
                'RPA图片上传OSS异常：' . $th->getMessage() . ' path=' . $relativePath,
                'error'
            );
            return false;
        }
    }

    private function buildLocalAbsolutePath(string $relativePath): string
    {
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
        return rtrim(public_path(), '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    }
}

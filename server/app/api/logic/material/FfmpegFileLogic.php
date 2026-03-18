<?php

namespace app\api\logic\material;

use app\api\logic\ApiLogic;
use app\common\model\material\FfmpegFile;
use app\common\service\ConfigService;
use app\common\service\FileService;
use think\facade\Log;

class FfmpegFileLogic extends ApiLogic
{
    public static function addFfmpegFile(array $params)
    {
        try {
            $user_id = $params['user_id'] ?? self::$uid;
            $uri = FileService::setFileUrl($params['uri']);
            $data    = [
                'file_id'     => $params['file_id'] ?? 0,
                'user_id'     => $user_id,
                'type'        => $params['type'] ?? 20,
                'status'      => $params['status'] ?? 0,
                'name'        => $params['name'] ?? '无',
                'tries'       => $params['tries'] ?? 0,
                'remark'      => $params['remark'] ?? '',
                'uri'         => $uri,
                'create_time' => time(),
            ];
            $ffmpegFile = FfmpegFile::create($data);
            self::$returnData = $ffmpegFile->refresh()->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function updateFfmpegFile(array $params)
    {
        try {
            $ffmpegFile = FfmpegFile::where('id', $params['id'])
                ->where('user_id', self::$uid)
                ->findOrEmpty();

            if ($ffmpegFile->isEmpty()) {
                self::setError('FFmpeg文件不存在');
                return false;
            }

            FfmpegFile::update($params);
            self::$returnData = FfmpegFile::where('id', $params['id'])->findOrEmpty()->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function deleteFfmpegFile($id)
    {
        try {
            if (is_string($id)) {
                FfmpegFile::destroy(['id' => $id, 'user_id' => self::$uid]);
            } else {
                FfmpegFile::whereIn('id', $id)->where('user_id', self::$uid)->select()->delete();
            }
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function getFfmpegFile(array $params)
    {
        $ffmpegFile = FfmpegFile::where('id', $params['id'])
            ->where('user_id', self::$uid)
            ->findOrEmpty();

        if ($ffmpegFile->isEmpty()) {
            self::setError('FFmpeg文件不存在');
            return false;
        }

        $data = $ffmpegFile->toArray();
        $data['type_text'] = FfmpegFile::getTypeText($data['type']);
        $data['status_text'] = FfmpegFile::getStatusText($data['status']);
        self::$returnData = $data;
        return true;
    }


    /**
     * 定时任务：批量处理FFmpeg文件
     * 使用UploadService进行文件下载、处理和上传
     */
    public static function taskCron()
    {
        try {
            // 查询待处理的任务
            $tasks = FfmpegFile::where('status', 0)
                ->where('tries', '<', 5)
                ->limit(5)
                ->order('tries DESC, id ASC')
                ->select()
                ->toArray();

            $processedCount = 0;
            $results = [];

            foreach ($tasks as $task) {
                $result = [
                    'id' => $task['id'],
                    'file_id' => $task['file_id'],
                    'original_uri' => $task['uri'],
                    'status' => $task['status'],
                    'tries' => $task['tries'],
                    'success' => false,
                    'message' => '',
                    'processed_at' => date('Y-m-d H:i:s')
                ];

                $tempFilePath = null;
                $processedFilePath = null;

                try {
                    // 更新状态为处理中
                    FfmpegFile::where('id', $task['id'])->update([
                        'status' => 1,
                        'remark' => '开始处理...'
                    ]);

                    // 验证输入参数
                    if (empty($task['uri'])) {
                        throw new \Exception("文件URI为空");
                    }
                    $command = 'ffmpeg6 -version';
                    $output = shell_exec($command);
                    $mediaInfo = $finalUrl = '';
                    if ($output !== null && (strpos($output, 'ffmpeg version') !== false || strpos($output, 'ffmpeg6 version') !== false)) {
                        $host = config('app.app_host');
                        $url = FileService::getFileUrl($task['uri']);
                        $is_local = strpos($url, $host) === 0;
                        if (!$is_local) {
                            $tempFilePath = \app\common\service\UploadService::downloadRemoteFile($url);
                        } else {
                            $tempFilePath = $task['uri'];
                        }
                        // 步骤1：下载远程文件到本地临时目录
                        // 步骤2：获取媒体文件信息
                        $mediaInfo = \app\common\service\UploadService::getMediaInfo($tempFilePath);
                        // 步骤3：使用UploadService进行标准化处理
                        $processedFilePath = \app\common\service\UploadService::standardizeMedia($tempFilePath);
                        // 步骤4：上传处理后的文件到云存储
                        $finalUrl = FileService::getFileUrl($processedFilePath);
                        $updateData = [
                            'status' => 2, // 成功
                            'tries' => $task['tries'] + 1,
                            'remark' => '处理成功 - ' . json_encode([
                                'processed_at' => date('Y-m-d H:i:s')
                            ]),
                        ];
                    } else {
                        $updateData = [
                            'status' => 0,
                            'tries' => $task['tries'] + 1,
                            'remark' => '失败 - ' . json_encode([
                                'processed_at' => date('Y-m-d H:i:s')
                            ]),
                        ];
                    }



                    FfmpegFile::where('id', $task['id'])->update($updateData);

                    $result['success'] = true;
                    $result['message'] = '处理成功';
                    $result['output_url'] = $finalUrl;
                    $result['media_info'] = $mediaInfo;
                    $processedCount++;
                } catch (\Exception $e) {
                    // 更新失败次数和状态
                    $newTries = $task['tries'] + 1;
                    $status = $newTries >= 5 ? 3 : 0; // 达到3次后标记为失败

                    FfmpegFile::where('id', $task['id'])->update([
                        'tries' => $newTries,
                        'status' => $status,
                        'remark' => '处理失败: ' . $e->getMessage()
                    ]);

                    $result['success'] = false;
                    $result['message'] = $e->getMessage();
                    $result['error_code'] = $e->getCode();
                } finally {
                    $default = ConfigService::get('storage', 'default', 'local');
                    if ($default != 'local') {
                        // 清理临时文件
                        if ($tempFilePath && file_exists($tempFilePath)) {
                            unlink($tempFilePath);
                        }
                        if ($processedFilePath && file_exists($processedFilePath)) {
                            unlink($processedFilePath);
                        }
                    }
                }

                $results[] = $result;
            }

            // 返回处理结果
            self::$returnData = [
                'processed_count' => $processedCount,
                'total_tasks' => count($tasks),
                'success_rate' => count($tasks) > 0 ? round($processedCount / count($tasks) * 100, 2) : 0,
                'results' => $results,
                'execution_time' => date('Y-m-d H:i:s')
            ];

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}

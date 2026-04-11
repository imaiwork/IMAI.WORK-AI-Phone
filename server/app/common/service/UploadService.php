<?php

namespace app\common\service;

use app\common\enum\FileEnum;
use app\common\Jobs\MediaTranscodeJob;
use app\common\model\audio\Audio;
use app\common\model\file\File;
use app\common\service\storage\Driver as StorageDriver;
use app\common\service\VideoInfoService; // ✅ 新增
use Exception;
use think\facade\Log;
use think\facade\Queue;


/**
 * 文件上传服务类（优化版）
 * Class UploadService
 * @package app\common\service
 */
class UploadService
{
    // ==================== 常量定义 ====================

    // 视频默认规范配置
    const DEFAULT_VIDEO_SPECS = [
        'format' => 'mp4',
        'video_codec' => 'h264',
        'audio_codec' => 'aac',
        'resolution' => null,
        'frame_rate' => 30,
        'bit_rate' => '4M',
        'pixel_format' => 'yuv420p',
        'duration' => 60,
        'max_dimension' => 2000,
        'target_dimension' => 1920,
    ];

    // 图片默认规范配置
    const DEFAULT_IMAGE_SPECS = [
        'format' => ['jpg', 'png'],
        'resolution' => null,
        'color_space' => 'sRGB',
        'max_dimension' => 2000,
        'target_dimension' => 1920,
    ];

    // 缩略图默认配置
    const DEFAULT_THUMBNAIL_OPTIONS = [
        'width' => 640,
        'quality' => 2,
        'format' => 'jpg',
        'time' => 1.0
    ];

    // ==================== 公共上传方法 ====================

    /**
     * 上传图片
     *
     * @param int $cid 分类ID
     * @param int $sourceId 来源ID
     * @param int $source 来源类型
     * @param string $saveDir 保存目录
     * @param int $ffmpeg 是否使用FFmpeg处理
     * @return array
     * @throws Exception
     */
    public static function image(
        int $cid = 0,
        int $sourceId = 0,
        int $source = FileEnum::SOURCE_ADMIN,
        string $saveDir = 'uploads/images',
        int $ffmpeg = 0
    ): array {
        return self::executeUpload([
            'cid' => $cid,
            'sourceId' => $sourceId,
            'source' => $source,
            'saveDir' => $saveDir,
            'fileType' => FileEnum::IMAGE_TYPE,
            'allowedExts' => config('project.file_image'),
            'ffmpeg' => $ffmpeg,
            'generateThumbnail' => false,
        ]);
    }

    /**
     * 上传视频
     *
     * @param int $cid 分类ID
     * @param int $sourceId 来源ID
     * @param int $source 来源类型
     * @param string $saveDir 保存目录
     * @param int $ffmpeg 是否使用FFmpeg处理
     * @param array $clip 裁剪参数
     * @param bool $generateThumbnail 是否生成缩略图
     * @param array $thumbnailOptions 缩略图配置
     * @return array
     * @throws Exception
     */
    public static function video(
        int $cid = 0,
        int $sourceId = 0,
        int $source = FileEnum::SOURCE_ADMIN,
        string $saveDir = 'uploads/video',
        int $ffmpeg = 0,
        array $clip = [],
        bool $generateThumbnail = false,
        array $thumbnailOptions = [],
        bool $fetchVideoInfo = true,
    ): array {
        return self::executeUpload([
            'cid' => $cid,
            'sourceId' => $sourceId,
            'source' => $source,
            'saveDir' => $saveDir,
            'fileType' => FileEnum::VIDEO_TYPE,
            'allowedExts' => config('project.file_video'),
            'ffmpeg' => $ffmpeg,
            'clip' => $clip,
            'generateThumbnail' => $generateThumbnail,
            'thumbnailOptions' => $thumbnailOptions,
            'autoDetectVideo' => true, // 自动检测是否为视频
            'fetchVideoInfo' => $fetchVideoInfo,
        ]);
    }

    /**
     * 上传音频
     *
     * @param int $cid 分类ID
     * @param int $sourceId 来源ID
     * @param int $source 来源类型
     * @param string $saveDir 保存目录
     * @param bool $isDate 是否使用日期目录
     * @return array
     * @throws Exception
     */
    public static function audio(
        int $cid = 0,
        int $sourceId = 0,
        int $source = FileEnum::SOURCE_ADMIN,
        string $saveDir = 'uploads/audio',
        bool $isDate = true
    ): array {
        $result = self::executeUpload([
            'cid' => $cid,
            'sourceId' => $sourceId,
            'source' => $source,
            'saveDir' => $saveDir,
            'fileType' => FileEnum::AUDIO_TYPE,
            'allowedExts' => config('project.file_audio'),
            'ffmpeg' => 0,
            'isDate' => $isDate,
            'generateThumbnail' => false,
        ]);

        // 音频特殊处理：创建Audio记录
        try {
            $audio = Audio::create([
                'user_id' => $sourceId,
                'file_id' => $result['id'],
                'file_name' => $result['name'],
                'file_path' => $result['url'],
                'create_time' => time(),
            ]);
            $result['audio_id'] = $audio['id'];
        } catch (Exception $e) {
            Log::error('创建音频记录失败', [
                'file_id' => $result['id'],
                'error' => $e->getMessage()
            ]);
        }

        return $result;
    }

    /**
     * 上传文件（通用）
     *
     * @param int $cid 分类ID
     * @param int $sourceId 来源ID
     * @param int $source 来源类型
     * @param string $saveDir 保存目录
     * @param int $ffmpeg 是否使用FFmpeg处理
     * @param array $clip 裁剪参数
     * @param bool $generateThumbnail 是否生成缩略图（仅视频）
     * @param array $thumbnailOptions 缩略图配置
     * @return array
     * @throws Exception
     */
    public static function file(
        int $cid = 0,
        int $sourceId = 0,
        int $source = FileEnum::SOURCE_ADMIN,
        string $saveDir = 'uploads/file',
        int $ffmpeg = 0,
        array $clip = [],
        bool $generateThumbnail = false,
        array $thumbnailOptions = [],
        bool $fetchVideoInfo = true,
    ): array {
        return self::executeUpload([
            'cid' => $cid,
            'sourceId' => $sourceId,
            'source' => $source,
            'saveDir' => $saveDir,
            'fileType' => FileEnum::FILE_TYPE,
            'allowedExts' => null, // 不限制扩展名
            'ffmpeg' => $ffmpeg,
            'clip' => $clip,
            'generateThumbnail' => $generateThumbnail,
            'thumbnailOptions' => $thumbnailOptions,
            'autoDetectVideo' => true, // 自动检测是否为视频
            'fetchVideoInfo' => $fetchVideoInfo,
        ]);
    }

    /**
     * 上传CSV文件
     *
     * @param int $cid 分类ID
     * @param int $sourceId 来源ID
     * @param int $source 来源类型
     * @param string $saveDir 保存目录
     * @return array
     * @throws Exception
     */
    public static function csvFile(
        int $cid = 0,
        int $sourceId = 0,
        int $source = FileEnum::SOURCE_ADMIN,
        string $saveDir = 'uploads/file'
    ): array {
        return self::executeUpload([
            'cid' => $cid,
            'sourceId' => $sourceId,
            'source' => $source,
            'saveDir' => $saveDir,
            'fileType' => FileEnum::CSV_TYPE,
            'allowedExts' => config('project.csv_file'),
            'ffmpeg' => 0,
            'generateThumbnail' => false,
        ]);
    }

    /**
     * 上传Base64截图
     *
     * @param array $params 参数
     * @return array
     */
    public static function screenshot(array $params): array
    {
        try {
            // 参数验证
            $content = $params['content'] ?? '';
            $type = $params['type'] ?? 'ai';
            $code = $params['code'] ?? generate_unique_task_id();

            if (empty(trim($content))) {
                return ['code' => 400, 'msg' => '图片内容不能为空'];
            }

            // 分离Base64头和数据
            $data = explode(',', $content);
            $base64Data = $data[1] ?? $data[0];
            // 解码Base64数据
            $decoded = base64_decode($base64Data);
            if ($decoded === false) {
                return [
                    'code' => 400,
                    'msg' => '图片解码失败',
                ];
            }

            // 生成保存路径
            $date = date('Ymd');
            $relativePath = "uploads/images/{$type}/{$date}/{$code}.png";
            $fullPath = public_path() . $relativePath;

            // 创建目录
            $dir = dirname($fullPath);
            if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
                return ['code' => 400, 'msg' => '创建目录失败'];
            }

            // 保存文件
            if (file_put_contents($fullPath, $decoded) === false) {
                return ['code' => 400, 'msg' => '文件保存失败'];
            }

            Log::info('截图上传成功', [
                'type' => $type,
                'path' => $relativePath,
                'size' => strlen($decoded)
            ]);

            return [
                'code' => 200,
                'msg' => '上传成功',
                'url' => '/' . $relativePath,
            ];
        } catch (Exception $e) {
            Log::error('截图上传失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'code' => 400,
                'msg' => $e->getMessage(),
            ];
        }
    }

    /**
     * 微信上传（特殊处理AMR格式）
     *
     * @param int $cid 分类ID
     * @param int $sourceId 来源ID
     * @param int $source 来源类型
     * @param string $saveDir 保存目录
     * @return array
     */
    public static function wechatUpload(
        int $cid = 0,
        int $sourceId = 0,
        int $source = FileEnum::SOURCE_WECHAT,
        string $saveDir = 'uploads/file'
    ): array {
        try {
            $config = self::getStorageConfig();

            // 执行文件上传
            $storageDriver = new StorageDriver($config);
            $storageDriver->setUploadFile('myfile');
            $fileName = $storageDriver->getFileName();
            $fileInfo = $storageDriver->getFileInfo();

            // 校验文件类型
            $allowedExts = config('project.file_file');
            if (!in_array(strtolower($fileInfo['ext']), $allowedExts)) {
                throw new Exception("不允许上传{$fileInfo['ext']}文件");
            }

            // AMR格式转换
            if (strtolower($fileInfo['ext']) === 'amr') {
                $convertResult = self::convertAmrToMp3($fileInfo, $storageDriver);
                if ($convertResult) {
                    $fileName = $convertResult['fileName'];
                }
            }

            // 上传文件
            $saveDir = self::getUploadUrl($saveDir);
            if (!$storageDriver->upload($saveDir)) {
                throw new Exception($storageDriver->getError());
            }

            // 处理文件名
            $fileInfo['name'] = self::truncateFileName($fileInfo['name']);

            // 保存到数据库
            $file = File::create([
                'cid' => $cid,
                'type' => FileEnum::FILE_TYPE,
                'name' => $fileInfo['name'],
                'uri' => $saveDir . '/' . str_replace("\\", "/", $fileName),
                'source' => $source,
                'source_id' => $sourceId,
                'create_time' => time(),
            ]);

            $url = FileService::getFileUrl($file['uri']);

            return [
                'bizCode' => 0,
                'data' => [
                    'fileSize' => $fileInfo['size'],
                    'url' => $url
                ],
                'msg' => '上传成功'
            ];
        } catch (Exception $e) {
            Log::error('微信上传失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'bizCode' => 6001,
                'data' => [],
                'msg' => '系统错误',
                'info' => $e->getMessage()
            ];
        }
    }

    // ==================== 核心私有方法 ====================

    /**
     * 统一上传执行逻辑
     *
     * @param array $options 上传选项
     * @return array
     * @throws Exception
     */
    private static function executeUpload(array $options): array
    {
        $tempFiles = [];

        try {
            // 1. 参数解析
            $params = self::parseUploadOptions($options);

            // 2. 初始化存储驱动
            $config = self::getStorageConfig($params['ffmpeg']);
            $storageDriver = new StorageDriver($config);
            $storageDriver->setUploadFile('file');

            // 3. 获取文件信息
            $fileName = $storageDriver->getFileName();
            $fileInfo = $storageDriver->getFileInfo();

            // 4. 验证文件类型
            if ($params['allowedExts']) {
                self::validateFileExtension($fileInfo['ext'], $params['allowedExts']);
            }

            // 5. 计算保存目录和路径
            $saveDir   = self::getUploadUrl($params['saveDir'], $params['isDate']);
            $localPath = rtrim(public_path(), '/')
                . '/'
                . ltrim($saveDir . '/' . str_replace("\\", "/", $fileName), '/');
            $fileUri   = $saveDir . '/' . str_replace("\\", "/", $fileName);

            // 6. 处理文件名
            $fileInfo['name'] = self::truncateFileName($fileInfo['name']);

            // ✅ 7. 在 upload() 之前生成缩略图
            //        此时文件还在本地临时目录，OSS尚未删除本地文件
            $thumbnailResult = null;
            if ($params['generateThumbnail']) {
                $isVideo = self::isVideoFile($fileInfo['ext'], $params['autoDetectVideo'] ?? false);
                if ($isVideo) {
                    // 优先用 getRealPath()，fallback 到 fileInfo 里的临时路径
                    $realPath = null;
                    if (method_exists($storageDriver, 'getRealPath')) {
                        $realPath = $storageDriver->getRealPath();
                    }
                    if (empty($realPath)) {
                        $realPath = $fileInfo['realPath'] ?? $fileInfo['tmp_name'] ?? null;
                    }

                    Log::info('【缩略图】upload前生成，检查临时文件', [
                        'realPath'    => $realPath,
                        'file_exists' => $realPath ? file_exists($realPath) : false,
                    ]);

                    if ($realPath && file_exists($realPath)) {
                        $thumbnailResult = self::handleThumbnail(
                            $realPath,
                            $params['thumbnailOptions']
                        );
                    } else {
                        Log::warning('【缩略图】临时文件不存在，跳过缩略图生成', [
                            'realPath' => $realPath,
                        ]);
                    }
                }
            }

            // 8. 上传文件到OSS/本地（OSS模式会删除本地临时文件）
            if (!$storageDriver->upload($saveDir)) {
                throw new Exception($storageDriver->getError());
            }

            // 9. 保存到数据库
            $file = self::saveToDatabase([
                'cid'       => $params['cid'],
                'type'      => $params['fileType'],
                'name'      => $fileInfo['name'],
                'uri'       => $fileUri,
                'source'    => $params['source'],
                'source_id' => $params['sourceId'],
            ]);

            // 10. 构建返回结果
            $result = [
                'id'   => $file['id'],
                'cid'  => $file['cid'],
                'type' => $file['type'],
                'name' => $file['name'],
                'uri'  => FileService::getFileUrl($file['uri']),
                'url'  => $file['uri'],
                'width' => $file['width'] ?? 0,
                'height' => $file['height'] ?? 0,
            ];

            // 10.1 缩略图写入返回结果
            if ($thumbnailResult) {
                $result['thumbnail_path'] = $thumbnailResult['full_url'];
                $result['thumbnail_url']  = $thumbnailResult['url'];
            }

            if ($params['fetchVideoInfo'] && self::isVideoFile($fileInfo['ext'], true)) {
                try {
                    $videoInfoService = new \app\common\service\VideoInfoService();
                    $fileUrl = FileService::getFileUrl($file['uri']);

                    $videoInfo = $videoInfoService->extractVideoInfo($fileUrl, 30);
                    if (!empty($videoInfo['duration'])) {
                        $result['duration'] = (int) $videoInfo['duration'];
                    }
                    if (!empty($videoInfo['size'])) {
                        $result['size'] = (int) $videoInfo['size'];
                    }
                    if (!empty($videoInfo['video']['width'])) {
                        $result['width'] = (int) $videoInfo['video']['width'];
                    }
                    if (!empty($videoInfo['video']['height'])) {
                        $result['height'] = (int) $videoInfo['video']['height'];
                    }
                } catch (Exception $e) {
                }
            }

            $storageDefault = ConfigService::get('storage', 'default', 'local');

            // 11. FFmpeg处理
            if ($params['ffmpeg'] === 1) {
                $checkResult = self::checkTranscodeNeeded($localPath, $params['custom_specs'] ?? null);

                if ($checkResult['need_transcode']) {

                    // OSS模式：确认本地文件是否还在
                    $transcodeLocalPath = $localPath;

                    if ($storageDefault !== 'local') {
                        if (file_exists($localPath)) {
                            // 本地副本还在，直接复用
                            $transcodeLocalPath = $localPath;
                            Log::channel('ffmpeg')->write(
                                "[转码准备] 本地副本存在，直接使用: {$transcodeLocalPath}"
                            );
                        } else {
                            // 本地已删除，从OSS重新下载到临时目录
                            $transcodeLocalPath = self::downloadForTranscode($fileUri, $saveDir, $fileName);
                            Log::channel('ffmpeg')->write(
                                "[转码准备] 从OSS下载临时文件完成: {$transcodeLocalPath}"
                            );
                        }
                    }

                    // 投递转码队列
                    self::dispatchTranscodeJob([
                        'file_id'      => $file['id'],
                        'local_path'   => $transcodeLocalPath,
                        'oss_uri'      => $fileUri,
                        'save_dir'     => $saveDir,
                        'clip'         => $params['clip'],
                        'custom_specs' => $params['custom_specs'] ?? null,
                    ]);

                    Log::channel('ffmpeg')->write(
                        "[转码] 任务已投递 file_id={$file['id']} local_path={$transcodeLocalPath} oss_uri={$fileUri}"
                    );
                } else {
                    // 不需要转码，OSS模式下清理本地临时文件
                    if ($storageDefault !== 'local' && file_exists($localPath)) {
                        if (@unlink($localPath)) {
                            Log::channel('ffmpeg')->write("[清理] ✅ 无需转码，删除本地临时文件: {$localPath}");
                        } else {
                            Log::channel('ffmpeg')->write("[清理] ⚠️ 无需转码，删除本地临时文件失败: {$localPath}");
                        }
                    }
                    Log::channel('ffmpeg')->write(
                        "[转码跳过] file_id={$file['id']} reason={$checkResult['reason']}"
                    );
                }
            }

            Log::info('文件上传成功', [
                'file_id'   => $result['id'],
                'file_type' => $params['fileType'],
                'file_name' => $fileInfo['name'],
                'file_size' => $fileInfo['size'],
                'file_uri'  => $fileUri,
                'storage'   => $storageDefault,
            ]);

            return $result;
        } catch (Exception $e) {
            foreach ($tempFiles as $tempFile) {
                if (file_exists($tempFile)) {
                    @unlink($tempFile);
                }
            }

            Log::error('文件上传失败', [
                'options' => $options,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * 从 OSS 下载文件到本地，供转码队列使用
     *
     * @param string $ossUri   OSS相对路径 如 uploads/images/20260408/xxx.jpg
     * @param string $saveDir  本地目录    如 uploads/images/20260408
     * @param string $fileName 文件名      如 xxx.jpg
     * @return string          本地绝对路径
     * @throws Exception
     */
    public static function downloadForTranscode(
        string $ossUri,
        string $saveDir,
        string $fileName
    ): string {
        $localDir  = public_path() . $saveDir . '/';
        $localPath = $localDir . $fileName;

        if (!is_dir($localDir) && !mkdir($localDir, 0755, true)) {
            throw new Exception("无法创建本地临时目录: {$localDir}");
        }

        $ossFullUrl = FileService::getFileUrl($ossUri);

        // Log::channel('ffmpeg')->write(
        //     "[转码准备] 开始下载: {$ossFullUrl} => {$localPath}"
        // );

        $fp = fopen($localPath, 'w+');
        if (!$fp) {
            throw new Exception("无法创建本地临时文件: {$localPath}");
        }

        $ch = curl_init($ossFullUrl);
        curl_setopt_array($ch, [
            CURLOPT_FILE           => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 300,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'MediaTranscode/1.0',
        ]);

        $success  = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);
        fclose($fp);

        if (!$success || $httpCode !== 200) {
            @unlink($localPath);
            throw new Exception("下载OSS文件失败: HTTP {$httpCode} | {$error} | url={$ossFullUrl}");
        }

        if (!file_exists($localPath) || filesize($localPath) === 0) {
            @unlink($localPath);
            throw new Exception("下载的临时文件无效或为空: {$localPath}");
        }

        // Log::channel('ffmpeg')->write(
        //     "[转码准备] ✅ 下载完成 size=" . round(filesize($localPath) / 1024, 1) . "KB path={$localPath}"
        // );

        return $localPath;
    }


    /**
     * ✅ 新增：检查文件是否需要转码（复用 standardizeMedia 的判断逻辑）
     *
     * @param string $filePath 文件路径
     * @param array|null $customSpecs 自定义规范
     * @return array ['need_transcode' => bool, 'media_type' => string, 'reason' => string]
     */
    public static function checkTranscodeNeeded(string $filePath, ?array $customSpecs = null): array
    {
        try {
            // 1. 获取媒体信息
            $info = self::getMediaInfo($filePath);
            if (empty($info['streams'])) {
                return [
                    'need_transcode' => false,
                    'media_type' => 'unknown',
                    'reason' => '无法获取媒体流信息'
                ];
            }

            // 2. 查找视频流
            $videoStream = null;
            foreach ($info['streams'] as $stream) {
                if (($stream['codec_type'] ?? '') === 'video') {
                    $videoStream = $stream;
                    break;
                }
            }

            if (!$videoStream) {
                return [
                    'need_transcode' => false,
                    'media_type' => 'unknown',
                    'reason' => '未找到视频流'
                ];
            }

            // 3. 判断媒体类型
            $mediaType = $videoStream['codec_type'] ?? 'unknown';
            $frameRate = $videoStream['r_frame_rate'] ?? 'unknown';
            $codecName = $videoStream['codec_name']  ?? ''; // ✅ 新增

            // 4. 根据类型检查是否符合规范
            // ✅ 修改判断条件：codec 是图片类型 或 帧率是 25/1
            $imageCodecs = ['png', 'apng', 'mjpeg', 'jpeg', 'jpg', 'gif', 'webp', 'bmp', 'tiff'];
            $isImage = in_array(strtolower($codecName), $imageCodecs) || $frameRate == '25/1';

            if ($mediaType === 'video' && $isImage) {
                // 图片处理
                $specs = array_merge(self::DEFAULT_IMAGE_SPECS, $customSpecs['image'] ?? []);
                $isCompliant = self::isImageCompliant($info, $specs);

                return [
                    'need_transcode' => !$isCompliant,
                    'media_type'     => 'image',
                    'reason'         => $isCompliant ? '已符合图片规范' : '不符合图片规范',
                    'specs'          => $specs,
                    'info'           => $info
                ];
            } elseif ($mediaType === 'video') {
                // 视频处理
                $specs = array_merge(self::DEFAULT_VIDEO_SPECS, $customSpecs['video'] ?? []);
                $isCompliant = self::isVideoCompliant($info, $specs);

                return [
                    'need_transcode' => !$isCompliant,
                    'media_type'     => 'video',
                    'reason'         => $isCompliant ? '已符合视频规范' : '不符合视频规范',
                    'specs'          => $specs,
                    'info'           => $info
                ];
            } else {
                return [
                    'need_transcode' => false,
                    'media_type' => 'unknown',
                    'reason' => "不支持的媒体类型: {$mediaType}"
                ];
            }
        } catch (\Throwable $e) {
            Log::channel('ffmpeg')->write(
                "[转码检查] 检查失败: {$filePath} | 错误: " . $e->getMessage()
            );

            // 检查失败时，为安全起见，默认需要转码
            return [
                'need_transcode' => true,
                'media_type' => 'unknown',
                'reason' => '检查失败，默认转码: ' . $e->getMessage()
            ];
        }
    }


    /**
     * 解析上传选项
     *
     * @param array $options
     * @return array
     */
    private static function parseUploadOptions(array $options): array
    {
        return [
            'cid' => $options['cid'] ?? 0,
            'sourceId' => $options['sourceId'] ?? 0,
            'source' => $options['source'] ?? FileEnum::SOURCE_ADMIN,
            'saveDir' => $options['saveDir'] ?? 'uploads/file',
            'fileType' => $options['fileType'] ?? FileEnum::FILE_TYPE,
            'allowedExts' => $options['allowedExts'] ?? null,
            'ffmpeg' => $options['ffmpeg'] ?? 0,
            'clip' => $options['clip'] ?? [],
            'generateThumbnail' => $options['generateThumbnail'] ?? false,
            'thumbnailOptions' => $options['thumbnailOptions'] ?? [],
            'isDate' => $options['isDate'] ?? true,
            'autoDetectVideo' => $options['autoDetectVideo'] ?? false,
            'fetchVideoInfo' => $options['fetchVideoInfo'] ?? false,
        ];
    }

    /**
     * 获取存储配置
     *
     * @param int $ffmpeg 是否使用FFmpeg
     * @return array
     */
    private static function getStorageConfig(int $ffmpeg = 0): array
    {


        return [
            'default' => ConfigService::get('storage', 'default', 'local'),
            'engine'  => ConfigService::get('storage') ?? ['local' => []],
        ];
    }

    /**
     * 投递转码任务到队列（强制队列）
     *
     * @param array $data 任务数据
     * @throws Exception
     */
    private static function dispatchTranscodeJob(array $data): void
    {
        try {
            // 投递到队列
            Queue::push(
                MediaTranscodeJob::class,
                $data,
                env('QUEUE.TRANSCODING')
            );

            Log::channel('ffmpeg')->write('转码任务已投递到队列', context: [
                'file_id'    => $data['file_id'],
                'local_path' => $data['local_path'] ?? '', // ✅ standardized_path → local_path
                'oss_uri'    => $data['oss_uri']    ?? '', // ✅ 顺带记录 oss_uri 方便排查
            ]);
        } catch (Exception $e) {
            Log::channel('ffmpeg')->write('转码任务投递失败', context: [
                'data' => $data,
                'error' => $e->getMessage()
            ]);

            // 队列投递失败，抛出异常
            throw new Exception('转码任务投递失败: ' . $e->getMessage());
        }
    }
    /**
     * 获取转码任务状态
     *
     * @param int $fileId 文件ID
     * @return array
     */
    public static function getTranscodeStatus(int $fileId): array
    {
        // 检查是否正在处理
        $processingTime = cache('transcode_processing_' . $fileId);
        if ($processingTime) {
            return [
                'status' => 'processing',
                'message' => '转码中',
                'start_time' => $processingTime,
                'elapsed' => time() - $processingTime
            ];
        }

        // 检查是否完成
        $result = cache('transcode_result_' . $fileId);
        if ($result) {
            return [
                'status' => 'completed',
                'message' => '转码完成',
                'result' => $result
            ];
        }

        // 检查是否失败
        $error = cache('transcode_error_' . $fileId);
        if ($error) {
            return [
                'status' => 'failed',
                'message' => '转码失败',
                'error' => $error
            ];
        }

        return [
            'status' => 'unknown',
            'message' => '未找到转码记录'
        ];
    }

    /**
     * 验证文件扩展名
     *
     * @param string $ext 文件扩展名
     * @param array $allowedExts 允许的扩展名列表
     * @throws Exception
     */
    private static function validateFileExtension(string $ext, array $allowedExts): void
    {
        if (!in_array(strtolower($ext), $allowedExts)) {
            throw new Exception("不允许上传{$ext}格式的文件");
        }
    }

    /**
     * 截断过长的文件名
     *
     * @param string $fileName 文件名
     * @param int $maxLength 最大长度
     * @return string
     */
    private static function truncateFileName(string $fileName, int $maxLength = 128): string
    {
        if (strlen($fileName) <= $maxLength) {
            return $fileName;
        }

        $name = substr($fileName, 0, $maxLength - 5);
        $nameEnd = substr($fileName, -5);
        return $name . $nameEnd;
    }

    /**
     * 保存文件记录到数据库
     *
     * @param array $data 文件数据
     * @return File
     * @throws Exception
     */
    private static function saveToDatabase(array $data): File
    {
        try {
            return File::create(array_merge($data, [
                'create_time' => time()
            ]));
        } catch (Exception $e) {
            Log::error('保存文件记录失败', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw new Exception('保存文件记录失败: ' . $e->getMessage());
        }
    }

    /**
     * 处理FFmpeg标准化
     *
     * @param string $localPath 本地文件路径
     * @param string $saveDir 保存目录
     * @param array $clip 裁剪参数
     * @return array|null
     */
    private static function handleFFmpeg(string $localPath, string $saveDir, array $clip = []): ?array
    {
        try {
            $storageDefault = ConfigService::get('storage', 'default', 'local');

            // 执行标准化
            $url = self::standardizeMedia($localPath, $clip);

            // 如果是OSS存储，返回OSS URL
            if ($storageDefault !== 'local') {
                return [
                    'url' => $url,
                    'uri' => FileService::getFileUrl($url)
                ];
            }

            return null;
        } catch (Exception $e) {
            Log::error('FFmpeg处理失败', [
                'local_path' => $localPath,
                'error' => $e->getMessage()
            ]);

            // 尝试直接上传到OSS
            $storageDefault = ConfigService::get('storage', 'default', 'local');
            if ($storageDefault !== 'local') {
                try {
                    $url = self::uploadToOSS($localPath, $saveDir);
                    return [
                        'url' => $url,
                        'uri' => FileService::getFileUrl($url)
                    ];
                } catch (Exception $ossException) {
                    Log::error('OSS上传失败', [
                        'error' => $ossException->getMessage()
                    ]);
                }
            }

            return null;
        }
    }

    /**
     * 处理缩略图生成
     *
     * @param string $videoUri 视频URI
     * @param array $options 缩略图选项
     * @return array|null
     */
    private static function handleThumbnail(string $videoUri, array $options = []): ?array
    {
        try {
            $options = array_merge(self::DEFAULT_THUMBNAIL_OPTIONS, $options);

            Log::info('【handleThumbnail】入参', [
                'videoUri'    => $videoUri,
                'file_exists' => file_exists($videoUri),
            ]);

            // ✅ 验证本地文件存在（传入的是本地绝对路径时）
            if (strpos($videoUri, '/') === 0 && !file_exists($videoUri)) {
                // Log::warning('缩略图生成跳过：本地视频文件不存在', [
                //     'video_uri' => $videoUri
                // ]);
                return null;
            }

            $videoInfoService = new VideoInfoService();
            $result = $videoInfoService->generateThumbnail(
                $videoUri,
                (float)($options['time'] ?? 1.0),
                $options
            );

            if ($result) {
                // Log::info('缩略图生成成功', [
                //     'video_uri'     => $videoUri,
                //     'thumbnail_url' => $result['url'],
                //     'storage'       => $result['storage'] ?? 'local'
                // ]);
                return $result;
            }

            return null;
        } catch (Exception $e) {
            // ✅ 缩略图失败不影响主流程，只记录日志
            // Log::error('缩略图生成失败', [
            //     'video_uri' => $videoUri,
            //     'options'   => $options,
            //     'error'     => $e->getMessage()
            // ]);
            return null;
        }
    }


    /**
     * 判断是否为视频文件
     *
     * @param string $ext 文件扩展名
     * @param bool $autoDetect 是否自动检测
     * @return bool
     */
    private static function isVideoFile(string $ext, bool $autoDetect = false): bool
    {
        if (!$autoDetect) {
            return false;
        }

        $videoExtensions = config('project.file_video') ?? [
            'mp4',
            'avi',
            'mov',
            'wmv',
            'flv',
            'webm',
            'mkv'
        ];

        return in_array(strtolower($ext), $videoExtensions);
    }

    /**
     * 解析Base64内容
     *
     * @param string $content Base64内容
     * @return string|null
     */
    private static function parseBase64Content(string $content): ?string
    {
        $data = explode(',', $content);
        $base64Data = $data[1] ?? $data[0];

        // 验证Base64格式
        if (!preg_match('/^[a-zA-Z0-9\/\+\=]+$/', $base64Data)) {
            return null;
        }

        return $base64Data;
    }

    /**
     * 转换AMR格式到MP3
     *
     * @param array $fileInfo 文件信息
     * @param StorageDriver $storageDriver 存储驱动
     * @return array|null
     */
    private static function convertAmrToMp3(array $fileInfo, StorageDriver $storageDriver): ?array
    {
        try {
            $path = $fileInfo['realPath'];
            $tempExtension = pathinfo($path, PATHINFO_EXTENSION);

            $command = root_path() . 'extend/lib/silk/converter.sh' . " " . $path . " mp3";
            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new Exception('AMR转换失败');
            }

            $newPath = str_replace($tempExtension, 'mp3', $path);
            $newFileName = str_replace('.amr', '.mp3', $fileInfo['name']);

            $storageDriver->setRealPath($newPath);
            $storageDriver->setFilename($newFileName);

            Log::info('AMR转MP3成功', [
                'original' => $path,
                'converted' => $newPath
            ]);

            return [
                'fileName' => $newFileName,
                'path' => $newPath
            ];
        } catch (Exception $e) {
            Log::error('AMR转换失败', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    // ==================== 媒体处理方法 ====================

    /**
     * 标准化媒体文件（修复版）
     *
     * @param string $inputPath 输入路径（本地绝对路径）
     * @param array|null $customSpecs 自定义规范
     * @param string|null $targetFileName 指定上传OSS时的目标文件名
     * @return string 输出URL（OSS路径 或 本地相对路径）
     * @throws Exception
     */
    public static function standardizeMedia(
        string $inputPath,
        ?array $customSpecs = null,
        ?string $targetFileName = null
    ): string {
        $tempOutputPath = null;

        try {
            // ✅ 1. 验证输入文件存在
            if (!file_exists($inputPath)) {
                throw new Exception("输入文件不存在: {$inputPath}");
            }
            if (filesize($inputPath) === 0) {
                throw new Exception("输入文件为空: {$inputPath}");
            }

            // 2. 获取媒体信息
            $info = self::getMediaInfo($inputPath);
            // Log::channel('ffmpeg')->write('[转码] 媒体信息: ' . json_encode($info, JSON_UNESCAPED_UNICODE));

            if (empty($info['streams'])) {
                throw new Exception("无法获取媒体文件信息");
            }

            // 3. 查找视频流
            $videoStream = null;
            foreach ($info['streams'] as $stream) {
                if (($stream['codec_type'] ?? '') === 'video') {
                    $videoStream = $stream;
                    break;
                }
            }

            if (!$videoStream) {
                throw new Exception("未找到视频流");
            }

            // 4. 判断媒体类型
            $mediaType = $videoStream['codec_type'] ?? 'unknown';
            $frameRate = $videoStream['r_frame_rate'] ?? 'unknown';
            $codecName = strtolower($videoStream['codec_name'] ?? '');

            // 5. 确定目标文件名
            $finalFileName = $targetFileName ?? basename($inputPath);

            // ✅ 6. 提取 ossPath（更健壮的方式，不依赖正则）
            //       inputPath 示例：/www/wwwroot/public/uploads/images/20260408/abc.jpg
            //       ossPath   示例：uploads/images/20260408
            $publicPath = rtrim(public_path(), '/');
            $relativePath = ltrim(str_replace($publicPath, '', $inputPath), '/');
            $ossPath = ltrim(dirname($relativePath), '/');

            // Log::channel('ffmpeg')->write(
            //     "[转码] inputPath={$inputPath} ossPath={$ossPath} finalFileName={$finalFileName}"
            // );

            // 7. 确定规范配置 & 媒体类型
            $imageCodecs = ['png', 'apng', 'mjpeg', 'jpeg', 'jpg', 'gif', 'webp', 'bmp', 'tiff'];
            $isImage = in_array($codecName, $imageCodecs) || $frameRate == '25/1';

            if ($mediaType === 'video' && $isImage) {
                $specs = array_merge(self::DEFAULT_IMAGE_SPECS, $customSpecs['image'] ?? []);
                // Log::channel('ffmpeg')->write("[转码] 识别为图片类型 codec={$codecName}");
            } elseif ($mediaType === 'video') {
                $specs = array_merge(self::DEFAULT_VIDEO_SPECS, $customSpecs['video'] ?? []);
                // Log::channel('ffmpeg')->write("[转码] 识别为视频类型 codec={$codecName}");
            } else {
                throw new Exception("不支持的媒体类型: {$mediaType}");
            }

            // ✅ 8. 生成临时输出路径（和 inputPath 同目录，避免跨盘 copy 问题）
            $inputDir       = dirname($inputPath);
            $tempOutputPath = $inputDir . '/tmp_' . uniqid() . '_' . $finalFileName;

            // ✅ 9. 确保输出目录存在且可写
            if (!is_dir($inputDir)) {
                if (!mkdir($inputDir, 0755, true)) {
                    throw new Exception("无法创建输出目录: {$inputDir}");
                }
            }
            if (!is_writable($inputDir)) {
                throw new Exception("输出目录无写权限: {$inputDir}");
            }

            // 10. 执行转码（输出到临时文件）
            if ($isImage) {
                self::transcodeImage($inputPath, $tempOutputPath, $specs, $info);
                // Log::channel('ffmpeg')->write("[转码完成] 图片转码成功: {$inputPath}");
            } else {
                self::transcodeVideo($inputPath, $tempOutputPath, $specs, $info);
                // Log::channel('ffmpeg')->write("[转码完成] 视频转码成功: {$inputPath}");
            }

            // ✅ 11. 验证临时输出文件
            if (!file_exists($tempOutputPath)) {
                throw new Exception("转码失败：临时输出文件未生成: {$tempOutputPath}");
            }
            $tempFileSize = filesize($tempOutputPath);
            if ($tempFileSize < 100) {
                @unlink($tempOutputPath);
                throw new Exception("转码失败：临时输出文件过小 ({$tempFileSize} bytes)");
            }

            // Log::channel('ffmpeg')->write(
            //     "[转码验证] ✅ 临时文件生成成功 size=" . round($tempFileSize / 1024, 1) . "KB path={$tempOutputPath}"
            // );

            // 12. 根据存储模式处理
            $storageDefault = ConfigService::get('storage', 'default', 'local');

            if ($storageDefault !== 'local') {
                // ✅ OSS模式：直接上传临时文件到 OSS，跳过 copy 步骤
                //    forceFileName 确保 OSS 上的文件名与数据库 uri 一致
                $ossUrl = self::uploadToOSS($tempOutputPath, $ossPath, $finalFileName);

                // 清理临时转码文件
                if (@unlink($tempOutputPath)) {
                    // Log::channel('ffmpeg')->write("[清理] ✅ 已删除临时转码文件: {$tempOutputPath}");
                } else {
                    // Log::channel('ffmpeg')->write("[清理] ⚠️ 删除临时转码文件失败: {$tempOutputPath}");
                }

                // 清理原始输入文件（如果和临时文件不同路径）
                if ($inputPath !== $tempOutputPath && file_exists($inputPath)) {
                    if (@unlink($inputPath)) {
                        // Log::channel('ffmpeg')->write("[清理] ✅ 已删除本地原始文件: {$inputPath}");
                    } else {
                        // Log::channel('ffmpeg')->write("[清理] ⚠️ 删除本地原始文件失败: {$inputPath}");
                    }
                }

                // Log::channel('ffmpeg')->write("[OSS上传] ✅ 转码文件已上传: {$ossUrl}");
                return $ossUrl;
            } else {
                // ✅ 本地模式：将临时文件 rename 到最终路径（原子操作，比 copy 更安全）
                $finalOutputPath = $inputDir . '/' . $finalFileName;

                if (!rename($tempOutputPath, $finalOutputPath)) {
                    // rename 失败时尝试 copy
                    if (!copy($tempOutputPath, $finalOutputPath)) {
                        @unlink($tempOutputPath);
                        throw new Exception("转码文件替换失败: {$tempOutputPath} => {$finalOutputPath}");
                    }
                    @unlink($tempOutputPath);
                }

                // 验证最终文件
                if (!file_exists($finalOutputPath) || filesize($finalOutputPath) < 100) {
                    throw new Exception("转码文件替换后验证失败: {$finalOutputPath}");
                }

                $finalRelativePath = $ossPath . '/' . $finalFileName;
                // Log::channel('ffmpeg')->write("[本地] ✅ 转码文件已就位: {$finalOutputPath}");
                return $finalRelativePath;
            }
        } catch (Exception $e) {
            // 清理临时文件
            if ($tempOutputPath && file_exists($tempOutputPath)) {
                @unlink($tempOutputPath);
                // Log::channel('ffmpeg')->write("[清理] 异常清理临时文件: {$tempOutputPath}");
            }
            // Log::channel('ffmpeg')->write("[转码失败] {$inputPath} | 错误: " . $e->getMessage());
            throw new Exception("媒体标准化失败: " . $e->getMessage());
        }
    }



    /**
     * 上传文件到OSS（修复版）
     *
     * @param string $localPath     本地文件绝对路径
     * @param string $ossPath       OSS目标目录（相对路径，如 uploads/images/20260408）
     * @param string|null $forceFileName 强制指定OSS上的文件名，不传则使用本地文件名
     * @return string               OSS相对路径（如 uploads/images/20260408/abc.jpg）
     * @throws Exception
     */
    public static function uploadToOSS(
        string $localPath,
        string $ossPath,
        ?string $forceFileName = null
    ): string {
        // 1. 严格验证本地文件
        if (!file_exists($localPath)) {
            throw new Exception("待上传文件不存在: {$localPath}");
        }
        $fileSize = filesize($localPath);
        if ($fileSize < 100) {
            throw new Exception("待上传文件异常，大小仅 {$fileSize} bytes，拒绝上传: {$localPath}");
        }

        $finalFileName = $forceFileName ?: basename($localPath);

        Log::channel('ffmpeg')->write(sprintf(
            '[OSS上传] 开始上传 | localPath=%s | size=%sKB | ossPath=%s | finalFileName=%s',
            $localPath,
            round($fileSize / 1024, 1),
            $ossPath,
            $finalFileName
        ));

        $config = [
            'default' => ConfigService::get('storage', 'default', 'local'),
            'engine'  => ConfigService::get('storage') ?? ['local' => []],
        ];

        $storageDriver = new StorageDriver($config);

        // ✅ 核心修复：使用 setUploadFileByFileName 直接指定文件名
        //    绕过 buildSaveName() 的随机命名逻辑
        $storageDriver->setUploadFileByFileName($localPath, $finalFileName);

        Log::channel('ffmpeg')->write(sprintf(
            '[OSS上传] 即将上传 | fileName=%s | realPath=%s',
            $finalFileName,
            $storageDriver->getRealPath()
        ));

        if (!$storageDriver->upload($ossPath)) {
            throw new Exception("OSS驱动上传失败: " . $storageDriver->getError());
        }

        $finalUrl = rtrim($ossPath, '/') . '/' . $finalFileName;
        $finalUrl = str_replace('\\', '/', $finalUrl);

        Log::channel('ffmpeg')->write("[OSS上传] ✅ 上传成功 url={$finalUrl}");
        return $finalUrl;
    }





    /**
     * 获取上传URL（带日期目录）
     *
     * @param string $saveDir 保存目录
     * @param bool $useDate 是否使用日期目录
     * @return string
     */
    private static function getUploadUrl(string $saveDir, bool $useDate = true): string
    {
        if (!$useDate) {
            return $saveDir;
        }

        return $saveDir . '/' . date('Ymd');
    }

    // ==================== 待实现的方法（保持原有逻辑） ====================

    /**
     * 获取媒体信息
     * 注：此方法需要根据原有实现补充
     */
    public static function getMediaInfo(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new Exception("文件不存在: " . $filePath);
        }

        $ffprobeCommand = "ffprobe -v error -show_streams -print_format json " . escapeshellarg($filePath);
        $output = shell_exec($ffprobeCommand);

        if ($output === null) {
            throw new Exception("无法执行ffprobe命令或命令执行失败");
        }

        $info = json_decode($output, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("解析媒体信息失败: " . json_last_error_msg());
        }

        return $info;
    }

    /**
     * 判断图片是否符合规范
     */
    private static function isImageCompliant(array $info, array $specs): bool
    {
        if (empty($info['streams'])) {
            return false;
        }

        $stream = $info['streams'][0];
        $format = $stream['codec_name'] ?? '';

        $targetResolution = self::calculateTargetResolution($info, $specs);
        $targetDims = explode('x', $targetResolution);

        return (
            isset($stream['width']) && $stream['width'] <= intval($targetDims[0]) &&
            isset($stream['height']) && $stream['height'] <= intval($targetDims[1]) &&
            in_array(strtolower($format), ['jpg', 'jpeg', 'png', 'mjpeg'])
        );
    }

    /**
     * 判断视频是否符合规范
     */
    private static function isVideoCompliant(array $info, array $specs): bool
    {
        if (empty($info['streams'])) {
            return false;
        }
        $specs = array_merge(self::DEFAULT_VIDEO_SPECS, $specs);
        $stream = $info['streams'][0];
        // 计算目标分辨率
        $targetResolution = self::calculateTargetResolution($info, $specs);
        $targetDims = explode('x', $targetResolution);
        $frameRate = explode('/', $stream['r_frame_rate']);
        $frameRate = $frameRate[0];
        if ($specs['duration'] < 1) {
            return (
                isset($stream['codec_name']) && $stream['codec_name'] === $specs['video_codec'] &&
                isset($stream['width']) && $stream['width'] <= intval($targetDims[0]) &&
                isset($stream['height']) && $stream['height'] <= intval($targetDims[1]) &&
                isset($frameRate) && $frameRate == $specs['frame_rate']
            );
        } else {
            return (
                isset($stream['codec_name']) && $stream['codec_name'] === $specs['video_codec'] &&
                isset($stream['width']) && $stream['width'] <= intval($targetDims[0]) &&
                isset($stream['height']) && $stream['height'] <= intval($targetDims[1]) &&
                isset($frameRate) && $frameRate == $specs['frame_rate'] &&
                isset($stream['duration']) && $stream['duration'] < $specs['duration']
            );
        }
    }

    /**
     * 转码图片（完整优化版）
     *
     * @param string $inputPath  输入文件的绝对路径
     * @param string $outputPath 输出文件的绝对路径（临时路径）
     * @param array  $specs      转码规范配置
     * @param array  $info       由 ffprobe 获取的媒体信息
     * @throws Exception
     */
    private static function transcodeImage(string $inputPath, string $outputPath, array $specs, array $info): void
    {
        // 1. 基础验证
        if (!file_exists($inputPath)) {
            throw new Exception("输入文件不存在: " . $inputPath);
        }

        // 2. 确定编码器：保持与原后缀一致，防止 OSS 驱动因 MIME 更改后缀
        $extension = strtolower(pathinfo($inputPath, PATHINFO_EXTENSION));
        $vcodec = 'png'; // 默认使用 PNG
        if (in_array($extension, ['jpg', 'jpeg'])) {
            $vcodec = 'mjpeg'; // JPG 原图对应 mjpeg 编码
        }

        // 3. 提取旋转角度（处理手机拍照自动旋转问题）
        $rotation = null;
        if (!empty($info['streams'])) {
            foreach ($info['streams'] as $stream) {
                if (isset($stream['rotation'])) {
                    $rotation = intval($stream['rotation']);
                    break;
                }
                if (!empty($stream['side_data_list'])) {
                    foreach ($stream['side_data_list'] as $sideData) {
                        if ($sideData['side_data_type'] === 'Display Matrix' && isset($sideData['rotation'])) {
                            $rotation = intval($sideData['rotation']);
                            break 2;
                        }
                    }
                }
            }
        }

        // 4. 计算目标分辨率
        $resolution = self::calculateTargetResolution($info, $specs, $rotation);

        // 5. 目录检查与准备
        $outputDir = dirname($outputPath);
        if (!is_dir($outputDir)) {
            if (!mkdir($outputDir, 0755, true)) {
                throw new Exception("无法创建输出目录: " . $outputDir);
            }
        }
        if (!is_writable($outputDir)) {
            throw new Exception("输出目录无写权限: " . $outputDir);
        }

        // 6. 识别动图类型（针对 GIF/WebP/APNG）
        $codecName = '';
        if (!empty($info['streams'][0]['codec_name'])) {
            $codecName = strtolower($info['streams'][0]['codec_name']);
        }
        $animatedCodecs = ['apng', 'gif', 'webp'];
        $isAnimated = in_array($codecName, $animatedCodecs);

        // 7. 构建 FFmpeg 命令
        $ffmpegParts = [
            "nice -n 19 ffmpeg6", // 降低优先级
            "-threads 1",         // 限制单线程保护 CPU
            "-i " . escapeshellarg($inputPath),
        ];

        // 动图只提取第一帧，防止转码卡死
        if ($isAnimated) {
            $ffmpegParts[] = "-frames:v 1";
            Log::channel('ffmpeg')->write("[转码] 识别为动图，仅提取首帧。codec={$codecName}");
        }

        // 设置编码器
        $ffmpegParts[] = "-c:v " . $vcodec;

        // 添加缩放滤镜
        if ($resolution) {
            $ffmpegParts[] = "-vf scale=" . escapeshellarg($resolution);
        }

        $ffmpegParts[] = "-y"; // 覆盖模式
        $ffmpegParts[] = escapeshellarg($outputPath);

        $command = implode(" ", $ffmpegParts);
        Log::channel('ffmpeg')->write('[图片转码指令] ' . $command);

        // 8. 执行命令（带 120s 超时保护）
        $output = self::execWithTimeout($command, 120);

        // 9. 结果验证
        if (!file_exists($outputPath)) {
            $errorMsg = "图片转码失败，未生成文件。命令: {$command} | 输出: {$output}";
            Log::channel('ffmpeg')->write($errorMsg);
            throw new Exception($errorMsg);
        }

        $outputSize = filesize($outputPath);
        if ($outputSize < 100) {
            @unlink($outputPath);
            throw new Exception("图片转码异常，输出文件过小 ({$outputSize} bytes)");
        }

        Log::channel('ffmpeg')->write("[转码成功] 图片大小: {$outputSize} bytes | 路径: {$outputPath}");
    }


    private static function transcodeVideo(string $inputPath, string $outputPath, array $specs, array $info): void
    {
        // 验证输入文件
        if (!file_exists($inputPath)) {
            throw new Exception("输入文件不存在: " . $inputPath);
        }

        // 文件大小检查
        if (filesize($inputPath) === 0) {
            throw new Exception("输入文件为空: " . $inputPath);
        }

        // 获取视频时长信息
        $duration = null;
        if (isset($info['format']['duration'])) {
            $duration = floatval($info['format']['duration']);
        }

        // 单个视频超过3小时，拒绝转码（防止撑爆服务器）
        if ($duration !== null && $duration > 10800) {
            throw new Exception("视频时长超过3小时（{$duration}s），拒绝转码: " . $inputPath);
        }

        // 从媒体信息中提取旋转角度
        $rotation = null;
        if (!empty($info['streams'])) {
            foreach ($info['streams'] as $stream) {
                if (isset($stream['rotation'])) {
                    $rotation = intval($stream['rotation']);
                    break;
                }
                if (!empty($stream['side_data_list'])) {
                    foreach ($stream['side_data_list'] as $sideData) {
                        if ($sideData['side_data_type'] === 'Display Matrix' && isset($sideData['rotation'])) {
                            $rotation = intval($sideData['rotation']);
                            break 2;
                        }
                    }
                }
            }
        }

        // 计算动态分辨率
        $resolution = self::calculateTargetResolution($info, $specs, $rotation);

        // 确保输出目录存在并有写权限
        $outputDir = dirname($outputPath);
        if (!is_dir($outputDir)) {
            if (!mkdir($outputDir, 0755, true)) {
                throw new Exception("无法创建输出目录: " . $outputDir);
            }
        }
        if (!is_writable($outputDir)) {
            throw new Exception("输出目录没有写权限: " . $outputDir);
        }

        // ✅ 磁盘空间检查（至少保留 2GB 可用空间）
        $freeBytes = disk_free_space($outputDir);
        if ($freeBytes !== false && $freeBytes < 2 * 1024 * 1024 * 1024) {
            throw new Exception("磁盘空间不足（剩余 " . round($freeBytes / 1024 / 1024) . "MB），拒绝转码");
        }

        // ✅ 构建 ffmpeg 命令
        $ffmpegParts = [
            // nice -n 19 降低进程优先级，避免抢占系统资源
            "nice -n 19",
            // ✅ 限制 ffmpeg 只使用 1 个线程，防止 CPU 爆满
            "ffmpeg6 -threads 1 -i " . escapeshellarg($inputPath),
            "-c:v libx264",
            // ✅ crf 提高到 28，降低编码压力（23 太高质量=更耗 CPU）
            "-crf 28",
            // ✅ preset 改为 veryfast，大幅降低 CPU 占用
            "-preset veryfast",
            "-c:a aac",
            "-b:a 128k",
            "-movflags +faststart",
            // ✅ 限制 x264 编码线程数
            "-x264-params threads=1",
        ];

        // 只有在明确指定时才限制时长
        if (isset($specs['duration']) && $specs['duration'] > 0) {
            $ffmpegParts[] = "-t " . intval($specs['duration']);
        }

        // 添加帧率
        if (isset($specs['frame_rate']) && $specs['frame_rate'] > 0) {
            $ffmpegParts[] = "-r " . intval($specs['frame_rate']);
        }

        // 添加像素格式
        if (isset($specs['pixel_format'])) {
            $ffmpegParts[] = "-pix_fmt " . escapeshellarg($specs['pixel_format']);
        }

        // 添加码率
        if (isset($specs['bit_rate'])) {
            $ffmpegParts[] = "-b:v " . escapeshellarg($specs['bit_rate']);
        }

        // 添加缩放滤镜
        if ($resolution) {
            $ffmpegParts[] = "-vf scale=" . escapeshellarg($resolution);
        }

        // ✅ 覆盖输出（避免交互式询问卡死）
        $ffmpegParts[] = "-y";
        $ffmpegParts[] = escapeshellarg($outputPath);

        $ffmpegCommand = implode(" ", $ffmpegParts);
        Log::channel('ffmpeg')->write('[转码指令] ' . $ffmpegCommand);

        // ✅ 使用 proc_open 执行，支持超时强制终止
        $output = self::execWithTimeout($ffmpegCommand, 600);

        // 输出文件不存在时，把完整 ffmpeg 输出打印出来
        if (!file_exists($outputPath)) {
            Log::channel('ffmpeg')->write('[ffmpeg stderr] ' . $output); // ← 加这行
            throw new Exception("视频转码失败...");
        }

        // 检查输出文件是否生成
        if (!file_exists($outputPath)) {
            // ✅ 转码失败时清理残留文件
            @unlink($outputPath);
            $errorMsg = "视频转码失败，输出文件未生成\n"
                . "命令: " . $ffmpegCommand . "\n"
                . "输出: " . $output . "\n"
                . "输入文件: " . $inputPath . "\n"
                . "输出文件: " . $outputPath . "\n"
                . "输入文件大小: " . (file_exists($inputPath) ? filesize($inputPath) : 'N/A') . " bytes";
            Log::channel('ffmpeg')->write($errorMsg);
            throw new Exception($errorMsg);
        }

        // 检查输出文件大小
        $outputSize = filesize($outputPath);
        if ($outputSize < 1000) {
            @unlink($outputPath);
            $errorMsg = "视频转码失败，输出文件过小 ({$outputSize} bytes)\n"
                . "命令: " . $ffmpegCommand . "\n"
                . "输出: " . $output;
            Log::channel('ffmpeg')->write($errorMsg);
            throw new Exception($errorMsg);
        }

        Log::channel('ffmpeg')->write(
            "[转码完成] 输出文件: {$outputPath} 大小: " . round($outputSize / 1024 / 1024, 2) . "MB"
        );
    }
    /**
     * @notes 动态计算目标分辨率
     * @param array $info 媒体信息
     * @param array $specs 规范配置
     * @param int|null $rotation 旋转角度
     * @return string 目标分辨率（格式：宽x高）
     * @author 系统
     * @date 2024/12/19
     */
    private static function calculateTargetResolution(array $info, array $specs, ?int $rotation = null): string
    {
        if (empty($info['streams'])) {
            throw new Exception("媒体信息无效");
        }

        $stream = $info['streams'][0];
        $originalWidth = $stream['width'] ?? 0;
        $originalHeight = $stream['height'] ?? 0;

        if ($originalWidth == 0 || $originalHeight == 0) {
            throw new Exception("无法获取媒体尺寸信息");
        }

        // 获取动态分辨率配置
        $maxDimension    = $specs['max_dimension']    ?? 2000;
        $targetDimension = $specs['target_dimension'] ?? 1920;

        // 检查是否需要压缩
        if (max($originalWidth, $originalHeight) <= $maxDimension) {
            // 不需要压缩，保持原始分辨率
            $finalWidth  = $originalWidth;
            $finalHeight = $originalHeight;

            // ✅ 补上偶数校验（libx264/libx265 要求宽高必须是偶数）
            $finalWidth  = $finalWidth  % 2 === 0 ? $finalWidth  : $finalWidth  - 1;
            $finalHeight = $finalHeight % 2 === 0 ? $finalHeight : $finalHeight - 1;
        } else {
            // 计算等比例缩放后的尺寸
            $scaleRatio  = $targetDimension / max($originalWidth, $originalHeight);
            $finalWidth  = intval($originalWidth  * $scaleRatio);
            $finalHeight = intval($originalHeight * $scaleRatio);

            // 确保尺寸是偶数（已有，保持不动）
            $finalWidth  = $finalWidth  % 2 === 0 ? $finalWidth  : $finalWidth  - 1;
            $finalHeight = $finalHeight % 2 === 0 ? $finalHeight : $finalHeight - 1;
        }

        // 如果是90/270/-90/-270度旋转，需要对换宽高
        if (in_array($rotation, [90, 270, -90, -270])) {
            $tempWidth   = $finalWidth;
            $finalWidth  = $finalHeight;
            $finalHeight = $tempWidth;

            // ✅ 对换后再做一次偶数校验（旋转后的值也可能是奇数）
            $finalWidth  = $finalWidth  % 2 === 0 ? $finalWidth  : $finalWidth  - 1;
            $finalHeight = $finalHeight % 2 === 0 ? $finalHeight : $finalHeight - 1;
        }

        return $finalWidth . 'x' . $finalHeight;
    }



    /**
     * ✅ 带超时控制的命令执行
     * 超时后强制 kill 进程，防止 ffmpeg 僵尸进程占用 CPU
     *
     * @param string $command  要执行的命令
     * @param int    $timeout  超时秒数
     * @return string          命令输出
     * @throws Exception       超时时抛出异常
     */
    private static function execWithTimeout(string $command, int $timeout = 60): string
    {
        $descriptors = [
            0 => ['pipe', 'r'],  // stdin
            1 => ['pipe', 'w'],  // stdout
            2 => ['pipe', 'w'],  // stderr
        ];

        $process = proc_open($command, $descriptors, $pipes);

        if (!is_resource($process)) {
            throw new Exception("无法启动进程: " . $command);
        }

        fclose($pipes[0]); // ✅ 关闭 stdin，防止 ffmpeg 等待输入

        // 设置非阻塞读取
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $output = '';
        $startTime = time();

        while (true) {
            $output .= fread($pipes[1], 4096);
            $output .= fread($pipes[2], 4096);

            $status = proc_get_status($process);
            if (!$status['running']) {
                break;
            }

            // ✅ 超时强制 kill
            if (time() - $startTime > $timeout) {
                proc_terminate($process, 9);
                throw new Exception("ffmpeg 执行超时 ({$timeout}s): " . $command);
            }

            usleep(100000); // 100ms
        }

        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        return $output;
    }



    /**
     * ✅ 从命令字符串中提取输出文件路径（用于超时清理）
     */
    private static function extractOutputPath(string $command): ?string
    {
        // 匹配最后一个单引号包裹的路径
        if (preg_match_all("/'([^']+)'/", $command, $matches)) {
            $last = end($matches[1]);
            // 判断是否像文件路径
            if (strpos($last, '/') !== false || strpos($last, '.') !== false) {
                return $last;
            }
        }
        return null;
    }


    /**
     * 文件上传到云端
     */
    public static function fileUpload(
        string $filesPath,
        int $source = FileEnum::SOURCE_USER,
        string $saveDir = 'uploads/images',
        bool $isSave = true
    ) {
        try {
            $config = [
                'default' => ConfigService::get('storage', 'default', 'local'),
                'engine'  => ConfigService::get('storage') ?? ['local' => []],
            ];
            // 2、执行文件上传
            $StorageDriver = new StorageDriver($config);
            $StorageDriver->setUploadFileByReal($filesPath);
            $fileName = $StorageDriver->getFileName();
            $fileInfo = $StorageDriver->getFileInfo();

            // 上传文件
            $saveDir = self::getUploadUrl($saveDir);

            if (!$StorageDriver->upload($saveDir)) {
                throw new Exception($StorageDriver->getError());
            }

            // 3、处理文件名称
            if (strlen($fileInfo['name']) > 128) {
                $name             = substr($fileInfo['name'], 0, 123);
                $nameEnd          = substr($fileInfo['name'], strlen($fileInfo['name']) - 5, strlen($fileInfo['name']));
                $fileInfo['name'] = $name . $nameEnd;
            }

            if ($isSave) {
                // 4、写入数据库中
                $file = File::create([
                    'cid'         => 0,
                    'type'        => FileEnum::IMAGE_TYPE,
                    'name'        => $fileInfo['name'],
                    'uri'         => $saveDir . '/' . str_replace("\\", "/", $fileName),
                    'source'      => $source,
                    'create_time' => time(),
                ]);
                // 5、返回结果
                return [
                    'id'  => $file['id'],
                    'uri' => FileService::getFileUrl($file['uri']),
                    'url' => $file['uri'],
                ];
            } else {
                // 5、返回结果
                return [
                    'uri' => FileService::getFileUrl($saveDir . '/' . str_replace("\\", "/", $fileName)),
                    'url' => $saveDir . '/' . str_replace("\\", "/", $fileName),
                ];
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 本地文件上传
     */
    public static function fileLocal(
        int $cid,
        int $sourceId = 0,
        int $source = FileEnum::SOURCE_ADMIN,
        string $saveDir = 'uploads/file'
    ) {
        try {
            $config = [
                'default' => "local",
                'engine'  => ['local' => [""]],
            ];

            // 2、执行文件上传
            $StorageDriver = new StorageDriver($config);
            $StorageDriver->setUploadFile('file');
            $fileName = $StorageDriver->getFileName();
            $fileInfo = $StorageDriver->getFileInfo();


            // 上传文件
            $saveDir = self::getUploadUrl($saveDir);
            if (!$StorageDriver->upload($saveDir)) {
                throw new Exception($StorageDriver->getError());
            }

            // 3、处理文件名称
            if (strlen($fileInfo['name']) > 128) {
                $name             = substr($fileInfo['name'], 0, 123);
                $nameEnd          = substr($fileInfo['name'], strlen($fileInfo['name']) - 5, strlen($fileInfo['name']));
                $fileInfo['name'] = $name . $nameEnd;
            }
            $host = config('app.app_host');

            // 4、写入数据库中
            $file = File::create([
                'cid'         => $cid,
                'type'        => FileEnum::FILE_TYPE,
                'name'        => $fileInfo['name'],
                'uri'         => $saveDir . '/' . str_replace("\\", "/", $fileName),
                'source'      => $source,
                'source_id'   => $sourceId,
                'create_time' => time(),
            ]);

            // 5、返回结果
            return [
                'id'   => $file['id'],
                'cid'  => $file['cid'],
                'type' => $file['type'],
                'name' => $file['name'],
                'uri'  => $host . '/' . $saveDir . '/' . str_replace("\\", "/", $fileName),
                'url'  => $file['uri']
            ];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * ZIP文件上传
     */
    public static function zipfile(
        int $cid,
        int $sourceId = 0,
        int $source = FileEnum::SOURCE_ADMIN,
        string $saveDir = '../extend/miniprogram-ci'
    ) {
        try {
            $config = [
                'default' => "local",
                'engine'  => [],
            ];

            // 2、执行文件上传
            $StorageDriver = new StorageDriver($config);
            $StorageDriver->setUploadFile('file');
            $fileName = $StorageDriver->getFileName();
            $fileInfo = $StorageDriver->getFileInfo();

            // 校验上传文件后缀
            if (!in_array(strtolower($fileInfo['ext']), config('project.zip_file'))) {
                throw new Exception("上传压缩文件不允许上传" . $fileInfo['ext'] . "文件");
            }

            // 上传文件
            if (!$StorageDriver->upload($saveDir)) {
                throw new Exception($StorageDriver->getError());
            }

            // 3、处理文件名称
            if (strlen($fileInfo['name']) > 128) {
                $name             = substr($fileInfo['name'], 0, 123);
                $nameEnd          = substr($fileInfo['name'], strlen($fileInfo['name']) - 5, strlen($fileInfo['name']));
                $fileInfo['name'] = $name . $nameEnd;
            }

            // 4、写入数据库中
            $file = File::create([
                'cid'         => $cid,
                'type'        => FileEnum::IMAGE_TYPE,
                'name'        => $fileInfo['name'],
                'uri'         => $saveDir . '/' . str_replace("\\", "/", $fileName),
                'source'      => $source,
                'source_id'   => $sourceId,
                'create_time' => time(),
            ]);

            // 5、返回结果
            return [
                'id'   => $file['id'],
                'cid'  => $file['cid'],
                'type' => $file['type'],
                'name' => $file['name'],
                //                'uri'  => FileService::getFileUrl($file['uri']),
                'url'  => $file['uri']
            ];
        } catch (\think\exception\HttpResponseException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    /**
     * 下载远程文件
     */
    public static function downloadRemoteFile(string $remoteUrl): string
    {
        $pathInfo = pathinfo(parse_url($remoteUrl, PHP_URL_PATH));

        // 获取路径部分
        // 生成下载目录
        $downloadDir = public_path() . ltrim($pathInfo['dirname'], '/') . '/';
        if (!is_dir($downloadDir)) {
            if (!mkdir($downloadDir, 0777, true)) {
                throw new Exception("无法创建下载目录: " . $downloadDir);
            }
        }
        // 生成唯一文件名：remote_media_[时间戳]_[随机数].[扩展名]
        $tempFile = $downloadDir . $pathInfo['basename'];
        // 下载文件
        $ch = curl_init();
        $fp = fopen($tempFile, 'w+');
        if (!$fp) {
            throw new Exception("无法创建下载文件: " . $tempFile);
        }

        curl_setopt($ch, CURLOPT_URL, $remoteUrl);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5分钟超时
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MediaProcessor/1.0)');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 忽略SSL证书验证

        $success = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        fclose($fp);
        if (!$success || $httpCode !== 200) {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
            throw new Exception("下载远程文件失败: " . ($error ?: "HTTP {$httpCode}"));
        }

        if (!file_exists($tempFile) || filesize($tempFile) === 0) {
            throw new Exception("下载的文件无效或为空");
        }
        chmod($tempFile, 0755);
        return $tempFile;
    }
}

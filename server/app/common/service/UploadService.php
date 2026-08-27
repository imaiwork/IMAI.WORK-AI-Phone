<?php

namespace app\common\service;

use app\common\enum\FileEnum;
use app\common\Jobs\MediaTranscodeJob;
use app\common\model\audio\Audio;
use app\common\model\file\File;
use app\common\service\storage\Driver as StorageDriver;
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

    private static $ffprobeCommands = [
        'ffprobe6',
        'ffprobe',
        '/usr/bin/ffprobe6',
        '/usr/local/bin/ffprobe6',
        '/usr/bin/ffprobe',
        '/usr/local/bin/ffprobe',
        '/opt/ffmpeg/bin/ffprobe',
        '/snap/bin/ffprobe'
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
        int $personaId = 0,
        string $sliceModule = '',
        string $scene = 'persona',
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
            'persona_id' => $personaId,
            'sliceModule' => $sliceModule,
            'scene' => $scene,
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
        int $personaId = 0,
        string $sliceModule = '',
        string $scene = 'persona',
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
            'persona_id' => $personaId,
            'sliceModule' => $sliceModule,
            'scene' => $scene,
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

            self::maybeUploadScreenshotToOss($relativePath, $fullPath);

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
     * 若开启非本地存储，将截图上传到 OSS；成功后删除本地文件。
     *
     * @param string $relativePath 相对路径，无前导斜杠
     * @param string $fullPath 本地绝对路径
     * @return bool true=已上传并删除本地；false=未开启 OSS 或上传失败（降级保留本地）
     */
    private static function maybeUploadScreenshotToOss(string $relativePath, string $fullPath): bool
    {
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
        if ($relativePath === '') {
            return false;
        }

        $storageDefault = (string)ConfigService::get('storage', 'default', 'local');
        if ($storageDefault === 'local') {
            return false;
        }

        if (!is_file($fullPath)) {
            Log::error('截图上传OSS失败：本地文件不存在', ['path' => $relativePath]);
            return false;
        }

        try {
            $config = [
                'default' => $storageDefault,
                'engine' => ConfigService::get('storage') ?? ['local' => []],
            ];
            $filename = basename($relativePath);
            $saveDir = dirname($relativePath);
            if ($saveDir === '.' || $saveDir === '\\') {
                $saveDir = '';
            }

            $storageDriver = new StorageDriver($config);
            $storageDriver->setUploadFileByFileName($fullPath, $filename);
            if (!$storageDriver->upload($saveDir)) {
                Log::error('截图上传OSS失败', [
                    'path' => $relativePath,
                    'error' => $storageDriver->getError() ?: '上传失败',
                ]);
                return false;
            }

            @unlink($fullPath);
            return true;
        } catch (\Throwable $th) {
            Log::error('截图上传OSS异常', [
                'path' => $relativePath,
                'error' => $th->getMessage(),
            ]);
            return false;
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

            $thumbnailResult     = null;
            $preFetchedVideoInfo = null;
            $uploadDuration      = 0.0;

            $isVideo = self::isVideoFile($fileInfo['ext'], $params['autoDetectVideo'] ?? false);
            $sliceModeFlagEarly = (int)($params['ffmpeg'] ?? 0);
            if (!in_array($sliceModeFlagEarly, [0, 1, 2], true)) {
                $sliceModeFlagEarly = 0;
            }
            // 上传前探测：缩略图 / 视频信息 / ffmpeg=2 切割预估时长（OSS 上传后临时文件可能被删）
            $needPreProbe = $isVideo && (
                $params['generateThumbnail']
                || $params['fetchVideoInfo']
                || $sliceModeFlagEarly === 2
            );
            if ($needPreProbe) {
                // 获取本地临时文件路径
                $realPath = null;
                if (method_exists($storageDriver, 'getRealPath')) {
                    $realPath = $storageDriver->getRealPath();
                }
                if (empty($realPath)) {
                    $realPath = $fileInfo['realPath'] ?? $fileInfo['tmp_name'] ?? null;
                }

                Log::info('【upload前处理】临时文件检查', [
                    'realPath'    => $realPath,
                    'file_exists' => $realPath ? file_exists($realPath) : false,
                ]);

                if ($realPath && file_exists($realPath)) {
                    // 缩略图
                    if ($params['generateThumbnail']) {
                        $thumbnailResult = self::handleThumbnail($realPath, $params['thumbnailOptions']);
                    }

                    // 视频信息 / 切割预估时长
                    if ($params['fetchVideoInfo'] || $sliceModeFlagEarly === 2) {
                        try {
                            $videoInfoService = new \app\common\service\VideoInfoService();
                            $preFetchedVideoInfo = $videoInfoService->extractVideoInfo($realPath, 30);
                            if ($sliceModeFlagEarly === 2) {
                                $uploadDuration = (float)($preFetchedVideoInfo['duration'] ?? 0);
                            }
                        } catch (Exception $e) {
                            Log::warning('【upload前】视频信息读取失败，将在upload后重试', [
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                } else {
                    Log::warning('【upload前】临时文件不存在，跳过预读', ['realPath' => $realPath]);
                }
            }


            // 8. 上传文件到OSS/本地（OSS模式会删除本地临时文件）
            if (!$storageDriver->upload($saveDir)) {
                throw new Exception($storageDriver->getError());
            }

            // 9. 保存到数据库
            // 记录本次 ffmpeg=0/1/2 选择、人设/场景与后台通道快照；
            // 后续批次确认只依据入库快照，不接受前端临时更改。
            $sliceModeFlag = $sliceModeFlagEarly;
            $uploadChannel = ($isVideo && $sliceModeFlag > 0)
                ? \app\common\service\transcoding\OssMediaProcessService::mediaProcessMode()
                : 'local';
            $file = self::saveToDatabase([
                'cid'         => $params['cid'],
                'type'        => $params['fileType'],
                'name'        => $fileInfo['name'],
                'uri'         => $fileUri,
                'source'      => $params['source'],
                'source_id'   => $params['sourceId'],
                'slice_mode'  => $sliceModeFlag,
                'persona_id'  => (int)($params['personaId'] ?? 0),
                'scene'       => (string)($params['scene'] ?? 'persona'),
                'process_channel' => $uploadChannel,
                'duration'    => round(max(0, $uploadDuration), 2),
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

            // 10.2 缩略图降级：upload前没生成，upload后再试
            if (!$thumbnailResult && $params['generateThumbnail'] && $isVideo) {
                try {
                    // 优先本地路径，没有才用远程URL
                    $thumbInput = file_exists($localPath)
                        ? $localPath
                        : FileService::getFileUrl($file['uri']);

                    $thumbnailResult = self::handleThumbnail($thumbInput, $params['thumbnailOptions']);

                    if ($thumbnailResult) {
                        $result['thumbnail_path'] = $thumbnailResult['full_url'];
                        $result['thumbnail_url']  = $thumbnailResult['url'];
                    }
                } catch (Exception $e) {
                    Log::error('【upload后】缩略图生成失败', ['error' => $e->getMessage()]);
                }
            }

            // 10.3 视频信息
            if ($params['fetchVideoInfo'] && $isVideo) {
                $videoInfo = $preFetchedVideoInfo;

                // 降级：upload前没读到，upload后再试
                if (!$videoInfo) {
                    try {
                        $videoInfoService = new \app\common\service\VideoInfoService();
                        $input = file_exists($localPath)
                            ? $localPath
                            : FileService::getFileUrl($file['uri']);

                        $videoInfo = $videoInfoService->extractVideoInfo($input, 30);
                    } catch (Exception $e) {
                        Log::error('【upload后】视频信息读取失败', ['error' => $e->getMessage()]);
                    }
                }

                if ($videoInfo) {
                    if (!empty($videoInfo['duration']))        $result['duration'] = (int)$videoInfo['duration'];
                    if (!empty($videoInfo['size']))            $result['size']     = (int)$videoInfo['size'];
                    if (!empty($videoInfo['video']['width']))  $result['width']    = (int)$videoInfo['video']['width'];
                    if (!empty($videoInfo['video']['height'])) $result['height']   = (int)$videoInfo['video']['height'];
                    // 上传前未写入时长时，补写供转码中切割预估
                    if ($sliceModeFlag === 2 && (float)($file['duration'] ?? 0) <= 0 && !empty($videoInfo['duration'])) {
                        File::where('id', (int)$file['id'])->update([
                            'duration' => round((float)$videoInfo['duration'], 2),
                            'update_time' => time(),
                        ]);
                    }
                }
            }

            $storageDefault = ConfigService::get('storage', 'default', 'local');

            // ============== 11. FFmpeg 后处理（按上传 ffmpeg 分流） ==============
            // 上传接口根据 ffmpeg 决定：0不转码 / 1转码 / 2转码后自动分割。
            // isMediaType 不能只看 fileType:/api/upload/file 接口对所有文件都标记成 FILE_TYPE(30),
            // 哪怕实际是 png/jpg/mp4。必须把"后缀属于图片/视频白名单"也算上,才能覆盖通用上传入口
            $fileExt    = strtolower($fileInfo['ext'] ?? '');
            $isImageExt = $fileExt !== '' && in_array($fileExt, (array)config('project.file_image', []), true);
            $isMediaType = $isVideo
                || $isImageExt
                || in_array($params['fileType'], [FileEnum::IMAGE_TYPE, FileEnum::VIDEO_TYPE], true);
            $personaId   = (int)$params['personaId'];
            $ffmpegFlag  = (int)($params['ffmpeg'] ?? 0); // 防御:可能传字符串 "1"/"2"
            Log::channel('ffmpeg')->write(
                "[决策] file_id={$file['id']} ffmpeg={$ffmpegFlag} fileType={$params['fileType']} ext={$fileExt} "
                    . "isVideo=" . ($isVideo ? 'true' : 'false')
                    . " isImageExt=" . ($isImageExt ? 'true' : 'false')
                    . " isMediaType=" . ($isMediaType ? 'true' : 'false')
                    . " personaId={$personaId}"
            );

            // 11.1 决策本次需要做什么（ffmpeg 只决定动作，通道由后台 media_process 决定）
            //   - ffmpeg=0: 不转码、不切割，原样入库（不做任何兜底转码）
            //   - ffmpeg=1: 免费转码；有 persona_id 时转码后入库，否则只转码由前端素材新增
            //   - ffmpeg=2: 免费转码，转码成功后自动发起切割（按切割计费），需 persona_id
            // 转码免费；仅切割在自动发起时按单视频预扣。
            $wantTranscode = false;
            $transcodeReason = '';

            if (($ffmpegFlag === 1 || $ffmpegFlag === 2) && $isMediaType) {
                $wantTranscode = true;
                $transcodeReason = 'ffmpeg-' . $ffmpegFlag;
            }

            // 11.2 准备本地处理文件
            // - 图片：始终本机 ffmpeg，必须有本地文件
            // - 视频 + OSS-MPS：可不依赖本地（探针失败仍可投递）
            // - 视频 + 本机：必须有本地文件
            $processLocalPath = null;
            $useOssMediaProcess = \app\common\service\transcoding\OssMediaProcessService::isEnabled();
            $isImageUpload = $isImageExt
                || (int)($params['fileType'] ?? 0) === FileEnum::IMAGE_TYPE;
            $useOssVideoMps = $useOssMediaProcess && !$isImageUpload && $isVideo;

            if ($wantTranscode && !$useOssVideoMps) {
                try {
                    $processLocalPath = self::prepareLocalFileForFfmpeg(
                        $localPath,
                        $fileUri,
                        $saveDir,
                        $fileName,
                        '处理'
                    );
                } catch (\Throwable $e) {
                    Log::channel('ffmpeg')->write(
                        "[准备本地文件失败] file_id={$file['id']} err=" . $e->getMessage()
                    );
                    $wantTranscode = false;
                }
            } elseif ($wantTranscode && $useOssVideoMps) {
                try {
                    $processLocalPath = self::prepareLocalFileForFfmpeg(
                        $localPath,
                        $fileUri,
                        $saveDir,
                        $fileName,
                        '处理'
                    );
                } catch (\Throwable $e) {
                    Log::channel('ffmpeg')->write(
                        "[OSS-MPS] 本地探针文件不可用，仍按 OSS 路径投递 file_id={$file['id']} err=" . $e->getMessage()
                    );
                }
            }

            // 11.3 投递转码任务
            $transcodeDispatched = false;
            if ($wantTranscode && ($useOssVideoMps || $processLocalPath)) {
                try {
                    $needTranscode = true;
                    $checkReason = 'oss-mps-force';
                    if ($processLocalPath) {
                        try {
                            $checkResult = self::checkTranscodeNeeded($processLocalPath, $params['custom_specs'] ?? null);
                            $needTranscode = (bool)$checkResult['need_transcode'];
                            $checkReason = (string)($checkResult['reason'] ?? '');
                        } catch (\Throwable $e) {
                            $needTranscode = $useOssVideoMps;
                            $checkReason = $useOssVideoMps ? 'probe-failed-fallback-oss' : 'probe-failed';
                            if (!$useOssVideoMps) {
                                throw $e;
                            }
                        }
                    }

                    if ($needTranscode) {
                        self::dispatchTranscodeJob([
                            'file_id'      => $file['id'],
                            'local_path'   => $processLocalPath ?: ($localPath ?: $fileUri),
                            'oss_uri'      => $fileUri,
                            'save_dir'     => $saveDir,
                            'clip'         => $params['clip'] ?? [],
                            'custom_specs' => $params['custom_specs'] ?? null,
                        ]);
                        $transcodeDispatched = true;
                        $modeLabel = $isImageUpload
                            ? 'local-ffmpeg-image'
                            : ($useOssVideoMps ? 'oss-mps-video' : 'local-ffmpeg-video');
                        Log::channel('ffmpeg')->write(
                            "[转码] 任务已投递 file_id={$file['id']} trigger={$transcodeReason} "
                                . "mode={$modeLabel} reason={$checkReason}"
                        );
                    } else {
                        Log::channel('ffmpeg')->write(
                            "[转码跳过] file_id={$file['id']} trigger={$transcodeReason} reason={$checkReason}"
                        );
                        // 已符合规范 = 视同转码成功：必须继续 ffmpeg=1 入库 / ffmpeg=2 自动切割，
                        // 否则不会投递 MediaTranscodeJob，后续链路永久断掉。
                        try {
                            $skipMeta = [
                                'thumbnail_url' => (string)($result['thumbnail_url'] ?? ''),
                                'duration' => (float)($result['duration'] ?? $file['duration'] ?? 0),
                                'width' => (int)($result['width'] ?? 0),
                                'height' => (int)($result['height'] ?? 0),
                            ];
                            MediaTranscodeJob::finalizeSkippedTranscode(
                                (int)$file['id'],
                                (string)$fileUri,
                                $skipMeta
                            );
                            Log::channel('ffmpeg')->write(
                                "[转码跳过后续] file_id={$file['id']} trigger={$transcodeReason}"
                                    . " 已按转码成功继续入库/切割 duration=" . $skipMeta['duration']
                            );
                        } catch (\Throwable $e) {
                            Log::channel('ffmpeg')->write(
                                "[转码跳过后续失败] file_id={$file['id']} trigger={$transcodeReason}"
                                    . " err=" . $e->getMessage()
                            );
                        }
                    }
                } catch (\Throwable $e) {
                    // 转码 check 异常不影响上传主流程
                    Log::channel('ffmpeg')->write(
                        "[转码异常] file_id={$file['id']} err=" . $e->getMessage()
                    );
                }
            }

            // 11.4 OSS 模式 + 没有任何 job 用到本地文件 → 清理掉,省存储
            if ($storageDefault !== 'local' && !$transcodeDispatched && file_exists($localPath)) {
                if (@unlink($localPath)) {
                    Log::channel('ffmpeg')->write("[清理] 无 FFmpeg 任务,删除本地临时文件 file_id={$file['id']}");
                }
                if ($processLocalPath && $processLocalPath !== $localPath && file_exists($processLocalPath)) {
                    @unlink($processLocalPath);
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
     * 上传文件到OSS
     *
     * @param string $localPath 本地文件路径
     * @param string $ossPath OSS路径
     * @return array
     */
    public static function uploadFileToOSS(string $localPath, string $ossPath): array
    {
        try {
            // 获取存储配置
            $config = [
                'default' => ConfigService::get('storage', 'default', 'local'),
                'engine'  => ConfigService::get('storage') ?? ['local' => []],
            ];

            // 使用 StorageDriver 上传
            $storageDriver = new \app\common\service\storage\Driver($config);

            // 设置要上传的本地文件
            $storageDriver->setUploadFileByReal($localPath);

            // 获取文件信息
            $fileInfo = $storageDriver->getFileInfo();

            // 解析OSS路径（去除文件名，只保留目录）
            $pathParts = pathinfo($ossPath);
            $saveDir = $pathParts['dirname'];

            // 上传到OSS
            if (!$storageDriver->upload($saveDir)) {
                return [
                    'success' => false,
                    'error' => $storageDriver->getError()
                ];
            }

            // 获取上传后的文件名
            $fileName = $storageDriver->getFileName();
            $ossFullPath = $saveDir . '/' . str_replace("\\", "/", $fileName);

            return [
                'result' => true,
                'file_path' => $ossFullPath,
                'url' => FileService::getFileUrl($ossFullPath)
            ];
        } catch (Exception $e) {
            Log::error('上传到OSS失败', [
                'local_path' => $localPath,
                'oss_path' => $ossPath,
                'error' => $e->getMessage()
            ]);

            return [
                'result' => false,
                'error' => $e->getMessage()
            ];
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

        $ossFullUrl = FileService::getFileUrl($ossUri, '', true);

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
            'custom_specs' => $options['custom_specs'] ?? null,
            'sliceModule' => $options['sliceModule'] ?? $options['module'] ?? '',
            'personaId' => $options['persona_id'] ?? $options['personaId'] ?? $options['character_ip_id'] ?? 0,
            'userId' => $options['user_id'] ?? $options['userId'] ?? 0,
            'scene' => in_array(($options['scene'] ?? 'persona'), ['ai_creation', 'persona'], true)
                ? ($options['scene'] ?? 'persona')
                : 'persona',
        ];
    }

    private static function prepareLocalFileForFfmpeg(
        string $localPath,
        string $fileUri,
        string $saveDir,
        string $fileName,
        string $scene
    ): string {
        if (ConfigService::get('storage', 'default', 'local') === 'local') {
            return $localPath;
        }

        if (file_exists($localPath)) {
            Log::channel('ffmpeg')->write("[{$scene}准备] 本地副本存在，直接使用: {$localPath}");
            return $localPath;
        }

        $downloadedPath = self::downloadForTranscode($fileUri, $saveDir, $fileName);
        Log::channel('ffmpeg')->write("[{$scene}准备] 从OSS下载临时文件完成: {$downloadedPath}");

        return $downloadedPath;
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
    public static function dispatchTranscodeJob(array $data): void
    {
        try {
            // 入队前标记文件待转码,供生成视频侧的就绪门禁查询
            $fileId = (int)($data['file_id'] ?? 0);
            if ($fileId > 0) {
                \app\common\model\file\File::where('id', $fileId)->update([
                    'transcode_status' => 1,
                    'update_time' => time(),
                ]);
            }

            // 投递到队列
            $queueName = env('QUEUE.TRANSCODING');
            $pushed = Queue::push(
                MediaTranscodeJob::class,
                $data,
                $queueName
            );
            if ($pushed === false) {
                throw new Exception('队列返回失败');
            }

            Log::channel('ffmpeg')->write(
                '转码任务已投递到队列'
                    . ' file_id=' . ($data['file_id'] ?? '')
                    . ' queue=' . ($queueName ?: 'default')
                    . ' job_id=' . (is_scalar($pushed) ? (string)$pushed : json_encode($pushed, JSON_UNESCAPED_UNICODE))
                    . ' local_path=' . ($data['local_path'] ?? '')
                    . ' oss_uri=' . ($data['oss_uri'] ?? '')
            );
        } catch (\Throwable $e) {
            $fileId = (int)($data['file_id'] ?? 0);
            if ($fileId > 0) {
                \app\common\model\file\File::where('id', $fileId)->update([
                    'transcode_status' => 4,
                    'update_time' => time(),
                ]);
            }
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
    /**
     * 处理FFmpeg标准化（同步 standardizeMedia 返回值变更）
     */
    private static function handleFFmpeg(string $localPath, string $saveDir, array $clip = []): ?array
    {
        try {
            $storageDefault = ConfigService::get('storage', 'default', 'local');

            // ✅ standardizeMedia 现在返回 array
            $result = self::standardizeMedia($localPath, $clip ?: null);

            return [
                'url'        => $result['url'],
                'uri'        => FileService::getFileUrl($result['url']),
                'width'      => $result['width']      ?? 0,
                'height'     => $result['height']     ?? 0,
                'media_type' => $result['media_type'] ?? 'unknown',
            ];
        } catch (Exception $e) {
            Log::error('FFmpeg处理失败', [
                'local_path' => $localPath,
                'error'      => $e->getMessage()
            ]);

            $storageDefault = ConfigService::get('storage', 'default', 'local');
            if ($storageDefault !== 'local') {
                try {
                    $url = self::uploadToOSS($localPath, $saveDir);
                    return [
                        'url'        => $url,
                        'uri'        => FileService::getFileUrl($url),
                        'width'      => 0,
                        'height'     => 0,
                        'media_type' => 'unknown',
                    ];
                } catch (Exception $ossException) {
                    Log::error('OSS上传失败', ['error' => $ossException->getMessage()]);
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
    /**
     * 标准化媒体文件（优化版：返回宽高信息）
     *
     * @param string      $inputPath      输入路径（本地绝对路径）
     * @param array|null  $customSpecs    自定义规范
     * @param string|null $targetFileName 指定上传OSS时的目标文件名
     * @return array {
     *     url:        string,  // OSS相对路径 或 本地相对路径
     *     width:      int,     // 转码后宽度（px），失败时为 0
     *     height:     int,     // 转码后高度（px），失败时为 0
     *     media_type: string,  // 'image' | 'video'
     * }
     * @throws Exception
     */
    public static function standardizeMedia(
        string $inputPath,
        ?array $customSpecs = null,
        ?string $targetFileName = null
    ): array {
        $tempOutputPath = null;

        try {
            // ✅ 1. 验证输入文件存在
            if (!file_exists($inputPath)) {
                throw new Exception("输入文件不存在1: {$inputPath}");
            }
            if (filesize($inputPath) === 0) {
                throw new Exception("输入文件为空: {$inputPath}");
            }

            // 2. 获取媒体信息
            $info = self::getMediaInfo($inputPath);

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

            // 6. 提取 ossPath
            $publicPath   = rtrim(public_path(), '/');
            $relativePath = ltrim(str_replace($publicPath, '', $inputPath), '/');
            $ossPath      = ltrim(dirname($relativePath), '/');

            // 7. 确定规范配置 & 媒体类型
            $imageCodecs = ['png', 'apng', 'mjpeg', 'jpeg', 'jpg', 'gif', 'webp', 'bmp', 'tiff'];
            $isImage     = in_array($codecName, $imageCodecs);

            Log::channel('ffmpeg')->write("[文件类型] {$mediaType} 识别为" . ($isImage ? '图片' : '视频') . "类型 codec={$codecName} 帧率={$frameRate}");

            if ($mediaType === 'video' && $isImage) {
                $specs = array_merge(self::DEFAULT_IMAGE_SPECS, $customSpecs['image'] ?? []);
            } elseif ($mediaType === 'video') {
                $specs = array_merge(self::DEFAULT_VIDEO_SPECS, $customSpecs['video'] ?? []);
            } else {
                throw new Exception("不支持的媒体类型: {$mediaType}");
            }

            // 8. 生成临时输出路径（和 inputPath 同目录）
            $inputDir       = dirname($inputPath);
            $tempOutputPath = $inputDir . '/tmp_' . uniqid() . '_' . $finalFileName;

            // 9. 确保输出目录存在且可写
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
                Log::channel('ffmpeg')->write("[转码] 识别为图片类型 codec={$codecName}");
                self::transcodeImage($inputPath, $tempOutputPath, $specs, $info);
            } else {
                self::transcodeVideo($inputPath, $tempOutputPath, $specs, $info);
            }

            // 11. 验证临时输出文件
            if (!file_exists($tempOutputPath)) {
                throw new Exception("转码失败：临时输出文件未生成: {$tempOutputPath}");
            }
            $tempFileSize = filesize($tempOutputPath);
            if ($tempFileSize < 100) {
                @unlink($tempOutputPath);
                throw new Exception("转码失败：临时输出文件过小 ({$tempFileSize} bytes)");
            }

            // ✅ 12. 提取转码后的宽高（在文件移走/上传前读取）
            [$outputWidth, $outputHeight] = self::extractDimensionsFromOutput($tempOutputPath);

            Log::channel('ffmpeg')->write(
                "[转码后宽高] width={$outputWidth} height={$outputHeight} path={$tempOutputPath}"
            );

            if (!$isImage) {
                $outputInfo = self::getMediaInfo($tempOutputPath);
                $outputRotation = self::extractRotation($outputInfo);
                Log::channel('ffmpeg')->write(
                    "[转码后方向] rotation={$outputRotation} normalized=" . self::normalizeRotation($outputRotation)
                );

                if (self::normalizeRotation($outputRotation) !== 0) {
                    throw new Exception("转码后仍存在旋转元数据 rotation={$outputRotation}");
                }
            }

            // 13. 根据存储模式处理
            $storageDefault = ConfigService::get('storage', 'default', 'local');

            if ($storageDefault !== 'local') {
                // OSS模式：上传临时文件到 OSS
                $ossUrl = self::uploadToOSS($tempOutputPath, $ossPath, $finalFileName);

                Log::channel('ffmpeg')->write("[OSS上传] ✅ 转码文件已上传: {$ossUrl}");

                // 上传后、删本地前截封面（避免入库时本地已空只能下半截 mp4）
                $thumbnailUrl = '';
                $outputDuration = 0.0;
                if (!$isImage && is_file($tempOutputPath)) {
                    try {
                        $meta = self::getMediaInfo($tempOutputPath);
                        $outputDuration = round((float)($meta['format']['duration'] ?? 0), 2);
                    } catch (\Throwable $e) {
                        $outputDuration = 0.0;
                    }
                    $seek = $outputDuration > 0 ? min(0.3, max(0.01, $outputDuration * 0.15)) : 0.1;
                    $thumbnailUrl = \app\common\service\ffmpeg\MaterialService::makeVideoThumbnail($tempOutputPath, $seek);
                }

                if (@unlink($tempOutputPath)) {
                    Log::channel('ffmpeg')->write("[清理] ✅ 已删除临时转码文件: {$tempOutputPath}");
                }

                if ($inputPath !== $tempOutputPath && file_exists($inputPath)) {
                    @unlink($inputPath);
                }

                return [
                    'url'           => $ossUrl,
                    'width'         => $outputWidth,
                    'height'        => $outputHeight,
                    'duration'      => $outputDuration,
                    'thumbnail_url' => $thumbnailUrl,
                    'media_type'    => $isImage ? 'image' : 'video',
                ];
            } else {
                // 本地模式：rename 到最终路径
                $finalOutputPath = $inputDir . '/' . $finalFileName;

                if (!rename($tempOutputPath, $finalOutputPath)) {
                    if (!copy($tempOutputPath, $finalOutputPath)) {
                        @unlink($tempOutputPath);
                        throw new Exception("转码文件替换失败: {$tempOutputPath} => {$finalOutputPath}");
                    }
                    @unlink($tempOutputPath);
                }

                if (!file_exists($finalOutputPath) || filesize($finalOutputPath) < 100) {
                    throw new Exception("转码文件替换后验证失败: {$finalOutputPath}");
                }

                $finalRelativePath = $ossPath . '/' . $finalFileName;
                Log::channel('ffmpeg')->write("[本地] ✅ 转码文件已就位: {$finalOutputPath}");

                $thumbnailUrl = '';
                $outputDuration = 0.0;
                if (!$isImage) {
                    try {
                        $meta = self::getMediaInfo($finalOutputPath);
                        $outputDuration = round((float)($meta['format']['duration'] ?? 0), 2);
                    } catch (\Throwable $e) {
                        $outputDuration = 0.0;
                    }
                    $seek = $outputDuration > 0 ? min(0.3, max(0.01, $outputDuration * 0.15)) : 0.1;
                    $thumbnailUrl = \app\common\service\ffmpeg\MaterialService::makeVideoThumbnail($finalOutputPath, $seek);
                }

                return [
                    'url'           => $finalRelativePath,
                    'width'         => $outputWidth,
                    'height'        => $outputHeight,
                    'duration'      => $outputDuration,
                    'thumbnail_url' => $thumbnailUrl,
                    'media_type'    => $isImage ? 'image' : 'video',
                ];
            }
        } catch (Exception $e) {
            if ($tempOutputPath && file_exists($tempOutputPath)) {
                @unlink($tempOutputPath);
            }
            throw new Exception("媒体标准化失败: " . $e->getMessage());
        }
    }

    /**
     * 标准化视频到本地临时文件，供切片等后续处理使用。
     *
     * 不上传、不替换原文件；默认不限制时长，避免切片前丢失内容。
     */
    public static function standardizeVideoToLocal(
        string $inputPath,
        string $outputPath,
        ?array $customSpecs = null
    ): array {
        $tempOutputPath = null;

        try {
            if (!file_exists($inputPath)) {
                throw new Exception("输入文件不存在2: {$inputPath}");
            }
            if (filesize($inputPath) === 0) {
                throw new Exception("输入文件为空: {$inputPath}");
            }

            $info = self::getMediaInfo($inputPath);
            $videoStream = null;
            foreach ($info['streams'] ?? [] as $stream) {
                if (($stream['codec_type'] ?? '') === 'video') {
                    $videoStream = $stream;
                    break;
                }
            }

            if (!$videoStream) {
                throw new Exception("未找到视频流");
            }

            $outputDir = dirname($outputPath);
            if (!is_dir($outputDir) && !mkdir($outputDir, 0755, true)) {
                throw new Exception("无法创建输出目录: {$outputDir}");
            }
            if (!is_writable($outputDir)) {
                throw new Exception("输出目录无写权限: {$outputDir}");
            }

            $videoSpecs = $customSpecs['video'] ?? [];
            $specs = array_merge(self::DEFAULT_VIDEO_SPECS, $videoSpecs);
            if (!array_key_exists('duration', $videoSpecs)) {
                $specs['duration'] = 0;
            }

            $tempOutputPath = $outputDir . '/tmp_' . uniqid() . '_' . basename($outputPath);
            self::transcodeVideo($inputPath, $tempOutputPath, $specs, $info);

            if (!file_exists($tempOutputPath)) {
                throw new Exception("标准化失败：临时输出文件未生成: {$tempOutputPath}");
            }

            $outputSize = filesize($tempOutputPath);
            if ($outputSize < 100) {
                @unlink($tempOutputPath);
                throw new Exception("标准化失败：输出文件过小 ({$outputSize} bytes)");
            }

            [$outputWidth, $outputHeight] = self::extractDimensionsFromOutput($tempOutputPath);
            $outputInfo = self::getMediaInfo($tempOutputPath);
            $outputRotation = self::extractRotation($outputInfo);
            if (self::normalizeRotation($outputRotation) !== 0) {
                throw new Exception("标准化后仍存在旋转元数据 rotation={$outputRotation}");
            }

            if (file_exists($outputPath)) {
                @unlink($outputPath);
            }

            if (!rename($tempOutputPath, $outputPath)) {
                if (!copy($tempOutputPath, $outputPath)) {
                    @unlink($tempOutputPath);
                    throw new Exception("标准化文件移动失败: {$tempOutputPath} => {$outputPath}");
                }
                @unlink($tempOutputPath);
            }

            return [
                'path' => $outputPath,
                'width' => $outputWidth,
                'height' => $outputHeight,
                'duration' => (float)($outputInfo['format']['duration'] ?? 0),
                'media_type' => 'video',
            ];
        } catch (Exception $e) {
            if ($tempOutputPath && file_exists($tempOutputPath)) {
                @unlink($tempOutputPath);
            }
            throw new Exception("视频标准化失败: " . $e->getMessage());
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

        if (filesize($filePath) === 0) {
            throw new Exception("文件为空: " . $filePath);
        }

        // 优先使用 ffprobe6，与 ffmpeg6 保持一致；找不到再 fallback 到 ffprobe
        $ffprobeBin = '';
        try {
            foreach (self::$ffprobeCommands as $bin) {
                $output = shell_exec($bin . ' -version 2>/dev/null');
                if (!empty($output) && (
                    strpos($output, 'ffprobe version') !== false ||
                    strpos($output, 'ffprobe6 version') !== false
                )) {
                    $ffprobeBin = $bin;
                    break;
                }
            }
        } catch (\Throwable $e) {
            Log::channel('ffmpeg')->write('[ffprobe循环异常] ' . $e->getMessage());
        }

        if (empty($ffprobeBin)) {
            throw new Exception("ffprobe/ffprobe6 未安装或不在 PATH 中");
        }

        $ffprobeCommand = "{$ffprobeBin} -v quiet "
            . "-show_streams -show_format -print_format json "
            . escapeshellarg($filePath) . " 2>/dev/null";

        $output = shell_exec($ffprobeCommand);

        // ✅ 如果 quiet 模式拿不到输出，降级用 error 级别重试一次（方便排查）
        if ($output === null || trim($output) === '') {
            $ffprobeCommand = "{$ffprobeBin} -v error "
                . "-show_streams -show_format -print_format json "
                . escapeshellarg($filePath) . " 2>&1";
            $output = shell_exec($ffprobeCommand);
        }

        if ($output === null || trim($output) === '') {
            throw new Exception(
                "ffprobe 无任何输出，可能被 disable_functions 禁用或无执行权限。命令: {$ffprobeCommand}"
            );
        }

        // ✅ 只截取 JSON 部分（防止 stderr 混入导致 json_decode 失败）
        $jsonStart = strpos($output, '{');
        $jsonEnd   = strrpos($output, '}');
        if ($jsonStart !== false && $jsonEnd !== false) {
            $output = substr($output, $jsonStart, $jsonEnd - $jsonStart + 1);
        }

        $info = json_decode($output, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception(
                "解析媒体信息失败: " . json_last_error_msg()
                    . " | 原始输出(前500字符): " . substr($output, 0, 500)
            );
        }

        return $info;
    }


    /**
     * 判断图片是否符合规范
     */
    private static function isImageCompliant(array $info, array $specs): bool
    {
        // ✅ 修复：使用 findVideoStream() 而不是 streams[0]
        $stream = self::findVideoStream($info);
        if (!$stream) {
            return false;
        }

        $format           = $stream['codec_name'] ?? '';
        $targetResolution = self::calculateTargetResolution($info, $specs);
        $targetDims       = explode('x', $targetResolution);

        return (
            isset($stream['width'])  && $stream['width']  <= intval($targetDims[0]) &&
            isset($stream['height']) && $stream['height'] <= intval($targetDims[1]) &&
            in_array(strtolower($format), ['jpg', 'jpeg', 'png', 'mjpeg'])
        );
    }

    /**
     * ✅ 新增：从 streams 中找到视频流
     * 修复 streams[0] 可能是音频流的问题（如小米手机录制的视频）
     */
    private static function findVideoStream(array $info): ?array
    {
        if (empty($info['streams'])) {
            return null;
        }
        foreach ($info['streams'] as $stream) {
            if (($stream['codec_type'] ?? '') === 'video') {
                return $stream;
            }
        }
        return null;
    }

    /**
     * ✅ 新增：从 streams 中找到音频流
     */
    private static function findAudioStream(array $info): ?array
    {
        if (empty($info['streams'])) {
            return null;
        }
        foreach ($info['streams'] as $stream) {
            if (($stream['codec_type'] ?? '') === 'audio') {
                return $stream;
            }
        }
        return null;
    }

    /**
     * ✅ 新增：从媒体信息中提取旋转角度
     * 优先读 side_data_list > Display Matrix，其次读 stream.rotation
     * 注意：ffprobe 输出的旋转值可能是 -90（对应实际 270 度）
     */
    private static function extractRotation(array $info): ?int
    {
        if (empty($info['streams'])) {
            return null;
        }

        foreach ($info['streams'] as $stream) {
            if (($stream['codec_type'] ?? '') !== 'video') {
                continue;
            }

            // 优先从 side_data_list > Display Matrix 读取（iPhone / 微信视频最常见）
            if (!empty($stream['side_data_list'])) {
                foreach ($stream['side_data_list'] as $sideData) {
                    if (
                        ($sideData['side_data_type'] ?? '') === 'Display Matrix'
                        && isset($sideData['rotation'])
                    ) {
                        $val = intval($sideData['rotation']);
                        Log::channel('ffmpeg')->write("[extractRotation] 来源=Display Matrix rotation={$val}");
                        return $val;
                    }
                }
            }

            // 其次从 stream.tags.rotate 读取（部分安卓视频）
            if (isset($stream['tags']['rotate'])) {
                $val = intval($stream['tags']['rotate']);
                Log::channel('ffmpeg')->write("[extractRotation] 来源=tags.rotate rotation={$val}");
                return $val;
            }

            // 最后从 stream.rotation 读取（旧版 ffprobe 格式）
            if (isset($stream['rotation'])) {
                $val = intval($stream['rotation']);
                Log::channel('ffmpeg')->write("[extractRotation] 来源=stream.rotation rotation={$val}");
                return $val;
            }
        }

        Log::channel('ffmpeg')->write("[extractRotation] 未找到旋转信息，返回 null");
        return null;
    }

    /**
     * 将 ffprobe 读取到的旋转角度归一化为 0/90/180/270。
     */
    private static function normalizeRotation(?int $rotation): int
    {
        if ($rotation === null) {
            return 0;
        }

        $normalized = $rotation % 360;
        if ($normalized < 0) {
            $normalized += 360;
        }

        return $normalized;
    }

    /**
     * 将旋转元数据转换成 FFmpeg 滤镜，把方向烘焙进输出画面。
     */
    private static function buildRotationFilters(?int $rotation): array
    {
        switch (self::normalizeRotation($rotation)) {
            case 90:
                return ['transpose=cclock'];
            case 180:
                return ['hflip', 'vflip'];
            case 270:
                return ['transpose=clock'];
            default:
                return [];
        }
    }

    /**
     * 判断视频是否符合规范
     */
    private static function isVideoCompliant(array $info, array $specs): bool
    {
        if (empty($info['streams'])) {
            return false;
        }

        $specs  = array_merge(self::DEFAULT_VIDEO_SPECS, $specs);

        // ✅ 修复：使用 findVideoStream() 而不是 streams[0]
        $stream = self::findVideoStream($info);
        if (!$stream) {
            return false;
        }

        $rotation = self::extractRotation($info);
        if (self::normalizeRotation($rotation) !== 0) {
            Log::channel('ffmpeg')->write("[视频规范] 检测到旋转元数据 rotation={$rotation}，需要转码烘焙方向");
            return false;
        }

        $targetResolution = self::calculateTargetResolution($info, $specs, $rotation);
        $targetDims       = explode('x', $targetResolution);

        // ✅ 修复：安全解析帧率，避免除零或格式异常
        $frameRateParts = explode('/', $stream['r_frame_rate'] ?? '0/1');
        $frameRate      = (isset($frameRateParts[1]) && intval($frameRateParts[1]) > 0)
            ? intval($frameRateParts[0]) / intval($frameRateParts[1])
            : intval($frameRateParts[0]);

        if ($specs['duration'] < 1) {
            return (
                isset($stream['codec_name']) && $stream['codec_name'] === $specs['video_codec'] &&
                isset($stream['width'])      && $stream['width']  <= intval($targetDims[0]) &&
                isset($stream['height'])     && $stream['height'] <= intval($targetDims[1]) &&
                round($frameRate) == $specs['frame_rate']
            );
        } else {
            return (
                isset($stream['codec_name']) && $stream['codec_name'] === $specs['video_codec'] &&
                isset($stream['width'])      && $stream['width']  <= intval($targetDims[0]) &&
                isset($stream['height'])     && $stream['height'] <= intval($targetDims[1]) &&
                round($frameRate) == $specs['frame_rate'] &&
                isset($stream['duration'])   && floatval($stream['duration']) < $specs['duration']
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
            throw new Exception("输入文件不存在3: " . $inputPath);
        }

        // 2. 确定编码器：保持与原后缀一致，防止 OSS 驱动因 MIME 更改后缀
        // webp 不能用 png 编码器写回 .webp(会转码失败 → 分辨率预检死循环重投)
        $extension = strtolower(pathinfo($inputPath, PATHINFO_EXTENSION));
        $vcodec = 'png';
        if (in_array($extension, ['jpg', 'jpeg'], true)) {
            $vcodec = 'mjpeg';
        } elseif ($extension === 'webp') {
            $vcodec = 'libwebp';
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
        [$tW, $tH] = explode('x', $resolution);
        $maxSide = max($tW, $tH);

        // 3. 确定目标画布尺寸 (强制 9:16 比例)
        if ($maxSide >= 2000) {
            // 情况 1：长边 >= 2000，固定为标准竖屏
            $tW = 1080;
            $tH = 1920;
        } else {
            $tH = $maxSide;
            $tW = (int)($tH * 9 / 16);
        }
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

        $filter = sprintf(
            "split[bg_raw][fg_raw];" .
                "[bg_raw]scale=%d:%d,boxblur=40:10[bg];" .
                "[fg_raw]scale=%d:%d:force_original_aspect_ratio=decrease[fg];" .
                "[bg][fg]overlay=(W-w)/2:(H-h)/2",
            $tW,
            $tH,
            $tW,
            $tH
        );

        $ffmpegParts = [
            "nice -n 19 ffmpeg6",
            "-threads 1",
            "-i " . escapeshellarg($inputPath),
            "-filter_complex " . escapeshellarg($filter),
            "-vframes 1",      // 确保只输出一帧
            "-c:v " . $vcodec,
            "-y",              // 强制覆盖
            escapeshellarg($outputPath)
        ];

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

        $inputSize = filesize($inputPath);
        Log::channel('ffmpeg')->write("[转码成功] 图片输出大小: {$outputSize} bytes | 输入大小: {$inputSize} bytes | 路径: {$outputPath}");
    }


    private static function transcodeVideo(string $inputPath, string $outputPath, array $specs, array $info): void
    {
        // 1. 验证输入文件
        if (!file_exists($inputPath)) {
            throw new Exception("输入文件不存在4: " . $inputPath);
        }
        $inputSize = filesize($inputPath);
        if ($inputSize === 0) {
            throw new Exception("输入文件为空: " . $inputPath);
        }

        // 2. 获取视频时长
        $duration = null;
        if (isset($info['format']['duration'])) {
            $duration = floatval($info['format']['duration']);
        }
        if ($duration !== null && $duration > 10800) {
            throw new Exception("视频时长超过3小时（{$duration}s），拒绝转码: " . $inputPath);
        }

        // 3. 读取旋转角度，后续手动烘焙到画面里，避免播放器二次解释方向元数据。
        $rotation = self::extractRotation($info);
        $normalizedRotation = self::normalizeRotation($rotation);
        $rotationFilters = self::buildRotationFilters($rotation);
        Log::channel('ffmpeg')->write("[transcodeVideo] rotation={$rotation} normalized={$normalizedRotation}");

        // 4. 计算目标分辨率（传入 rotation，内部会先互换宽高再计算）
        $targetRes = self::calculateTargetResolution($info, $specs, $rotation);
        [$tW, $tH] = explode('x', $targetRes);

        // 5. 确保输出目录存在且可写
        $outputDir = dirname($outputPath);
        if (!is_dir($outputDir)) {
            if (!mkdir($outputDir, 0755, true)) {
                throw new Exception("无法创建输出目录: " . $outputDir);
            }
        }
        if (!is_writable($outputDir)) {
            throw new Exception("输出目录没有写权限: " . $outputDir);
        }

        // 6. 磁盘空间检查（至少保留 2GB）
        $freeBytes = disk_free_space($outputDir);
        if ($freeBytes !== false && $freeBytes < 2 * 1024 * 1024 * 1024) {
            throw new Exception("磁盘空间不足（剩余 " . round($freeBytes / 1024 / 1024) . "MB），拒绝转码");
        }

        // 7. 构建滤镜链：先手动烘焙旋转方向，再做 SAR、缩放和偶数对齐。
        $videoFilters = $rotationFilters;

        // 7.1 修正 SAR（像素宽高比），防止画面拉伸
        $videoFilters[] = "scale=iw*sar:ih";
        $videoFilters[] = "setsar=1:1";

        // 7.2 等比缩放到目标分辨率
        $videoFilters[] = "scale='min({$tW},iw)':'min({$tH},ih)':force_original_aspect_ratio=decrease";

        // 7.3 pad 确保宽高为偶数（H264 强制要求）
        $videoFilters[] = "pad=ceil(iw/2)*2:ceil(ih/2)*2";
        Log::channel('ffmpeg')->write(
            "[方向修正] rotation={$rotation} normalized={$normalizedRotation} filters=" . implode(',', $videoFilters)
        );

        // 8. 构建 FFmpeg 命令
        // 禁用 FFmpeg 自动旋转，按上面的滤镜显式旋正画面；输出端清掉旋转元数据。
        $ffmpegParts = [
            "nice -n 19",
            "ffmpeg6 -threads 1",
        ];

        if ($normalizedRotation !== 0) {
            $ffmpegParts[] = "-noautorotate";
            $ffmpegParts[] = "-display_rotation:v:0 0";
        }

        $ffmpegParts = array_merge($ffmpegParts, [
            "-i " . escapeshellarg($inputPath),
            "-c:v libx264",
            "-crf 28",
            "-preset veryfast",
            "-c:a aac",
            "-b:a 128k",
            "-movflags +faststart",
            "-x264-params threads=1",
            "-vf " . escapeshellarg(implode(',', $videoFilters)),
            "-map_metadata -1",
            "-metadata:s:v:0 rotate=0",
        ]);

        // 8.1 限制时长（仅在明确指定时生效）
        if (isset($specs['duration']) && $specs['duration'] > 0) {
            $ffmpegParts[] = "-t " . intval($specs['duration']);
        }

        // 8.2 帧率
        if (isset($specs['frame_rate']) && $specs['frame_rate'] > 0) {
            $ffmpegParts[] = "-r " . intval($specs['frame_rate']);
        }

        // 8.3 像素格式
        if (isset($specs['pixel_format'])) {
            $ffmpegParts[] = "-pix_fmt " . escapeshellarg($specs['pixel_format']);
        }

        // 8.4 码率
        if (isset($specs['bit_rate'])) {
            $ffmpegParts[] = "-b:v " . escapeshellarg($specs['bit_rate']);
        }

        // 8.5 覆盖输出
        $ffmpegParts[] = "-y";
        $ffmpegParts[] = escapeshellarg($outputPath);

        $ffmpegCommand = implode(" ", $ffmpegParts);
        Log::channel('ffmpeg')->write('[转码指令] ' . $ffmpegCommand);

        // 9. 执行转码（600s 超时）
        $output = self::execWithTimeout($ffmpegCommand, 600);

        // 10. 验证输出文件
        if (!file_exists($outputPath)) {
            Log::channel('ffmpeg')->write('[ffmpeg stderr] ' . $output);
            throw new Exception(
                "视频转码失败，输出文件未生成\n"
                    . "命令: " . $ffmpegCommand . "\n"
                    . "输出: " . $output
            );
        }

        $outputSize = filesize($outputPath);
        if ($outputSize < 1000) {
            @unlink($outputPath);
            throw new Exception(
                "视频转码失败，输出文件过小 ({$outputSize} bytes)\n"
                    . "命令: " . $ffmpegCommand . "\n"
                    . "输出: " . $output
            );
        }

        Log::channel('ffmpeg')->write(
            "[转码完成] 输出: {$outputPath}"
                . " 大小: " . round($outputSize / 1024 / 1024, 2) . "MB"
                . " 输入: {$inputPath}"
                . " 大小: " . round($inputSize / 1024 / 1024, 2) . "MB"
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
        $stream = self::findVideoStream($info);
        if (!$stream) {
            throw new Exception("未找到视频流，无法计算分辨率");
        }

        $originalWidth  = $stream['width']  ?? 0;
        $originalHeight = $stream['height'] ?? 0;

        Log::channel('ffmpeg')->write(
            "[calculateTargetResolution] 存储尺寸 width={$originalWidth} height={$originalHeight} rotation={$rotation}"
        );

        if ($originalWidth == 0 || $originalHeight == 0) {
            throw new Exception("无法获取媒体尺寸信息");
        }

        // ✅ 旋转90/270时，先把逻辑宽高互换，再计算缩放比例
        // 原因：存储尺寸是 1920x1080，但旋转90°后逻辑显示尺寸是 1080x1920
        $normalizedRotation = self::normalizeRotation($rotation);
        $needSwap = in_array($normalizedRotation, [90, 270], true);
        if ($needSwap) {
            [$originalWidth, $originalHeight] = [$originalHeight, $originalWidth];
            Log::channel('ffmpeg')->write(
                "[calculateTargetResolution] 宽高互换后 width={$originalWidth} height={$originalHeight}"
            );
        }

        $maxDimension    = max($originalWidth, $originalHeight);
        $twoKThreshold   = $specs['max_dimension']    ?? 2000;
        $fullHdThreshold = 1080;
        $twoKDimension   = $specs['two_k_dimension']  ?? 1980;
        $fullHdDimension = $specs['target_dimension'] ?? 1080;
        $targetLongSide  = null;

        if ($maxDimension > $twoKThreshold) {
            $targetLongSide = $twoKDimension;
        } elseif ($maxDimension > $fullHdThreshold) {
            $targetLongSide = $fullHdDimension;
        }

        if ($targetLongSide === null) {
            $finalWidth  = $originalWidth;
            $finalHeight = $originalHeight;
        } else {
            $scaleRatio  = $targetLongSide / $maxDimension;
            $finalWidth  = (int)round($originalWidth  * $scaleRatio);
            $finalHeight = (int)round($originalHeight * $scaleRatio);
        }

        // 确保宽高为偶数（H264 编码要求）
        $toEvenDimension = static function (int $dimension): int {
            $dimension = max(2, $dimension);
            return $dimension % 2 === 0 ? $dimension : $dimension - 1;
        };

        $finalWidth  = $toEvenDimension($finalWidth);
        $finalHeight = $toEvenDimension($finalHeight);

        Log::channel('ffmpeg')->write(
            "[calculateTargetResolution] 目标分辨率 {$finalWidth}x{$finalHeight}"
        );

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

    /**
     * 从转码输出文件中提取宽高
     * 复用 getMediaInfo + findVideoStream，失败时静默返回 [0, 0]
     *
     * @param string $filePath 本地文件绝对路径
     * @return array [width, height]
     */
    private static function extractDimensionsFromOutput(string $filePath): array
    {
        try {
            $info   = self::getMediaInfo($filePath);
            $stream = self::findVideoStream($info);

            if ($stream && isset($stream['width'], $stream['height'])) {
                return [(int)$stream['width'], (int)$stream['height']];
            }
        } catch (\Throwable $e) {
            Log::channel('ffmpeg')->write(
                "[宽高提取失败] path={$filePath} error=" . $e->getMessage()
            );
        }

        return [0, 0];
    }


    /**
     * 根据来源下载并投递转码任务
     *
     * @param string $url 远程URL
     * @param string $type 类型: image 或 video
     * @param int $cid 分类ID
     * @param int $sourceId 来源ID
     * @param int $source 来源类型
     * @return array
     * @throws Exception
     */
    public static function transcodeBySource(
        string $url,
        string $type = 'video',
        int $cid = 0,
        int $sourceId = 0,
        int $source = FileEnum::SOURCE_ADMIN,
        int $timeout = 0
    ): array {
        if (empty($url)) {
            throw new Exception("URL不能为空");
        }

        // 获取存储配置，后续按本地/OSS分流
        $config = [
            'default' => ConfigService::get('storage', 'default', 'local'),
            'engine'  => ConfigService::get('storage')
        ];
        $ossUri = '';

        $publicRoot = rtrim(public_path(), '/\\') . '/';
        $urlPath = (string)(parse_url($url, PHP_URL_PATH) ?: '');
        $localSourceUri = '';
        $localSourcePath = '';
        if ($config['default'] === 'local' && $urlPath !== '') {
            $appHost = (string)config('app.app_host');
            $appHostName = (string)(parse_url($appHost, PHP_URL_HOST) ?: '');
            $urlHostName = (string)(parse_url($url, PHP_URL_HOST) ?: '');
            $candidateUri = ltrim($urlPath, '/\\');
            $isSameHost = $appHostName !== '' && strcasecmp($urlHostName, $appHostName) === 0;
            $isRelativeLocalUpload = $urlHostName === '' && str_starts_with($candidateUri, 'uploads/');
            if ($isSameHost || $isRelativeLocalUpload) {
                $localSourceUri = $candidateUri;
                $localSourcePath = $publicRoot . $candidateUri;
            }
        }

        // 确定保存目录和文件类型枚举
        $typePath = $type === 'video' ? 'video' : 'images';
        $fileTypeEnum = $type === 'video' ? FileEnum::VIDEO_TYPE : FileEnum::IMAGE_TYPE;
        $dateDir = date('Ymd');
        $saveDir = "uploads/{$typePath}/{$dateDir}";

        // 处理文件名和后缀
        $filename = basename(parse_url($url, PHP_URL_PATH) ?: '');
        if (empty($filename) || !str_contains(substr($filename, -7), '.')) {
            $filename = date('YmdHis') . md5(uniqid() . time());
            $filename .= ($type === 'video' ? '.mp4' : '.jpg');
        }

        $fileUri = $localSourceUri !== '' ? $localSourceUri : ($saveDir . '/' . $filename);
        $localPath = $localSourcePath !== '' ? $localSourcePath : ($publicRoot . ltrim($fileUri, '/\\'));
        if ($localSourceUri !== '') {
            $saveDir = ltrim(dirname($localSourceUri), '/\\');
        }

        $fileSize = 0;
        // 下载/抓取文件
        if ($config['default'] == 'local') {
            $directory = dirname($localPath);
            if (!is_dir($directory)) {
                mkdir($directory, 0777, true);
            }
            Log::channel('ffmpeg')->write("[Source转码投递] 类型1:{$type} | URL:{$url} | 本地路径:{$localPath}");

            if ($localSourcePath === '') {
                if ($timeout > 0) {
                    if (!FileService::streamDownloadToFile($url, $localPath, $timeout)) {
                        throw new Exception("远程文件下载超时或失败: {$url}");
                    }
                } else {
                    $downloadedPath = download_file($url, rtrim($directory, '/\\') . '/', $filename);
                    if (empty($downloadedPath) || !is_file($localPath) || filesize($localPath) <= 0) {
                        throw new Exception("远程文件下载到本地失败: {$url}");
                    }
                }
            } elseif (!is_file($localPath) || filesize($localPath) <= 0) {
                throw new Exception("本地素材文件不存在或为空: {$localPath}");
            }
            $fileSize = self::localFileSize($localPath);
        } else {
            $storageDriver = new StorageDriver($config);
            if ($timeout > 0) {
                $tmp = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
                    . 'dl_' . md5($url . microtime(true)) . '_' . $filename;
                if (!FileService::streamDownloadToFile($url, $tmp, $timeout)) {
                    throw new Exception("远程文件下载超时或失败: {$url}");
                }
                $fileSize = self::localFileSize($tmp);
                $storageDriver->setUploadFileByReal($tmp);
                if (!$storageDriver->upload($saveDir)) {
                    @unlink($tmp);
                    throw new Exception("第三方存储上传失败: " . $storageDriver->getError());
                }
                $savedName = (string)$storageDriver->getFileName();
                if ($savedName !== '') {
                    $fileUri = $saveDir . '/' . $savedName;
                }
                $ossUri = FileService::getFileUrl($fileUri);
                $localPath = self::downloadForTranscode($fileUri, $saveDir, $savedName !== '' ? $savedName : $filename);
                @unlink($tmp);
            } else {
                if (!$storageDriver->fetch($url, $fileUri)) {
                    throw new Exception("第三方存储抓取失败: " . $storageDriver->getError());
                }
                $ossUri = FileService::getFileUrl($fileUri);
                $localPath = self::downloadForTranscode($fileUri, $saveDir, $filename);
                if ($fileSize <= 0) {
                    $fileSize = self::localFileSize($localPath);
                }
            }
            Log::channel('ffmpeg')->write("[Source转码投递] 类型2:{$type} | URL:{$url} | 本地路径:{$fileUri}");
        }

        // 5. 写入数据库记录
        $file = self::saveToDatabase([
            'cid'       => $cid,
            'type'      => $fileTypeEnum,
            'name'      => self::truncateFileName($filename),
            'uri'       => $fileUri,
            'source'    => $source,
            'source_id' => $sourceId,
        ]);

        // 6. 投递转码任务
        self::dispatchTranscodeJob([
            'file_id'      => $file['id'],
            'local_path'   => $localPath, // 本地绝对路径
            'oss_uri'      => $fileUri,   // 云端/相对路径
            'save_dir'     => $saveDir,
            'clip'         => [],
            'custom_specs' => null,
        ]);

        Log::channel('ffmpeg')->write("[Source转码投递] 类型:{$type} | URL:{$url} | FileID:{$file['id']}");

        if ($fileSize <= 0) {
            $fileSize = self::localFileSize($localPath);
        }

        return [
            'id'  => $file['id'],
            'url' => FileService::getFileUrl($fileUri),
            'uri' => $localPath,
            'oss_uri' => $ossUri,
            'size' => $fileSize,
        ];
    }

    private static function localFileSize(string $path): int
    {
        if ($path === '' || !is_file($path)) {
            return 0;
        }
        $size = filesize($path);
        return ($size !== false && $size > 0) ? (int)$size : 0;
    }

    /**
     * 远程素材转码入库，返回可写入业务表的访问地址
     *
     * @param string $url 远程文件地址
     * @param string $type 类型: video 或 image
     * @param int $userId 用户ID（写入文件来源 source_id）
     * @param int $cid 分类ID
     * @param int $timeout 下载超时秒数，0 表示沿用原路径（无硬超时）
     * @param int|null $outSize 上传前算出的字节数
     * @return string
     */
    public static function transcodeRemoteFileBySource(
        string $url,
        string $type = 'video',
        int $userId = 0,
        int $cid = 0,
        int $timeout = 0,
        ?int &$outSize = null
    ): string {
        $outSize = 0;
        if (empty($url)) {
            return '';
        }
        if (!str_contains($url, 'http')) {
            return $url;
        }

        $transRes = self::transcodeBySource($url, $type, $cid, $userId, FileEnum::SOURCE_USER, $timeout);
        $outSize = (int)($transRes['size'] ?? 0);
        $path = !empty($transRes['oss_uri']) ? $transRes['oss_uri'] : ($transRes['url'] ?? '');
        if (!empty($path)) {
            return FileService::getFileUrl($path);
        }
        return $path ?: '';
    }

    /**
     * 分辨率超标素材自动投递转码。
     *
     * 用于下发前门禁:发现超 2000 的素材时,按 uri 找回 iw_file 并入队,
     * 让任务保持"生成中"等转码完成后再下发,而不是直接标失败。
     *
     * @param array<int, array{url:string,type?:string,width?:int,height?:int}> $violations
     * @return array{
     *   dispatched: array<int, array{file_id:int,uri:string}>,
     *   pending: array<int, array{file_id:int,uri:string}>,
     *   skipped: array<int, array{uri:string,reason:string}>,
     *   unrecoverable: array<int, array{file_id?:int,uri:string,reason:string}>
     * }
     */
    public static function dispatchTranscodeForOversizedUrls(array $violations): array
    {
        $result = [
            'dispatched' => [],
            'pending' => [],
            'skipped' => [],
            // 已失败/转码后仍超标:不可再死循环重投
            'unrecoverable' => [],
        ];

        $seen = [];
        foreach ($violations as $item) {
            $url = trim((string)($item['url'] ?? ''));
            if ($url === '') {
                continue;
            }

            $uri = $url;
            if (str_starts_with($uri, 'http')) {
                $path = parse_url($uri, PHP_URL_PATH) ?: '';
                $uri = ltrim($path, '/');
            } else {
                $uri = ltrim($uri, '/');
            }
            if ($uri === '' || isset($seen[$uri])) {
                continue;
            }
            $seen[$uri] = true;

            $file = File::where('uri', $uri)->find();
            if (!$file) {
                $basename = basename($uri);
                $file = $basename !== ''
                    ? File::where('uri', 'like', '%' . $basename)->order('id', 'desc')->find()
                    : null;
            }
            if (!$file) {
                $result['skipped'][] = ['uri' => $uri, 'reason' => 'iw_file 无记录'];
                $result['unrecoverable'][] = ['uri' => $uri, 'reason' => 'iw_file 无记录'];
                continue;
            }

            $fileId = (int)$file->id;
            $fileUri = trim((string)$file->uri);
            $status = (int)$file->transcode_status;

            // 已在队列中:无需重复投递
            if (in_array($status, [1, 2], true)) {
                $result['pending'][] = ['file_id' => $fileId, 'uri' => $fileUri];
                continue;
            }

            // 转码失败:不要每轮 cron 再投一次,否则会永久"生成中"
            if ($status === 4) {
                $result['unrecoverable'][] = [
                    'file_id' => $fileId,
                    'uri' => $fileUri,
                    'reason' => '转码失败且分辨率仍超标',
                ];
                continue;
            }

            // 已标记完成但仍超标:说明转码未真正压到阈值,再投也容易空转
            if ($status === 3) {
                $result['unrecoverable'][] = [
                    'file_id' => $fileId,
                    'uri' => $fileUri,
                    'reason' => '转码完成但分辨率仍超标',
                ];
                continue;
            }

            // status=0 仅允许投递一次;若任务又回到 0 且尺寸仍超标,视为不可恢复,避免死循环
            $triesKey = 'resolution_preflight_dispatch_' . $fileId;
            $tries = (int)cache($triesKey);
            if ($tries >= 1) {
                $result['unrecoverable'][] = [
                    'file_id' => $fileId,
                    'uri' => $fileUri,
                    'reason' => '已自动转码过但分辨率仍超标',
                ];
                continue;
            }

            $saveDir = ltrim(str_replace('\\', '/', dirname($fileUri)), './');
            if ($saveDir === '.' || $saveDir === '') {
                $saveDir = 'uploads/file/' . date('Ymd');
            }

            try {
                self::dispatchTranscodeJob([
                    'file_id' => $fileId,
                    'local_path' => public_path() . ltrim($fileUri, '/'),
                    'oss_uri' => $fileUri,
                    'save_dir' => $saveDir,
                    'clip' => [],
                    'custom_specs' => null,
                    'recover_from' => 'resolution-preflight',
                    'recover_at' => time(),
                ]);
                cache($triesKey, $tries + 1, 86400);
                $result['dispatched'][] = ['file_id' => $fileId, 'uri' => $fileUri];
            } catch (\Throwable $e) {
                $result['skipped'][] = [
                    'uri' => $fileUri,
                    'reason' => '投递失败: ' . $e->getMessage(),
                ];
                $result['unrecoverable'][] = [
                    'file_id' => $fileId,
                    'uri' => $fileUri,
                    'reason' => '投递失败: ' . $e->getMessage(),
                ];
                Log::channel('ffmpeg')->write(
                    '[分辨率超标自动转码] 投递失败 file_id=' . $fileId
                    . ' uri=' . $fileUri
                    . ' err=' . $e->getMessage()
                );
            }
        }

        return $result;
    }

}

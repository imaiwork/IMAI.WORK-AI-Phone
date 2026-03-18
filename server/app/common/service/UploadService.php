<?php


namespace app\common\service;


use app\common\enum\FileEnum;
use app\common\model\audio\Audio;
use app\common\model\file\File;
use app\common\service\ConfigService;
use app\common\service\FileService;
use app\common\service\storage\Driver as StorageDriver;
use Exception;
use think\facade\Log;


class UploadService
{
    // 视频默认规范配置
    const DEFAULT_VIDEO_SPECS = [
        'format' => 'mp4',
        'video_codec' => 'h264',
        'audio_codec' => 'aac',
        'resolution' => null, // null表示动态计算分辨率：最大边>2000时压缩到1920，另一边等比例缩小
        'frame_rate' => 30,
        'bit_rate' => '4M',
        'pixel_format' => 'yuv420p',
        'duration' => 60, // 秒
        'max_dimension' => 2000, // 最大边超过此值时进行压缩
        'target_dimension' => 1920, // 压缩目标最大边尺寸
    ];

    // 图片默认规范配置
    const DEFAULT_IMAGE_SPECS = [
        'format' => ['jpg', 'png'],
        'resolution' => null, // null表示动态计算分辨率：最大边>2000时压缩到1920，另一边等比例缩小
        'color_space' => 'sRGB',
        'max_dimension' => 2000, // 最大边超过此值时进行压缩
        'target_dimension' => 1920, // 压缩目标最大边尺寸
    ];


    /**
     * @notes 上传Base64编码的图片
     * @param array $params 上传参数
     * @param string $params['content'] Base64编码的图片内容
     * @param string $params['type'] 图片类型，默认为'ai'
     * @param string $params['code'] 文件名，默认为时间戳+随机数
     * @return array
     * @author 系统
     * @date 2026/03/05
     */
    public static function screenshot($params): array
    {
        try {
            // 获取参数并设置默认值
            $content = $params['content'] ?? '';
            $type = $params['type'] ?? 'ai';
            $code = $params['code'] ?? generate_unique_task_id();
            // 验证内容是否为空
            if (!trim($content)) {
                return [
                    'code' => 400,
                    'msg' => '图片不能为空',
                ];
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
            $output = 'uploads/images/' . $type . '/' . $date . '/' . $code . '.png';
            $rootPath = public_path();
            $fullPath = $rootPath . $output;

            // 创建目录（如果不存在）
            $dir = dirname($fullPath);
            if (!is_dir($dir)) {
                if (!mkdir($dir, 0755, true)) {
                    return [
                        'code' => 400,
                        'msg' => '创建目录失败',
                    ];
                }
            }

            // 保存文件
            if (file_put_contents($fullPath, $decoded) !== false) {
                return [
                    'code' => 200,
                    'msg' => '上传成功',
                    'url' => '/' . $output,
                ];
            }
            return [
                'code' => 400,
                'msg' => '上传失败',
            ];
        } catch (\Throwable $th) {
            return [
                'code' => 400,
                'msg' => $th->getMessage(),
            ];
        }
    }

    /**
     * @notes 验证Base64数据是否有效
     * @param string $base64Data Base64数据
     * @return bool
     */
    private static function isValidBase64(string $base64Data): bool
    {
        // 检查是否符合Base64格式
        return preg_match('/^[a-zA-Z0-9\/\+\=]+$/', $base64Data) === 1;
    }
    /**
     * @notes 上传图片
     * @param $cid
     * @param int $user_id
     * @param string $saveDir
     * @return array
     * @throws Exception
     * @author 段誉
     * @date 2021/12/29 16:30
     */
    public static function image($cid, int $sourceId = 0, int $source = FileEnum::SOURCE_ADMIN, string $saveDir = 'uploads/images', $ffmpeg = 0): array
    {
        try {
            if ($ffmpeg == 0) {
                $config = [
                    'default' => ConfigService::get('storage', 'default', 'local'),
                    'engine'  => ConfigService::get('storage') ?? ['local' => []],
                ];
            } else {
                $config = [
                    'default' => "local",
                    'engine'  => [],
                ];
            }


            // 2、执行文件上传
            $StorageDriver = new StorageDriver($config);
            $StorageDriver->setUploadFile('file');
            $fileName = $StorageDriver->getFileName();
            $fileInfo = $StorageDriver->getFileInfo();

            // 校验上传文件后缀
            if (!in_array(strtolower($fileInfo['ext']), config('project.file_image'))) {
                throw new Exception("上传图片不允许上传" . $fileInfo['ext'] . "文件");
            }

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


            $return = [
                'id'   => $file['id'],
                'cid'  => $file['cid'],
                'type' => $file['type'],
                'name' => $file['name'],
                'uri'  => FileService::getFileUrl($file['uri']),
                'url'  => $file['uri']
            ];
            if ($ffmpeg == 1) {
                $standardizedPath = public_path() . $saveDir . '/' . str_replace("\\", "/", $fileName);
                $default = ConfigService::get('storage', 'default', 'local');
                try {
                    $url = self::standardizeMedia($standardizedPath, null);
                } catch (Exception $e) {
                    Log::channel('ffmpeg')->write('图片标准化失败' . $e->getMessage());
                    if ($default != 'local') {
                        $url = self::uploadToOSS($standardizedPath, $saveDir);
                    }
                }
                if ($default != 'local') {
                    $return['url'] = $url;
                    $return['uri'] = FileService::getFileUrl($url);
                }
            }

            return $return;
            // 5、返回结果

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }


    /**
     * @notes 文件上传云
     * @param string $saveDir
     * @return array
     * @throws Exception
     * @author 段誉
     * @date 2021/12/29 16:30
     */
    public static function fileUpload(string $filesPath, int $source = FileEnum::SOURCE_USER, string $saveDir = 'uploads/images', bool $isSave = true)
    {
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
     * @notes 视频上传
     * @param $cid
     * @param int $user_id
     * @param string $saveDir
     * @return array
     * @throws Exception
     * @author 段誉
     * @date 2021/12/29 16:32
     */
    public static function video($cid, int $sourceId = 0, int $source = FileEnum::SOURCE_ADMIN, string $saveDir = 'uploads/video',  $ffmpeg = 0, $clip = [])
    {
        try {
            if ($ffmpeg == 0) {
                $config = [
                    'default' => ConfigService::get('storage', 'default', 'local'),
                    'engine'  => ConfigService::get('storage') ?? ['local' => []],
                ];
            } else {
                $config = [
                    'default' => "local",
                    'engine'  => [],
                ];
            }
            // 2、执行文件上传
            $StorageDriver = new StorageDriver($config);
            $StorageDriver->setUploadFile('file');
            $fileName = $StorageDriver->getFileName();
            $fileInfo = $StorageDriver->getFileInfo();
            // 校验上传文件后缀
            if (!in_array(strtolower($fileInfo['ext']), config('project.file_video'))) {
                throw new Exception("上传视频不允许上传" . $fileInfo['ext'] . "文件");
            }

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

            // 4、写入数据库中
            $file = File::create([
                'cid'         => $cid,
                'type'        => FileEnum::VIDEO_TYPE,
                'name'        => $fileInfo['name'],
                'uri'         => $saveDir . '/' . str_replace("\\", "/", $fileName),
                'source'      => $source,
                'source_id'   => $sourceId,
                'create_time' => time(),
            ]);

            $return = [
                'id'   => $file['id'],
                'cid'  => $file['cid'],
                'type' => $file['type'],
                'name' => $file['name'],
                'uri'  => FileService::getFileUrl($file['uri']),
                'url'  => $file['uri']
            ];

            if ($ffmpeg == 1) {
                $standardizedPath = public_path() . $saveDir . '/' . str_replace("\\", "/", $fileName);
                $default = ConfigService::get('storage', 'default', 'local');
                try {
                    $url = self::standardizeMedia($standardizedPath, $clip);
                } catch (Exception $e) {
                    Log::channel('ffmpeg')->write('视频标准化失败' . $e->getMessage());
                    if ($default != 'local') {
                        $url = self::uploadToOSS($standardizedPath, $saveDir);
                    }
                }
                if ($default != 'local') {
                    $return['url'] = $url;
                    $return['uri'] = FileService::getFileUrl($url);
                }
            }

            return $return;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }


    /**
     * @notes 视频上传
     * @param $cid
     * @param int $user_id
     * @param string $saveDir
     * @return array
     * @throws Exception
     * @author 段誉
     * @date 2021/12/29 16:32
     */
    public static function audio($cid, int $sourceId = 0, int $source = FileEnum::SOURCE_ADMIN, string $saveDir = 'uploads/audio', bool $isDate = true)
    {
        try {
            $config = [
                'default' => ConfigService::get('storage', 'default', 'local'),
                'engine'  => ConfigService::get('storage') ?? ['local' => []],
            ];

            // 2、执行文件上传
            $StorageDriver = new StorageDriver($config);
            $StorageDriver->setUploadFile('file');
            $fileName = $StorageDriver->getFileName();
            $fileInfo = $StorageDriver->getFileInfo();

            // 校验上传文件后缀
            if (!in_array(strtolower($fileInfo['ext']), config('project.file_audio'))) {
                throw new Exception("上传音频不允许上传" . $fileInfo['ext'] . "文件");
            }
            if (empty($saveDir)) {
                mkdir($saveDir);
            }
            // 上传文件
            if ($isDate) {
                $saveDir = self::getUploadUrl($saveDir);
            }
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
                'type'        => FileEnum::AUDIO_TYPE,
                'name'        => $fileInfo['name'],
                'uri'         => $saveDir . '/' . str_replace("\\", "/", $fileName),
                'source'      => $source,
                'source_id'   => $sourceId,
                'create_time' => time(),
            ]);

            $audio = Audio::create([
                'user_id'     => $sourceId,
                'file_id'     => $file['id'],
                'file_name'   => $fileInfo['name'],
                'file_path'   => $saveDir . '/' . str_replace("\\", "/", $fileName),
                'create_time' => time(),
            ]);

            // 5、返回结果
            return [
                'id'       => $file['id'],
                'audio_id' => $audio['id'],
                'cid'      => $file['cid'],
                'type'     => $file['type'],
                'name'     => $file['name'],
                'uri'      => FileService::getFileUrl($file['uri']),
                'url'      => $file['uri']
            ];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @notes 上传文件
     * @param $cid
     * @param int $sourceId
     * @param int $source
     * @param string $saveDir
     * @return array
     * @throws Exception
     * @author dw
     * @date 2023/06/26
     */
    public static function file($cid, int $sourceId = 0, int $source = FileEnum::SOURCE_ADMIN, string $saveDir = 'uploads/file', $ffmpeg = 0, $clip = [])
    {
        try {
            if ($ffmpeg == 0) {
                $config = [
                    'default' => ConfigService::get('storage', 'default', 'local'),
                    'engine'  => ConfigService::get('storage') ?? ['local' => []],
                ];
            } else {
                $config = [
                    'default' => "local",
                    'engine'  => [],
                ];
            }

            // 2、执行文件上传
            $StorageDriver = new StorageDriver($config);
            $StorageDriver->setUploadFile('file');
            $fileName = $StorageDriver->getFileName();
            $fileInfo = $StorageDriver->getFileInfo();

            // 校验上传文件后缀
            //            if (!in_array(strtolower($fileInfo['ext']), config('project.file_file'))) {
            //                throw new Exception("上传文件不允许上传" . $fileInfo['ext'] . "文件");
            //            }

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

            $return = [
                'id'   => $file['id'],
                'cid'  => $file['cid'],
                'type' => $file['type'],
                'name' => $file['name'],
                'uri'  => FileService::getFileUrl($file['uri']),
                'url'  => $file['uri']
            ];

            if ($ffmpeg == 1) {
                $default = ConfigService::get('storage', 'default', 'local');
                $standardizedPath = public_path() . $saveDir . '/' . str_replace("\\", "/", $fileName);
                try {
                    $url = self::standardizeMedia($standardizedPath, $clip);
                } catch (Exception $e) {
                    if ($default != 'local') {
                        $url = self::uploadToOSS($standardizedPath, $saveDir);
                    }
                }
                if ($default != 'local') {
                    $return['url'] = $url;
                    $return['uri'] = FileService::getFileUrl($url);
                }
            }

            return $return;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }



    /**
     * @notes 上传文件
     * @param $cid
     * @param int $sourceId
     * @param int $source
     * @param string $saveDir
     * @return array
     * @throws Exception
     * @author dw
     * @date 2023/06/26
     */
    public static function csvFile($cid, int $sourceId = 0, int $source = FileEnum::SOURCE_ADMIN, string $saveDir = 'uploads/file')
    {
        try {
            $config = [
                'default' => ConfigService::get('storage', 'default', 'local'),
                'engine'  => ConfigService::get('storage') ?? ['local' => []],
            ];

            // 2、执行文件上传
            $StorageDriver = new StorageDriver($config);
            $StorageDriver->setUploadFile('file');
            $fileName = $StorageDriver->getFileName();
            $fileInfo = $StorageDriver->getFileInfo();

            // 校验上传文件后缀
            if (!in_array(strtolower($fileInfo['ext']), config('project.csv_file'))) {
                throw new Exception("上传文件不允许上传" . $fileInfo['ext'] . "文件");
            }

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

            // 4、写入数据库中
            $file = File::create([
                'cid'         => $cid,
                'type'        => FileEnum::CSV_TYPE,
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
                'uri'  => FileService::getFileUrl($file['uri']),
                'url'  => $file['uri']
            ];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }


    /**
     * @notes 上传文件
     * @param $cid
     * @param int $sourceId
     * @param int $source
     * @param string $saveDir
     * @return array
     * @throws Exception
     * @author dw
     * @date 2023/06/26
     */
    public static function zipfile($cid, int $sourceId = 0, int $source = FileEnum::SOURCE_ADMIN, string $saveDir = '../extend/miniprogram-ci')
    {
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
     * @notes 上传文件到
     * @param string $saveDir
     * @return array
     * @throws Exception
     * @author dw
     * @date 2023/06/26
     */
    public static function fileLocal($cid, int $sourceId = 0, int $source = FileEnum::SOURCE_ADMIN, string $saveDir = 'uploads/file')
    {
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

    public static function wechatUpload($cid, int $sourceId = 0, int $source = FileEnum::SOURCE_ADMIN, string $saveDir = 'uploads/file')
    {
        try {
            $config = [
                'default' => ConfigService::get('storage', 'default', 'local'),
                'engine'  => ConfigService::get('storage') ?? ['local' => []],
            ];

            // 2、执行文件上传
            $StorageDriver = new StorageDriver($config);
            $StorageDriver->setUploadFile('myfile');
            $fileName = $StorageDriver->getFileName();
            $fileInfo = $StorageDriver->getFileInfo();
            //print_r($fileInfo);die;
            // 校验上传文件后缀
            if (!in_array(strtolower($fileInfo['ext']), config('project.file_file'))) {
                throw new Exception("上传文件不允许上传" . $fileInfo['ext'] . "文件");
            }

            $extension = $fileInfo['ext'];
            $path = $fileInfo['realPath'];
            $fileSize = $fileInfo['size'];

            // 检查是否为AMR文件并转换为MP3
            if (strtolower($extension) === 'amr') {

                $fileName = str_replace('.amr', '.mp3', $fileName);
                // 获取临时文件的后缀
                $tempExtension = pathinfo($path, PATHINFO_EXTENSION);

                $command = root_path() . 'extend/lib/silk/converter.sh' . " " . $path . " mp3";

                exec($command, $output, $returnCode);

                if ($returnCode !== 0) {
                    throw new \Exception('无法转换文件' . $command);
                }
                //@unlink($path);
                $path = str_replace($tempExtension, 'mp3', $path);
                //print_r($path);die;
                $StorageDriver->setRealPath($path);
                $StorageDriver->setFilename($fileName);
            }

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
            $url =  FileService::getFileUrl($file['uri']);
            // 5、返回结果
            return [
                'bizCode' => 0,
                'data' => [
                    'fileSize' => $fileSize,
                    'url' => $url
                ],
                'msg' => '上传成功'
            ];
        } catch (Exception $e) {
            //throw new Exception($e->getMessage());
            //print_r($e);die;
            return [
                'bizCode' => 6001,
                'data' => [],
                'msg' => '系统错误',
                'info' => $e->__toString()
            ];
        }
    }

    /**
     * @notes 标准化媒体文件（图片和视频）- 支持本地文件和OSS远程文件
     * @param string $inputPath 输入文件路径（可以是本地路径或OSS URL）
     * @param string $outputPath 输出文件路径（本地路径）
     * @param array|null $customSpecs 自定义规范配置，如果为null则使用默认配置
     * @param bool $uploadToOSS 是否将处理后的文件上传到OSS
     * @param string|null $ossOutputPath OSS输出路径，如果uploadToOSS为true时必填
     * @return array 返回标准化结果信息
     * @throws Exception
     * @author 系统
     * @date 2024/12/19
     */
    /**
     * @notes 标准化媒体文件
     * @param string $inputPath 输入文件路径
     * @param array|null $customSpecs 自定义规范
     * @return string
     * @throws Exception
     * @author 系统
     * @date 2024/12/19
     */
    public static function standardizeMedia(string $inputPath, ?array $customSpecs = null)
    {
        $tempOutputPath = null;
        try {
            // 获取媒体信息
            $info = self::getMediaInfo($inputPath);
            Log::channel('ffmpeg')->write('媒体信息'.json_encode($info, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            if (empty($info['streams'])) {
                throw new Exception("无法获取媒体文件信息");
            }

            $videoStream = null;
            foreach ($info['streams'] as $s) {
                if (($s['codec_type'] ?? '') === 'video') {
                    $videoStream = $s;
                    break;
                }
            }

            if (!$videoStream) {
                throw new Exception("未找到视频流");
            }

            $stream = $videoStream;
            $mediaType = $stream['codec_type'] ?? 'unknown';
            $r_frame_rate = $stream['r_frame_rate'] ?? 'unknown';

            // 提取oss路径信息
            if (preg_match('/uploads\/(.+?)\/\d{14}/', $inputPath, $matches)) {
                $ossPath = "uploads/" . $matches[1];
            } else {
                throw new Exception("未找到匹配的路径部分");
            }

            $outputUrl = $ossPath . '/' . basename($inputPath);
            $finalOutputPath = public_path() . $outputUrl;

            // 根据媒体类型进行标准化处理
            $needTranscode = false;
            if ($mediaType === 'video' && $r_frame_rate == '25/1') {
                // 图片处理
                $specs = $customSpecs['image'] ?? self::DEFAULT_IMAGE_SPECS;
                $specs = array_merge(self::DEFAULT_IMAGE_SPECS, $specs);
                if (!self::isImageCompliant($info, $specs)) {
                    $needTranscode = true;
                }
            } elseif ($mediaType === 'video') {
                // 视频处理
                $specs = $customSpecs['video'] ?? self::DEFAULT_VIDEO_SPECS;
                $specs = array_merge(self::DEFAULT_VIDEO_SPECS, $specs);
                if (!self::isVideoCompliant($info, $specs)) {
                    $needTranscode = true;
                }
            } else {
                throw new Exception("不支持的媒体类型: " . $mediaType);
            }
            // 执行转码
            if ($needTranscode) {
                // 生成临时输出路径
                $tempOutputPath = dirname($finalOutputPath) . '/tmp_' . uniqid() . '_' . basename($finalOutputPath);
                // 确保输出目录存在
                $outputDir = dirname($finalOutputPath);
                if (!is_dir($outputDir)) {
                    if (!mkdir($outputDir, 0755, true)) {
                        throw new Exception("无法创建输出目录: " . $outputDir);
                    }
                }

                // 执行转码到临时文件
                if ($mediaType === 'video' && $r_frame_rate == '25/1') {
                    self::transcodeImage($inputPath, $tempOutputPath, $specs, $info);
                } else {
                    self::transcodeVideo($inputPath, $tempOutputPath, $specs, $info);
                }

                // 验证临时文件是否生成成功
                if (!file_exists($tempOutputPath)) {
                    throw new Exception("转码失败：临时输出文件未生成");
                }

                // 将临时文件移动到最终位置（覆盖原文件）
                if (!copy($tempOutputPath, $finalOutputPath)) {
                    @unlink($tempOutputPath);
                    throw new Exception("文件替换失败");
                }

                // 清理临时文件
                @unlink($tempOutputPath);
                $tempOutputPath = null; // 标记临时文件已清理
            }

            $default = ConfigService::get('storage', 'default', 'local');
            $ossUrl = $outputUrl;

            // 如果需要上传到OSS
            if ($default != 'local') {
                $actualPath = $needTranscode ? $finalOutputPath : $inputPath;
                $ossUrl = self::uploadToOSS($actualPath, $ossPath);
            }

            return $ossUrl;
        } catch (Exception $e) {
            // 清理临时文件（如果存在）
            if ($tempOutputPath && file_exists($tempOutputPath)) {
                @unlink($tempOutputPath);
            }
            throw new Exception("媒体标准化失败: " . $e->getMessage());
        }
    }

    /**
     * @notes 下载远程文件到本地临时目录
     * @param string $remoteUrl 远程文件URL
     * @return string 本地临时文件路径
     * @throws Exception
     * @author 系统
     * @date 2024/12/19
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
     * @notes 上传文件到OSS
     * @param string $localPath 本地文件路径
     * @param string $ossPath OSS存储路径
     * @return string OSS文件URL
     * @throws Exception
     * @author 系统
     * @date 2024/12/19
     */
    public static function uploadToOSS(string $localPath, $ossPath): string
    {
        if (!file_exists($localPath)) {
            throw new Exception("本地文件不存在: " . $localPath);
        }

        try {
            // 使用项目的StorageDriver上传到OSS
            $config = [
                'default' => ConfigService::get('storage', 'default', 'local'),
                'engine'  => ConfigService::get('storage') ?? ['local' => []],
            ];
            if ($config['default'] == 'local') {
                return $localPath;
            }
            $StorageDriver = new StorageDriver($config);
            $StorageDriver->setUploadFileByReal($localPath);
            $StorageDriver->setFilename(basename($localPath));



            if (!$StorageDriver->upload($ossPath)) {
                throw new Exception("OSS上传失败: " . $StorageDriver->getError());
            } else {
                $url = $ossPath . '/' . basename($localPath);

                if ($url && file_exists($url)) {
                    unlink($url);
                }
            }

            return $url;
        } catch (Exception $e) {
            throw new Exception("OSS上传失败: " . $e->getMessage());
        }
    }

    /**
     * @notes 获取媒体文件基本信息
     * @param string $filePath 文件路径
     * @return array 媒体信息数组
     * @throws Exception
     * @author 系统
     * @date 2024/12/19
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
     * @notes 检查视频是否符合规范
     * @param array $info 媒体信息
     * @param array $specs 规范配置
     * @return bool 是否符合规范
     * @author 系统
     * @date 2024/12/19
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
        if ($specs['duration'] < 1) {
            return (
                isset($stream['codec_name']) && $stream['codec_name'] === $specs['video_codec'] &&
                isset($stream['width']) && $stream['width'] <= intval($targetDims[0]) &&
                isset($stream['height']) && $stream['height'] <= intval($targetDims[1]) &&
                isset($stream['r_frame_rate']) && $stream['r_frame_rate'] == $specs['frame_rate'] &&
                isset($stream['bit_rate']) && $stream['bit_rate'] >= 4000000 && $stream['bit_rate'] <= 6000000 &&
                isset($stream['pix_fmt']) && $stream['pix_fmt'] === $specs['pixel_format']
            );
        } else {
            return (
                isset($stream['codec_name']) && $stream['codec_name'] === $specs['video_codec'] &&
                isset($stream['width']) && $stream['width'] <= intval($targetDims[0]) &&
                isset($stream['height']) && $stream['height'] <= intval($targetDims[1]) &&
                isset($stream['r_frame_rate']) && $stream['r_frame_rate'] == $specs['frame_rate'] &&
                isset($stream['bit_rate']) && $stream['bit_rate'] >= 4000000 && $stream['bit_rate'] <= 6000000 &&
                isset($stream['pix_fmt']) && $stream['pix_fmt'] === $specs['pixel_format'] &&
                isset($stream['duration']) && $stream['duration'] >= $specs['duration']
            );
        }
    }

    /**
     * @notes 检查图片是否符合规范
     * @param array $info 媒体信息
     * @param array $specs 规范配置
     * @return bool 是否符合规范
     * @author 系统
     * @date 2024/12/19
     */
    private static function isImageCompliant(array $info, array $specs): bool
    {
        if (empty($info['streams'])) {
            return false;
        }

        $stream = $info['streams'][0];
        // 计算目标分辨率
        $targetResolution = self::calculateTargetResolution($info, $specs);
        $targetDims = explode('x', $targetResolution);

        return (
            isset($stream['width']) && $stream['width'] <= intval($targetDims[0]) &&
            isset($stream['height']) && $stream['height'] <= intval($targetDims[1])
        );
    }

    /**
     * @notes 转码视频
     * @param string $inputPath 输入文件路径
     * @param string $outputPath 输出文件路径
     * @param array $specs 规范配置
     * @throws Exception
     * @author 系统
     * @date 2024/12/19
     */
    private static function transcodeVideo(string $inputPath, string $outputPath, array $specs, array $info): void
    {
        // 验证输入文件
        if (!file_exists($inputPath)) {
            throw new Exception("输入文件不存在: " . $inputPath);
        }
        // 获取视频时长信息
        $duration = null;
        if (isset($info['format']['duration'])) {
            $duration = floatval($info['format']['duration']);
        }

        // 从媒体信息中提取旋转角度
        $rotation = null;
        if (!empty($info['streams'])) {
            foreach ($info['streams'] as $stream) {
                // 首先检查直接的rotation字段
                if (isset($stream['rotation'])) {
                    $rotation = intval($stream['rotation']);
                    break;
                }

                // 检查side_data_list中的Display Matrix
                if (!empty($stream['side_data_list'])) {
                    foreach ($stream['side_data_list'] as $sideData) {
                        if ($sideData['side_data_type'] === 'Display Matrix' && isset($sideData['rotation'])) {
                            $rotation = intval($sideData['rotation']);
                            break 2; // 跳出两层循环
                        }
                    }
                }
            }
        }

        // 计算动态分辨率（传递旋转角度）
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

        // 构建ffmpeg命令
        $ffmpegParts = [
            "ffmpeg6 -i " . escapeshellarg($inputPath),
            "-c:v libx264",
            "-crf 23", // 调整CRF值，18可能过于严格
            "-preset fast", // 使用fast而非veryfast，提高兼容性
            "-c:a aac",
            "-b:a 128k",
            "-movflags +faststart", // 优化网络播放
        ];

        // 只有在明确指定时才限制时长
        if (isset($specs['duration']) && $specs['duration'] > 0) {
            $ffmpegParts[] = "-t " . $specs['duration'];
        }
        // 添加帧率（如果指定）
        if (isset($specs['frame_rate']) && $specs['frame_rate'] > 0) {
            $ffmpegParts[] = "-r " . $specs['frame_rate'];
        }

        // 添加像素格式
        if (isset($specs['pixel_format'])) {
            $ffmpegParts[] = "-pix_fmt " . escapeshellarg($specs['pixel_format']);
        }

        // 添加码率（如果指定）
        if (isset($specs['bit_rate'])) {
            $ffmpegParts[] = "-b:v " . escapeshellarg($specs['bit_rate']);
        }

        // 添加缩放滤镜
        if ($resolution) {
            $ffmpegParts[] = "-vf scale=" . escapeshellarg($resolution);
        }
        $ffmpegParts[] = escapeshellarg($outputPath);

        $ffmpegCommand = implode(" ", $ffmpegParts);
        Log::channel('ffmpeg')->write('转码指令'.$ffmpegCommand);
        
        // 执行命令并捕获详细输出
        $output = shell_exec($ffmpegCommand . " 2>&1");
        // 检查输出文件
        if (!file_exists($outputPath)) {
            $errorMsg = "视频转码失败，输出文件未生成.\n";
            $errorMsg .= "命令: " . $ffmpegCommand . "\n";
            $errorMsg .= "输出: " . $output . "\n";
            $errorMsg .= "输入文件: " . $inputPath . "\n";
            $errorMsg .= "输出文件: " . $outputPath . "\n";
            $errorMsg .= "文件大小: " . (file_exists($inputPath) ? filesize($inputPath) : 'N/A') . " bytes\n";

            Log::channel('ffmpeg')->write($errorMsg);
            throw new Exception($errorMsg);
        }

        // 检查输出文件大小
        $outputSize = filesize($outputPath);
        if ($outputSize < 1000) { // 小于1KB可能表示转码失败
            @unlink($outputPath); // 删除无效文件
            $errorMsg = "视频转码失败，输出文件过小 (" . $outputSize . " bytes).\n";
            $errorMsg .= "命令: " . $ffmpegCommand . "\n";
            $errorMsg .= "输出: " . $output;

            Log::channel('ffmpeg')->write($errorMsg);
            throw new Exception($errorMsg);
        }
    }

    /**
     * @notes 转码图片
     * @param string $inputPath 输入文件路径
     * @param string $outputPath 输出文件路径
     * @param array $specs 规范配置
     * @param array $info 媒体信息
     * @throws Exception
     * @author 系统
     * @date 2024/12/19
     */
    public static function transcodeImage(string $inputPath, string $outputPath, array $specs, array $info): void
    {
        // 验证输入文件
        if (!file_exists($inputPath)) {
            throw new Exception("输入文件不存在: " . $inputPath);
        }

        // 从媒体信息中提取旋转角度
        $rotation = null;
        if (!empty($info['streams'])) {
            foreach ($info['streams'] as $stream) {
                // 首先检查直接的rotation字段
                if (isset($stream['rotation'])) {
                    $rotation = intval($stream['rotation']);
                    break;
                }

                // 检查side_data_list中的Display Matrix
                if (!empty($stream['side_data_list'])) {
                    foreach ($stream['side_data_list'] as $sideData) {
                        if ($sideData['side_data_type'] === 'Display Matrix' && isset($sideData['rotation'])) {
                            $rotation = intval($sideData['rotation']);
                            break 2; // 跳出两层循环
                        }
                    }
                }
            }
        }

        // 计算动态分辨率（传递旋转角度）
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

        // 构建ffmpeg命令
        $ffmpegParts = [
            "ffmpeg6 -i " . escapeshellarg($inputPath),
            "-c:v png"
        ];

        // 添加缩放滤镜
        if ($resolution) {
            $ffmpegParts[] = "-vf scale=" . escapeshellarg($resolution);
        }
        $ffmpegParts[] = "-y"; // 覆盖输出文件
        $ffmpegParts[] = escapeshellarg($outputPath);

        $ffmpegCommand = implode(" ", $ffmpegParts);

        // 执行命令并捕获详细输出
        $output = shell_exec($ffmpegCommand . " 2>&1");
        // 检查输出文件
        if (!file_exists($outputPath)) {
            $errorMsg = "图片转码失败，输出文件未生成.\n";
            $errorMsg .= "命令: " . $ffmpegCommand . "\n";
            $errorMsg .= "输出: " . $output . "\n";
            $errorMsg .= "输入文件: " . $inputPath . "\n";
            $errorMsg .= "输出文件: " . $outputPath . "\n";
            $errorMsg .= "文件大小: " . (file_exists($inputPath) ? filesize($inputPath) : 'N/A') . " bytes\n";

            Log::channel('ffmpeg')->write($errorMsg);
            throw new Exception($errorMsg);
        }

        // 检查输出文件大小
        $outputSize = filesize($outputPath);
        if ($outputSize < 100) { // 小于100字节可能表示转码失败
            @unlink($outputPath); // 删除无效文件
            $errorMsg = "图片转码失败，输出文件过小 (" . $outputSize . " bytes).\n";
            $errorMsg .= "命令: " . $ffmpegCommand . "\n";
            $errorMsg .= "输出: " . $output;

            Log::channel('ffmpeg')->write($errorMsg);
            throw new Exception($errorMsg);
        }
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
        $maxDimension = $specs['max_dimension'] ?? 2000;
        $targetDimension = $specs['target_dimension'] ?? 1920;
        // 检查是否需要压缩
        if (max($originalWidth, $originalHeight) <= $maxDimension) {
            // 不需要压缩，保持原始分辨率
            $finalWidth = $originalWidth;
            $finalHeight = $originalHeight;
        } else {
            // 计算等比例缩放后的尺寸
            $scaleRatio = $targetDimension / max($originalWidth, $originalHeight);
            $finalWidth = intval($originalWidth * $scaleRatio);
            $finalHeight = intval($originalHeight * $scaleRatio);

            // 确保尺寸是偶数（很多视频编码器要求）
            $finalWidth = $finalWidth % 2 == 0 ? $finalWidth : $finalWidth - 1;
            $finalHeight = $finalHeight % 2 == 0 ? $finalHeight : $finalHeight - 1;
        }

        // 如果是90/270/-90/-270度旋转，需要对换宽高
        if (in_array($rotation, [90, 270, -90, -270])) {
            // 对换宽高
            $tempWidth = $finalWidth;
            $finalWidth = $finalHeight;
            $finalHeight = $tempWidth;
        }

        return $finalWidth . 'x' . $finalHeight;
    }

    /**
     * @notes 上传地址
     * @param $saveDir
     * @return string
     * @author dw
     * @date 2023/06/26
     */
    private static function getUploadUrl($saveDir): string
    {
        return $saveDir . '/' . date('Ymd');
    }
}

<?php


namespace app\common\service;


use app\common\enum\FileEnum;
use app\common\model\audio\Audio;
use app\common\model\file\File;
use app\common\model\file\GptFile;
use app\common\service\storage\Driver as StorageDriver;
use Exception;


class UploadService
{

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
    public static function image($cid, int $sourceId = 0, int $source = FileEnum::SOURCE_ADMIN, string $saveDir = 'uploads/images')
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
    public static function video($cid, int $sourceId = 0, int $source = FileEnum::SOURCE_ADMIN, string $saveDir = 'uploads/video')
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
    public static function file($cid, int $sourceId = 0, int $source = FileEnum::SOURCE_ADMIN, string $saveDir = 'uploads/file')
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
            if (!in_array(strtolower($fileInfo['ext']), config('project.file_file'))) {
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
                'id'   => $file['id'],
                'cid'  => $file['cid'],
                'type' => $file['type'],
                'name' => $file['name'],
                'uri'  => $url,
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
    public static function gptfile($cid, int $sourceId = 0, int $source = FileEnum::SOURCE_ADMIN, string $saveDir = 'uploads/file')
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
            if (!in_array(strtolower($fileInfo['ext']), config('project.file_file'))) {
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

            $uri = $saveDir . '/' . str_replace("\\", "/", $fileName);
            // 4、写入数据库中
            $openData = [
                'purpose' => "assistants",
                "file"    => curl_file_create(public_path() . $uri),
            ];

            $result = \app\common\service\ToolsService::Chat()->upload($openData);

            if (!isset($result['data']['file']) || isset($result['data']['file']['error'])) {
                throw new \Exception("文件提交异常");
            }

            $result  = $result['data']['file'];

            if (empty($result['id'])) {
                throw new \Exception("上传失败");
            }
            $file = GptFile::create([
                'user_id' => 0,
                'type' => 0,
                'file_id' => $result['id'],
                'bytes' => $result['bytes'],
                'file_path' => $uri,
                'purpose' => "assistants",
                'file_name' => $fileInfo['name'],
            ]);

            // 5、返回结果
            return [
                'id'   => $file['id'],
                'cid'  => $file['cid'],
                'type' => $file['type'],
                'name' => $file['name'],
                //                'uri'  => FileService::getFileUrl($file['uri']),
                'gpt_id'  => $result['id'],
                'url'  => $file['uri']
            ];
        } catch (\think\exception\HttpResponseException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new Exception($exception->getMessage());
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

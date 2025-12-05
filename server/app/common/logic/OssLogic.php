<?php

namespace app\common\logic;

use think\facade\Log;
use think\facade\Db;
use Exception;
use app\common\service\ConfigService;
use app\common\enum\FileEnum;
use app\common\service\storage\Driver as StorageDriver;

/**
 * OSS文件迁移逻辑
 * Class OssLogic
 * @package app\common\logic
 */
class OssLogic extends BaseLogic
{
    private $storageDriver;
    private $config;
    private $progressFile;
    private $ossType;
    private $storageConfig;
    private $migration;

    public function __construct()
    {
       
        $this->progressFile = runtime_path() . 'oss_upload_progress.log';
        // 获取存储配置
        $this->storageConfig = [
            'default' => ConfigService::get('storage', 'default', 'local'),
            'engine'  => ConfigService::get('storage') ?? ['local' => []],
        ];

        $this->ossType = $this->storageConfig['default'];

        $this->migration =  $this->storageConfig['engine'][ $this->ossType]['migration'] ?? 0;
       
        // 初始化存储驱动
        $this->storageDriver = new StorageDriver($this->storageConfig);

    }

    /**
     * 批量上传文件到OSS
     * @param string $localDir 本地目录
     * @param string $ossDir OSS目录
     * @param int $batchSize 批次大小
     * @return void
     */
    public function uploadFilesInBatches($localDir, $ossDir, $batchSize = 20)
    {

        $filesToUpload = $this->scanLocalFiles($localDir, $ossDir);
        $totalFiles = count($filesToUpload);


        if ($totalFiles  == 0){
            $storage = ConfigService::get('storage', 'default', 'local');
            if (  $this->ossType == $storage){
                $data = ConfigService::get('storage', $storage);
                $data['migration'] = 2;
                ConfigService::set('storage', $storage,$data);
            }

            $this->clearProgress();
        }
       
        echo "开始上传文件，总计 {$totalFiles} 个文件没有上传\n";
        $batchFiles = array_slice($filesToUpload, 0, $batchSize);
        $this->uploadBatch($batchFiles);
        // 清理进度文件
        echo "所有文件上传完成\n";
    }

    /**
     * 扫描本地文件
     * @param string $localDir
     * @param string $ossDir
     * @return array
     */
    private function scanLocalFiles($localDir, $ossDir)
    {
        try {
            $filesToUpload = [];
            // 确保目录存在且可读
            if (!is_dir($localDir) || !is_readable($localDir)) {
                throw new Exception("目录不存在或不可读: {$localDir}");
            }

            // 使用 RecursiveDirectoryIterator 遍历目录，设置递归深度
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($localDir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST,
                \RecursiveIteratorIterator::CATCH_GET_CHILD
            );

            // 设置最大递归深度，防止无限递归
            $iterator->setMaxDepth(3); // 设置最大递归深度为3层

            $totalFiles = 0;
            foreach ($iterator as $file) {
                // 跳过目录
                if ($file->isDir()) {
                    continue;
                }

                // 检查文件是否可读
                if (!$file->isReadable()) {
                    continue;
                }

                // 获取文件信息
                $fileInfo = $this->getFileInfo($file);

                // 获取相对路径（相对于 uploads 目录）
                $relativeFilePath = str_replace($localDir . DIRECTORY_SEPARATOR, '', $file->getPathname());
                $relativeFilePath = str_replace('\\', '/', $relativeFilePath); // 统一使用正斜杠
                
                // 构建 OSS 路径
                $ossFilePath = $ossDir . '/' . $relativeFilePath;
                $totalFiles += 1;
                // 检查文件是否已上传
                if (!$this->isFileUploaded($relativeFilePath)) {

                    $filesToUpload[] = [
                        'localPath' => $file->getPathname(),
                        'ossPath' => $ossFilePath,
                        'relativePath' => $relativeFilePath,
                        'fileHash' => md5_file($file->getPathname()),
                        'fileMtime' => filemtime($file->getPathname()),
                        'fileInfo' => $fileInfo
                    ];
                    // 记录找到的文件
                }
            }
            $key = 'oss_migration_total_files' ;
            cache($key,$totalFiles,600);

            $key = 'oss_migration_upload_to_files' ;
            $filesToUploadNum = count($filesToUpload);
            cache($key,$filesToUploadNum,600);

        } catch (Exception $e) {
            throw $e;
        }

        return $filesToUpload;
    }

    /**
     * 获取文件信息
     * @param \SplFileInfo $file
     * @return array
     */
    private function getFileInfo($file)
    {
        $extension = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
        return [
            'name' => $file->getFilename(),
            'ext' => $extension,
            'size' => $file->getSize(),
            'mime' => mime_content_type($file->getPathname()),
            'type' => $this->getFileType($extension)
        ];
    }

    /**
     * 获取文件类型
     * @param string $extension
     * @return int
     */
    private function getFileType($extension)
    {
        if (in_array($extension, config('project.file_image', []))) {
            return FileEnum::IMAGE_TYPE;
        } elseif (in_array($extension, config('project.file_video', []))) {
            return FileEnum::VIDEO_TYPE;
        } elseif (in_array($extension, config('project.file_audio', []))) {
            return FileEnum::AUDIO_TYPE;
        } elseif (in_array($extension, config('project.csv_file', []))) {
            return FileEnum::CSV_TYPE;
        }  
        return FileEnum::FILE_TYPE;
    }

    /**
     * 验证文件
     * @param array $fileInfo
     * @return bool
     */
    private function validateFile($fileInfo)
    {
        $allowedTypes = array_merge(
            config('project.file_image', []),
            config('project.file_video', []),
            config('project.file_audio', []),
            config('project.file_document', [])
        );

        if (!in_array($fileInfo['ext'], $allowedTypes)) {
            return false;
        }
        return true;
    }

    /**
     * 上传批次文件
     * @param array $batchFiles
     * @throws Exception
     */
    private function uploadBatch($batchFiles)
    {
        // 获取上次上传进度
        $lastProgress = $this->getLastProgress();
        $startIndex = $lastProgress['index'] ?? 0;
        $key = 'oss_migration_total_files' ;
        $totalFiles = cache($key);
        foreach ($batchFiles as $fileInfo) {
            try {
                // 检查文件是否存在
                if (!file_exists($fileInfo['localPath'])) {
                    throw new Exception("文件不存在: " . $fileInfo['localPath']);
                }
                // 设置上传文件
               $this->storageDriver->setUploadFileByFileName($fileInfo['localPath'],$fileInfo['fileInfo']['name']);
             
              
                // 校验文件类型
//                if (!$this->validateFileType($fileInfo['fileInfo'])) {
//                    throw new Exception("文件类型不允许: " . $fileInfo['fileInfo']['ext']);
//                }

         
                $lastSlashPos = strrpos($fileInfo['ossPath'], '/');
                if ($lastSlashPos !== false) {
                    $fileInfo['ossPath'] = substr($fileInfo['ossPath'], 0, $lastSlashPos);
                }
                // 上传文件
                $saveDir = $this->getUploadUrl($fileInfo['ossPath']);
                if (!$this->storageDriver->upload($saveDir)) {
                    throw new Exception($this->storageDriver->getError());
                }

                $this->markFileAsUploaded($fileInfo);
                $startIndex += 1;
                // 记录成功日志
                echo "文件上传成功: " . $fileInfo['relativePath'] . "\n";
                $progress = round($startIndex / $totalFiles * 100, 2);
                echo "上传进度: {$progress}%\n";
                $this->saveProgress($startIndex , $totalFiles);

            } catch (Exception $e) {
                // 记录错误日志
                echo "文件上传失败: " . $fileInfo['relativePath'] . " - " . $e->getMessage() . "\n";
                continue; // 继续处理下一个文件
            }
        }
    }

    /**
     * 验证文件类型
     * @param array $fileInfo
     * @return bool
     */
    private function validateFileType($fileInfo)
    {
        $allowedTypes = array_merge(
            config('project.file_image', []),
            config('project.file_video', []),
            config('project.file_audio', []),
            config('project.file_document', [])
        );

        return in_array(strtolower($fileInfo['ext']), $allowedTypes);
    }

    /**
     * 获取上传URL
     * @param string $path
     * @return string
     */
    private function getUploadUrl($path)
    {
        return rtrim($path, '/');
    }

    /**
     * 记录已上传文件
     * @param array $fileInfo
     */
    private function markFileAsUploaded($fileInfo)
    {
        Db::name('oss_upload_records')->insert([
            'file_path' => $fileInfo['relativePath'],
            'file_hash' => $fileInfo['fileHash'],
            'oss_type' => $this->ossType,
            'file_type' => $fileInfo['fileInfo']['type'],
            'create_time' => time(),
            'update_time' => time()
        ]);

    }


    private function isFileUploaded($relativePath)
    {
        return Db::name('oss_upload_records')
            ->where('file_path', $relativePath)
            ->where('oss_type', $this->ossType)
            ->find();
    }


    /**
     * 保存上传进度
     * @param int $index
     * @param int $total
     */
    private function saveProgress($index, $total)
    {
        $progress = [
            'index' => $index,
            'total' => $total,
            'timestamp' => time()
        ];
        file_put_contents($this->progressFile, json_encode($progress));
    }

    /**
     * 获取上次上传进度
     * @return array
     */
    private function getLastProgress()
    {
        if (file_exists($this->progressFile)) {
            return json_decode(file_get_contents($this->progressFile), true) ?: [];
        }
        return [];
    }

    /**
     * 清理进度文件
     */
    private function clearProgress()
    {
        if (file_exists($this->progressFile)) {
            unlink($this->progressFile);
        }
    }

    /**
     * 定时任务入口
     */
    public static function migrationCron()
    {
        try {
            $ossLogic = new OssLogic();

            if($ossLogic->migration != 1){
                echo '没有要迁移的oss';
                return ;
            }
            // 获取上传目录的绝对路径
            $uploadedFiles = public_path() . 'uploads';

            // 检查目录是否存在
            if (!is_dir($uploadedFiles)) {
                throw new Exception("上传目录不存在: {$uploadedFiles}");
            }

            $ossLogic->uploadFilesInBatches(
                $uploadedFiles,
                'uploads',
                20
            );
        } catch (Exception $e) {
            Log::error("OSS迁移任务执行失败: " . $e->getMessage());
        }
    }

}
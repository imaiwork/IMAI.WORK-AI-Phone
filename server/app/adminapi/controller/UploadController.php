<?php

namespace app\adminapi\controller;


use app\common\service\UploadService;
use Exception;
use PhpOffice\PhpWord\Shared\ZipArchive;
use think\response\Json;

/**
 * 上传文件
 * Class UploadController
 * @package app\adminapi\controller
 */
class UploadController extends BaseAdminController
{
    /**
     * @notes 上传图片
     * @return Json
     * @author 段誉
     * @date 2021/12/29 16:27
     */
    public function image()
    {
        try {
            $cid = $this->request->post('cid', 0);
            $result = UploadService::image($cid);
            return $this->success('上传成功', $result);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * @notes 上传视频
     * @return Json
     * @author 段誉
     * @date 2021/12/29 16:27
     */
    public function video()
    {
        try {
            $cid = $this->request->post('cid', 0);
            $result = UploadService::video($cid);
            return $this->success('上传成功', $result);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }
    /**
     * @notes 上传视频
     * @return Json
     * @author 段誉
     * @date 2021/12/29 16:27
     */
    public function audio()
    {
        try {
            $cid = $this->request->post('cid', 0);
            $result = UploadService::audio($cid);
            return $this->success('上传成功', $result);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }
    /**
     * @notes 上传文件
     * @return Json
     * @author dw
     * @date 2023/06/26
     */
    public function file()
    {
        try {
            $cid = $this->request->post('cid', 0);
            $result = UploadService::file($cid);
            return $this->success('上传成功', $result);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }


    /**
     * @notes 上传文件
     * @return Json
     * @author dw
     * @date 2023/06/26
     */
    public function gptfile()
    {
        try {
            $cid = $this->request->post('cid', 0);
            $result = UploadService::gptfile($cid);
            return $this->success('上传成功', $result);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * @notes 上传并解压文件
     * @return Json
     * @author Rick
     * @date 2025/05/29
     */
    public function zipfile(): Json
    {
        try {
            $cid = $this->request->post('cid', 0);
            $result = UploadService::zipfile($cid);
            //解压
            if (isset($result['url'])){
                $zip = new ZipArchive();
                $extractDir = '../extend/miniprogram-ci/';
                if ($zip->open($result['url'])===true) {
                    // 检查解压目录权限
                    if (!is_writable($extractDir)) {
                        $result['message'] = '目标目录不可写，无法解压文件。';
                        $zip->close();
                        echo json_encode($result);
                        exit;
                    }
                    // 开始解压
                    $extractedFiles = [];
                    $errorCount = 0;
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $fileInfo = $zip->statIndex($i);
                        $fileName = $fileInfo['name'];
                        $filePath = $extractDir . $fileName;
                        // 检查是否为目录
                        if (substr($fileName, -1) === '/') {
                            // 创建目录（如果不存在）
                            if (!is_dir($filePath)) {
                                mkdir($filePath, 0755, true);
                            }
                            $extractedFiles[] = ['type' => 'directory', 'path' => $fileName];
                        } else {
                            // 创建父目录（如果不存在）
                            $parentDir = dirname($filePath);
                            if (!is_dir($parentDir)) {
                                mkdir($parentDir, 0755, true);
                            }
                            // 提取文件
                            $fileContent = $zip->getFromIndex($i);
                            if (file_put_contents($filePath, $fileContent) === false) {
                                $errorCount++;
                                $result['errors'][] = "无法解压文件：$fileName";
                            } else {
                                $extractedFiles[] = ['type' => 'file', 'path' => $fileName];
                            }
                        }
                    }
                    $zip->close();
                    // 返回结果
                    if ($errorCount > 0) {
                        $result['message'] = "解压完成，但有 $errorCount 个文件失败。";
                    } else {
                        $result['success'] = true;
                        $result['message'] = '文件已成功上传并解压。';
                    }
//                    $result['extractedFiles'] = $extractedFiles;
                }
            } else {
                $result['message'] = '无法打开ZIP文件。';
            }
            return $this->success($result['message'], $result);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }
}

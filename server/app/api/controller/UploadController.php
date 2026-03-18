<?php


namespace app\api\controller;

use app\common\enum\FileEnum;
use app\common\model\lianlian\LlConversation;
use app\common\service\UploadService;
use Exception;
use think\response\Json;


/** 上传文件
 * Class UploadController
 * @package app\api\controller
 */
class UploadController extends BaseApiController
{
    public array $notNeedLogin = ['wechatUpload','svfile','screenshot'];

    /**
     * @notes 上传图片
     * @return Json
     * @author 段誉
     * @date 2022/9/20 18:11
     */
    public function image()
    {
        try {
            $cid = $this->request->post('cid', 0);
            $ffmpeg = $this->request->post('ffmpeg', 0);
            $result = UploadService::image($cid, $this->userId, FileEnum::SOURCE_USER,'uploads/images',$ffmpeg);
            return $this->success('上传成功', $result);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * @notes 上传文件
     * @return Json
     * @author 段誉
     * @date 2022/9/20 18:11
     */
    public function file()
    {
        try {
            $cid = $this->request->post('cid', 0);
            $ffmpeg = $this->request->post('ffmpeg', 0);
            $clip = $this->request->post('clip', []);
            $result = UploadService::file($cid, $this->userId, FileEnum::SOURCE_USER,'uploads/file',$ffmpeg,$clip);
            return $this->success('上传成功', $result);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }


    /**
     * @notes 上传文件
     * @return Json
     * @author 段誉
     * @date 2022/9/20 18:11
     */
    public function csvFile()
    {
        try {
            $result = UploadService::csvFile(0, $this->userId, FileEnum::SOURCE_USER);
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
            $ffmpeg = $this->request->post('ffmpeg', 0);
            $clip = $this->request->post('clip', []);
            
            $result = UploadService::video($cid, $this->userId, FileEnum::SOURCE_USER,'uploads/video',$ffmpeg,$clip);
            return $this->success('上传成功', $result);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }


    /**
     * @notes 上传文件
     * @return Json
     * @author 段誉
     * @date 2022/9/20 18:11
     */
    public function audio()
    {
        set_time_limit(0);
        try {
            $result = UploadService::audio(0, $this->userId, FileEnum::SOURCE_USER);
            return $this->success('上传成功', $result);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

 
    public function wechatUpload(){
        try {
            $result = UploadService::wechatUpload(0, 0, FileEnum::SOURCE_WECHAT);
            return json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }


    public function svfile()
    {
        try {
            $result = UploadService::file(0, 0, FileEnum::SOURCE_USER);
            return $this->success('上传成功', $result);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function screenshot(){
        try {
            $params = $this->request->post();
            $result = UploadService::screenshot($params);
            return $this->success('上传成功', $result);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

}

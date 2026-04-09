<?php

namespace app\api\controller;

use app\common\enum\FileEnum;
use app\common\model\lianlian\LlConversation;
use app\common\service\UploadService;
use Exception;
use think\response\Json;

/**
 * 上传文件控制器
 * Class UploadController
 * @package app\api\controller
 */
class UploadController extends BaseApiController
{
    public array $notNeedLogin = ['wechatUpload', 'svfile', 'screenshot'];

    /**
     * @notes 上传图片
     * @return Json
     * @author 段誉
     * @date 2022/9/20 18:11
     */
    public function image(): Json
    {
        try {
            $cid = $this->request->post('cid/d', 0);
            $ffmpeg = $this->request->post('ffmpeg/d', 0);

            $result = UploadService::image(
                $cid,
                $this->userId,
                FileEnum::SOURCE_USER,
                'uploads/images',
                $ffmpeg
            );

            return $this->success('上传成功', $result);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * @notes 上传文件（支持视频缩略图）
     * @return Json
     * @author 段誉
     * @date 2022/9/20 18:11
     */
    public function file(): Json
    {
        try {
            // 获取基础参数
            $cid = $this->request->post('cid/d', 0);
            $ffmpeg = $this->request->post('ffmpeg/d', 0);
            $clip = $this->request->post('clip/a', []);

            // 获取缩略图相关参数
            $generateThumbnail = $this->request->post('generate_thumbnail/b', false);
            $thumbnailOptions = $this->getThumbnailOptions();
            $fetchVideoInfo = $this->request->post('fetch_video_info/b', false);
            $result = UploadService::file(
                $cid,
                $this->userId,
                FileEnum::SOURCE_USER,
                'uploads/file',
                $ffmpeg,
                $clip,
                $generateThumbnail,
                $thumbnailOptions,
                $fetchVideoInfo
            );

            return $this->success('上传成功', $result);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * @notes 上传CSV文件
     * @return Json
     * @author 段誉
     * @date 2022/9/20 18:11
     */
    public function csvFile(): Json
    {
        try {
            $result = UploadService::csvFile(
                0,
                $this->userId,
                FileEnum::SOURCE_USER
            );

            return $this->success('上传成功', $result);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * @notes 上传视频（支持自动生成缩略图）
     * @return Json
     * @author 段誉
     * @date 2021/12/29 16:27
     */
    public function video(): Json
    {
        try {
            // 获取基础参数
            $cid = $this->request->post('cid/d', 0);
            $ffmpeg = $this->request->post('ffmpeg/d', 0);
            $clip = $this->request->post('clip/a', []);

            // 获取缩略图相关参数
            $generateThumbnail = $this->request->post('generate_thumbnail/b', false);
            $thumbnailOptions = $this->getThumbnailOptions();
            $fetchVideoInfo = $this->request->post('fetch_video_info/b', false);
            $result = UploadService::video(
                $cid,
                $this->userId,
                FileEnum::SOURCE_USER,
                'uploads/video',
                $ffmpeg,
                $clip,
                $generateThumbnail,
                $thumbnailOptions,
                $fetchVideoInfo
            );

            return $this->success('上传成功', $result);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * @notes 上传音频
     * @return Json
     * @author 段誉
     * @date 2022/9/20 18:11
     */
    public function audio(): Json
    {
        set_time_limit(0);

        try {
            $result = UploadService::audio(
                0,
                $this->userId,
                FileEnum::SOURCE_USER
            );

            return $this->success('上传成功', $result);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * @notes 微信上传
     * @return false|string
     */
    public function wechatUpload()
    {
        try {
            $result = UploadService::wechatUpload(
                0,
                0,
                FileEnum::SOURCE_WECHAT
            );

            return json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * @notes 上传文件（无需登录）
     * @return Json
     */
    public function svfile(): Json
    {
        try {
            // 获取缩略图相关参数
            $generateThumbnail = $this->request->post('generate_thumbnail/b', false);
            $thumbnailOptions = $this->getThumbnailOptions();

            $result = UploadService::file(
                0,
                0,
                FileEnum::SOURCE_USER,
                'uploads/file',
                0,
                [],
                $generateThumbnail,
                $thumbnailOptions
            );

            return $this->success('上传成功', $result);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * @notes 上传截图（Base64）
     * @return Json
     */
    public function screenshot(): Json
    {
        try {
            $params = $this->request->post();
            $result = UploadService::screenshot($params);

            return $this->success('上传成功', $result);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * @notes 获取缩略图配置选项
     * @return array
     */
    private function getThumbnailOptions(): array
    {
        $options = [];

        // 截取时间点（秒）
        if ($this->request->has('thumbnail_time')) {
            $options['time'] = floatval($this->request->post('thumbnail_time', 1.0));
        }

        // 缩略图宽度
        if ($this->request->has('thumbnail_width')) {
            $options['width'] = intval($this->request->post('thumbnail_width'));
        }

        // 缩略图高度
        if ($this->request->has('thumbnail_height')) {
            $options['height'] = intval($this->request->post('thumbnail_height'));
        }

        // 缩略图质量（1-31，数字越小质量越高）
        if ($this->request->has('thumbnail_quality')) {
            $options['quality'] = intval($this->request->post('thumbnail_quality'));
        }

        // 缩略图格式（jpg/png）
        if ($this->request->has('thumbnail_format')) {
            $format = $this->request->post('thumbnail_format');
            if (in_array($format, ['jpg', 'png'])) {
                $options['format'] = $format;
            }
        }

        // 是否强制重新生成
        if ($this->request->has('thumbnail_force')) {
            $options['force'] = boolval($this->request->post('thumbnail_force'));
        }

        return $options;
    }
}

<?php

namespace app\api\controller;

use app\common\enum\FileEnum;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\Material;
use app\common\model\lianlian\LlConversation;
use app\common\service\UploadService;
use Exception;
use think\facade\Log;
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
            $personaId = $this->request->post('persona_id/d', 0);

            $logParams = $this->request->post();
            unset($logParams['file']);
            Log::channel('upload_image')->write('图片上传参数: ' . json_encode([
                'user_id' => $this->userId,
                'ip'      => $this->request->ip(),
                'params'  => $logParams,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            $result = UploadService::image(
                $cid,
                $this->userId,
                FileEnum::SOURCE_USER,
                'uploads/images',
                $ffmpeg
            );

            $material = $this->createPersonaImageMaterial($result, $personaId, $ffmpeg);
            if (!empty($material['id'])) {
                $result['material_id'] = (int)$material['id'];
            }

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
            $personaId = $this->request->post('persona_id/d', 0);

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
                $fetchVideoInfo,
                $personaId
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
            $personaId = $this->request->post('persona_id/d', 0);
            $scene = (string)$this->request->post('scene', 'persona');
            if (!in_array($scene, ['ai_creation', 'persona'], true)) {
                $scene = 'persona';
            }
            // ffmpeg=2 自动切割入库必须带人设；ffmpeg=1 仅转码，可不传 persona_id（前端再调素材新增）
            if ((int)$ffmpeg === 2 && $personaId <= 0) {
                return $this->fail('切割上传必须传 persona_id');
            }

            // 获取缩略图相关参数
            $generateThumbnail = $this->request->post('generate_thumbnail/b', false);
            $thumbnailOptions = $this->getThumbnailOptions();
            $fetchVideoInfo = $this->request->post('fetch_video_info/b', false);

            $logParams = $this->request->post();
            unset($logParams['file']);
            Log::channel('upload_video')->write('视频上传参数: ' . json_encode([
                'user_id' => $this->userId,
                'ip'      => $this->request->ip(),
                'params'  => $logParams,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            $result = UploadService::video(
                $cid,
                $this->userId,
                FileEnum::SOURCE_USER,
                'uploads/video',
                $ffmpeg,
                $clip,
                $generateThumbnail,
                $thumbnailOptions,
                $fetchVideoInfo,
                $personaId,
                '',
                $scene
            );

            // ffmpeg=0：原始完整视频立即入库，不转码、不切割、不扣费（需 persona_id）。
            // ffmpeg=1：仅异步转码；传了 persona_id 则转码队列自动入库，否则由前端调素材新增。
            // ffmpeg=2：转码成功后自动发起切割（预扣+入队），必须传 persona_id。
            $material = $this->createPersonaOriginalVideoMaterial($result, $personaId, $ffmpeg);
            if (!empty($material['id'])) {
                $result['material_id'] = (int)$material['id'];
            }

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
     * @notes 图片上传后自动写入IP人设素材库
     */
    private function createPersonaImageMaterial(array $uploadResult, int $personaId, int $ffmpeg): array
    {
        if (!in_array($ffmpeg, [1, 2], true) || $personaId <= 0) {
            return [];
        }

        try {
            $persona = AiPersona::where('user_id', $this->userId)
                ->where('id', $personaId)
                ->findOrEmpty();
            if ($persona->isEmpty()) {
                Log::channel('upload_image')->write('图片上传素材入库跳过: ' . json_encode([
                    'user_id'    => $this->userId,
                    'persona_id' => $personaId,
                    'reason'     => 'IP人设不存在',
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                return [];
            }

            $fileUrl = (string)($uploadResult['url'] ?? '');
            if ($fileUrl === '') {
                Log::channel('upload_image')->write('图片上传素材入库跳过: ' . json_encode([
                    'user_id'    => $this->userId,
                    'persona_id' => $personaId,
                    'reason'     => '上传结果缺少url',
                    'result'     => $uploadResult,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                return [];
            }

            $now = time();
            $material = Material::create([
                'persona_id'    => $personaId,
                'user_id'       => $this->userId,
                'material_name' => (string)($uploadResult['name'] ?? ''),
                'material_type' => Material::MATERIAL_TYPE_IMAGE,
                'file_url'      => $fileUrl,
                'thumbnail_url' => (string)($uploadResult['thumbnail_url'] ?? $fileUrl),
                'duration'      => 0,
                'width'         => (int)($uploadResult['width'] ?? 0),
                'height'        => (int)($uploadResult['height'] ?? 0),
                'use_status'    => Material::USE_STATUS_ENABLED,
                'publish_mode'  => (int)($persona->publish_mode ?? Material::PUBLISH_MODE_MAKE_VIDEO),
                'create_time'   => $now,
                'update_time'   => $now,
            ]);

            $materialData = $material->toArray();
            Log::channel('upload_image')->write('图片上传素材入库成功: ' . json_encode([
                'user_id'     => $this->userId,
                'persona_id'  => $personaId,
                'material_id' => $materialData['id'] ?? 0,
                'file_url'    => $fileUrl,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            return $materialData;
        } catch (\Throwable $e) {
            Log::channel('upload_image')->write('图片上传素材入库失败: ' . json_encode([
                'user_id'    => $this->userId,
                'persona_id' => $personaId,
                'error'      => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return [];
        }
    }

    /**
     * @notes ffmpeg=0 视频上传后，原始完整视频立即入库为可用素材
     */
    private function createPersonaOriginalVideoMaterial(array $uploadResult, int $personaId, int $ffmpeg): array
    {
        if ((int)$ffmpeg !== 0 || $personaId <= 0) {
            return [];
        }
        if ((int)($uploadResult['type'] ?? 0) !== FileEnum::VIDEO_TYPE) {
            return [];
        }

        try {
            $persona = AiPersona::where('user_id', $this->userId)
                ->where('id', $personaId)
                ->findOrEmpty();
            if ($persona->isEmpty()) {
                return [];
            }

            $materialId = \app\common\service\ffmpeg\MaterialService::publishUploadedVideo(
                (int)($uploadResult['id'] ?? 0),
                $personaId,
                $this->userId,
                [
                    'file_url'      => (string)($uploadResult['url'] ?? ''),
                    'name'          => pathinfo((string)($uploadResult['name'] ?? ''), PATHINFO_FILENAME),
                    'thumbnail_url' => (string)($uploadResult['thumbnail_url'] ?? ''),
                    'duration'      => (float)($uploadResult['duration'] ?? 0),
                    'width'         => (int)($uploadResult['width'] ?? 0),
                    'height'        => (int)($uploadResult['height'] ?? 0),
                ]
            );

            return $materialId > 0 ? ['id' => $materialId] : [];
        } catch (\Throwable $e) {
            Log::channel('upload_video')->write('视频上传原视频入库失败: ' . json_encode([
                'user_id'    => $this->userId,
                'persona_id' => $personaId,
                'file_id'    => (int)($uploadResult['id'] ?? 0),
                'error'      => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return [];
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

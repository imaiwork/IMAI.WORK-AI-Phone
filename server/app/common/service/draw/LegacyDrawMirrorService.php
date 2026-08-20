<?php

declare(strict_types=1);

namespace app\common\service\draw;

use app\common\enum\draw\DrawEnum;
use app\common\model\draw\DrawAsset;
use app\common\model\draw\DrawTask;
use app\common\model\draw\DrawVideo;
use app\common\model\hd\HdImage;
use app\common\model\hd\HdLog;
use think\facade\Log;

/**
 * 双写适配器：把新链路（la_draw_task/asset）的数据同步写入旧表，
 * 供现有前端/后台列表展示。新表为主、旧表为镜像；失败只记日志不阻断主流程。
 *
 * 图片 -> la_hd_log + la_hd_image
 * 视频 -> la_draw_video
 */
class LegacyDrawMirrorService
{
    /**
     * 幂等镜像：按 draw_task_id upsert 旧表
     */
    public static function mirror(DrawTask $task): void
    {
        try {
            if ($task->media_type === DrawEnum::MEDIA_VIDEO) {
                self::mirrorVideo($task);
            } else {
                self::mirrorImage($task);
            }
        } catch (\Throwable $e) {
            Log::warning('draw legacy mirror failed: ' . $e->getMessage(), [
                'task_no' => $task->task_no,
                'media'   => $task->media_type,
            ]);
        }
    }

    private static function mirrorImage(DrawTask $task): void
    {
        $params = is_array($task->params) ? $task->params : [];
        $isImg2img = self::hasInputImage($params);

        $log = HdLog::where('draw_task_id', $task->id)->findOrEmpty();
        $data = [
            'user_id'      => (int)$task->user_id,
            'type'         => $isImg2img ? 4 : 3, // 3文生图 4图生图
            'params'       => json_encode($params, JSON_UNESCAPED_UNICODE),
            'task_id'      => (string)$task->task_no,
            'request_id'   => (string)$task->request_id,
            'task_status'  => self::mapImageStatus((int)$task->status),
            'remark'       => (string)$task->error_msg,
            'model'        => (string)$task->model,
            'draw_task_id' => (int)$task->id,
        ];

        if ($log->isEmpty()) {
            $data['sub_task_ids'] = '';
            $log = HdLog::create($data);
        } else {
            HdLog::update($data, ['id' => $log->id]);
        }

        // 成功后写子图（仅在未写过时补齐）
        if ((int)$task->status === DrawEnum::STATUS_SUCCESS) {
            $exists = HdImage::where('log_id', $log->id)->count();
            if ($exists === 0) {
                $assets = DrawAsset::where('task_id', $task->id)
                    ->where('asset_type', DrawEnum::ASSET_IMAGE)
                    ->order('sort', 'asc')
                    ->select();
                foreach ($assets as $asset) {
                    HdImage::create([
                        'log_id'          => (int)$log->id,
                        'image'           => (string)$asset->file_url,
                        'sub_task_id'     => '',
                        'task_status'     => 1,
                        'task_completion' => 1,
                    ]);
                }
            }
        }
    }

    private static function mirrorVideo(DrawTask $task): void
    {
        $params = is_array($task->params) ? $task->params : [];
        $isImg2video = self::hasInputImage($params);

        $video = DrawVideo::where('draw_task_id', $task->id)->findOrEmpty();
        $data = [
            'user_id'      => (int)$task->user_id,
            'task_id'      => (string)$task->task_no,
            'request_id'   => (string)$task->request_id,
            'model'        => 0,
            'model_name'   => (string)$task->model,
            'type'         => $isImg2video ? 1 : 0, // 0文生视频 1图生视频
            'prompt'       => mb_substr((string)$task->prompt, 0, 1000),
            'desc'         => mb_substr((string)$task->prompt, 0, 1000),
            'task_status'  => self::mapVideoStatus((int)$task->status),
            'remark'       => mb_substr((string)$task->error_msg, 0, 255),
            'draw_task_id' => (int)$task->id,
        ];

        $aspectRatio = self::resolveAspectRatio($params);
        if ($aspectRatio !== null) {
            $data['aspect_ratio'] = $aspectRatio;
        }
        $inputImage = self::firstInputImage($params);
        if ($inputImage !== '') {
            $data['image_url'] = mb_substr($inputImage, 0, 500);
        }

        // 成功后补产物地址
        if ((int)$task->status === DrawEnum::STATUS_SUCCESS) {
            $videoAsset = DrawAsset::where('task_id', $task->id)
                ->where('asset_type', DrawEnum::ASSET_VIDEO)
                ->order('sort', 'asc')
                ->find();
            $coverAsset = DrawAsset::where('task_id', $task->id)
                ->where('asset_type', DrawEnum::ASSET_COVER)
                ->order('sort', 'asc')
                ->find();
            if ($videoAsset) {
                $data['video_url'] = (string)$videoAsset->file_url;
            }
            if ($coverAsset) {
                $data['cover_url'] = mb_substr((string)$coverAsset->file_url, 0, 500);
            }
        }

        if ($video->isEmpty()) {
            DrawVideo::create($data);
        } else {
            DrawVideo::update($data, ['id' => $video->id]);
        }
    }

    /**
     * 新状态 -> hd_log.task_status(0等待 1成功 2失败)
     */
    private static function mapImageStatus(int $status): int
    {
        return match ($status) {
            DrawEnum::STATUS_SUCCESS => 1,
            DrawEnum::STATUS_FAILED, DrawEnum::STATUS_CANCELLED => 2,
            default => 0,
        };
    }

    /**
     * 新状态 -> draw_video.task_status(-1失败 0等待 1成功 2处理中)
     */
    private static function mapVideoStatus(int $status): int
    {
        return match ($status) {
            DrawEnum::STATUS_SUCCESS => 1,
            DrawEnum::STATUS_FAILED, DrawEnum::STATUS_CANCELLED => -1,
            DrawEnum::STATUS_PROCESSING => 2,
            default => 0,
        };
    }

    private static function hasInputImage(array $params): bool
    {
        return self::firstInputImage($params) !== '';
    }

    private static function firstInputImage(array $params): string
    {
        foreach (['image_url', 'image', 'images'] as $key) {
            if (empty($params[$key])) {
                continue;
            }
            $val = $params[$key];
            if (is_array($val)) {
                $first = reset($val);
                if (is_string($first) && $first !== '') {
                    return $first;
                }
            } elseif (is_string($val) && $val !== '') {
                return $val;
            }
        }
        return '';
    }

    /**
     * 仅当 params 中的比例命中旧表 enum 时才回写，否则用旧表默认值
     */
    private static function resolveAspectRatio(array $params): ?string
    {
        $allowed = ['1:1', '3:4', '4:3', '9:16', '16:9', '21:9'];
        $ratio = (string)($params['aspect_ratio'] ?? '');
        return in_array($ratio, $allowed, true) ? $ratio : null;
    }
}

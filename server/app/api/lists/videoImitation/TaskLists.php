<?php

namespace app\api\lists\videoImitation;

use app\api\lists\BaseApiDataLists;
use app\common\model\videoImitation\VideoImitationTask;
use app\common\service\FileService;
use app\common\service\ShanjianQueueService;

/**
 * 视频复刻任务列表
 */
class TaskLists extends BaseApiDataLists
{
    /**
     * 搜索条件
     * @return array
     */
    public function queryWhere(): array
    {
        $where = [];
        $where[] = ['user_id', '=', $this->userId];
        $where[] = ['task_delete', '=', 0];

        // 状态筛选
        if (isset($this->params['status']) && $this->params['status'] !== '') {
            if ($this->params['status'] == 3) {
                $where[] = ['status', '=', $this->params['status']];
            } else {
                $status = explode(',', $this->params['status']);
                $where[] = ['status', 'in', $status];
            }
        }

        // 人设筛选
        if (!empty($this->params['persona_id'])) {
            $where[] = ['persona_id', '=', $this->params['persona_id']];
        }

        // 媒体类型：1视频仿写 2图文仿写；不传则全部返回（兼容旧前端）
        if (isset($this->params['media_type']) && $this->params['media_type'] !== '') {
            $mediaType = (int)$this->params['media_type'];
            if (in_array($mediaType, [VideoImitationTask::MEDIA_TYPE_VIDEO, VideoImitationTask::MEDIA_TYPE_IMAGE_TEXT], true)) {
                $where[] = ['media_type', '=', $mediaType];
            }
        }

        $where[] = ['task_delete', '=', 0];

        return $where;
    }

    /**
     * 获取列表
     * @return array
     */
    public function lists(): array
    {
        $lists = VideoImitationTask::where($this->queryWhere())
            ->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->withTrashed()
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item['queue_status_text'] = ShanjianQueueService::statusText(
                (string)($item['queue_status'] ?? ''),
                (int)($item['queue_position'] ?? 0)
            );
            $item['video_url'] = !empty($item['video_url']) ? FileService::getFileUrl($item['video_url']) : '';
            $item['thumbnail'] = !empty($item['thumbnail']) ? FileService::getFileUrl($item['thumbnail']) : '';
            if (!empty($item['analysis_tags']) && is_string($item['analysis_tags'])) {
                $item['analysis_tags'] = json_decode($item['analysis_tags'], true) ?: [];
            }

            $taskModel = new VideoImitationTask();
            $taskModel->data($item);
            $originalImages = is_array($taskModel->original_images) ? $taskModel->original_images : [];
            $selectedImages = is_array($taskModel->selected_images) ? $taskModel->selected_images : [];
            $rewrittenImages = is_array($taskModel->rewritten_images) ? $taskModel->rewritten_images : [];
            // JSON 字段在 toArray 后可能仍是字符串，再兜底解析
            if (empty($originalImages) && !empty($item['original_images']) && is_string($item['original_images'])) {
                $originalImages = json_decode($item['original_images'], true) ?: [];
            }
            if (empty($selectedImages) && !empty($item['selected_images']) && is_string($item['selected_images'])) {
                $selectedImages = json_decode($item['selected_images'], true) ?: [];
            }
            if (empty($rewrittenImages) && !empty($item['rewritten_images']) && is_string($item['rewritten_images'])) {
                $rewrittenImages = json_decode($item['rewritten_images'], true) ?: [];
            }
            $item['original_images'] = array_values(array_map(
                static fn($u) => FileService::getFileUrl((string)(is_array($u) ? ($u['url'] ?? '') : $u)),
                $originalImages
            ));
            $item['selected_images'] = array_values(array_map(
                static fn($u) => FileService::getFileUrl((string)(is_array($u) ? ($u['url'] ?? '') : $u)),
                $selectedImages
            ));
            $item['rewritten_images'] = array_values(array_map(
                static fn($u) => FileService::getFileUrl((string)(is_array($u) ? ($u['url'] ?? '') : $u)),
                $rewrittenImages
            ));
            $item['image_count'] = count($item['rewritten_images']) > 0
                ? count($item['rewritten_images'])
                : count($item['original_images']);
            $item['platform_type'] = (int)($item['platform_type'] ?? 4);
            $item['media_type'] = (int)($item['media_type'] ?? 1);
            $item['image_rewrite_status'] = (int)($item['image_rewrite_status'] ?? 0);
            $item['progress_steps'] = \app\api\logic\videoImitation\TaskLogic::buildProgressSteps($taskModel);
        }

        return $lists;
    }

    /**
     * 获取总数
     * @return int
     */
    public function count(): int
    {
        return VideoImitationTask::where($this->queryWhere())->count();
    }
}

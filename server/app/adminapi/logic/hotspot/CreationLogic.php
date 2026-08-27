<?php

namespace app\adminapi\logic\hotspot;

use app\common\logic\BaseLogic;
use app\common\model\hotspot\HotspotCreation;
use app\common\model\hotspot\HotspotTask;
use app\common\model\user\User;
use app\common\service\hotspot\HotspotLog;
use app\common\service\hotspot\RecordService;
use app\common\service\hotspot\TaskService;

class CreationLogic extends BaseLogic
{
    public static function detail(int $id): array|false
    {
        $row = HotspotCreation::where('id', $id)->findOrEmpty();
        if ($row->isEmpty()) {
            self::setError('记录不存在');
            return false;
        }

        $taskNo = (string)$row->task_no;
        $task = null;
        if ($taskNo !== '') {
            $task = HotspotTask::where('task_no', $taskNo)->findOrEmpty();
            if ($task->isEmpty()) {
                $task = null;
            }
        }
        $creationStatus = (string)$row->status;
        $taskStatus = RecordService::resolveTaskStatus(
            $creationStatus,
            $taskNo,
            $task ? (string)$task->status : ''
        );
        $taskError = $task ? (string)$task->error : '';
        $videoUrl = $task ? TaskService::formatVideoUrl((string)$task->video_url) : '';
        $sjTaskId = $task ? TaskService::resolveBoundVideoTaskId($task) : 0;
        $nickname = '';
        if ((int)$row->user_id > 0) {
            $nickname = (string)User::where('id', (int)$row->user_id)->value('nickname');
        }

        return [
            'id' => (int)$row->id,
            'record_no' => (string)$row->record_no,
            'user' => $nickname !== '' ? $nickname : '体验用户',
            'type' => '视频',
            'platform' => (string)$row->platform,
            'title' => (string)$row->title,
            'topic' => (string)$row->topic,
            'persona_name' => (string)$row->persona_name,
            'duration_sec' => (int)$row->duration_sec,
            'status' => $creationStatus,
            'task_no' => $taskNo,
            'task_status' => $taskStatus,
            'task_error' => $taskError,
            'video_url' => $videoUrl,
            'shanjian_video_task_id' => $sjTaskId,
            'remark' => RecordService::buildRemark(
                (string)$row->status,
                $taskStatus,
                $taskError,
                (string)$row->material_mode
            ),
            'goal' => (string)$row->goal,
            'direction' => (string)$row->direction,
            'material_mode' => (string)$row->material_mode,
            'video_type' => (string)$row->video_type,
            'avatar' => (string)$row->avatar,
            'script' => (string)$row->script,
            'word_count' => (int)$row->word_count,
            'est_duration_sec' => (int)$row->est_duration_sec,
            'hashtags' => is_array($row->hashtags_json) ? $row->hashtags_json : [],
            'shots' => is_array($row->shots_json) ? $row->shots_json : [],
            'create_time' => (string)$row->create_time,
        ];
    }

    public static function delete(mixed $id): bool
    {
        $ids = self::normalizeIds($id);
        if ($ids === []) {
            self::setError('id参数缺失');
            return false;
        }
        $rows = HotspotCreation::whereIn('id', $ids)->select();
        if ($rows->isEmpty()) {
            self::setError('记录不存在');
            return false;
        }
        foreach ($rows as $row) {
            $row->delete();
        }
        HotspotLog::write('后台删除创作记录：数量=' . count($rows));
        return true;
    }

    private static function normalizeIds(mixed $id): array
    {
        if (is_array($id)) {
            $raw = $id;
        } elseif ($id === '' || $id === null) {
            $raw = [];
        } else {
            $raw = [$id];
        }
        $out = [];
        foreach ($raw as $item) {
            $item = (int)$item;
            if ($item > 0) {
                $out[] = $item;
            }
        }
        return array_values(array_unique($out));
    }
}

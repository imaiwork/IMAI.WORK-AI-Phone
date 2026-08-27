<?php

namespace app\adminapi\lists\hotspot;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\hotspot\HotspotTask;
use app\common\model\user\User;
use app\common\service\hotspot\TaskService;

class TaskLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [];
    }

    public function lists(): array
    {
        $rows = $this->buildQuery()
            ->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select();
        if ($rows->isEmpty()) {
            return [];
        }

        $userMap = User::whereIn('id', array_column($rows->toArray(), 'user_id'))->column('nickname', 'id');
        $out = [];
        foreach ($rows as $row) {
            $options = is_array($row->options_json) ? $row->options_json : [];
            $userId = (int)$row->user_id;
            $nickname = trim((string)($userMap[$userId] ?? ''));
            $out[] = [
                'id' => (int)$row->id,
                'task_no' => (string)$row->task_no,
                'user' => $nickname !== '' ? $nickname : '体验用户',
                'topic' => (string)$row->topic,
                'platform' => (string)$row->platform,
                'title' => (string)$row->title,
                'persona_name' => (string)(is_array($row->persona_json) ? ($row->persona_json['name'] ?? '') : ''),
                'video_type' => (string)($options['video_type'] ?? ''),
                'status' => (string)$row->status,
                'dispatch_status' => (string)($options['dispatch_status'] ?? 'pending'),
                'retry_seq' => (int)($options['retry_seq'] ?? 0),
                'error' => (string)$row->error,
                'video_url' => TaskService::formatVideoUrl((string)$row->getData('video_url')),
                'shanjian_video_task_id' => (int)$row->shanjian_video_task_id,
                'create_time' => (string)$row->create_time,
                'update_time' => (string)$row->update_time,
            ];
        }
        return $out;
    }

    public function count(): int
    {
        return $this->buildQuery()->count();
    }

    private function buildQuery()
    {
        $query = HotspotTask::field([
            'id', 'task_no', 'user_id', 'topic', 'platform', 'title', 'persona_json',
            'options_json', 'status', 'error', 'video_url', 'shanjian_video_task_id',
            'create_time', 'update_time',
        ]);

        // 状态白名单在此兜底（validate 场景不校验 status，见 HotspotAdminValidate::sceneTaskLists）
        $status = trim((string)($this->params['status'] ?? ''));
        if ($status !== '' && in_array($status, ['running', 'wait', 'done', 'fail'], true)) {
            $query->where('status', $status);
        }

        $keyword = trim((string)($this->params['keyword'] ?? ''));
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->whereLike('topic', '%' . $keyword . '%')
                    ->whereOr('title', 'like', '%' . $keyword . '%')
                    ->whereOr('task_no', 'like', '%' . $keyword . '%');
            });
        }

        $user = trim((string)($this->params['user'] ?? ''));
        if ($user !== '') {
            $userIds = User::where('mobile|nickname', 'like', '%' . $user . '%')->column('id');
            if ($userIds === []) {
                $query->where('user_id', -1);
            } else {
                $query->whereIn('user_id', $userIds);
            }
        }

        $startTime = trim((string)($this->params['start_time'] ?? ''));
        $endTime = trim((string)($this->params['end_time'] ?? ''));
        if ($startTime !== '' && $endTime !== '') {
            $query->whereBetween('create_time', [strtotime($startTime), strtotime($endTime)]);
        }

        return $query;
    }
}

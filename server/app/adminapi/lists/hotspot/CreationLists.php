<?php

namespace app\adminapi\lists\hotspot;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\hotspot\HotspotCreation;
use app\common\model\hotspot\HotspotTask;
use app\common\model\user\User;
use app\common\service\hotspot\RecordService;
use app\common\service\hotspot\TaskService;

class CreationLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [];
    }

    public function lists(): array
    {
        $query = $this->buildQuery();
        $rows = $query->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
        if ($rows === []) {
            return [];
        }

        $userMap = User::whereIn('id', array_column($rows, 'user_id'))->column('nickname', 'id');
        $taskMap = $this->taskMap(array_column($rows, 'task_no'));
        $out = [];
        foreach ($rows as $row) {
            $out[] = $this->formatRow($row, $userMap, $taskMap);
        }
        return $out;
    }

    public function count(): int
    {
        return $this->buildQuery()->count();
    }

    private function buildQuery()
    {
        $query = HotspotCreation::field([
            'id', 'record_no', 'user_id', 'topic', 'platform', 'persona_name',
            'goal', 'direction', 'material_mode', 'duration_sec', 'title',
            'status', 'task_no', 'create_time',
        ]);

        $platform = trim((string)($this->params['platform'] ?? ''));
        if ($platform !== '') {
            $query->where('platform', $platform);
        }

        $status = trim((string)($this->params['status'] ?? ''));
        if ($status !== '') {
            $query->where('status', $status);
        }

        $keyword = trim((string)($this->params['keyword'] ?? ''));
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->whereLike('topic', '%' . $keyword . '%')
                    ->whereOr('title', 'like', '%' . $keyword . '%');
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

    private function taskMap(array $taskNos): array
    {
        $nos = [];
        foreach ($taskNos as $no) {
            $no = trim((string)$no);
            if ($no !== '') {
                $nos[] = $no;
            }
        }
        if ($nos === []) {
            return [];
        }
        $tasks = HotspotTask::whereIn('task_no', $nos)->select();
        $map = [];
        foreach ($tasks as $task) {
            $map[(string)$task->task_no] = $task;
        }
        return $map;
    }

    private function formatRow(array $row, array $userMap, array $taskMap): array
    {
        $taskNo = (string)($row['task_no'] ?? '');
        $task = $taskMap[$taskNo] ?? null;
        $status = (string)($row['status'] ?? 'script');
        $taskStatus = RecordService::resolveTaskStatus(
            $status,
            $taskNo,
            $task ? (string)$task->status : ''
        );
        $taskError = $task ? (string)$task->error : '';
        $videoUrl = $task ? TaskService::formatVideoUrl((string)$task->video_url) : '';
        $sjTaskId = $task ? TaskService::resolveBoundVideoTaskId($task) : 0;
        $nickname = trim((string)($userMap[(int)($row['user_id'] ?? 0)] ?? ''));

        return [
            'id' => (int)$row['id'],
            'record_no' => (string)($row['record_no'] ?? ''),
            'user' => $nickname !== '' ? $nickname : '体验用户',
            'type' => '视频',
            'platform' => (string)($row['platform'] ?? ''),
            'title' => (string)($row['title'] ?? ''),
            'topic' => (string)($row['topic'] ?? ''),
            'persona_name' => (string)($row['persona_name'] ?? ''),
            'duration_sec' => (int)($row['duration_sec'] ?? 0),
            'status' => $status,
            'task_no' => $taskNo,
            'task_status' => $taskStatus,
            'task_error' => $taskError,
            'video_url' => $videoUrl,
            'shanjian_video_task_id' => $sjTaskId,
            'remark' => RecordService::buildRemark(
                $status,
                $taskStatus,
                $taskError,
                (string)($row['material_mode'] ?? '')
            ),
            'create_time' => (string)($row['create_time'] ?? ''),
        ];
    }
}

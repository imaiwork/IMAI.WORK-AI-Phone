<?php

declare(strict_types=1);

namespace app\adminapi\lists\playVideo;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\enum\playVideo\PlayVideoEnum;
use app\common\lists\ListsSearchInterface;
use app\common\model\playVideo\PlayVideoTask;
use app\common\model\user\User;
use app\common\service\FileService;

/**
 * 爆款玩法任务列表（后台）
 */
class PlayVideoTaskLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [];
    }

    public function where(): array
    {
        $where = [];
        if (isset($this->params['status']) && $this->params['status'] !== '') {
            $where[] = ['status', '=', (int)$this->params['status']];
        }
        if (!empty($this->params['play'])) {
            $where[] = ['play', '=', (string)$this->params['play']];
        }
        if (!empty($this->params['sn'])) {
            $where[] = ['sn', 'like', '%' . trim((string)$this->params['sn']) . '%'];
        }
        if (!empty($this->params['user_id'])) {
            $where[] = ['user_id', '=', (int)$this->params['user_id']];
        }
        if (!empty($this->params['start_time']) && !empty($this->params['end_time'])) {
            $where[] = ['create_time', 'between', [
                strtotime((string)$this->params['start_time']),
                strtotime((string)$this->params['end_time']),
            ]];
        }
        return $where;
    }

    public function lists(): array
    {
        $rows = PlayVideoTask::where($this->where())
            ->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
        $userIds = array_values(array_unique(array_column($rows, 'user_id')));
        $users = $userIds !== []
            ? User::whereIn('id', $userIds)->column('nickname', 'id')
            : [];
        foreach ($rows as &$row) {
            $row['status_desc'] = PlayVideoEnum::statusDesc((int)$row['status']);
            $row['video_url'] = $row['video_url'] !== '' ? FileService::getFileUrl((string)$row['video_url']) : '';
            $row['nickname'] = $users[$row['user_id']] ?? '';
            $snapshot = json_decode((string)($row['template_snapshot'] ?? ''), true) ?: [];
            $row['template_name'] = (string)($snapshot['name'] ?? '');
            unset($row['template_snapshot'], $row['input']);
            $duration = (int)($row['finish_time'] ?? 0) > 0
                ? (int)$row['finish_time'] - (int)$row['create_time'] : 0;
            $row['duration_seconds'] = max(0, $duration);
        }
        return $rows;
    }

    public function count(): int
    {
        return PlayVideoTask::where($this->where())->count();
    }
}

<?php

namespace app\api\lists\phoneAgent;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\phoneAgent\PhoneAgentConversation;
use app\common\service\phoneAgent\PhoneAgentConversationService;
use think\db\Query;

class PhoneAgentHistoryLists extends BaseApiDataLists implements ListsSearchInterface
{
    private ?int $totalCount = null;

    public function setSearch(): array
    {
        return [];
    }

    public function lists(): array
    {
        $rows = $this->baseQuery()
            ->field([
                'c.id' => 'id',
                'c.conversation_id' => 'conversation_id',
                'c.device_code' => 'device_code',
                'c.title' => 'title',
                'c.last_message' => 'last_message',
                'c.last_task_id' => 'last_task_id',
                'c.task_count' => 'task_count',
                'c.last_task_status' => 'last_task_status',
                'c.create_time' => 'create_time',
                'c.update_time' => 'update_time',
                'd.device_name' => 'device_name',
                'd.device_model' => 'device_model',
            ])
            ->order('c.update_time', 'desc')
            ->order('c.id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        $conversationIds = array_column($rows, 'conversation_id');
        $devicesMap = PhoneAgentConversationService::devicesByConversationIds($conversationIds, (int)$this->userId);

        return array_map(
            fn(array $row) => $this->formatItem($row, $devicesMap[(string)($row['conversation_id'] ?? '')] ?? []),
            $rows
        );
    }

    public function count(): int
    {
        if ($this->totalCount !== null) {
            return $this->totalCount;
        }

        $this->totalCount = $this->baseQuery()->count();
        return $this->totalCount;
    }

    private function baseQuery(): Query
    {
        $query = PhoneAgentConversation::alias('c')
            ->leftJoin('sv_device d', 'd.user_id = c.user_id and d.device_code = c.device_code')
            ->where('c.user_id', $this->userId);

        $keyword = trim((string)($this->params['keyword'] ?? ''));
        if ($keyword !== '') {
            $like = '%' . $keyword . '%';
            $query->where(function ($query) use ($like) {
                $query->where('c.title', 'like', $like)
                    ->whereOr('c.last_message', 'like', $like)
                    ->whereOr('c.device_code', 'like', $like)
                    ->whereOr('d.device_name', 'like', $like)
                    ->whereOr('d.device_model', 'like', $like)
                    ->whereExists(function ($query) use ($like) {
                        $query->name('phone_agent_task')
                            ->alias('t')
                            ->leftJoin('sv_device td', 'td.user_id = t.user_id and td.device_code = t.device_code')
                            ->whereColumn('t.conversation_id', 'c.conversation_id')
                            ->whereColumn('t.user_id', 'c.user_id')
                            ->where(function ($query) use ($like) {
                                $query->where('t.device_code', 'like', $like)
                                    ->whereOr('td.device_name', 'like', $like)
                                    ->whereOr('td.device_model', 'like', $like);
                            });
                    }, 'OR');
            });
        }

        $status = trim((string)($this->params['status'] ?? ''));
        if ($status !== '') {
            $query->where('c.last_task_status', '=', $status);
        }

        $deviceCode = trim((string)($this->params['device_code'] ?? ''));
        if ($deviceCode !== '') {
            $query->whereExists(function ($query) use ($deviceCode) {
                $query->name('phone_agent_task')
                    ->alias('t')
                    ->whereColumn('t.conversation_id', 'c.conversation_id')
                    ->whereColumn('t.user_id', 'c.user_id')
                    ->where('t.device_code', '=', $deviceCode);
            });
        }

        if (is_numeric($this->startTime ?? null) && is_numeric($this->endTime ?? null)) {
            $query->where('c.update_time', 'between', [$this->startTime, $this->endTime]);
        }

        return $query;
    }

    private function formatItem(array $row, array $devices = []): array
    {
        $deviceName = trim((string)($row['device_name'] ?? ''));
        if ($deviceName === '') {
            $deviceName = trim((string)($row['device_model'] ?? ''));
        }
        if ($deviceName === '') {
            $deviceName = (string)($row['device_code'] ?? '');
        }

        $status = (string)($row['last_task_status'] ?? '');
        return [
            'id' => (int)($row['id'] ?? 0),
            'conversation_id' => (string)($row['conversation_id'] ?? ''),
            'title' => (string)($row['title'] ?? ''),
            'last_message' => (string)($row['last_message'] ?? ''),
            'last_task_id' => (string)($row['last_task_id'] ?? ''),
            'task_count' => (int)($row['task_count'] ?? 0),
            'last_task_status' => $status,
            'status_text' => PhoneAgentConversationService::statusText($status),
            'device_code' => (string)($row['device_code'] ?? ''),
            'device_name' => $deviceName,
            'device_model' => (string)($row['device_model'] ?? ''),
            'devices' => $devices,
            'device_count' => count($devices),
            'create_time' => $this->formatTime($row['create_time'] ?? 0),
            'update_time' => $this->formatTime($row['update_time'] ?? 0),
        ];
    }

    private function formatTime($value): string
    {
        if ($value === null || $value === '' || $value === 0 || $value === '0') {
            return '';
        }
        if (is_numeric($value)) {
            return (int)$value > 0 ? date('Y-m-d H:i:s', (int)$value) : '';
        }
        $timestamp = strtotime((string)$value);
        return $timestamp === false ? (string)$value : date('Y-m-d H:i:s', $timestamp);
    }
}

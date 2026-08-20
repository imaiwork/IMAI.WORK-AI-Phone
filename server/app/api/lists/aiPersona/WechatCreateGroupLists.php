<?php

namespace app\api\lists\aiPersona;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\wechat\AiWechatCreateGroupLog;
use think\db\Query;

class WechatCreateGroupLists extends BaseApiDataLists implements ListsSearchInterface
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
                'l.id' => 'id',
                'd.persona_id' => 'persona_id',
                'l.device_code' => 'device_code',
                'd.device_name' => 'device_name',
                'd.device_model' => 'device_model',
                'l.friend_id' => 'friend_id',
                'l.wechat_id' => 'wechat_id',
                'l.sales_wechat' => 'sales_wechat',
                'l.group_name' => 'group_name',
                'l.message_content' => 'message_content',
                'l.scene' => 'scene',
                'l.status' => 'status',
                'l.result' => 'result',
                'l.task_id' => 'task_id',
                'l.create_time' => 'create_time',
                'l.update_time' => 'update_time',
            ])
            ->order('l.create_time', 'desc')
            ->order('l.id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        return array_map(fn(array $row) => $this->formatItem($row), $rows);
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
        $query = AiWechatCreateGroupLog::alias('l')
            ->join('sv_device d', 'd.user_id = l.user_id and d.device_code = l.device_code')
            ->where('l.user_id', '=', $this->userId)
            ->where('d.persona_id', '=', $this->personaId())
            ->whereNull('l.delete_time');

        if (!empty($this->params['device_code'])) {
            $query->where('l.device_code', '=', trim((string)$this->params['device_code']));
        }

        if (isset($this->params['status']) && $this->params['status'] !== '') {
            $query->where('l.status', '=', (int)$this->params['status']);
        }

        $this->applyKeyword($query);
        $this->applyDateFilter($query);

        return $query;
    }

    private function applyKeyword(Query $query): void
    {
        $keyword = trim((string)($this->params['keyword'] ?? ''));
        if ($keyword === '') {
            return;
        }

        $like = '%' . $keyword . '%';
        $query->where(function ($query) use ($like) {
            $query->where('l.group_name', 'like', $like)
                ->whereOr('l.message_content', 'like', $like)
                ->whereOr('l.friend_id', 'like', $like)
                ->whereOr('l.wechat_id', 'like', $like)
                ->whereOr('l.sales_wechat', 'like', $like)
                ->whereOr('l.device_code', 'like', $like)
                ->whereOr('d.device_name', 'like', $like)
                ->whereOr('d.device_model', 'like', $like)
                ->whereOr('l.result', 'like', $like);
        });
    }

    private function applyDateFilter(Query $query): void
    {
        [$start, $end] = $this->dateRange();
        $query->where('l.create_time', 'between', [$start, $end]);
    }

    private function formatItem(array $row): array
    {
        $scene = (int)($row['scene'] ?? 0);
        $status = (int)($row['status'] ?? 0);
        $deviceName = trim((string)($row['device_name'] ?? ''));

        if ($deviceName === '') {
            $deviceName = (string)($row['device_model'] ?? '');
        }

        return [
            'id' => (int)($row['id'] ?? 0),
            'persona_id' => (int)($row['persona_id'] ?? 0),
            'device_code' => (string)($row['device_code'] ?? ''),
            'device_name' => $deviceName,
            'device_model' => (string)($row['device_model'] ?? ''),
            'friend_id' => (string)($row['friend_id'] ?? ''),
            'wechat_id' => (string)($row['wechat_id'] ?? ''),
            'sales_wechat' => (string)($row['sales_wechat'] ?? ''),
            'group_name' => (string)($row['group_name'] ?? ''),
            'message_content' => (string)($row['message_content'] ?? ''),
            'scene' => $scene,
            'scene_text' => $this->sceneText($scene),
            'status' => $status,
            'status_text' => $this->statusText($status),
            'result' => (string)($row['result'] ?? ''),
            'task_id' => (string)($row['task_id'] ?? ''),
            'create_time' => $this->formatTime($row['create_time'] ?? null),
            'update_time' => $this->formatTime($row['update_time'] ?? null),
            'screenshot_url' => '',
        ];
    }

    private function personaId(): int
    {
        return (int)($this->params['persona_id'] ?? 0);
    }

    private function sceneText(int $scene): string
    {
        return [
            0 => '手动',
            1 => '自动',
        ][$scene] ?? '未知';
    }

    private function statusText(int $status): string
    {
        return [
            -1 => '未知',
            0 => '失败',
            1 => '成功',
        ][$status] ?? '未知';
    }

    private function dateRange(): array
    {
        $date = $this->taskDate();
        return [
            strtotime($date . ' 00:00:00') ?: 0,
            strtotime($date . ' 23:59:59') ?: 0,
        ];
    }

    private function taskDate(): string
    {
        $date = trim((string)($this->params['date'] ?? ''));
        if ($date !== '') {
            $timestamp = strtotime($date);
            if ($timestamp !== false) {
                return date('Y-m-d', $timestamp);
            }
        }

        return date('Y-m-d');
    }

    private function formatTime($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_numeric($value)) {
            return date('Y-m-d H:i:s', (int)$value);
        }

        $timestamp = strtotime((string)$value);
        return $timestamp === false ? (string)$value : date('Y-m-d H:i:s', $timestamp);
    }
}

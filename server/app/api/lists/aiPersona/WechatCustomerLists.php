<?php

namespace app\api\lists\aiPersona;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsExtendInterface;
use app\common\lists\ListsSearchInterface;
use app\common\model\sv\SvAddWechatRecord;
use app\common\service\FileService;
use think\db\Query;

class WechatCustomerLists extends BaseApiDataLists implements ListsSearchInterface, ListsExtendInterface
{
    private const VALID_STATUSES = [1, 2, 4];

    private ?int $totalCount = null;

    public function setSearch(): array
    {
        return [];
    }

    public function lists(): array
    {
        $rows = $this->baseQuery()
            ->field([
                'r.id',
                'r.device_code',
                'r.account',
                'r.user_account',
                'r.original_message',
                'r.reg_wechat',
                'r.wechat_no',
                'r.wechat_name',
                'r.remark',
                'r.status',
                'r.result',
                'r.image',
                'r.create_time',
                'r.update_time',
            ])
            ->order('r.update_time', 'desc')
            ->order('r.id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
            //print_r(\think\facade\Db::getLastSql());die;
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

    public function extend(): array
    {
        $newFriendCount = $this->count();

        return [
            'title' => '帮我管理微信客户',
            'subtitle' => '自动加好友',
            'summary' => [
                'new_friend_count' => $newFriendCount,
            ],
            'tabs' => [
                [
                    'key' => 'new_friend',
                    'name' => '新好友',
                    'count' => $newFriendCount,
                ],
            ],
        ];
    }

    private function baseQuery(): Query
    {
        $query = SvAddWechatRecord::alias('r')
            ->join('sv_device d', 'd.device_code = r.device_code and d.user_id = r.user_id')
            ->where('r.user_id', '=', $this->userId)
            ->where('d.persona_id', '=', $this->personaId())
            ->where('r.status', 'in', self::VALID_STATUSES)
            ->whereNull('r.delete_time');
        [$start, $end] = $this->dateRange();
        $query->whereBetween('r.update_time', [$start, $end]);

        if (!empty($this->params['device_code'])) {
            $query->where('r.device_code', '=', trim((string)$this->params['device_code']));
        }

        if (isset($this->params['status']) && $this->params['status'] !== '') {
            $status = (int)$this->params['status'];
            if (in_array($status, self::VALID_STATUSES, true)) {
                $query->where('r.status', '=', $status);
            } else {
                $query->where('r.id', '=', 0);
            }
        }

        $keyword = trim((string)($this->params['keyword'] ?? ''));
        if ($keyword !== '') {
            $like = '%' . $keyword . '%';
            $query->where(function ($query) use ($like) {
                $query->where('r.user_account', 'like', $like)
                    ->whereOr('r.original_message', 'like', $like)
                    ->whereOr('r.reg_wechat', 'like', $like)
                    ->whereOr('r.wechat_no', 'like', $like)
                    ->whereOr('r.wechat_name', 'like', $like)
                    ->whereOr('r.remark', 'like', $like)
                    ->whereOr('r.device_code', 'like', $like);
            });
        }

        return $query;
    }

    private function formatItem(array $row): array
    {
        $status = (int)($row['status'] ?? 0);

        return [
            'id' => (int)($row['id'] ?? 0),
            'device_code' => (string)($row['device_code'] ?? ''),
            'customer_name' => $this->firstText([
                $row['user_account'] ?? '',
                $row['reg_wechat'] ?? '',
                $row['account'] ?? '',
            ]),
            'request_remark' => (string)($row['original_message'] ?? ''),
            'request_account' => $this->firstText([
                $row['wechat_name'] ?? '',
                $row['wechat_no'] ?? '',
                $row['account'] ?? '',
            ]),
            'reg_wechat' => (string)($row['reg_wechat'] ?? ''),
            'wechat_no' => (string)($row['wechat_no'] ?? ''),
            'wechat_name' => (string)($row['wechat_name'] ?? ''),
            'add_remark' => (string)($row['remark'] ?? ''),
            'status' => $status,
            'status_text' => $this->statusText($status),
            'image' => $this->formatFileUrl((string)($row['image'] ?? '')),
            'create_time' => $this->formatTime($row['update_time'] ?? null),
        ];
    }

    private function statusText(int $status): string
    {
        return [
            1 => '新增好友',
            2 => '加好友中',
            4 => '待加好友',
        ][$status] ?? '';
    }

    private function personaId(): int
    {
        return (int)($this->params['persona_id'] ?? 0);
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

    private function firstText(array $values): string
    {
        foreach ($values as $value) {
            $text = trim((string)$value);
            if ($text !== '') {
                return $text;
            }
        }

        return '';
    }

    private function formatTime($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $timestamp = is_numeric($value) ? (int)$value : strtotime((string)$value);
        return $timestamp ? date('Y-m-d H:i', $timestamp) : '';
    }

    private function formatFileUrl(string $url): string
    {
        return $url === '' ? '' : FileService::getFileUrl($url);
    }
}

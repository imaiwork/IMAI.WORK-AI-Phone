<?php

namespace app\api\lists\aiPersona;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsExtendInterface;
use app\common\lists\ListsSearchInterface;
use app\common\model\sv\SvDeviceCircleLikeReplyRecord;
use app\common\service\FileService;
use think\db\Query;

class WechatCircleInteractionLists extends BaseApiDataLists implements ListsSearchInterface, ListsExtendInterface
{
    private const AUTO_TYPE = 1;

    private ?int $totalCount = null;
    private ?array $extendData = null;

    public function setSearch(): array
    {
        return [];
    }

    public function lists(): array
    {
        $rows = $this->baseQuery()
            ->field([
                'r.id' => 'id',
                'd.persona_id' => 'persona_id',
                'r.device_code' => 'device_code',
                'd.device_name' => 'device_name',
                'd.device_model' => 'device_model',
                'r.task_id' => 'task_id',
                'r.like_reply_account' => 'like_reply_account_id',
                'a.circle_like_reply_id' => 'circle_like_reply_id',
                'r.account' => 'account',
                'a.account' => 'execute_account',
                'a.nickname' => 'execute_name',
                'a.avatar' => 'execute_avatar',
                'a.task_name' => 'account_task_name',
                't.task_name' => 'task_name',
                'r.nickname' => 'nickname',
                'r.content' => 'content',
                'r.comment' => 'comment',
                'r.type' => 'type',
                'r.image' => 'image',
                'r.create_time' => 'create_time',
            ])
            ->order('r.create_time', 'desc')
            ->order('r.id', 'desc')
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

    public function extend(): array
    {
        if ($this->extendData !== null) {
            return $this->extendData;
        }

        $summary = $this->summary();
        $this->extendData = [
            'title' => '帮我发朋友圈',
            'summary' => $summary,
            'cards' => [
                [
                    'key' => 'today_interaction',
                    'name' => '今日互动人数',
                    'count' => $summary['today_interaction_count'],
                ],
                [
                    'key' => 'auto_like',
                    'name' => '自动点赞',
                    'count' => $summary['auto_like_count'],
                ],
                [
                    'key' => 'auto_comment',
                    'name' => '自动评论',
                    'count' => $summary['auto_comment_count'],
                ],
            ],
        ];

        return $this->extendData;
    }

    private function baseQuery(bool $includeTypeFilter = true): Query
    {
        $query = SvDeviceCircleLikeReplyRecord::alias('r')
            ->join('sv_device d', 'd.user_id = r.user_id and d.device_code = r.device_code')
            ->join('sv_device_circle_like_reply_account a', 'a.id = r.like_reply_account and a.user_id = r.user_id')
            ->join('sv_device_circle_like_reply t', 't.id = a.circle_like_reply_id and t.user_id = r.user_id')
            ->where('r.user_id', '=', $this->userId)
            ->where('d.persona_id', '=', $this->personaId())
            ->where('r.auto_type', '=', self::AUTO_TYPE)
            ->whereNull('r.delete_time');

        if (!empty($this->params['device_code'])) {
            $query->where('r.device_code', '=', trim((string)$this->params['device_code']));
        }

        if ($includeTypeFilter && isset($this->params['type']) && $this->params['type'] !== '') {
            $query->where('r.type', '=', (int)$this->params['type']);
        }

        $this->applyKeyword($query);
        $this->applyDateFilter($query);

        return $query;
    }

    private function summary(): array
    {
        $nicknames = $this->baseQuery(false)
            ->whereNotNull('r.nickname')
            ->where('r.nickname', '<>', '')
            ->distinct(true)
            ->column('r.nickname');

        return [
            'today_interaction_count' => count($nicknames),
            'auto_like_count' => $this->baseQuery(false)->where('r.type', 'in', [1, 3])->count(),
            'auto_comment_count' => $this->baseQuery(false)->where('r.type', 'in', [2, 3])->count(),
        ];
    }

    private function applyKeyword(Query $query): void
    {
        $keyword = trim((string)($this->params['keyword'] ?? ''));
        if ($keyword === '') {
            return;
        }

        $like = '%' . $keyword . '%';
        $query->where(function ($query) use ($like) {
            $query->where('r.nickname', 'like', $like)
                ->whereOr('r.content', 'like', $like)
                ->whereOr('r.comment', 'like', $like)
                ->whereOr('r.account', 'like', $like)
                ->whereOr('r.device_code', 'like', $like)
                ->whereOr('d.device_name', 'like', $like)
                ->whereOr('d.device_model', 'like', $like)
                ->whereOr('a.account', 'like', $like)
                ->whereOr('a.nickname', 'like', $like)
                ->whereOr('a.task_name', 'like', $like)
                ->whereOr('t.task_name', 'like', $like);
        });
    }

    private function applyDateFilter(Query $query): void
    {
        $start = $this->parseTimeParam('start_time');
        $end = $this->parseTimeParam('end_time', true);

        if ($start <= 0 && $end <= 0) {
            $date = $this->taskDate();
            $start = strtotime($date . ' 00:00:00') ?: 0;
            $end = strtotime($date . ' 23:59:59') ?: 0;
        }

        if ($start > 0 && $end > 0) {
            $query->whereBetween('r.create_time', [$start, $end]);
            return;
        }

        if ($start > 0) {
            $query->where('r.create_time', '>=', $start);
            return;
        }

        if ($end > 0) {
            $query->where('r.create_time', '<=', $end);
        }
    }

    private function formatItem(array $row): array
    {
        $type = (int)($row['type'] ?? 0);
        $comments = $this->parseListValue($row['comment'] ?? '');
        $images = $this->parseImageValue($row['image'] ?? '');
        $createTime = $this->formatTime($row['create_time'] ?? '');
        $deviceName = trim((string)($row['device_name'] ?? ''));

        if ($deviceName === '') {
            $deviceName = (string)($row['device_model'] ?? '');
        }

        return [
            'id' => (int)($row['id'] ?? 0),
            'record_id' => (int)($row['id'] ?? 0),
            'persona_id' => (int)($row['persona_id'] ?? 0),
            'device_code' => (string)($row['device_code'] ?? ''),
            'device_name' => $deviceName,
            'device_model' => (string)($row['device_model'] ?? ''),
            'task_id' => (string)($row['task_id'] ?? ''),
            'circle_like_reply_id' => (int)($row['circle_like_reply_id'] ?? 0),
            'like_reply_account_id' => (int)($row['like_reply_account_id'] ?? 0),
            'account' => (string)($row['account'] ?? ''),
            'execute_account' => (string)(($row['execute_account'] ?? '') ?: ($row['account'] ?? '')),
            'execute_name' => (string)($row['execute_name'] ?? ''),
            'execute_avatar' => $this->formatFileUrl((string)($row['execute_avatar'] ?? '')),
            'nickname' => (string)($row['nickname'] ?? ''),
            'content' => (string)($row['content'] ?? ''),
            'comment' => (string)($row['comment'] ?? ''),
            'comments' => $comments,
            'type' => $type,
            'type_text' => $this->typeText($type),
            'type_desc' => $this->typeText($type),
            'is_liked' => in_array($type, [1, 3], true) ? 1 : 0,
            'is_commented' => in_array($type, [2, 3], true) ? 1 : 0,
            'action_text' => $this->actionText($type, (string)($row['nickname'] ?? ''), (string)($row['content'] ?? ''), $comments),
            'image' => $images[0] ?? '',
            'images' => $images,
            'screenshot_url' => $images[0] ?? '',
            'create_time' => $createTime,
            'time_text' => $this->formatClock($createTime),
        ];
    }

    private function actionText(int $type, string $nickname, string $content, array $comments): string
    {
        $target = $nickname !== '' ? '@' . ltrim($nickname, '@') : '';

        if ($type === 1) {
            $subject = $content !== '' ? $content : '朋友圈';
            return trim('给 ' . $target . ' 的' . $subject . '点赞');
        }

        if ($type === 2) {
            $comment = (string)($comments[0] ?? '');
            return trim('评论 ' . $target . ($comment !== '' ? '：“' . $comment . '”' : ''));
        }

        if ($type === 3) {
            $comment = (string)($comments[0] ?? '');
            return trim('给 ' . $target . ' 点赞并评论' . ($comment !== '' ? '：“' . $comment . '”' : ''));
        }

        return trim($this->typeText($type) . ' ' . $target);
    }

    private function parseImageValue($value): array
    {
        $items = $this->parseListValue($value);
        $images = [];

        foreach ($items as $item) {
            $url = $this->formatFileUrl($item);
            if ($url !== '') {
                $images[$url] = $url;
            }
        }

        return array_values($images);
    }

    private function parseListValue($value): array
    {
        if (is_array($value)) {
            $values = $value;
        } else {
            $value = trim((string)$value);
            if ($value === '') {
                return [];
            }

            $decoded = json_decode($value, true);
            $values = is_array($decoded) ? $decoded : (preg_split('/[,，]+/u', $value) ?: []);
        }

        $items = [];
        foreach ($values as $item) {
            $item = trim((string)$item);
            if ($item !== '') {
                $items[$item] = $item;
            }
        }

        return array_values($items);
    }

    private function typeText(int $type): string
    {
        return [
            1 => '点赞',
            2 => '评论',
            3 => '点赞+评论',
        ][$type] ?? '未知';
    }

    private function personaId(): int
    {
        return (int)($this->params['persona_id'] ?? 0);
    }

    private function taskDate(): string
    {
        return !empty($this->params['date']) ? (string)$this->params['date'] : date('Y-m-d');
    }

    private function parseTimeParam(string $name, bool $endOfDay = false): int
    {
        $value = trim((string)($this->params[$name] ?? ''));
        if ($value === '') {
            return 0;
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return 0;
        }

        if ($endOfDay && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            $timestamp += 86399;
        }

        return $timestamp;
    }

    private function formatFileUrl(string $url): string
    {
        return $url === '' ? '' : FileService::getFileUrl($url);
    }

    private function formatTime($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_numeric($value)) {
            return (int)$value > 0 ? date('Y-m-d H:i:s', (int)$value) : '';
        }

        $timestamp = strtotime((string)$value);
        return $timestamp === false ? (string)$value : date('Y-m-d H:i:s', $timestamp);
    }

    private function formatClock(string $value): string
    {
        $timestamp = strtotime($value);
        return $timestamp === false ? '' : date('H:i', $timestamp);
    }
}

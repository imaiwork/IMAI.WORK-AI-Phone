<?php

namespace app\api\lists\aiPersona;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsExtendInterface;
use app\common\lists\ListsSearchInterface;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvPrivateMessage;
use app\common\service\FileService;
use think\db\Query;

class WechatMessageReplyLists extends BaseApiDataLists implements ListsSearchInterface, ListsExtendInterface
{
    private const WECHAT_TYPE = 1;
    private const MESSAGE_TASK_TYPE_PRIVATE = 2;
    private const CUSTOMER_TYPE_PERSONAL = 0;

    private ?array $deviceCodes = null;
    private ?int $replyCount = null;

    public function setSearch(): array
    {
        return [];
    }

    public function lists(): array
    {
        $deviceCodes = $this->getDeviceCodes();
        if (empty($deviceCodes)) {
            return [];
        }

        $rows = $this->baseQuery($deviceCodes)
            ->field([
                'id',
                'device_code',
                'account',
                'friend_id',
                'avatar',
                'author_name',
                'message_content',
                'message_timer',
                'is_reply',
                'reply_content',
                'reply_time',
                'create_time',
            ])
            ->order('reply_time', 'desc')
            ->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        return array_map(function (array $row) {
            $messageTime = (string)(($row['message_timer'] ?? '') ?: ($row['create_time'] ?? ''));

            return [
                'id' => (int)$row['id'],
                'device_code' => (string)($row['device_code'] ?? ''),
                'account' => (string)($row['account'] ?? ''),
                'friend_id' => (string)($row['friend_id'] ?? ''),
                'avatar' => $this->formatFileUrl((string)($row['avatar'] ?? '')),
                'author_name' => (string)($row['author_name'] ?? ''),
                'message_content' => $this->parseMessageContent((string)($row['message_content'] ?? '')),
                'reply_content' => $this->parseMessageContent((string)($row['reply_content'] ?? '')),
                'is_reply' => $this->isReply($row) ? 1 : 0,
                'message_time' => $this->formatTime($messageTime),
                'reply_time' => $this->formatTime((string)($row['reply_time'] ?? '')),
                'create_time' => $this->formatTime((string)($row['create_time'] ?? '')),
            ];
        }, $rows);
    }

    public function count(): int
    {
        return $this->autoReplyCount();
    }

    public function extend(): array
    {
        return [
            'summary' => [
                'auto_reply_count' => $this->autoReplyCount(),
            ],
        ];
    }

    private function baseQuery(array $deviceCodes): Query
    {
        $query = SvPrivateMessage::where('user_id', '=', $this->userId)
            ->where('device_code', 'in', $deviceCodes)
            ->where('type', '=', self::WECHAT_TYPE)
            ->where('message_task_type', '=', self::MESSAGE_TASK_TYPE_PRIVATE)
            ->where('customer_type', '=', self::CUSTOMER_TYPE_PERSONAL)
            ->whereNull('delete_time');

        $query->where(function ($query) {
            $query->where('is_reply', '=', 1)
                ->whereOr('reply_content', '<>', '')
                ->whereOr(function ($query) {
                    $query->whereNotNull('reply_time')
                        ->where('reply_time', '<>', '');
                });
        });

        $this->applyKeyword($query);
        $this->applyDateFilter($query);

        return $query;
    }

    private function autoReplyCount(): int
    {
        if ($this->replyCount !== null) {
            return $this->replyCount;
        }

        $deviceCodes = $this->getDeviceCodes();
        if (empty($deviceCodes)) {
            $this->replyCount = 0;
            return $this->replyCount;
        }

        $this->replyCount = $this->baseQuery($deviceCodes)->count();
        return $this->replyCount;
    }

    private function getDeviceCodes(): array
    {
        if ($this->deviceCodes !== null) {
            return $this->deviceCodes;
        }

        $personaId = (int)($this->params['persona_id'] ?? 0);
        if ($personaId <= 0) {
            $this->deviceCodes = [];
            return $this->deviceCodes;
        }

        $this->deviceCodes = array_values(array_filter(SvDevice::where('user_id', '=', $this->userId)
            ->where('persona_id', '=', $personaId)
            ->where('device_code', '<>', '')
            ->column('device_code')));

        return $this->deviceCodes;
    }

    private function applyKeyword(Query $query): void
    {
        $keyword = trim((string)($this->params['keyword'] ?? ''));
        if ($keyword === '') {
            return;
        }

        $like = '%' . $keyword . '%';
        $query->where(function ($query) use ($like) {
            $query->where('author_name', 'like', $like)
                ->whereOr('message_content', 'like', $like)
                ->whereOr('reply_content', 'like', $like)
                ->whereOr('friend_id', 'like', $like)
                ->whereOr('account', 'like', $like);
        });
    }

    private function applyDateFilter(Query $query): void
    {
        [$start, $end] = $this->dateRange();
        $query->whereBetween('create_time', [$start, $end]);
    }

    private function isReply(array $row): bool
    {
        return (int)($row['is_reply'] ?? 0) === 1
            || trim((string)($row['reply_content'] ?? '')) !== ''
            || trim((string)($row['reply_time'] ?? '')) !== '';
    }

    private function parseMessageContent(string $content): string
    {
        $parts = preg_split('/\s*&&\s*/', $content, 2);
        return trim((string)($parts[0] ?? $content));
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

    private function formatFileUrl(string $url): string
    {
        return $url === '' ? '' : FileService::getFileUrl($url);
    }

    private function formatTime(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (is_numeric($value)) {
            return date('Y-m-d H:i:s', (int)$value);
        }

        $timestamp = strtotime($value);
        return $timestamp === false ? $value : date('Y-m-d H:i:s', $timestamp);
    }
}

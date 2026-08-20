<?php

namespace app\api\lists\aiPersona;

use app\api\lists\BaseApiDataLists;
use app\common\enum\DeviceEnum;
use app\common\lists\ListsExtendInterface;
use app\common\lists\ListsSearchInterface;
use app\common\model\sv\SvAddWechatRecord;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvDeviceTakeOverRecord;
use app\common\model\sv\SvPrivateMessage;
use app\common\service\FileService;
use think\db\Query;

class MessageTaskLists extends BaseApiDataLists implements ListsSearchInterface, ListsExtendInterface
{
    private ?array $deviceCodes = null;
    private ?array $privateRows = null;
    private ?array $contactRows = null;
    private ?array $allPrivateRows = null;
    private ?array $allContactRows = null;
    private ?array $sphCommentRows = null;
    private ?array $allSphCommentRows = null;
    private ?array $items = null;
    private ?array $allItems = null;
    private ?array $statistics = null;

    public function setSearch(): array
    {
        return [];
    }

    public function lists(): array
    {
        return array_slice($this->getItems(), $this->limitOffset, $this->limitLength);
    }

    public function count(): int
    {
        return count($this->getItems());
    }

    public function extend(): array
    {
        return $this->statistics();
    }

    public function statistics(): array
    {
        if ($this->statistics !== null) {
            return $this->statistics;
        }

        $repliedCount = 0;
        foreach ($this->getPrivateRows() as $row) {
            if ($this->isReply($row)) {
                $repliedCount++;
            }
        }
        $repliedCount += count($this->getSphCommentRows());

        $this->statistics = [
            'replied_count' => $repliedCount,
            'contact_count' => $this->contactCount(),
            'tabs' => $this->buildTabs($this->getItems(true)),
        ];

        return $this->statistics;
    }

    private function getItems(bool $ignorePlatform = false): array
    {
        if ($ignorePlatform && $this->allItems !== null) {
            return $this->allItems;
        }
        if (!$ignorePlatform && $this->items !== null) {
            return $this->items;
        }

        $contacts = $this->getContactRows($ignorePlatform);
        $contactIndex = $this->buildContactIndex($contacts);
        $usedContactIds = [];
        $items = [];

        foreach ($this->getPrivateRows($ignorePlatform) as $row) {
            $contact = $this->findContactForMessage($row, $contactIndex, $usedContactIds);
            $items[] = $this->formatPrivateMessage($row, $contact);
        }

        foreach ($this->getSphCommentRows($ignorePlatform) as $row) {
            $items[] = $this->formatSphCommentRecord($row);
        }

        if ($this->messageTaskType() === null) {
            foreach ($contacts as $row) {
                $id = (int)($row['id'] ?? 0);
                if ($id > 0 && isset($usedContactIds[$id])) {
                    continue;
                }
                $items[] = $this->formatContactRecord($row);
            }
        }

        usort($items, static function (array $left, array $right) {
            return ($right['timestamp'] ?? 0) <=> ($left['timestamp'] ?? 0);
        });

        foreach ($items as &$item) {
            unset($item['timestamp']);
        }
        unset($item);

        if ($ignorePlatform) {
            $this->allItems = $items;
            return $this->allItems;
        }

        $this->items = $items;
        return $this->items;
    }

    private function contactCount(): int
    {
        $contacts = [];

        if ($this->messageTaskType() === null) {
            foreach ($this->getContactRows() as $row) {
                $contact = $this->normalizeContactValue((string)($row['reg_wechat'] ?? ''));
                if ($contact !== '') {
                    $contacts[$contact] = true;
                }
            }
            return count($contacts);
        }

        $contactIndex = $this->buildContactIndex($this->getContactRows());
        $usedContactIds = [];
        foreach ($this->getPrivateRows() as $row) {
            $contact = $this->findContactForMessage($row, $contactIndex, $usedContactIds);
            $contactValue = $this->normalizeContactValue((string)($contact['reg_wechat'] ?? ''));
            if ($contactValue !== '') {
                $contacts[$contactValue] = true;
            }
        }
        foreach ($this->getSphCommentRows() as $row) {
            $contactValue = $this->normalizeContactValue(
                $this->extractContactValue([(string)($row['content'] ?? '')])
            );
            if ($contactValue !== '') {
                $contacts[$contactValue] = true;
            }
        }

        return count($contacts);
    }

    private function getPrivateRows(bool $ignorePlatform = false): array
    {
        if ($ignorePlatform && $this->allPrivateRows !== null) {
            return $this->allPrivateRows;
        }
        if (!$ignorePlatform && $this->privateRows !== null) {
            return $this->privateRows;
        }

        $deviceCodes = $this->getDeviceCodes();
        if (empty($deviceCodes)) {
            return $ignorePlatform ? $this->allPrivateRows = [] : $this->privateRows = [];
        }

        $query = SvPrivateMessage::field([
                'id',
                'user_id',
                'device_code',
                'account',
                'type',
                'friend_id',
                'avatar',
                'author_name',
                'message_task_type',
                'message_content',
                'message_timer',
                'new_message_count',
                'is_reply',
                'reply_content',
                'reply_time',
                'create_time',
                'update_time',
            ])
            ->where('user_id', '=', $this->userId)
            ->where('device_code', 'in', $deviceCodes)
            ->where('type', 'in', $this->platformTypes())
            ->whereNull('delete_time');

        if ($this->messageTaskType() !== null) {
            $query->where('message_task_type', '=', $this->messageTaskType());
        } else {
            $query->where('message_task_type', 'in', [1, 2]);
        }

        if (!$ignorePlatform && $this->platformType() !== null) {
            $query->where('type', '=', $this->platformType());
        }

        $this->applyPrivateKeyword($query);
        $this->applyDateFilter($query);

        $rows = $query->order('create_time', 'desc')
            ->order('id', 'desc')
            ->select()
            ->toArray();

        if ($ignorePlatform) {
            $this->allPrivateRows = $rows;
            return $this->allPrivateRows;
        }

        $this->privateRows = $rows;
        return $this->privateRows;
    }

    private function getContactRows(bool $ignorePlatform = false): array
    {
        if ($ignorePlatform && $this->allContactRows !== null) {
            return $this->allContactRows;
        }
        if (!$ignorePlatform && $this->contactRows !== null) {
            return $this->contactRows;
        }

        $deviceCodes = $this->getDeviceCodes();
        if (empty($deviceCodes)) {
            return $ignorePlatform ? $this->allContactRows = [] : $this->contactRows = [];
        }

        $query = SvAddWechatRecord::field([
                'id',
                'user_id',
                'device_code',
                'channel',
                'account',
                'account_type',
                'user_account',
                'original_message',
                'reg_wechat',
                'wechat_no',
                'wechat_name',
                'action',
                'status',
                'result',
                'task_id',
                'create_time',
                'update_time',
            ])
            ->where('user_id', '=', $this->userId)
            ->where('device_code', 'in', $deviceCodes)
            ->where('reg_wechat', '<>', '')
            ->whereNull('delete_time');

        $query->where(function ($query) {
            $query->where('account_type', 'in', $this->platformTypes())
                ->whereOr('channel', 'in', $this->platformTypes());
        });

        if (!$ignorePlatform && $this->platformType() !== null) {
            $platform = $this->platformType();
            $query->where(function ($query) use ($platform) {
                $query->where('account_type', '=', $platform)
                    ->whereOr('channel', '=', $platform);
            });
        }

        $this->applyContactKeyword($query);
        $this->applyDateFilter($query);

        $rows = $query->order('create_time', 'desc')
            ->order('id', 'desc')
            ->select()
            ->toArray();

        if ($ignorePlatform) {
            $this->allContactRows = $rows;
            return $this->allContactRows;
        }

        $this->contactRows = $rows;
        return $this->contactRows;
    }

    private function getSphCommentRows(bool $ignorePlatform = false): array
    {
        if ($ignorePlatform && $this->allSphCommentRows !== null) {
            return $this->allSphCommentRows;
        }
        if (!$ignorePlatform && $this->sphCommentRows !== null) {
            return $this->sphCommentRows;
        }

        $deviceCodes = $this->getDeviceCodes();
        if (empty($deviceCodes) || !$this->shouldIncludeSphComments($ignorePlatform)) {
            return $ignorePlatform ? $this->allSphCommentRows = [] : $this->sphCommentRows = [];
        }

        $query = SvDeviceTakeOverRecord::alias('r')
            ->distinct(true)
            ->join('sv_device_take_over_task_account ta', 'ta.id = r.task_account_id and ta.user_id = r.user_id')
            ->join('sv_device_take_over_task t', 't.id = ta.take_over_id and t.user_id = r.user_id')
            ->join('sv_device_task dt', 'dt.sub_task_id = ta.id and dt.user_id = r.user_id and dt.device_code = r.device_code')
            ->where('r.user_id', '=', $this->userId)
            ->where('r.device_code', 'in', $deviceCodes)
            ->where('r.type', '=', 3)
            ->where('ta.account_type', '=', DeviceEnum::ACCOUNT_TYPE_SPH)
            ->where('ta.persona_id', '=', (int)($this->params['persona_id'] ?? 0))
            ->where('t.persona_id', '=', (int)($this->params['persona_id'] ?? 0))
            ->where('dt.persona_id', '=', (int)($this->params['persona_id'] ?? 0))
            ->where('dt.task_scene', '=', DeviceEnum::AUTO_TASK_SCENE_COMMENT_LIKE)
            ->whereNull('r.delete_time')
            ->whereNull('ta.delete_time')
            ->whereNull('t.delete_time')
            ->whereNull('dt.delete_time')
            ->field('r.id,r.user_id,r.task_account_id,r.device_code,r.account,r.nickname,r.avatar,r.user_account,r.user_nickname,r.user_avatar,r.content,r.create_time');

        $this->applySphCommentKeyword($query);
        $this->applyDateFilter($query, 'r.create_time');

        $rows = $query->order('r.create_time', 'desc')
            ->order('r.id', 'desc')
            ->select()
            ->toArray();

        if ($ignorePlatform) {
            $this->allSphCommentRows = $rows;
            return $this->allSphCommentRows;
        }

        $this->sphCommentRows = $rows;
        return $this->sphCommentRows;
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

    private function buildContactIndex(array $contacts): array
    {
        $index = [
            'exact' => [],
            'account' => [],
        ];

        foreach ($contacts as $row) {
            $platform = $this->normalizeContactPlatform($row);
            $baseKey = $this->contactBaseKey($row, $platform);
            $message = $this->normalizeText((string)($row['original_message'] ?? ''));

            if ($message !== '') {
                $index['exact'][$baseKey . '|' . $message][] = $row;
            }
            $index['account'][$baseKey][] = $row;
        }

        return $index;
    }

    private function findContactForMessage(array $row, array $index, array &$usedContactIds): ?array
    {
        $platform = (int)($row['type'] ?? 0);
        $baseKey = $this->messageBaseKey($row, $platform);
        $message = $this->normalizeText((string)($row['message_content'] ?? ''));
        $exactKey = $baseKey . '|' . $message;

        if (isset($index['exact'][$exactKey])) {
            foreach ($index['exact'][$exactKey] as $contact) {
                if ($this->contactIsAvailable($contact, $usedContactIds)) {
                    return $this->takeContact($contact, $usedContactIds);
                }
            }
        }

        foreach ($index['account'][$baseKey] ?? [] as $contact) {
            if (!$this->contactIsAvailable($contact, $usedContactIds)) {
                continue;
            }

            $original = $this->normalizeText((string)($contact['original_message'] ?? ''));
            if ($original !== '' && ($this->containsText($message, $original) || $this->containsText($original, $message))) {
                return $this->takeContact($contact, $usedContactIds);
            }
        }

        foreach ($index['account'][$baseKey] ?? [] as $contact) {
            if ($this->contactIsAvailable($contact, $usedContactIds)) {
                return $this->takeContact($contact, $usedContactIds);
            }
        }

        return null;
    }

    private function formatPrivateMessage(array $row, ?array $contact): array
    {
        $platform = (int)($row['type'] ?? 0);
        $messageContent = $this->displayMessage((string)($row['message_content'] ?? ''));
        $replyContent = (string)($row['reply_content'] ?? '');
        $contactValue = $this->normalizeContactValue((string)($contact['reg_wechat'] ?? ''));

        if ($contactValue === '') {
            $contactValue = $this->extractContactValue([$messageContent, $replyContent]);
        }

        $messageTime = (string)($row['create_time'] ?? '');
        $isReply = $this->isReply($row);

        return [
            'id' => 'private_message:' . (int)($row['id'] ?? 0),
            'source_id' => (int)($row['id'] ?? 0),
            'source_type' => ((int)($row['message_task_type'] ?? 0) === 1) ? 'comment_reply' : 'private_message',
            'message_task_type' => (int)($row['message_task_type'] ?? 0),
            'platform_type' => $platform,
            'platform_name' => $this->platformName($platform),
            'device_code' => (string)($row['device_code'] ?? ''),
            'account' => (string)($row['account'] ?? ''),
            'friend_id' => (string)($row['friend_id'] ?? ''),
            'author_name' => (string)($row['author_name'] ?? ''),
            'avatar' => $this->formatFileUrl((string)($row['avatar'] ?? '')),
            'message_content' => $messageContent,
            'reply_content' => $replyContent,
            'is_reply' => $isReply ? 1 : 0,
            'contact_value' => $contactValue,
            'contact_display' => $this->contactDisplay($contactValue),
            'contact_type' => $this->contactType($contactValue),
            'contact_record_id' => (int)($contact['id'] ?? 0),
            'new_message_count' => max(1, (int)($row['new_message_count'] ?? 0)),
            'status_text' => $isReply ? '已发送' : '待回复',
            'message_time' => $this->formatTime($messageTime),
            'reply_time' => $this->formatTime((string)($row['reply_time'] ?? '')),
            'create_time' => $this->formatTime((string)($row['create_time'] ?? '')),
            'timestamp' => $this->toTimestamp($messageTime),
        ];
    }

    private function formatContactRecord(array $row): array
    {
        $platform = $this->normalizeContactPlatform($row);
        $contactValue = $this->normalizeContactValue((string)($row['reg_wechat'] ?? ''));
        $createTime = (string)($row['create_time'] ?? '');

        return [
            'id' => 'add_wechat:' . (int)($row['id'] ?? 0),
            'source_id' => (int)($row['id'] ?? 0),
            'source_type' => 'contact',
            'message_task_type' => 0,
            'platform_type' => $platform,
            'platform_name' => $this->platformName($platform),
            'device_code' => (string)($row['device_code'] ?? ''),
            'account' => (string)($row['account'] ?? ''),
            'friend_id' => '',
            'author_name' => (string)(($row['user_account'] ?? '') ?: ($row['wechat_name'] ?? '')),
            'avatar' => '',
            'message_content' => (string)($row['original_message'] ?? ''),
            'reply_content' => '',
            'is_reply' => 0,
            'contact_value' => $contactValue,
            'contact_display' => $this->contactDisplay($contactValue),
            'contact_type' => $this->contactType($contactValue),
            'contact_record_id' => (int)($row['id'] ?? 0),
            'new_message_count' => 1,
            'status_text' => $this->contactStatusText((int)($row['status'] ?? 0), (string)($row['result'] ?? '')),
            'message_time' => $this->formatTime($createTime),
            'reply_time' => '',
            'create_time' => $this->formatTime($createTime),
            'timestamp' => $this->toTimestamp($createTime),
        ];
    }

    private function formatSphCommentRecord(array $row): array
    {
        $messageContent = $this->displayMessage((string)($row['content'] ?? ''));
        $contactValue = $this->normalizeContactValue($this->extractContactValue([$messageContent]));
        $createTime = (string)($row['create_time'] ?? '');

        return [
            'id' => 'sph_comment:' . (int)($row['id'] ?? 0),
            'source_id' => (int)($row['id'] ?? 0),
            'source_type' => 'comment_reply',
            'message_task_type' => 1,
            'platform_type' => DeviceEnum::ACCOUNT_TYPE_SPH,
            'platform_name' => $this->platformName(DeviceEnum::ACCOUNT_TYPE_SPH),
            'device_code' => (string)($row['device_code'] ?? ''),
            'account' => (string)($row['account'] ?? ''),
            'friend_id' => (string)($row['user_account'] ?? ''),
            'author_name' => (string)(($row['user_nickname'] ?? '') ?: ($row['user_account'] ?? '')),
            'avatar' => $this->formatFileUrl((string)($row['user_avatar'] ?? '')),
            'message_content' => $messageContent,
            'reply_content' => '',
            'is_reply' => 1,
            'contact_value' => $contactValue,
            'contact_display' => $this->contactDisplay($contactValue),
            'contact_type' => $this->contactType($contactValue),
            'contact_record_id' => 0,
            'new_message_count' => 1,
            'status_text' => '已执行',
            'message_time' => $this->formatTime($createTime),
            'reply_time' => '',
            'create_time' => $this->formatTime($createTime),
            'task_account_id' => (int)($row['task_account_id'] ?? 0),
            'execute_account' => (string)($row['account'] ?? ''),
            'execute_name' => (string)($row['nickname'] ?? ''),
            'execute_avatar' => $this->formatFileUrl((string)($row['avatar'] ?? '')),
            'timestamp' => $this->toTimestamp($createTime),
        ];
    }

    private function buildTabs(array $items): array
    {
        $counts = [];
        foreach ($items as $item) {
            $platform = (int)($item['platform_type'] ?? 0);
            $counts[$platform] = ($counts[$platform] ?? 0) + 1;
        }

        $tabs = [
            ['platform_type' => 'all', 'platform_name' => '全部', 'count' => count($items)],
            ['platform_type' => DeviceEnum::ACCOUNT_TYPE_DY, 'platform_name' => '抖音', 'count' => $counts[DeviceEnum::ACCOUNT_TYPE_DY] ?? 0],
            ['platform_type' => DeviceEnum::ACCOUNT_TYPE_XHS, 'platform_name' => '小红书', 'count' => $counts[DeviceEnum::ACCOUNT_TYPE_XHS] ?? 0],
            ['platform_type' => DeviceEnum::ACCOUNT_TYPE_KS, 'platform_name' => '快手', 'count' => $counts[DeviceEnum::ACCOUNT_TYPE_KS] ?? 0],
        ];

        if ($this->messageTaskType() !== 2) {
            $tabs[] = ['platform_type' => DeviceEnum::ACCOUNT_TYPE_SPH, 'platform_name' => '视频号', 'count' => $counts[DeviceEnum::ACCOUNT_TYPE_SPH] ?? 0];
        }

        return $tabs;
    }

    private function applyPrivateKeyword(Query $query): void
    {
        $keyword = $this->keyword();
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

    private function applyContactKeyword(Query $query): void
    {
        $keyword = $this->keyword();
        if ($keyword === '') {
            return;
        }

        $like = '%' . $keyword . '%';
        $query->where(function ($query) use ($like) {
            $query->where('user_account', 'like', $like)
                ->whereOr('original_message', 'like', $like)
                ->whereOr('reg_wechat', 'like', $like)
                ->whereOr('wechat_no', 'like', $like)
                ->whereOr('wechat_name', 'like', $like)
                ->whereOr('account', 'like', $like);
        });
    }

    private function applySphCommentKeyword(Query $query): void
    {
        $keyword = $this->keyword();
        if ($keyword === '') {
            return;
        }

        $like = '%' . $keyword . '%';
        $query->where(function ($query) use ($like) {
            $query->where('r.user_account', 'like', $like)
                ->whereOr('r.user_nickname', 'like', $like)
                ->whereOr('r.content', 'like', $like)
                ->whereOr('r.account', 'like', $like)
                ->whereOr('r.nickname', 'like', $like);
        });
    }

    private function applyDateFilter(Query $query, string $field = 'create_time'): void
    {
        $start = $this->parseTimeParam('start_time');
        $end = $this->parseTimeParam('end_time', true);

        if ($start > 0 && $end > 0) {
            $query->whereBetween($field, [$start, $end]);
            return;
        }
        if ($start > 0) {
            $query->where($field, '>=', $start);
            return;
        }
        if ($end > 0) {
            $query->where($field, '<=', $end);
            return;
        }

        $date = $this->queryDate();
        $query->whereBetween($field, [
            strtotime($date . ' 00:00:00'),
            strtotime($date . ' 23:59:59'),
        ]);
    }

    private function platformType(): ?int
    {
        $raw = $this->params['platform_type'] ?? null;
        if ($raw === null || $raw === '') {
            $raw = $this->params['account_type'] ?? null;
        }
        if ($raw === null || $raw === '' || $raw === 'all') {
            return null;
        }
        $platform = (int)$raw;
        return in_array($platform, $this->filterablePlatformTypes(), true) ? $platform : -1;
    }

    private function keyword(): string
    {
        return trim((string)($this->params['keyword'] ?? ''));
    }

    private function messageTaskType(): ?int
    {
        if (!isset($this->params['message_task_type']) || $this->params['message_task_type'] === '') {
            return null;
        }
        return (int)$this->params['message_task_type'];
    }

    private function queryDate(): string
    {
        $date = trim((string)($this->params['date'] ?? ''));
        if ($date !== '' && strtotime($date) !== false) {
            return date('Y-m-d', strtotime($date));
        }
        return date('Y-m-d');
    }

    private function normalizeContactPlatform(array $row): int
    {
        $accountType = (int)($row['account_type'] ?? 0);
        if (in_array($accountType, $this->platformTypes(), true)) {
            return $accountType;
        }

        $channel = (int)($row['channel'] ?? 0);
        if (in_array($channel, $this->platformTypes(), true)) {
            return $channel;
        }

        return 0;
    }

    private function platformTypes(): array
    {
        return [
            DeviceEnum::ACCOUNT_TYPE_XHS,
            DeviceEnum::ACCOUNT_TYPE_DY,
            DeviceEnum::ACCOUNT_TYPE_KS,
        ];
    }

    private function filterablePlatformTypes(): array
    {
        return array_merge($this->platformTypes(), [DeviceEnum::ACCOUNT_TYPE_SPH]);
    }

    private function platformName(int $platform): string
    {
        $map = [
            DeviceEnum::ACCOUNT_TYPE_SPH => '视频号',
            DeviceEnum::ACCOUNT_TYPE_XHS => '小红书',
            DeviceEnum::ACCOUNT_TYPE_DY => '抖音',
            DeviceEnum::ACCOUNT_TYPE_KS => '快手',
        ];
        return $map[$platform] ?? '未知';
    }

    private function isReply(array $row): bool
    {
        return (int)($row['is_reply'] ?? 0) === 1
            || trim((string)($row['reply_content'] ?? '')) !== ''
            || trim((string)($row['reply_time'] ?? '')) !== '';
    }

    private function shouldIncludeSphComments(bool $ignorePlatform): bool
    {
        $messageTaskType = $this->messageTaskType();
        if ($messageTaskType !== null && $messageTaskType !== 1) {
            return false;
        }

        if ($ignorePlatform) {
            return true;
        }

        $platform = $this->platformType();
        return $platform === null || $platform === DeviceEnum::ACCOUNT_TYPE_SPH;
    }

    private function contactStatusText(int $status, string $result): string
    {
        if ($result !== '') {
            return $result;
        }

        $map = [
            0 => '待执行',
            1 => '添加成功',
            2 => '执行中',
            3 => '账号冷却中',
            4 => '待执行',
            5 => '冷却中',
        ];
        return $map[$status] ?? '未知';
    }

    private function messageBaseKey(array $row, int $platform): string
    {
        return $this->baseKey(
            (string)($row['device_code'] ?? ''),
            (string)($row['account'] ?? ''),
            $platform
        );
    }

    private function contactBaseKey(array $row, int $platform): string
    {
        return $this->baseKey(
            (string)($row['device_code'] ?? ''),
            (string)($row['account'] ?? ''),
            $platform
        );
    }

    private function baseKey(string $deviceCode, string $account, int $platform): string
    {
        return strtolower(trim($deviceCode)) . '|' . strtolower(trim($account)) . '|' . $platform;
    }

    private function contactIsAvailable(array $contact, array $usedContactIds): bool
    {
        $id = (int)($contact['id'] ?? 0);
        return $id > 0 && !isset($usedContactIds[$id]);
    }

    private function takeContact(array $contact, array &$usedContactIds): array
    {
        $usedContactIds[(int)$contact['id']] = true;
        return $contact;
    }

    private function displayMessage(string $message): string
    {
        $parts = explode('&&', $message);
        return trim((string)($parts[0] ?? $message));
    }

    private function normalizeText(string $text): string
    {
        $text = strip_tags($text);
        $text = str_replace('&&', ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text) ?: '';
        return strtolower(trim($text));
    }

    private function containsText(string $haystack, string $needle): bool
    {
        return $haystack !== '' && $needle !== '' && str_contains($haystack, $needle);
    }

    private function extractContactValue(array $contents): string
    {
        foreach ($contents as $content) {
            $content = (string)$content;
            if (preg_match('/(?<!\d)1[3-9]\d{9}(?!\d)/', $content, $matches)) {
                return $matches[0];
            }
            if (preg_match('/(?:微信号?|微号|VX|vx|wx|wechat)[^\w]{0,8}([a-zA-Z][-_a-zA-Z0-9]{5,19})/iu', $content, $matches)) {
                return $matches[1];
            }
        }
        return '';
    }

    private function normalizeContactValue(string $contact): string
    {
        return trim($contact);
    }

    private function contactType(string $contact): string
    {
        if ($contact === '') {
            return '';
        }
        if (preg_match('/^1[3-9]\d{9}$/', $contact)) {
            return 'phone';
        }
        return 'wechat';
    }

    private function contactDisplay(string $contact): string
    {
        if ($this->contactType($contact) !== 'phone') {
            return $contact;
        }
        return substr($contact, 0, 3) . '****' . substr($contact, -4);
    }

    private function formatFileUrl(string $url): string
    {
        return $url === '' ? '' : FileService::getFileUrl($url);
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

    private function toTimestamp(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }
        if (is_numeric($value)) {
            return (int)$value;
        }
        $timestamp = strtotime($value);
        return $timestamp === false ? 0 : $timestamp;
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

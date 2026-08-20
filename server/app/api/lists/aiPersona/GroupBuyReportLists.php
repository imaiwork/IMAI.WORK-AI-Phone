<?php

namespace app\api\lists\aiPersona;

use app\api\lists\BaseApiDataLists;
use app\common\enum\DeviceEnum;
use app\common\lists\ListsExtendInterface;
use app\common\lists\ListsSearchInterface;
use app\common\model\aiPersona\AiPersonaTrafficConfig;
use app\common\model\sv\SvDeviceTask;
use app\common\service\FileService;
use think\db\Query;

class GroupBuyReportLists extends BaseApiDataLists implements ListsSearchInterface, ListsExtendInterface
{
    private const DEFAULT_CONFIG = [
        'group_buy_method' => 1,
        'group_buy_keyword' => '',
        'range' => 5,
        'exec_number' => 50,
        'comment_keywords' => [],
        'group_publish_day' => 3,
        'group_num_comment' => 1,
        'interactive_action' => [1, 2, 3, 4],
        'group_thumb_method' => 1,
        'view_video_time' => 10,
        'touch_interval' => 10,
        'gender' => 0,
        'filter_ip' => '',
        'filter_address' => '',
        'filter_nickname' => [],
    ];

    private ?array $rows = null;
    private ?array $taskRows = null;
    private ?array $trafficConfig = null;
    private ?array $extendData = null;

    public function setSearch(): array
    {
        return [];
    }

    public function lists(): array
    {
        return array_slice($this->getRows(), $this->limitOffset, $this->limitLength);
    }

    public function count(): int
    {
        return count($this->getRows());
    }

    public function extend(): array
    {
        if ($this->extendData !== null) {
            return $this->extendData;
        }

        $rows = $this->getRows();
        $this->extendData = [
            'config' => $this->buildConfig(),
            'summary' => $this->buildSummary($rows),
        ];

        return $this->extendData;
    }

    private function baseRowsQuery(): Query
    {
        $query = SvDeviceTask::alias('dt')
            ->join('sv_group_buy_task_account gta', 'gta.id = dt.sub_task_id and gta.user_id = dt.user_id')
            ->join('sv_group_buy_task gt', 'gt.id = gta.group_buy_id and gt.user_id = dt.user_id')
            ->join('sv_group_buy_record r', 'r.group_buy_account_id = gta.id and r.group_buy_id = gt.id and r.user_id = dt.user_id')
            ->where('dt.user_id', '=', $this->userId)
            ->where('dt.persona_id', '=', $this->personaId())
            ->where('dt.source', '=', DeviceEnum::TASK_SOURCE_GROUP_BUY)
            ->where('dt.task_type', '=', DeviceEnum::TASK_TYPE_GROUP_BUY)
            ->where('dt.task_scene', '=', DeviceEnum::AUTO_TASK_SCENE_GROUP_BUY)
            ->where('dt.day', '=', $this->taskDate())
            ->whereNull('dt.delete_time')
            ->whereNull('gta.delete_time')
            ->whereNull('gt.delete_time')
            ->whereNull('r.delete_time');

        $this->applyPlatformFilter($query, true);
        $this->applyTaskTypeFilter($query, true);
        $this->applyStatusFilter($query);
        $this->applyKeywordFilter($query);

        return $query;
    }

    private function baseTaskQuery(): Query
    {
        $query = SvDeviceTask::alias('dt')
            ->join('sv_group_buy_task_account gta', 'gta.id = dt.sub_task_id and gta.user_id = dt.user_id')
            ->join('sv_group_buy_task gt', 'gt.id = gta.group_buy_id and gt.user_id = dt.user_id')
            ->where('dt.user_id', '=', $this->userId)
            ->where('dt.persona_id', '=', $this->personaId())
            ->where('dt.source', '=', DeviceEnum::TASK_SOURCE_GROUP_BUY)
            ->where('dt.task_type', '=', DeviceEnum::TASK_TYPE_GROUP_BUY)
            ->where('dt.task_scene', '=', DeviceEnum::AUTO_TASK_SCENE_GROUP_BUY)
            ->where('dt.day', '=', $this->taskDate())
            ->whereNull('dt.delete_time')
            ->whereNull('gta.delete_time')
            ->whereNull('gt.delete_time');

        $this->applyPlatformFilter($query, false);
        $this->applyTaskTypeFilter($query, false);

        return $query;
    }

    private function getRows(): array
    {
        if ($this->rows !== null) {
            return $this->rows;
        }

        $records = $this->baseRowsQuery()
            ->field([
                'dt.id' => 'device_task_id',
                'dt.task_name' => 'device_task_name',
                'dt.account_type' => 'device_account_type',
                'dt.account' => 'device_account',
                'dt.nickname' => 'device_nickname',
                'dt.avatar' => 'device_avatar',
                'dt.time_config' => 'device_time_config',
                'dt.start_time' => 'device_start_time',
                'dt.end_time' => 'device_end_time',
                'gta.id' => 'group_buy_account_id',
                'gta.task_type' => 'account_task_type',
                'gta.account' => 'executor_account',
                'gta.account_type' => 'executor_account_type',
                'gta.nickname' => 'executor_nickname',
                'gta.avatar' => 'executor_avatar',
                'gta.device_code' => 'executor_device_code',
                'gta.send_start_time' => 'account_start_time',
                'gta.send_end_time' => 'account_end_time',
                'gt.id' => 'group_buy_id',
                'gt.task_type' => 'setting_task_type',
                'gt.group_buy_type' => 'group_buy_type',
                'gt.name' => 'setting_name',
                'gt.marker_method' => 'marker_method',
                'gt.group_type' => 'setting_group_type',
                'gt.time_config' => 'setting_time_config',
                'gt.task_start_time' => 'setting_start_time',
                'gt.task_end_time' => 'setting_end_time',
                'r.id' => 'record_id',
                'r.task_type' => 'record_task_type',
                'r.status' => 'record_status',
                'r.avatar' => 'target_avatar',
                'r.account' => 'target_account',
                'r.account_name' => 'target_account_name',
                'r.account_type' => 'record_account_type',
                'r.platform' => 'record_platform',
                'r.device_code' => 'record_device_code',
                'r.task_id' => 'report_task_id',
                'r.remark' => 'remark',
                'r.send_time' => 'send_time',
                'r.exec_time' => 'record_exec_time',
                'r.pusher_timer' => 'pusher_timer',
                'r.address' => 'address',
                'r.likes' => 'likes',
                'r.fans' => 'fans',
                'r.follows' => 'follows',
                'r.industry_keyword' => 'industry_keyword',
                'r.note_title' => 'note_title',
                'r.notes' => 'notes',
                'r.filter_keyword' => 'filter_keyword',
                'r.comment_content' => 'comment_content',
                'r.touch_content' => 'touch_content',
                'r.image' => 'image',
                'r.create_time' => 'record_create_time',
                'r.update_time' => 'record_update_time',
                'r.content' => 'content',
            ])
            ->order('r.id', 'desc')
            ->select()
            ->toArray();
        //print_r(\think\facade\Db::getLastSql());die;
        $items = [];
        $seen = [];
        foreach ($records as $record) {
            $recordId = (int)($record['record_id'] ?? 0);
            if ($recordId > 0 && isset($seen[$recordId])) {
                continue;
            }
            $seen[$recordId] = true;
            $items[] = $this->formatItem($record);
        }

        $this->rows = $items;
        return $this->rows;
    }

    private function getTaskRows(): array
    {
        if ($this->taskRows !== null) {
            return $this->taskRows;
        }

        $rows = $this->baseTaskQuery()
            ->field([
                'dt.id' => 'device_task_id',
                'dt.account_type' => 'device_account_type',
                'dt.time_config' => 'device_time_config',
                'dt.start_time' => 'device_start_time',
                'dt.end_time' => 'device_end_time',
                'gta.id' => 'group_buy_account_id',
                'gta.task_type' => 'account_task_type',
                'gta.account_type' => 'executor_account_type',
                'gta.send_start_time' => 'account_start_time',
                'gta.send_end_time' => 'account_end_time',
                'gt.id' => 'group_buy_id',
                'gt.task_type' => 'setting_task_type',
                'gt.group_buy_type' => 'group_buy_type',
                'gt.marker_method' => 'marker_method',
                'gt.group_type' => 'setting_group_type',
                'gt.time_config' => 'setting_time_config',
                'gt.task_start_time' => 'setting_start_time',
                'gt.task_end_time' => 'setting_end_time',
            ])
            ->order('dt.start_time', 'asc')
            ->order('dt.id', 'asc')
            ->select()
            ->toArray();

        $this->taskRows = $this->uniqueRowsBy($rows, 'device_task_id');
        return $this->taskRows;
    }

    private function formatItem(array $row): array
    {
        $platform = $this->rowPlatform($row);
        $markerMethod = $this->normalizeIntList($row['marker_method'] ?? []);
        $actionType = $this->rowActionType(['marker_method' => $markerMethod]);
        $messageContent = (string)(($row['comment_content'] ?? '') ?: ($row['touch_content'] ?? ''));
        $industryKeyword = (string)($row['industry_keyword'] ?? '');

        return [
            'id' => (int)($row['record_id'] ?? 0),
            'record_id' => (int)($row['record_id'] ?? 0),
            'task_id' => (int)($row['device_task_id'] ?? 0),
            'report_task_id' => (string)($row['report_task_id'] ?? ''),
            'group_buy_id' => (int)($row['group_buy_id'] ?? 0),
            'group_buy_account_id' => (int)($row['group_buy_account_id'] ?? 0),
            'platform_type' => $platform,
            'platform_name' => $this->platformName($platform),
            'action_type' => $actionType,
            'action_text' => $this->actionText($actionType),
            'marker_method' => $markerMethod,
            'account_name' => (string)($row['target_account_name'] ?? ''),
            'account' => (string)($row['target_account'] ?? ''),
            'avatar' => $this->formatFileUrl((string)($row['target_avatar'] ?? '')),
            'image' => $this->formatFileUrl((string)($row['image'] ?? '')),
            'content' => (string)($row['content'] ?? ''),
            'note_title' => (string)($row['note_title'] ?? ''),
            'notes' => (string)($row['notes'] ?? ''),
            'hit_keyword' => $industryKeyword,
            'industry_keyword' => $industryKeyword,
            'filter_keyword' => (string)($row['filter_keyword'] ?? ''),
            'comment_content' => (string)($row['comment_content'] ?? ''),
            'touch_content' => (string)($row['touch_content'] ?? ''),
            'message_content' => $messageContent,
            'likes' => (int)($row['likes'] ?? 0),
            'fans' => (int)($row['fans'] ?? 0),
            'follows' => (int)($row['follows'] ?? 0),
            'address' => (string)($row['address'] ?? ''),
            'pusher_timer' => (string)($row['pusher_timer'] ?? ''),
            'status' => (int)($row['record_status'] ?? 0),
            'status_text' => $this->recordStatusText((int)($row['record_status'] ?? 0)),
            'create_time' => $this->formatTime($row['record_create_time'] ?? ''),
            'exec_time' => $this->formatTime($row['record_exec_time'] ?? ''),
            'send_time' => $this->formatTime($row['send_time'] ?? ''),
            'executor_account' => (string)($row['executor_account'] ?? ''),
            'executor_nickname' => (string)(($row['executor_nickname'] ?? '') ?: ($row['device_nickname'] ?? '')),
            'executor_avatar' => $this->formatFileUrl((string)(($row['executor_avatar'] ?? '') ?: ($row['device_avatar'] ?? ''))),
            'device_code' => (string)(($row['record_device_code'] ?? '') ?: ($row['executor_device_code'] ?? '')),
            'remark' => (string)($row['remark'] ?? ''),
        ];
    }

    private function buildConfig(): array
    {
        $trafficConfig = $this->getTrafficConfig();
        $groupConfig = $this->arrayValue($trafficConfig['group_buy_config'] ?? []);
        $taskRows = $this->getTaskRows();

        return array_merge([
            'config_id' => (int)($trafficConfig['id'] ?? 0),
            'persona_id' => $this->personaId(),
            'edit_url_key' => 'group_buy_config',
        ], self::DEFAULT_CONFIG, $groupConfig, [
            'execute_times' => $this->collectExecuteTimes($taskRows),
        ]);
    }

    private function buildSummary(array $rows): array
    {
        $summary = [
            'total_count' => count($rows),
            'comment_count' => 0,
            'private_message_count' => 0,
            'like_count' => 0,
        ];

        // marker_method: 1点赞 2关注 3评论 4私信；多项同时累加，不依赖 action_type
        foreach ($rows as $row) {
            $methods = $this->normalizeIntList($row['marker_method'] ?? []);
            if (in_array(1, $methods, true)) {
                $summary['like_count']++;
            }
            if (in_array(3, $methods, true)) {
                $summary['comment_count']++;
            }
            if (in_array(4, $methods, true)) {
                $summary['private_message_count']++;
            }
        }

        return $summary;
    }

    private function applyPlatformFilter(Query $query, bool $includeRecord): void
    {
        $platform = $this->platformType();
        if ($platform === null) {
            return;
        }

        $query->where(function ($query) use ($platform, $includeRecord) {
            $query->where('dt.account_type', '=', $platform)
                ->whereOr('gta.account_type', '=', $platform);

            if ($includeRecord) {
                $query->whereOr('r.account_type', '=', $platform)
                    ->whereOr('r.platform', '=', $platform);
            }
        });
    }

    private function applyTaskTypeFilter(Query $query, bool $includeRecord): void
    {
        $taskType = $this->taskType();
        if ($taskType === null) {
            return;
        }

        $query->where(function ($query) use ($taskType, $includeRecord) {
            $query->where('gta.task_type', '=', $taskType)
                ->whereOr('gt.task_type', '=', $taskType);

            if ($includeRecord) {
                $query->whereOr('r.task_type', '=', $taskType);
            }
        });
    }

    private function applyStatusFilter(Query $query): void
    {
        if (!isset($this->params['status']) || $this->params['status'] === '') {
            return;
        }

        $query->where('r.status', '=', (int)$this->params['status']);
    }

    private function applyKeywordFilter(Query $query): void
    {
        $keyword = $this->keyword();
        if ($keyword === '') {
            return;
        }

        $like = '%' . $keyword . '%';
        $query->where(function ($query) use ($like) {
            $query->where('r.account_name', 'like', $like)
                ->whereOr('r.account', 'like', $like)
                ->whereOr('r.content', 'like', $like)
                ->whereOr('r.note_title', 'like', $like)
                ->whereOr('r.notes', 'like', $like)
                ->whereOr('r.industry_keyword', 'like', $like)
                ->whereOr('r.filter_keyword', 'like', $like)
                ->whereOr('r.comment_content', 'like', $like)
                ->whereOr('r.touch_content', 'like', $like)
                ->whereOr('gt.group_type', 'like', $like)
                ->whereOr('gt.name', 'like', $like)
                ->whereOr('gta.nickname', 'like', $like);
        });
    }

    private function getTrafficConfig(): array
    {
        if ($this->trafficConfig !== null) {
            return $this->trafficConfig;
        }

        $config = AiPersonaTrafficConfig::where('user_id', '=', $this->userId)
            ->where('persona_id', '=', $this->personaId())
            ->whereNull('delete_time')
            ->order('id', 'desc')
            ->find();

        $this->trafficConfig = $config ? $config->toArray() : [];
        return $this->trafficConfig;
    }

    private function rowPlatform(array $row): int
    {
        foreach (['record_platform', 'record_account_type', 'executor_account_type', 'device_account_type'] as $field) {
            $platform = (int)($row[$field] ?? 0);
            if (in_array($platform, $this->platformTypes(), true)) {
                return $platform;
            }
        }
        return 0;
    }

    /**
     * 由 marker_method 推导列表主动作（对外：1评论 2私信 3点赞）
     * marker_method：1点赞 2关注 3评论 4私信；优先级 私信 > 评论 > 点赞
     */
    private function rowActionType(array $row): int
    {
        $methods = $this->normalizeIntList($row['marker_method'] ?? []);
        if (in_array(4, $methods, true)) {
            return 2;
        }
        if (in_array(3, $methods, true)) {
            return 1;
        }
        if (in_array(1, $methods, true)) {
            return 3;
        }
        return 0;
    }

    /**
     * marker_method / interactive_action（1点赞 2关注 3评论 4私信）→ 对外 action_type（1评论 2私信 3点赞）
     */
    private function markerMethodsToActionTypes(array $methods): array
    {
        $actions = [];
        foreach ($methods as $method) {
            $method = (int)$method;
            if ($method === 3) {
                $actions[1] = true;
            }
            if ($method === 4) {
                $actions[2] = true;
            }
            if ($method === 1) {
                $actions[3] = true;
            }
        }
        return array_keys($actions);
    }

    private function configActionTypes(array $groupConfig, array $taskRows): array
    {
        if (isset($groupConfig['interactive_action'])) {
            return $this->markerMethodsToActionTypes(
                $this->normalizeIntList($groupConfig['interactive_action'])
            );
        }

        $methods = [];
        foreach ($taskRows as $row) {
            foreach ($this->normalizeIntList($row['marker_method'] ?? []) as $method) {
                $methods[$method] = true;
            }
        }

        return $this->markerMethodsToActionTypes(array_keys($methods));
    }

    private function collectExecuteTimes(array $rows): array
    {
        $times = [];
        foreach ($rows as $row) {
            foreach ($this->extractTimeRanges($row['device_time_config'] ?? '') as $time) {
                $times[$time] = true;
            }
        }

        return array_keys($times);
    }

    private function collectTaskValues(array $rows, string $field): array
    {
        $values = [];
        foreach ($rows as $row) {
            foreach ($this->normalizeListValue($row[$field] ?? '') as $value) {
                $values[$value] = true;
            }
        }

        return array_keys($values);
    }

    private function extractTimeRanges($value): array
    {
        $times = [];
        foreach ($this->flattenValue($value) as $item) {
            $normalized = $this->normalizeTimeRangeText((string)$item);
            if ($normalized !== '') {
                $times[$normalized] = true;
            }
        }

        return array_keys($times);
    }

    private function normalizeTimeRangeText(string $timeRange): string
    {
        $timeRange = trim($timeRange);
        if ($timeRange === '') {
            return '';
        }

        $timeRange = str_replace(['至', '～', '~', '—', '–'], '-', $timeRange);
        $timeRange = preg_replace('/\s+/', '', $timeRange) ?: '';

        if (!preg_match('/^(\d{1,2}):(\d{2})-(\d{1,2}):(\d{2})$/', $timeRange, $matches)) {
            return '';
        }

        $start = sprintf('%02d:%02d', min(23, max(0, (int)$matches[1])), min(59, max(0, (int)$matches[2])));
        $end = sprintf('%02d:%02d', min(23, max(0, (int)$matches[3])), min(59, max(0, (int)$matches[4])));

        return $start . ' - ' . $end;
    }

    private function flattenValue($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $this->flattenValue($decoded);
            }
            return [$value];
        }

        if (!is_array($value)) {
            return [$value];
        }

        $items = [];
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                if (isset($item['start_time'], $item['end_time'])) {
                    $items[] = $this->formatClock($item['start_time']) . '-' . $this->formatClock($item['end_time']);
                    continue;
                }
                if (isset($item['time'])) {
                    $items[] = $item['time'];
                    continue;
                }
                if (is_string($key) && !is_numeric($key)) {
                    $items[] = $key;
                }
                array_push($items, ...$this->flattenValue($item));
                continue;
            }

            if (is_string($key) && !is_numeric($key)) {
                $items[] = $key;
            }
            $items[] = $item;
        }

        return $items;
    }

    private function normalizeListValue($value): array
    {
        $values = [];
        foreach ($this->flattenValue($value) as $item) {
            $item = trim((string)$item);
            if ($item === '') {
                continue;
            }

            foreach (preg_split('/[,，、\n\r]+/u', $item) ?: [] as $part) {
                $part = trim((string)$part);
                if ($part !== '') {
                    $values[$part] = true;
                }
            }
        }

        return array_keys($values);
    }

    private function normalizeIntList($value): array
    {
        $items = [];
        foreach ($this->normalizeListValue($value) as $item) {
            if (is_numeric($item)) {
                $items[(int)$item] = true;
            }
        }

        return array_keys($items);
    }

    private function arrayValue($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    private function dateRange(): array
    {
        $date = $this->taskDate();
        $start = $this->parseDateParam('start_time', false);
        $end = $this->parseDateParam('end_time', true);

        if ($start <= 0) {
            $start = strtotime($date . ' 00:00:00') ?: 0;
        }
        if ($end <= 0) {
            $end = strtotime($date . ' 23:59:59') ?: 0;
        }

        return [$start, $end];
    }

    private function parseDateParam(string $name, bool $endOfDay = false): int
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

    private function taskDate(): string
    {
        if (!empty($this->params['date'])) {
            return (string)$this->params['date'];
        }

        if (!empty($this->params['start_time'])) {
            $timestamp = strtotime((string)$this->params['start_time']);
            if ($timestamp !== false) {
                return date('Y-m-d', $timestamp);
            }
        }

        return date('Y-m-d');
    }

    private function personaId(): int
    {
        return (int)($this->params['persona_id'] ?? 0);
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

        return (int)$raw;
    }

    private function taskType(): ?int
    {
        if (!isset($this->params['task_type']) || $this->params['task_type'] === '') {
            return null;
        }

        return (int)$this->params['task_type'];
    }

    private function keyword(): string
    {
        return trim((string)($this->params['keyword'] ?? ''));
    }

    private function uniqueRowsBy(array $rows, string $field): array
    {
        $items = [];
        $seen = [];
        foreach ($rows as $row) {
            $value = (string)($row[$field] ?? '');
            if ($value !== '' && isset($seen[$value])) {
                continue;
            }
            $seen[$value] = true;
            $items[] = $row;
        }
        return $items;
    }

    private function platformTypes(): array
    {
        return [
            DeviceEnum::ACCOUNT_TYPE_SPH,
            DeviceEnum::ACCOUNT_TYPE_XHS,
            DeviceEnum::ACCOUNT_TYPE_DY,
            DeviceEnum::ACCOUNT_TYPE_KS,
        ];
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

    private function actionText(int $actionType): string
    {
        $map = [
            1 => '评论',
            2 => '私信',
            3 => '点赞',
        ];
        return $map[$actionType] ?? '未知';
    }

    private function configActionText(int $actionType): string
    {
        // 与 actionText 一致：对外 action_type 1评论 2私信 3点赞
        $map = [
            1 => '评论',
            2 => '私信',
            3 => '点赞',
        ];
        return $map[$actionType] ?? '未知';
    }

    private function recordStatusText(int $status): string
    {
        $map = [
            0 => '待发送',
            1 => '已发送',
            2 => '发送失败',
            3 => '发送中',
            4 => '已删除',
        ];
        return $map[$status] ?? '未知';
    }

    private function timeRangeFromStartEnd($startTime, $endTime): string
    {
        $start = $this->formatClock($startTime);
        $end = $this->formatClock($endTime);

        if ($start === '' || $end === '') {
            return '';
        }

        return $start . ' - ' . $end;
    }

    private function formatClock($time): string
    {
        if ($time === null || $time === '') {
            return '';
        }
        if (is_numeric($time)) {
            return date('H:i', (int)$time);
        }

        $timestamp = strtotime((string)$time);
        return $timestamp ? date('H:i', $timestamp) : (string)$time;
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

    private function formatFileUrl(string $url): string
    {
        return $url === '' ? '' : FileService::getFileUrl($url);
    }
}

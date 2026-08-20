<?php

namespace app\api\lists\aiPersona;

use app\api\lists\BaseApiDataLists;
use app\common\enum\DeviceEnum;
use app\common\lists\ListsExtendInterface;
use app\common\lists\ListsSearchInterface;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaTrafficConfig;
use app\common\model\sv\SvCityTouchRecord;
use app\common\model\sv\SvDeviceTask;
use app\common\service\FileService;
use think\db\Query;

class SameCityTouchLists extends BaseApiDataLists implements ListsSearchInterface, ListsExtendInterface
{
    private const DEFAULT_CONFIG = [
        'interactive_action' => [1, 2, 3, 4],
        'view_video_time' => 10,
        'touch_interval' => 10,
        'range' => 5,
        'gender' => 0,
        'age_range' => [
            'min' => 18,
            'max' => 30,
        ],
        'filter_video_thumb_num' => 200,
        'filter_video_comment_num' => 200,
        'filter_comment_fans' => [
            'min' => 10,
            'max' => 200,
        ],
        'filter_comment_follow' => [
            'min' => 10,
            'max' => 200,
        ],
        'filter_nickname' => [],
    ];

    private ?array $rows = null;
    private ?array $allRows = null;
    private ?array $taskRows = null;
    private ?array $allTaskRows = null;
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
            'title' => '找附近的客户',
            'subtitle' => '同城曝光/电子传单找客户',
            'config' => $this->buildConfig(),
            'summary' => $this->buildSummary($rows),
            'time_tabs' => $this->buildTimeTabs(),
            'tabs' => $this->buildPlatformTabs($this->getRows(true)),
        ];

        return $this->extendData;
    }

    private function baseRowsQuery(bool $ignorePlatform = false): Query
    {
        $query = SvCityTouchRecord::alias('r')
            ->join('sv_city_touch_task t', 't.id = r.city_touch_id and t.user_id = r.user_id')
            ->join('sv_city_touch_task_account ta', 'ta.id = r.city_touch_account_id and ta.user_id = r.user_id', 'left')
            ->join('sv_device_task dt', 'dt.sub_task_id = ta.id and dt.user_id = r.user_id')
            ->where('r.user_id', '=', $this->userId)
            ->where('dt.persona_id', '=', $this->personaId())
            ->where('dt.source', '=', DeviceEnum::TASK_SOURCE_SAME_CITY_CUTOFF)
            ->where('dt.task_scene', '=', DeviceEnum::AUTO_TASK_SCENE_SAME_CITY_CUTOFF)
            ->where('dt.day', '=', $this->taskDate())
            ->where('t.persona_id', '=', $this->personaId())
            ->whereNull('r.delete_time')
            ->whereNull('dt.delete_time')
            ->whereNull('t.delete_time')
            ->whereNull('ta.delete_time');

        $this->applyDeviceFilter($query, 'dt.device_code');
        $this->applyPlatformFilter($query, $ignorePlatform, true);
        $this->applyStatusFilter($query);
        $this->applyKeywordFilter($query);

        return $query;
    }

    private function baseTaskQuery(bool $ignorePlatform = false, bool $ignoreTime = false): Query
    {
        $query = SvDeviceTask::alias('dt')
            ->join('sv_city_touch_task_account ta', 'ta.id = dt.sub_task_id and ta.user_id = dt.user_id', 'left')
            ->join('sv_city_touch_task t', 't.id = ta.city_touch_id and t.user_id = dt.user_id', 'left')
            ->where('dt.user_id', '=', $this->userId)
            ->where('dt.persona_id', '=', $this->personaId())
            ->where('dt.source', '=', DeviceEnum::TASK_SOURCE_SAME_CITY_CUTOFF)
            ->where('dt.task_scene', '=', DeviceEnum::AUTO_TASK_SCENE_SAME_CITY_CUTOFF)
            ->where('dt.day', '=', $this->taskDate())
            ->whereNull('dt.delete_time')
            ->whereNull('ta.delete_time')
            ->whereNull('t.delete_time');

        $this->applyDeviceFilter($query, 'dt.device_code');
        $this->applyPlatformFilter($query, $ignorePlatform, false);

        return $query;
    }

    private function getRows(bool $ignorePlatform = false): array
    {
        if ($ignorePlatform && $this->allRows !== null) {
            return $this->allRows;
        }
        if (!$ignorePlatform && $this->rows !== null) {
            return $this->rows;
        }

        $records = $this->baseRowsQuery($ignorePlatform)
            ->field([
                'r.id' => 'record_id',
                'r.task_type' => 'record_task_type',
                'r.city_touch_id' => 'city_touch_id',
                'r.city_touch_account_id' => 'city_touch_account_id',
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
                'r.content' => 'source_content',
                'dt.id' => 'device_task_id',
                'dt.status' => 'task_status',
                'dt.start_time' => 'task_start_time',
                'dt.end_time' => 'task_end_time',
                't.id' => 'setting_id',
                't.name' => 'setting_name',
                't.task_type' => 'setting_task_type',
                't.marker_method' => 'marker_method',
                't.chat_type' => 'chat_type',
                't.radius' => 'radius',
                't.gender' => 'setting_gender',
                't.region' => 'region',
                't.city' => 'city',
                't.filter' => 'setting_filter',
                't.nickname_filter' => 'nickname_filter',
                't.task_start_time' => 'city_task_start_time',
                't.task_end_time' => 'city_task_end_time',
                'ta.account' => 'executor_account',
                'ta.account_type' => 'executor_account_type',
                'ta.nickname' => 'executor_nickname',
                'ta.avatar' => 'executor_avatar',
                'ta.device_code' => 'executor_device_code',
            ])
            ->order('dt.start_time', 'asc')
            ->order('r.id', 'desc')
            ->select()
            ->toArray();
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

        if ($ignorePlatform) {
            $this->allRows = $items;
            return $this->allRows;
        }

        $this->rows = $items;
        return $this->rows;
    }

    private function getTaskRows(bool $ignorePlatform = false, bool $ignoreTime = false): array
    {
        if (($ignorePlatform || $ignoreTime) && $this->allTaskRows !== null) {
            return $this->allTaskRows;
        }
        if (!$ignorePlatform && !$ignoreTime && $this->taskRows !== null) {
            return $this->taskRows;
        }

        $rows = $this->baseTaskQuery($ignorePlatform, $ignoreTime)
            ->field([
                'dt.id' => 'task_id',
                'dt.device_code' => 'device_code',
                'dt.account' => 'account',
                'dt.account_type' => 'task_account_type',
                'dt.nickname' => 'task_nickname',
                'dt.avatar' => 'task_avatar',
                'dt.task_name' => 'task_name',
                'dt.status' => 'task_status',
                'dt.start_time' => 'task_start_time',
                'dt.end_time' => 'task_end_time',
                't.time_config' => 'task_time_config',
                'ta.id' => 'city_touch_account_id',
                'ta.account_type' => 'executor_account_type',
                'ta.device_code' => 'executor_device_code',
                'ta.nickname' => 'executor_nickname',
                't.id' => 'city_touch_id',
                't.task_type' => 'setting_task_type',
                't.marker_method' => 'marker_method',
                't.chat_type' => 'chat_type',
                't.radius' => 'radius',
                't.gender' => 'setting_gender',
                't.region' => 'region',
                't.city' => 'city',
                't.filter' => 'setting_filter',
                't.nickname_filter' => 'nickname_filter',
            ])
            ->order('dt.start_time', 'asc')
            ->order('dt.id', 'asc')
            ->select()
            ->toArray();

        $rows = $this->uniqueRowsBy($rows, 'task_id');

        if ($ignorePlatform || $ignoreTime) {
            $this->allTaskRows = $rows;
            return $this->allTaskRows;
        }

        $this->taskRows = $rows;
        return $this->taskRows;
    }

    private function formatItem(array $row): array
    {
        $platform = $this->rowPlatform($row);
        $action = $this->rowAction($row);
        $hitKeyword = (string)(($row['filter_keyword'] ?? '') ?: ($row['industry_keyword'] ?? ''));
        $messageContent = (string)(($row['comment_content'] ?? '') ?: ($row['touch_content'] ?? ''));

        return [
            'id' => (int)($row['record_id'] ?? 0),
            'record_id' => (int)($row['record_id'] ?? 0),
            'city_touch_id' => (int)($row['city_touch_id'] ?? 0),
            'city_touch_account_id' => (int)($row['city_touch_account_id'] ?? 0),
            'task_id' => (string)($row['report_task_id'] ?? ''),
            'platform_type' => $platform,
            'platform_name' => $this->platformName($platform),
            'action_type' => $action['type'],
            'action_key' => $action['key'],
            'action_text' => $action['text'],
            'account_name' => (string)($row['target_account_name'] ?? ''),
            'account' => (string)($row['target_account'] ?? ''),
            'avatar' => $this->formatFileUrl((string)($row['target_avatar'] ?? '')),
            'image' => $this->formatFileUrl((string)($row['image'] ?? '')),
            'source_content' => (string)($row['source_content'] ?? ''),
            'note_title' => (string)($row['note_title'] ?? ''),
            'notes' => (string)($row['notes'] ?? ''),
            'hit_keyword' => $hitKeyword,
            'industry_keyword' => (string)($row['industry_keyword'] ?? ''),
            'filter_keyword' => (string)($row['filter_keyword'] ?? ''),
            'message_content' => $messageContent,
            'comment_content' => (string)($row['comment_content'] ?? ''),
            'touch_content' => (string)($row['touch_content'] ?? ''),
            'likes' => (int)($row['likes'] ?? 0),
            'fans' => (int)($row['fans'] ?? 0),
            'follows' => (int)($row['follows'] ?? 0),
            'address' => (string)($row['address'] ?? ''),
            'pusher_timer' => (string)($row['pusher_timer'] ?? ''),
            'status' => (int)($row['record_status'] ?? 0),
            'status_text' => $this->recordStatusText((int)($row['record_status'] ?? 0)),
            'send_time' => $this->formatTime($row['send_time'] ?? ''),
            'exec_time' => $this->formatTime($row['record_exec_time'] ?? ''),
            'create_time' => $this->formatTime($row['record_create_time'] ?? ''),
            'executor_account' => (string)($row['executor_account'] ?? ''),
            'executor_nickname' => (string)($row['executor_nickname'] ?? ''),
            'executor_avatar' => $this->formatFileUrl((string)($row['executor_avatar'] ?? '')),
            'device_code' => (string)(($row['record_device_code'] ?? '') ?: ($row['executor_device_code'] ?? '')),
            'remark' => (string)($row['remark'] ?? ''),
        ];
    }

    private function buildConfig(): array
    {
        $trafficConfig = $this->getTrafficConfig();
        $config = $this->arrayValue($trafficConfig['same_city_config'] ?? []);
        $config = $this->mergeConfigDefaults($config);

        return array_merge($config, [
            'store_position' => $this->storePosition(),
            'execute_times' => $this->collectDeviceTaskExecuteTimes(),
            'distance_range' => $this->distanceRangeDisplay((int)($config['range'] ?? 0)),
            'gender_filter' => $this->genderFilterDisplay((int)($config['gender'] ?? 0)),
            'target_username_exclude' => $this->normalizeListValue($config['filter_nickname'] ?? []),
            'comment_hit_keywords' => $this->collectCommentHitKeywords(),
        ]);
    }

    private function mergeConfigDefaults(array $config): array
    {
        $merged = self::DEFAULT_CONFIG;
        $nestedFields = [
            'age_range',
            'filter_comment_fans',
            'filter_comment_follow',
        ];

        foreach ($config as $key => $value) {
            if (in_array($key, $nestedFields, true) && is_array($value) && is_array($merged[$key] ?? null)) {
                $merged[$key] = array_replace($merged[$key], $value);
                continue;
            }

            $merged[$key] = $value;
        }

        return $merged;
    }

    private function storePosition(): string
    {
        return (string)AiPersona::where('id', '=', $this->personaId())
            ->where('user_id', '=', $this->userId)
            ->whereNull('delete_time')
            ->value('store_position');
    }

    private function collectDeviceTaskExecuteTimes(): array
    {
        $rows = SvDeviceTask::where('user_id', '=', $this->userId)
            ->where('persona_id', '=', $this->personaId())
            ->where('source', '=', DeviceEnum::TASK_SOURCE_SAME_CITY_CUTOFF)
            ->where('task_scene', '=', DeviceEnum::AUTO_TASK_SCENE_SAME_CITY_CUTOFF)
            ->where('day', '=', $this->taskDate())
            ->whereNull('delete_time')
            ->field(['id', 'time_config', 'start_time'])
            ->order('start_time', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();

        $times = [];
        foreach ($rows as $row) {
            foreach ($this->extractTimeRanges($row['time_config'] ?? '') as $time) {
                $times[$time] = true;
            }
        }

        return array_keys($times);
    }

    private function distanceRangeDisplay(int $range): array
    {
        return [
            'value' => $range,
            'label' => $this->rangeText($range),
            'options' => $this->distanceRangeOptions(),
        ];
    }

    private function distanceRangeOptions(): array
    {
        $options = [];
        foreach ([0, 5, 10, 20] as $value) {
            $options[] = [
                'value' => $value,
                'label' => $this->rangeText($value),
            ];
        }

        return $options;
    }

    private function genderFilterDisplay(int $gender): array
    {
        $gender = in_array($gender, [0, 1, 2], true) ? $gender : 0;

        return [
            'value' => $gender,
            'label' => $this->genderText($gender),
            'options' => $this->genderFilterOptions(),
        ];
    }

    private function genderFilterOptions(): array
    {
        $options = [];
        foreach ([0, 1, 2] as $value) {
            $options[] = [
                'value' => $value,
                'label' => $this->genderText($value),
            ];
        }

        return $options;
    }

    private function collectCommentHitKeywords(): array
    {
        $keywords = [];
        foreach ($this->getRows() as $row) {
            foreach ($this->normalizeListValue($row['filter_keyword'] ?? '') as $keyword) {
                $keywords[$keyword] = true;
            }
        }

        return array_keys($keywords);
    }

    private function buildSummary(array $rows): array
    {
        $summary = [
            'today_count' => count($rows),
            'total_count' => count($rows),
            'device_count' => 0,
            'task_count' => count($this->getTaskRows()),
            'comment_count' => 0,
            'private_message_count' => 0,
            'like_count' => 0,
            'flyer_count' => 0,
            'success_count' => 0,
            'failed_count' => 0,
            'running_count' => 0,
            'waiting_count' => 0,
        ];
        $devices = [];

        foreach ($rows as $row) {
            $deviceCode = (string)($row['device_code'] ?? '');
            if ($deviceCode !== '') {
                $devices[$deviceCode] = true;
            }

            switch ((string)($row['action_key'] ?? '')) {
                case 'comment':
                    $summary['comment_count']++;
                    break;
                case 'private_message':
                    $summary['private_message_count']++;
                    break;
                case 'like':
                    $summary['like_count']++;
                    break;
                case 'electronic_flyer':
                    $summary['flyer_count']++;
                    break;
            }

            $this->incrementStatusCount($summary, $this->recordStatusKey((int)($row['status'] ?? 0)));
        }

        $summary['device_count'] = count($devices);
        return $summary;
    }

    private function buildTimeTabs(): array
    {
        $groups = [];
        foreach ($this->getTaskRows(false, true) as $row) {
            $slot = $this->timeSlot($row);
            $slotKey = $slot['slot_key'];
            $statusKey = $this->taskStatusKey((int)($row['task_status'] ?? 0));
            $deviceCode = (string)($row['device_code'] ?? '');

            if (!isset($groups[$slotKey])) {
                $groups[$slotKey] = [
                    'slot_key' => $slotKey,
                    'time_config' => $slot['time_config'],
                    'time_range' => $slot['time_range'],
                    'start_time' => $slot['start_time'],
                    'end_time' => $slot['end_time'],
                    'status' => DeviceEnum::TASK_STATUS_WAIT,
                    'status_text' => $this->taskStatusText(DeviceEnum::TASK_STATUS_WAIT),
                    'total_count' => 0,
                    'device_count' => 0,
                    'success_count' => 0,
                    'failed_count' => 0,
                    'running_count' => 0,
                    'waiting_count' => 0,
                    'is_all_done' => false,
                    'is_current' => $this->isCurrentTimeSlot($slot['time_range']),
                    '_devices' => [],
                ];
            }

            $groups[$slotKey]['total_count']++;
            if ($deviceCode !== '') {
                $groups[$slotKey]['_devices'][$deviceCode] = true;
            }
            $this->incrementStatusCount($groups[$slotKey], $statusKey);
        }

        foreach ($groups as &$group) {
            $group['device_count'] = count($group['_devices']);
            $group['is_all_done'] = $group['total_count'] > 0 && $group['success_count'] === $group['total_count'];
            $group['status'] = $this->resolveGroupStatus($group);
            $group['status_text'] = $this->taskStatusText($group['status'], $group['is_all_done']);
            unset($group['_devices']);
        }
        unset($group);

        return array_values($groups);
    }

    private function buildPlatformTabs(array $rows): array
    {
        $counts = [];
        foreach ($rows as $row) {
            $platform = (int)($row['platform_type'] ?? 0);
            $counts[$platform] = ($counts[$platform] ?? 0) + 1;
        }

        return [
            ['platform_type' => 'all', 'platform_name' => '全部', 'count' => count($rows)],
            ['platform_type' => DeviceEnum::ACCOUNT_TYPE_DY, 'platform_name' => '抖音', 'count' => $counts[DeviceEnum::ACCOUNT_TYPE_DY] ?? 0],
            ['platform_type' => DeviceEnum::ACCOUNT_TYPE_XHS, 'platform_name' => '小红书', 'count' => $counts[DeviceEnum::ACCOUNT_TYPE_XHS] ?? 0],
            ['platform_type' => DeviceEnum::ACCOUNT_TYPE_KS, 'platform_name' => '快手', 'count' => $counts[DeviceEnum::ACCOUNT_TYPE_KS] ?? 0],
            ['platform_type' => DeviceEnum::ACCOUNT_TYPE_SPH, 'platform_name' => '视频号', 'count' => $counts[DeviceEnum::ACCOUNT_TYPE_SPH] ?? 0],
        ];
    }

    private function applyDeviceFilter(Query $query, string $field): void
    {
        if (empty($this->params['device_code'])) {
            return;
        }

        $query->where($field, '=', trim((string)$this->params['device_code']));
    }

    private function applyPlatformFilter(Query $query, bool $ignorePlatform, bool $includeRecord): void
    {
        if ($ignorePlatform || $this->platformType() === null) {
            return;
        }

        $platform = $this->platformType();
        $query->where(function ($query) use ($platform, $includeRecord) {
            $query->where('ta.account_type', '=', $platform);

            if ($includeRecord) {
                $query->whereOr('r.account_type', '=', $platform)
                    ->whereOr('r.platform', '=', $platform);
            } else {
                $query->whereOr('dt.account_type', '=', $platform);
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
                ->whereOr('t.name', 'like', $like)
                ->whereOr('ta.nickname', 'like', $like);
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
        foreach (['record_platform', 'record_account_type', 'executor_account_type', 'task_account_type'] as $field) {
            $platform = (int)($row[$field] ?? 0);
            if (in_array($platform, $this->platformTypes(), true)) {
                return $platform;
            }
        }

        return 0;
    }

    private function rowAction(array $row): array
    {
        if ((int)($row['chat_type'] ?? 1) !== 1) {
            return ['type' => 4, 'key' => 'electronic_flyer', 'text' => '电子传单'];
        }

        $taskType = (int)(($row['record_task_type'] ?? 0) ?: ($row['setting_task_type'] ?? 0));
        if ($taskType === 1) {
            return ['type' => 1, 'key' => 'comment', 'text' => '评论'];
        }
        if ($taskType === 2) {
            return ['type' => 2, 'key' => 'private_message', 'text' => '私信'];
        }

        foreach ($this->normalizeIntList($row['marker_method'] ?? []) as $method) {
            if ((int)$method === 1) {
                return ['type' => 3, 'key' => 'like', 'text' => '点赞'];
            }
        }

        return ['type' => 0, 'key' => 'unknown', 'text' => '未知'];
    }

    private function collectExecuteTimes(array $rows): array
    {
        $times = [];
        foreach ($rows as $row) {
            foreach ($this->extractTimeRanges($row['task_time_config'] ?? '') as $time) {
                $times[$time] = true;
            }

            $fallback = $this->timeRangeFromStartEnd($row['task_start_time'] ?? '', $row['task_end_time'] ?? '');
            if ($fallback !== '') {
                $times[$fallback] = true;
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

    private function timeSlot(array $row): array
    {
        $timeConfig = $this->normalizeTimeConfig($row['task_time_config'] ?? '');
        $range = $this->parseTimeRange($timeConfig);

        if (!empty($range)) {
            return [
                'slot_key' => 'time_config:' . $range['time_range'],
                'time_config' => $timeConfig,
                'time_range' => $range['time_range'],
                'start_time' => $range['start_time'],
                'end_time' => $range['end_time'],
            ];
        }

        $startTime = $this->formatClock($row['task_start_time'] ?? '');
        $endTime = $this->formatClock($row['task_end_time'] ?? '');
        $timeRange = trim($startTime . ' - ' . $endTime);

        return [
            'slot_key' => 'time_config:' . ($timeConfig !== '' ? $timeConfig : $timeRange),
            'time_config' => $timeConfig,
            'time_range' => $timeConfig !== '' ? $timeConfig : $timeRange,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ];
    }

    private function normalizeTimeConfig($timeConfig): string
    {
        foreach ($this->flattenValue($timeConfig) as $value) {
            $normalized = $this->normalizeTimeRangeText((string)$value);
            if ($normalized !== '') {
                return $normalized;
            }
        }

        return '';
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

        $timeRange = str_replace(['至', '～', '~', '–', '—', '－'], '-', $timeRange);
        $timeRange = preg_replace('/\s+/', '', $timeRange) ?: '';
        $range = $this->parseTimeRange($timeRange);

        return !empty($range) ? $range['time_range'] : '';
    }

    private function parseTimeRange(string $timeRange): array
    {
        $timeRange = str_replace(['至', '～', '~', '–', '—', '－'], '-', trim($timeRange));
        $timeRange = preg_replace('/\s+/', '', $timeRange) ?: '';

        if (!preg_match('/^(\d{1,2}):(\d{2})-(\d{1,2}):(\d{2})$/', $timeRange, $matches)) {
            return [];
        }

        $start = sprintf('%02d:%02d', min(23, max(0, (int)$matches[1])), min(59, max(0, (int)$matches[2])));
        $end = sprintf('%02d:%02d', min(23, max(0, (int)$matches[3])), min(59, max(0, (int)$matches[4])));

        return [
            'start_time' => $start,
            'end_time' => $end,
            'time_range' => $start . ' - ' . $end,
        ];
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

    private function taskDate(): string
    {
        return !empty($this->params['date']) ? (string)$this->params['date'] : date('Y-m-d');
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

    private function keyword(): string
    {
        return trim((string)($this->params['keyword'] ?? ''));
    }

    private function queryTimeRange(): string
    {
        foreach (['time_config', 'time_range', 'slot_key'] as $field) {
            if (empty($this->params[$field])) {
                continue;
            }

            $value = trim((string)$this->params[$field]);
            if ($field === 'slot_key' && str_starts_with($value, 'time_config:')) {
                $value = substr($value, strlen('time_config:'));
            }

            $timeRange = $this->normalizeTimeRangeText($value);
            if ($timeRange !== '') {
                return $timeRange;
            }
        }

        $startTime = trim((string)($this->params['time_start'] ?? ''));
        $endTime = trim((string)($this->params['time_end'] ?? ''));
        if ($this->isClockTime($startTime) && $this->isClockTime($endTime)) {
            return $this->normalizeTimeRangeText($startTime . '-' . $endTime);
        }

        return '';
    }

    private function isClockTime(string $time): bool
    {
        return (bool)preg_match('/^\d{1,2}:\d{2}$/', $time);
    }

    private function isCurrentTimeSlot(string $timeRange): bool
    {
        $queryTimeRange = $this->queryTimeRange();
        return $queryTimeRange !== '' && $queryTimeRange === $timeRange;
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

    private function resolveCity(array $sameCityConfig, array $taskRows): string
    {
        foreach (['city', 'city_name', 'target_city'] as $field) {
            $city = trim((string)($sameCityConfig[$field] ?? ''));
            if ($city !== '') {
                return $city;
            }
        }

        foreach ($taskRows as $row) {
            foreach (['city', 'region'] as $field) {
                $city = trim((string)($row[$field] ?? ''));
                if ($city !== '' && $city !== '不限') {
                    return $city;
                }
            }
        }

        foreach ($this->getRows(true) as $row) {
            $address = trim((string)($row['address'] ?? ''));
            if ($address !== '') {
                return $address;
            }
        }

        return '不限';
    }

    private function configGenderValue(array $sameCityConfig, array $firstTask): int
    {
        if (isset($sameCityConfig['gender']) && $sameCityConfig['gender'] !== '') {
            return (int)$sameCityConfig['gender'];
        }

        $gender = (string)($firstTask['setting_gender'] ?? '');
        if ($gender === '男') {
            return 1;
        }
        if ($gender === '女') {
            return 2;
        }

        return 0;
    }

    private function distanceOptions(int $selected): array
    {
        $options = [];
        foreach ([0, 5, 10, 20] as $value) {
            $options[] = [
                'value' => $value,
                'text' => $this->rangeText($value),
                'selected' => $value === $selected,
            ];
        }

        return $options;
    }

    private function genderOptions(int $selected): array
    {
        $options = [];
        foreach ([0, 1, 2] as $value) {
            $options[] = [
                'value' => $value,
                'text' => $this->genderText($value),
                'selected' => $value === $selected,
            ];
        }

        return $options;
    }

    private function targetScopeOptions(int $selected): array
    {
        $options = [];
        foreach ([0, 1, 2, 3] as $value) {
            $options[] = [
                'value' => $value,
                'text' => $this->targetScopeText($value),
                'selected' => $value === $selected,
            ];
        }

        return $options;
    }

    private function incrementStatusCount(array &$target, string $statusKey): void
    {
        if ($statusKey === 'success') {
            $target['success_count']++;
            return;
        }
        if ($statusKey === 'failed') {
            $target['failed_count']++;
            return;
        }
        if ($statusKey === 'running') {
            $target['running_count']++;
            return;
        }
        if ($statusKey === 'waiting') {
            $target['waiting_count']++;
        }
    }

    private function recordStatusKey(int $status): string
    {
        if ($status === 1) {
            return 'success';
        }
        if ($status === 2) {
            return 'failed';
        }
        if ($status === 3) {
            return 'running';
        }
        return 'waiting';
    }

    private function taskStatusKey(int $status): string
    {
        if ($status === DeviceEnum::TASK_STATUS_FINISHED) {
            return 'success';
        }
        if ($status === DeviceEnum::TASK_STATUS_FAILED) {
            return 'failed';
        }
        if ($status === DeviceEnum::TASK_STATUS_RUNNING) {
            return 'running';
        }
        return 'waiting';
    }

    private function resolveGroupStatus(array $group): int
    {
        if ($group['is_all_done']) {
            return DeviceEnum::TASK_STATUS_FINISHED;
        }
        if ($group['failed_count'] > 0) {
            return DeviceEnum::TASK_STATUS_FAILED;
        }
        if ($group['running_count'] > 0) {
            return DeviceEnum::TASK_STATUS_RUNNING;
        }
        return DeviceEnum::TASK_STATUS_WAIT;
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

    private function rangeText(int $range): string
    {
        return $range > 0 ? $range . '公里内' : '不限';
    }

    private function genderText(int $gender): string
    {
        $map = [
            0 => '不限',
            1 => '男',
            2 => '女',
        ];
        return $map[$gender] ?? '不限';
    }

    private function targetScopeText(int $targetScope): string
    {
        $map = [
            0 => '不限',
            1 => '只找发视频的达人',
            2 => '达人和评论用户都找',
            3 => '只找评论过视频的用户',
        ];
        return $map[$targetScope] ?? '不限';
    }

    private function interactiveActionText(int $action): string
    {
        $map = [
            1 => '点赞',
            2 => '关注',
            3 => '评论',
            4 => '私信',
        ];
        return $map[$action] ?? '未知';
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

    private function taskStatusText(int $status, bool $isAllDone = false): string
    {
        if ($isAllDone) {
            return '已完成';
        }

        $map = [
            DeviceEnum::TASK_STATUS_WAIT => '待执行',
            DeviceEnum::TASK_STATUS_RUNNING => '执行中',
            DeviceEnum::TASK_STATUS_FINISHED => '已完成',
            DeviceEnum::TASK_STATUS_FAILED => '执行失败',
            DeviceEnum::TASK_STATUS_INTERRUPTED => '已中断',
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

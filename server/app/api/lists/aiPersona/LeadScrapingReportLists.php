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

class LeadScrapingReportLists extends BaseApiDataLists implements ListsSearchInterface, ListsExtendInterface
{
    private ?array $rows = null;
    private ?array $allRows = null;
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
            'tabs' => $this->buildTabs($this->getRows(true)),
        ];

        return $this->extendData;
    }

    private function baseRowsQuery(bool $ignorePlatform = false): Query
    {
        $query = SvDeviceTask::alias('dt')
            ->join('sv_lead_scraping_setting_account sa', 'sa.id = dt.sub_task_id and sa.user_id = dt.user_id')
            ->join('sv_lead_scraping_setting s', 's.id = sa.scraping_id and s.user_id = dt.user_id')
            ->join('sv_lead_scraping_record r', 'r.scraping_account_id = sa.id and r.scraping_id = s.id and r.user_id = dt.user_id')
            ->where('dt.user_id', '=', $this->userId)
            ->where('dt.persona_id', '=', $this->personaId())
            ->where('dt.source', '=', DeviceEnum::TASK_SOURCE_TOUCH)
            ->where('dt.day', '=', $this->taskDate())
            ->whereNull('dt.delete_time')
            ->whereNull('sa.delete_time')
            ->whereNull('s.delete_time')
            ->whereNull('r.delete_time');

        $this->applyPlatformFilter($query, $ignorePlatform);
        $this->applyTaskTypeFilter($query, true);
        $this->applyStatusFilter($query);
        $this->applyKeywordFilter($query);

        return $query;
    }

    private function baseTaskQuery(bool $ignorePlatform = false): Query
    {
        $query = SvDeviceTask::alias('dt')
            ->join('sv_lead_scraping_setting_account sa', 'sa.id = dt.sub_task_id and sa.user_id = dt.user_id')
            ->join('sv_lead_scraping_setting s', 's.id = sa.scraping_id and s.user_id = dt.user_id')
            ->where('dt.user_id', '=', $this->userId)
            ->where('dt.persona_id', '=', $this->personaId())
            ->where('dt.source', '=', DeviceEnum::TASK_SOURCE_TOUCH)
            ->where('dt.day', '=', $this->taskDate())
            ->whereNull('dt.delete_time')
            ->whereNull('sa.delete_time')
            ->whereNull('s.delete_time');

        $this->applyPlatformFilter($query, $ignorePlatform, false);
        $this->applyTaskTypeFilter($query, false);

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
                'dt.id' => 'task_id',
                'dt.task_name' => 'task_name',
                'dt.task_scene' => 'task_scene',
                'dt.time_config' => 'task_time_config',
                'dt.start_time' => 'task_start_time',
                'dt.end_time' => 'task_end_time',
                'dt.day' => 'task_day',
                'dt.status' => 'task_status',
                'dt.account_type' => 'task_account_type',
                'dt.account' => 'task_account',
                'dt.nickname' => 'task_nickname',
                'dt.avatar' => 'task_avatar',
                'sa.id' => 'scraping_account_id',
                'sa.name' => 'scraping_account_name',
                'sa.account' => 'scraping_account',
                'sa.account_type' => 'scraping_account_type',
                'sa.nickname' => 'executor_nickname',
                'sa.avatar' => 'executor_avatar',
                'sa.device_code' => 'scraping_device_code',
                'sa.status' => 'scraping_account_status',
                's.id' => 'scraping_id',
                's.name' => 'scraping_name',
                's.task_type' => 'setting_task_type',
                's.industry' => 'setting_industry',
                's.filter' => 'setting_filter',
                'r.id' => 'record_id',
                'r.task_type' => 'record_task_type',
                'r.status' => 'record_status',
                'r.account' => 'target_account',
                'r.account_name' => 'target_account_name',
                'r.account_type' => 'record_account_type',
                'r.platform' => 'record_platform',
                'r.device_code' => 'record_device_code',
                'r.task_id' => 'report_task_id',
                'r.remark' => 'remark',
                'r.exec_time' => 'record_exec_time',
                'r.create_time' => 'record_create_time',
                'r.update_time' => 'record_update_time',
                'r.content' => 'content',
                'r.pusher_timer' => 'pusher_timer',
                'r.address' => 'address',
                'r.image' => 'image',
                'r.likes' => 'likes',
                'r.fans' => 'fans',
                'r.follows' => 'follows',
                'r.industry_keyword' => 'industry_keyword',
                'r.note_title' => 'note_title',
                'r.notes' => 'notes',
                'r.filter_keyword' => 'filter_keyword',
                'r.comment_content' => 'comment_content',
                'r.touch_content' => 'touch_content',
                'r.avatar' => 'target_avatar',
            ])
            ->order('r.create_time', 'desc')
            ->order('r.exec_time', 'desc')
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

    private function getTaskRows(): array
    {
        if ($this->taskRows !== null) {
            return $this->taskRows;
        }

        $rows = $this->baseTaskQuery()
            ->field([
                'dt.id' => 'task_id',
                'dt.time_config' => 'task_time_config',
                'dt.start_time' => 'task_start_time',
                'dt.end_time' => 'task_end_time',
                'dt.account_type' => 'task_account_type',
                'dt.task_scene' => 'task_scene',
                'sa.account_type' => 'scraping_account_type',
                's.id' => 'scraping_id',
                's.task_type' => 'setting_task_type',
                's.industry' => 'setting_industry',
                's.filter' => 'setting_filter',
            ])
            ->order('dt.start_time', 'asc')
            ->order('dt.id', 'asc')
            ->select()
            ->toArray();

        $this->taskRows = $this->uniqueRowsBy($rows, 'task_id');
        return $this->taskRows;
    }

    private function formatItem(array $row): array
    {
        $platform = $this->rowPlatform($row);
        $actionType = $this->rowActionType($row);

        return [
            'id' => (int)($row['record_id'] ?? 0),
            'record_id' => (int)($row['record_id'] ?? 0),
            'task_id' => (int)($row['task_id'] ?? 0),
            'report_task_id' => (string)($row['report_task_id'] ?? ''),
            'scraping_id' => (int)($row['scraping_id'] ?? 0),
            'scraping_account_id' => (int)($row['scraping_account_id'] ?? 0),
            'platform_type' => $platform,
            'platform_name' => $this->platformName($platform),
            'action_type' => $actionType,
            'action_text' => $this->actionText($actionType),
            'account_name' => (string)($row['target_account_name'] ?? ''),
            'account' => (string)($row['target_account'] ?? ''),
            'avatar' => $this->formatFileUrl((string)($row['target_avatar'] ?? '')),
            'image' => $this->formatFileUrl((string)($row['image'] ?? '')),
            'content' => (string)($row['content'] ?? ''),
            'note_title' => (string)($row['note_title'] ?? ''),
            'notes' => (string)($row['notes'] ?? ''),
            'industry_keyword' => (string)($row['industry_keyword'] ?? ''),
            'filter_keyword' => (string)($row['filter_keyword'] ?? ''),
            'comment_content' => (string)($row['comment_content'] ?? ''),
            'touch_content' => (string)($row['touch_content'] ?? ''),
            'likes' => (int)($row['likes'] ?? 0),
            'fans' => (int)($row['fans'] ?? 0),
            'follows' => (int)($row['follows'] ?? 0),
            'address' => (string)($row['address'] ?? ''),
            'pusher_timer' => (string)($row['pusher_timer'] ?? ''),
            'status' => (int)($row['record_status'] ?? 0),
            'status_text' => $this->recordStatusText((int)($row['record_status'] ?? 0)),
            'create_time' => $this->formatTime($row['record_create_time'] ?? ''),
            'exec_time' => $this->formatTime($row['record_exec_time'] ?? ''),
            'executor_account' => (string)($row['scraping_account'] ?? ''),
            'executor_nickname' => (string)(($row['executor_nickname'] ?? '') ?: ($row['task_nickname'] ?? '')),
            'executor_avatar' => $this->formatFileUrl((string)(($row['executor_avatar'] ?? '') ?: ($row['task_avatar'] ?? ''))),
            'device_code' => (string)(($row['record_device_code'] ?? '') ?: ($row['scraping_device_code'] ?? '')),
            'remark' => (string)($row['remark'] ?? ''),
        ];
    }

    private function buildConfig(): array
    {
        $trafficConfig = $this->getTrafficConfig();
        $taskRows = $this->getTaskRows();

        $searchKeywords = $this->normalizeListValue($trafficConfig['acquire_keywords'] ?? []);
        if (empty($searchKeywords)) {
            $searchKeywords = $this->collectTaskValues($taskRows, 'setting_industry');
        }

        $hitKeywords = $this->collectTaskValues($this->getRows(), 'filter_keyword');

        return [
            'config_id' => (int)($trafficConfig['id'] ?? 0),
            'persona_id' => $this->personaId(),
            'search_keywords' => $searchKeywords,
            'hit_keywords' => $hitKeywords,
            'execute_times' => $this->collectExecuteTimes($taskRows),
            'edit_url_key' => 'clue_touch_config',
        ];
    }

    private function buildSummary(array $rows): array
    {
        $summary = [
            'total_count' => count($rows),
            'comment_count' => 0,
            'private_message_count' => 0,
            'like_count' => 0,
        ];

        foreach ($rows as $row) {
            $actionType = (int)($row['action_type'] ?? 0);
            if ($actionType === DeviceEnum::AUTO_TASK_SCENE_COMMENT_COMMENT) {
                $summary['comment_count']++;
                continue;
            }
            if ($actionType === DeviceEnum::AUTO_TASK_SCENE_COMMENT_MSG) {
                $summary['private_message_count']++;
                continue;
            }
            if ($actionType === DeviceEnum::AUTO_TASK_SCENE_MARK_CLUE) {
                $summary['like_count']++;
            }
        }

        return $summary;
    }

    private function buildTabs(array $rows): array
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

    private function applyPlatformFilter(Query $query, bool $ignorePlatform, bool $includeRecord = true): void
    {
        if ($ignorePlatform || $this->platformType() === null) {
            return;
        }

        $platform = $this->platformType();
        $query->where(function ($query) use ($platform, $includeRecord) {
            $query->where('dt.account_type', '=', $platform)
                ->whereOr('sa.account_type', '=', $platform);

            if ($includeRecord) {
                $query->whereOr('r.account_type', '=', $platform)
                    ->whereOr('r.platform', '=', $platform);
            }
        });
    }

    private function applyTaskTypeFilter(Query $query, bool $includeRecord = true): void
    {
        $taskType = $this->taskType();
        if ($taskType === null) {
            return;
        }

        $query->where(function ($query) use ($taskType, $includeRecord) {
            $query->where('dt.task_scene', '=', $taskType)
                ->whereOr('sa.task_type', '=', $taskType)
                ->whereOr('s.task_type', '=', $taskType);

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
                ->whereOr('s.name', 'like', $like);
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

    private function extractTimeRanges($value): array
    {
        $values = $this->flattenValue($value);
        $times = [];

        foreach ($values as $item) {
            $item = trim((string)$item);
            if ($item === '') {
                continue;
            }

            $normalized = $this->normalizeTimeRangeText($item);
            if ($normalized !== '') {
                $times[$normalized] = true;
            }
        }

        return array_keys($times);
    }

    private function normalizeListValue($value): array
    {
        $items = $this->flattenValue($value);
        $values = [];

        foreach ($items as $item) {
            $item = trim((string)$item);
            if ($item === '') {
                continue;
            }

            foreach (preg_split('/[,\n\r，、]+/u', $item) ?: [] as $part) {
                $part = trim((string)$part);
                if ($part !== '') {
                    $values[$part] = true;
                }
            }
        }

        return array_keys($values);
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
                if (is_string($key) && isset($item[0]) && is_scalar($item[0])) {
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

    private function personaId(): int
    {
        return (int)($this->params['persona_id'] ?? 0);
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

    private function rowPlatform(array $row): int
    {
        foreach (['record_platform', 'record_account_type', 'scraping_account_type', 'task_account_type'] as $field) {
            $platform = (int)($row[$field] ?? 0);
            if (in_array($platform, $this->platformTypes(), true)) {
                return $platform;
            }
        }
        return 0;
    }

    private function rowActionType(array $row): int
    {
        foreach (['record_task_type', 'setting_task_type', 'task_scene'] as $field) {
            $taskType = (int)($row[$field] ?? 0);
            if (in_array($taskType, $this->actionTypes(), true)) {
                return $taskType;
            }
        }
        return 0;
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

    private function actionTypes(): array
    {
        return [
            DeviceEnum::AUTO_TASK_SCENE_COMMENT_COMMENT,
            DeviceEnum::AUTO_TASK_SCENE_COMMENT_MSG,
            DeviceEnum::AUTO_TASK_SCENE_MARK_CLUE,
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
            DeviceEnum::AUTO_TASK_SCENE_COMMENT_COMMENT => '评论',
            DeviceEnum::AUTO_TASK_SCENE_COMMENT_MSG => '私信',
            DeviceEnum::AUTO_TASK_SCENE_MARK_CLUE => '点赞',
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
            return date('Y-m-d H:i:s', (int)$value);
        }

        $timestamp = strtotime((string)$value);
        return $timestamp === false ? (string)$value : date('Y-m-d H:i:s', $timestamp);
    }

    private function formatFileUrl(string $url): string
    {
        return $url === '' ? '' : FileService::getFileUrl($url);
    }
}

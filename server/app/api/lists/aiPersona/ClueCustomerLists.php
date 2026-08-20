<?php

namespace app\api\lists\aiPersona;

use app\api\lists\BaseApiDataLists;
use app\common\enum\DeviceEnum;
use app\common\lists\ListsExtendInterface;
use app\common\lists\ListsSearchInterface;
use app\common\model\aiPersona\AiPersonaTrafficConfig;
use app\common\model\sv\SvCrawlingRecord;
use app\common\model\sv\SvDeviceTask;
use app\common\service\FileService;
use think\db\Query;

class ClueCustomerLists extends BaseApiDataLists implements ListsSearchInterface, ListsExtendInterface
{
    private ?array $trafficConfig = null;

    private array $clueType = [
        0 => '/',
        1 => '微信号',
        2 => '手机号',
    ];

    public function setSearch(): array
    {
        return [];
    }

    public function lists(): array
    {
        $rows = $this->baseRecordQuery()
            ->field('r.*, max(r.exec_time) as exectime, ct.name as task_name, dt.time_config as task_time_config, b.keywords as bind_keywords')
            ->group('r.task_id,r.reg_content')
            ->order('exectime', 'desc')
            ->order('r.id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        return array_map(fn($row) => $this->formatItem($row), $rows);
    }

    public function count(): int
    {
        return $this->baseRecordQuery()
            ->group('r.task_id,r.reg_content')
            ->count();
    }

    public function extend(): array
    {
        return [
            'title' => 'B端招商获客',
            'subtitle' => '视频号引流招商',
            'config' => [
                'clue_keywords' => $this->getClueKeywords(),
                'execute_times' => $this->getExecuteTimes(),
            ],
            'summary' => [
                'today_count' => $this->count(),
            ],
        ];
    }

    private function baseRecordQuery(): Query
    {
        $query = SvCrawlingRecord::alias('r')
            ->join('sv_crawling_task ct', 'ct.id = r.task_id and ct.user_id = r.user_id')
            ->join('sv_crawling_task_device_bind b', 'b.task_id = ct.id and b.device_code = r.device_code and b.user_id = r.user_id', 'left')
            ->join('sv_device_task dt', 'dt.sub_task_id = ct.id and dt.device_code = r.device_code and dt.user_id = r.user_id')
            ->where('r.user_id', '=', $this->userId)
            ->where('ct.persona_id', '=', $this->personaId())
            ->where('dt.persona_id', '=', $this->personaId())
            ->where('dt.source', '=', DeviceEnum::TASK_SOURCE_CLUES)
            ->where('dt.task_scene', '=', DeviceEnum::AUTO_TASK_SCENE_SPH_CLUE)
            ->where('dt.day', '=', $this->taskDate())
            ->whereNotNull('r.reg_content')
            ->where('r.reg_content', '<>', '')
            ->whereNotNull('r.hash')
            ->where('r.hash', '<>', '')
            ->whereNull('r.delete_time')
            ->whereNull('ct.delete_time')
            ->whereNull('b.delete_time')
            ->whereNull('dt.delete_time');

        if (!empty($this->params['device_code'])) {
            $query->where('dt.device_code', '=', trim((string)$this->params['device_code']));
        }

        if (isset($this->params['status']) && $this->params['status'] !== '') {
            $query->where('r.status', '=', (int)$this->params['status']);
        }

        if (!empty($this->params['exec_keyword'])) {
            $query->where('r.exec_keyword', '=', trim((string)$this->params['exec_keyword']));
        }

        $keyword = trim((string)($this->params['keyword'] ?? ''));
        if ($keyword !== '') {
            $like = '%' . $keyword . '%';
            $query->where(function ($query) use ($like) {
                $query->where('r.username', 'like', $like)
                    ->whereOr('r.reg_content', 'like', $like)
                    ->whereOr('r.address', 'like', $like)
                    ->whereOr('r.exec_keyword', 'like', $like)
                    ->whereOr('r.crawl_content', 'like', $like)
                    ->whereOr('ct.name', 'like', $like);
            });
        }

        return $query;
    }

    private function taskQuery(): Query
    {
        $query = SvDeviceTask::alias('dt')
            ->join('sv_crawling_task ct', 'ct.id = dt.sub_task_id and ct.user_id = dt.user_id')
            ->where('dt.user_id', '=', $this->userId)
            ->where('dt.persona_id', '=', $this->personaId())
            ->where('ct.persona_id', '=', $this->personaId())
            ->where('dt.source', '=', DeviceEnum::TASK_SOURCE_CLUES)
            ->where('dt.task_scene', '=', DeviceEnum::AUTO_TASK_SCENE_SPH_CLUE)
            ->where('dt.day', '=', $this->taskDate())
            ->whereNull('dt.delete_time')
            ->whereNull('ct.delete_time');

        if (!empty($this->params['device_code'])) {
            $query->where('dt.device_code', '=', trim((string)$this->params['device_code']));
        }

        return $query;
    }

    private function formatItem(array $row): array
    {
        [$wechat, $phone] = $this->splitContact((string)($row['reg_content'] ?? ''), (int)($row['clue_type'] ?? 0));
        $execTime = (string)(($row['exectime'] ?? '') ?: ($row['exec_time'] ?? ''));

        return [
            'id' => (int)($row['id'] ?? 0),
            'task_id' => (int)($row['task_id'] ?? 0),
            'username' => (string)($row['username'] ?? ''),
            'fans_text' => '',
            'address' => (string)($row['address'] ?? ''),
            'wechat' => $wechat,
            'phone' => $phone,
            'reg_content' => (string)($row['reg_content'] ?? ''),
            'clue_type' => (int)($row['clue_type'] ?? 0),
            'clue_type_name' => $this->clueType[(int)($row['clue_type'] ?? 0)] ?? '/',
            'exec_keyword' => (string)($row['exec_keyword'] ?? ''),
            'image' => $this->formatFileUrl((string)($row['image'] ?? '')),
            'exec_time' => $this->formatTime($execTime),
            'device_code' => (string)($row['device_code'] ?? ''),
            'task_name' => (string)($row['task_name'] ?? ''),
        ];
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

    private function getClueKeywords(): array
    {
        $config = $this->getTrafficConfig();
        return $this->arrayValue($config['clue_keywords'] ?? []);
    }

    private function getExecuteTimes(): array
    {
        $values = $this->taskQuery()
            ->column('dt.time_config');

        $times = [];
        foreach ($values as $value) {
            foreach ($this->extractTimeRanges($value) as $time) {
                $times[$time] = true;
            }
        }

        return array_keys($times);
    }

    private function splitContact(string $regContent, int $clueType): array
    {
        $items = $this->splitListText($regContent);
        $phones = [];
        $wechats = [];

        foreach ($items as $item) {
            if (preg_match_all('/1[3-9]\d{9}/', $item, $matches)) {
                foreach ($matches[0] as $phone) {
                    $phones[$phone] = true;
                }
                continue;
            }
            if ($item !== '') {
                $wechats[$item] = true;
            }
        }

        if (empty($phones) && $clueType === 2 && $regContent !== '') {
            $phones[$regContent] = true;
        }
        if (empty($wechats) && $clueType === 1 && $regContent !== '') {
            $wechats[$regContent] = true;
        }

        return [
            implode(',', array_keys($wechats)),
            implode(',', array_keys($phones)),
        ];
    }

    private function splitListText(string $text): array
    {
        $parts = preg_split('/[,，、;\s]+/u', $text) ?: [];
        $items = [];
        foreach ($parts as $part) {
            $part = trim((string)$part);
            if ($part !== '') {
                $items[] = $part;
            }
        }

        return $items;
    }

    private function extractTimeRanges($value): array
    {
        $times = [];
        foreach ($this->flattenValue($value) as $item) {
            $time = $this->normalizeTimeRangeText((string)$item);
            if ($time !== '') {
                $times[$time] = true;
            }
        }

        return array_keys($times);
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

    private function normalizeTimeRangeText(string $timeRange): string
    {
        $timeRange = trim($timeRange);
        if ($timeRange === '') {
            return '';
        }

        $timeRange = str_replace(['至', '～', '~', '–', '—', '－'], '-', $timeRange);
        $timeRange = preg_replace('/\s+/', '', $timeRange) ?: '';

        if (!preg_match('/^(\d{1,2}):(\d{2})-(\d{1,2}):(\d{2})$/', $timeRange, $matches)) {
            return '';
        }

        $start = sprintf('%02d:%02d', min(23, max(0, (int)$matches[1])), min(59, max(0, (int)$matches[2])));
        $end = sprintf('%02d:%02d', min(23, max(0, (int)$matches[3])), min(59, max(0, (int)$matches[4])));

        return $start . ' - ' . $end;
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

    private function personaId(): int
    {
        return (int)($this->params['persona_id'] ?? 0);
    }

    private function taskDate(): string
    {
        return !empty($this->params['date']) ? (string)$this->params['date'] : date('Y-m-d');
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

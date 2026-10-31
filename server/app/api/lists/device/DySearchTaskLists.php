<?php

namespace app\api\lists\device;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\sv\SvAddWechatRecord;
use app\common\model\sv\SvDySearchRecord;
use app\common\model\sv\SvDySearchTask;
use app\common\service\display\DySearchIntentionMapper;
use think\facade\Log;

/**
 * 抖音获客任务列表
 */
class DySearchTaskLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '='      => ['status'],
            '%like%' => ['name'],
        ];
    }

    public function lists(): array
    {
        $query = $this->manualTaskQuery();
        $list = $query
            ->order(['id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
        foreach ($list as &$item) {
            $item['start_time'] = !empty($item['start_time']) ? date('Y-m-d H:i:s', (int)$item['start_time']) : '';
            $item['end_time'] = !empty($item['end_time']) ? date('Y-m-d H:i:s', (int)$item['end_time']) : '';
            $item['clue_total'] = 0;
            $item['added_count'] = 0;
            $item['remain_count'] = 0;
            // 执行进度：已走访过的线索词数 / 任务线索词总数，口径对齐视频号采集任务
            $keywords = is_array($item['keywords'] ?? null) ? $item['keywords'] : [];
            $item['implementation_keywords_number'] = count($keywords);
            $item['number_of_implemented_keywords'] = 0;
        }
        unset($item);

        try {
            $stats = self::loadClueStats(array_column($list, 'id'));
        } catch (\Throwable $e) {
            Log::channel('device')->write('抖音获客列表统计失败：' . $e->getMessage());
            return $list;
        }

        foreach ($list as &$item) {
            $id = (int)($item['id'] ?? 0);
            $row = $stats[$id] ?? ['clue_total' => 0, 'added_count' => 0];
            $item['clue_total'] = (int)$row['clue_total'];
            $item['added_count'] = (int)$row['added_count'];
            $item['remain_count'] = max(0, $item['clue_total'] - $item['added_count']);
        }
        unset($item);

        try {
            $visited = self::loadVisitedKeywords(array_column($list, 'id'));
            foreach ($list as &$item) {
                $id = (int)($item['id'] ?? 0);
                if (!isset($visited[$id])) {
                    continue;
                }
                $keywords = is_array($item['keywords'] ?? null) ? $item['keywords'] : [];
                $item['number_of_implemented_keywords'] = count(array_intersect($keywords, $visited[$id]));
            }
            unset($item);
        } catch (\Throwable $e) {
            Log::channel('device')->write('抖音获客执行进度统计失败：' . $e->getMessage());
        }

        return $list;
    }

    public function count(): int
    {
        return $this->manualTaskQuery()->count();
    }

    /**
     * 手动获客台账：只出手动任务，排除 24h 自动化（source=2 / auto_type=1）。
     */
    private function manualTaskQuery()
    {
        $query = SvDySearchTask::where('user_id', $this->userId);
        if ($this->searchWhere !== []) {
            $query->where($this->searchWhere);
        }
        return $this->applySearchMode($query);
    }

    /**
     * 搜寻模式只认当前 URL 的 query。
     * 出现空的 search_mode= 则不筛，避免 get()/公共参数把 keyword、account 拼进 SQL。
     */
    private function applySearchMode(mixed $query)
    {
        $mode = $this->resolveSearchMode();
        if (in_array($mode, ['keyword', 'account'], true)) {
            $query->where('search_mode', $mode);
        }
        return $query;
    }

    private function resolveSearchMode(): string
    {
        $queryString = $this->rawQueryString();
        if ($queryString !== '') {
            if (preg_match('/(?:^|&)search_mode=(?:&|$)/', $queryString) === 1) {
                return '';
            }
            parse_str($queryString, $qs);
            return trim((string)($qs['search_mode'] ?? ''));
        }
        return trim((string)$this->request->get('search_mode', ''));
    }

    private function rawQueryString(): string
    {
        $uri = (string)$this->request->server('REQUEST_URI', '');
        if ($uri !== '') {
            $fromUri = parse_url($uri, PHP_URL_QUERY);
            if (is_string($fromUri) && $fromUri !== '') {
                return $fromUri;
            }
        }
        $qs = (string)$this->request->query();
        if ($qs !== '') {
            return $qs;
        }
        return (string)($_SERVER['QUERY_STRING'] ?? '');
    }

    /**
     * @param list<int|string> $taskIds
     * @return array<int, array{clue_total: int, added_count: int}>
     */
    private static function loadClueStats(array $taskIds): array
    {
        $ids = [];
        foreach ($taskIds as $taskId) {
            $taskId = (int)$taskId;
            if ($taskId > 0) {
                $ids[] = $taskId;
            }
        }
        $ids = array_values(array_unique($ids));
        if ($ids === []) {
            return [];
        }

        $stats = [];
        foreach ($ids as $id) {
            $stats[$id] = ['clue_total' => 0, 'added_count' => 0];
        }

        $rows = SvDySearchRecord::whereIn('task_id', $ids)
            ->where('result', 1)
            ->where('reg_content', '<>', '')
            ->fieldRaw("task_id, SUM(LENGTH(reg_content) - LENGTH(REPLACE(reg_content, ',', '')) + 1) as clue_total")
            ->group('task_id')
            ->select()
            ->toArray();
        foreach ($rows as $row) {
            $taskId = (int)($row['task_id'] ?? 0);
            if (!isset($stats[$taskId])) {
                continue;
            }
            $stats[$taskId]['clue_total'] = (int)($row['clue_total'] ?? 0);
        }

        $addedRows = SvAddWechatRecord::whereIn('dy_search_task_id', $ids)
            ->where('channel', DySearchIntentionMapper::ADD_CHANNEL)
            ->where('status', 1)
            ->field('dy_search_task_id, count(*) as added_count')
            ->group('dy_search_task_id')
            ->select()
            ->toArray();
        foreach ($addedRows as $row) {
            $taskId = (int)($row['dy_search_task_id'] ?? 0);
            if (isset($stats[$taskId])) {
                $stats[$taskId]['added_count'] = (int)($row['added_count'] ?? 0);
            }
        }

        return $stats;
    }

    /**
     * 执行进度取数：任务下已产生走访记录的去重线索词。
     * 与详情 keywordsCount 的走访口径一致，不看 result（未识别/跳过也算走访过该词）。
     *
     * @param list<int|string> $taskIds
     * @return array<int, list<string>> task_id => 线索词列表
     */
    private static function loadVisitedKeywords(array $taskIds): array
    {
        $ids = [];
        foreach ($taskIds as $taskId) {
            $taskId = (int)$taskId;
            if ($taskId > 0) {
                $ids[$taskId] = $taskId;
            }
        }
        $ids = array_values($ids);
        if ($ids === []) {
            return [];
        }

        $rows = SvDySearchRecord::whereIn('task_id', $ids)
            ->where('exec_keyword', '<>', '')
            ->fieldRaw('task_id, exec_keyword')
            ->group('task_id, exec_keyword')
            ->select()
            ->toArray();
        $visited = [];
        foreach ($rows as $row) {
            $taskId = (int)($row['task_id'] ?? 0);
            $keyword = trim((string)($row['exec_keyword'] ?? ''));
            if ($taskId < 1 || $keyword === '') {
                continue;
            }
            $visited[$taskId][] = $keyword;
        }
        return $visited;
    }
}

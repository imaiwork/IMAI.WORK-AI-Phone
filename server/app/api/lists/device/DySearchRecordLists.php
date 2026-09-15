<?php

namespace app\api\lists\device;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\sv\SvDySearchRecord;

/**
 * 抖音获客走访记录
 */
class DySearchRecordLists extends BaseApiDataLists implements ListsSearchInterface
{
    private array $resultNames = [
        0 => '走访中',
        1 => '已入库',
        2 => '未识别跳过',
        3 => '已私信',
        4 => '初筛跳过',
        5 => '已有跳过',
        6 => '默认号丢弃',
        7 => '日上限跳过',
        8 => '词上限跳过',
    ];

    public function setSearch(): array
    {
        return [
            '=' => ['task_id', 'result', 'exec_keyword', 'device_code'],
        ];
    }

    public function lists(): array
    {
        return SvDySearchRecord::where($this->searchWhere)
            ->where('user_id', $this->userId)
            ->when($this->recordTimeRange() !== null, function ($query) {
                $range = $this->recordTimeRange();
                $query->whereBetween('create_time', $range);
            })
            ->order(['id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                $item['result_name'] = $this->resultNames[(int)$item['result']] ?? '';
            })
            ->toArray();
    }

    public function count(): int
    {
        return SvDySearchRecord::where($this->searchWhere)
            ->where('user_id', $this->userId)
            ->when($this->recordTimeRange() !== null, function ($query) {
                $range = $this->recordTimeRange();
                $query->whereBetween('create_time', $range);
            })
            ->count();
    }

    /**
     * @return array{0:int,1:int}|null
     */
    private function recordTimeRange(): ?array
    {
        $start = trim((string)$this->request->get('start_time', ''));
        $end = trim((string)$this->request->get('end_time', ''));
        if ($start === '' || $end === '') {
            return null;
        }
        $startTs = strtotime($start);
        $endTs = strtotime($end);
        if ($startTs === false || $endTs === false) {
            return null;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $end) === 1) {
            $endTs = strtotime($end . ' 23:59:59');
        }
        return [$startTs, $endTs];
    }
}

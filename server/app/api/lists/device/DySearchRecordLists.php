<?php

namespace app\api\lists\device;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsExcelInterface;
use app\common\lists\ListsSearchInterface;
use app\common\model\sv\SvDySearchRecord;

/**
 * 抖音获客走访记录
 */
class DySearchRecordLists extends BaseApiDataLists implements ListsSearchInterface, ListsExcelInterface
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

    private array $clueTypeNames = [
        0 => '未识别',
        1 => '微信号',
        2 => '手机号',
        3 => 'QQ号',
    ];

    private bool $defaultResultFilterApplied = false;

    public function setSearch(): array
    {
        return [
            '=' => ['task_id', 'result', 'exec_keyword', 'device_code'],
        ];
    }

    public function lists(): array
    {
        $this->applyDefaultResultFilter();
        return SvDySearchRecord::where($this->searchWhere)
            ->where('user_id', $this->userId)
            ->when($this->recordTimeRange() !== null, function ($query) {
                $range = $this->recordTimeRange();
                $query->whereBetween('create_time', $range);
            })
            ->order(['result' => 'asc', 'id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                $item['result_name'] = $this->resultNames[(int)$item['result']] ?? '';
                $item['clue_type_name'] = $this->clueTypeNames[(int)$item['clue_type']] ?? '未识别';
                $item['create_time_str'] = $this->formatVisitTime($item['create_time']);
            })
            ->toArray();
    }

    public function count(): int
    {
        $this->applyDefaultResultFilter();
        return SvDySearchRecord::where($this->searchWhere)
            ->where('user_id', $this->userId)
            ->when($this->recordTimeRange() !== null, function ($query) {
                $range = $this->recordTimeRange();
                $query->whereBetween('create_time', $range);
            })
            ->count();
    }

    /**
     * 默认不展示「默认号丢弃」(result=6) 的走访记录；显式按 result 筛选时不过滤，
     * 保证前端选择“默认号丢弃”作为采集结果筛选条件时仍能查到对应记录
     */
    private function applyDefaultResultFilter(): void
    {
        if ($this->defaultResultFilterApplied) {
            return;
        }
        $this->defaultResultFilterApplied = true;
        if ($this->request->get('result', '') === '') {
            $this->searchWhere[] = ['result', 'not in', [2, 5,6]];
        }
    }

    /**
     * @notes 导出文件名
     * @return string
     */
    public function setFileName(): string
    {
        return '抖音获客走访记录';
    }

    /**
     * @notes 导出字段
     * @return string[]
     */
    public function setExcelFields(): array
    {
        return [
            'exec_keyword' => '执行线索',
            'nickname' => '昵称',
            'dy_id' => '抖音号',
            'fans' => '粉丝数',
            'reg_content' => '线索内容',
            'clue_type_name' => '线索类型',
            'result_name' => '走访状态',
            'create_time_str' => '走访时间',
        ];
    }

    /**
     * create_time 在 ORM 自动时间戳配置下可能返回格式化字符串或裸时间戳，导出前统一格式化
     */
    private function formatVisitTime(mixed $value): string
    {
        if (empty($value)) {
            return '';
        }
        if (is_numeric($value)) {
            return date('Y-m-d H:i:s', (int)$value);
        }
        return (string)$value;
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

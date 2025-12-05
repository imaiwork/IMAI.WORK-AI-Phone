<?php

namespace app\api\lists\sv;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\sv\SvCrawlingManualTaskRecord;

/**
 * 爬取任务列表
 * Class CrawlingManualRecordLists
 */
class CrawlingManualRecordLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '='      => ['user_id', 'type', 'status'],
            '%like%' => ['name'],
        ];
    }
    public function lists(): array
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        $this->searchWhere[] = ['task_id', '=', $this->params['id']];
        return SvCrawlingManualTaskRecord::where($this->searchWhere)->select()->toArray();
    }
    public function count(): int
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        $this->searchWhere[] = ['task_id', '=', $this->params['id']];
        return SvCrawlingManualTaskRecord::where($this->searchWhere)->count();
    }
}
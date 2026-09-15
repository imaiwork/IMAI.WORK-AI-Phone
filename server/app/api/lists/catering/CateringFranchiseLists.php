<?php

namespace app\api\lists\catering;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\catering\CateringFranchise;

class CateringFranchiseLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['category_type', 'status'],
            'like' => ['title'],
        ];
    }

    public function lists(): array
    {
        $list = CateringFranchise::where($this->searchWhere)
            ->append(['category_type_text', 'status_text'])
            ->where('status', 1)
            ->order(['create_time' => 'desc', 'id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()->toArray();

        foreach ($list as &$item) {
         
            $taskTypes = $item['task_types'] ?? [];
            $item['task_types_count'] = is_array($taskTypes) ? count($taskTypes) : 0;
        }

        return $list;
    }

    public function count(): int
    {
        return CateringFranchise::where($this->searchWhere)
            ->where('status', 1)
            ->count();
    }
}

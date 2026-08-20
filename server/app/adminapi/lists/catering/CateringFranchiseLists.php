<?php


namespace app\adminapi\lists\catering;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\lists\ListsSortInterface;
use app\common\model\catering\CateringFranchise;

/**
 * 招商项目列表
 * Class CateringFranchiseLists
 * @package app\adminapi\lists\catering
 */
class CateringFranchiseLists extends BaseAdminDataLists implements ListsSearchInterface, ListsSortInterface
{


    public function setSearch(): array
    {
        return [
            '%like%' => ['title'],
            '=' => ['category_type', 'status']
        ];
    }

    public function setSortFields(): array
    {
        return ['create_time' => 'create_time', 'id' => 'id'];
    }

    public function setDefaultOrder(): array
    {
        return ['create_time' => 'desc', 'id' => 'desc'];
    }

    public function lists(): array
    {
        $list = CateringFranchise::where($this->searchWhere)
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->append(['category_type_text', 'status_text'])
            ->limit($this->limitOffset, $this->limitLength)
            ->order($this->sortOrder)
            ->select()
            ->toArray();

        foreach ($list as &$item) {
            $taskTypes = $item['task_types'] ?? [];
            $item['task_types_count'] = is_array($taskTypes) ? count($taskTypes) : 0;
        }

        return $list;
    }

    public function count(): int
    {
        return CateringFranchise::where($this->searchWhere)->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })->count();
    }

    public function extend()
    {
        return [];
    }
}

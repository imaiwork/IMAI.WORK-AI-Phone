<?php


namespace app\adminapi\lists\tutorial;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\lists\ListsSortInterface;
use app\common\model\tutorial\TutorialCategory;

/**
 * 教程分类列表
 * Class TutorialCategoryLists
 * @package app\adminapi\lists\tutorial
 */
class TutorialCategoryLists extends BaseAdminDataLists implements ListsSearchInterface, ListsSortInterface
{


    public function setSearch(): array
    {
      return [
            '%like%' => ['name'],
            'status' => ['status']
        ];
    }

    public function setSortFields(): array
    {
        return ['create_time' => 'create_time', 'id' => 'id'];
    }

    public function setDefaultOrder(): array
    {
        return ['sort' => 'desc', 'id' => 'desc'];
    }

    public function lists(): array
    {
        return TutorialCategory::where($this->searchWhere)
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->limit($this->limitOffset, $this->limitLength)
            ->order($this->sortOrder)
            ->select()
            ->toArray();
    }

    public function count(): int
    {
        return TutorialCategory::where($this->searchWhere)->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })->count();
    }

    public function extend()
    {
        return [];
    }
}

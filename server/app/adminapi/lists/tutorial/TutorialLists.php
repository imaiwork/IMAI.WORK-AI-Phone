<?php


namespace app\adminapi\lists\tutorial;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\lists\ListsSortInterface;
use app\common\model\tutorial\Tutorial;
use app\common\model\tutorial\TutorialCategory;
/**
 * 教程卡片列表
 * Class TutorialLists
 * @package app\adminapi\lists\tutorial
 */
class TutorialLists extends BaseAdminDataLists implements ListsSearchInterface, ListsSortInterface
{


    public function setSearch(): array
    {
        return [
            '%like%' => ['title'],
            '=' => ['tutorial_category_id', 'main_type', 'status']
        ];
    }

    public function setSortFields(): array
    {
        return ['sort' => 'sort', 'id' => 'id'];
    }

    public function setDefaultOrder(): array
    {
        return ['sort' => 'desc', 'id' => 'desc'];
    }

    public function lists(): array
    {
        return Tutorial::where($this->searchWhere)
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->append(['cate_name'])
            ->limit($this->limitOffset, $this->limitLength)
            ->order($this->sortOrder)
            ->select()->each(function ($item) {
                $item['tutorial_category_name'] = TutorialCategory::where('id',$item['tutorial_category_id'])->value('name') ?? '';
            })
            ->toArray();
    }

    public function count(): int
    {
        return Tutorial::where($this->searchWhere)
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })->count();
    }

    public function extend()
    {
        return [];
    }
}

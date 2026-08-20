<?php

namespace app\adminapi\lists\marketing;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\marketing\MarketingCategory;
use app\common\service\FileService;

class MarketingCategoryLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['status'],
            '%like%' => ['name'],
        ];
    }

    public function lists(): array
    {
        $list = MarketingCategory::where($this->searchWhere)
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->limit($this->limitOffset, $this->limitLength)
            ->order('sort', 'desc')
            ->select()
            ->toArray();
        return $list;
    }

    public function count(): int
    {
        return MarketingCategory::where($this->searchWhere)
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })->count();
    }
}

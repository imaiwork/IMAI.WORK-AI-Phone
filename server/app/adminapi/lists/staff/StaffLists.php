<?php

namespace app\adminapi\lists\staff;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\staff\Staff;


/**
 * 列表
 * Class RechargeLists
 * @package app\Adminapi\lists\staff
 */
class StaffLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 列表
     * @return array
     * @throws @\think\db\exception\DbException
     * @author L
     * @date 2024-07-10 09:40:09
     */
    public function lists(): array
    {
        return Staff::where($this->searchWhere)
            ->order('sort', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                $item['tips'] = json_decode($item['tips']);
            })
            ->toArray();
    }

    /**
     * @notes 统计
     * @return int
     * @throws @\think\db\exception\DbException
     * @author L
     * @date 2024-07-10 09:40:09
     */
    public function count(): int
    {
        return Staff::where($this->searchWhere)->count();
    }

    /**
     * @notes 搜索条件
     * @return array
     * @author L
     * @date 2024-07-10 09:40:09
     */
    public function setSearch(): array
    {
        return [
            "%like%" =>  ['name'],
        ];
    }
}

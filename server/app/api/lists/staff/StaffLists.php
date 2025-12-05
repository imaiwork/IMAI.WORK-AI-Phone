<?php


namespace app\api\lists\staff;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\staff\Staff;


/**
 * 列表
 * Class RechargeLists
 * @package app\api\lists\recharge
 */
class StaffLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            "%like%" => ['name'],
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2023/2/23 18:43
     */
    public function lists(): array
    {
        return Staff::where($this->searchWhere)
            ->order('sort', 'desc')
            ->select()
            ->each(function ($item) {
                $item['tips'] = json_decode($item['tips']);
            })
            ->toArray();
    }


    /**
     * @notes  获取数量
     * @return int
     * @author 段誉
     * @date 2023/2/23 18:43
     */
    public function count(): int
    {
        return Staff::where($this->searchWhere)->count();
    }
}

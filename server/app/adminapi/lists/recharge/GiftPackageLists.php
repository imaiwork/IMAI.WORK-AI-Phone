<?php

namespace app\adminapi\lists\recharge;

use app\common\model\recharge\GiftPackage;
use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;


/**
 * 列表
 * Class RechargeLists
 * @package app\Adminapi\lists\recharge
 */
class GiftPackageLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 列表
     * @return array
     * @throws @\think\db\exception\DbException
     * @author L
     * @date 2024-08-15 15:04:27
     */
    public function lists(): array
    {
        return GiftPackage::where($this->searchWhere)
            ->order('id', 'desc')
            ->json(['package_info'], true)
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
    }

    /**
     * @notes 统计
     * @return int
     * @throws @\think\db\exception\DbException
     * @author L
     * @date 2024-08-15 15:04:27
     */
    public function count(): int
    {
        return GiftPackage::where($this->searchWhere)->count();
    }

    /**
     * @notes 搜索条件
     * @return array
     * @author L
     * @date 2024-08-15 15:04:27
     */
    public function setSearch(): array
    {
        return [
            "%like%" => ['name'],
            '=' => ['status', 'type']
        ];
    }
}

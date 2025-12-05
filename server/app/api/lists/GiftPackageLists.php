<?php

namespace app\api\lists;

use app\common\lists\ListsSearchInterface;
use app\common\model\chat\ChatConversation;
use app\common\model\recharge\GiftPackage;


/**
 * 列表
 * Class RechargeLists
 * @package app\Api\lists\
 */
class GiftPackageLists extends BaseApiDataLists implements ListsSearchInterface
{
    /**
     * @notes 列表
     * @return array
     * @throws @\think\db\exception\DbException
     * @author L
     * @date 2024-08-15 11:45:40
     */
    public function lists(): array
    {
        return GiftPackage::where($this->searchWhere)
            ->json(['package_info'], true)
            ->order(['sort' => 'desc', 'id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
    }

    /**
     * @notes 统计
     * @return int
     * @throws @\think\db\exception\DbException
     * @author L
     * @date 2024-08-15 11:45:40
     */
    public function count(): int
    {
        return GiftPackage::where($this->searchWhere)->count();
    }

    /**
     * @notes 搜索条件
     * @return array
     * @author L
     * @date 2024-08-15 11:45:40
     */
    public function setSearch(): array
    {
        return [
            '=' => ['type']
        ];
    }
}
            
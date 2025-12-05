<?php

namespace app\api\lists;

use app\common\lists\ListsSearchInterface;
use app\common\model\chat\ChatConversation;
use app\common\model\workWeChat\WorkConfig;


/**
 * 列表
 * Class RechargeLists
 * @package app\Api\lists\
 */
class WorkConfigLists extends BaseApiDataLists implements ListsSearchInterface
{
    /**
     * @notes 列表
     * @return array
     * @throws @\think\db\exception\DbException
     * @author L
     * @date 2024-08-22 15:06:34
     */
    public function lists(): array
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        return WorkConfig::where($this->searchWhere)
            ->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
    }

    /**
     * @notes 统计
     * @return int
     * @throws @\think\db\exception\DbException
     * @author L
     * @date 2024-08-22 15:06:34
     */
    public function count(): int
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        return WorkConfig::where($this->searchWhere)->count();
    }

    /**
     * @notes 搜索条件
     * @return array
     * @author L
     * @date 2024-08-22 15:06:34
     */
    public function setSearch(): array
    {
        return [];
    }
}
            
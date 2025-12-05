<?php

namespace app\api\lists\workWeChat;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\workWeChat\WorkWeChat;


/**
 * 列表
 * Class RechargeLists
 * @package app\Api\lists\
 */
class WorkWeChatLists extends BaseApiDataLists implements ListsSearchInterface
{
    /**
     * @notes 列表
     * @return array
     * @throws @\think\db\exception\DbException
     * @author L
     * @date 2024-08-19 11:38:47
     */
    public function lists(): array
    {
        $this->searchWhere[] = ['login_user_id', '=', $this->userId];
        return WorkWeChat::where($this->searchWhere)
            ->order('id', 'desc')
            ->json(['msg'], true)
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
    }

    /**
     * @notes 统计
     * @return int
     * @throws @\think\db\exception\DbException
     * @author L
     * @date 2024-08-19 11:38:47
     */
    public function count(): int
    {
        $this->searchWhere[] = ['login_user_id', '=', $this->userId];
        return WorkWeChat::where($this->searchWhere)->count();
    }

    /**
     * @notes 搜索条件
     * @return array
     * @author L
     * @date 2024-08-19 11:38:47
     */
    public function setSearch(): array
    {
        return [
            "%like%" => [
                'nick_name',
                'real_name',
                'alias'
            ]
        ];
    }
}
            
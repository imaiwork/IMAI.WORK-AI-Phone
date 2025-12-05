<?php

namespace app\api\lists\suno;

use app\common\model\suno\Suno;
use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\chat\ChatConversation;
use app\common\service\FileService;


/**
 * 列表
 * Class RechargeLists
 * @package app\Api\lists\suno
 */
class SunoLists extends BaseApiDataLists implements ListsSearchInterface
{
    /**
     * @notes 列表
     * @return array
     * @throws @\think\db\exception\DbException
     * @author L
     * @date 2024-07-03 10:09:00
     */
    public function lists(): array
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        return Suno::where($this->searchWhere)
            ->json(['json_info'], true)
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
     * @date 2024-07-03 10:09:00
     */
    public function count(): int
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        return Suno::where($this->searchWhere)->count();
    }

    /**
     * @notes 搜索条件
     * @return array
     * @author L
     * @date 2024-07-03 10:09:00
     */
    public function setSearch(): array
    {
        return [];
    }
}
            
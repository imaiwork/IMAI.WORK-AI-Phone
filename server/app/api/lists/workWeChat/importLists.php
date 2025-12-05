<?php

namespace app\api\lists\workWeChat;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\file\File;
use app\common\model\workWeChat\PhoneList;


/**
 * 列表
 * Class RechargeLists
 * @package app\Api\lists\
 */
class importLists extends BaseApiDataLists implements ListsSearchInterface
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
        $this->searchWhere[] = ['type' , '=', 50];
        $this->searchWhere[] = ['source_id', '=', $this->userId];
        return File::where($this->searchWhere)
            ->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->map(function ($data) {
                $data['count'] = PhoneList::where('file_id', $data['id'])->count();
                return $data;
            })
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
        $this->searchWhere[] = ['type' , '=', 50];
        $this->searchWhere[] = ['source_id', '=', $this->userId];
        return File::where($this->searchWhere)->count();
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
                'name',
            ],
        ];
    }
}
            
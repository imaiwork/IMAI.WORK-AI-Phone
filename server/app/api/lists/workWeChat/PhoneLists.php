<?php

namespace app\api\lists\workWeChat;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\workWeChat\PhoneList;
use app\common\model\workWeChat\WorkWeChat;


/**
 * 列表
 * Class RechargeLists
 * @package app\Api\lists\
 */
class PhoneLists extends BaseApiDataLists implements ListsSearchInterface
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
        $this->searchWhere[] = ['login_id', '=', $this->userId];
        return PhoneList::where($this->searchWhere)
            ->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->map(function ($data) {
                if (!empty($data['work_we_chat_id'])) {
                     $data['work_we_chat_name'] = WorkWeChat::where('id', $data['work_we_chat_id'])->value('real_name');
                }
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
        $this->searchWhere[] = ['login_id', '=', $this->userId];
        return PhoneList::where($this->searchWhere)->count();
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
            "=" => ['status', 'work_we_chat_id']
        ];
    }
}
            
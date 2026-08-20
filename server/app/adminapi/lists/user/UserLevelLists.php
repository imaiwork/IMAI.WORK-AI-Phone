<?php

namespace app\adminapi\lists\user;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\user\UserLevel;

/**
 * 用户等级列表
 * Class UserLevelLists
 * @package app\adminapi\lists\user
 */
class UserLevelLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '%like%' => ['level_name'],
        ];
    }

    /**
     * @notes 获取用户等级列表
     * @return array
     */
    public function lists(): array
    {
        return $this->query()
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                $item['name'] = $item['level_name'] ?? '';
                $item['level'] = '第' . $item['sort'] . '级';
            })
            ->toArray();
    }

    /**
     * @notes 获取数量
     * @return int
     */
    public function count(): int
    {
        return $this->query()->count();
    }

    /**
     * @notes 查询构造
     */
    private function query()
    {
        return UserLevel::where($this->searchWhere)
            ->when($this->request->get('name'), function ($query) {
                $query->where('level_name', 'like', '%' . $this->request->get('name') . '%');
            })
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('create_time', [
                    strtotime($this->request->get('start_time')),
                    strtotime($this->request->get('end_time')),
                ]);
            })
            ->order('sort desc, id desc');
    }
}

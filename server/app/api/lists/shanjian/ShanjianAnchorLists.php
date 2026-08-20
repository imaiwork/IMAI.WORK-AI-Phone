<?php

namespace app\api\lists\shanjian;

use app\api\lists\BaseApiDataLists;
use app\api\logic\shanjian\ShanjianAnchorLogic;
use app\common\lists\ListsSearchInterface;
use app\common\model\shanjian\ShanjianAnchor;

class ShanjianAnchorLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['name', 'clone_type'],
            'in' => ['status'],
        ];
    }

    public function lists(): array
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        $this->ensureOrdinaryCloneFilter();
        $list = ShanjianAnchor::where($this->searchWhere)
            ->order(['id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
        return $list;
    }

    public function count(): int
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        $this->ensureOrdinaryCloneFilter();
        return ShanjianAnchor::where($this->searchWhere)->count();
    }

    /**
     * 口播混剪默认取普通（极速）闪剪，含一克三同批极速记录
     */
    private function ensureOrdinaryCloneFilter(): void
    {
        if (isset($this->params['clone_type']) && $this->params['clone_type'] !== '') {
            return;
        }
        $this->searchWhere[] = ['clone_type', '=', ShanjianAnchorLogic::CLONE_TYPE_FAST];
    }
}

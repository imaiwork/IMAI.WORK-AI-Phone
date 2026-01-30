<?php

namespace app\api\lists\sv;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\lists\ListsSortInterface;
use app\common\model\sv\SvMediaMaterial;
use app\common\model\sv\SvMediaMaterialGroup;

class SvMediaMaterialGroupLists extends BaseApiDataLists implements ListsSearchInterface, ListsSortInterface
{
    public function setSearch(): array
    {
        return [
            "%like%" => ['name'],
        ];
    }

    public function setSortFields(): array
    {
        return ['create_time' => 'create_time', 'id' => 'id', 'sort' => 'sort'];
    }

    public function setDefaultOrder(): array
    {
        return ['sort' => 'asc', 'id' => 'asc'];
    }

    public function lists(): array
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        $list = SvMediaMaterialGroup::where($this->searchWhere)
            ->order($this->sortOrder)
            ->limit($this->limitOffset, $this->limitLength)
            ->select()->toArray();

        foreach ($list as $key => $value) {
            $list[$key]['material_count'] = SvMediaMaterial::where('group_id', $value['id'])->where('user_id', $this->userId)->count();
        }

        return $list;
    }

    public function count(): int
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        return SvMediaMaterialGroup::where($this->searchWhere)->count();
    }
}
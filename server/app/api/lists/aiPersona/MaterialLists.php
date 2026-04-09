<?php

namespace app\api\lists\aiPersona;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\aiPersona\Material;
use app\common\model\aiPersona\MaterialUseLog;
use think\facade\Cache as FacadeCache;

class MaterialLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['persona_id', 'material_type', 'use_status', 'publish_mode'],
            'like' => ['material_name'],
        ];
    }

    public function lists(): array
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        $this->searchWhere[] = ['use_status', '<>', 0];

        return Material::where($this->searchWhere)
            ->order(['id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()->each(function ($item) {
                $item->use_num =    MaterialUseLog::where('material_id', $item->id)->where('use_status', 1)->count();
            })
            ->toArray();
    }

    public function count(): int
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        $this->searchWhere[] = ['use_status', '<>', 0];

        return Material::where($this->searchWhere)->count();
    }
}

<?php

namespace app\adminapi\lists\aiPersona;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\aiPersona\Material;
use app\common\model\aiPersona\MaterialUseLog;

class MaterialLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['m.user_id', 'm.persona_id', 'm.material_type', 'm.use_status', 'm.publish_mode'],
            '%like%' => ['m.material_name'],
        ];
    }

    public function where(): array
    {
        $where = [
            ['m.delete_time', '=', null]
        ];

        if (isset($this->params['material_type']) && is_numeric($this->params['material_type'])) {
            $where[] = ['m.material_type', '=', intval($this->params['material_type'])];
        }

        if (isset($this->params['use_status']) && $this->params['use_status'] !== '') {
            $where[] = ['m.use_status', '=', intval($this->params['use_status'])];
        }

        return $where;
    }

    public function lists(): array
    {
        return Material::alias('m')
            ->where($this->where())
            ->where($this->searchWhere)
            ->field('m.*')
            ->limit($this->limitOffset, $this->limitLength)
            ->order('m.id desc')
            ->select()->each(function ($item) {
                $item['use_num'] = MaterialUseLog::where('material_id', $item['id'])->count();
            })
            ->toArray();
    }

    public function count(): int
    {
        return (new Material())->alias('m')
            ->where($this->where())
            ->where($this->searchWhere)
            ->count('m.id');
    }
}

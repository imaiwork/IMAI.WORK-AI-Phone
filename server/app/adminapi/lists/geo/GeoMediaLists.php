<?php

namespace app\adminapi\lists\geo;

use app\adminapi\lists\BaseAdminDataLists;
use app\adminapi\logic\geo\GeoMediaLogic;
use app\common\lists\ListsSearchInterface;
use app\common\model\geo\GeoMedia;

class GeoMediaLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['type', 'status'],
            '%like%' => ['name', 'provider_code', 'category'],
        ];
    }

    public function lists(): array
    {
        $list = GeoMedia::where($this->searchWhere)
            ->limit($this->limitOffset, $this->limitLength)
            ->order(['sort' => 'desc', 'id' => 'desc'])
            ->select()
            ->toArray();
        foreach ($list as &$row) {
            $row['type_text'] = GeoMediaLogic::TYPES[$row['type']] ?? $row['type'];
            $row['create_time'] = $row['create_time'] ? date('Y-m-d H:i:s', (int)$row['create_time']) : '';
            $row['update_time'] = $row['update_time'] ? date('Y-m-d H:i:s', (int)$row['update_time']) : '';
        }
        return $list;
    }

    public function count(): int
    {
        return GeoMedia::where($this->searchWhere)->count();
    }
}

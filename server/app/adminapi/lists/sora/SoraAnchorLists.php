<?php

namespace app\adminapi\lists\sora;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\sora\SoraAnchor;

class SoraAnchorLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '%like%' => ['name'],
        ];
    }

    public function lists(): array
    {
        $status = $this->request->get('status');
        if (isset($status) && $status != ''){
            $status = explode(',', $status);
            $this->searchWhere[] = ['status', 'in', $status];
        }
        $list = SoraAnchor::where($this->searchWhere)
            ->order(['id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
        return $list;
    }

    public function count(): int
    {
        $status = $this->request->get('status');
        if (isset($status) && $status != ''){
            $status = explode(',', $status);
            $this->searchWhere[] = ['status', 'in', $status];
        }
        return SoraAnchor::where($this->searchWhere)->count();
    }
}



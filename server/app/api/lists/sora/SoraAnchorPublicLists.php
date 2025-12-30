<?php

namespace app\api\lists\sora;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\sora\SoraAnchor;

class SoraAnchorPublicLists extends BaseApiDataLists implements ListsSearchInterface
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
        $this->searchWhere[] = ['is_public', '=', 1];
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
        $this->searchWhere[] = ['is_public', '=', 1];
        return SoraAnchor::where($this->searchWhere)->count();
    }
}



<?php

namespace app\adminapi\lists\coze;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\coze\CozeConfig;

class CozeConfigLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['source_id'],
        ];
    }

    public function lists(): array
    {
        $list = CozeConfig::where($this->searchWhere)
            ->where('source', CozeConfig::SOURCE_ADMIN)
            ->order(['create_time' => 'desc'])
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($list as &$item) {
            $item['source_text'] = CozeConfig::getSourceText((int)($item['source'] ?? 0));
        }

        return $list;
    }

    public function count(): int
    {
        return CozeConfig::where($this->searchWhere)
            ->where('source', CozeConfig::SOURCE_ADMIN)
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->count();
    }
}



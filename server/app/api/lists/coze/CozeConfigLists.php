<?php

namespace app\api\lists\coze;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\coze\CozeConfig;

class CozeConfigLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['source'],
        ];
    }

    public function lists(): array
    {
        $where = [
            ['source_id', '=', $this->userId],
        ];

        $list = CozeConfig::where($where)
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
        $where = [
            ['source_id', '=', $this->userId],
        ];

        return CozeConfig::where($where)
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->count();
    }
}



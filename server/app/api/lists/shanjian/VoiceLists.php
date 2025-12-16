<?php

namespace app\api\lists\shanjian;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\human\HumanVoice;

class VoiceLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['name'],
            'in' => ['status'],
        ];
    }

    public function lists(): array
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        $this->searchWhere[] = ['model_version', '', 8];
        $list = HumanVoice::where($this->searchWhere)
            ->order(['id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
        return $list;
    }

    public function count(): int
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        return HumanVoice::where($this->searchWhere)->count();
    }
}



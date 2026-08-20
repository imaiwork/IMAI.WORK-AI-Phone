<?php

namespace app\api\lists\aiPersona;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\aiPersona\SynthesisConfig;
use think\facade\Cache as FacadeCache;

class SynthesisConfigLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['persona_id'],
            'like' => [],
        ];
    }

    public function lists(): array
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        $this->searchWhere[] = ['delete_time', '=', 0];

        return SynthesisConfig::where($this->searchWhere)
            ->order(['id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
    }

    public function count(): int
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        $this->searchWhere[] = ['delete_time', '=', 0];

        return SynthesisConfig::where($this->searchWhere)->count();
    }
}

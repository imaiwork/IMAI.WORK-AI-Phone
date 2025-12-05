<?php

namespace app\api\lists\sv;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\sv\SvMaterial;
use app\common\service\FileService;

class SvMaterialLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['account', 'type', 'm_type'],
        ];
    }

    public function lists(): array
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        $list = SvMaterial::where($this->searchWhere)
            ->order(['sort' => 'asc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($list as &$item) {
            if (in_array($item['m_type'], [1, 2])){
                $item['content']     = FileService::getFileUrl($item['content']);
            }
        }
    }

    public function count(): int
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        return SvMaterial::where($this->searchWhere)->count();
    }
}
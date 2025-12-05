<?php

namespace app\api\lists\sv;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\lists\ListsSortInterface;
use app\common\model\sv\SvMediaMaterial;
use app\common\service\FileService;

class SvMediaMaterialLists extends BaseApiDataLists implements ListsSearchInterface,  ListsSortInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['type', 'm_type'],
            "%like%" => ['name'],
        ];
    }

    public function setSortFields(): array
    {
        return ['create_time' => 'create_time', 'id' => 'id', 'duration' => 'duration', 'size' => 'size'];
    }



    public function setDefaultOrder(): array
    {
        return ['id' => 'desc'];
    }
    public function lists(): array
    {


        $this->searchWhere[] = ['user_id', '=', $this->userId];
        $list = SvMediaMaterial::where($this->searchWhere)
            ->order($this->sortOrder)
            ->limit($this->limitOffset, $this->limitLength)
            ->select()->toArray();

        foreach ($list as &$item) {
            if (in_array($item['m_type'], [1, 2])){
                $item['content'] = $item['content'] ? FileService::getFileUrl($item['content']) : '';
            }
        }
        return  $list;
    }

    public function count(): int
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        return SvMediaMaterial::where($this->searchWhere)->count();
    }
}
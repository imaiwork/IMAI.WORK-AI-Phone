<?php

namespace app\adminapi\lists\oem;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\oem\Oem;
use app\common\service\FileService;

/**
 * oem列表
 * Class OemLists
 * @package app\adminapi\lists\oem
 * @author Lee
 */
class OemLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 列表
     * @return array
     * @throws @\think\db\exception\DbException
     */
    public function lists(): array
    {
        return Oem::field('*')
            ->where($this->searchWhere)
            ->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                $item['logo_url'] =  FileService::getFileUrl($item['logo_url']);
                $item['site_logo'] = is_null($item['site_logo']) ? '' : FileService::getFileUrl($item['site_logo']);
                return $item;
            })
            ->toArray();


            
    }
    /**
     * @notes  获取数量
     * @return int
     */
    public function count(): int
    {
        return Oem::field('*')
            ->where($this->searchWhere)
            ->count();
    }

    /**
     * @notes 搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            "%like%" =>  ['username', 'domain'],
        ];
    }
}

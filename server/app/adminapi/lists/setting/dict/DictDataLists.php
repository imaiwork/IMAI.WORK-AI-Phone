<?php


namespace app\adminapi\lists\setting\dict;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\dict\DictData;


/**
 * 字典数据列表
 * Class DictDataLists
 * @package app\adminapi\lists\dict
 */
class DictDataLists extends BaseAdminDataLists implements ListsSearchInterface
{

    /**
     * @notes 设置搜索条件
     * @return \string[][]
     * @author 段誉
     * @date 2022/6/20 16:29
     */
    public function setSearch(): array
    {
        return [
            '%like%' => ['name', 'type_value'],
            '=' => ['status', 'type_id']
        ];
    }


    /**
     * @notes 获取列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2022/6/20 16:35
     */
    public function lists(): array
    {
        return DictData::where($this->searchWhere)
            ->append(['status_desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->order(['sort' => 'desc', 'id' => 'desc'])
            ->select()
            ->toArray();
    }


    /**
     * @notes 获取数量
     * @return int
     * @author 段誉
     * @date 2022/6/20 16:35
     */
    public function count(): int
    {
        return DictData::where($this->searchWhere)->count();
    }
}

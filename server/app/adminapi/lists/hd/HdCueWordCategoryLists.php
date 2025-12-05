<?php


namespace app\adminapi\lists\hd;


use app\adminapi\lists\BaseAdminDataLists;
use app\common\model\hd\HdCueWord;
use app\common\model\hd\HdCueWordCategory;
use app\common\lists\ListsSearchInterface;


/**
 * HdCueWordCategoryl列表
 * Class HdCueWordCategoryLists
 * @package app\adminapi\listsmp
 */
class HdCueWordCategoryLists extends BaseAdminDataLists implements ListsSearchInterface
{


    /**
     * @desc 设置搜索条件
     * @return array[]
     * @date 2024/5/23 11:52
     * @author dagouzi
     */
    public function setSearch(): array
    {
        return [
            '%like%' => ['title'],
        ];
    }


    /**
     * @desc 获取列表
     * @return array
     * @date 2024/5/23 11:52
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author dagouzi
     */
    public function lists(): array
    {
        return HdCueWordCategory::where($this->searchWhere)
            ->limit($this->limitOffset, $this->limitLength)
            ->order(['sort' => 'desc', 'id' => 'desc'])
            ->select()->each(function ($item) {
                $item->number = HdCueWord::where('cid', $item->id)->count();
            })
            ->toArray();
    }


    /**
     * @desc 获取数量
     * @return int
     * @date 2024/5/23 11:52
     * @throws \think\db\exception\DbException
     * @author dagouzi
     */
    public function count(): int
    {
        return HdCueWordCategory::where($this->searchWhere)->count();
    }
}

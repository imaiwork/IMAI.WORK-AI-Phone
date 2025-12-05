<?php


namespace app\adminapi\lists\hd;


use app\adminapi\lists\BaseAdminDataLists;
use app\common\model\hd\HdCueImage;
use app\common\model\hd\HdCueImageCategory;
use app\common\lists\ListsSearchInterface;


/**
 * HdCueImageCategoryl列表
 * Class HdCueImageCategoryLists
 * @package app\adminapi\listshd
 */
class HdCueImageCategoryLists extends BaseAdminDataLists implements ListsSearchInterface
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
        return HdCueImageCategory::where($this->searchWhere)
            ->limit($this->limitOffset, $this->limitLength)
            ->order(['sort' => 'desc', 'id' => 'desc'])
            ->select() ->each(function ($item) {
                $item->number = HdCueImage::where('cid', $item->id)->count();
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
        return HdCueImageCategory::where($this->searchWhere)->count();
    }
}
